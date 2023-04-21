<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSetAddressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('set_address', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('client_id');
            $table->string('token');
            $table->timestamp("created_at");
            $table->timestamp("expired_at");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('set_address');
    }
}
