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
        Schema::create('training_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained()->cascadeOnDelete();

            // Personel Sistemi'ndeki personel ID'si. Farklı veritabanında olduğu için
            // foreign key yok; sadece dış referans olarak tutulur.
            $table->unsignedBigInteger('employee_id')->index();

            // Atama anındaki personel bilgisi (snapshot). Personel API'sine
            // ulaşılamadığında veya personel silindiğinde de geçmiş okunabilsin diye.
            $table->string('employee_name');
            $table->string('employee_email');
            $table->string('employee_department', 100)->index();

            $table->string('status', 20)->default('assigned')->index();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['training_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_assignments');
    }
};
