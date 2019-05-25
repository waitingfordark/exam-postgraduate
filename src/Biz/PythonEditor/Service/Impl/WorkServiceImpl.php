<?php

namespace Biz\PythonEditor\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use Biz\BaseService;
use Biz\Content\Service\FileService;
use Biz\PythonEditor\Dao\WorkDao;
use Biz\Scratch\Dao\ProjectDao;
use Biz\Scratch\Dao\ShareDao;
use Biz\PythonEditor\Service\WorkService;
use Biz\System\Service\LogService;
use Biz\Task\Dao\TaskResultDao;
use Biz\User\Service\UserService;

class WorkServiceImpl extends BaseService implements WorkService
{
    public function getWorkByProjectId($userId, $projectId)
    {
        return $this->getWorkDao()->getWorkByProjectId($userId, $projectId);
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
            $fields['status'] = 'published';
            $fields['publishTime'] = time();
        }
        unset($fields['publish']);

        $work = $this->getWorkDao()->update($work['id'], $fields);

        $this->getLogService()->info(
            'python_editor',
            'save_work',
            '保存作品 (#'.$work['id'].')',
            $fields
        );

        return $work;
    }

    public function searchWorks(array $conditions, $orderBy, $start, $limit, array $columns = array())
    {
        $preparedCondtions = $this->prepareConditions($conditions);

        return $this->getWorkDao()->search($preparedCondtions, $orderBy, $start, $limit, $columns);
    }

    public function searchWorkCount(array $conditions)
    {
        $preparedCondtions = $this->prepareConditions($conditions);

        return $this->getWorkDao()->count($preparedCondtions);
    }

    public function publishWork($id)
    {
        $work = $this->getWorkDao()->update(
            $id,
            array(
                'status' => 'published',
            )
        );

        $this->getLogService()->info(
            'python_editor',
            'publish',
            sprintf('发布 python 作品《%s》(#%s)', $work['title'], $work['id']),
            array('work' => $work)
        );

        return $work;
    }

    public function deleteWork($id)
    {
        $work = $this->getWorkDao()->delete($id);
        $this->getLogService()->info(
            'python_editor',
            'delete',
            sprintf('删除 python 作品《%s》(#%s)', $work['title'], $work['id']),
            array('work' => $work)
        );

        return $work;
    }

    public function getWork($id)
    {
        return $this->getWorkDao()->get($id);
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

        $work = $this->getWorkDao()->update($id, $fields);

        $this->getLogService()->info(
            'python_editor',
            'recommend_work',
            sprintf('推荐 python 作品《%s》(#%s)', $work['title'], $work['id']),
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
        $work = $this->getWorkDao()->update(
            $id,
            $fields
        );

        $this->getLogService()->info(
            'python_editor',
            'cancel_recommend_work',
            sprintf('取消推荐 python 作品《%s》(#%s)', $work['title'], $work['id']),
            array('work' => $work)
        );

        return $work;
    }

    public function getLastWorkByUserId($userId)
    {
        $work = $this->getWorkDao()->search(array('userId' => $userId), array('createdTime' => 'desc'), 0, 1);

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
            $work = $this->getWorkDao()->create($fields);
            $this->getLogService()->info(
                'python_editor',
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
        $status = $this->getWorkDao()->addHits($id);
        $this->getLogService()->info(
            'python_editor',
            'work_hits',
            '作品页浏览+1(#'.$id.')',
            array($status)
        );

        return $status;
    }

    public function like($id)
    {
        $status = $this->getWorkDao()->addUpsNum($id);
        $this->getLogService()->info(
            'python_editor',
            'work_like',
            '作品页点赞+1(#'.$id.')',
            array($status)
        );

        return $status;
    }

    public function cancelLike($id)
    {
        $status = $this->getWorkDao()->subtractUpsNum($id);
        $this->getLogService()->info(
            'python_editor',
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
     * @return WorkDao
     */
    protected function getWorkDao()
    {
        return $this->createDao('PythonEditor:WorkDao');
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
