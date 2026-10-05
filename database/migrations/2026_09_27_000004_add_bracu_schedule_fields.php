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
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedBigInteger('bracu_section_id')->nullable()->unique()->after('id');
            $table->string('course_type', 20)->nullable()->after('course_name');
            $table->string('faculty_initials')->nullable()->after('lecturer_id');
            $table->json('class_schedules')->nullable()->after('schedule');
            $table->date('class_start_date')->nullable()->after('class_schedules');
            $table->date('class_end_date')->nullable()->after('class_start_date');
            $table->timestamp('synced_at')->nullable()->after('class_end_date');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('faculty_initial', 10)->nullable()->unique()->after('student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique(['bracu_section_id']);
            $table->dropColumn([
                'bracu_section_id', 'course_type', 'faculty_initials', 'class_schedules',
                'class_start_date', 'class_end_date', 'synced_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['faculty_initial']);
            $table->dropColumn('faculty_initial');
        });
    }
};
