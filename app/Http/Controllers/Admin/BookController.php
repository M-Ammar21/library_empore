<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BookController extends Controller
{
    public function index(): View
    {
        return view('admin.books');
    }

    public function data(): JsonResponse
    {
        return DataTables::eloquent(
            Book::query()
                ->select(['id', 'code', 'title', 'publication_year', 'author', 'stock', 'cover_image', 'created_at'])
                ->latest()
                ->withExists([
                    'borrowings as has_active_borrowings' => fn ($query) => $query->where('status', 'borrowed'),
                ])
        )
            ->filter(function ($query): void {
                $keyword = request('search.value');

                if (! $keyword) {
                    return;
                }

                $query->where(function ($query) use ($keyword): void {
                    $query->where('code', 'like', "%{$keyword}%")
                        ->orWhere('title', 'like', "%{$keyword}%")
                        ->orWhere('publication_year', 'like', "%{$keyword}%")
                        ->orWhere('author', 'like', "%{$keyword}%")
                        ->orWhere('stock', 'like', "%{$keyword}%");
                });
            })
            ->addColumn('stock_badge', function (Book $book): string {
                $class = $book->stock <= 1 ? 'danger' : 'success';

                return '<span class="badge badge-'.$class.'">'.$book->stock.'</span>';
            })
            ->addColumn('cover_image_url', fn (Book $book): ?string => $book->cover_image ? '/storage/'.ltrim($book->cover_image, '/') : null)
            ->addColumn('action', function (Book $book): string {
                return '<button class="btn btn-info btn-sm btn-icon edit-book" data-id="'.$book->id.'" data-toggle="tooltip" title="Edit"><i class="fas fa-pen"></i></button>
                    <button class="btn btn-danger btn-sm btn-icon delete-book" data-id="'.$book->id.'" data-toggle="tooltip" title="Hapus"><i class="fas fa-trash"></i></button>';
            })
            ->rawColumns(['stock_badge', 'action'])
            ->toJson();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);

        $book = DB::transaction(function () use ($request, $data): Book {
            $data['cover_image'] = $this->storeCoverImage($request);

            return Book::create($data);
        });

        $this->clearBookPickerCache();

        return response()->json([
            'success' => true,
            'message' => 'Data buku berhasil dibuat',
            'data' => $book,
        ], 201);
    }

    public function update(Request $request, Book $book): JsonResponse
    {
        $data = $this->validatedData($request, $book);

        $this->ensureBookCanBeUpdated($book, $data);

        DB::transaction(function () use ($request, $book, $data): void {
            if ($request->hasFile('cover_image')) {
                $this->deleteCoverImage($book);
                $data['cover_image'] = $this->storeCoverImage($request);
            }

            $book->update($data);
        });

        $this->clearBookPickerCache();

        return response()->json([
            'success' => true,
            'message' => 'Data buku berhasil diperbarui',
            'data' => $book,
        ]);
    }

    public function destroy(Book $book): JsonResponse
    {
        if ($book->borrowRequests()->exists() || $book->borrowings()->exists()) {
            throw ValidationException::withMessages([
                'book' => 'Buku tidak bisa dihapus karena sudah memiliki riwayat peminjaman.',
            ]);
        }

        DB::transaction(function () use ($book): void {
            $this->deleteCoverImage($book);
            $book->delete();
        });

        $this->clearBookPickerCache();

        return response()->json([
            'success' => true,
            'message' => 'Data buku berhasil dihapus',
        ]);
    }

    private function validatedData(Request $request, ?Book $book = null): array
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:255', Rule::unique('books', 'code')->ignore($book)],
            'title' => ['required', 'string', 'max:255'],
            'publication_year' => ['required', 'integer'],
            'author' => ['required', 'string', 'max:255'],
            'stock' => ['required', 'integer', 'min:0'],
            'cover_image' => ['nullable', File::image()->max('2mb')],
        ], [
            'code.required' => 'Kode buku wajib diisi.',
            'code.unique' => 'Kode buku sudah digunakan.',
            'title.required' => 'Judul buku wajib diisi.',
            'publication_year.required' => 'Tahun terbit wajib diisi.',
            'publication_year.integer' => 'Tahun terbit harus berupa angka.',
            'author.required' => 'Penulis wajib diisi.',
            'stock.required' => 'Stok buku wajib diisi.',
            'stock.integer' => 'Stok buku harus berupa angka.',
            'stock.min' => 'Stok buku tidak boleh bernilai negatif.',
            'cover_image.image' => 'Cover buku harus berupa gambar.',
            'cover_image.max' => 'Ukuran cover buku maksimal 2MB.',
        ]);

        $duplicateExists = Book::query()
            ->whereRaw('LOWER(code) = ?', [strtolower($data['code'])])
            ->when($book, fn ($query) => $query->whereKeyNot($book->id))
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'code' => 'Kode buku sudah digunakan.',
            ]);
        }

        return $data;
    }

    private function ensureBookCanBeUpdated(Book $book, array $data): void
    {
        $hasActiveBorrowing = $book->borrowings()
            ->where('status', 'borrowed')
            ->exists();

        if (! $hasActiveBorrowing) {
            return;
        }

        $messages = [];

        if ($data['code'] !== $book->code) {
            $messages['code'] = 'Kode buku tidak bisa diubah karena buku sedang dipinjam.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    private function storeCoverImage(Request $request): ?string
    {
        if (! $request->hasFile('cover_image')) {
            return null;
        }

        return $request->file('cover_image')->store('book-covers', 'public');
    }

    private function deleteCoverImage(Book $book): void
    {
        if ($book->cover_image) {
            Storage::disk('public')->delete($book->cover_image);
        }
    }

    private function clearBookPickerCache(): void
    {
        if (! Cache::has('books_picker_cache_version')) {
            Cache::forever('books_picker_cache_version', 1);

            return;
        }

        Cache::increment('books_picker_cache_version');
    }
}
