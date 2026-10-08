<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('type', 20)->default('family')->after('slug');
            $table->string('badge', 60)->nullable()->after('type');
            $table->unsignedInteger('included_users')->nullable()->after('commission');
            $table->decimal('extra_price', 10, 2)->default(0)->after('included_users');
        });
    }

    public function down(): void {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['type', 'badge', 'included_users', 'extra_price']);
        });
    }
};
