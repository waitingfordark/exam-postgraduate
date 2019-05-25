<?php

namespace Tests\Unit\Article\Service;

use Biz\BaseTestCase;
use Biz\LeaveMessage\Service\LeaveMessageService;

class LeaveMessageServiceTest extends BaseTestCase
{
    public function testGetLeaveMessage()
    {
        $newLeaveMessage = $this->createLeaveMessage();
        $getArticle = $this->getLeaveMessageService()->getLeaveMessage($newLeaveMessage['id']);
        $this->assertEquals('dili', $getArticle['name']);
        $this->assertEquals('测试留言', $getArticle['content']);
    }

    public function testCreateLeaveMessage()
    {
        $fields = array(
            'name' => 'dili',
            'email' => 'adili@howzhi.com',
            'phone' => '13209971340',
            'content' => '测试留言',
        );
        $article = $this->getLeaveMessageService()->createLeaveMessage($fields);
        $this->assertEquals('测试留言', $article['content']);
    }

    public function testSearchLeaveMessages()
    {
        $this->createLeaveMessage();

        $result = $this->getLeaveMessageService()->searchLeaveMessages(array(), array('createdTime' => 'DESC'), 0, 20);
        $this->assertEquals('1', count($result));
    }

    public function testCountLeaveMessages()
    {
        $this->createLeaveMessage();

        $result = $this->getLeaveMessageService()->countLeaveMessages(array());
        $this->assertEquals('1', $result);
    }

    public function testDeleteLeaveMessage()
    {
        $newLeaveMessage = $this->createLeaveMessage();
        $this->getLeaveMessageService()->deleteLeaveMessage($newLeaveMessage['id']);

        $this->assertEquals(null, $this->getLeaveMessageService()->getLeaveMessage($newLeaveMessage['id']));
        $this->assertEquals(0, $this->getLeaveMessageService()->countLeaveMessages(array()));
    }

    protected function createLeaveMessage()
    {
        $fields = array(
            'name' => 'dili',
            'email' => 'adili@howzhi.com',
            'phone' => '13209971340',
            'content' => '测试留言',
        );

        return $this->getLeaveMessageService()->createLeaveMessage($fields);
    }

    /**
     * @return LeaveMessageService
     */
    protected function getLeaveMessageService()
    {
        return $this->createService('LeaveMessage:LeaveMessageService');
    }
}
