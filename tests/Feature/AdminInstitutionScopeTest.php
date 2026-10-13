<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminInstitutionScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_only_manage_assigned_institutions(): void
    {
        Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'Teacher', 'guard_name' => 'web']);

        $assigned = Institution::create([
            'name' => 'Assigned University',
            'type' => 'university',
            'address' => 'Damascus',
        ]);
        $outside = Institution::create([
            'name' => 'Outside School',
            'type' => 'school',
            'address' => 'Aleppo',
        ]);

        $admin = User::factory()->create(['institution_id' => $assigned->id]);
        $admin->assignRole('Admin');
        $admin->managedInstitutions()->sync([$assigned->id]);

        $outsideTeacher = User::factory()->create(['institution_id' => $outside->id]);
        $outsideTeacher->assignRole('Teacher');

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/institutions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assigned->id);

        $this->getJson('/api/admin/institutions/'.$outside->id)->assertForbidden();
        $this->putJson('/api/admin/institutions/'.$outside->id, ['name' => 'Hijacked'])->assertForbidden();
        $this->deleteJson('/api/admin/institutions/'.$outside->id)->assertForbidden();
        $this->assertDatabaseHas('institutions', ['id' => $outside->id, 'name' => 'Outside School']);

        $this->postJson('/api/admin/colleges', [
            'institution_id' => $outside->id,
            'name' => 'Outside College',
        ])->assertForbidden();

        $this->postJson('/api/admin/users', [
            'name' => 'Outside Teacher',
            'email' => 'outside-teacher@sba.local',
            'password' => 'password',
            'role' => 'teacher',
            'institution_id' => $outside->id,
        ])->assertForbidden();

        $this->deleteJson('/api/admin/users/'.$outsideTeacher->id)->assertForbidden();

        $users = $this->getJson('/api/admin/users')->assertOk()->json('data');
        $userIds = collect($users)->pluck('id')->all();
        $this->assertContains($admin->id, $userIds);
        $this->assertNotContains($outsideTeacher->id, $userIds);

        $created = $this->postJson('/api/admin/institutions', [
            'name' => 'New Managed School',
            'type' => 'school',
            'address' => 'Homs',
        ])->assertCreated();

        $newId = $created->json('data.id');
        $this->assertTrue($admin->fresh()->managesInstitution($newId));

        $this->postJson('/api/admin/users', [
            'name' => 'Scoped Admin',
            'email' => 'scoped-admin@sba.local',
            'password' => 'password',
            'role' => 'admin',
            'institution_ids' => [$assigned->id],
        ])->assertCreated()
            ->assertJsonPath('data.institution_ids.0', $assigned->id);

        $this->postJson('/api/admin/users', [
            'name' => 'Overreach Admin',
            'email' => 'overreach-admin@sba.local',
            'password' => 'password',
            'role' => 'admin',
            'institution_ids' => [$assigned->id, $outside->id],
        ])->assertForbidden();
    }
}
