<?php

namespace Biz\PythonEditor\Dao;

use Codeages\Biz\Framework\Dao\GeneralDaoInterface;

interface WorkDao extends GeneralDaoInterface
{
    public function findByIds(array $ids);

    public function getWorkByProjectId($userId, $projectId);

    public function addHits($id);

    public function addUpsNum($id);

    public function subtractUpsNum($id);
}
