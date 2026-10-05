<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        $departmentName = Department::query()->first()?->name ?? 'Church';

        return [
            'name' => fake()->name(),
            'sex' => fake()->randomElement(['M', 'F']),
            'dept' => $departmentName,
            'role' => 'user',
            'phone' => fake()->unique()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'birth_date' => fake()->dateTimeBetween('-50 years', '-10 years')->format('Y-m-d'),
            'address' => fake()->address(),
            'profile_photo_url' => null,
            'notes' => null,
        ];
    }
}
