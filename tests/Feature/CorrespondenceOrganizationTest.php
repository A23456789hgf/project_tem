<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\Correspondence;
use App\Models\InternalEntity;
use App\Models\User;
use App\Services\CorrespondenceOrganizationResolver;
use App\Services\OrganizationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CorrespondenceOrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function grantTestPermissions(User $user): void
    {
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('localPermissionsCache');
        $property->setAccessible(true);
        $property->setValue($user, [
            'correspondence.forward' => true,
            'correspondence.close' => true,
            'correspondence.view' => true,
        ]);
    }

    /**
     * Test 1: Old correspondence without type works (fallback to internal).
     */
    public function test_old_correspondence_without_type_works()
    {
        $internalEntity = InternalEntity::create(['name' => 'Test Entity']);

        $uid = DB::table('users')->insertGetId([
            'user_id' => 'u99',
            'name' => 'sys',
            'username' => 'sys',
            'password' => bcrypt('password'),
            'role_id' => 999,
        ]);
        $user = User::find($uid);
        $this->grantTestPermissions($user);

        $id = DB::table('correspondences')->insertGetId([
            'correspondence_number' => 'OLD-001',
            'subject' => 'Old Legacy',
            'message_body' => 'Legacy Body',
            'sender_entity_id' => $internalEntity->id,
            'recipient_entity_id' => $internalEntity->id,
            'sender_type' => null,
            'sender_authority_id' => null,
            'sender_user_id' => $uid,
            'status' => 'pending',
        ]);

        $correspondence = Correspondence::find($id);

        $resolver = app(CorrespondenceOrganizationResolver::class);
        $resolvedSender = $resolver->resolveSender($correspondence);

        $this->assertEquals(OrganizationResolver::INTERNAL, $resolvedSender['type']);
        $this->assertEquals($internalEntity->id, $resolvedSender['id']);
        $this->assertEquals($internalEntity->name, $resolvedSender['model']->name);

        $this->assertEquals($internalEntity->id, $correspondence->senderOrganization['id']);
    }

    /**
     * Test 2: Internal correspondence works.
     */
    public function test_internal_correspondence_works()
    {
        $internalEntity = InternalEntity::create(['name' => 'Internal Test']);

        $uid = DB::table('users')->insertGetId([
            'user_id' => 'u98',
            'name' => 'sys',
            'username' => 'sys2',
            'password' => bcrypt('password'),
            'role_id' => 999,
        ]);

        $id = DB::table('correspondences')->insertGetId([
            'correspondence_number' => 'INT-001',
            'subject' => 'Int',
            'message_body' => 'Body',
            'sender_type' => OrganizationResolver::INTERNAL,
            'sender_entity_id' => $internalEntity->id,
            'sender_authority_id' => null,
            'recipient_entity_id' => $internalEntity->id,
            'sender_user_id' => $uid,
            'status' => 'pending',
        ]);

        $correspondence = Correspondence::find($id);

        $this->assertEquals(OrganizationResolver::INTERNAL, $correspondence->senderOrganization['type']);
        $this->assertInstanceOf(InternalEntity::class, $correspondence->senderOrganization['model']);
        $this->assertEquals($internalEntity->id, $correspondence->senderOrganization['model']->id);
    }

    /**
     * Test 3: External correspondence works.
     */
    public function test_external_correspondence_works()
    {
        $authority = Authority::create(['agency_name' => 'External Test']);

        $uid = DB::table('users')->insertGetId([
            'user_id' => 'u97',
            'name' => 'sys',
            'username' => 'sys3',
            'password' => bcrypt('password'),
            'role_id' => 999,
        ]);
        $internalEntity = InternalEntity::create(['name' => 'Dummy Entity']);

        $id = DB::table('correspondences')->insertGetId([
            'correspondence_number' => 'EXT-001',
            'subject' => 'Ext',
            'message_body' => 'Body',
            'sender_type' => OrganizationResolver::EXTERNAL,
            'sender_entity_id' => $internalEntity->id,
            'sender_authority_id' => $authority->id,
            'recipient_entity_id' => $internalEntity->id,
            'sender_user_id' => $uid,
            'status' => 'pending',
        ]);

        $correspondence = Correspondence::find($id);

        $this->assertEquals(OrganizationResolver::EXTERNAL, $correspondence->senderOrganization['type']);
        $this->assertInstanceOf(Authority::class, $correspondence->senderOrganization['model']);
        $this->assertEquals($authority->id, $correspondence->senderOrganization['model']->id);
    }

    /**
     * Test 4: Internal user can access internal correspondence.
     */
    public function test_internal_user_can_access_internal_correspondence()
    {
        $internalEntity = InternalEntity::create(['name' => 'Test Entity']);

        $id = DB::table('users')->insertGetId([
            'user_id' => 'u1',
            'name' => 'Test User',
            'username' => 'testuser',
            'password' => bcrypt('password'),
            'entity_id' => $internalEntity->id,
            'organization_type' => OrganizationResolver::INTERNAL,
            'role_id' => 999,
        ]);
        $internalUser = User::find($id);
        $this->grantTestPermissions($internalUser);

        $cid = DB::table('correspondences')->insertGetId([
            'correspondence_number' => 'INT-001',
            'subject' => 'Int',
            'message_body' => 'Body',
            'recipient_type' => OrganizationResolver::INTERNAL,
            'recipient_entity_id' => $internalEntity->id,
            'sender_type' => OrganizationResolver::INTERNAL,
            'sender_entity_id' => $internalEntity->id,
            'sender_user_id' => 1,
            'status' => 'pending',
        ]);
        $correspondence = Correspondence::find($cid);

        $this->assertTrue($internalUser->can('forward', $correspondence));
        $this->assertTrue($internalUser->can('close', $correspondence));
        $this->assertTrue($correspondence->canBeViewedBy($internalUser));
    }

    /**
     * Test 5: External user can access external correspondence.
     */
    public function test_external_user_can_access_external_correspondence()
    {
        $authority = Authority::create(['agency_name' => 'External Test']);

        $id = DB::table('users')->insertGetId([
            'user_id' => 'u2',
            'name' => 'Test User',
            'username' => 'testuser2',
            'password' => bcrypt('password'),
            'authority_id' => $authority->id,
            'entity_id' => null,
            'organization_type' => OrganizationResolver::EXTERNAL,
            'role_id' => 999,
        ]);
        $externalUser = User::find($id);
        $this->grantTestPermissions($externalUser);

        $internalEntity = InternalEntity::create(['name' => 'Dummy Entity']);
        $cid = DB::table('correspondences')->insertGetId([
            'correspondence_number' => 'EXT-001',
            'subject' => 'Ext',
            'message_body' => 'Body',
            'sender_type' => OrganizationResolver::EXTERNAL,
            'sender_authority_id' => $authority->id,
            'sender_entity_id' => $internalEntity->id,
            'recipient_type' => OrganizationResolver::EXTERNAL,
            'recipient_authority_id' => $authority->id,
            'recipient_entity_id' => $internalEntity->id,
            'sender_user_id' => $id,
            'status' => 'pending',
        ]);
        $correspondence = Correspondence::find($cid);

        $this->assertTrue($externalUser->can('forward', $correspondence));
        $this->assertTrue($externalUser->can('close', $correspondence));
        $this->assertTrue($correspondence->canBeViewedBy($externalUser));
    }

    /**
     * Test 6: External user cannot access another authority correspondence.
     */
    public function test_external_user_cannot_access_another_authority_correspondence()
    {
        dump('start test');
        $authority1 = Authority::create(['agency_name' => 'External 1']);
        $authority2 = Authority::create(['agency_name' => 'External 2']);

        $id = DB::table('users')->insertGetId([
            'user_id' => 'u3',
            'name' => 'Test User',
            'username' => 'testuser3',
            'password' => bcrypt('password'),
            'authority_id' => $authority1->id,
            'entity_id' => null,
            'organization_type' => OrganizationResolver::EXTERNAL,
            'role_id' => 999,
        ]);
        $externalUser = User::find($id);
        $this->grantTestPermissions($externalUser);

        $internalEntity = InternalEntity::create(['name' => 'Dummy Entity']);
        $cid = DB::table('correspondences')->insertGetId([
            'correspondence_number' => 'EXT-002',
            'subject' => 'Ext',
            'message_body' => 'Body',
            'sender_type' => OrganizationResolver::EXTERNAL,
            'sender_authority_id' => $authority2->id,
            'sender_entity_id' => $internalEntity->id,
            'recipient_type' => OrganizationResolver::EXTERNAL,
            'recipient_authority_id' => $authority2->id,
            'recipient_entity_id' => $internalEntity->id,
            'sender_user_id' => $id,
            'status' => 'pending',
        ]);
        $correspondence = Correspondence::find($cid);

        $this->assertFalse($externalUser->can('forward', $correspondence));
        $this->assertFalse($externalUser->can('close', $correspondence));
        $this->assertFalse($correspondence->canBeViewedBy($externalUser));
    }

    /**
     * Test 7: Organization accessor returns correct model.
     */
    public function test_organization_accessor_returns_correct_model()
    {
        $internalEntity = InternalEntity::create(['name' => 'Internal']);
        $authority = Authority::create(['agency_name' => 'External']);

        $uid = DB::table('users')->insertGetId([
            'user_id' => 'u96',
            'name' => 'sys',
            'username' => 'sys4',
            'password' => bcrypt('password'),
            'role_id' => 999,
        ]);

        $cid1 = DB::table('correspondences')->insertGetId([
            'correspondence_number' => 'INT-002',
            'subject' => 'Int',
            'message_body' => 'Body',
            'sender_type' => OrganizationResolver::INTERNAL,
            'sender_entity_id' => $internalEntity->id,
            'sender_authority_id' => null,
            'recipient_entity_id' => $internalEntity->id,
            'sender_user_id' => $uid,
            'status' => 'pending',
        ]);
        $internalCorrespondence = Correspondence::find($cid1);

        $cid2 = DB::table('correspondences')->insertGetId([
            'correspondence_number' => 'EXT-003',
            'subject' => 'Ext',
            'message_body' => 'Body',
            'sender_type' => OrganizationResolver::EXTERNAL,
            'sender_entity_id' => $internalEntity->id,
            'sender_authority_id' => $authority->id,
            'recipient_entity_id' => $internalEntity->id,
            'sender_user_id' => $uid,
            'status' => 'pending',
        ]);
        $externalCorrespondence = Correspondence::find($cid2);

        $this->assertInstanceOf(InternalEntity::class, $internalCorrespondence->senderOrganization['model']);
        $this->assertInstanceOf(Authority::class, $externalCorrespondence->senderOrganization['model']);
    }
}
