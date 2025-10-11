<?php

namespace App\Models;

use App\Models\Scopes\NonHiddenPostScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[ScopedBy([NonHiddenPostScope::class])]
class Post extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    /**
     * The hashtags that belong to the post.
     *
     * @return BelongsToMany<Hashtag,$this,Pivot>
     */
    public function hashtags(): BelongsToMany
    {
        return $this->belongsToMany(Hashtag::class);
    }

    /**
     * The labels that belong to the post.
     *
     * @return BelongsToMany<PostLabel,$this,Pivot>
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(PostLabel::class);
    }
}
