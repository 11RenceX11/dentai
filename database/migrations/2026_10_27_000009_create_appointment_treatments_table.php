<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_id')->constrained()->restrictOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('dentists')->restrictOnDelete();
            $table->string('tooth_number')->default('');
            $table->decimal('cost_at_time', 10, 2)->default(0);
            $table->string('status')->default('planned'); // planned | in_progress | completed | cancelled
            $table->text('clinical_notes')->nullable();
            $table->text('recommendations')->nullable();
            $table->timestamps();
            $table->unique(['appointment_id', 'treatment_id', 'tooth_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_treatments');
    }
};
