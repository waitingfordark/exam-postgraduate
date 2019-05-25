<?php

namespace Biz\LeaveMessage\Service\Impl;

use Biz\BaseService;
use Biz\LeaveMessage\Dao\LeaveMessageDao;
use Biz\LeaveMessage\Service\LeaveMessageService;

class LeaveMessageServiceImpl extends BaseService implements LeaveMessageService
{
    public function getLeaveMessage($id)
    {
        return $this->getLeaveMessageDao()->get($id);
    }

    public function createLeaveMessage($field)
    {
        if (!empty($field['content'])) {
            $field['content'] = $this->biz['html_helper']->purify($field['content'], false);
        }

        return $this->getLeaveMessageDao()->create($field);
    }

    public function searchLeaveMessages($conditions, $oderBy, $start, $limit)
    {
        $conditions = $this->prepareConditions($conditions);

        return $this->getLeaveMessageDao()->search($conditions, $oderBy, $start, $limit);
    }

    public function countLeaveMessages($conditions)
    {
        $conditions = $this->prepareConditions($conditions);

        return $this->getLeaveMessageDao()->count($conditions);
    }

    public function deleteLeaveMessage($id)
    {
        return $this->getLeaveMessageDao()->delete($id);
    }

    public function prepareConditions($conditions)
    {
        if (!empty($conditions['createdTime_GTE'])) {
            $conditions['createdTime_GTE'] = strtotime($conditions['createdTime_GTE']);
        }

        if (!empty($conditions['createdTime_LTE'])) {
            $conditions['createdTime_LTE'] = strtotime($conditions['createdTime_LTE']);
        }

        return $conditions;
    }

    /**
     * @return LeaveMessageDao
     */
    protected function getLeaveMessageDao()
    {
        return $this->createDao('LeaveMessage:LeaveMessageDao');
    }
}
