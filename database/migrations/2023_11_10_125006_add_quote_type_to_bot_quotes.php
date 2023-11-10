<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuoteTypeToBotQuotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bot_quotes', function (Blueprint $table) {
            $table->string('quote_type', 16)->default('unknown')->after('quote');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bot_quotes', function (Blueprint $table) {
            $table->dropColumn('quote_type');
        });
    }
}
