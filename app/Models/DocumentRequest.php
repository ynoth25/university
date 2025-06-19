<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class DocumentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'learning_reference_number',
        'name_of_student',
        'last_schoolyear_attended',
        'gender',
        'grade',
        'section',
        'major',
        'adviser',
        'contact_number',
        'person_requesting',
        'status',
        'remarks',
        'request_id',
        'processed_at',
    ];

    protected $casts = [
        'person_requesting' => 'array',
        'processed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($documentRequest) {
            if (empty($documentRequest->request_id)) {
                $documentRequest->request_id = self::generateRequestId();
            }
        });

        // Delete associated files from S3 when document request is deleted
        static::deleting(function ($documentRequest) {
            foreach ($documentRequest->files as $file) {
                // Delete from S3
                if (Storage::disk('s3')->exists($file->file_path)) {
                    Storage::disk('s3')->delete($file->file_path);
                }
            }
        });
    }

    /**
     * Generate a unique request ID
     */
    public static function generateRequestId(): string
    {
        do {
            $requestId = 'DOC-' . date('Y') . '-' . strtoupper(Str::random(8));
        } while (self::where('request_id', $requestId)->exists());

        return $requestId;
    }

    /**
     * Get the files associated with this document request.
     */
    public function files(): HasMany
    {
        return $this->hasMany(DocumentFile::class);
    }

    /**
     * Get the signature file for this request.
     */
    public function signatureFile()
    {
        return $this->files()->ofType('signature')->first();
    }

    /**
     * Get the supporting documents for this request.
     */
    public function supportingDocuments()
    {
        return $this->files()->where('file_type', '!=', 'signature')->get();
    }

    /**
     * Get a specific type of supporting document.
     */
    public function getDocumentByType(string $fileType)
    {
        return $this->files()->ofType($fileType)->first();
    }

    /**
     * Get the person requesting name
     */
    public function getPersonRequestingNameAttribute(): string
    {
        return $this->person_requesting['name'] ?? '';
    }

    /**
     * Get the request type
     */
    public function getRequestTypeAttribute(): string
    {
        return $this->person_requesting['request_for'] ?? '';
    }

    /**
     * Get the signature URL (now from S3 file)
     */
    public function getSignatureUrlAttribute(): string
    {
        $signatureFile = $this->signatureFile();
        return $signatureFile ? $signatureFile->url : '';
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for processing requests
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope for completed requests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function delete()
    {
        // Ensure the files relationship is loaded
        if (!$this->relationLoaded('files')) {
            $this->load('files');
        }
        $this->files->each->delete();
        return parent::delete();
    }
}
