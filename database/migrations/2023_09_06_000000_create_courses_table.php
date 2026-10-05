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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_code');
            $table->string('course_name');
            $table->string('section');
            $table->string('semester');
            $table->year('year');
            $table->foreignId('lecturer_id')->constrained('users')->onDelete('cascade');
            $table->text('schedule')->nullable();
            $table->string('room')->nullable();
            $table->timestamps();
            
            // Unique constraint for course code, section, semester, and year
            $table->unique(['course_code', 'section', 'semester', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};