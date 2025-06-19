<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_request_id',
        'file_type',
        'original_name',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'file_size' => 'integer',
    ];

    /**
     * Get the document request that owns the file.
     */
    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    /**
     * Get the file URL for public access.
     */
    public function getUrlAttribute(): string
    {
        return $this->file_path;
    }

    /**
     * Get the file size in human readable format.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if the file exists in S3.
     */
    public function exists(): bool
    {
        return Storage::disk('s3')->exists($this->file_name);
    }

    /**
     * Delete the file from S3.
     */
    public function deleteFile(): bool
    {
        if ($this->exists()) {
            return Storage::disk('s3')->delete($this->file_name);
        }
        return false;
    }

    /**
     * Get a temporary URL for the file (if needed for private files).
     */
    public function getTemporaryUrl(int $expiresInMinutes = 60): string
    {
        return Storage::disk('s3')->temporaryUrl(
            $this->file_name,
            now()->addMinutes($expiresInMinutes)
        );
    }

    /**
     * Scope to filter by file type.
     */
    public function scopeOfType($query, string $fileType)
    {
        return $query->where('file_type', $fileType);
    }

    /**
     * Scope to filter by document request.
     */
    public function scopeForRequest($query, int $documentRequestId)
    {
        return $query->where('document_request_id', $documentRequestId);
    }

    /**
     * Boot the model and add event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Delete file from S3 when record is deleted
        static::deleting(function ($documentFile) {
            \Log::info('Deleting DocumentFile: ' . $documentFile->file_name);
            if (Storage::disk('s3')->exists($documentFile->file_name)) {
                Storage::disk('s3')->delete($documentFile->file_name);
            }
        });
    }
}
