<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('timezone')->default('Asia/Jakarta');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('work_reports', function (Blueprint $table): void {
            $table->foreignId('message_schedule_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('delivery_mode')->default('manual')->after('status');
        });

        Schema::create('message_schedule_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_schedule_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('send_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['message_schedule_id', 'weekday'], 'schedule_weekday_unique');
        });

        Schema::create('message_schedule_whatsapp_group', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_group_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['message_schedule_id', 'whatsapp_group_id'], 'schedule_group_unique');
        });

        Schema::create('work_report_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_connection_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('status')->default('pending');
            $table->timestamp('dispatched_at')->nullable();
            $table->foreignId('whatsapp_message_log_id')->nullable()->constrained()->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_report_deliveries');
        Schema::dropIfExists('message_schedule_whatsapp_group');
        Schema::dropIfExists('message_schedule_slots');

        Schema::table('work_reports', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('message_schedule_id');
            $table->dropColumn('delivery_mode');
        });

        Schema::dropIfExists('message_schedules');
    }
};
