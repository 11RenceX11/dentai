<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone_number')->index();
            $table->string('email')->nullable();
            $table->string('gender'); // male | female | other | prefer_not_to_say
            $table->date('date_of_birth')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province')->nullable();
            $table->date('date_registered')->nullable();
            $table->text('access_code')->nullable(); // encrypted; staff read it out / print it on the slip
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
