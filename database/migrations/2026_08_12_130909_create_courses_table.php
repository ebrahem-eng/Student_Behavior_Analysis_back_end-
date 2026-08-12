<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->integer('credits')->default(3);
            $table->timestamps();
            
            $table->unique(['institution_id', 'code']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('courses');
    }
};
