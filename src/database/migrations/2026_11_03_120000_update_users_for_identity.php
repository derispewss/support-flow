<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->string('timezone', 64)->default('UTC')->after('password');
            $table->string('locale', 10)->default('en')->after('timezone');
            $table->string('avatar_path')->nullable()->after('locale');
            $table->boolean('is_active')->default(true)->after('avatar_path');
            $table->timestamp('last_seen_at')->nullable()->after('is_active');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['timezone', 'locale', 'avatar_path', 'is_active', 'last_seen_at']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
