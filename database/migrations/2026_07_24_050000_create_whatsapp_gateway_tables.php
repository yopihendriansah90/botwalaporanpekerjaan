<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_connections', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('status')->default('disconnected');
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('whatsapp_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('whatsapp_connection_id')->constrained()->cascadeOnDelete();
            $table->string('jid');
            $table->string('name');
            $table->unsignedInteger('participants_count')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['whatsapp_connection_id', 'jid']);
        });

        Schema::create('whatsapp_message_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('whatsapp_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_report_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('whatsapp_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_jid');
            $table->longText('message');
            $table->string('status')->default('pending');
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_logs');
        Schema::dropIfExists('whatsapp_groups');
        Schema::dropIfExists('whatsapp_connections');
    }
};
