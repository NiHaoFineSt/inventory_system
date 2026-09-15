<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade')->after('id');
            }
            if (!Schema::hasColumn('audit_logs', 'action')) {
                $table->string('action')->after('user_id');
            }
            if (!Schema::hasColumn('audit_logs', 'asset_name')) {
                $table->string('asset_name')->nullable()->after('action');
            }
            if (!Schema::hasColumn('audit_logs', 'description')) {
                $table->text('description')->after('asset_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'action', 'asset_name', 'description']);
        });
    }
};
