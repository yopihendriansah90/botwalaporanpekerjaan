<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tenantTables = [
        'work_reports', 'message_schedules', 'message_schedule_slots',
        'whatsapp_connections', 'whatsapp_groups', 'whatsapp_message_logs',
        'work_report_deliveries', 'work_report_whatsapp_group',
        'message_schedule_whatsapp_group',
    ];

    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id'], 'tenant_user_unique');
        });

        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Tenant Utama',
            'slug' => 'tenant-utama',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($this->tenantTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            });

            DB::table($tableName)->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
        }

        $now = now();
        DB::table('users')->orderBy('id')->eachById(function (object $user) use ($tenantId, $now): void {
            DB::table('tenant_user')->insertOrIgnore([
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'role' => 'owner',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        foreach (array_reverse($this->tenantTables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }

        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('tenants');
    }
};
