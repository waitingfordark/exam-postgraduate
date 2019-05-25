<?php

namespace AppBundle\Controller;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\Paginator;
use Biz\Activity\Service\ActivityService;
use Biz\Content\Service\FileService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\CourseSetService;
use Biz\Course\Service\MemberService;
use Biz\Scratch\Service\MaterialService;
use Biz\Scratch\Service\ScratchService;
use Biz\Scratch\Service\WorkService;
use Biz\System\Service\LogService;
use Biz\Task\Service\TaskResultService;
use Biz\Task\Service\TaskService;
use Biz\User\Service\UserService;
use Codeages\Biz\Framework\Service\Exception\AccessDeniedException;
use MallPlugin\Biz\Mall\Service\ProductOrderService;
use RewardPointPlugin\Biz\RewardPoint\Service\AccountService;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScratchController extends BaseController
{
    //分享给别人后展示的页面
    public function workShareAction(Request $request, $projectId)
    {
        $project = $this->getScratchService()->getProject($projectId);
        if (0 == $project['shareId']) {
            throw $this->createNotFoundException();
        }

        $shareInfo = $this->getScratchService()->getShareInfo($project['shareId']);
        if (empty($shareInfo)) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            'scratch/work-show.html.twig',
            array(
                'project' => $project,
                'shareInfo' => $shareInfo
            )
        );
    }

    //展示页面加载分享信息
    public function workShareInfoAction(Request $request, $shareId)
    {
        $shareInfo = $this->getScratchService()->getShareInfo($shareId);
        if (empty($shareInfo)) {
            throw $this->createNotFoundException();
        }

        $this->getScratchService()->addHits($shareId);
        $response = array(
            'nickname' => $shareInfo['nickname'],
            'projectTitle' => $shareInfo['title'],
            'projectDes' => $shareInfo['summary'],
            'usageDes' => $shareInfo['usageText'],
            'visit' => $shareInfo['hits'],
            'liked' => $shareInfo['upsNum'],
            'status' => true,
        );

        return $this->createJsonResponse($response);
    }

    //展示页面点赞
    public function likeAction(Request $request, $shareId)
    {
        $shareInfo = $this->getScratchService()->getShareInfo($shareId);
        if (empty($shareInfo)) {
            throw $this->createNotFoundException();
        }

        $liked = $request->request->get('liked');
        $success = false;

        if ('true' == $liked) {
            $this->getScratchService()->like($shareId);
            $success = true;
        } elseif ($shareInfo['upsNum'] > 0) {
            $this->getScratchService()->cancelLike($shareId);
            $success = true;
        }

        return $this->createJsonResponse(
            array(
                'success' => $success,
            )
        );
    }

    /**
     * @param Request $request
     * @param $projectId
     *
     * @return JsonResponse
     */
    public function shareLogAction(Request $request, $projectId)
    {
        $logs = $request->request->all();

        if (!ArrayToolkit::requireds($logs, array('userId', 'shareType', 'shareName', 'eventType'))) {
            throw $this->createErrorResponse('缺少必要字段');
        }

        $this->getLogService()->info(
            'scratch',
            'share_project_with_wechat',
            '分享项目到微信(#'.$projectId.')',
            $logs
        );

        return $this->createSuccessJsonResponse();
    }

    /**
     * @param Request $request
     * @param $projectId
     *
     * @return Response
     *
     * @throws AccessDeniedException
     */
    public function showAction(Request $request, $projectId)
    {
        $project = $this->tryTakeProject($projectId);

        $work = $this->getWorkService()->getWorkByProjectId($this->getCurrentUser()->getId(), $projectId);
        $isWork = true;
        if (empty($work)) {
            $isWork = false;
        }

        return $this->render(
            'scratch/show.html.twig',
            array(
                'project' => $project,
                'is_work' => $isWork,
            )
        );
    }

    //flash语言包重定向(flash写死了链接,这里帮助flash获得语言包)
    public function localeAction(Request $request, $filename)
    {
        return $this->redirect('/assets/libs/scratch/locale/'.$filename);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws AccessDeniedException
     */
    public function saveAction(Request $request)
    {
        $projectId = $request->query->get('projectId');
        $project = $this->tryTakeProject($projectId);

        $file = file_get_contents('php://input');

        if ($file) {
            $tmpfile = '/tmp/'.$projectId.'.sb2';
            file_put_contents($tmpfile, $file);

            $record = $this->getFileService()->uploadFile('default', new File($tmpfile));
            $fields = array(
                'fileUri' => $record['uri'],
                'shareId' => $project['shareId'],
            );
            $this->getScratchService()->updateProject($project['id'], $fields);

            if ($project['fileUri']) {
                $this->getFileService()->deleteFileByUri($project['fileUri']);
            }

            return $this->createSuccessJsonResponse();
        }

        return $this->createFailJsonResponse();
    }

    /**
     * @param Request $request
     * @param $projectId
     *
     * @return JsonResponse
     *
     * @throws AccessDeniedException
     */
    public function shareAction(Request $request, $projectId)
    {
        $project = $this->tryTakeProject($projectId);

        $response = array(
            'fileName' => $this->container->get('web.twig.extension')->getFurl(
                $project['fileUri'],
                '../../libs/scratch/project.sb2',
                'default'
            ),
            'status' => false,
        );
        if (!empty($project['shareId'])) {
            $shareInfo = $this->getScratchService()->getShareInfo($project['shareId']);
            $response = array_merge(
                $response,
                array(
                    'userName' => $shareInfo['nickname'],
                    'projectDes' => $shareInfo['summary'],
                    'usageDes' => $shareInfo['usageText'],
                    'status' => true,
                )
            );
        }

        return $this->createJsonResponse($response);
    }

    /**
     * @param Request $request
     * @param $projectId
     *
     * @return JsonResponse
     *
     * @throws AccessDeniedException
     */
    public function shareMsgConfirmAction(Request $request, $projectId)
    {
        $project = $this->tryTakeProject($projectId);

        $_fields = $request->request->all();
        $fields = array(
            'nickname' => $_fields['userName'],
            'title' => $_fields['projectTitle'],
            'summary' => $_fields['projectDes'],
            'usageText' => $_fields['projectUsage'],
        );

        if ($project['shareId']) {
            $this->getScratchService()->updateShareInfo($project['shareId'], $fields);
        } else {
            $this->getScratchService()->saveShareInfo($project, $fields);
        }

        return $this->createJsonResponse(array('status' => true));
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function exploreAction(Request $request)
    {
        $conditions = $request->query->all();
        $conditions['status'] = 'published';

        $paginator = new Paginator(
            $request,
            $this->getScratchMaterialService()->searchMaterialCount($conditions),
            20
        );

        $materials = $this->getScratchMaterialService()->searchMaterials(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $account = $this->getRewardPointAccountService()->getAccountByUserId($this->getCurrentUser()->getId());

        $orders = $this->getMallProductOrderService()->findProductOrdersByUserId($this->getCurrentUser()->getId());
        $orders = ArrayToolkit::index($orders, 'productId');

        return $this->render(
            'scratch/explore.html.twig',
            array(
                'account' => $account,
                'materials' => $materials,
                'productOrders' => $orders,
                'paginator' => $paginator,
            )
        );
    }

    //兑换
    public function exchangeAction(Request $request, $materialId)
    {
        $order = $request->request->all();
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            return $this->createJsonResponse(
                array('status' => 'danger', 'message' => 'json_response.not_login.message')
            );
        }

        $order['userId'] = $user['id'];
        $order['productId'] = $materialId;

        $result = $this->getScratchMaterialService()->exchangeMaterial($order);

        return $this->createJsonResponse($result);
    }

    public function myMaterialAction(Request $request, $id)
    {
        $user = $this->tryGetUser($id);
        $conditions = array('userId' => $user['id']);

        $paginator = new Paginator(
            $request,
            $this->getMallProductOrderService()->countProductOrders($conditions),
            20
        );

        $orders = $this->getMallProductOrderService()->searchProductOrders(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $materials = $this->getScratchMaterialService()->findMaterialsByIds(ArrayToolkit::column($orders, 'productId'));
        $materials = ArrayToolkit::index($materials, 'id');

        return $this->render(
            'scratch/my.html.twig',
            array(
                'user' => $user,
                'orders' => $orders,
                'materials' => $materials,
                'paginator' => $paginator,
            )
        );
    }

    public function myMaterialAjaxAction(Request $request)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('Unauthorized');
        }

        $conditions = $request->query->all();

        $orders = $this->getMallProductOrderService()->findProductOrdersByUserId($user->getId());
        $materialIds = ArrayToolkit::column($orders, 'productId');

        $conditions = array_merge(
            $conditions,
            array(
                'ids' => $materialIds,
            )
        );
        $paginator = new Paginator(
            $request,
            $this->getScratchMaterialService()->searchMaterialCount($conditions),
            20
        );

        $materials = $this->getScratchMaterialService()->searchMaterials(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );
        foreach ($materials as $index => $material) {
            $material['fileUri'] = $this->getWebExtension()->getFpath($material['fileUri']);
            $materials[$index] = ArrayToolkit::filter(
                $material,
                array(
                    'id' => 0,
                    'title' => '',
                    'type' => '',
                    'fileUri' => '',
                    'categoryId' => 0,
                    'price' => 0.01,
                )
            );
        }

        return $this->createJsonResponse(
            array(
                'paginator' => Paginator::toArray($paginator),
                'materials' => $materials,
            )
        );
    }

    public function myWorkAction(Request $request, $id)
    {
        $user = $this->tryGetUser($id);
        $conditions = array('userId' => $user['id']);
        if ($user['id'] != $this->getCurrentUser()->getId()) {
            $conditions['status'] = 'published';
        }

        $paginator = new Paginator(
            $request,
            $this->getWorkService()->searchWorkCount($conditions),
            20
        );

        $work = $this->getWorkService()->searchWorks(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render(
            'scratch/my-work.html.twig',
            array(
                'user' => $user,
                'works' => $work,
                'paginator' => $paginator,
            )
        );
    }

    public function workShowAction(Request $request, $id)
    {
        return $this->render(
            'scratch/work.html.twig',
            array(
                'id' => $id,
            )
        );
    }

    public function workDetailAction(Request $request, $id)
    {
        $work = $this->getWorkService()->getWork($id);
        if (empty($work)) {
            throw $this->createNotFoundException();
        }
        $user = $this->tryGetUser($work['userId']);
        $project = $this->getScratchService()->getProject($work['projectId']);
        $this->getWorkService()->addHits($work['id']);

        return $this->render(
            'scratch/work.iframe.html.twig',
            array(
                'user' => $user,
                'work' => $work,
                'project' => $project,
            )
        );
    }

    public function workLikeAction(Request $request, $id)
    {
        $work = $this->getWorkService()->getWork($id);
        if (empty($work)) {
            throw $this->createNotFoundException();
        }

        $liked = $request->request->get('liked');
        $success = false;

        if ('true' == $liked) {
            $this->getWorkService()->like($id);
            $success = true;
        } elseif ($work['upsNum'] > 0) {
            $this->getWorkService()->cancelLike($id);
            $success = true;
        }

        return $this->createJsonResponse(
            array(
                'success' => $success,
            )
        );
    }

    /**
     * @param Request $request
     * @param $projectId
     *
     * @return JsonResponse|Response
     *
     * @throws AccessDeniedException
     */
    public function workRecordAction(Request $request, $projectId)
    {
        $project = $this->tryTakeProject($projectId);

        $number = $request->query->get('number');

        //根据$projectId获取work
        $user = $this->getCurrentUser();
        $work = $this->getWorkService()->getWorkByProjectId($user['id'], $projectId);

        if ('POST' == $request->getMethod()) {
            $temp_fields = $request->request->all();

            $fields = $this->getWorkService()->getPictureFromImage($temp_fields['image']);
            $fields = array_merge($temp_fields, $fields);
            unset($fields['image']);

            $this->getWorkService()->updateWorkInfo($work, $fields);

            return $this->createJsonResponse(array('status' => true));
        }

        if (!empty($work)) {
            return $this->render(
                'scratch/work-info.modal.html.twig',
                array(
                    'work' => $work,
                    'user' => $user,
                    'project' => $project,
                    'number' => $number,
                )
            );
        } else {
            return $this->createJsonResponse(array('status' => false));
        }
    }

    public function coverCropAction(Request $request)
    {
        if ('POST' == $request->getMethod()) {
            $data = $request->request->all();
            list($fileUri, $imageAttr) = $this->getWorkService()->generatePicture($data['images']);
            $cover = $this->getWebExtension()->getFpath($fileUri);
            $cover = $cover.'?cover='.$imageAttr;

            return $this->createJsonResponse(array('image' => $cover));
        }

        return $this->render('courseset-manage/cover-crop-modal.html.twig');
    }

    public function checkListAction(Request $request, $id)
    {
        $course = $this->getCourseService()->tryManageCourse($id);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $user = $this->getUser();
        $isTeacher = $this->getCourseMemberService()->isCourseTeacher(
            $course['id'],
            $user['id']
        ) || $user->isSuperAdmin();

        $conditions = array(
            'fromCourseId' => $id,
            'mediaType' => 'coding',
        );

        $paginator = new Paginator(
            $request,
            $this->getActivityService()->count($conditions),
            10
        );
        $activities = $this->getActivityService()->search(
            $conditions,
            array('endTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render(
            'scratch/check-list.html.twig',
            array(
                'courseSet' => $courseSet,
                'course' => $course,
                'isTeacher' => $isTeacher,
                'activities' => $activities,
                'paginator' => $paginator,
            )
        );
    }

    public function checkDetailAction(Request $request, $courseId, $activityId)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        $conditions = $request->query->all();
        $conditions['activityId'] = $activityId;

        if (!empty($conditions['nickname'])) {
            $user = $this->getUserService()->getUserByNickname($conditions['nickname']);
            $conditions['userId'] = $user ? $user['id'] : -1;
        }

        $paginator = new Paginator(
            $request,
            $this->getTaskResultService()->countTaskResults($conditions),
            20
        );

        $results = $this->getTaskResultService()->searchTaskResults(
            $conditions,
            array('updatedTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($results, 'userId'));

        return $this->render(
            'scratch/check-detail.html.twig',
            array(
                'courseSet' => $courseSet,
                'course' => $course,
                'users' => $users,
                'results' => $results,
                'paginator' => $paginator,
            )
        );
    }

    public function createWorkAction(Request $request)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('need login');
        }

        $work = $this->getWorkService()->getLastWorkByUserId($user['id']);
        if (!empty($work['title']) || empty($work)) {
            $work = $this->getWorkService()->createWork(array('userId' => $user['id']));
        }

        return $this->redirect($this->generateUrl('scratch_show', array('projectId' => $work['projectId'])));
    }

    public function workExploreAction(Request $request)
    {
        $orderby = $request->query->get('orderBy');
        $page = $request->query->get('page');

        return $this->render(
            'default/selected-works.html.twig',
            array(
                'orderBy' => $orderby,
                'page' => $page,
            )
        );
    }

    protected function tryGetUser($id)
    {
        $user = $this->getUserService()->getUser($id);

        if (empty($user)) {
            throw $this->createNotFoundException();
        }

        return $user;
    }

    /**
     * @param $projectId
     *
     * @return mixed
     *
     * @throws AccessDeniedException
     */
    protected function tryTakeProject($projectId)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('Unauthorized');
        }

        $project = $this->getScratchService()->getProject($projectId);
        if (empty($project)) {
            throw $this->createNotFoundException();
        }
        if (!$this->canTakeProject($project) && !$user->isSuperAdmin()) {
            throw $this->createAccessDeniedException();
        }

        return $project;
    }

    protected function canTakeProject($project)
    {
        $user = $this->getCurrentUser();

        return $project['userId'] == $user['id'];
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    /**
     * @return ScratchService
     */
    protected function getScratchService()
    {
        return $this->createService('Scratch:ScratchService');
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    /**
     * @return FileService
     */
    protected function getFileService()
    {
        return $this->createService('Content:FileService');
    }

    /**
     * @return MaterialService
     */
    protected function getScratchMaterialService()
    {
        return $this->createService('Scratch:MaterialService');
    }

    /**
     * @return AccountService
     */
    protected function getRewardPointAccountService()
    {
        return $this->createService('RewardPointPlugin:RewardPoint:AccountService');
    }

    /**
     * @return ProductOrderService
     */
    protected function getMallProductOrderService()
    {
        return $this->createService('MallPlugin:Mall:ProductOrderService');
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
     * @return WorkService
     */
    protected function getWorkService()
    {
        return $this->createService('Scratch:WorkService');
    }

    /**
     * @return MemberService
     */
    protected function getCourseMemberService()
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
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }
}
