<?php

namespace App\Models;

use App\Enums\DataReportReason;
use Database\Factories\DepartmentReportFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentReport extends Model
{
    /** @use HasFactory<DepartmentReportFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * department_id and duplicate_of_id are set through the department() and duplicateOf()
     * relations, not mass assignment. The resolved_* attributes are set explicitly when an
     * admin resolves the report.
     *
     * @var list<string>
     */
    protected $fillable = [
        'reason',
        'comment',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => DataReportReason::class,
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * The user that created the report.
     *
     * @return BelongsTo<User,$this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The reported department, null when the report suggests a new one.
     *
     * @return BelongsTo<Department,$this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The department the reported one duplicates.
     *
     * @return BelongsTo<Department,$this>
     */
    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'duplicate_of_id');
    }

    /**
     * The admin that resolved the report.
     *
     * @return BelongsTo<User,$this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Only the reports that have been resolved.
     *
     * @param  Builder<DepartmentReport>  $query
     */
    #[Scope]
    protected function resolved(Builder $query): void
    {
        $query->whereNotNull('resolved_at');
    }

    /**
     * Only the reports that are still open.
     *
     * @param  Builder<DepartmentReport>  $query
     */
    #[Scope]
    protected function unresolved(Builder $query): void
    {
        $query->whereNull('resolved_at');
    }
}
