<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('exam_name');
            $table->decimal('score', 5, 2);
            $table->decimal('weight', 5, 2)->default(1.0);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('grades');
    }
};
