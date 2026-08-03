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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number_of_meals');
            $table->unsignedBigInteger('budget');
            $table->string('prep_time');
            $table->unsignedBigInteger('estimated_cost');
            // $table->boolean('accepted')->default(false); // لسا ماقررت الغيها ولا خليها
            $table->unsignedTinyInteger('days_per_meal');
            $table->unsignedTinyInteger('servings');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
