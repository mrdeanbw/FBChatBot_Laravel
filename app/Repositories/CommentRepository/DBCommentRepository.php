<?php namespace App\Repositories\CommentRepository;

use App\Repositories\CommentRepository\CommentRepositoryInterface;
use DB;
use App\Models\Comment;
use App\Models\Bug;

class DBCommentRepository implements CommentRepositoryInterface
{
    protected $model;

    public function __construct()
    {
        $this->model = new \App\Models\Comment();
    }

    public function getAll($bugId)
    {
        return Bug::find($bugId)
            ->comments();
    }

    public function getById($commentId)
    {
        return Comment::find($commentId)
            ->whereNull('deleted_at')
            ->first();
    }

    public function create($inputs)
    {
        $comment = new Comment;

        $comment->content = isset($inputs['content']) ? $inputs['content'] : null;
        $comment->author_id = isset($inputs['author']) ? $inputs['author'] : 1;
        $comment->bug_id = isset($inputs['bug_id']) ? $inputs['bug_id'] : 0;
        $comment->parent_id = isset($inputs['parent_id']) ? $inputs['parent_id'] : 0;

        return $comment->save();
    }

    public function update($inputs)
    {
        if(isset($inputs['id']))
        {
            $comment = $this->getById($inputs['id']);

            $updateData = [
                'content' => isset($inputs['content']) ? $inputs['content'] : $comment->content,
            ];

            return Comment::find($inputs['id'])->update($inputs['id'], $updateData);
        }

        return false;
    }

    public function destroy($bugId)
    {
        return Comment::destroy($bugId);
    }
}