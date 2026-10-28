<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row = "the patient took this dose". Missing row = not taken (yet).
        Schema::create('medication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->date('dose_date');
            $table->string('dose_time', 5);
            $table->timestamp('taken_at');
            $table->timestamps();
            $table->unique(['prescription_id', 'dose_date', 'dose_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_logs');
    }
};
