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
        Schema::create('nft_events', function (Blueprint $table) {
            $table->id()->unsigned();
            $table->uuid('event_id');
            $table->uuid('aggregate_root_id');
            $table->unsignedInteger('version')->nullable();
            $table->jsonb('payload');

            $table->unique(['aggregate_root_id', 'version'], 'reconstitution');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nft_events');
    }
};
