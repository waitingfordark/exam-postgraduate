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

            return $this->forward(
                'AppBundle:Course/CourseManage:tasks',
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

            // 这里已经更新了
            // $lesson = $this->getCourseLessonService()->updateLesson($lesson['id'], $fields);
            $task = $this->getTaskService()->updateTask($lessonId, $fields);
            // var_dump($task);
            // die;

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

    public function setOptionalAction(Request $request, $courseId, $lessonId)
    {
        $this->getCourseLessonService()->setOptional($courseId, $lessonId);

        return $this->createJsonResponse(array('success' => true));
    }

    public function unsetOptionalAction(Request $request, $courseId, $lessonId)
    {
        $this->getCourseLessonService()->unsetOptional($courseId, $lessonId);

        return $this->createJsonResponse(array('success' => true));
    }

    private function createTaskByFileAndCourse($file, $course)
    {
        $task = array(
            'mediaType' => $file['type'],
            'fromCourseId' => $course['id'],
            'fromUserId' => $this->getUser()->getId(),
            'fromCourseSetId' => $course['courseSetId'],
            'courseSetType' => 'normal',
            'media' => json_encode(array('source' => 'self', 'id' => $file['id'], 'name' => $file['filename'])),
            'mediaId' => $file['id'],
            'type' => $file['type'],
            'length' => $file['length'],
            'title' => str_replace(strrchr($file['filename'], '.'), '', $file['filename']),
            'ext' => array('mediaSource' => 'self', 'mediaId' => $file['id']),
            'categoryId' => 0,
        );
        if ('document' == $file['type']) {
            $task['type'] = 'doc';
            $task['mediaType'] = 'doc';
        }

        return $task;
    }

    //创建任务或修改任务返回的html
    protected function getTaskJsonView($course, $task)
    {
        $taskJsonData = $this->createCourseStrategy($course)->getTasksJsonData($task);
        if (empty($taskJsonData)) {
            return $this->createJsonResponse(false);
        }

        return $this->createJsonResponse($this->renderView(
            $taskJsonData['template'],
            $taskJsonData['data']
        ));
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
