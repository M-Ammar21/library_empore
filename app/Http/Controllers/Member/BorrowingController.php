<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BorrowingController extends Controller
{
    public function index(): View
    {
        return view('member.borrowings', [
            'member' => Auth::guard('member')->user(),
        ]);
    }

    public function data(): JsonResponse
    {
        $memberId = Auth::guard('member')->id();

        return DataTables::eloquent(
            Borrowing::query()
                ->select('borrowings.*')
                ->latest()
                ->with('book:id,code,title')
                ->where('member_id', $memberId)
        )
            ->filter(function ($query): void {
                $keyword = request('search.value');

                if (! $keyword) {
                    return;
                }

                $query->where(function ($query) use ($keyword): void {
                    $query->where('status', 'like', "%{$keyword}%")
                        ->orWhere('borrow_date', 'like', "%{$keyword}%")
                        ->orWhere('return_date', 'like', "%{$keyword}%")
                        ->orWhere('actual_return_date', 'like', "%{$keyword}%")
                        ->orWhereHas('book', function ($query) use ($keyword): void {
                            $query->where('code', 'like', "%{$keyword}%")
                                ->orWhere('title', 'like', "%{$keyword}%");
                        });
                });
            })
            ->addColumn('book_label', fn (Borrowing $borrowing): string => $borrowing->book->code.' - '.$borrowing->book->title)
            ->editColumn('borrow_date', fn (Borrowing $borrowing): string => $borrowing->borrow_date->format('Y-m-d'))
            ->editColumn('return_date', fn (Borrowing $borrowing): string => $borrowing->return_date->format('Y-m-d'))
            ->editColumn('actual_return_date', fn (Borrowing $borrowing): string => $borrowing->actual_return_date?->format('Y-m-d') ?? '-')
            ->addColumn('status_badge', function (Borrowing $borrowing): string {
                $class = $borrowing->status === 'returned' ? 'success' : 'info';

                return '<span class="badge badge-'.$class.'">'.$borrowing->status.'</span>';
            })
            ->rawColumns(['status_badge'])
            ->toJson();
    }
}
