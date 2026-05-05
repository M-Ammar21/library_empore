<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BorrowRequest;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BorrowRequestController extends Controller
{
    public function create(): View
    {
        $view = Auth::guard('member')->check()
            ? 'member.borrow-request-auth'
            : 'member.borrow-request';

        return view($view, [
            'books' => Book::query()
                ->where('stock', '>', 0)
                ->orderBy('title')
                ->limit(9)
                ->get(['id', 'code', 'title', 'publication_year', 'author', 'stock', 'cover_image'])
                ->map(fn (Book $book): array => $this->bookPickerPayload($book))
                ->values(),
            'member' => Auth::guard('member')->user(),
        ]);
    }

    public function latestRequests(Request $request): JsonResponse
    {
        $member = Auth::guard('member')->user();

        if (! $member) {
            $memberCode = trim((string) $request->query('member_code', ''));
            $member = Member::query()
                ->where('member_code', $memberCode)
                ->first();
        }

        if (! $member) {
            return response()->json([
                'data' => [],
            ]);
        }

        $requests = BorrowRequest::query()
            ->with('book:id,code,title')
            ->where('member_id', $member->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (BorrowRequest $request): array => [
                'id' => $request->id,
                'book_label' => $request->book->code.' - '.$request->book->title,
                'borrow_date' => $request->borrow_date->format('Y-m-d'),
                'return_date' => $request->return_date->format('Y-m-d'),
                'status' => $request->status,
            ])
            ->values();

        return response()->json([
            'data' => $requests,
        ]);
    }

    public function searchBooks(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query('q', ''));
        $page = max(1, $request->integer('page', 1));
        $perPage = 9;
        $version = Cache::get('books_picker_cache_version', 1);
        $cacheKey = 'books_picker:v'.$version.':'.md5($keyword).':page:'.$page;

        $result = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($keyword, $page, $perPage): array {
            $books = Book::query()
                ->when($keyword, function ($query) use ($keyword): void {
                    $query->where(function ($query) use ($keyword): void {
                        $query->where('code', 'like', "%{$keyword}%")
                            ->orWhere('title', 'like', "%{$keyword}%")
                            ->orWhere('author', 'like', "%{$keyword}%")
                            ->orWhere('publication_year', 'like', "%{$keyword}%");
                    });
                })
                ->orderBy('title')
                ->paginate($perPage, ['id', 'code', 'title', 'publication_year', 'author', 'stock', 'cover_image'], 'page', $page);

            return [
                'data' => $books->getCollection()
                    ->map(fn (Book $book): array => $this->bookPickerPayload($book))
                    ->values()
                    ->all(),
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'total' => $books->total(),
            ];
        });

        return response()->json($result);
    }

    public function store(Request $request): JsonResponse
    {
        $authenticatedMember = Auth::guard('member')->user();

        $data = $request->validate([
            'member_code' => [$authenticatedMember ? 'nullable' : 'required', 'exists:members,member_code'],
            'book_ids' => ['required', 'array', 'min:1', 'max:5'],
            'book_ids.*' => ['integer', 'distinct', 'exists:books,id'],
            'borrow_date' => ['required', 'date'],
            'return_date' => ['required', 'date', 'after_or_equal:borrow_date'],
        ], [
            'member_code.required' => 'ID anggota wajib diisi.',
            'member_code.exists' => 'ID anggota tidak ditemukan.',
            'book_ids.required' => 'Minimal satu buku wajib dipilih.',
            'book_ids.array' => 'Pilihan buku tidak valid.',
            'book_ids.min' => 'Minimal satu buku wajib dipilih.',
            'book_ids.max' => 'Maksimal 5 buku dalam satu pengajuan.',
            'book_ids.*.exists' => 'Salah satu buku tidak ditemukan.',
            'book_ids.*.distinct' => 'Buku yang sama tidak boleh dipilih lebih dari sekali.',
            'borrow_date.required' => 'Tanggal peminjaman wajib diisi.',
            'return_date.required' => 'Tanggal pengembalian wajib diisi.',
            'return_date.after_or_equal' => 'Tanggal pengembalian tidak boleh sebelum tanggal peminjaman.',
        ]);

        $member = $authenticatedMember ?: Member::query()
            ->where('member_code', $data['member_code'])
            ->firstOrFail();
        $books = Book::query()
            ->whereIn('id', $data['book_ids'])
            ->get(['id', 'code', 'title', 'stock']);

        $unavailableBooks = $books
            ->filter(fn (Book $book): bool => $book->stock < 1)
            ->map(fn (Book $book): string => $book->code.' - '.$book->title)
            ->values();

        if ($unavailableBooks->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Buku berikut tidak tersedia: '.$unavailableBooks->join(', '),
            ], 422);
        }

        $borrowRequests = DB::transaction(function () use ($data, $member): array {
            return collect($data['book_ids'])
                ->map(fn (int $bookId): BorrowRequest => BorrowRequest::create([
                    'member_id' => $member->id,
                    'book_id' => $bookId,
                    'borrow_date' => $data['borrow_date'],
                    'return_date' => $data['return_date'],
                    'status' => 'pending',
                ]))
                ->all();
        });

        return response()->json([
            'success' => true,
            'message' => count($borrowRequests).' pengajuan peminjaman berhasil dibuat',
            'data' => $borrowRequests,
        ], 201);
    }

    private function bookPickerPayload(Book $book): array
    {
        return [
            'id' => $book->id,
            'code' => $book->code,
            'title' => $book->title,
            'publication_year' => $book->publication_year,
            'author' => $book->author,
            'stock' => $book->stock,
            'cover_image_url' => $book->cover_image ? '/storage/'.ltrim($book->cover_image, '/') : null,
        ];
    }
}
