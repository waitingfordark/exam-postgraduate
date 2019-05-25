<?php

use Phpmig\Migration\Migration;

class ScratchProject extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec("
            create table if not exists `scratch_project` (
             `id` int(10) unsigned not null auto_increment comment '项目ID',
             `userId` int(10) unsigned not null default 0 comment '项目所属用户ID', 
             `fileUri` varchar(256) not null default '' comment '上传文件URI',
             `shareId` int(10) unsigned not null default 0 comment 'scratch_share关联ID',
             `createdTime` int(10) unsigned not null default 0 comment '创建时间',
             `updatedTime` int(10) unsigned not null default 0 comment '最后更新时间',
             primary key (`id`),
             key `userId` (`userId`)
            ) engine=InnoDB charset=utf8;
        ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];
        $connection->exec('drop table if exists `scratch_project`;');
    }
}
