<?php

namespace Biz\Scratch\Dao\Impl;

use Biz\Scratch\Dao\ScratchMaterialDao;
use Codeages\Biz\Framework\Dao\DynamicQueryBuilder;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class ScratchMaterialDaoImpl extends GeneralDaoImpl implements ScratchMaterialDao
{
    protected $table = 'scratch_material';

    public function findByIds(array $ids)
    {
        return $this->findInField('id', $ids);
    }

    public function findByType($type)
    {
        return $this->findByFields(array('type' => $type));
    }

    /**
     * @return array
     */
    public function declares()
    {
        return array(
            'timestamps' => array('createdTime', 'updatedTime'),
            'orderbys' => array('createdTime'),
            'conditions' => array(
                'id = :id',
                'id IN (:ids)',
                'title LIKE :titleLike',
                'categoryId = : categoryId',
                'categoryId IN (:categoryIds)',
                'type = :type',
                'fileId = :fileId',
                'fileId IN (:fileIds)',
                'status = :status',
            ),
        );
    }

    /**
     * @param /conditions $conditions
     *
     * @return DynamicQueryBuilder
     */
    protected function createQueryBuilder($conditions)
    {
        if (isset($conditions['titleLike'])) {
            $conditions['titleLike'] = "%{$conditions['titleLike']}%";
        }

        if (isset($conditions['title'])) {
            $conditions['titleLike'] = "%{$conditions['title']}%";
            unset($conditions['title']);
        }

        return parent::createQueryBuilder($conditions);
    }
}
