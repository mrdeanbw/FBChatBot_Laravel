<?php namespace App\Repositories\BugRepository;

use App\Repositories\BugRepository\BugRepositoryInterface;
use DB;
use App\Models\Bug;

class DBBugRepository implements BugRepositoryInterface
{
    protected $model;

    public function __construct()
    {
        $this->model = new \App\Models\Bug();
    }

    public function getById($id)
    {
        return Bug::where('id', '=', $id)->first();
    }

    public function getAll($count)
    {
        return Bug::take($count)->get();
    }

    public function getNewest($count)
    {
        return Bug::whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->take($count)
            ->get();
    }

    public function getSortedByVotes($table, $order, $count)
    {
        return Bug::whereNull('deleted_at')
            ->orderBy($table, $order)
            ->take($count)
            ->get();
    }

    public function getByStatus($status, $count)
    {
        return Bug::whereNull('deleted_at')
            ->where('status', '=', $status)
            ->take($count)
            ->get();
    }

    public function create($inputs)
    {
        $bug = new Bug;

        $bug->title = isset($inputs['title']) ? $inputs['title'] : null;
        $bug->content = isset($inputs['content']) ? $inputs['content'] : null;
        $bug->author_id = isset($inputs['author']) ? $inputs['author'] : 1;
        $bug->title = isset($inputs['title']) ? $inputs['title'] : null;
        $bug->upvotes = 0;
        $bug->downvotes = 0;
        $bug->status = 'pending';

        return $bug->save();

    }

    public function update($inputs)
    {
        if(isset($inputs['id']))
        {
            $bug = $this->getById($inputs['id']);

            $updateData = [
                'content' => isset($inputs['content']) ? $inputs['content'] : $bug->content,
                'status' => isset($inputs['status']) ? $inputs['status'] : $bug->status
            ];

            return Bug::find($inputs['id'])->update($inputs['id'], $updateData);
        }

        return false;
    }

    public function destroy($bugId)
    {
        return Bug::destroy($bugId);
    }
}