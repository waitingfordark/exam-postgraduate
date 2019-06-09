<?php

namespace AppBundle\Controller;

use Biz\Activity\Service\ActivityService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\CourseSetService;
use Biz\Course\Service\LearningDataAnalysisService;
use Biz\Course\Service\MemberService;
use Biz\Task\Service\TaskResultService;
use Biz\Task\Service\TaskService;
use Biz\User\Service\TokenService;
use Codeages\Biz\Framework\Service\Exception\AccessDeniedException as ServiceAccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class TaskController extends BaseController
{
    public function showAction(Request $request, $courseId, $id)
    {
        $task = $this->tryLearnTask($courseId, $id);

        return $this->forward(
            'AppBundle:Activity/Activity:show',
            array(
                'task' => $task,
            )
        );
    }

    protected function tryLearnTask($courseId, $taskId)
    {
        $isTeacher = $this->getCourseMemberService()->isCourseTeacher($courseId, $this->getUser()->getId());
        if ($isTeacher) {
            $task = $this->getTaskService()->getTask($taskId);
        } else {
            $task = $this->getTaskService()->tryTakeTask($taskId);
        }
        if (empty($task)) {
            throw $this->createNotFoundException(sprintf('task not found #%d', $taskId));
        }

        return $task;
    }


    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return MemberService
     */
    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    /**
     * @return TaskResultService
     */
    protected function getTaskResultService()
    {
        return $this->createService('Task:TaskResultService');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }


    protected function getActivityConfig()
    {
        return $this->get('extension.manager')->getActivities();
    }
}
