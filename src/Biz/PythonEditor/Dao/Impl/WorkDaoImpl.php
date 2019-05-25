<?php

namespace Biz\PythonEditor\Dao\Impl;

use Biz\PythonEditor\Dao\WorkDao;
use Codeages\Biz\Framework\Dao\DynamicQueryBuilder;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class WorkDaoImpl extends GeneralDaoImpl implements WorkDao
{
    protected $table = 'python_work';

    public function findByIds(array $ids)
    {
        return $this->findInField('id', $ids);
    }

    public function getWorkByProjectId($userId, $projectId)
    {
        return $this->getByFields(
            array(
                'userId' => $userId,
                'projectId' => $projectId,
            )
        );
    }

    public function addHits($id)
    {
        $id = (int) $id;
        $sql = "UPDATE {$this->table} set hits = hits + 1,hotSeq = hits * 0.3 + upsNum * 0.7 WHERE id = {$id};";

        return $this->db()->exec($sql);
    }

    public function addUpsNum($id)
    {
        $id = (int) $id;
        $sql = "UPDATE {$this->table} set upsNum = upsNum + 1,hotSeq = hits * 0.3 + upsNum * 0.7 WHERE id = {$id};";

        return $this->db()->exec($sql);
    }

    public function subtractUpsNum($id)
    {
        $id = (int) $id;
        $sql = "UPDATE {$this->table} set upsNum = upsNum - 1,hotSeq = hits * 0.3 + upsNum * 0.7 WHERE id = {$id};";

        return $this->db()->exec($sql);
    }

    /**
     * @return array
     */
    public function declares()
    {
        return array(
            'timestamps' => array('createdTime', 'updatedTime'),
            'orderbys' => array('createdTime', 'publishTime', 'recommendedSeq', 'hotSeq'),
            'conditions' => array(
                'id = :id',
                'title LIKE :titleLike',
                'userId = :userId',
                'projectId = :projectId',
                'status = :status',
                'recommended = :recommended',
                'publishTime >= :publishStartTime',
                'publishTime <= :publishEndTime',
                'createdTime >= :createdStartTime',
                'createdTime <= :createdEndTime',
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
        if (!empty($conditions['type']) && 'title' == $conditions['type']) {
            if (isset($conditions['keyword'])) {
                $conditions['titleLike'] = "%{$conditions['keyword']}%";
            }
        }

        if (!empty($conditions['datePicker']) && 'publishDate' == $conditions['datePicker']) {
            if (isset($conditions['startDate'])) {
                $conditions['publishStartTime'] = strtotime($conditions['startDate']);
            }

            if (isset($conditions['endDate'])) {
                $conditions['publishEndTime'] = strtotime($conditions['endDate']);
            }
        }

        if (!empty($conditions['datePicker']) && 'createdDate' == $conditions['datePicker']) {
            if (isset($conditions['startDate'])) {
                $conditions['createdStartTime'] = strtotime($conditions['startDate']);
            }

            if (isset($conditions['endDate'])) {
                $conditions['createdEndTime'] = strtotime($conditions['endDate']);
            }
        }

        return parent::createQueryBuilder($conditions);
    }
}
