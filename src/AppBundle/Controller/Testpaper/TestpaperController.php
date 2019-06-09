<?php

namespace AppBundle\Controller\Testpaper;

use Biz\Task\Service\TaskService;
use Biz\User\Service\UserService;
use AppBundle\Common\ArrayToolkit;
use Biz\Course\Service\CourseService;
use Biz\System\Service\SettingService;
use AppBundle\Controller\BaseController;
use Topxia\Service\Common\ServiceKernel;
use Biz\Activity\Service\ActivityService;
use Biz\Question\Service\QuestionService;
use Biz\Testpaper\Service\TestpaperService;
use Symfony\Component\HttpFoundation\Request;
use Biz\Activity\Service\TestpaperActivityService;
use AppBundle\Common\Exception\AccessDeniedException;

class TestpaperController extends BaseController
{
    public function doTestpaper1Action(Request $request, $testId, $lessonId)
    {
        $user = $this->getUser();

        $testpaper = $this->getTestpaperService()->getTestpaperByIdAndType($testId, 'testpaper');

        if (empty($testpaper)) {
            throw $this->createResourceNotFoundException('testpaper', $testId);
        }

        if ('draft' === $testpaper['status']) {
            return $this->createMessageResponse('info', $this->getServiceKernel()->trans('该试卷未发布'));
        }

        if ('closed' === $testpaper['status']) {
            return $this->createMessageResponse('info', $this->getServiceKernel()->trans('该试卷已关闭'));
        }

        $result = $this->testpaperActivityCheck($lessonId, $testpaper);
        if (!$result['result']) {
            return $this->createMessageResponse('info', $result['message']);
        }

        $fields = $this->getTestpaperFields($lessonId);
        $testpaperResult = $this->getTestpaperService()->startTestpaper($testpaper['id'], $fields);

        if ('doing' === $testpaperResult['status']) {
            return $this->redirect($this->generateUrl('testpaper_show', array('resultId' => $testpaperResult['id'])));
        }

        return $this->redirect(
            $this->generateUrl('testpaper_result_show', array('resultId' => $testpaperResult['id']))
        );
    }

    public function doTestAction(Request $request, $resultId)
    {
        $testpaperResult = $this->getTestpaperService()->getTestpaperResult($resultId);

        // 如果状态在批阅中、批阅完就重定向到试卷结果。
        if (in_array($testpaperResult['status'], array('reviewing', 'finished'))) {
            return $this->redirect(
                $this->generateUrl('testpaper_result_show', array('resultId' => $testpaperResult['id']))
            );
        }

        $canLookTestpaper = $this->getTestpaperService()->canLookTestpaper($testpaperResult['id']);
        if (!$canLookTestpaper) {
            return $this->createMessageResponse('info', 'access denied');
        }

        $testpaper = $this->getTestpaperService()->getTestpaperByIdAndType($testpaperResult['testId'], $testpaperResult['type']);

        $questions = $this->getTestpaperService()->showTestpaperItems($testpaper['id'], $testpaperResult['id']);

        $total = $this->getTestpaperService()->countQuestionTypes($testpaper, $questions);

        $favorites = $this->getQuestionService()->findUserFavoriteQuestions($testpaperResult['userId']);

        $activity = $this->getActivityService()->getActivity($testpaperResult['lessonId']);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);

        if ('realTime' === $testpaperActivity['testMode']) {
            $testpaperResult['usedTime'] = time() - $activity['startTime'];
        }

        $attachments = $this->getTestpaperService()->findAttachments($testpaper['id']);
        $limitedTime = $testpaperActivity['limitedTime'] * 60 - $testpaperResult['usedTime'];
        $limitedTime = $limitedTime > 0 ? $limitedTime : 1;

        return $this->render('testpaper/start-do-show.html.twig', array(
            'questions' => $questions,
            'limitedTime' => $limitedTime,
            'paper' => $testpaper,
            'paperResult' => $testpaperResult,
            'activity' => $activity,
            'testpaperActivity' => $testpaperActivity,
            'favorites' => ArrayToolkit::column($favorites, 'questionId'),
            'total' => $total,
            'attachments' => $attachments,
            'questionTypes' => $this->getCheckedQuestionType($testpaper),
            'showTypeBar' => 1,
            'showHeader' => 0,
            'isDone' => true,
        ));
    }

    public function showResultAction(Request $request, $resultId)
    {
        $testpaperResult = $this->getTestpaperService()->getTestpaperResult($resultId);

        $testpaper = $this->getTestpaperService()->getTestpaperByIdAndType($testpaperResult['testId'], $testpaperResult['type']);

        if (!$testpaper) {
            return $this->createMessageResponse('info', '该试卷已删除，不能查看结果');
        }

        if ('doing' === $testpaperResult['status']) {
            return $this->redirect($this->generateUrl('testpaper_show', array('resultId' => $testpaperResult['id'])));
        }

        $canLookTestpaper = $this->getTestpaperService()->canLookTestpaper($testpaperResult['id']);
        if (!$canLookTestpaper) {
            return $this->createMessageResponse('info', 'access denied');
        }

        $builder = $this->getTestpaperService()->getTestpaperBuilder($testpaper['type']);
        $questions = $builder->showTestItems($testpaper['id'], $testpaperResult['id']);

        $accuracy = $this->getTestpaperService()->makeAccuracy($testpaperResult['id']);

        $total = $this->makeTestpaperTotal($testpaper, $questions);

        $favorites = $this->getQuestionService()->findUserFavoriteQuestions($testpaperResult['userId']);

        $student = $this->getUserService()->getUser($testpaperResult['userId']);

        // $attachments = $this->getTestpaperService()->findAttachments($testpaper['id']);

        $activity = $this->getActivityService()->getActivity($testpaperResult['lessonId']);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);
        $task = $this->getTaskService()->getTaskByCourseIdAndActivityId($activity['fromCourseId'], $activity['id']);

        return $this->render('testpaper/result.html.twig', array(
            'questions' => $questions,
            'accuracy' => $accuracy,
            'paper' => $testpaper,
            'paperResult' => $testpaperResult,
            'favorites' => ArrayToolkit::column($favorites, 'questionId'),
            'total' => $total,
            'student' => $student,
            'source' => $request->query->get('source', 'course'),
            // 'attachments' => $attachments,
            'questionTypes' => $this->getCheckedQuestionType($testpaper),
            'task' => $task,
            'action' => $request->query->get('action', ''),
            'target' => $testpaperActivity,
        ));
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

    public function testSuspendAction(Request $request, $resultId)
    {
        $testpaperResult = $this->getTestpaperService()->getTestpaperResult($resultId);

        if (!$testpaperResult) {
            throw $this->createResourceNotFoundException('testpaperResult', $resultId);
        }

        $user = $this->getUser();
        if ($testpaperResult['userId'] != $user['id']) {
            throw new AccessDeniedException('不可以访问其他学生的试卷');
        }

        if ('POST' === $request->getMethod()) {
            $data = $request->request->all();
            $answers = !empty($data['data']) ? $data['data'] : array();
            $attachments = empty($data['attachments']) ? array() : $data['attachments'];
            $orders = empty($data['seq']) ? array() : $data['seq'];
            $usedTime = $data['usedTime'];

            $this->getTestpaperService()->submitAnswers($testpaperResult['id'], $answers, $attachments);

            $this->getTestpaperService()->updateTestpaperResult($testpaperResult['id'], array(
                'usedTime' => $usedTime,
                'metas' => array('orders' => $orders),
            ));

            return $this->createJsonResponse(true);
        }
    }

    public function submitTestAction(Request $request, $resultId)
    {
        if ('POST' === $request->getMethod()) {
            $data = $request->request->all();
            $answers = !empty($data['data']) ? $data['data'] : array();
            $usedTime = $data['usedTime'];
            $attachments = empty($formData['attachments']) ? array() : $formData['attachments'];

            $this->getTestpaperService()->submitAnswers($resultId, $answers, $attachments);

            $this->getTestpaperService()->updateTestpaperResult($resultId, $usedTime);

            return $this->createJsonResponse(true);
        }
    }

    public function finishTestAction(Request $request, $resultId)
    {
        $testpaperResult = $this->getTestpaperService()->getTestpaperResult($resultId);

        if (!$testpaperResult['status'] === 'doing') {
            return $this->createJsonResponse(array('result' => false, 'message' => '试卷已提交，不能再修改答案！'));
        }

        if ('POST' === $request->getMethod()) {
            $activity = $this->getActivityService()->getActivity($testpaperResult['lessonId']);
            $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);

            $formData = $request->request->all();

            $paperResult = $this->getTestpaperService()->finishTest($testpaperResult['id'], $formData);

            $response = array('result' => true, 'message' => '');

            return $this->createJsonResponse($response);
        }
    }

    protected function makeTestpaperTotal($testpaper, $items)
    {
        $total = array();
        if (empty($testpaper['metas']['counts'])) {
            return $total;
        }
        foreach ($testpaper['metas']['counts'] as $type => $count) {
            if (empty($items[$type])) {
                $total[$type]['score'] = 0;
                $total[$type]['number'] = 0;
                $total[$type]['missScore'] = 0;
            } else {
                $total[$type]['score'] = array_sum(ArrayToolkit::column($items[$type], 'score'));
                $total[$type]['number'] = count($items[$type]);

                if (array_key_exists('missScore', $testpaper['metas'])
                    && array_key_exists($type, $testpaper['metas']['missScore'])) {
                    $total[$type]['missScore'] = $testpaper['metas']['missScore'][$type];
                } else {
                    $total[$type]['missScore'] = 0;
                }
            }
        }

        return $total;
    }

    protected function testpaperActivityCheck($activityId, $testpaper)
    {
        $user = $this->getUser();

        $activity = $this->getActivityService()->getActivity($activityId);

        $canTakeCourse = $this->getCourseService()->canTakeCourse($activity['fromCourseId']);
        if (!$canTakeCourse) {
            return array('result' => false, 'message' => $this->getServiceKernel()->trans('access denied!'));
        }

        $result = array('result' => true, 'message' => '');
        if (!$activity) {
            return $result;
        }

        if ($activity['startTime'] && $activity['startTime'] > time()) {
            return array(
                'result' => false,
                'message' => $this->getServiceKernel()->trans('考试未开始，请在'.date('Y-m-d H:i:s', $activity['startTime']).'之后再来！'),
            );
        }

        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);
        $testpaperResult = $this->getTestpaperService()->getUserLatelyResultByTestId(
            $user['id'],
            $testpaper['id'],
            $activity['fromCourseId'],
            $activityId,
            $testpaper['type']
        );

        if ($testpaperActivity['doTimes'] && $testpaperResult && 'finished' === $testpaperResult['status']) {
            return array('result' => false, 'message' => $this->getServiceKernel()->trans('该试卷只能考一次，不能再考！'));
        }

        return $result;
    }

    protected function getTestpaperFields($activityId)
    {
        $activity = $this->getActivityService()->getActivity($activityId);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);

        if (!$activity || !$testpaperActivity) {
            return array();
        }

        return array(
            'lessonId' => $activityId,
            'courseId' => $activity['fromCourseId'],
            'limitedTime' => $testpaperActivity['limitedTime'],
        );
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * @return TestpaperService
     */
    protected function getTestpaperService()
    {
        return $this->createService('Testpaper:TestpaperService');
    }

    /**
     * @return QuestionService
     */
    protected function getQuestionService()
    {
        return $this->createService('Question:QuestionService');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    /**
     * @return TestpaperActivityService
     */
    protected function getTestpaperActivityService()
    {
        return $this->createService('Activity:TestpaperActivityService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    /**
     * @return ServiceKernel
     */
    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }
}
