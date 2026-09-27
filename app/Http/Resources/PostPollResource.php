<?php

namespace App\Http\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class PostPollResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $options = $this->options()
            ->withCount('votes')
            ->withCount(['votes as user_votes_count' => function (Builder $query) {
                $query->where('user_id', Auth::user()->id);
            }])
            ->get();

        return [
            'id' => $this->id,
            'allow_multiple_votes' => $this->allow_multiple_votes,
            'ends_at' => $this->ends_at,
            'created_at' => $this->created_at,
            'total_votes' => $options->sum('votes_count'),
            'options' => PostPollOptionResource::collection($options),
        ];
    }
}
