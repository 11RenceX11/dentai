<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('medication_name');
            $table->string('dosage');                 // e.g. "1 pill"
            $table->json('times');                    // e.g. ["08:00","21:00"]
            $table->date('start_date');
            $table->unsignedSmallInteger('duration_days')->default(1);
            $table->text('instructions')->nullable(); // e.g. "after meals"
            $table->foreignId('prescribed_by')->constrained('dentists')->restrictOnDelete();
            $table->date('prescribed_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
