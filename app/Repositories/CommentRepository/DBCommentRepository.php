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
        return Bug::find($bugId)->comments();
    }

    /*
     * ToDo: rewrite to use eloquent relation approach
     */

    public function getById($commentId, $bugId)
    {
        return DB::table('bug_comments')
            ->where('id', '=', $commentId)
            ->where('bug_id', '=', $bugId)
            ->whereNull('deleted_at')
            ->get();
    }

    public function getChildComments($parentId)
    {
        return Comment::where('parent_id', '=', $parentId)->comments();
    }
}