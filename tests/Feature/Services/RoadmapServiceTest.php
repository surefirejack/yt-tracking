<?php

namespace Tests\Feature\Services;

use App\Models\RoadmapItem;
use App\Services\RoadmapService;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTest;

class RoadmapServiceTest extends FeatureTest
{
    public function test_create(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $roadmapService = app()->make(RoadmapService::class);

        $title = 'New Roadmap Item';
        $description = 'Description of the new roadmap item';
        $type = 'feature';

        $roadmapItem = $roadmapService->createItem($title, $description, $type);

        $this->assertDatabaseHas('roadmap_items', [
            'title' => $title,
            'description' => $description,
            'type' => $type,
            'user_id' => $user->id,
            'upvotes' => 1,
            'status' => 'pending_approval',
        ]);

        $this->assertDatabaseHas('roadmap_item_user_upvotes', [
            'user_id' => $user->id,
            'roadmap_item_id' => $roadmapItem->id,
        ]);
    }

    public function test_is_upvotable()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $roadmapService = app()->make(RoadmapService::class);

        $title = 'New Roadmap Item';
        $description = 'Description of the new roadmap item';
        $type = 'feature';

        $roadmapItem = $roadmapService->createItem($title, $description, $type);

        $this->assertTrue($roadmapService->isUpvotable($roadmapItem));

        $roadmapItem->status = 'completed';
        $roadmapItem->save();

        $this->assertFalse($roadmapService->isUpvotable($roadmapItem));

        $roadmapItem->status = 'cancelled';

        $this->assertFalse($roadmapService->isUpvotable($roadmapItem));

    }

    public function test_upvote()
    {
        $user1 = $this->createUser();
        $this->actingAs($user1);

        $roadmapService = app()->make(RoadmapService::class);

        $title = 'New Roadmap Item';
        $description = 'Description of the new roadmap item';
        $type = 'feature';

        $roadmapItem = $roadmapService->createItem($title, $description, $type);

        $user2 = $this->createUser();
        $this->actingAs($user2);

        $roadmapService->upvote($roadmapItem->id);

        $this->assertDatabaseHas('roadmap_item_user_upvotes', [
            'user_id' => $user2->id,
            'roadmap_item_id' => $roadmapItem->id,
        ]);

        $this->assertEquals(2, $roadmapItem->userUpvotes()->count());

        $this->assertDatabaseHas('roadmap_items', [
            'id' => $roadmapItem->id,
            'upvotes' => 2,
        ]);

        // make sure the user can't upvote the same item twice

        $roadmapService->upvote($roadmapItem->id);

        $this->assertEquals(2, $roadmapItem->userUpvotes()->count());
    }

    public function test_upvote_unauthenticated()
    {
        $user1 = $this->createUser();

        $roadmapService = app()->make(RoadmapService::class);

        $title = 'New Roadmap Item';
        $description = 'Description of the new roadmap item';
        $type = 'feature';

        $roadmapItem = RoadmapItem::create([
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'description' => $description,
            'type' => $type,
            'user_id' => $user1->id,
            'upvotes' => 1,
            'status' => 'approved',
        ]);

        $roadmapService->upvote($roadmapItem->id);

        $this->assertDatabaseHas('roadmap_items', [
            'id' => $roadmapItem->id,
            'user_id' => $user1->id,
            'upvotes' => 1,
        ]);
    }

    public function test_remove_upvote_unauthenticated()
    {
        $user1 = $this->createUser();

        $roadmapService = app()->make(RoadmapService::class);

        $title = 'New Roadmap Item';
        $description = 'Description of the new roadmap item';
        $type = 'feature';

        $roadmapItem = RoadmapItem::create([
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'description' => $description,
            'type' => $type,
            'user_id' => $user1->id,
            'upvotes' => 1,
            'status' => 'approved',
        ]);

        $roadmapService->removeUpvote($roadmapItem->id);

        $this->assertDatabaseHas('roadmap_items', [
            'id' => $roadmapItem->id,
            'user_id' => $user1->id,
            'upvotes' => 1,
        ]);
    }

    public function test_remove_upvote()
    {
        $user1 = $this->createUser();
        $this->actingAs($user1);

        $roadmapService = app()->make(RoadmapService::class);

        $title = 'New Roadmap Item';
        $description = 'Description of the new roadmap item';
        $type = 'feature';

        $roadmapItem = $roadmapService->createItem($title, $description, $type);

        $user2 = $this->createUser();
        $this->actingAs($user2);

        $roadmapService->upvote($roadmapItem->id);

        $this->assertDatabaseHas('roadmap_item_user_upvotes', [
            'user_id' => $user2->id,
            'roadmap_item_id' => $roadmapItem->id,
        ]);

        $this->assertEquals(2, $roadmapItem->userUpvotes()->count());

        $this->assertDatabaseHas('roadmap_items', [
            'id' => $roadmapItem->id,
            'upvotes' => 2,
        ]);

        $roadmapService->removeUpvote($roadmapItem->id);

        $this->assertEquals(1, $roadmapItem->userUpvotes()->count());

        $this->assertDatabaseMissing('roadmap_item_user_upvotes', [
            'user_id' => $user2->id,
            'roadmap_item_id' => $roadmapItem->id,
        ]);

        $this->assertDatabaseHas('roadmap_items', [
            'id' => $roadmapItem->id,
            'upvotes' => 1,
        ]);

    }

    public function test_has_user_upvoted()
    {
        $user1 = $this->createUser();
        $this->actingAs($user1);

        $roadmapService = app()->make(RoadmapService::class);

        $title = 'New Roadmap Item';
        $description = 'Description of the new roadmap item';
        $type = 'feature';

        $roadmapItem = $roadmapService->createItem($title, $description, $type);

        $user2 = $this->createUser();
        $this->actingAs($user2);

        $roadmapService->upvote($roadmapItem->id);

        $this->assertTrue($roadmapService->hasUserUpvoted($roadmapItem));

        $user3 = $this->createUser();
        $this->actingAs($user3);

        $this->assertFalse($roadmapService->hasUserUpvoted($roadmapItem));

    }
}
