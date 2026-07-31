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
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dataset_id')->unique();
            $table->string('name');
            $table->unsignedTinyInteger('servings');
            $table->string('prep_time');
            $table->string('difficulty_level');
            $table->string('seasonality');
            // $table->enum('prep_time', ['قليل', 'متوسط', 'طويل']);
            // $table->enum('difficulty_level', ['مبتدئ', 'متوسط', 'محترف']);
            // $table->enum('seasonality', ['الصيف', 'الشتاء', 'الربيع', 'الخريف', 'مدار السنة']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
