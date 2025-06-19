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
        Schema::create('document_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_request_id')->constrained()->onDelete('cascade');
            $table->string('file_type'); // signature, affidavit_of_loss, birth_certificate, etc.
            $table->string('original_name');
            $table->string('file_name'); // S3 key
            $table->string('file_path'); // S3 URL
            $table->string('mime_type');
            $table->bigInteger('file_size'); // in bytes
            $table->json('metadata')->nullable(); // Additional file metadata
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['document_request_id', 'file_type']);
            $table->index('file_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_files');
    }
};
