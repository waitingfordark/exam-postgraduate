<?php

namespace Biz\Scratch\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use Biz\BaseService;
use Biz\Content\Service\FileService;
use Biz\Scratch\Dao\ProjectDao;
use Biz\Scratch\Dao\ScratchWorkDao;
use Biz\Scratch\Dao\ShareDao;
use Biz\Scratch\Service\WorkService;
use Biz\System\Service\LogService;
use Biz\Task\Dao\TaskResultDao;
use Biz\User\Service\UserService;

class WorkServiceImpl extends BaseService implements WorkService
{
    public function getWorkByProjectId($userId, $projectId)
    {
        return $this->getScratchWorkDao()->getWorkByProjectId($userId, $projectId);
    }

    public function saveWorkInfo($work, $fields)
    {
        if (!ArrayToolkit::requireds(
            $fields,
            array('title', 'smallPicture', 'middlePicture', 'largePicture', 'publish')
        )) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $fields = ArrayToolkit::filter(
            $fields,
            array(
                'title' => '',
                'smallPicture' => '',
                'middlePicture' => '',
                'largePicture' => '',
                'publish' => '',
            )
        );
        if ('true' == $fields['publish']) {
            $fields['status'] = 'verify';
            $fields['publishTime'] = time();
        }
        unset($fields['publish']);

        $work = $this->getScratchWorkDao()->update($work['id'], $fields);
        $this->getLogService()->info(
            'scratch',
            'save_work',
            '保存作品(#'.$work['id'].')',
            $fields
        );

        return $work;
    }

    public function updateWorkInfo($work, $fields)
    {
        if (!ArrayToolkit::requireds($fields, array('title', 'publish'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $fields = ArrayToolkit::filter(
            $fields,
            array(
                'title' => '',
                'smallPicture' => '',
                'middlePicture' => '',
                'largePicture' => '',
                'publish' => '',
            )
        );
        if ('true' == $fields['publish'] && 'published' != $work['status']) {
            $fields['status'] = 'verify';
            $fields['publishTime'] = time();
        }
        unset($fields['publish']);

        $work = $this->getScratchWorkDao()->update($work['id'], $fields);

        $this->getLogService()->info(
            'scratch',
            'save_work',
            '保存作品 (#'.$work['id'].')',
            $fields
        );

        return $work;
    }

    public function generatePicture($coverArray)
    {
        if (empty($coverArray)) {
            throw $this->createInvalidArgumentException('Invalid Param: cover');
        }
        $file = array('uri' => '');
        foreach ($coverArray as $cover) {
            if ('large' == $cover['type']) {
                $file = $this->getFileService()->getFile($cover['id']);
            }
        }

        $imageAttr = json_encode($coverArray);

        return array($file['uri'], $imageAttr);
    }

    public function getPictureFromImage($image)
    {
        if (empty($image)) {
            throw $this->createInvalidArgumentException('Invalid Param: image');
        }
        $imageData = explode('?cover=', $image);
        if (!isset($imageData[1]) || 0 == strlen($imageData[1])) {
            return array();
        }

        $covers = json_decode($imageData[1], true);

        foreach ($covers as $cover) {
            $file = $this->getFileService()->getFile($cover['id']);
            $result[$cover['type'].'Picture'] = $file['uri'];
        }

        return $result;
    }

    public function searchWorks(array $conditions, $orderBy, $start, $limit, array $columns = array())
    {
        $preparedCondtions = $this->prepareConditions($conditions);

        return $this->getScratchWorkDao()->search($preparedCondtions, $orderBy, $start, $limit, $columns);
    }

    public function searchWorkCount(array $conditions)
    {
        $preparedCondtions = $this->prepareConditions($conditions);

        return $this->getScratchWorkDao()->count($preparedCondtions);
    }

    public function cancelPublishWork($id)
    {
        $user = $this->getCurrentUser();
        $work = $this->getScratchWorkDao()->update(
            $id,
            array(
                'status' => 'verify',
                'recommended' => 0,
                'recommendedSeq' => 0,
                'recommendedTime' => 0,
                'verifyTime' => time(),
                'verifyUserId' => $user['id'],
            )
        );

        $this->getLogService()->info(
            'scratch_work',
            'cancel_publish',
            sprintf('取消审核 Scratch 作品《%s》(#%s)', $work['title'], $work['id']),
            array('work' => $work)
        );

        return $work;
    }

    public function publishWork($id)
    {
        $user = $this->getCurrentUser();
        $work = $this->getScratchWorkDao()->update(
            $id,
            array(
                'status' => 'published',
                'verifyTime' => time(),
                'verifyUserId' => $user['id'],
            )
        );

        $this->getLogService()->info(
            'scratch_work',
            'publish',
            sprintf('通过审核 Scratch 作品《%s》(#%s)', $work['title'], $work['id']),
            array('work' => $work)
        );

        return $work;
    }

    public function getWork($id)
    {
        return $this->getScratchWorkDao()->get($id);
    }

    public function recommendWork($id, $number)
    {
        if (!is_numeric($number)) {
            throw $this->createAccessDeniedException('recommend seq must be number!');
        }

        $fields = array(
            'recommended' => 1,
            'recommendedSeq' => (int) $number,
            'recommendedTime' => time(),
        );

        $work = $this->getScratchWorkDao()->update($id, $fields);

        $this->getLogService()->info(
            'scratch_work',
            'recommend_work',
            sprintf('推荐 Scratch 作品《%s》(#%s)', $work['title'], $work['id']),
            array('work' => $work)
        );

        return $work;
    }

    public function cancelRecommendWork($id)
    {
        $fields = array(
            'recommended' => 0,
            'recommendedTime' => 0,
            'recommendedSeq' => 0,
        );
        $work = $this->getScratchWorkDao()->update(
            $id,
            $fields
        );

        $this->getLogService()->info(
            'scratch_work',
            'cancel_recommend_work',
            sprintf('取消推荐 Scratch 作品《%s》(#%s)', $work['title'], $work['id']),
            array('work' => $work)
        );

        return $work;
    }

    public function getLastWorkByUserId($userId)
    {
        $work = $this->getScratchWorkDao()->search(array('userId' => $userId), array('createdTime' => 'desc'), 0, 1);

        return count($work) ? $work[0] : array();
    }

    public function createWork($work)
    {
        try {
            $this->beginTransaction();
            $project = $this->getProjectDao()->create($work);

            $fields = array(
                'userId' => $project['userId'],
                'projectId' => $project['id'],
            );
            $work = $this->getScratchWorkDao()->create($fields);
            $this->getLogService()->info(
                'scratch',
                'create_work',
                '创建作品(#'.$work['id'].')',
                $fields
            );
            $this->commit();

            return $work;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function addHits($id)
    {
        $status = $this->getScratchWorkDao()->addHits($id);
        $this->getLogService()->info(
            'scratch',
            'work_hits',
            '作品页浏览+1(#'.$id.')',
            array($status)
        );

        return $status;
    }

    public function like($id)
    {
        $status = $this->getScratchWorkDao()->addUpsNum($id);
        $this->getLogService()->info(
            'scratch',
            'work_like',
            '作品页点赞+1(#'.$id.')',
            array($status)
        );

        return $status;
    }

    public function cancelLike($id)
    {
        $status = $this->getScratchWorkDao()->subtractUpsNum($id);
        $this->getLogService()->info(
            'scratch',
            'work_like',
            '作品页点赞-1(#'.$id.')',
            array($status)
        );

        return $status;
    }

    protected function prepareConditions($conditions)
    {
        $conditions = array_filter(
            $conditions,
            function ($value) {
                if (is_numeric($value)) {
                    return true;
                }

                return !empty($value);
            }
        );

        if (!empty($conditions['type']) && 'creator' == $conditions['type']) {
            if (!empty($conditions['keyword'])) {
                $user = $this->getUserService()->getUserByNickname($conditions['keyword']);
                $conditions['userId'] = $user ? $user['id'] : -1;
            }
        }

        return $conditions;
    }

    /**
     * @return ProjectDao
     */
    protected function getProjectDao()
    {
        return $this->createDao('Scratch:ProjectDao');
    }

    /**
     * @return ShareDao
     */
    protected function getShareDao()
    {
        return $this->createDao('Scratch:ShareDao');
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    /**
     * @return ScratchWorkDao
     */
    protected function getScratchWorkDao()
    {
        return $this->createDao('Scratch:ScratchWorkDao');
    }

    /**
     * @return FileService
     */
    protected function getFileService()
    {
        return $this->createService('Content:FileService');
    }

    /**
     * @return TaskResultDao
     */
    protected function getTaskResultDao()
    {
        return $this->createDao('Task:TaskResultDao');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }
}
