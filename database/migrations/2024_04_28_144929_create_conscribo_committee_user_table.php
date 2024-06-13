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
        Schema::create('conscribo_committee_user', function (Blueprint $table) {
            $table->foreignId('conscribo_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conscribo_committee_id')->constrained()->cascadeOnDelete();

            $table->string('role')->nullable();
            $table->boolean('is_owner')->default(false);

            $table->primary([
                'conscribo_user_id',
                'conscribo_committee_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conscribo_committee_user');
    }
};
