<?php namespace App\Http\Controllers\API;

use App\Repositories\BugRepository\BugRepositoryInterface;
use App\Repositories\CommentRepository\CommentRepositoryInterface;
use Illuminate\Http\Request;
use App\Services\BugService;

class BugController extends APIController
{
    private $bugRepository;
    private $commentRepository;
    private $bugService;

    public function __construct(BugRepositoryInterface $bugRepository, CommentRepositoryInterface $commentRepository, BugService $bugService)
    {
        $this->bugRepository = $bugRepository;
        $this->commentRepository = $commentRepository;
        $this->bugService = $bugService;
    }

    public function get($newest = false, $popular = false, $status = false)
    {
    }

    public function transformer()
    {
        return null;
    }
}
