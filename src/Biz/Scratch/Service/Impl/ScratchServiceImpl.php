<?php

namespace Biz\Scratch\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use Biz\BaseService;
use Biz\Content\Service\FileService;
use Biz\Scratch\Dao\ProjectDao;
use Biz\Scratch\Dao\ScratchWorkDao;
use Biz\Scratch\Dao\ShareDao;
use Biz\Scratch\Service\ScratchService;
use Biz\System\Service\LogService;
use Biz\Task\Dao\TaskResultDao;
use Codeages\Biz\Framework\Event\Event;

class ScratchServiceImpl extends BaseService implements ScratchService
{
    public function getProject($projectId)
    {
        return $this->getProjectDao()->get($projectId);
    }

    public function updateProject($projectId, $fields)
    {
        if (!ArrayToolkit::requireds($fields, array('fileUri', 'shareId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $fields = ArrayToolkit::filter(
            $fields,
            array(
                'fileUri' => '',
                'shareId' => 0,
            )
        );

        try {
            $this->beginTransaction();

            $project = $this->getProjectDao()->update($projectId, $fields);

            $user = $this->getCurrentUser();
            $this->getTaskResultDao()->update(
                array(
                    'userId' => $user['id'],
                    'scratchProjectId' => $projectId,
                ),
                array(
                    'status' => 'finish',
                    'finishedTime' => time(),
                )
            );
            $taskResult = $this->getTaskResultDao()->search(
                array(
                    'userId' => $user['id'],
                    'scratchProjectId' => $projectId,
                ),
                array('createdTime' => 'DESC'),
                0,
                1
            );
            if (!empty($taskResult)) {
                $this->dispatchEvent('course.task.finish', new Event($taskResult[0], array('user' => $user)));
            }

            $this->getLogService()->info(
                'scratch',
                'save_project',
                '保存项目(#'.$projectId.')',
                $fields
            );
            $this->commit();

            return $project;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function createProject($fields)
    {
        if (!ArrayToolkit::requireds($fields, array('userId'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $fields = ArrayToolkit::filter(
            $fields,
            array(
                'userId' => 0,
            )
        );
        $project = $this->getProjectDao()->create($fields);
        $this->getLogService()->info(
            'scratch',
            'save_project',
            '新建项目(#'.$project['id'].')',
            $fields
        );

        return $project;
    }

    public function getShareInfo($shareId)
    {
        return $this->getShareDao()->get($shareId);
    }

    public function addHits($shareId)
    {
        $status = $this->getShareDao()->wave(
            array($shareId),
            array(
                'hits' => +1,
            )
        );
        $this->getLogService()->info(
            'scratch',
            'hits',
            '分享页浏览+1(#'.$shareId.')',
            array($status)
        );

        return $status;
    }

    public function like($shareId)
    {
        $status = $this->getShareDao()->wave(
            array($shareId),
            array(
                'upsNum' => +1,
            )
        );
        $this->getLogService()->info(
            'scratch',
            'like',
            '对分享页点赞(#'.$shareId.')',
            array($status)
        );

        return $status;
    }

    public function cancelLike($shareId)
    {
        $status = $this->getShareDao()->wave(
            array($shareId),
            array(
                'upsNum' => -1,
            )
        );
        $this->getLogService()->info(
            'scratch',
            'cancelLike',
            '对分享页取消点赞(#'.$shareId.')',
            array($status)
        );

        return $status;
    }

    public function updateShareInfo($shareId, $fields)
    {
        if (!ArrayToolkit::requireds($fields, array('nickname', 'title', 'summary', 'usageText'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $fields = ArrayToolkit::filter(
            $fields,
            array(
                'nickname' => '',
                'title' => '',
                'summary' => '',
                'usageText' => '',
            )
        );

        $shareInfo = $this->getShareDao()->update($shareId, $fields);

        $this->getLogService()->info(
            'scratch',
            'save_shareinfo',
            '保存分享信息 (#'.$shareInfo['id'].')',
            $fields
        );

        return $shareInfo;
    }

    public function saveShareInfo($project, $fields)
    {
        try {
            $this->beginTransaction();
            $share = $this->createShareInfo($fields);
            $projectFields = array(
                'shareId' => $share['id'],
                'fileUri' => $project['fileUri'],
            );
            $project = $this->updateProject($project['id'], $projectFields);
            $this->commit();

            return $project;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function createShareInfo($fields)
    {
        if (!ArrayToolkit::requireds($fields, array('nickname', 'title', 'summary', 'usageText'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $fields = ArrayToolkit::filter(
            $fields,
            array(
                'nickname' => '',
                'title' => '',
                'summary' => '',
                'usageText' => '',
            )
        );

        $shareInfo = $this->getShareDao()->create($fields);
        $this->getLogService()->info(
            'scratch',
            'save_shareinfo',
            '保存分享信息 (#'.$shareInfo['id'].')',
            $fields
        );

        return $shareInfo;
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
}
