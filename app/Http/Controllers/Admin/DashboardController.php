<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\BorrowRequest;
use App\Models\Member;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $trendDates = collect(range(6, 0))->map(
            fn (int $day): CarbonImmutable => now()->toImmutable()->subDays($day)
        );

        $topBorrowedBooks = Book::query()
            ->withCount('borrowings')
            ->has('borrowings')
            ->orderByDesc('borrowings_count')
            ->orderBy('title')
            ->limit(5)
            ->get(['id', 'code', 'title']);

        return view('admin.dashboard', [
            'stats' => [
                'books' => Book::query()->count(),
                'members' => Member::query()->count(),
                'pendingRequests' => BorrowRequest::query()->where('status', 'pending')->count(),
                'activeBorrowings' => Borrowing::query()->where('status', 'borrowed')->count(),
            ],
            'latestRequests' => BorrowRequest::query()
                ->with(['member:id,member_code,name', 'book:id,code,title'])
                ->latest()
                ->limit(5)
                ->get(),
            'lowStockBooks' => Book::query()
                ->where('stock', '<=', 5)
                ->orderBy('stock')
                ->orderBy('title')
                ->limit(5)
                ->get(['id', 'code', 'title', 'stock']),
            'trend' => [
                'labels' => $trendDates
                    ->map(fn (CarbonImmutable $date): string => $date->translatedFormat('d M'))
                    ->all(),
                'requests' => $trendDates
                    ->map(fn (CarbonImmutable $date): int => BorrowRequest::query()->whereDate('created_at', $date)->count())
                    ->all(),
                'borrowings' => $trendDates
                    ->map(fn (CarbonImmutable $date): int => Borrowing::query()->whereDate('borrow_date', $date)->count())
                    ->all(),
                'returns' => $trendDates
                    ->map(fn (CarbonImmutable $date): int => Borrowing::query()->whereDate('actual_return_date', $date)->count())
                    ->all(),
            ],
            'insights' => [
                'requestStatuses' => [
                    'labels' => ['Pending', 'Disetujui', 'Ditolak'],
                    'data' => [
                        BorrowRequest::query()->where('status', 'pending')->count(),
                        BorrowRequest::query()->where('status', 'approved')->count(),
                        BorrowRequest::query()->where('status', 'rejected')->count(),
                    ],
                ],
                'stockHealth' => [
                    'labels' => ['Habis', 'Kritis', 'Rendah', 'Aman'],
                    'data' => [
                        Book::query()->where('stock', 0)->count(),
                        Book::query()->whereBetween('stock', [1, 2])->count(),
                        Book::query()->whereBetween('stock', [3, 5])->count(),
                        Book::query()->where('stock', '>', 5)->count(),
                    ],
                ],
                'topBorrowedBooks' => [
                    'labels' => $topBorrowedBooks
                        ->map(fn (Book $book): string => "{$book->code} - {$book->title}")
                        ->all(),
                    'data' => $topBorrowedBooks
                        ->map(fn (Book $book): int => $book->borrowings_count)
                        ->all(),
                ],
            ],
        ]);
    }
}
