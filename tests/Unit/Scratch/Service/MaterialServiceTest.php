<?php

namespace Tests\Unit\Scratch\Service;

use Biz\BaseTestCase;
use Biz\Content\Service\FileService;
use Biz\Scratch\Service\MaterialService;

class MaterialServiceTest extends BaseTestCase
{
    public function testGetMaterial()
    {
        $material = $this->createMaterial('新素材！');
        $material = $this->getMaterialService()->getMaterial($material['id']);
        $this->assertEquals('新素材！', $material['title']);
        $this->assertEquals('role', $material['type']);
    }

    public function testCreateMaterial()
    {
        $material = $this->createMaterial('新素材！');
        $this->assertEquals('新素材！', $material['title']);
        $this->assertEquals('role', $material['type']);
    }

    public function testUpdateMaterial()
    {
        $newMaterial = $this->createMaterial('新素材！');
        $newMaterial['title'] = '新素材2！';
        $material = $this->getMaterialService()->updateMaterial($newMaterial['id'], $newMaterial);
        $this->assertEquals('新素材2！', $material['title']);
    }

    public function testPublishMaterial()
    {
        $newMaterial = $this->createMaterial('新素材！');
        $material = $this->getMaterialService()->publishMaterial($newMaterial['id']);

        $this->assertEquals('published', $material['status']);
    }

    public function testCloseMaterial()
    {
        $newMaterial = $this->createMaterial('新素材！');
        $material = $this->getMaterialService()->closeMaterial($newMaterial['id']);

        $this->assertEquals('closed', $material['status']);
    }

    public function testDeleteMaterial()
    {
        $newMaterial = $this->createMaterial('新素材！');
        $material = $this->getMaterialService()->deleteMaterial($newMaterial['id']);
        $this->assertTrue($material);
        $material = $this->getMaterialService()->getMaterial($newMaterial['id']);
        $this->assertEmpty($material);
    }

    public function testSearchMaterials()
    {
        $newMaterial1 = $this->createMaterial('新素材1！');
        $newMaterial2 = $this->createMaterial('新素材2！');
        $this->getMaterialService()->publishMaterial($newMaterial1['id']);

        $materials = $this->getMaterialService()->searchMaterials(
            array(),
            array('createdTime' => 'DESC'),
            0,
            PHP_INT_MAX
        );
        $this->assertEquals(2, count($materials));
        $this->assertEquals($newMaterial2['id'], $materials[1]['id']);

        $materials = $this->getMaterialService()->searchMaterials(
            array('status' => 'published'),
            array('createdTime' => 'DESC'),
            0,
            PHP_INT_MAX
        );
        $this->assertEquals(1, count($materials));
        $this->assertEquals($newMaterial1['id'], $materials[0]['id']);
    }

    public function testSearchMaterialCount()
    {
        $newMaterial1 = $this->createMaterial('新素材1！');
        $this->createMaterial('新素材2！');
        $this->getMaterialService()->publishMaterial($newMaterial1['id']);

        $count = $this->getMaterialService()->searchMaterialCount(array());
        $this->assertEquals(2, $count);

        $count = $this->getMaterialService()->searchMaterialCount(array('status' => 'published'));
        $this->assertEquals(1, $count);
    }

    protected function createMaterial($title)
    {
        $fields = array(
            'title' => $title,
            'type' => 'role',
            'fileUri' => 'public://tmp/2018/09-10/154250a3455e617273.png',
        );

        return $this->getMaterialService()->createMaterial($fields);
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
    protected function getMaterialService()
    {
        return $this->createService('Scratch:MaterialService');
    }
}
