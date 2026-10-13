<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'institution_id']);
        });

        $roleIds = DB::table('roles')->whereRaw('LOWER(name) = ?', ['admin'])->pluck('id');
        if ($roleIds->isEmpty()) {
            return;
        }

        $adminIds = DB::table('model_has_roles')
            ->whereIn('role_id', $roleIds)
            ->where('model_type', User::class)
            ->pluck('model_id');

        $now = now();

        foreach ($adminIds as $adminId) {
            $user = DB::table('users')->where('id', $adminId)->first();
            if (!$user) {
                continue;
            }

            $institutionIds = [];
            if ($user->institution_id) {
                $institutionIds[] = (int) $user->institution_id;
            }

            // The seeded system administrator is responsible for both demo institutions.
            if ($user->email === 'admin@sba.local') {
                $named = DB::table('institutions')
                    ->whereIn('name', [
                        'Al-Hikma International University',
                        'Al-Amal Secondary School',
                    ])
                    ->pluck('id');
                foreach ($named as $institutionId) {
                    $institutionIds[] = (int) $institutionId;
                }
            }

            foreach (array_unique($institutionIds) as $institutionId) {
                DB::table('institution_admins')->insertOrIgnore([
                    'user_id' => $adminId,
                    'institution_id' => $institutionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_admins');
    }
};
