<?php

use Phpmig\Migration\Migration;

class ScratchMaterial extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("
            create table if not exists `scratch_material` (
              `id` int(10) unsigned not null auto_increment comment '素材ID',
              `title` varchar(255) not null default '' comment '素材标题',
              `categoryId` int(10) unsigned not null default 0 comment '分类ID',
              `type` varchar(32) not null default '' comment '素材类型',
              `fileUri` varchar(255) not null default '' comment '素材文件URI',
              `price` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '兑换价格（积分）',
              `status` varchar(32) not null default 'draft' comment 'draft, published, closed',
              `fromUserId` int(10) unsigned not null default 0 comment '发布人',
              `createdUserId` int(10) unsigned not null default 0 comment '创建人',
              `createdTime` int(10) unsigned not null default 0 comment '创建时间',
              `updatedUserId` int(10) unsigned not null default 0 comment '更新人',
              `updatedTime` int(10) unsigned not null default 0 comment '最后更新时间',
              primary key (`id`)
            ) engine=InnoDB default charset=utf8;
        ");

        $container['db']->exec("INSERT INTO `category_group` (`id`, `code`, `name`, `depth`) VALUES (NULL, 'scratch_material', 'Scratch 素材分类', '3');");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];
        $connection->exec('drop table if exists `scratch_material`;');
    }
}
