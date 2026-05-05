<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    public function index(): JsonResponse
    {
        $books = Book::query()
            ->latest()
            ->get()
            ->map(fn (Book $book): array => $this->bookResource($book))
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Data buku berhasil diambil',
            'data' => $books,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $book = Book::create($this->storeData($request));

        return response()->json([
            'success' => true,
            'message' => 'Data buku berhasil dibuat',
            'data' => $this->bookResource($book),
        ], 201);
    }

    public function show(string $code): JsonResponse
    {
        $book = Book::where('code', $code)->first();

        if (! $book) {
            return response()->json([
                'success' => false,
                'message' => 'Data buku tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data buku berhasil ditemukan',
            'data' => $this->bookResource($book),
        ]);
    }

    public function update(Request $request, string $code): JsonResponse
    {
        $book = Book::where('code', $code)->first();

        if (! $book) {
            return response()->json([
                'success' => false,
                'message' => 'Data buku tidak ditemukan',
            ], 404);
        }

        $book->update($this->updateData($request));

        return response()->json([
            'success' => true,
            'message' => 'Data buku berhasil diperbarui',
            'data' => $this->bookResource($book->refresh()),
        ]);
    }

    public function destroy(string $code): JsonResponse
    {
        $book = Book::where('code', $code)->first();

        if (! $book) {
            return response()->json([
                'success' => false,
                'message' => 'Data buku tidak ditemukan',
            ], 404);
        }

        $book->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data buku berhasil dihapus',
        ]);
    }

    private function storeData(Request $request): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:255', Rule::unique('books', 'code')],
            'title' => ['required', 'string', 'max:255'],
            'publication_year' => ['required', 'integer'],
            'author' => ['required', 'string', 'max:255'],
            'stock' => ['required', 'integer', 'min:0'],
        ]);
    }

    private function updateData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'publication_year' => ['required', 'integer'],
            'author' => ['required', 'string', 'max:255'],
            'stock' => ['required', 'integer', 'min:0'],
        ]);
    }

    private function bookResource(Book $book): array
    {
        return [
            'code' => $book->code,
            'title' => $book->title,
            'publication_year' => $book->publication_year,
            'author' => $book->author,
            'stock' => $book->stock,
        ];
    }
}
