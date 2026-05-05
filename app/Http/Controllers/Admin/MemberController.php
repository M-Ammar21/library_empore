<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class MemberController extends Controller
{
    public function index(): View
    {
        return view('admin.members');
    }

    public function data(): JsonResponse
    {
        return DataTables::eloquent(Member::query()->select(['id', 'member_code', 'name', 'email', 'phone', 'address', 'created_at'])->latest())
            ->filter(function ($query): void {
                $keyword = request('search.value');

                if (! $keyword) {
                    return;
                }

                $query->where(function ($query) use ($keyword): void {
                    $query->where('member_code', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%")
                        ->orWhere('address', 'like', "%{$keyword}%");
                });
            })
            ->addColumn('action', function (Member $member): string {
                return '<button class="btn btn-info btn-sm btn-icon edit-member" data-id="'.$member->id.'" data-toggle="tooltip" title="Edit"><i class="fas fa-pen"></i></button>
                    <button class="btn btn-danger btn-sm btn-icon delete-member" data-id="'.$member->id.'" data-toggle="tooltip" title="Hapus"><i class="fas fa-trash"></i></button>';
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);
        $data['password'] = Hash::make($data['password']);

        $member = DB::transaction(function () use ($data): Member {
            $member = Member::create($data);
            $member->update([
                'member_code' => $this->makeMemberCode($member),
            ]);

            return $member;
        });

        return response()->json(['success' => true, 'message' => 'Data anggota berhasil dibuat', 'data' => $member], 201);
    }

    public function update(Request $request, Member $member): JsonResponse
    {
        $data = $this->validatedData($request, $member);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        DB::transaction(function () use ($member, $data): void {
            $member->update($data);
        });

        return response()->json(['success' => true, 'message' => 'Data anggota berhasil diperbarui', 'data' => $member]);
    }

    public function destroy(Member $member): JsonResponse
    {
        if ($member->borrowRequests()->exists() || $member->borrowings()->exists()) {
            throw ValidationException::withMessages([
                'member' => 'Anggota tidak bisa dihapus karena sudah memiliki riwayat peminjaman.',
            ]);
        }

        DB::transaction(function () use ($member): void {
            $member->delete();
        });

        return response()->json(['success' => true, 'message' => 'Data anggota berhasil dihapus']);
    }

    private function validatedData(Request $request, ?Member $member = null): array
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('members', 'email')->ignore($member)],
            'password' => [$member ? 'nullable' : 'required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
        ], [
            'name.required' => 'Nama anggota wajib diisi.',
            'email.required' => 'Email anggota wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email anggota sudah digunakan.',
            'password.required' => 'Password wajib diisi saat membuat anggota baru.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        $duplicateExists = Member::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($data['email'])])
            ->when($member, fn ($query) => $query->whereKeyNot($member->id))
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'email' => 'Email anggota sudah digunakan.',
            ]);
        }

        return $data;
    }

    private function makeMemberCode(Member $member): string
    {
        $initials = collect(preg_split('/\s+/', trim($member->name)))
            ->filter()
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->take(2)
            ->implode('');

        return 'AG-'
            .($initials ?: 'MB')
            .'-'
            .$member->created_at->format('Ymd')
            .'-'
            .str_pad((string) $member->id, 4, '0', STR_PAD_LEFT);
    }
}
