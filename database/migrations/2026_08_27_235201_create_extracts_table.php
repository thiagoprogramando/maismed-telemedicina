<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::create('extracts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('title');
            $table->string('description');
            $table->decimal('value', 12, 2)->default(0);
            $table->json('payment_features')->nullable();
            $table->string('payment_token');
            $table->string('payment_url');
            $table->date('payment_date');
            $table->enum('type', ['commission', 'payment', 'credit', 'debit']);
            $table->enum('status', ['paid', 'pendent', 'canceled'])->default('pendent');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('extracts');
    }
};
