<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('subscription_token')->nullable()->after('pay_timing');
            $table->unsignedInteger('contract_months')->default(12)->after('subscription_token');
        });
    }

    public function down(): void {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['subscription_token', 'contract_months']);
        });
    }
};
