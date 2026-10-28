<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dentist_treatment', function (Blueprint $table) {
            $table->foreignId('dentist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
            $table->date('certified_date')->nullable();
            $table->string('proficiency_level')->default('competent'); // trainee | competent | proficient | expert
            $table->boolean('is_active')->default(true);
            $table->primary(['dentist_id', 'treatment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dentist_treatment');
    }
};
