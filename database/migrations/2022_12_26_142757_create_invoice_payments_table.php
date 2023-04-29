<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->string('account_number')->length(120);
            $table->string('account_number_owner')->length(120);
            $table->uuid('invoice_id');
            $table->uuid('transaction_id');
            $table->enum('status',["waiting_verification","payment_invalid","payment_valid"]);
            $table->double('amount');
            $table->string('transfer_slip_img')->length(150);
            $table->uuid('created_by');
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
        Schema::dropIfExists('invoice_payments');
    }
}
