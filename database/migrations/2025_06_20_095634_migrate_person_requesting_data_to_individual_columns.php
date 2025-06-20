<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing data from person_requesting JSON to individual columns
        $documentRequests = DB::table('document_requests')->get();

        foreach ($documentRequests as $request) {
            $personRequesting = json_decode($request->person_requesting, true);

            if ($personRequesting) {
                DB::table('document_requests')
                    ->where('id', $request->id)
                    ->update([
                        'person_requesting_name' => $personRequesting['name'] ?? null,
                        'request_for' => $personRequesting['request_for'] ?? null,
                        'signature_url' => $personRequesting['signature'] ?? null,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Migrate data back to JSON format
        $documentRequests = DB::table('document_requests')->get();

        foreach ($documentRequests as $request) {
            $personRequesting = [
                'name' => $request->person_requesting_name,
                'request_for' => $request->request_for,
                'signature' => $request->signature_url,
            ];

            DB::table('document_requests')
                ->where('id', $request->id)
                ->update([
                    'person_requesting' => json_encode($personRequesting),
                ]);
        }
    }
};
