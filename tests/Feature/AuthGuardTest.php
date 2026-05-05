<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Book;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_session_cannot_open_admin_routes(): void
    {
        $member = Member::query()->create([
            'member_code' => 'AG-TEST-20260505-0001',
            'name' => 'Anggota Test',
            'email' => 'member.test@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($member, 'member')
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_session_cannot_open_member_routes(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin Test',
            'email' => 'admin.test@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('member.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_public_borrow_request_page_does_not_require_auth(): void
    {
        $this->get(route('member.borrow-request'))
            ->assertOk()
            ->assertSee('Ajukan Peminjaman')
            ->assertSee('ID Anggota');
    }

    public function test_public_borrow_request_uses_member_code_to_create_request(): void
    {
        $member = Member::query()->create([
            'member_code' => 'AG-TEST-20260505-0002',
            'name' => 'Anggota Pinjam',
            'email' => 'borrower.test@example.com',
            'password' => 'password',
        ]);

        $book = Book::query()->create([
            'code' => 'BK-TEST-001',
            'title' => 'Buku Test',
            'publication_year' => 2026,
            'author' => 'Penulis Test',
            'stock' => 3,
        ]);

        $this->postJson(route('member.borrow-request.store'), [
            'member_code' => $member->member_code,
            'book_ids' => [$book->id],
            'borrow_date' => '2026-05-05',
            'return_date' => '2026-05-12',
        ])->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('borrow_requests', [
            'member_id' => $member->id,
            'book_id' => $book->id,
            'status' => 'pending',
        ]);
    }

    public function test_public_borrow_request_rejects_more_than_five_books(): void
    {
        $member = Member::query()->create([
            'member_code' => 'AG-TEST-20260505-0003',
            'name' => 'Anggota Banyak Buku',
            'email' => 'many-books.test@example.com',
            'password' => 'password',
        ]);

        $bookIds = collect(range(1, 6))
            ->map(fn (int $number): int => Book::query()->create([
                'code' => 'BK-MAX-'.$number,
                'title' => 'Buku Maksimal '.$number,
                'publication_year' => 2026,
                'author' => 'Penulis Test',
                'stock' => 3,
            ])->id)
            ->all();

        $this->postJson(route('member.borrow-request.store'), [
            'member_code' => $member->member_code,
            'book_ids' => $bookIds,
            'borrow_date' => '2026-05-05',
            'return_date' => '2026-05-12',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('book_ids');
    }

    public function test_authenticated_member_borrow_request_does_not_need_member_code(): void
    {
        $member = Member::query()->create([
            'member_code' => 'AG-TEST-20260505-0004',
            'name' => 'Anggota Login',
            'email' => 'logged-member.test@example.com',
            'password' => 'password',
        ]);

        $book = Book::query()->create([
            'code' => 'BK-AUTH-001',
            'title' => 'Buku Auth',
            'publication_year' => 2026,
            'author' => 'Penulis Auth',
            'stock' => 3,
        ]);

        $this->actingAs($member, 'member')
            ->get(route('member.borrow-request'))
            ->assertOk()
            ->assertSee('Pengajuan diproses dari akun yang sedang login.')
            ->assertDontSee('ID Anggota');

        $this->actingAs($member, 'member')
            ->postJson(route('member.borrow-request.store'), [
                'book_ids' => [$book->id],
                'borrow_date' => '2026-05-05',
                'return_date' => '2026-05-12',
            ])->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('borrow_requests', [
            'member_id' => $member->id,
            'book_id' => $book->id,
            'status' => 'pending',
        ]);
    }
}
