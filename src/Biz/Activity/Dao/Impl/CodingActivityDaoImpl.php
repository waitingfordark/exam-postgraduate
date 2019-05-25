<?php

namespace Biz\Activity\Dao\Impl;

use Biz\Activity\Dao\CodingActivityDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class CodingActivityDaoImpl extends GeneralDaoImpl implements CodingActivityDao
{
    protected $table = 'activity_coding';

    public function findByIds($Ids)
    {
        return $this->findInField('id', $Ids);
    }

    public function declares()
    {
        return array('timestamps' => array('createdTime', 'updatedTime'));
    }
}
