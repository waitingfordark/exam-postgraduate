<?php

namespace Biz\Scratch\Dao;

use Codeages\Biz\Framework\Dao\GeneralDaoInterface;

interface ScratchWorkDao extends GeneralDaoInterface
{
    public function findByIds(array $ids);

    public function getWorkByProjectId($userId, $projectId);

    public function addHits($id);

    public function addUpsNum($id);

    public function subtractUpsNum($id);
}
