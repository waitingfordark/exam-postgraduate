<?php

namespace Biz\Scratch\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\Exception\InvalidArgumentException;
use Biz\BaseService;
use Biz\Content\Service\FileService;
use Biz\RewardPoint\Service\AccountFlowService;
use Biz\Scratch\Dao\ScratchMaterialDao;
use Biz\Scratch\Service\MaterialService;
use Biz\System\Service\LogService;
use Biz\Taxonomy\Service\CategoryService;
use Biz\User\Service\UserService;
use MallPlugin\Biz\Mall\Service\ProductOrderService;
use RewardPointPlugin\Biz\RewardPoint\Service\AccountService;

/**
 * Class MaterialServiceImpl
 */
class MaterialServiceImpl extends BaseService implements MaterialService
{
    public function getMaterial($id)
    {
        return $this->getScratchMaterialDao()->get($id);
    }

    public function createMaterial(array $material)
    {
        $this->checkRequiredFields($material);
        $material = $this->partsFields($material);
        if (isset($material['price'])) {
            $material['price'] = intval($material['price']);
        }

        $user = $this->getCurrentUser();
        $material['createdUserId'] = $user['id'];
        $material['updatedUserId'] = $user['id'];

        $material = $this->getScratchMaterialDao()->create($material);

        $this->getLogService()->info(
            'scratch_material',
            'create',
            sprintf('创建 Scratch 素材《%s》(#%s)', $material['title'], $material['id']),
            array('material' => $material)
        );

        return $material;
    }

    public function updateMaterial($id, array $fields)
    {
        $this->checkRequiredFields($fields);
        $fields = $this->partsFields($fields);
        if (isset($fields['price'])) {
            $fields['price'] = intval($fields['price']);
        }

        $user = $this->getCurrentUser();
        $fields['updatedUserId'] = $user['id'];

        $material = $this->getScratchMaterialDao()->update($id, $fields);

        $this->getLogService()->info(
            'scratch_material',
            'update',
            sprintf('更新 Scratch 素材《%s》(#%s)', $material['title'], $material['id']),
            array('material' => $material)
        );

        return $material;
    }

    public function publishMaterial($id)
    {
        $user = $this->getCurrentUser();
        $material = $this->getScratchMaterialDao()->update(
            $id,
            array(
                'status' => 'published',
                'fromUserId' => $user['id'],
            )
        );

        $this->getLogService()->info(
            'scratch_material',
            'publish',
            sprintf('发布 Scratch 素材《%s》(#%s)', $material['title'], $material['id']),
            array('material' => $material)
        );

        return $material;
    }

    public function closeMaterial($id)
    {
        $user = $this->getCurrentUser();
        $material = $this->getScratchMaterialDao()->update(
            $id,
            array(
                'status' => 'closed',
                'updatedUserId' => $user['id'],
            )
        );

        $this->getLogService()->info(
            'scratch_material',
            'close',
            sprintf('取消发布 Scratch 素材《%s》(#%s)', $material['title'], $material['id']),
            array('material' => $material)
        );

        return $material;
    }

    /**
     * @param /materialId $id
     *
     * @throws \Codeages\Biz\Framework\Service\Exception\AccessDeniedException
     * @throws \Exception
     *
     * @return bool
     */
    public function deleteMaterial($id)
    {
        $material = $this->getScratchMaterialDao()->get($id);

        if ('published' == $material['status']) {
            throw $this->createAccessDeniedException('Deleting published Course is not allowed');
        }

        try {
            $this->beginTransaction();

            $this->getScratchMaterialDao()->delete($id);
            $this->getFileService()->deleteFileByUri($material['fileUri']);

            $this->getLogService()->info(
                'scratch_material',
                'delete',
                sprintf('删除 Scratch 素材《%s》(#%s)', $material['title'], $material['id']),
                array('material' => $material)
            );

            $this->commit();

            return true;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function searchMaterials(array $conditions, $orderBy, $start, $limit, array $columns = array())
    {
        $preparedCondtions = $this->prepareConditions($conditions);

        return $this->getScratchMaterialDao()->search($preparedCondtions, $orderBy, $start, $limit, $columns);
    }

    public function searchMaterialCount(array $conditions)
    {
        $preparedCondtions = $this->prepareConditions($conditions);

        return $this->getScratchMaterialDao()->count($preparedCondtions);
    }

    public function findMaterialsByIds(array $ids)
    {
        return $this->getScratchMaterialDao()->findByIds($ids);
    }

    public function findMaterialsByType($type)
    {
        return $this->getScratchMaterialDao()->findByType($type);
    }

    public function exchangeMaterial($order)
    {
        $result = array('status' => 'danger', 'message' => '兑换失败');
        $material = $this->getMaterial($order['productId']);
        $account = $this->getAccountService()->getAccountByUserId($order['userId']);

        if (empty($material)) {
            return array('status' => 'danger', 'message' => '未找到素材，兑换失败');
        }

        if ('closed' == $material['status']) {
            return array('status' => 'danger', 'message' => '素材已下架，兑换失败');
        }

        if (empty($order['userId'])) {
            return array('status' => 'danger', 'message' => '用户未登录');
        }

        if (empty($account)) {
            $account = $this->getAccountService()->createAccount(array('userId' => $order['userId']));
        }

        if ($account['balance'] < $material['price']) {
            return array('status' => 'danger', 'message' => '积分余额不足，兑换失败');
        }

        $mallProductOrder = $this->getMallProductOrderService()->searchProductOrders(
            array(
                'productId' => $order['productId'],
                'userId' => $order['userId'],
            ),
            array('createdTime' => 'DESC'),
            0,
            1
        );
        if (!empty($mallProductOrder)) {
            return array('status' => 'danger', 'message' => '素材已兑换，不能多次兑换');
        }

        if ($account['balance'] >= $material['price']) {
            $order['title'] = $material['title'];
            $order['price'] = $material['price'];
            $order['status'] = 'created';
            $order['type'] = 'scratch';
            $order = $this->getMallProductOrderService()->createProductOrder($order);
            $flow = array(
                'userId' => $order['userId'],
                'type' => 'outflow',
                'amount' => $order['price'],
                'way' => 'exchange_scratch',
                'targetId' => $material['id'],
                'targetType' => 'scratch',
                'operator' => $order['userId'],
            );
            $this->getAccountFlowService()->createAccountFlow($flow);
            $this->getAccountService()->waveDownBalance($account['id'], $order['price']);
            $result = array('status' => 'success', 'message' => '兑换成功');
        }

        return $result;
    }

    protected function checkRequiredFields($fields)
    {
        if (!ArrayToolkit::requireds($fields, array('title', 'type', 'fileUri'))) {
            throw new InvalidArgumentException('Lack of required fields');
        }
    }

    protected function partsFields($fields)
    {
        $fields = ArrayToolkit::parts(
            $fields,
            array(
                'title',
                'categoryId',
                'type',
                'price',
                'fileUri',
                'status',
            )
        );

        return $fields;
    }

    protected function prepareConditions($conditions)
    {
        $conditions = array_filter(
            $conditions,
            function ($value) {
                if (is_numeric($value)) {
                    return true;
                }

                return !empty($value);
            }
        );

        if (!empty($conditions['creatorName'])) {
            $user = $this->getUserService()->getUserByNickname($conditions['creatorName']);
            $conditions['createdUserId'] = $user ? $user['id'] : -1;
        }

        if (isset($conditions['categoryId'])) {
            $conditions['categoryIds'] = array();
            if (!empty($conditions['categoryId'])) {
                $childrenIds = $this->getCategoryService()->findCategoryChildrenIds($conditions['categoryId']);
                $conditions['categoryIds'] = array_merge(array($conditions['categoryId']), $childrenIds);
            }
            unset($conditions['categoryId']);
        }

        return $conditions;
    }

    /**
     * @return ScratchMaterialDao
     */
    protected function getScratchMaterialDao()
    {
        return $this->biz->dao('Scratch:ScratchMaterialDao');
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->biz->service('System:LogService');
    }

    /**
     * @return FileService
     */
    protected function getFileService()
    {
        return $this->createService('Content:FileService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    /**
     * @return CategoryService
     */
    protected function getCategoryService()
    {
        return $this->createService('Taxonomy:CategoryService');
    }

    /**
     * @return AccountService
     */
    protected function getAccountService()
    {
        return $this->createService('RewardPoint:AccountService');
    }

    /**
     * @return ProductOrderService
     */
    protected function getMallProductOrderService()
    {
        return $this->createService('MallPlugin:Mall:ProductOrderService');
    }

    /**
     * @return AccountFlowService
     */
    protected function getAccountFlowService()
    {
        return $this->createService('RewardPoint:AccountFlowService');
    }
}
