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
        Schema::create('conscribo_group_user', function (Blueprint $table) {
            $table->foreignId('conscribo_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conscribo_group_id')->constrained()->cascadeOnDelete();

            $table->primary(['conscribo_user_id', 'conscribo_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conscribo_group_user');
    }
};
