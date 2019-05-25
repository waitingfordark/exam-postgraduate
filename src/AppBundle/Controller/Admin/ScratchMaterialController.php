<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\Exception\AccessDeniedException;
use AppBundle\Common\FileToolkit;
use AppBundle\Common\Paginator;
use Biz\Content\Service\FileService;
use Biz\Scratch\Service\MaterialService;
use MallPlugin\Biz\Mall\Service\ProductOrderService;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

class ScratchMaterialController extends BaseController
{
    public function indexAction(Request $request)
    {
        $conditions = $request->query->all();

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

        $creatorIds = ArrayToolkit::column($materials, 'createdUserId');
        $creators = $this->getUserService()->findUsersByIds($creatorIds);

        return $this->render(
            'admin/scratch-material/index.html.twig',
            array('materials' => $materials, 'paginator' => $paginator, 'creators' => $creators)
        );
    }

    public function createAction(Request $request)
    {
        if ($request->isMethod('POST')) {
            $fields = $request->request->all();

            if (!empty($fields['fileUri'])) {
                $parsed = $this->getFileService()->parseFileUri($fields['fileUri']);
                $file = $this->getFileService()->uploadFile('scratch_material', new File($parsed['fullpath']));
                $this->getFileService()->deleteFileByUri($fields['fileUri']);
                $fields['fileUri'] = $file['uri'];
            }

            $this->getScratchMaterialService()->createMaterial($fields);

            return $this->redirect($this->generateUrl('admin_scratch_material'));
        }

        return $this->render('admin/scratch-material/modal.html.twig');
    }

    public function editAction(Request $request, $id)
    {
        $material = $this->getScratchMaterialService()->getMaterial($id);
        if ($request->isMethod('POST')) {
            $fields = $request->request->all();

            if (!empty($fields['fileUri']) && $material['fileUri'] != $fields['fileUri']) {
                $parsed = $this->getFileService()->parseFileUri($fields['fileUri']);
                $file = $this->getFileService()->uploadFile('scratch_material', new File($parsed['fullpath']));
                $this->getFileService()->deleteFileByUri($fields['fileUri']);
                $this->getFileService()->deleteFileByUri($material['fileUri']);
                $fields['fileUri'] = $file['uri'];
            }

            $this->getScratchMaterialService()->updateMaterial($id, $fields);

            return $this->redirectToRoute('admin_scratch_material');
        }

        return $this->render('admin/scratch-material/modal.html.twig', array('material' => $material));
    }

    public function publishAction(Request $request, $id)
    {
        $material = $this->getScratchMaterialService()->publishMaterial($id);

        return $this->renderMaterialTr($material);
    }

    public function closeAction(Request $request, $id)
    {
        $material = $this->getScratchMaterialService()->closeMaterial($id);

        return $this->renderMaterialTr($material);
    }

    public function deleteAction(Request $request, $id)
    {
        $this->getScratchMaterialService()->deleteMaterial($id);

        return $this->createSuccessJsonResponse();
    }

    public function categoryAction(Request $request)
    {
        return $this->forward(
            'AppBundle:Admin/Category:embed',
            array(
                'group' => 'scratch_material',
                'layout' => 'admin/layout.html.twig',
                'menu' => 'admin_operation_scratch_material_category_manage',
            )
        );
    }

    public function fileUploadAction(Request $request)
    {
        $response = $request->request->get('response');
        $type = $request->request->get('type');
        $fileId = $response['id'];
        $objectFile = $this->getFileService()->getFileObject($fileId);

        if (!FileToolkit::isImageFile($objectFile)) {
            throw new AccessDeniedException('图片格式不正确！');
        }

        $file = $this->getFileService()->getFile($fileId);

        $response = array(
            'path' => $file['uri'],
            'url' => $this->get('web.twig.extension')->getFilePath($file['uri']),
        );

        return $this->createJsonResponse($response);
    }

    public function recordAction(Request $request)
    {
        list($orders, $paginator) = $this->getRecordOrdersAndPaginator($request);

        $creatorIds = ArrayToolkit::column($orders, 'userId');
        $creators = $this->getUserService()->findUsersByIds($creatorIds);

        $materialIds = ArrayToolkit::column($orders, 'productId');
        $materials = $this->getScratchMaterialService()->findMaterialsByIds($materialIds);
        $materials = ArrayToolkit::index($materials, 'id');

        return $this->render(
            'admin/scratch-material/record.html.twig',
            array(
                'orders' => $orders,
                'creators' => $creators,
                'materials' => $materials,
                'paginator' => $paginator,
            )
        );
    }

    protected function getRecordOrdersAndPaginator($request)
    {
        $conditions = array_merge(
            array(
                'type' => '',
                'categoryId' => '',
            ),
            $request->query->all()
        );
        $orderConditions = array('type' => 'scratch');
        $paginator = $orders = array();

        if (!empty($conditions['type']) || !empty($conditions['categoryId'])) {
            $materials = $this->getScratchMaterialService()->searchMaterials(
                $conditions,
                array('createdTime' => 'DESC'),
                0,
                $this->getScratchMaterialService()->searchMaterialCount($conditions)
            );

            $orderConditions['productIds'] = ArrayToolkit::column($materials, 'id');
        }

        if (!empty($conditions['keyword'])) {
            if ('nickname' == $conditions['keywordType']) {
                $user = $this->getUserService()->getUserByNickname($conditions['keyword']);
                $orderConditions['userId'] = $user['id'] ? $user['id'] : 0;
            }

            if ('titleLike' == $conditions['keywordType']) {
                $orderConditions['titleLike'] = $conditions['keyword'];
            }
        }

        if ($conditions['type'] != '' && empty($orderConditions['productIds'])) {
            $orderConditions['productIds'] = array(-1);
        }

        $paginator = new Paginator(
            $request,
            $this->getProductOrderService()->countProductOrders($orderConditions),
            20
        );

        $orders = $this->getProductOrderService()->searchProductOrders(
            $orderConditions,
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return array($orders, $paginator);
    }

    protected function renderMaterialTr($material)
    {
        $creator = $this->getUserService()->getUser($material['createdUserId']);

        return $this->render(
            'admin/scratch-material/tr.html.twig',
            array('material' => $material, 'creator' => $creator)
        );
    }

    /**
     * @return MaterialService
     */
    protected function getScratchMaterialService()
    {
        return $this->createService('Scratch:MaterialService');
    }

    /**
     * @return FileService
     */
    protected function getFileService()
    {
        return $this->createService('Content:FileService');
    }

    /**
     * @return ProductOrderService
     */
    protected function getProductOrderService()
    {
        return $this->createService('MallPlugin:Mall:ProductOrderService');
    }
}
