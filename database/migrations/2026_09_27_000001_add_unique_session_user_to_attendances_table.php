<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove existing duplicates (keep the earliest record) so the unique index can be created
        $keepIds = DB::table('attendances')
            ->selectRaw('MIN(id) as id')
            ->groupBy('session_id', 'user_id')
            ->pluck('id');

        DB::table('attendances')->whereNotIn('id', $keepIds)->delete();

        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['session_id', 'user_id']);
            $table->string('device_id', 64)->nullable()->after('device_info');
            $table->decimal('latitude', 10, 7)->nullable()->after('device_id');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('distance_meters')->nullable()->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique(['session_id', 'user_id']);
            $table->dropColumn(['device_id', 'latitude', 'longitude', 'distance_meters']);
        });
    }
};
