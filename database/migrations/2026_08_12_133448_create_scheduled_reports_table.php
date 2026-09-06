<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('report_type'); // 'grades', 'attendance', 'risk_profiles'
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->default('weekly');
            $table->json('recipients'); // email addresses
            $table->json('filters')->nullable(); // section_id, course_id, risk_level, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('scheduled_reports');
    }
};
