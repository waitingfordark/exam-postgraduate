<?php

namespace Biz\LeaveMessage\Dao\Impl;

use Biz\LeaveMessage\Dao\LeaveMessageDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class LeaveMessageDaoImpl extends GeneralDaoImpl implements LeaveMessageDao
{
    protected $table = 'leave_message';

    public function declares()
    {
        return array(
            'timestamps' => array(
                'createdTime',
                'updatedTime',
            ),
            'orderbys' => array(
                'createdTime',
                'updatedTime',
            ),
            'conditions' => array(
                'createdTime >= :createdTime_GTE',
                'createdTime <= :createdTime_LTE',
            ),
        );
    }
}
