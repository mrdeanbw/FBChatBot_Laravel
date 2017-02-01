<?php namespace App\Http\Controllers\API;

use App\Repositories\BugRepository\BugRepositoryInterface;
use App\Services\BugService;
use Illuminate\Http\Request;

class BugController extends APIController
{
    private $bugRepository;
    private $bugService;

    public function __construct(BugRepositoryInterface $bugRepository, BugService $bugService)
    {
        $this->bugRepository = $bugRepository;
        $this->bugService = $bugService;
    }

    public function getAll(Request $request)
    {
        return $this->bugService->getBugs($request->all());
    }

    public function getSingle($bugId)
    {
        return $this->bugRepository->getById($bugId);
    }

    public function transformer()
    {
        return null;
    }
}
