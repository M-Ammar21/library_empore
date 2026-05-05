<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\BorrowRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $member = Auth::guard('member')->user();
        $trendDates = collect(range(6, 0))->map(
            fn (int $day): CarbonImmutable => now()->toImmutable()->subDays($day)
        );

        $topBorrowedBooks = Book::query()
            ->withCount(['borrowings' => fn ($query) => $query->where('member_id', $member->id)])
            ->whereHas('borrowings', fn ($query) => $query->where('member_id', $member->id))
            ->orderByDesc('borrowings_count')
            ->orderBy('title')
            ->limit(5)
            ->get(['id', 'code', 'title']);

        return view('member.dashboard', [
            'stats' => [
                'activeBorrowings' => Borrowing::query()->where('member_id', $member->id)->where('status', 'borrowed')->count(),
                'pendingRequests' => BorrowRequest::query()->where('member_id', $member->id)->where('status', 'pending')->count(),
                'totalHistory' => Borrowing::query()->where('member_id', $member->id)->count(),
                'availableBooks' => Book::query()->where('stock', '>', 0)->count(),
            ],
            'recommendedBooks' => Book::query()
                ->where('stock', '>', 0)
                ->orderByDesc('stock')
                ->orderBy('title')
                ->limit(8)
                ->get(['id', 'code', 'title', 'author', 'stock']),
            'latestBorrowings' => Borrowing::query()
                ->with('book:id,code,title')
                ->where('member_id', $member->id)
                ->latest()
                ->limit(5)
                ->get(),
            'trend' => [
                'labels' => $trendDates
                    ->map(fn (CarbonImmutable $date): string => $date->translatedFormat('d M'))
                    ->all(),
                'requests' => $trendDates
                    ->map(fn (CarbonImmutable $date): int => BorrowRequest::query()->where('member_id', $member->id)->whereDate('created_at', $date)->count())
                    ->all(),
                'borrowings' => $trendDates
                    ->map(fn (CarbonImmutable $date): int => Borrowing::query()->where('member_id', $member->id)->whereDate('borrow_date', $date)->count())
                    ->all(),
                'returns' => $trendDates
                    ->map(fn (CarbonImmutable $date): int => Borrowing::query()->where('member_id', $member->id)->whereDate('actual_return_date', $date)->count())
                    ->all(),
            ],
            'insights' => [
                'requestStatuses' => [
                    'labels' => ['Pending', 'Disetujui', 'Ditolak'],
                    'data' => [
                        BorrowRequest::query()->where('member_id', $member->id)->where('status', 'pending')->count(),
                        BorrowRequest::query()->where('member_id', $member->id)->where('status', 'approved')->count(),
                        BorrowRequest::query()->where('member_id', $member->id)->where('status', 'rejected')->count(),
                    ],
                ],
                'borrowingStatuses' => [
                    'labels' => ['Dipinjam', 'Dikembalikan'],
                    'data' => [
                        Borrowing::query()->where('member_id', $member->id)->where('status', 'borrowed')->count(),
                        Borrowing::query()->where('member_id', $member->id)->where('status', 'returned')->count(),
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
