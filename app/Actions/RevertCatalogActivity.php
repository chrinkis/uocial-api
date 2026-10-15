<?php

namespace App\Actions;

use App\Models\Concerns\LogsCatalogActivity;
use App\Models\Relations\LoggedBelongsToMany;
use App\Models\User;
use App\Support\ActivityLogs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Undoes a catalog change by applying its inverse and recording a `reverted` activity.
 *
 * History is never rewritten: the original entry stays and the revert is a new entry.
 * A revert is refused when later changes have made the original one impossible to undo cleanly.
 */
class RevertCatalogActivity
{
    /**
     * @throws ConflictHttpException
     * @throws UnprocessableEntityHttpException
     */
    public function __invoke(Activity $activity, User $reverter): Activity
    {
        if ($activity->event === 'reverted') {
            throw new UnprocessableEntityHttpException('A revert cannot be reverted. Make the change again instead.');
        }

        $subject = $this->subject($activity);

        try {
            return DB::transaction(function () use ($activity, $subject, $reverter) {
                $this->lockAgainstDoubleRevert($activity);

                $log = match ($activity->event) {
                    'updated' => $this->revertUpdate($activity, $subject),
                    'created', 'restored' => $this->revertByDeleting($subject),
                    'deleted' => $this->revertByRestoring($subject),
                    'attached' => $this->revertRelation($activity, $subject, attach: false),
                    'detached' => $this->revertRelation($activity, $subject, attach: true),
                    default => throw new UnprocessableEntityHttpException('This change cannot be reverted.'),
                };

                return $this->record($activity, $subject, $reverter, $log);
            });
        } catch (UniqueConstraintViolationException) {
            throw new ConflictHttpException('Reverting would duplicate an existing entry.');
        }
    }

    private function subject(Activity $activity): Model
    {
        $class = $activity->subject_type;

        if ($class === null
            || ! class_exists($class)
            || ! in_array(LogsCatalogActivity::class, class_uses_recursive($class), true)) {
            throw new UnprocessableEntityHttpException('This change cannot be reverted.');
        }

        $subject = $class::withTrashed()->find($activity->subject_id);

        return $subject ?? throw new ConflictHttpException('The entry no longer exists.');
    }

    private function lockAgainstDoubleRevert(Activity $activity): void
    {
        $alreadyReverted = Activity::query()
            ->inLog(ActivityLogs::CATALOG)
            ->forEvent('reverted')
            ->where('properties->reverted_activity_id', $activity->id)
            ->lockForUpdate()
            ->exists();

        if ($alreadyReverted) {
            throw new ConflictHttpException('This change has already been reverted.');
        }
    }

    /**
     * @return array{changes: array<string, mixed>, properties: array<string, mixed>}
     */
    private function revertUpdate(Activity $activity, Model $subject): array
    {
        $old = $activity->attribute_changes['old'] ?? [];
        $new = $activity->attribute_changes['attributes'] ?? [];

        if ($subject->trashed()) {
            throw new ConflictHttpException('The entry is deleted. Restore it first.');
        }

        foreach ($new as $attribute => $value) {
            if (! $this->same($subject->getAttributes()[$attribute] ?? null, $value)) {
                throw new ConflictHttpException("[{$attribute}] has been changed since. Revert the later change first.");
            }
        }

        activity()->withoutLogging(fn () => $subject->forceFill($old)->save());

        return [
            'changes' => ['attributes' => $old, 'old' => $new],
            'properties' => [],
        ];
    }

    /**
     * @return array{changes: array<string, mixed>, properties: array<string, mixed>}
     */
    private function revertByDeleting(Model $subject): array
    {
        if ($subject->trashed()) {
            throw new ConflictHttpException('The entry is already deleted.');
        }

        activity()->withoutLogging(fn () => $subject->delete());

        return ['changes' => [], 'properties' => []];
    }

    /**
     * @return array{changes: array<string, mixed>, properties: array<string, mixed>}
     */
    private function revertByRestoring(Model $subject): array
    {
        if (! $subject->trashed()) {
            throw new ConflictHttpException('The entry is not deleted.');
        }

        activity()->withoutLogging(fn () => $subject->restore());

        return ['changes' => [], 'properties' => []];
    }

    /**
     * @return array{changes: array<string, mixed>, properties: array<string, mixed>}
     */
    private function revertRelation(Activity $activity, Model $subject, bool $attach): array
    {
        $relation = $activity->properties['relation'] ?? '';
        $ids = $activity->properties['ids'] ?? [];

        $query = method_exists($subject, $relation) ? $subject->$relation() : null;

        if (! $query instanceof LoggedBelongsToMany) {
            throw new UnprocessableEntityHttpException('This change cannot be reverted.');
        }

        $current = array_map(intval(...), $query->allRelatedIds()->all());

        $affected = $attach
            ? array_values(array_diff($ids, $current))
            : array_values(array_intersect($ids, $current));

        if ($affected === []) {
            throw new ConflictHttpException('The relation has been changed since.');
        }

        activity()->withoutLogging(fn () => $attach ? $query->attach($affected) : $query->detach($affected));

        return [
            'changes' => [],
            'properties' => ['relation' => $relation, 'ids' => $affected],
        ];
    }

    /**
     * @param  array{changes: array<string, mixed>, properties: array<string, mixed>}  $log
     */
    private function record(Activity $activity, Model $subject, User $reverter, array $log): Activity
    {
        $logger = activity()
            ->useLog(ActivityLogs::CATALOG)
            ->performedOn($subject)
            ->causedBy($reverter)
            ->event('reverted')
            ->withProperties([
                ...$log['properties'],
                'reverted_activity_id' => $activity->id,
                'reverted_event' => $activity->event,
            ]);

        if ($log['changes'] !== []) {
            $logger->withChanges($log['changes']);
        }

        /** @var Activity $recorded */
        $recorded = $logger->log('reverted');

        return $recorded;
    }

    private function same(mixed $a, mixed $b): bool
    {
        return (string) ($a ?? '') === (string) ($b ?? '');
    }
}
