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
        Schema::create('conscribo_organisations', function (Blueprint $table) {
            $table->id();
            $table->string('conscribo_id', 8)->unique();

            $table->string('name');

            $table->timestamps();
            $table->timestamp('contract_ends_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conscribo_organisations');
    }
};
