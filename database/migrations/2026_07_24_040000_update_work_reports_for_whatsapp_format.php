<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_reports', function (Blueprint $table): void {
            $table->string('officer_name')->nullable()->after('user_id');
            $table->json('tasks')->nullable()->after('officer_name');
            $table->string('title')->nullable()->change();
            $table->text('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('work_reports', function (Blueprint $table): void {
            $table->dropColumn(['officer_name', 'tasks']);
            $table->string('title')->nullable(false)->change();
            $table->text('description')->nullable(false)->change();
        });
    }
};
