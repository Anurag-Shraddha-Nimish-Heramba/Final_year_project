<?php

namespace App\Http\Resources;

use App\Models\Status;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'organization' => OrganizationResource::make($this->organization),
            'author' => UserResource::make($this->user),
            'desc_comment' => CommentResource::make($this->comment),
            'assignee' => UserResource::make(User::find($this->assignee_id)),
            'status' => StatusResource::make(Status::find($this->status_id)),
            'tags' => TagResource::collection($this->tags),
        ];
    }
}
