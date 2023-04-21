<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendingTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pending_transactions', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->string('client_phone_number');
            $table->string('wa_msg_id');
            $table->text('wa_msg_payload');
            $table->string('product_list');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pending_transaction');
    }
}
