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
        Schema::create('google_mail_lists', function (Blueprint $table) {
            $table->id();

            $table->string('directory_id')->nullable()->unique();
            $table->string('conscribo_id', 8)->nullable()->unique();

            $table->foreignId('conscribo_committee_id')->nullable()->constrained('conscribo_committees')->nullOnDelete();

            $table->string('name');
            $table->string('email');
            $table->json('aliases');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_mail_lists');
    }
};
