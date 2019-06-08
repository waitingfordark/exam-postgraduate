<?php

namespace AppBundle\Controller\My;

use AppBundle\Common\Paginator;
use Biz\Classroom\Service\ClassroomService;
use Biz\Task\Service\TaskService;
use AppBundle\Common\ArrayToolkit;
use Biz\System\Service\SettingService;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\Course\CourseBaseController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CourseSetController extends CourseBaseController
{
    public function teachingAction(Request $request, $filter = 'normal')
    {
        $user = $this->getCurrentUser();

        if (!$user->isTeacher()) {
            return $this->createMessageResponse('error', '您不是老师，不能查看此页面！');
        }

        $conditions = array(
            'type' => $filter,
            'parentId' => 0,
        );

        $paginator = new Paginator(
            $this->get('request'),
            $this->getCourseSetService()->countUserTeachingCourseSets($user['id'], $conditions),
            20
        );

        $courseSets = $this->getCourseSetService()->searchUserTeachingCourseSets(
            $user['id'],
            $conditions,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $service = $this->getCourseService();
        $that = $this;
        $courseSets = array_map(
            function ($set) use ($user, $service, $that) {
                $courseNum = $service->countCourses(array(
                    'courseSetId' => $set['id'],
                ));

                if ($courseNum > 1) {
                    $set['redirect_path'] = $that->generateUrl('course_set_manage_courses', array('courseSetId' => $set['id']));
                } else {
                    $courses = $service->findCoursesByCourseSetId($set['id']);
                    $set['redirect_path'] = $that->generateUrl('course_set_manage_course_info', array('courseSetId' => $set['id'], 'courseId' => $courses['0']['id']));
                }

                $set['courseNum'] = $courseNum;

                return $set;
            },
            $courseSets
        );

        return $this->render(
            'my/teaching/course-sets.html.twig',
            array(
                'courseSets' => $courseSets,
                'paginator' => $paginator,
                'filter' => $filter,
            )
        );
    }

    public function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }


    public function livesAction(Request $request)
    {
        $currentUser = $this->getCurrentUser();

        $courseSets = $this->getCourseSetService()->findLearnCourseSetsByUserId($currentUser['id']);
        $setIds = ArrayToolkit::column($courseSets, 'id');
        $courses = $this->getCourseService()->findCoursesByCourseSetIds($setIds);
        $courseIds = ArrayToolkit::column($courses, 'id');

        $conditions = array(
            'status' => 'published',
            'startTime_GE' => time(),
            'parentId' => 0,
            'courseIds' => $courseIds,
            'type' => 'live',
        );

        $paginator = new Paginator(
            $this->get('request'),
            $this->getTaskService()->countTasks($conditions),
            10
        );

        $tasks = $this->getTaskService()->searchTasks(
            $conditions,
            array('startTime' => 'ASC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $courseSets = ArrayToolkit::index($courseSets, 'id');
        $courses = ArrayToolkit::index($courses, 'id');

        $newCourseSets = array();
        if (!empty($courseSets)) {
            foreach ($tasks as $key => &$task) {
                $course = $courses[$task['courseId']];
                $courseSetId = $course['courseSetId'];
                $newCourseSets[$courseSetId] = $courseSets[$courseSetId];
                $newCourseSets[$courseSetId]['task'] = $task;
            }
        }

        $default = $this->getSettingService()->get('default', array());

        return $this->render(
            'my/learning/course-set/live-list.html.twig',
            array(
                'courseSets' => $newCourseSets,
                'paginator' => $paginator,
                'default' => $default,
            )
        );
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * @return ClassroomService
     */
    protected function getClassroomService()
    {
        return $this->createService('Classroom:ClassroomService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getOpenCourseService()
    {
        return $this->createService('OpenCourse:OpenCourseService');
    }
}
