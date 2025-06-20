<?php

namespace Database\Factories;

use App\Models\DocumentRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentRequest>
 */
class DocumentRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $documentTypes = ['SF10', 'ENROLLMENT_CERT', 'DIPLOMA', 'CAV', 'ENG. INST.', 'CERT OF GRAD', 'OTHERS'];
        $genders = ['male', 'female', 'other'];
        $statuses = ['pending', 'processing', 'pickup', 'completed', 'rejected'];
        $grades = ['7', '8', '9', '10', '11', '12'];
        $sections = ['A', 'B', 'C', 'D', 'E'];
        $majors = ['STEM', 'HUMSS', 'ABM', 'GAS', 'TVL', null];

        return [
            'learning_reference_number' => $this->faker->numerify('##########'),
            'name_of_student' => $this->faker->name(),
            'last_schoolyear_attended' => $this->faker->randomElement(['2022-2023', '2023-2024', '2024-2025']),
            'gender' => $this->faker->randomElement($genders),
            'grade' => $this->faker->randomElement($grades),
            'section' => $this->faker->randomElement($sections),
            'major' => $this->faker->randomElement($majors),
            'adviser' => $this->faker->name(),
            'contact_number' => $this->faker->numerify('09##########'),
            'person_requesting_name' => $this->faker->name(),
            'request_for' => $this->faker->randomElement($documentTypes),
            'signature_url' => $this->faker->imageUrl(640, 480, 'signature', true),
            'status' => $this->faker->randomElement($statuses),
            'remarks' => $this->faker->optional()->sentence(),
            'processed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the document request is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the document request is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the document request is ready for pickup.
     */
    public function pickup(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pickup',
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the document request is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'processed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the document request is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'processed_at' => null,
            'remarks' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the document request is for SF10.
     */
    public function sf10(): static
    {
        return $this->state(fn (array $attributes) => [
            'request_for' => 'SF10',
        ]);
    }

    /**
     * Indicate that the document request is for enrollment certificate.
     */
    public function enrollmentCert(): static
    {
        return $this->state(fn (array $attributes) => [
            'request_for' => 'ENROLLMENT_CERT',
        ]);
    }

    /**
     * Indicate that the document request is for diploma.
     */
    public function diploma(): static
    {
        return $this->state(fn (array $attributes) => [
            'request_for' => 'DIPLOMA',
        ]);
    }
}
