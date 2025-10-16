<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'branch_address' => $this->faker->address(),
            'branch_contactNum' => $this->faker->phoneNumber(),
            'branch_name' => $this->faker->company(),
        ];
    }
}