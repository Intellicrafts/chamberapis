<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLawyersCasesTable extends Migration
{
    public function up()
    {
        Schema::create('lawyers_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->uuid('lawyer_id');
            $table->foreign('lawyer_id')->references('id')->on('lawyers')->onDelete('cascade');
            $table->string('casename');
            $table->uuid('category_id');
            $table->foreign('category_id')->references('id')->on('lawyer_categories')->onDelete('cascade');
            $table->timestamps(); // includes created_at and updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('lawyers_cases');
    }
}

