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
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
            $table->string('learning_reference_number');
            $table->string('name_of_student');
            $table->string('last_schoolyear_attended');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('grade');
            $table->string('section');
            $table->string('major')->nullable();
            $table->string('adviser');
            $table->string('contact_number');
            
            // Person requesting details as JSON
            $table->json('person_requesting');
            
            // Status tracking
            $table->enum('status', ['pending', 'processing', 'completed', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            
            // Tracking
            $table->string('request_id')->unique(); // Auto-generated unique ID
            $table->timestamp('processed_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('learning_reference_number');
            $table->index('request_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};
