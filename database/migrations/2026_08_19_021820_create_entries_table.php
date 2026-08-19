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
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warung_id')->constrained()->cascadeOnDelete();
            $table->foreignId('debtor_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['debt', 'payment']);
            $table->string('item_description')->nullable();
            $table->unsignedBigInteger('amount')->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('users');
            $table->foreignId('edited_by_user_id')->nullable()->constrained('users');
            $table->boolean('is_voided')->default(false);
            $table->foreignId('voided_by_user_id')->nullable()->constrained('users');
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();

            $table->index(['debtor_id', 'is_voided']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
