<?php

namespace AppBundle\Controller;

use Biz\LeaveMessage\Service\LeaveMessageService;
use Symfony\Component\HttpFoundation\Request;

class LeaveMessageController extends BaseController
{
    public function commitAction(Request $request)
    {
        if ($request->isMethod('POST')) {
            $fields = $request->request->all();
            $this->getLeaveMessageService()->createLeaveMessage($fields);

            return $this->redirectToRoute('homepage');
        }
    }

    /**
     * @return LeaveMessageService
     */
    protected function getLeaveMessageService()
    {
        return $this->createService('LeaveMessage:LeaveMessageService');
    }
}
