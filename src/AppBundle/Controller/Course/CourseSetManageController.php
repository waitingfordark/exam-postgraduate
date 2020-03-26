<?php

namespace AppBundle\Controller\Course;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Controller\BaseController;
use Biz\Content\Service\FileService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\CourseSetService;
use Biz\OpenCourse\Service\OpenCourseService;
use Biz\Task\Service\TaskService;
use Biz\Taxonomy\Service\TagService;
use Symfony\Component\HttpFoundation\Request;

class CourseSetManageController extends BaseController
{
    public function createAction(Request $request)
    {
        $visibleCourseTypes = $this->getCourseTypes();

        if ($request->isMethod('POST')) {
            $type = "normal";

            return $this->forward($visibleCourseTypes[$type]['saveAction'], array('request' => $request));
        }

        if (!$this->getCourseSetService()->hasCourseSetManageRole()) {
            throw  $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        $userProfile = $this->getUserService()->getUserProfile($user->getId());

        return $this->render(
            'courseset-manage/create.html.twig',
            array(
                'userProfile' => $userProfile,
                'courseTypes' => $visibleCourseTypes,
            )
        );
    }

    public function saveCourseAction(Request $request)
    {
        $data = $request->request->all();
        
        $courseSet = $this->getCourseSetService()->createCourseSet($data);

        return $this->redirectToRoute(
            'course_set_manage_base',
            array(
                'id' => $courseSet['id'],
            )
        );
    }

    public function indexAction($id)
    {
        $courseSet = $this->getCourseSetService()->tryManageCourseSet($id);
        if ($courseSet['locked']) {
            return $this->redirectToRoute(
                'course_set_manage_sync',
                array(
                    'id' => $id,
                    'sideNav' => 'tasks',
                )
            );
        }

        return $this->redirectToRoute(
            'course_set_manage_courses',
            array(
                'courseSetId' => $id,
            )
        );
    }

    public function headerAction($courseSet, $course = null, $foldType = 0)
    {
        //暂时显示课程的创建者
        $studentNum = $this->getCourseMemberService()->countStudentMemberByCourseSetId($courseSet['id']);
        $couserNum = $this->getCourseService()->countCoursesByCourseSetId($courseSet['id']);

        return $this->render(
            'courseset-manage/header.html.twig',
            array(
                'courseSet' => $courseSet,
                'course' => $course,
                'studentNum' => $studentNum,
                'couserNum' => $couserNum,
                'foldType' => $foldType,
            )
        );
    }

    public function sidebarAction($courseSetId, $curCourse, $courseSideNav)
    {
        $user = $this->getCurrentUser();

        $courses = $this->getCourseService()->findCoursesByCourseSetId($courseSetId);

        if (!$user->isAdmin()) {
            $courses = array_filter(
                $courses,
                function ($course) use ($user) {
                    return in_array($user->getId(), $course['teacherIds']);
                }
            );
        }

        if (empty($curCourse)) {
            $curCourse = $this->getCourseService()->getDefaultCourseByCourseSetId($courseSetId);
        }
        if (empty($curCourse) && !empty($courses)) {
            $curCourse = reset($courses);
        }
        $tasks = $this->getTaskService()->findTasksByCourseId($curCourse['id']);

        $hasLiveTasks = ArrayToolkit::some($tasks, function ($task) {
            return 'live' === $task['type'];
        });

        $courseSet = $this->getCourseSetService()->getCourseSet($courseSetId);

        
        return $this->render(
            'courseset-manage/sidebar.html.twig',
            array(
                'courseSet' => $courseSet,
                'curCourse' => $curCourse,
                'courses' => $courses,
                'course_side_nav' => $courseSideNav,
                'hasLiveTasks' => $hasLiveTasks,
            )
        );
    }

    //基础信息
    public function baseAction(Request $request, $id)
    {
        $courseSet = $this->getCourseSetService()->tryManageCourseSet($id);

        return $this->redirectToRoute(
            'course_set_manage_course_tasks',
            array(
                'courseSetId' => $courseSet['id'],
                'courseId' => $courseSet['id']
            )
            );

    }


    public function deleteAction($id)
    {
        try {
            $this->getCourseSetService()->deleteCourseSet($id);

            return $this->createJsonResponse(array('success' => true));
        } catch (\Exception $e) {
            return $this->createJsonResponse(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    public function publishAction($id)
    {
        try {
            $courseSet = $this->getCourseSetService()->getCourseSet($id);

            $this->getCourseSetService()->publishCourseSet($id);

            return $this->createJsonResponse(array('success' => true));
        } catch (\Exception $e) {
            return $this->createJsonResponse(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    public function closeAction($id)
    {
        try {
            $this->getCourseSetService()->closeCourseSet($id);

            return $this->createJsonResponse(array('success' => true));
        } catch (\Exception $e) {
            return $this->createJsonResponse(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    public function unlockConfirmAction($id)
    {
        $this->getCourseSetService()->tryManageCourseSet($id);

        return $this->render(
            'courseset-manage/unlock-confirm.html.twig',
            array(
                'id' => $id,
            )
        );
    }

    public function unlockAction($id)
    {
        try {
            $this->getCourseSetService()->unlockCourseSet($id);

            return $this->createJsonResponse(array('success' => true));
        } catch (\Exception $e) {
            return $this->createJsonResponse(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    public function courseSortAction(Request $request, $courseSetId)
    {
        try {
            $courseIds = $request->request->get('ids');
            $this->getCourseService()->sortCourse($courseSetId, $courseIds);

            return $this->createJsonResponse(true, 200);
        } catch (\Exception $e) {
            return $this->createJsonResponse($e->getMessage(), 500);
        }
    }

    protected function getTemplate($sideNav)
    {
        if (in_array($sideNav, array('files', 'testpaper', 'question'))) {
            return 'courseset-manage/locked-item.html.twig';
        } else {
            return 'courseset-manage/locked.html.twig';
        }
    }

    protected function getCourseTypes()
    {
        return $this->get('web.twig.course_extension')->getCourseTypes();
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
     * @return TagService
     */
    protected function getTagService()
    {
        return $this->createService('Taxonomy:TagService');
    }

    /**
     * @return FileService
     */
    protected function getFileService()
    {
        return $this->createService('Content:FileService');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    /**
     * @return OpenCourseService
     */
    protected function getOpenCourseService()
    {
        return $this->createService('OpenCourse:OpenCourseService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }
}
