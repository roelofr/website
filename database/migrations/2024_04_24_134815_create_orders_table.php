<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();

            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('status')->default('pending');

            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->string('name');
            $table->string('email');

            $table->unsignedSmallInteger('amount')->nullable();
            $table->unsignedTinyInteger('fee')->nullable();
            $table->unsignedSmallInteger('total_amount')->nullable();

            $table->string('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
