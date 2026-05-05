<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('member_code')->nullable()->after('id');
        });

        DB::table('members')
            ->orderBy('id')
            ->get(['id', 'name', 'created_at'])
            ->each(function ($member): void {
                DB::table('members')
                    ->where('id', $member->id)
                    ->update([
                        'member_code' => $this->makeMemberCode($member->name, $member->created_at, $member->id),
                    ]);
            });

        Schema::table('members', function (Blueprint $table) {
            $table->unique('member_code');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['member_code']);
            $table->dropColumn('member_code');
        });
    }

    private function makeMemberCode(string $name, ?string $createdAt, int $id): string
    {
        $initials = collect(preg_split('/\s+/', trim($name)))
            ->filter()
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->take(2)
            ->implode('');

        $date = $createdAt ? date('Ymd', strtotime($createdAt)) : now()->format('Ymd');

        return 'AG-'.($initials ?: 'MB').'-'.$date.'-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }
};
