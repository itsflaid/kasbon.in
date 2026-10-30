<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debtors', function (Blueprint $table) {
            $table->index('warung_id', 'debtors_warung_id_index');
        });

        Schema::table('debtors', function (Blueprint $table) {
            $table->dropIndex(['warung_id', 'normalized_name']);
            $table->unique(['warung_id', 'normalized_name']);
        });
    }

    public function down(): void
    {
        Schema::table('debtors', function (Blueprint $table) {
            $table->dropUnique(['warung_id', 'normalized_name']);
            $table->index(['warung_id', 'normalized_name']);
        });

        Schema::table('debtors', function (Blueprint $table) {
            $table->dropIndex('debtors_warung_id_index');
        });
    }
};
