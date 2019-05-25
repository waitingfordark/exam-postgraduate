<?php

namespace AppBundle\Controller;

use AppBundle\Common\Paginator;
use Biz\Content\Service\FileService;
use Biz\PythonEditor\Service\WorkService;
use Biz\Scratch\Service\ScratchService;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

class PythonEditorController extends BaseController
{
    public function indexAction(Request $request)
    {
        $projectId = $request->query->get('projectId');
        $project = $this->tryTakeProject($projectId);
        $code = '';
        if (!empty($project)) {
            $uri = $this->getFileService()->parseFileUri($project['fileUri']);
            $code = file_get_contents($uri['fullpath']);
            $code = base64_decode($code);
        }

        return $this->render(
            'python-editor/index.html.twig',
            array(
                'project' => $project,
                'python_code' => $code,
            )
        );
    }

    public function saveModalAction(Request $request)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('need login');
        }
        $projectId = $request->query->get('projectId');

        $work = $this->getWorkService()->getWorkByProjectId($user['id'], $projectId);

        return $this->render(
            'python-editor/work-info.modal.html.twig',
            array(
                'work' => $work,
            )
        );
    }

    public function saveAction(Request $request)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('need login');
        }

        $projectId = $request->query->get('projectId');
        $project = $this->tryTakeProject($projectId);
        if (empty($project)) {
            $work = $this->getWorkService()->getLastWorkByUserId($user['id']);
            if (!empty($work['title']) || empty($work)) {
                $work = $this->getWorkService()->createWork(array('userId' => $user['id']));
            }
            $project = $this->getScratchService()->getProject($work['projectId']);
        } else {
            $work = $this->getWorkService()->getWorkByProjectId($user['id'], $projectId);
        }

        $file = $request->request->get('code');

        if (empty($work)) {
            $projectId = $project['id'];
        } else {
            $projectId = $work['projectId'];
        }

        if ($file) {
            $tmpfile = '/tmp/'.$projectId.'.txt';
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

            if (!empty($work)) {
                $this->getWorkService()->updateWorkInfo($work, array(
                    'title' => $request->request->get('title'),
                    'publish' => $request->request->get('publish'),
                ));
            }

            return $this->createSuccessJsonResponse(array('url' => $this->generateUrl('python_work_show').'?projectId='.$projectId));
        }

        return $this->createFailJsonResponse();
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
            array('publishTime' => 'DESC','createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render(
            'python-editor/my-work.html.twig',
            array(
                'user' => $user,
                'works' => $work,
                'paginator' => $paginator,
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

    public function workPublishAction(Request $request, $id)
    {
        $work = $this->getWorkService()->getWork($id);
        if (empty($work)) {
            throw $this->createNotFoundException();
        }
        $user = $this->getCurrentUser();
        if (!$user->isLogin() || $work['userId'] != $user['id']){
            throw $this->createAccessDeniedException();
        }
        $this->getWorkService()->publishWork($id);

        return $this->createSuccessJsonResponse();
    }

    public function workDeleteAction(Request $request, $id)
    {
        $work = $this->getWorkService()->getWork($id);
        if (empty($work)) {
            throw $this->createNotFoundException();
        }
        $user = $this->getCurrentUser();
        if (!$user->isLogin() || $work['userId'] != $user['id']){
            throw $this->createAccessDeniedException();
        }
        $this->getWorkService()->deleteWork($id);

        return $this->createSuccessJsonResponse();
    }

    protected function tryTakeProject($projectId)
    {
        $project = $this->getScratchService()->getProject($projectId);
        if (empty($project)) {
            return array();
        }
        $user = $this->getCurrentUser();
        if (!$user->isLogin() || ($project['userId'] != $user['id'] && !$user->isSuperAdmin())) {
            $work = $this->getWorkService()->getWorkByProjectId($project['userId'], $projectId);
            if (!isset($work['status']) || 'published' != $work['status']) {
                return array();
            }
        }

        return $project;
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
     * @return ScratchService
     */
    protected function getScratchService()
    {
        return $this->createService('Scratch:ScratchService');
    }

    /**
     * @return WorkService
     */
    protected function getWorkService()
    {
        return $this->createService('PythonEditor:WorkService');
    }

    /**
     * @return FileService
     */
    protected function getFileService()
    {
        return $this->createService('Content:FileService');
    }
}
