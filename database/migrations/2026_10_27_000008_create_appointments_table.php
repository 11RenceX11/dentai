<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dentist_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('problem_type_id')->nullable()->constrained('problem_types')->nullOnDelete();
            $table->string('problem_title');
            $table->text('description');
            $table->unsignedTinyInteger('pain_level')->nullable(); // 0-10
            $table->string('urgency')->default('normal'); // low | normal | high | emergency
            $table->date('preferred_date')->nullable();
            $table->date('appointment_date')->nullable()->index();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('status')->default('requested')->index(); // requested | scheduled | completed | cancelled
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable()->index(); // set when marked Done (goes to Records)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
