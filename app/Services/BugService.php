<?php namespace App\Services;

use App\Repositories\BugRepository\BugRepositoryInterface;
use App\Repositories\CommentRepository\CommentRepositoryInterface;


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
     * @param UserRepository $userRepo
     * @param AuthService    $FacebookAuth
     */

    public function __construct(BugRepositoryInterface $bugRepo, CommentRepositoryInterface $commentRepo)
    {
        $this->bugRepository = $bugRepo;
        $this->commentRepository = $commentRepo;
    }

    /*
     * Return a tree of nested comments using recursion
     *
     * @param $parentId
     */

    private function getNestedComments($parentId)
    {
        $result = [];

        $children = $this->commentRepository->getChildComments($parentId);

        $result[$parentId] = $children;

        foreach($children as $k => $v)
        {
            $result[$v->id][$v->parent_id] = $this->commentRepository->getChildComments($v->id);
        }

        return $result;
    }

}