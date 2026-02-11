<!-- Add Comment Form -->
<div class="mb-4 p-3" style="background-color: #f8f9fa; border-radius: 8px;">
    <h6 class="mb-3"><i class="bx bx-comment-add"></i> Add a Comment</h6>
    <form action="/user/issue/{{$id}}/comment" method="POST">
        @csrf
        <div class="mb-3">
            <textarea class="form-control" name="content" rows="3" placeholder="Write your comment here..." required></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="bx bx-send"></i> Post Comment
        </button>
    </form>
</div>

<hr class="my-4">

<!-- Main Issue Description -->
<div class="mb-4">
   <div class="comment__card">
     <h6 class="mb-2"><i class="bx bx-user-circle"></i> {{$issue['author']['name']}}</h6>
     <p>{{$issue['desc_comment']['content']}}</p>
     <div class="comment__card-footer">
         <div><i class="bx bx-like"></i> {{$issue['desc_comment']['upvote']}}</div>
         <div><i class="bx bx-dislike"></i> {{$issue['desc_comment']['downvote']}}</div>
     </div>
  </div>
</div>

@php
    // Fetch ALL comments and build a tree structure
    use App\Models\Comment;
    
    // Get all comments that are descendants of the main issue comment
    $all_comments = Comment::where('parent_id', $issue['desc_comment']['id'])
        ->orWhere(function($query) use ($issue) {
            // Get comments whose parent is a child of the main comment
            $query->whereIn('parent_id', function($subquery) use ($issue) {
                $subquery->select('id')
                    ->from('comments')
                    ->where('parent_id', $issue['desc_comment']['id']);
            });
        })
        ->orderBy('created_at', 'asc')
        ->get();
    
    // Organize into parent-child structure
    $comment_tree = [];
    foreach ($all_comments as $comment) {
        if ($comment->parent_id == $issue['desc_comment']['id']) {
            // Direct child of main issue
            $comment_tree[$comment->id] = [
                'comment' => $comment,
                'children' => []
            ];
        }
    }
    
    // Add nested replies
    foreach ($all_comments as $comment) {
        if ($comment->parent_id != $issue['desc_comment']['id']) {
            // This is a reply to another comment
            if (isset($comment_tree[$comment->parent_id])) {
                $comment_tree[$comment->parent_id]['children'][] = $comment;
            }
        }
    }
@endphp

@if(count($comment_tree) > 0)
    <h6 class="mt-4 mb-3"><i class="bx bx-conversation"></i> Comments ({{count($all_comments)}})</h6>
    
    @foreach($comment_tree as $item)
        @php $comment = $item['comment']; @endphp
        
        <!-- Top-level comment -->
        <div class="ms-4 mb-3">
            <div class="comment__card">
                <h6 class="mb-2"><i class="bx bx-user"></i> {{$comment->user->name ?? 'User #' . $comment->user_id}}</h6>
                <p>{{$comment->content}}</p>
                <div class="comment__card-footer">
                    <div><i class="bx bx-like"></i> {{$comment->upvote}}</div>
                    <div><i class="bx bx-dislike"></i> {{$comment->downvote}}</div>
                </div>
            </div>
            
            <!-- Reply Form -->
            <div class="mt-2">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#reply-form-{{$comment->id}}">
                    <i class="bx bx-reply"></i> Reply
                </button>
                <div class="collapse mt-2" id="reply-form-{{$comment->id}}">
                    <form action="/user/issue/{{$id}}/comment" method="POST" class="p-2" style="background-color: #f8f9fa; border-radius: 4px;">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{$comment->id}}">
                        <div class="mb-2">
                            <textarea class="form-control form-control-sm" name="content" rows="2" placeholder="Write your reply..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bx bx-send"></i> Post Reply
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Nested Replies (indented further) -->
            @if(count($item['children']) > 0)
                <div class="ms-4 mt-2">
                    @foreach($item['children'] as $child)
                        <div class="comment__card mb-2" style="background-color: #f8f9fa;">
                            <h6 class="mb-2"><i class="bx bx-user"></i> {{$child->user->name ?? 'User #' . $child->user_id}}</h6>
                            <p>{{$child->content}}</p>
                            <div class="comment__card-footer">
                                <div><i class="bx bx-like"></i> {{$child->upvote}}</div>
                                <div><i class="bx bx-dislike"></i> {{$child->downvote}}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
@else
    <p class="text-muted mt-3"><i class="bx bx-info-circle"></i> No comments yet. Be the first to comment!</p>
@endif
