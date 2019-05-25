<?php

namespace Biz\Scratch\Service;

interface ScratchService
{
    public function getProject($projectId);

    public function getShareInfo($shareId);

    public function updateProject($projectId, $fields);

    public function createProject($fields);

    public function updateShareInfo($shareId, $fields);

    public function saveShareInfo($project, $fields);

    public function createShareInfo($fields);

    public function addHits($shareId);

    public function like($shareId);

    public function cancelLike($shareId);
}
