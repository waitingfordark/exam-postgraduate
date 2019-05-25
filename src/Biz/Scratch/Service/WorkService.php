<?php

namespace Biz\Scratch\Service;

interface WorkService
{
    public function getWorkByProjectId($userId, $projectId);

    public function saveWorkInfo($work, $fields);

    public function updateWorkInfo($work, $fields);

    public function generatePicture($coverArray);

    public function getPictureFromImage($image);

    public function searchWorks(array $conditions, $orderBy, $start, $limit, array $columns = array());

    public function searchWorkCount(array $conditions);

    public function cancelPublishWork($id);

    public function getWork($id);

    public function recommendWork($id, $number);

    public function cancelRecommendWork($id);

    public function publishWork($id);

    public function getLastWorkByUserId($userId);

    public function createWork($work);

    public function addHits($id);

    public function like($id);

    public function cancelLike($id);
}
