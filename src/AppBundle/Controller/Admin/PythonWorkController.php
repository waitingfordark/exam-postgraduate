<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\Paginator;
use Symfony\Component\HttpFoundation\Request;

class PythonWorkController extends BaseController
{
    public function indexAction(Request $request)
    {
        $conditions = $request->query->all();

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

        $creatorIds = ArrayToolkit::column($work, 'userId');
        $creators = $this->getUserService()->findUsersByIds($creatorIds);

        return $this->render(
            'admin/python-work/index.html.twig',
            array('works' => $work, 'paginator' => $paginator, 'creators' => $creators)
        );
    }

    public function cancelRecommendAction(Request $request, $id)
    {
        $work = $this->getWorkService()->cancelRecommendWork($id);

        return $this->renderWorkTr($work);
    }

    public function recommendAction(Request $request, $id)
    {
        $work = $this->getWorkService()->getWork($id);
        if (empty($work)) {
            return $this->createResourceNotFoundException('pythonWork', $id);
        }

        if ('POST' == $request->getMethod()) {
            $number = $request->request->get('number');

            $work = $this->getWorkService()->recommendWork($id, $number);

            return $this->renderWorkTr($work);
        }

        return $this->render(
            'admin/python-work/work-recommend-modal.html.twig',
            array(
                'work' => $work,
            )
        );
    }

    protected function renderWorkTr($work)
    {
        $users = $this->getUserService()->findUsersByIds(array($work['userId']));
        $creator = $users[$work['userId']];

        return $this->render(
            'admin/python-work/tr.html.twig',
            array('work' => $work, 'creator' => $creator)
        );
    }

    /**
     * @return \Biz\PythonEditor\Service\WorkService
     */
    protected function getWorkService()
    {
        return $this->createService('PythonEditor:WorkService');
    }
}
