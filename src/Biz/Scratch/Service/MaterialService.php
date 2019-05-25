<?php

namespace Biz\Scratch\Service;

interface MaterialService
{
    public function getMaterial($id);

    public function createMaterial(array $material);

    public function updateMaterial($id, array $fields);

    public function publishMaterial($id);

    public function closeMaterial($id);

    public function deleteMaterial($id);

    public function searchMaterials(array $conditions, $orderBy, $start, $limit, array $columns = array());

    public function searchMaterialCount(array $conditions);

    public function findMaterialsByIds(array $ids);

    public function findMaterialsByType($type);

    public function exchangeMaterial($order);
}
