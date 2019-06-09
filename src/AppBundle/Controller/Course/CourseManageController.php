<?php

namespace AppBundle\Controller\Course;

use AppBundle\Common\DateToolkit;
use AppBundle\Common\Paginator;
use Biz\Task\Strategy\CourseStrategy;
use Biz\Util\EdusohoLiveClient;
use Biz\Task\Service\TaskService;
use AppBundle\Common\ArrayToolkit;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\MemberService;
use Biz\Course\Service\ReportService;
use Biz\Course\Service\ThreadService;
use Biz\System\Service\SettingService;
use Biz\File\Service\UploadFileService;
use Biz\Task\Service\TaskResultService;
use AppBundle\Controller\BaseController;
use Biz\Course\Service\CourseSetService;
use Biz\Activity\Service\ActivityService;
use Biz\Course\Service\CourseNoteService;
use Biz\Course\Service\LiveReplayService;
use Biz\Testpaper\Service\TestpaperService;
use Codeages\Biz\Pay\Service\PayService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Biz\Activity\Service\ActivityLearnLogService;
use Codeages\Biz\Framework\Service\Exception\InvalidArgumentException;

class CourseManageController extends BaseController
{
    public function createAction(Request $request, $courseSetId)
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $data = $this->prepareExpiryMode($data);

            $this->getCourseService()->createCourse($data);

            return $this->redirect(
                $this->generateUrl('course_set_manage_courses', array('courseSetId' => $courseSetId))
            );
        }

        $courseSet = $this->getCourseSetService()->getCourseSet($courseSetId);

        return $this->render(
            'course-manage/create-modal.html.twig',
            array(
                'courseSet' => $courseSet,
            )
        );
    }

    public function listAction(Request $request, $courseSetId)
    {
        $courseSet = $this->getCourseSetService()->tryManageCourseSet($courseSetId);

        $conditions = array(
            'courseSetId' => $courseSet['id'],
        );

        $paginator = new Paginator(
            $request,
            $this->getCourseService()->countCourses($conditions),
            20
        );

        $courses = $this->getCourseService()->searchCourses(
            $conditions,
            array('seq' => 'DESC', 'createdTime' => 'ASC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        list($courses, $courseSet) = $this->fillManageRole($courses, $courseSet);

        return $this->render(
            'courseset-manage/courses.html.twig',
            array(
                'courseSet' => $courseSet,
                'courses' => $courses,
                'paginator' => $paginator,
            )
        );
    }

    private function fillManageRole($courses, $courseSet)
    {
        $user = $this->getCurrentUser();
        if ($user->isAdmin() || ($courseSet['creator'] == $user->getId())) {
            $courseSet['canManage'] = true;
        } else {
            $courseMember = $this->getCourseMemberService()->searchMembers(
                array(
                    'courseSetId' => $courseSet['id'],
                    'userId' => $user->getId(),
                    'role' => 'teacher',
                ),
                array(),
                0,
                PHP_INT_MAX
            );
            $memberCourseIds = ArrayToolkit::column($courseMember, 'courseId');
            foreach ($courses as &$course) {
                $course['canManage'] = in_array($course['id'], $memberCourseIds);
            }
        }

        return array($courses, $courseSet);
    }

    public function tasksAction(Request $request, $courseSetId, $courseId)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId, $courseSetId);
        $courseSet = $this->getCourseSetService()->getCourseSet($courseSetId);

        $tasks = $this->getTaskService()->findTasksByCourseId($courseId);
        $tasksListJsonData = $this->createCourseStrategy($course)->getTasksListJsonData($courseId);

        // lesson-manage/default-list.html.twig
        
        return $this->render(
            $tasksListJsonData['template'],
            array_merge(
                array(
                    'courseSet' => $courseSet,
                    'course' => $course,
                ),
                $tasksListJsonData['data']
            )
        );
    }

    protected function createCourseStrategy($course)
    {
        return $this->getBiz()->offsetGet('course.strategy_context')->createStrategy($course['courseType']);
    }

    public function infoAction(Request $request, $courseSetId, $courseId)
    {
        $course = $this->getCourseService()->canUpdateCourseBaseInfo($courseId, $courseSetId);

        $freeTasks = $this->getTaskService()->findFreeTasksByCourseId($courseId);
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $courseSet = $this->getCourseSetService()->tryManageCourseSet($courseSetId);
            
            $updatedCourse = $this->getCourseService()->updateBaseInfo($courseId, $data);

            return $this->createJsonResponse(true);
        }

        $courseSet = $this->getCourseSetService()->getCourseSet($courseSetId);
       
        return $this->redirectToRoute(
            'course_set_manage_course_tasks',
            array(
                'request' => $request,
                'courseSetId' => $courseSetId,
                'courseId' => $courseId
            )
        );
    }

    public function headerAction($courseSet, $course)
    {
        return $this->render(
            'course-manage/header.html.twig',
            array(
                'courseSet' => $courseSet,
                'course' => $course,
            )
        );
    }

    public function teachersAction(Request $request, $courseSetId, $courseId)
    {
        $courseSet = $this->getCourseSetService()->getCourseSet($courseSetId);

        $course = $this->getCourseService()->tryManageCourse($courseId, $courseSetId);
        $teachers = $this->getCourseService()->findTeachersByCourseId($courseId);
        $teacherIds = array();

        if (!empty($teachers)) {
            foreach ($teachers as $teacher) {
                $teacherIds[] = array(
                    'id' => $teacher['userId'],
                    'isVisible' => $teacher['isVisible'],
                    'nickname' => $teacher['nickname'],
                );
            }
        }

        return $this->render(
            'course-manage/teacher/index.html.twig',
            array(
                'courseSet' => $courseSet,
                'course' => $course,
                'teacherIds' => $teacherIds,
            )
        );
    }

    // 添加教师
    public function addTeachersAction(Request $request, $courseSetId, $courseId)
    {
        $operateUser = $this->getUser();
        $courseSetting = $this->getSettingService()->get('course');

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $teachers = $this->getUserService()->searchUsers(
                array('nickname' => $data['queryfield'], 'roles' => 'ROLE_TEACHER'),
                array('createdTime' => 'DESC'),
                0,
                10
            );

            if(empty($teachers)) {
                $this->setFlashMessage('wrong', '没有找到用户');
                return $this->redirectToRoute(
                    'course_set_manage_course_teachers',
                    array('courseSetId' => $courseSetId, 'courseId' => $courseId)
                ); 
            }

            $this->getCourseMemberService()->setCourseTeachers($courseId, $teachers);

            return $this->redirectToRoute(
                'course_set_manage_course_teachers',
                array('courseSetId' => $courseSetId, 'courseId' => $courseId)
            );
        }
        $course = $this->getCourseService()->tryManageCourse($courseId, $courseSetId);

        return $this->render(
            'course-manage/teacher/add-modal.html.twig',
            array(
                'course' => $course,
                'courseSetId' => $courseSetId,
            )
        );
    }


    // course 
    public function closeCheckAction(Request $request, $courseSetId, $courseId)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId, $courseSetId);
        $publishedCourses = $this->getCourseService()->findPublishedCoursesByCourseSetId($courseSetId);

        return $this->createJsonResponse(array('warn' => false));
    }

    public function closeAction(Request $request, $courseSetId, $courseId)
    {
        try {
            $this->getCourseService()->closeCourse($courseId);

            return $this->createJsonResponse(array('success' => true));
        } catch (\Exception $e) {
            return $this->createJsonResponse(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    public function deleteAction(Request $request, $courseSetId, $courseId)
    {
        try {
            $this->getCourseService()->deleteCourse($courseId);
            if (!$this->getCourseSetService()->hasCourseSetManageRole($courseSetId)) {
                return $this->createJsonResponse(array('success' => true, 'redirect' => $this->generateUrl('homepage')));
            }
        } catch (\Exception $e) {
            return $this->createJsonResponse(array('success' => false, 'message' => $e->getMessage()));
        }

        return $this->createJsonResponse(array('success' => true));
    }

    public function publishAction($courseSetId, $courseId)
    {
        try {
            $this->getCourseService()->publishCourse($courseId, true);

            return $this->createJsonResponse(array('success' => true));
        } catch (\Exception $e) {
            return $this->createJsonResponse(array('success' => false, 'message' => $e->getMessage()));
        }
    }


    public function showPublishAction(Request $request, $courseId)
    {
        $status = $request->request->get('status', 1);
        $this->getCourseService()->changeShowPublishLesson($courseId, $status);

        return $this->createJsonResponse(true);
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
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return \AppBundle\Twig\WebExtension
     */
    protected function getWebExtension()
    {
        return $this->container->get('web.twig.extension');
    }

    /**
     * @return MemberService
     */
    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    /**
     * @return ActivityLearnLogService
     */
    protected function getActivityLearnLogService()
    {
        return $this->createService('Activity:ActivityLearnLogService');
    }

    /**
     * @return \Codeages\Biz\Order\Service\OrderService
     */
    protected function getOrderService()
    {
        return $this->createService('Order:OrderService');
    }

    /**
     * @return CourseNoteService
     */
    protected function getNoteService()
    {
        return $this->createService('Course:CourseNoteService');
    }

    /**
     * @return ThreadService
     */
    protected function getThreadService()
    {
        return $this->createService('Course:ThreadService');
    }

    /**
     * @return ReportService
     */
    protected function getReportService()
    {
        return $this->createService('Course:ReportService');
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
     * @return UploadFileService
     */
    protected function getUploadFileService()
    {
        return $this->createService('File:UploadFileService');
    }

    /**
     * @return TaskResultService
     */
    protected function getTaskResultService()
    {
        return $this->createService('Task:TaskResultService');
    }

    /**
     * @return LiveReplayService
     */
    protected function getLiveReplayService()
    {
        return $this->createService('Course:LiveReplayService');
    }

    protected function getTestpaperActivityService()
    {
        return $this->createService('Activity:TestpaperActivityService');
    }

    /**
     * @return \Biz\Marker\Service\ReportService
     */
    protected function getMarkerReportService()
    {
        return $this->createService('Marker:ReportService');
    }

    /**
     * @return PayService
     */
    protected function getPayService()
    {
        return $this->createService('Pay:PayService');
    }

    protected function getActivityConfig()
    {
        return $this->get('extension.manager')->getActivities();
    }

    protected function getCourseLessonService()
    {
        return $this->createService('Course:LessonService');
    }
}
