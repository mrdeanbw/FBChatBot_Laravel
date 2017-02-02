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
        return $this->bugRepository->create($request->all());
    }

    public function updateBug(Request $request)
    {
        return $this->bugRepository->update($request->all());
    }

    public function destroyBug(Request $request)
    {
        return $this->bugRepository->destroy($request->id);
    }

    public function createComment(Request $request)
    {
        return $this->commentRepository->create($request->all());
    }

    public function updateComment(Request $request)
    {
        return $this->commentRepository->update($request->all());
    }

    public function destroyComment(Request $request)
    {
        return $this->commentRepository->destroy($request->id);
    }

    public function transformer()
    {
        return null;
    }
}
