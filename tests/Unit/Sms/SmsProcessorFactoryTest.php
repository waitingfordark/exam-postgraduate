<?php

namespace Tests\Unit\Sms;

use Biz\BaseTestCase;
use Biz\Sms\SmsProcessor\SmsProcessorFactory;
use Biz\Sms\SmsProcessor\TaskSmsProcessor;

class SmsProcessorFactoryTest extends BaseTestCase
{
    public function testCreate()
    {
        $class = SmsProcessorFactory::create('task');
        $this->assertEquals(true, $class instanceof TaskSmsProcessor);
    }

    /**
     * @throws \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     * @expectedExceptionMessage 短信类型不存在
     */
    public function testCreateWithException()
    {
        SmsProcessorFactory::create('');
    }
}
