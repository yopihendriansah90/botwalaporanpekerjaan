<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_reports', function (Blueprint $table): void {
            $table->string('status')->default('draft')->change();
            $table->timestamp('sent_at')->nullable()->after('status');
            $table->text('send_error')->nullable()->after('sent_at');
        });

        Schema::create('work_report_whatsapp_group', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_group_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['work_report_id', 'whatsapp_group_id'], 'work_report_group_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_report_whatsapp_group');

        Schema::table('work_reports', function (Blueprint $table): void {
            $table->dropColumn(['sent_at', 'send_error']);
            $table->string('status')->default('completed')->change();
        });
    }
};
