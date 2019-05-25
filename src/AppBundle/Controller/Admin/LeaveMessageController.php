<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Common\Paginator;
use AppBundle\Common\PHPExcelToolkit;
use Biz\LeaveMessage\Service\LeaveMessageService;
use Symfony\Component\HttpFoundation\Request;

class LeaveMessageController extends BaseController
{
    public function indexAction(Request $request)
    {
        $conditions = $request->query->all();

        $paginator = new Paginator(
            $this->get('request'),
            $this->getLeaveMessageService()->countLeaveMessages($conditions),
            20
        );

        $leaveMessages = $this->getLeaveMessageService()->searchLeaveMessages(
            $conditions,
            array('createdTime' => 'desc'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render(
            'admin/leave-message/index.html.twig',
            array(
                'leaveMessages' => $leaveMessages,
                'paginator' => $paginator,
            )
        );
    }

    public function exportAction(Request $request)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException();
        }

        $leaveMessages = $this->getLeaveMessageService()->searchLeaveMessages(
            $request->query->all(),
            array('createdTime' => 'desc'),
            0,
            PHP_INT_MAX
        );

        $filename = date('ymd').'leave-messages.xls';
        $execelInfo = $this->makeInfo($user);
        $objWriter = PHPExcelToolkit::export($leaveMessages, $execelInfo);
        $this->setHeader($filename);
        $objWriter->save('php://output');
    }

    protected function makeInfo($user)
    {
        $title = array(
            'name' => '姓名',
            'email' => '邮箱',
            'phone' => '电话',
            'content' => '留言内容',
            'createdTime' => '留言时间',
        );
        $info = array();
        $info['title'] = $title;
        $info['creator'] = $user['nickname'];
        $info['sheetName'] = '成员';

        return $info;
    }

    protected function setHeader($filename)
    {
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename={$filename}");
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
    }

    /**
     * @return LeaveMessageService
     */
    protected function getLeaveMessageService()
    {
        return $this->createService('LeaveMessage:LeaveMessageService');
    }
}
