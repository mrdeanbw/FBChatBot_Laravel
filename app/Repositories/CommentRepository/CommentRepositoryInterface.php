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
     * @param $commentId
     * @return mixed
     */

    public function getById($commentId);

    public function create($inputs);

    /**
     * Update a single comment
     *
     * @param $inputs
     * @return boolean
     */

    public function update($inputs);

    /**
     * Delete a single comment
     *
     * @param $bugId
     * @return boolean
     */

    public function destroy($commentId);
}