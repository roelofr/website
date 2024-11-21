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
        Schema::create('order_ticket', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('ticket_id')->constrained()->restrictOnDelete();
            $table->foreignId('event_id');

            // Store without case
            $table->string('barcode')->nullable();
            $table->string('barcode_type', 40)->default('qrcode');

            $table->unsignedSmallInteger('price')->nullable();
            $table->text('data');

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'event_id',
                'barcode',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_ticket');
    }
};
