<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MoonShineIdSequencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_role_created_through_the_panel_does_not_collide_with_the_seeded_one(): void
    {
        $this->assertSame(1, Role::query()->count());

        $role = Role::create(['name' => 'Оператор']);

        $this->assertNotSame(1, $role->id);
        $this->assertSame(2, Role::query()->count());
    }

    public function test_sequences_are_ahead_of_the_rows_inserted_by_migrations(): void
    {
        foreach (['moonshine_user_roles', 'moonshine_users'] as $table) {
            $sequence = DB::selectOne('select pg_get_serial_sequence(?, ?) as name', [$table, 'id'])->name;
            $state = DB::selectOne("select last_value, is_called from {$sequence}");

            $nextId = $state->is_called ? $state->last_value + 1 : $state->last_value;

            $this->assertGreaterThan((int) DB::table($table)->max('id'), $nextId, $table);
        }
    }
}
