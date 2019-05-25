<?php

namespace Biz\Scratch\Dao;

use Codeages\Biz\Framework\Dao\GeneralDaoInterface;

interface ScratchMaterialDao extends GeneralDaoInterface
{
    public function findByIds(array $ids);

    public function findByType($type);
}
