<?php

namespace App\Repositories\BugRepository;

interface BugRepositoryInterface
{
    /**
     * Get bug by id
     *
     * @param $id
     * @return mixed
     */

    public function getById($id);

    /**
     * Get all bugs with a given count
     *
     * @param $count
     * @return mixed
     */

    public function getAll($count);

    /**
     * Get newest bugs
     *
     * @param $count
     * @return mixed
     */
    public function getNewest($count);

    /**
     * Get bugs sorted by upvotes/downvotes
     *
     * @param $table
     * @param $order
     * @param $count
     * @return mixed
     */

    public function getSortedByVotes($table, $order, $count);

    /**
     * Get bugs by given status
     *
     * @param $table
     * @param $order
     * @param $count
     * @return mixed
     */

    public function getByStatus($status, $count);

    /**
     * Create a single bug
     *
     * @param $inputs
     * @return mixed
     */

    public function create($inputs);

    /**
     * Update a single bug
     *
     * @param $inputs
     * @return boolean
     */

    public function update($inputs);

    /**
     * Delete a single bug
     *
     * @param $bugId
     * @return boolean
     */

    public function destroy($bugId);
}