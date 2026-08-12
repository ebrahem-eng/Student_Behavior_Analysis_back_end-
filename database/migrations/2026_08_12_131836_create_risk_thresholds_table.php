<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('risk_thresholds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->enum('level', ['low', 'medium', 'high']);
            $table->decimal('min_score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->boolean('requires_action')->default(false);
            $table->timestamps();
            
            $table->unique(['institution_id', 'level']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('risk_thresholds');
    }
};
