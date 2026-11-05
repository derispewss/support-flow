<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number', 32);
            $table->string('subject', 150);
            $table->text('description');
            $table->string('status', 20)->default('new');
            $table->string('priority', 20)->default('normal');
            $table->string('channel', 20)->default('web');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sla_policy_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('first_response_due_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('sla_breached_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->unsignedTinyInteger('satisfaction_rating')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'ticket_number']);
            $table->index(['organization_id', 'status', 'priority']);
            $table->index(['organization_id', 'assignee_id', 'status']);
            $table->index(['organization_id', 'requester_id']);
            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'due_at']);
            $table->index(['organization_id', 'sla_breached_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
