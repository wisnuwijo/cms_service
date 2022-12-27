<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->uuid('product_id');
            $table->enum('type',['in','out']);
            $table->uuid('transaction_id');
            $table->integer('stock_change_amount')->length(10);
            $table->integer('stock_before')->length(10);
            $table->integer('stock_after')->length(10);
            $table->text('note')->nullable();
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
        Schema::dropIfExists('stock_histories');
    }
}
