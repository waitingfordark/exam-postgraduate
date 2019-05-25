<?php

namespace Biz\Scratch\Dao\Impl;

use Codeages\Biz\Framework\Dao\GeneralDaoImpl;
use Biz\Scratch\Dao\ProjectDao;

class ProjectDaoImpl extends GeneralDaoImpl implements ProjectDao
{
    protected $table = 'scratch_project';

    public function declares()
    {
        $declares['conditions'] = array(
            'userId = :userId',
            'id = :id',
            'shareId = :shareId',
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
