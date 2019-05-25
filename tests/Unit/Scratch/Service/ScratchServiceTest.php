<?php

namespace Tests\Unit\Scratch\Service;

use Biz\BaseTestCase;
use Biz\Scratch\Dao\ProjectDao;
use Biz\Scratch\Dao\ShareDao;
use Biz\Scratch\Service\ScratchService;

class ScratchServiceTest extends BaseTestCase
{
    public function testGetProject()
    {
        $user = $this->getCurrentUser();
        $fields = array(
            'userId' => $user['id'],
        );
        $project = $this->getProjectDao()->create($fields);

        $testProject = $this->getScratchService()->getProject($project['id']);

        $this->assertEquals($project, $testProject);
    }

    public function testGetShareInfo()
    {
        $share = $this->createShare('张三');

        $testshare = $this->getScratchService()->getShareInfo($share['id']);

        $this->assertEquals('张三', $testshare['nickname']);
        $this->assertEquals('月球旅行', $testshare['title']);
        $this->assertEquals('欣赏月球风光', $testshare['summary']);
        $this->assertEquals('点击移动', $testshare['usageText']);
    }

    public function testUpdateProject()
    {
        $new_project = $this->getProjectDao()->create(
            array(
                'userId' => 3,
            )
        );
        $project = $this->getScratchService()->updateProject(
            $new_project['id'],
            array(
                'userId' => 2,
                'fileUri' => '/tmp/srdic.sb2',
                'shareId' => 4,
            )
        );
        $this->assertEquals('3', $project['userId']);
        $this->assertEquals('/tmp/srdic.sb2', $project['fileUri']);
        $this->assertEquals('4', $project['shareId']);
    }

    public function testCreateProject()
    {
        $project = $this->getScratchService()->createProject(
            array(
                'userId' => '2',
            )
        );
        $this->assertEquals('2', $project['userId']);
    }

    public function testUpdateShareInfo()
    {
        $new_share = $this->createShare('张三');
        $new_share['nickname'] = '李四';
        $share = $this->getScratchService()->updateShareInfo($new_share['id'], $new_share);
        $this->assertEquals('李四', $share['nickname']);
        $this->assertEquals('月球旅行', $share['title']);
        $this->assertEquals('欣赏月球风光', $share['summary']);
        $this->assertEquals('点击移动', $share['usageText']);
    }

    public function testSaveShareInfo()
    {
        $new_project = $this->getProjectDao()->create(
            array(
                'userId' => 2,
            )
        );
        $project = $this->getScratchService()->saveShareInfo(
            $new_project,
            array(
                'nickname' => '张三',
                'title' => '月球旅行',
                'summary' => '欣赏月球风光',
                'usageText' => '点击移动',
            )
        );
        $share = $this->getShareDao()->get($project['shareId']);

        $this->assertEquals('张三', $share['nickname']);
        $this->assertEquals('月球旅行', $share['title']);
        $this->assertEquals('欣赏月球风光', $share['summary']);
        $this->assertEquals('点击移动', $share['usageText']);
    }

    public function testCreateShareInfo()
    {
        $share = $this->getScratchService()->createShareInfo(
            array(
                'nickname' => '张三',
                'title' => '月球旅行',
                'summary' => '欣赏月球风光',
                'usageText' => '点击移动',
            )
        );
        $this->assertEquals('张三', $share['nickname']);
        $this->assertEquals('月球旅行', $share['title']);
        $this->assertEquals('欣赏月球风光', $share['summary']);
        $this->assertEquals('点击移动', $share['usageText']);
    }

    public function testAddHits()
    {
        $share = $this->createShare('张三');
        $this->getScratchService()->addHits($share['id']);
        $test_share = $this->getShareDao()->get($share['id']);
        $this->assertEquals('1', $test_share['hits']);
    }

    public function testLike()
    {
        $share = $this->createShare('张三');
        $this->getScratchService()->like($share['id']);
        $test_share = $this->getShareDao()->get($share['id']);
        $this->assertEquals('1', $test_share['upsNum']);
    }

    public function testCancelLike()
    {
        $share = $this->createShare('张三');
        $this->getScratchService()->like($share['id']);
        $this->getScratchService()->like($share['id']);
        $this->getScratchService()->cancelLike($share['id']);
        $test_share = $this->getShareDao()->get($share['id']);
        $this->assertEquals('1', $test_share['upsNum']);
    }

    public function createShare($nickname)
    {
        return $this->getShareDao()->create(
            array(
                'nickname' => $nickname,
                'title' => '月球旅行',
                'summary' => '欣赏月球风光',
                'usageText' => '点击移动',
            )
        );
    }

    /**
     * @return ProjectDao
     */
    protected function getProjectDao()
    {
        return $this->createDao('Scratch:ProjectDao');
    }

    /**
     * @return ScratchService
     */
    protected function getScratchService()
    {
        return $this->createService('Scratch:ScratchService');
    }

    /**
     * @return ShareDao
     */
    protected function getShareDao()
    {
        return $this->createDao('Scratch:ShareDao');
    }
}
