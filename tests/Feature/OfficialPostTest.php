<?php

use App\Enums\NotificationReason;
use App\Enums\NotificationType;
use App\Events\PostCreated;
use App\Http\Middleware\UserHasAcceptedLegalDocuments;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(UserHasAcceptedLegalDocuments::class);
});

it('lets an admin create an official post', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $this->postJson('/api/app/posts', ['title' => 'Hi', 'body' => 'Body', 'is_official' => true])
        ->assertOk();

    expect(Post::first()->is_official)->toBeTruthy();
});

it('does not allow non-admins to create an official post', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/app/posts', ['title' => 'Hi', 'body' => 'Body', 'is_official' => true])
        ->assertUnprocessable();

    expect(Post::count())->toBe(0);
});

it('creates non-official posts by default', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson('/api/app/posts', ['title' => 'Hi', 'body' => 'Body'])->assertOk();

    expect(Post::first()->is_official)->toBeFalsy();
});

it('notifies all other users about a new official post', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(3)->create();

    $post = Post::factory()->official()->create(['user_id' => $admin->id]);
    PostCreated::dispatch($post);

    expect(Notification::count())->toBe(3)
        ->and(Notification::where('user_id', $admin->id)->exists())->toBeFalse()
        ->and(Notification::first())
        ->type->toBe(NotificationType::NewOfficialPost)
        ->reason->toBe(NotificationReason::Everyone)
        ->post_id->toBe($post->id);
});

it('does not broadcast notifications for regular posts', function () {
    User::factory()->count(2)->create();

    PostCreated::dispatch(Post::factory()->create());

    expect(Notification::count())->toBe(0);
});
