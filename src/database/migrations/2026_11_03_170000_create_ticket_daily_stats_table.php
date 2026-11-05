<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_daily_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->date('stat_date');
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('resolved_count')->default(0);
            $table->unsignedInteger('first_response_count')->default(0);
            $table->unsignedInteger('sla_on_time_count')->default(0);
            $table->unsignedInteger('sla_evaluated_count')->default(0);
            $table->unsignedInteger('avg_first_response_minutes')->nullable();
            $table->unsignedInteger('avg_resolution_minutes')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'stat_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_daily_stats');
    }
};
