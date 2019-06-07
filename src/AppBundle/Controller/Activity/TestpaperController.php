<?php

namespace AppBundle\Controller\Activity;

use Biz\Course\Service\CourseService;
use Biz\Activity\Service\ActivityService;
use Biz\Testpaper\Service\TestpaperService;
use Symfony\Component\HttpFoundation\Request;
use Biz\Activity\Service\TestpaperActivityService;
use AppBundle\Common\Paginator;
use AppBundle\Common\ArrayToolkit;

class TestpaperController extends BaseActivityController implements ActivityActionInterface
{
    public function showAction(Request $request, $activity, $preview = 0)
    {
        if ($preview) {
            return $this->previewTestpaper($activity['id'], $activity['fromCourseId']);
        }

        $user = $this->getUser();
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);
        $testpaper = $this->getTestpaperService()->getTestpaperByIdAndType($testpaperActivity['mediaId'], $activity['mediaType']);

        if (!$testpaper) {
            return $this->render('activity/testpaper/preview.html.twig', array(
                'paper' => null,
            ));
        }

        $testpaperResult = $this->getTestpaperService()->getUserLatelyResultByTestId($user['id'], $testpaperActivity['mediaId'], $activity['fromCourseId'], $activity['id'], $activity['mediaType']);

        if (!$testpaperResult || ($testpaperResult['status'] == 'doing' && !$testpaperResult['updateTime']) || $testpaper['status'] != 'open') {
            // return $this->render('activity/testpaper/show.html.twig', array(
            //     'activity' => $activity,
            //     'testpaperActivity' => $testpaperActivity,
            //     'testpaperResult' => $testpaperResult,
            //     'testpaper' => $testpaper,
            //     'courseId' => $activity['fromCourseId'],
            // ));
        } elseif ($testpaperResult['status'] === 'finished') {
            return $this->forward('AppBundle:Testpaper/Testpaper:showResult', array(
                'resultId' => $testpaperResult['id'],
            ));
        }

        return $this->forward('AppBundle:Testpaper/Testpaper:doTestpaper', array(
            'testId' => $testpaperActivity['mediaId'],
            'lessonId' => $activity['id'],
        ));
    }


    public function editAction(Request $request, $id, $courseId)
    {
        $course = $this->getCourseService()->getCourse($courseId);

        $activity = $this->getActivityService()->getActivity($id);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);

        if ($testpaperActivity) {
            $testpaperActivity['testpaperMediaId'] = $testpaperActivity['mediaId'];
            unset($testpaperActivity['mediaId']);
        }
        $activity = array_merge($activity, $testpaperActivity);

        $testpapers = $this->findCourseTestpapers($course);


        return $this->render('activity/testpaper/modal1.html.twig', array(
            'activity' => $activity,
            'testpapers' => $testpapers,
            'mode' => 'edit',
            'courseId' => $activity['fromCourseId'],
            'course' => $course,
        ));
    }

    public function createAction(Request $request, $courseId)
    {
        $course = $this->getCourseService()->getCourse($courseId);
        $testpapers = $this->findCourseTestpapers($course);

     
        return $this->render('activity/testpaper/modal1.html.twig', array(
            'testpapers' => $testpapers,
            'features' => array(),
            'course' => $course,
        ));
    }

    public function finishConditionAction(Request $request, $activity)
    {
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);

        return $this->render('activity/testpaper/finish-condition.html.twig', array(
            'testpaperActivity' => $testpaperActivity,
        ));
    }

    public function learnDataDetailAction(Request $request, $task)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId'], true);
        $testpaper = $this->getTestpaperService()->getTestpaperByIdAndType($activity['ext']['mediaId'], $activity['mediaType']);

        $conditions = array(
            'courseTaskId' => $task['id'],
        );

        $paginator = new Paginator(
            $request,
            $this->getTaskResultService()->countTaskResults($conditions),
            20
        );

        $taskResults = $this->getTaskResultService()->searchTaskResults(
            $conditions,
            array('createdTime' => 'ASC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $userIds = ArrayToolkit::column($taskResults, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        $testpaperResults = $this->getTestpaperService()->findTestResultsByTestpaperIdAndUserIds($userIds, $testpaper['id']);

        return $this->render('activity/testpaper/learn-data-detail-modal.html.twig', array(
            'task' => $task,
            'taskResults' => $taskResults,
            'users' => $users,
            'testpaperResults' => $testpaperResults,
            'paginator' => $paginator,
        ));
    }

    protected function findCourseTestpapers($course)
    {
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $conditions = array(
            'courseSetId' => $course['courseSetId'],
            'status' => 'open',
            'type' => 'testpaper',
        );

        if ($courseSet['parentId'] > 0 && $courseSet['locked']) {
            $conditions['copyIdGT'] = 0;
        }

        $testpapers = $this->getTestpaperService()->searchTestpapers(
            $conditions,
            array('createdTime' => 'DESC'),
            0,
            PHP_INT_MAX
        );

        return $testpapers;
    }

    protected function getCheckedQuestionType($testpaper)
    {
        $questionTypes = array();
        if (!empty($testpaper['metas']['counts'])) {
            foreach ($testpaper['metas']['counts'] as $type => $count) {
                if ($count > 0) {
                    $questionTypes[] = $type;
                }
            }
        }

        return $questionTypes;
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    /**
     * @return TestpaperService
     */
    protected function getTestpaperService()
    {
        return $this->createService('Testpaper:TestpaperService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    /**
     * @return TestpaperActivityService
     */
    protected function getTestpaperActivityService()
    {
        return $this->createService('Activity:TestpaperActivityService');
    }
}
