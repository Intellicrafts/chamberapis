<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lawyers', function (Blueprint $table) {
            if (Schema::hasColumn('lawyers', 'license_number')) {
                $table->renameColumn('license_number', 'enrollment_no');
            }
            if (!Schema::hasColumn('lawyers', 'status')) {
                $table->string('status')->default('pending');
            }
        });
    }

    public function down()
    {
        Schema::table('lawyers', function (Blueprint $table) {
            if (Schema::hasColumn('lawyers', 'enrollment_no')) {
                $table->renameColumn('enrollment_no', 'license_number');
            }
            if (Schema::hasColumn('lawyers', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
