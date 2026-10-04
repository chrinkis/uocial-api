<?php

use App\Enums\ModerationAction;
use App\Enums\ReportReviewStatus;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PrivacyPolicy;
use App\Models\TermsOfUse;
use App\Models\User;
use App\Models\UserBan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function signInForModerationCounts(User $user): void
{
    $privacyPolicy = PrivacyPolicy::latest('id')->first() ?? PrivacyPolicy::factory()->create();
    $termsOfUse = TermsOfUse::latest('id')->first() ?? TermsOfUse::factory()->create();

    $user->acceptedPrivacyPolicies()->attach($privacyPolicy->id);
    $user->acceptedTermsOfUses()->attach($termsOfUse->id);

    Sanctum::actingAs($user);
}

function moderationCountsBanMessage(UserBan $ban, User $sender): void
{
    $message = $ban->messages()->make(['body' => 'hello']);
    $message->sender()->associate($sender);
    $message->save();
}

it('forbids regular users from reading the moderation counts', function () {
    signInForModerationCounts(User::factory()->create());

    $this->getJson('/api/app/moderation-counts')->assertForbidden();
});

it('returns the moderation counts without ban threads to moderators', function () {
    signInForModerationCounts(User::factory()->moderator()->create());

    $this->getJson('/api/app/moderation-counts')
        ->assertOk()
        ->assertExactJson([
            'posts_pending_review' => 0,
            'post_comments_pending_review' => 0,
            'posts_pending_reports' => 0,
            'post_comments_pending_reports' => 0,
        ]);
});

it('returns the ban threads pending reply to admins', function () {
    signInForModerationCounts(User::factory()->admin()->create());

    $this->getJson('/api/app/moderation-counts')
        ->assertOk()
        ->assertJsonPath('ban_threads_pending_reply', 0)
        ->assertJsonCount(5);
});

it('counts posts hidden by the system until a moderator decides', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->moderator()->create();

    $pending = Post::factory()->create(['user_id' => $owner->id]);
    $pending->autoHide();

    $hiddenByModerator = Post::factory()->create(['user_id' => $owner->id]);
    $moderator->postModerations()->create([
        'post_id' => $hiddenByModerator->id,
        'action' => ModerationAction::Hide,
        'comment' => 'Inappropriate',
    ]);

    $decided = Post::factory()->create(['user_id' => $owner->id]);
    $decided->autoHide();
    $this->travel(1)->minute();
    $moderator->postModerations()->create([
        'post_id' => $decided->id,
        'action' => ModerationAction::Unhide,
        'comment' => 'Fine',
    ]);

    Post::factory()->create(['user_id' => $owner->id]);

    signInForModerationCounts($moderator);

    $this->getJson('/api/app/moderation-counts')
        ->assertOk()
        ->assertJsonPath('posts_pending_review', 1);
});

it('counts posts with at least one unreviewed report once', function () {
    $owner = User::factory()->create();
    $reporter = User::factory()->create();
    $moderator = User::factory()->moderator()->create();

    $reported = Post::factory()->create(['user_id' => $owner->id]);
    $reporter->postReports()->create(['post_id' => $reported->id, 'comment' => 'Spam']);
    User::factory()->create()->postReports()->create(['post_id' => $reported->id, 'comment' => 'Spam again']);

    $reviewed = Post::factory()->create(['user_id' => $owner->id]);
    $report = $reporter->postReports()->create(['post_id' => $reviewed->id, 'comment' => 'Rude']);
    $moderator->postReportReviews()->create([
        'post_report_id' => $report->id,
        'comment' => 'Checked',
        'status' => ReportReviewStatus::Invalid,
    ]);

    signInForModerationCounts($moderator);

    $this->getJson('/api/app/moderation-counts')
        ->assertOk()
        ->assertJsonPath('posts_pending_reports', 1);
});

it('counts comments hidden by the system and comments with unreviewed reports', function () {
    $owner = User::factory()->create();
    $reporter = User::factory()->create();
    $moderator = User::factory()->moderator()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    $pendingReview = PostComment::factory()->create(['post_id' => $post->id, 'user_id' => $owner->id]);
    $pendingReview->autoHide();

    $pendingReports = PostComment::factory()->create(['post_id' => $post->id, 'user_id' => $owner->id]);
    $reporter->postCommentReports()->create(['post_comment_id' => $pendingReports->id, 'comment' => 'Rude']);

    $reviewedReport = PostComment::factory()->create(['post_id' => $post->id, 'user_id' => $owner->id]);
    $report = $reporter->postCommentReports()->create(['post_comment_id' => $reviewedReport->id, 'comment' => 'Rude']);
    $moderator->postCommentReportReviews()->create([
        'post_comment_report_id' => $report->id,
        'comment' => 'Checked',
        'status' => ReportReviewStatus::Invalid,
    ]);

    signInForModerationCounts($moderator);

    $this->getJson('/api/app/moderation-counts')
        ->assertOk()
        ->assertJsonPath('post_comments_pending_review', 1)
        ->assertJsonPath('post_comments_pending_reports', 1);
});

it('counts only ban threads where the banned user wrote last and the thread is open', function () {
    $admin = User::factory()->admin()->create();

    $pending = User::factory()->create()->ban('Spam', $admin);
    moderationCountsBanMessage($pending, $pending->user);

    $repliedTo = User::factory()->create()->ban('Spam', $admin);
    moderationCountsBanMessage($repliedTo, $repliedTo->user);
    moderationCountsBanMessage($repliedTo, $admin);

    $closed = User::factory()->create()->ban('Spam', $admin);
    moderationCountsBanMessage($closed, $closed->user);
    $closed->closeThread($admin);

    signInForModerationCounts($admin);

    $this->getJson('/api/app/moderation-counts')
        ->assertOk()
        ->assertJsonPath('ban_threads_pending_reply', 1);
});
