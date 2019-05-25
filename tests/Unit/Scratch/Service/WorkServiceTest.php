<?php

namespace Tests\Unit\Scratch\Service;

use Biz\BaseTestCase;
use Biz\Content\Dao\FileDao;
use Biz\Scratch\Dao\ProjectDao;
use Biz\Scratch\Dao\ScratchWorkDao;
use Biz\Scratch\Service\WorkService;

class WorkServiceTest extends BaseTestCase
{
    public function testGetWorkByProjectId()
    {
        $user = $this->getCurrentUser();
        $fields = array(
            'userId' => $user['id'],
        );
        $project = $this->getProjectDao()->create($fields);
        $work = $this->getScratchWorkDao()->create(array(
            'userId' => $user['id'],
            'projectId' => $project['id'],
        ));

        $testWork = $this->getWorkService()->getWorkByProjectId($user['id'], $project['id']);

        $this->assertEquals($project['id'], $testWork['projectId']);
    }

    public function testUpdateWorkInfo()
    {
        $work = $this->getScratchWorkDao()->create(array(
            'userId' => 2,
            'projectId' => 4,
        ));

        $testWork = $this->getWorkService()->updateWorkInfo($work, array(
            'title' => '喵喵大作战',
            'smallPicture' => 'public://course/2018/09-22/165538adaa86660883.png',
            'middlePicture' => 'public://course/2018/09-22/165538adaa86660883.png',
            'largePicture' => 'public://course/2018/09-22/165538adaa86660883.png',
            'publish' => 'true',
        ));

        $this->assertEquals('喵喵大作战', $testWork['title']);
        $this->assertEquals('verify', $testWork['status']);
    }

    public function testGeneratePicture()
    {
        $file1 = $this->createFile('public://default/2018/09-05/170001147b7a243267.jpg');
        $file2 = $this->createFile('public://default/2018/09-05/180001147b7a243267.jpg');
        $file3 = $this->createFile('public://default/2018/09-05/190001147b7a243267.jpg');
        $coverarray = array(
            array('type' => 'large', 'id' => $file1['id']),
            array('type' => 'middle', 'id' => $file2['id']),
            array('type' => 'small', 'id' => $file3['id']),
        );
        list($fileUri, $imgAttr) = $this->getWorkService()->generatePicture($coverarray);

        $this->assertEquals('public://default/2018/09-05/170001147b7a243267.jpg', $fileUri);
        $this->assertEquals(json_encode($coverarray), $imgAttr);
    }

    public function testGetPictureFromImage()
    {
        $file1 = $this->createFile('public://default/2018/09-05/170001147b7a243267.jpg');
        $file2 = $this->createFile('public://default/2018/09-05/180001147b7a243267.jpg');
        $file3 = $this->createFile('public://default/2018/09-05/190001147b7a243267.jpg');
        $coverarray = array(
            array('type' => 'large', 'id' => $file1['id']),
            array('type' => 'middle', 'id' => $file2['id']),
            array('type' => 'small', 'id' => $file3['id']),
        );
        $imgSrc = '/files/course/2018/09-27/0932353574d9029158.jpg?cover='.json_encode($coverarray);
        $picture = $this->getWorkService()->getPictureFromImage($imgSrc);

        $this->assertEquals('public://default/2018/09-05/170001147b7a243267.jpg', $picture['largePicture']);
        $this->assertEquals('public://default/2018/09-05/180001147b7a243267.jpg', $picture['middlePicture']);
        $this->assertEquals('public://default/2018/09-05/190001147b7a243267.jpg', $picture['smallPicture']);
    }

    public function testSearchWorks()
    {
        $this->getScratchWorkDao()->create(array(
            'userId' => 2,
            'projectId' => 4,
        ));
        $work = $this->getWorkService()->searchWorks(
            array('userId' => 2, 'status' => 'draft', 'projectId' => 4),
            array('createdTime' => 'DESC'),
            0,
            1
        );
        $this->assertEquals(4, $work[0]['projectId']);
    }

    public function testSearchWorkCount()
    {
        $this->getScratchWorkDao()->create(array(
            'userId' => 2,
            'projectId' => 5,
        ));
        $workNum = $this->getWorkService()->searchWorkCount(
            array('userId' => 2, 'status' => 'draft', 'projectId' => 5)
        );
        $this->assertEquals(1, $workNum);
    }

    public function testCancelPublishWork()
    {
        $work = $this->getScratchWorkDao()->create(array(
            'userId' => 2,
            'projectId' => 6,
        ));

        $test_work = $this->getWorkService()->cancelPublishWork($work['id']);

        $this->assertEquals('verify', $test_work['status']);
    }

    public function testGetWork()
    {
        $work = $this->getScratchWorkDao()->create(array(
            'userId' => 2,
            'projectId' => 7,
        ));

        $test_work = $this->getWorkService()->getWork($work['id']);

        $this->assertEquals(7, $test_work['projectId']);
    }

    public function testRecommendWork()
    {
        $work = $this->getScratchWorkDao()->create(array(
            'userId' => 2,
            'projectId' => 8,
        ));

        $test_work = $this->getWorkService()->recommendWork($work['id'], 1023);

        $this->assertEquals(1023, $test_work['recommendedSeq']);
    }

    public function testCancelRecommendWork()
    {
        $work = $this->getScratchWorkDao()->create(array(
            'userId' => 2,
            'projectId' => 9,
            'recommended' => 1,
            'recommendedSeq' => 1022,
            'recommendedTime' => time(),
        ));

        $test_work = $this->getWorkService()->cancelRecommendWork($work['id']);

        $this->assertEquals(0, $test_work['recommended']);
    }

    public function testPublishWork()
    {
        $work = $this->getScratchWorkDao()->create(array(
            'userId' => 2,
            'projectId' => 10,
        ));

        $test_work = $this->getWorkService()->publishWork($work['id']);

        $this->assertEquals('published', $test_work['status']);
    }

    public function testGetLastWorkByUserId()
    {
        $work = $this->getScratchWorkDao()->create(array(
            'userId' => 2,
            'projectId' => 11,
        ));

        $test_work = $this->getWorkService()->getLastWorkByUserId(2);

        $this->assertEquals(11, $test_work['projectId']);
    }

    public function testCreateWork()
    {
        $work = $this->getWorkService()->createWork(array('userId' => 2));

        $this->assertEquals(2, $work['userId']);
    }

    public function testAddHits()
    {
        $work = $this->getScratchWorkDao()->create(array(
            'userId' => 2,
            'projectId' => 12,
        ));

        $this->getWorkService()->addHits($work['id']);

        $test_work = $this->getScratchWorkDao()->get($work['id']);

        $this->assertEquals(1, $test_work['hits']);
    }

    public function testLike()
    {
        $work = $this->getScratchWorkDao()->create(array(
            'userId' => 2,
            'projectId' => 13,
        ));

        $this->getWorkService()->like($work['id']);

        $test_work = $this->getScratchWorkDao()->get($work['id']);

        $this->assertEquals(1, $test_work['upsNum']);
    }

    public function testCancelLike()
    {
        $work = $this->getScratchWorkDao()->create(array(
            'userId' => 2,
            'projectId' => 14,
            'upsNum' => 4,
        ));

        $this->getWorkService()->cancelLike($work['id']);

        $test_work = $this->getScratchWorkDao()->get($work['id']);

        $this->assertEquals(3, $test_work['upsNum']);
    }

    private function createFile($fileUri)
    {
        return $this->getFileDao()->create(array(
            'groupId' => 1,
            'userId' => 2,
            'uri' => $fileUri,
            'size' => '4000',
        ));
    }

    /**
     * @return WorkService
     */
    protected function getWorkService()
    {
        return $this->createService('Scratch:WorkService');
    }

    /**
     * @return ScratchWorkDao
     */
    protected function getScratchWorkDao()
    {
        return $this->createDao('Scratch:ScratchWorkDao');
    }

    /**
     * @return ProjectDao
     */
    protected function getProjectDao()
    {
        return $this->createDao('Scratch:ProjectDao');
    }

    /**
     * @return FileDao
     */
    protected function getFileDao()
    {
        return $this->createDao('Content:FileDao');
    }
}
