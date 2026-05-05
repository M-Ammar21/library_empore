<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['code' => 'BK001', 'title' => 'Laravel Dasar', 'publication_year' => 2024, 'author' => 'Budi Santoso', 'stock' => 10],
            ['code' => 'BK002', 'title' => 'PHP Modern', 'publication_year' => 2023, 'author' => 'Andi Wijaya', 'stock' => 5],
            ['code' => 'BK003', 'title' => 'Database Praktis', 'publication_year' => 2022, 'author' => 'Sari Permata', 'stock' => 3],
            ['code' => 'BK004', 'title' => 'Algoritma dan Struktur Data', 'publication_year' => 2021, 'author' => 'Rizky Mahendra', 'stock' => 8],
            ['code' => 'BK005', 'title' => 'Pemrograman Web Interaktif', 'publication_year' => 2025, 'author' => 'Nadia Putri', 'stock' => 7],
            ['code' => 'BK006', 'title' => 'Manajemen Basis Data', 'publication_year' => 2020, 'author' => 'Hendra Gunawan', 'stock' => 4],
            ['code' => 'BK007', 'title' => 'Desain UI untuk Aplikasi', 'publication_year' => 2024, 'author' => 'Maya Lestari', 'stock' => 6],
            ['code' => 'BK008', 'title' => 'Keamanan Aplikasi Web', 'publication_year' => 2023, 'author' => 'Doni Pratama', 'stock' => 5],
            ['code' => 'BK009', 'title' => 'Analisis Sistem Informasi', 'publication_year' => 2022, 'author' => 'Lina Kartika', 'stock' => 9],
            ['code' => 'BK010', 'title' => 'REST API dengan Laravel', 'publication_year' => 2025, 'author' => 'Fajar Nugroho', 'stock' => 6],
            ['code' => 'BK011', 'title' => 'JavaScript Modern', 'publication_year' => 2024, 'author' => 'Intan Puspita', 'stock' => 12],
            ['code' => 'BK012', 'title' => 'Pengantar Cloud Computing', 'publication_year' => 2021, 'author' => 'Arif Setiawan', 'stock' => 4],
        ])->each(fn (array $book): Book => Book::query()->updateOrCreate(
            ['code' => $book['code']],
            $book
        ));
    }
}
