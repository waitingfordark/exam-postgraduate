<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Common\Paginator;
use Biz\Crontab\SystemCrontabInitializer;
use Biz\Task\Service\TaskService;
use Codeages\Biz\Framework\Scheduler\Service\SchedulerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Common\ArrayToolkit;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\MemberService;
use Biz\Course\Service\ThreadService;
use Biz\System\Service\SettingService;
use Biz\Task\Service\TaskResultService;
use Biz\Course\Service\CourseSetService;
use Biz\CloudPlatform\Service\AppService;
use Biz\Taxonomy\Service\CategoryService;
use Biz\Classroom\Service\ClassroomService;
use Biz\Course\Service\CourseDeleteService;
use Biz\Testpaper\Service\TestpaperService;
use Symfony\Component\HttpFoundation\Request;
use Biz\Activity\Service\ActivityLearnLogService;
use Codeages\Biz\Framework\Service\Exception\AccessDeniedException;
use Codeages\Biz\Framework\Service\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use VipPlugin\Biz\Vip\Service\LevelService;

class CourseSetController extends BaseController
{
    public function indexAction(Request $request, $filter)
    {
        $conditions = $request->query->all();
        $conditions['excludeTypes'] = array('reservation');
        $conditions = $this->filterCourseSetConditions($filter, $conditions);

        $paginator = new Paginator(
            $this->get('request'),
            $this->getCourseSetService()->countCourseSets($conditions),
            20
        );
        $courseSets = $this->getCourseSetService()->searchCourseSets(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        list($courseSets, $coursesCount) = $this->findRelatedOptions($filter, $courseSets);

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($courseSets, 'creator'));
        $courseSetStatusNum = $this->getDifferentCourseSetsNum($conditions);

        return $this->render(
            'admin/course-set/index.html.twig',
            array(
                'courseSets' => $courseSets,
                'users' => $users,
                'paginator' => $paginator,
                'filter' => $filter,
                'courseSetStatusNum' => $courseSetStatusNum,
                'coursesCount' => $coursesCount,
            )
        );
    }

    protected function getDifferentCourseSetsNum($conditions)
    {
        $total = $this->getCourseSetService()->countCourseSets($conditions);
        $published = $this->getCourseSetService()->countCourseSets(array_merge($conditions, array('status' => 'published')));
        $closed = $this->getCourseSetService()->countCourseSets(array_merge($conditions, array('status' => 'closed')));
        $draft = $this->getCourseSetService()->countCourseSets(array_merge($conditions, array('status' => 'draft')));

        return array(
            'total' => empty($total) ? 0 : $total,
            'published' => empty($published) ? 0 : $published,
            'closed' => empty($closed) ? 0 : $closed,
            'draft' => empty($draft) ? 0 : $draft,
        );
    }

    public function closeAction(Request $request, $id)
    {
        $this->getCourseSetService()->closeCourseSet($id);

        return $this->renderCourseTr($id, $request);
    }

    /*
    code 状态编号
    1:　删除班级课程
    2: 移除班级课程
    0: 删除未发布课程成功
     */
    public function deleteAction(Request $request, $id)
    {
        $currentUser = $this->getUser();

        if (!$currentUser->hasPermission('admin_course_set_delete')) {
            throw $this->createAccessDeniedException('您没有删除课程的权限！');
        }

        $courseSet = $this->getCourseSetService()->getCourseSet($id);
    
        try {
            if ('draft' == $courseSet['status']) {
                $this->getCourseSetService()->deleteCourseSet($id);

                return $this->createJsonResponse(array('code' => 0, 'message' => '删除课程成功'));
            }

            $isCheckPassword = $request->getSession()->get('checkPassword');
            if (!$isCheckPassword) {
                return $this->render('admin/course/delete.html.twig', array('courseSet' => $courseSet));
            }

            $request->getSession()->remove('checkPassword');

            $this->getCourseSetService()->deleteCourseSet($id);

            return $this->createJsonResponse(array('code' => 0, 'message' => '删除课程成功'));
        } catch (\Exception $e) {
            return $this->createJsonResponse(array('code' => -1, 'message' => $e->getMessage()));
        }
    }

    public function checkPasswordAction(Request $request)
    {
        if ('POST' == $request->getMethod()) {
            $password = $request->request->get('password');
            $currentUser = $this->getUser();
            $password = $this->getPasswordEncoder()->encodePassword($password, $currentUser->salt);

            if ($password == $currentUser->password) {
                $response = array('success' => true, 'message' => '密码正确');
                $request->getSession()->set('checkPassword', true);
            } else {
                $response = array('success' => false, 'message' => '密码错误');
            }

            return $this->createJsonResponse($response);
        }
        throw new AccessDeniedException('Method Not Allowed');
    }

    public function publishAction(Request $request, $id)
    {
        $courseSet = $this->getCourseSetService()->getCourseSet($id);

        if ('live' == $courseSet['type']) {
            $course = $this->getCourseService()->getDefaultCourseByCourseSetId($courseSet['id']);

            if (empty($course['maxStudentNum'])) {
                return $this->createJsonResponse(array(
                    'success' => false,
                    'message' => '直播课程发布前需要在计划设置中设置课程人数',
                ));
            }
        }

        $this->getCourseSetService()->publishCourseSet($id);

        return $this->renderCourseTr($id, $request);
    }

    public function cloneAction(Request $request, $courseSetId)
    {
        $courseSet = $this->getCourseSetService()->getCourseSet($courseSetId);

        return $this->render(
            'admin/course-set/course-set-clone-modal.html.twig',
            array(
                'courseSet' => $courseSet,
            )
        );
    }


    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    protected function renderCourseTr($courseId, $request)
    {
        $fields = $request->query->all();
        $courseSet = $this->getCourseSetService()->getCourseSet($courseId);
        $courseSet['defaultCourse'] = $this->getCourseService()->getDefaultCourseByCourseSetId($courseId);
        $default = $this->getSettingService()->get('default', array());
        $classrooms = array();
        $vips = array();

        if ('classroom' == $fields['filter']) {
            $classrooms = $this->getClassroomService()->findClassroomCourseByCourseSetIds(array($courseSet['id']));
            $classrooms = ArrayToolkit::index($classrooms, 'courseSetId');

            foreach ($classrooms as $key => $classroom) {
                $classroomInfo = $this->getClassroomService()->getClassroom($classroom['classroomId']);
                $classrooms[$key]['classroomTitle'] = $classroomInfo['title'];
            }
        } elseif ('vip' == $fields['filter']) {
            if ($this->isPluginInstalled('Vip')) {
                $vips = $this->getVipLevelService()->searchLevels(array(), 0, PHP_INT_MAX);
                $vips = ArrayToolkit::index($vips, 'id');
            }
        }

        return $this->render(
            'admin/course-set/tr.html.twig',
            array(
                'user' => $this->getUserService()->getUser($courseSet['creator']),
                'category' => isset($courseSet['categoryId']) ? $this->getCategoryService()->getCategory(
                    $courseSet['categoryId']
                ) : array(),
                'courseSet' => $courseSet,
                'default' => $default,
                'classrooms' => $classrooms,
                'filter' => $fields['filter'],
                'vips' => $vips,
            )
        );
    }

    public function chooserAction(Request $request)
    {
        $conditions = $request->query->all();
        $conditions['parentId'] = 0;

        if (isset($conditions['categoryId']) && '' == $conditions['categoryId']) {
            unset($conditions['categoryId']);
        }

        if (isset($conditions['status']) && '' == $conditions['status']) {
            unset($conditions['status']);
        }

        if (isset($conditions['title']) && '' == $conditions['title']) {
            unset($conditions['title']);
        }

        if (isset($conditions['creator']) && '' == $conditions['creator']) {
            unset($conditions['creator']);
        }

        $count = $this->getCourseSetService()->countCourseSets($conditions);

        $paginator = new Paginator($this->get('request'), $count, 20);

        $courseSets = $this->getCourseSetService()->searchCourseSets(
            $conditions,
            null,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $categories = $this->getCategoryService()->findCategoriesByIds(ArrayToolkit::column($courseSets, 'categoryId'));

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($courseSets, 'creator'));

        return $this->render(
            'admin/course/course-set-chooser.html.twig',
            array(
                'users' => $users,
                'conditions' => $conditions,
                'courseSets' => $courseSets,
                'categories' => $categories,
                'paginator' => $paginator,
            )
        );
    }

    public function courseListAction(Request $request, $id)
    {
        $conditions = array(
            'courseSetId' => $id,
        );

        $paginator = new Paginator(
            $this->get('request'),
            $this->getCourseService()->countCourses($conditions),
            10
        );

        $courses = $this->getCourseService()->searchCourses(
            $conditions,
            array('seq' => 'DESC', 'createdTime' => 'asc'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $userIds = ArrayToolkit::column($courses, 'creator');
        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render('admin/course-set/course-list-modal.html.twig', array(
            'courses' => $courses,
            'users' => $users,
            'paginator' => $paginator,
        ));
    }

    protected function filterCourseSetConditions($filter, $conditions)
    {
        if ('classroom' == $filter) {
            $conditions['parentId_GT'] = 0;
        } elseif ('vip' == $filter) {
            $conditions['isVip'] = 1;
            $conditions['parentId'] = 0;
        } else {
            $conditions['parentId'] = 0;
            $conditions = $this->filterCourseSetType($conditions);
        }

        $conditions = $this->fillOrgCode($conditions);

        if (!empty($conditions['categoryId'])) {
            $categorIds = $this->getCategoryService()->findCategoryChildrenIds($conditions['categoryId']);
            $categorIds[] = $conditions['categoryId'];
            $conditions['categoryIds'] = $categorIds;
            unset($conditions['categoryId']);
        }

        return $conditions;
    }

    protected function findRelatedOptions($filter, $courseSets)
    {
        $coursesCount = array();
        $courseSetIds = ArrayToolkit::column($courseSets, 'id');        
        
        $coursesCount = $this->getCourseService()->countCoursesGroupByCourseSetIds($courseSetIds);
        $coursesCount = ArrayToolkit::index($coursesCount, 'courseSetId');
        
        return array($courseSets, $coursesCount);
    }


    protected function filterCourseSetType($conditions)
    {
        if (!$this->getWebExtension()->isPluginInstalled('Reservation')) {
            $conditions['excludeTypes'] = array('reservation');
        }

        return $conditions;
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
     * @return CourseDeleteService
     */
    protected function getCourseSetDeleteService()
    {
        return $this->createService('Course:CourseSetDeleteService');
    }

    /**
     * @return CategoryService
     */
    protected function getCategoryService()
    {
        return $this->createService('Taxonomy:CategoryService');
    }

    /**
     * @return TestpaperService
     */
    protected function getTestpaperService()
    {
        return $this->createService('Testpaper:TestpaperService');
    }

    /**
     * @return AppService
     */
    protected function getAppService()
    {
        return $this->createService('CloudPlatform:AppService');
    }

    /**
     * @return ClassroomService
     */
    protected function getClassroomService()
    {
        return $this->createService('Classroom:ClassroomService');
    }

    /**
     * @return MessageDigestPasswordEncoder
     */
    protected function getPasswordEncoder()
    {
        return new MessageDigestPasswordEncoder('sha256');
    }

    /**
     * @return LevelService
     */
    protected function getVipLevelService()
    {
        return $this->createService('VipPlugin:Vip:LevelService');
    }

    /**
     * @return MemberService
     */
    protected function getMemberService()
    {
        return $this->createService('Course:MemberService');
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
     * @return ThreadService
     */
    protected function getThreadService()
    {
        return $this->createService('Course:ThreadService');
    }

    /**
     * @return ActivityLearnLogService
     */
    protected function getActivityLearnLogService()
    {
        return $this->createService('Activity:ActivityLearnLogService');
    }

    /**
     * @return SchedulerService
     */
    protected function getSchedulerService()
    {
        return $this->createService('Scheduler:SchedulerService');
    }

    protected function getWebExtension()
    {
        return $this->get('web.twig.extension');
    }
}
