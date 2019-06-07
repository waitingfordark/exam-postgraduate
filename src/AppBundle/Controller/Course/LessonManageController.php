<?php

namespace AppBundle\Controller\Course;

use AppBundle\Util\UploaderToken;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\CourseSetService;
use Biz\Course\Service\LessonService;
use Biz\File\Service\UploadFileService;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\BaseController;

class LessonManageController extends BaseController
{
    public function createAction(Request $request, $courseId)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId);

        $this->getCourseLessonService()->isLessonCountEnough($course['id']);

        if ('POST' == $request->getMethod()) {
            $formData = $request->request->all();

            $formData['_base_url'] = $request->getSchemeAndHttpHost();
            $formData['fromUserId'] = $this->getUser()->getId();
            $formData['fromCourseSetId'] = $course['courseSetId'];
            list($lesson, $task) = $this->getCourseLessonService()->createLesson($formData);

            // 显示考试管理列表
            return $this->redirectToRoute(
                'course_set_manage_course_tasks',
                array(
                    'courseId' => $course['id'],
                    'courseSetId' => $course['courseSetId']
                )
            );
        }

        return $this->forward('AppBundle:TaskManage:create', array('courseId' => $course['id']));
    }

    public function updateAction(Request $request, $courseId, $lessonId)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId);
        $lesson = $this->getCourseService()->getChapter($courseId, $lessonId);

        if ('POST' == $request->getMethod()) {
            $fields = $request->request->all();
            $fields['doTimes'] = 0; 

            $task = $this->getTaskService()->updateTask($lessonId, $fields);
           

            return $this->forward(
                'AppBundle:Course/CourseManage:tasks',
                array(
                    'courseId' => $course['id'],
                    'courseSetId' => $course['courseSetId']
                )
            );
        }
    }

    public function publishAction(Request $request, $courseId, $lessonId)
    {
        $this->getCourseLessonService()->publishLesson($courseId, $lessonId);

        return $this->createJsonResponse(array('success' => true));
    }

    public function unpublishAction(Request $request, $courseId, $lessonId)
    {
        $this->getCourseLessonService()->unpublishLesson($courseId, $lessonId);

        return $this->createJsonResponse(array('success' => true));
    }

    public function deleteAction(Request $request, $courseId, $lessonId)
    {
        $this->getCourseLessonService()->deleteLesson($courseId, $lessonId);

        return $this->createJsonResponse(array('success' => true));
    }



    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    /**
     * @return LessonService
     */
    protected function getCourseLessonService()
    {
        return $this->createService('Course:LessonService');
    }

    protected function createCourseStrategy($course)
    {
        return $this->getBiz()->offsetGet('course.strategy_context')->createStrategy($course['courseType']);
    }

    /**
     * @return UploadFileService
     */
    protected function getUploadFileService()
    {
        return $this->createService('File:UploadFileService');
    }

    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

}
