<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('invoice_series')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('invoice_type')->nullable();
            $table->string('currency_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('invoice_series');
            $table->dropColumn('invoice_number');
            $table->dropColumn('invoice_type');
            $table->dropColumn('currency_code');
        });
    }
};
