<?php

namespace Database\Factories;

use App\Models\DocumentRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentFile>
 */
class DocumentFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileTypes = ['signature', 'affidavit_of_loss', 'birth_certificate', 'valid_id', 'transcript_of_records', 'other'];
        $fileType = $this->faker->randomElement($fileTypes);

        $mimeTypes = [
            'signature' => ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'],
            'affidavit_of_loss' => ['application/pdf', 'image/jpeg', 'image/png'],
            'birth_certificate' => ['application/pdf', 'image/jpeg', 'image/png'],
            'valid_id' => ['application/pdf', 'image/jpeg', 'image/png'],
            'transcript_of_records' => ['application/pdf', 'image/jpeg', 'image/png'],
            'other' => ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ];

        $mimeType = $this->faker->randomElement($mimeTypes[$fileType]);
        $extension = $this->getExtensionFromMimeType($mimeType);
        $fileName = $this->generateFileName($fileType, $extension);

        return [
            'document_request_id' => DocumentRequest::factory(),
            'file_type' => $fileType,
            'original_name' => $this->faker->words(2, true).'.'.$extension,
            'file_name' => $fileName,
            'file_path' => 'https://s3.amazonaws.com/test-bucket/'.$fileName,
            'mime_type' => $mimeType,
            'file_size' => $this->faker->numberBetween(1024, 10 * 1024 * 1024), // 1KB to 10MB
            'metadata' => [
                'uploaded_at' => $this->faker->dateTimeBetween('-1 year', 'now')->format('c'),
                'upload_method' => 'api',
                'file_extension' => $extension,
                'uploaded_by' => $this->faker->name(),
            ],
        ];
    }

    /**
     * Indicate that the file is a signature.
     */
    public function signature(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'signature',
            'mime_type' => $this->faker->randomElement(['image/jpeg', 'image/png', 'image/gif']),
            'file_size' => $this->faker->numberBetween(1024, 5 * 1024 * 1024), // 1KB to 5MB
        ]);
    }

    /**
     * Indicate that the file is an affidavit of loss.
     */
    public function affidavitOfLoss(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'affidavit_of_loss',
            'mime_type' => $this->faker->randomElement(['application/pdf', 'image/jpeg', 'image/png']),
            'file_size' => $this->faker->numberBetween(1024, 10 * 1024 * 1024), // 1KB to 10MB
        ]);
    }

    /**
     * Indicate that the file is a birth certificate.
     */
    public function birthCertificate(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'birth_certificate',
            'mime_type' => $this->faker->randomElement(['application/pdf', 'image/jpeg', 'image/png']),
            'file_size' => $this->faker->numberBetween(1024, 10 * 1024 * 1024), // 1KB to 10MB
        ]);
    }

    /**
     * Indicate that the file is a valid ID.
     */
    public function validId(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'valid_id',
            'mime_type' => $this->faker->randomElement(['application/pdf', 'image/jpeg', 'image/png']),
            'file_size' => $this->faker->numberBetween(1024, 10 * 1024 * 1024), // 1KB to 10MB
        ]);
    }

    /**
     * Indicate that the file is a transcript of records.
     */
    public function transcriptOfRecords(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'transcript_of_records',
            'mime_type' => $this->faker->randomElement(['application/pdf', 'image/jpeg', 'image/png']),
            'file_size' => $this->faker->numberBetween(1024, 15 * 1024 * 1024), // 1KB to 15MB
        ]);
    }

    /**
     * Indicate that the file is a PDF.
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'application/pdf',
            'file_size' => $this->faker->numberBetween(1024, 5 * 1024 * 1024), // 1KB to 5MB
        ]);
    }

    /**
     * Indicate that the file is an image.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => $this->faker->randomElement(['image/jpeg', 'image/png', 'image/gif']),
            'file_size' => $this->faker->numberBetween(1024, 2 * 1024 * 1024), // 1KB to 2MB
        ]);
    }

    /**
     * Generate a realistic filename based on file type and extension.
     */
    private function generateFileName(string $fileType, string $extension): string
    {
        $requestId = 'DOC-'.date('Y').'-'.strtoupper($this->faker->regexify('[A-Z0-9]{8}'));
        $timestamp = $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d_H-i-s');
        $randomString = strtoupper($this->faker->regexify('[A-Z0-9]{8}'));

        // Generate a realistic requestor name
        $requestorName = $this->sanitizeFileName($this->faker->name());

        $folder = $fileType === 'signature' ? 'signatures' : 'supporting_documents';

        return "{$folder}/{$requestId}_{$requestorName}_{$fileType}_{$timestamp}_{$randomString}.{$extension}";
    }

    /**
     * Sanitize filename to remove special characters and spaces
     */
    private function sanitizeFileName(string $name): string
    {
        // Remove special characters and replace spaces with underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
        $sanitized = preg_replace('/\s+/', '_', trim($sanitized));

        // Limit length to 50 characters
        return substr($sanitized, 0, 50);
    }

    /**
     * Get file extension from MIME type.
     */
    private function getExtensionFromMimeType(string $mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        ];

        return $extensions[$mimeType] ?? 'pdf';
    }
}
