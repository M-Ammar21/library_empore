<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use App\Models\BorrowRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BorrowRequestController extends Controller
{
    public function index(): View
    {
        return view('admin.borrow-requests');
    }

    public function data(): JsonResponse
    {
        return DataTables::eloquent(
            BorrowRequest::query()
                ->select('borrow_requests.*')
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
            ->addColumn('member_code', fn (BorrowRequest $request): string => $request->member->member_code)
            ->addColumn('member_name', fn (BorrowRequest $request): string => $request->member->name)
            ->addColumn('book_label', fn (BorrowRequest $request): string => $request->book->code.' - '.$request->book->title)
            ->addColumn('book_stock', fn (BorrowRequest $request): int => $request->book->stock)
            ->editColumn('borrow_date', fn (BorrowRequest $request): string => $request->borrow_date->format('Y-m-d'))
            ->editColumn('return_date', fn (BorrowRequest $request): string => $request->return_date->format('Y-m-d'))
            ->addColumn('status_badge', function (BorrowRequest $request): string {
                $class = match ($request->status) {
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'warning',
                };

                return '<span class="badge badge-'.$class.'">'.$request->status.'</span>';
            })
            ->addColumn('action', function (BorrowRequest $request): string {
                if ($request->status !== 'pending') {
                    return '<span class="text-muted">Sudah diproses</span>';
                }

                return '<button class="btn btn-success btn-sm approve-request" data-id="'.$request->id.'" data-toggle="tooltip" title="Approve"><i class="fas fa-check mr-1"></i> Approve</button>
                    <button class="btn btn-danger btn-sm reject-request" data-id="'.$request->id.'" data-toggle="tooltip" title="Reject"><i class="fas fa-xmark mr-1"></i> Reject</button>';
            })
            ->rawColumns(['status_badge', 'action'])
            ->toJson();
    }

    public function approve(Request $request, BorrowRequest $borrowRequest): JsonResponse
    {
        if ($borrowRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Pengajuan sudah diproses'], 422);
        }

        DB::transaction(function () use ($borrowRequest): void {
            $book = $borrowRequest->book()->lockForUpdate()->firstOrFail();

            if ($book->stock < 1) {
                throw ValidationException::withMessages([
                    'book' => 'Stok buku tidak tersedia.',
                ]);
            }

            $book->decrement('stock');

            $borrowRequest->update([
                'status' => 'approved',
                'approved_by' => Auth::guard('admin')->id(),
                'approved_at' => now(),
            ]);

            Borrowing::create([
                'borrow_request_id' => $borrowRequest->id,
                'member_id' => $borrowRequest->member_id,
                'book_id' => $borrowRequest->book_id,
                'borrow_date' => $borrowRequest->borrow_date,
                'return_date' => $borrowRequest->return_date,
                'status' => 'borrowed',
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Pengajuan peminjaman disetujui']);
    }

    public function reject(Request $request, BorrowRequest $borrowRequest): JsonResponse
    {
        if ($borrowRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Pengajuan sudah diproses'], 422);
        }

        DB::transaction(function () use ($borrowRequest): void {
            $borrowRequest->update([
                'status' => 'rejected',
                'approved_by' => Auth::guard('admin')->id(),
                'approved_at' => now(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Pengajuan peminjaman ditolak']);
    }
}
