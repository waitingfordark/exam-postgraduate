<?php

namespace AppBundle\Controller\Activity;

use Biz\Activity\Service\ActivityService;
use Biz\Course\Service\CourseDraftService;
use Biz\Scratch\Service\ScratchService;
use Biz\Task\Service\TaskResultService;
use Biz\Task\Service\TaskService;
use Symfony\Component\HttpFoundation\Request;

class CodingController extends BaseActivityController implements ActivityActionInterface
{
    public function showAction(Request $request, $activity)
    {
        if (empty($activity)) {
            throw $this->createNotFoundException('activity not found');
        }

        $coding = $this->getActivityService()->getActivityConfig('coding')->get($activity['mediaId']);

        if (empty($coding)) {
            throw $this->createNotFoundException('coding activity not found');
        }

        return $this->render('activity/coding/show.html.twig', array(
            'activity' => $activity,
            'coding' => $coding,
        ));
    }

    public function previewAction(Request $request, $task)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId']);

        if (empty($activity)) {
            throw $this->createNotFoundException('activity not found');
        }

        $coding = $this->getActivityService()->getActivityConfig('coding')->get($activity['mediaId']);

        if (empty($coding)) {
            throw $this->createNotFoundException('coding activity not found');
        }

        return $this->render('activity/coding/preview.html.twig', array(
            'activity' => $activity,
            'coding' => $coding,
        ));
    }

    public function editAction(Request $request, $id, $courseId)
    {
        $user = $this->getCurrentUser();
        $activity = $this->getActivityService()->getActivity($id);
        $coding = $this->getActivityService()->getActivityConfig('coding')->get($activity['mediaId']);
        $draft = $this->getCourseDraftService()->getCourseDraftByCourseIdAndActivityIdAndUserId($courseId, $activity['id'], $user->id);

        return $this->render('activity/coding/modal.html.twig', array(
            'activity' => $activity,
            'coding' => $coding,
            'courseId' => $courseId,
            'draft' => $draft,
        ));
    }

    public function autoSaveAction(Request $request, $courseId, $activityId = 0)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('user not login');
        }

        $content = $request->request->get('content', '');

        if (empty($content)) {
            return $this->createJsonResponse(true);
        }

        $draft = $this->getCourseDraftService()->getCourseDraftByCourseIdAndActivityIdAndUserId($courseId, $activityId,
            $user->getId());

        if (empty($draft)) {
            $draft = array(
                'activityId' => $activityId,
                'title' => '',
                'content' => $content,
                'courseId' => $courseId,
            );

            $this->getCourseDraftService()->createCourseDraft($draft);
        } else {
            $draft['content'] = $content;
            $this->getCourseDraftService()->updateCourseDraft($draft['id'], $draft);
        }

        return $this->createJsonResponse(true);
    }

    public function doCodingAction(Request $request, $courseId, $activityId)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('user not login');
        }

        $task = $this->getTaskService()->getTaskByCourseIdAndActivityId($courseId, $activityId);
        $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($task['id']);

        if (empty($taskResult)) {
            throw $this->createResourceNotFoundException('找不到学习记录');
        }

        if (0 == $taskResult['scratchProjectId']) {
            $project = $this->getScratchService()->createProject(array('userId' => $user['id']));
            $taskResult = $this->getTaskResultService()->updateTaskResult($taskResult['id'], array('scratchProjectId' => $project['id']));
        }

        return $this->redirectToRoute('scratch_show', array('projectId' => $taskResult['scratchProjectId']));
    }

    public function createAction(Request $request, $courseId)
    {
        $user = $this->getCurrentUser();
        $draft = $this->getCourseDraftService()->getCourseDraftByCourseIdAndActivityIdAndUserId($courseId, 0, $user->id);

        return $this->render('activity/coding/modal.html.twig', array(
            'courseId' => $courseId,
            'draft' => $draft,
        ));
    }

    public function finishConditionAction(Request $request, $activity)
    {
        return $this->render('activity/coding/finish-condition.html.twig');
    }

    /**
     * @return CourseDraftService
     */
    public function getCourseDraftService()
    {
        return $this->createService('Course:CourseDraftService');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    /**
     * @return ScratchService
     */
    protected function getScratchService()
    {
        return $this->createService('Scratch:ScratchService');
    }

    /**
     * @return TaskResultService
     */
    protected function getTaskResultService()
    {
        return $this->createService('Task:TaskResultService');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }
}
