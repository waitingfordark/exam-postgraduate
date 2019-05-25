<?php

namespace Biz\LeaveMessage\Service;

interface LeaveMessageService
{
    public function getLeaveMessage($id);

    public function createLeaveMessage($field);

    public function searchLeaveMessages($conditions, $oderBy, $start, $limit);

    public function countLeaveMessages($conditions);

    public function deleteLeaveMessage($id);
}
