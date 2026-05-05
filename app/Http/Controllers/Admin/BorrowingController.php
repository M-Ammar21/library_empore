<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BorrowingController extends Controller
{
    public function index(): View
    {
        return view('admin.borrowings');
    }

    public function data(): JsonResponse
    {
        return DataTables::eloquent(
            Borrowing::query()
                ->select('borrowings.*')
                ->latest()
                ->with(['member:id,member_code,name', 'book:id,code,title,stock'])
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
                        ->orWhereHas('member', function ($query) use ($keyword): void {
                            $query->where('member_code', 'like', "%{$keyword}%")
                                ->orWhere('name', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('book', function ($query) use ($keyword): void {
                            $query->where('code', 'like', "%{$keyword}%")
                                ->orWhere('title', 'like', "%{$keyword}%");
                        });
                });
            })
            ->addColumn('member_code', fn (Borrowing $borrowing): string => $borrowing->member->member_code)
            ->addColumn('member_name', fn (Borrowing $borrowing): string => $borrowing->member->name)
            ->addColumn('book_label', fn (Borrowing $borrowing): string => $borrowing->book->code.' - '.$borrowing->book->title)
            ->addColumn('book_stock', fn (Borrowing $borrowing): int => $borrowing->book->stock)
            ->editColumn('borrow_date', fn (Borrowing $borrowing): string => $borrowing->borrow_date->format('Y-m-d'))
            ->editColumn('return_date', fn (Borrowing $borrowing): string => $borrowing->return_date->format('Y-m-d'))
            ->editColumn('actual_return_date', fn (Borrowing $borrowing): string => $borrowing->actual_return_date?->format('Y-m-d') ?? '-')
            ->addColumn('status_badge', function (Borrowing $borrowing): string {
                $class = $borrowing->status === 'returned' ? 'success' : 'info';

                return '<span class="badge badge-'.$class.'">'.$borrowing->status.'</span>';
            })
            ->addColumn('action', function (Borrowing $borrowing): string {
                if ($borrowing->status === 'returned') {
                    return '<span class="text-muted">Selesai</span>';
                }

                return '<button class="btn btn-success btn-sm mark-returned" data-id="'.$borrowing->id.'"><i class="fas fa-rotate-left mr-1"></i> Returned</button>';
            })
            ->rawColumns(['status_badge', 'action'])
            ->toJson();
    }

    public function markReturned(Borrowing $borrowing): JsonResponse
    {
        if ($borrowing->status === 'returned') {
            return response()->json(['success' => false, 'message' => 'Buku sudah dikembalikan'], 422);
        }

        DB::transaction(function () use ($borrowing): void {
            $book = $borrowing->book()->lockForUpdate()->firstOrFail();

            $borrowing->update([
                'actual_return_date' => now()->toDateString(),
                'status' => 'returned',
            ]);

            $book->increment('stock');
        });

        return response()->json(['success' => true, 'message' => 'Status peminjaman berhasil diperbarui']);
    }
}
