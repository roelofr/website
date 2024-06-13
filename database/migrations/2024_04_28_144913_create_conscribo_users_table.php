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
        Schema::create('conscribo_users', function (Blueprint $table) {
            $table->id();
            $table->string('conscribo_id', 8)->unique();
            $table->string('conscribo_selector')->index();

            $table->string('first_name');
            $table->string('infix')->nullable();
            $table->string('last_name');

            $table->string('email')->unique();
            $table->text('address')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conscribo_users');
    }
};
