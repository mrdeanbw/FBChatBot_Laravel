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

    public function getById($commentId, $bugId)
    {
        return Comment::find($commentId)
            ->where('bug_id', '=', $bugId)
            ->whereNull('deleted_at')
            ->first();
    }
}