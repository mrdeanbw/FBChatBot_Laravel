<?php

namespace App\Repositories\CommentRepository;

interface CommentRepositoryInterface
{
    /*
     * All the methods return data based on a specific bug
     */

    /**
     * Get all comments
     *
     * @param $bugId
     * @return mixed
     */

    public function getAll($bugId);

    /**
     * Get all comments
     *
     * @param $bugId
     * @return mixed
     */

    public function getById($commentId, $bugId);
}