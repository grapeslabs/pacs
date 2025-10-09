<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        return [
            'last_name' => $this->faker->lastName('male'),
            'first_name' => $this->faker->firstName('male'),
            'middle_name' => $this->faker->boolean(90) ? $this->faker->middleName('male'): null,
            'birth_date' => $this->faker->boolean(80) ? $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d') : null,
            'certificate_number' => $this->faker->regexify('[A-Z0-9]{5,8}'),
            'photo' => null,
            'organization_id' => $this->faker->boolean(70) ? Organization::inRandomOrder()->value('id') : null,
            'comment' => $this->faker->optional(0.6)->sentence(),
            'grapesva_uuid' => null,
            'face_vector' => null,
            'vectorization_status' => 'pending',
            'vectorization_error' => null,
            'vectorized_at' => null,
            'key_uid' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    /**
     * Конфигурация после создания записи
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Person $person) {
            $person->key_uid = 'person_' . $person->id;
            $person->save();
        });
    }

    /**
     * Создать персону с конкретным номером карты для СКУД
     */
    public function withCardNumber(string $cardNumber): static
    {
        return $this->state(fn (array $attributes) => [
            'key_uid' => $cardNumber,
        ]);
    }

    /**
     * Создать персону с key_uid в формате person_{id}
     */
    public function withPersonKey(): static
    {
        return $this->afterCreating(function (Person $person) {
            $person->key_uid = 'person_' . $person->id;
            $person->save();
        });
    }


    /**
     * Указать конкретную организацию
     */
    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization->id,
        ]);
    }

    /**
     * Создать персону без организации
     */
    public function withoutOrganization(): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => null,
        ]);
    }

    /**
     * Создать персону с конкретными ФИО
     */
    public function named(string $lastName, string $firstName, ?string $middleName = null): static
    {
        return $this->state(fn (array $attributes) => [
            'last_name' => $lastName,
            'first_name' => $firstName,
            'middle_name' => $middleName,
        ]);
    }
}
