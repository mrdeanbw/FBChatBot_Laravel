<?php namespace App\Services;

use Common\Repositories\BugRepository\BugRepositoryInterface;
use Common\Repositories\CommentRepository\CommentRepositoryInterface;


class BugService
{
    /**
     * @type BugRepositoryInterface
     */
    private $bugRepository;

    /**
     * @type CommentRepositoryInterface
     */
    private $commentRepository;

    /**
     * BugService constructor
     *
     * @param BugRepositoryInterface $bugRepo
     * @param CommentRepositoryInterface $commentRepo
     */

    public function __construct(BugRepositoryInterface $bugRepo, CommentRepositoryInterface $commentRepo)
    {
        $this->bugRepository = $bugRepo;
        $this->commentRepository = $commentRepo;
    }

    public function getBugs($inputs)
    {
        if(isset($inputs['newest']))
        {
            return $this->bugRepository->getAll($inputs['newest']);
        }
        else if(isset($inputs['table']) && isset($inputs['order']) && isset($inputs['count']))
        {
            return $this->bugRepository->getSortedByVotes($inputs['table'], $inputs['order'], $inputs['count']);
        }
        else if(isset($inputs['status']) && isset($inputs['count']))
        {
            return $this->bugRepository->getByStatus($inputs['status'], $inputs['count']);
        }
        else
        {
            return $this->bugRepository->getAll(isset($inputs['count']) ? $inputs['count'] : 20);
        }
    }

}