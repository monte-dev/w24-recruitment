<?php

namespace Database\Factories;

use App\Enums\ImportStatus;
use App\Models\Import;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Import>
 */
class ImportFactory extends Factory
{
    protected $model = Import::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'file_name' => 'import_' . fake()->lexify('????') . '.csv',
            'total_records' => 10,
            'successful_records' => 10,
            'failed_records' => 0,
            'status' => ImportStatus::Success,
        ];
    }
}
