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
        Schema::create('google_mail_list_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('google_mail_list_id')->constrained()->cascadeOnDelete();

            $table->string('action');
            $table->text('data');

            $table->timestamps();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_mail_list_changes');
    }
};
