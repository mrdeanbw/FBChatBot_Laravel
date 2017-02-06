<?php namespace App\Http\Controllers\API;

use App\Repositories\BugRepository\BugRepositoryInterface;
use App\Repositories\CommentRepository\CommentRepositoryInterface;
use App\Services\BugService;
use Illuminate\Http\Request;

class BugController extends APIController
{
    /*
     * ToDo: document all of this
     */
    private $commentRepository;
    private $bugRepository;
    private $bugService;

    public function __construct(BugRepositoryInterface $bugRepository, CommentRepositoryInterface $commentRepository,
                                BugService $bugService)
    {
        $this->bugRepository = $bugRepository;
        $this->commentRepository = $commentRepository;
        $this->bugService = $bugService;
    }

    public function getAllBugs(Request $request)
    {
        return $this->bugService->getBugs($request->all());
    }

    public function getSingleBig(Request $request)
    {
        return $this->bugRepository->getById($request->id);
    }

    public function createBug(Request $request)
    {
        $this->validate($request, [
            'title' => 'required|max:50',
            'content' => 'required|max:500',
            'author' => 'required|numeric'
        ]);

        return $this->bugRepository->create($request->all());
    }

    public function updateBug(Request $request)
    {
        $this->validate($request, [
            'title' => 'sometimes|max:50',
            'content' => 'sometimes|max:500',
            'author' => 'sometimes|numeric',
            'id' => 'required|numeric',
            'status' => 'sometimes|alpha'
        ]);

        return $this->bugRepository->update($request->all());
    }

    public function destroyBug(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|numeric'
        ]);
        return $this->bugRepository->destroy($request->id);
    }

    public function createComment(Request $request)
    {
        $this->validate($request, [
            'bug_id' => 'required|numeric',
            'content' => 'required|max:150',
            'author' => 'required|numeric'
        ]);

        return $this->commentRepository->create($request->all());
    }

    public function updateComment(Request $request)
    {
        $this->validate($request, [
            'content' => 'required|max:150'
        ]);

        return $this->commentRepository->update($request->all());
    }

    public function destroyComment(Request $request)
    {
        $this->validate($request, [
            'id' => 'numeric'
        ]);

        return $this->commentRepository->destroy($request->id);
    }

    public function transformer()
    {
        return null;
    }
}
