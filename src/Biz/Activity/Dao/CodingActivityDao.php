<?php

namespace Biz\Activity\Dao;

use Codeages\Biz\Framework\Dao\GeneralDaoInterface;

interface CodingActivityDao extends GeneralDaoInterface
{
    public function findByIds($Ids);
}
