<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_index_returns_prd_json_shape(): void
    {
        Book::query()->create([
            'code' => 'BK001',
            'title' => 'Laravel Dasar',
            'publication_year' => 2024,
            'author' => 'Budi Santoso',
            'stock' => 10,
            'cover_image' => 'covers/laravel.jpg',
        ]);

        $this->getJson('/api/books')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Data buku berhasil diambil',
                'data' => [
                    [
                        'code' => 'BK001',
                        'title' => 'Laravel Dasar',
                        'publication_year' => 2024,
                        'author' => 'Budi Santoso',
                        'stock' => 10,
                    ],
                ],
            ])
            ->assertJsonMissingPath('data.0.id')
            ->assertJsonMissingPath('data.0.cover_image')
            ->assertJsonMissingPath('data.0.created_at');
    }

    public function test_book_can_be_found_by_code(): void
    {
        Book::query()->create([
            'code' => 'BK001',
            'title' => 'Laravel Dasar',
            'publication_year' => 2024,
            'author' => 'Budi Santoso',
            'stock' => 10,
        ]);

        $this->getJson('/api/books/BK001')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Data buku berhasil ditemukan',
                'data' => [
                    'code' => 'BK001',
                    'title' => 'Laravel Dasar',
                    'publication_year' => 2024,
                    'author' => 'Budi Santoso',
                    'stock' => 10,
                ],
            ]);
    }

    public function test_book_not_found_returns_prd_error_shape(): void
    {
        $this->getJson('/api/books/BK404')
            ->assertNotFound()
            ->assertExactJson([
                'success' => false,
                'message' => 'Data buku tidak ditemukan',
            ]);
    }

    public function test_book_can_be_created(): void
    {
        $payload = [
            'code' => 'BK002',
            'title' => 'PHP Modern',
            'publication_year' => 2023,
            'author' => 'Andi Wijaya',
            'stock' => 5,
        ];

        $this->postJson('/api/books', $payload)
            ->assertCreated()
            ->assertExactJson([
                'success' => true,
                'message' => 'Data buku berhasil dibuat',
                'data' => $payload,
            ]);

        $this->assertDatabaseHas('books', $payload);
    }

    public function test_book_can_be_updated_without_changing_code(): void
    {
        Book::query()->create([
            'code' => 'BK002',
            'title' => 'PHP Modern',
            'publication_year' => 2023,
            'author' => 'Andi Wijaya',
            'stock' => 5,
        ]);

        $payload = [
            'title' => 'PHP Modern Edisi Revisi',
            'publication_year' => 2025,
            'author' => 'Andi Wijaya',
            'stock' => 8,
        ];

        $this->putJson('/api/books/BK002', $payload)
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Data buku berhasil diperbarui',
                'data' => [
                    'code' => 'BK002',
                    ...$payload,
                ],
            ]);

        $this->assertDatabaseHas('books', [
            'code' => 'BK002',
            ...$payload,
        ]);
    }

    public function test_book_can_be_deleted(): void
    {
        $book = Book::query()->create([
            'code' => 'BK002',
            'title' => 'PHP Modern',
            'publication_year' => 2023,
            'author' => 'Andi Wijaya',
            'stock' => 5,
        ]);

        $this->deleteJson('/api/books/BK002')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Data buku berhasil dihapus',
            ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }
}
