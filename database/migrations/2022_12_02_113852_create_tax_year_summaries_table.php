<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tax_year_summaries', function (Blueprint $table) {
            $table->string('tax_year_id', 9)->primary();
            $table->string('currency', 3);
            $table->json('capital_gain')->default('{"cost_basis":"0","proceeds":"0","difference":"0"}');
            $table->string('income')->default('0');
            $table->string('non_attributable_allowable_cost')->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tax_year_summaries');
    }
};
