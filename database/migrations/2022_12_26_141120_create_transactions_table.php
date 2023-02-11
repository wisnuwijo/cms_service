<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->uuid('client_id');
            $table->enum('status',["unpaid","prepare_for_delivery","expired","canceled","delivery","delivered","success"]);
            $table->double('delivery_fee');
            $table->double('tax_percentage');
            $table->double('total_before_tax');
            $table->double('total_after_tax');
            $table->double('final_price');
            $table->timestamps();
            $table->softDeletes('deleted_at', 0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
