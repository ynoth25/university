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
        Schema::table('document_requests', function (Blueprint $table) {
            // Add new individual columns for person_requesting data
            $table->string('person_requesting_name')->nullable()->after('contact_number');
            $table->enum('request_for', ['SF10', 'ENROLLMENT_CERT', 'DIPLOMA', 'CAV', 'ENG. INST.', 'CERT OF GRAD', 'OTHERS'])->nullable()->after('person_requesting_name');
            $table->string('signature_url')->nullable()->after('request_for');
            
            // Modify status enum to include pickup
            $table->enum('status', ['pending', 'processing', 'pickup', 'completed', 'rejected'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            // Remove the new columns
            $table->dropColumn(['person_requesting_name', 'request_for', 'signature_url']);
            
            // Revert status enum to original values
            $table->enum('status', ['pending', 'processing', 'completed', 'rejected'])->default('pending')->change();
        });
    }
};
