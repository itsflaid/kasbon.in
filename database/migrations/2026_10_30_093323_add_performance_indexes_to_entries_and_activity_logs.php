<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->index(['warung_id', 'is_voided', 'created_at'], 'entries_warung_active_created_index');
            $table->index(['debtor_id', 'is_voided', 'type'], 'entries_debtor_active_type_index');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['warung_id', 'created_at'], 'activity_logs_warung_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropIndex('entries_warung_active_created_index');
            $table->dropIndex('entries_debtor_active_type_index');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_warung_created_index');
        });
    }
};
