<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\Paginator;
use Biz\Scratch\Service\WorkService;
use Symfony\Component\HttpFoundation\Request;

class ScratchWorkController extends BaseController
{
    public function indexAction(Request $request)
    {
        $conditions = $request->query->all();

        $conditions['status'] = 'published';
        $paginator = new Paginator(
            $request,
            $this->getScratchWorkService()->searchWorkCount($conditions),
            20
        );

        $work = $this->getScratchWorkService()->searchWorks(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $creatorIds = ArrayToolkit::column($work, 'userId');
        $creators = $this->getUserService()->findUsersByIds($creatorIds);

        $verifyUserIds = ArrayToolkit::column($work, 'verifyUserId');
        $verifyUsers = $this->getUserService()->findUsersByIds($verifyUserIds);

        return $this->render(
            'admin/scratch-work/index.html.twig',
            array('works' => $work, 'paginator' => $paginator, 'creators' => $creators, 'verifyUsers' => $verifyUsers)
        );
    }

    public function cancelPublishAction(Request $request, $id)
    {
        $work = $this->getScratchWorkService()->cancelPublishWork($id);

        return $this->renderWorkTr($work);
    }

    public function cancelRecommendAction(Request $request, $id)
    {
        $work = $this->getScratchWorkService()->cancelRecommendWork($id);

        return $this->renderWorkTr($work);
    }

    public function recommendAction(Request $request, $id)
    {
        $work = $this->getScratchWorkService()->getWork($id);
        if (empty($work)) {
            return $this->createResourceNotFoundException('scratchWork', $id);
        }

        if ('POST' == $request->getMethod()) {
            $number = $request->request->get('number');

            $work = $this->getScratchWorkService()->recommendWork($id, $number);

            return $this->renderWorkTr($work);
        }

        return $this->render(
            'admin/scratch-work/work-recommend-modal.html.twig',
            array(
                'work' => $work,
            )
        );
    }

    public function verifyAction(Request $request)
    {
        $conditions = $request->query->all();

        $conditions['status'] = 'verify';
        $paginator = new Paginator(
            $request,
            $this->getScratchWorkService()->searchWorkCount($conditions),
            20
        );

        $work = $this->getScratchWorkService()->searchWorks(
            $conditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $creatorIds = ArrayToolkit::column($work, 'userId');
        $creators = $this->getUserService()->findUsersByIds($creatorIds);

        return $this->render(
            'admin/scratch-work/verify.html.twig',
            array('works' => $work, 'paginator' => $paginator, 'creators' => $creators)
        );
    }

    public function publishAction(Request $request, $id)
    {
        $work = $this->getScratchWorkService()->publishWork($id);

        return $this->renderWorkTr($work);
    }

    protected function renderWorkTr($work)
    {
        $users = $this->getUserService()->findUsersByIds(array($work['userId'], $work['verifyUserId']));
        $creator = $users[$work['userId']];
        $verifyUser = $users[$work['verifyUserId']];

        return $this->render(
            'admin/scratch-work/tr.html.twig',
            array('work' => $work, 'creator' => $creator, 'verifyUser' => $verifyUser)
        );
    }

    /**
     * @return WorkService
     */
    protected function getScratchWorkService()
    {
        return $this->createService('Scratch:WorkService');
    }
}
