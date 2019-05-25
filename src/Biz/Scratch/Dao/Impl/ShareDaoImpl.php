<?php

namespace Biz\Scratch\Dao\Impl;

use Codeages\Biz\Framework\Dao\GeneralDaoImpl;
use Biz\Scratch\Dao\ShareDao;

class ShareDaoImpl extends GeneralDaoImpl implements ShareDao
{
    protected $table = 'scratch_share';

    public function declares()
    {
        $declares['conditions'] = array(
            'id = :id',
        );

        $declares['timestamps'] = array(
            'createdTime',
            'updatedTime',
        );

        $declares['orderbys'] = array(
            'createdTime',
            'updatedTime',
        );

        return $declares;
    }
}
