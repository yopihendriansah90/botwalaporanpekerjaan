<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('account_type')->default('user')->after('password');
        });

        DB::table('users')->where('email', 'admin@mail.com')->update(['account_type' => 'superadmin']);

        $defaultTenantId = DB::table('tenants')->where('slug', 'tenant-utama')->value('id');
        $users = DB::table('users')->where('account_type', 'user')->orderBy('id')->get();

        foreach ($users as $user) {
            $slug = 'user-'.$user->id;
            $tenantId = DB::table('tenants')->where('slug', $slug)->value('id');

            if (! $tenantId) {
                $tenantId = DB::table('tenants')->insertGetId([
                    'name' => 'Workspace '.$user->name,
                    'slug' => $slug,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('tenant_user')->where('user_id', $user->id)->delete();
            DB::table('tenant_user')->insert([
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'role' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('work_reports')
                ->where('user_id', $user->id)
                ->update(['tenant_id' => $tenantId]);
        }

        // Resource tanpa pemilik eksplisit tetap berada di workspace utama
        // dan hanya dapat dilihat oleh superadmin sampai dibuat ulang oleh user.
        if ($defaultTenantId) {
            foreach ([
                'message_schedules', 'message_schedule_slots', 'whatsapp_connections',
                'whatsapp_groups', 'whatsapp_message_logs', 'work_report_deliveries',
                'work_report_whatsapp_group', 'message_schedule_whatsapp_group',
            ] as $table) {
                DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('account_type');
        });
    }
};
