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
        Schema::create('tax_year_events', function (Blueprint $table) {
            $table->id()->unsigned();
            $table->uuid('event_id');
            $table->string('aggregate_root_id', 9);
            $table->unsignedInteger('version')->nullable();
            $table->jsonb('payload');

            $table->unique(['aggregate_root_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tax_year_events');
    }
};
