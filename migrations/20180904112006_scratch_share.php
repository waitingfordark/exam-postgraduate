<?php

use Phpmig\Migration\Migration;

class ScratchShare extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec("
            create table if not exists  `scratch_share` ( 
              `id` int(10) unsigned not null auto_increment comment '分享ID',
              `nickname` varchar(62) not null default '' comment '分享人名称', 
              `title` varchar(256) not null default '' comment '项目标题',
              `summary` varchar(512) not null default '' comment '项目描述',
              `usageText` varchar(512) not null default '' comment '操作方法',
              `upsNum` int(10) not null default 0 comment '点赞数量',
              `hits` int(10) not null default 0 comment '浏览数量',
              `createdTime` int(10) unsigned not null default 0 comment '创建时间',
              `updatedTime` int(10) unsigned not null default 0 comment '最后更新时间',
              primary key (`id`)
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
        $connection->exec('drop table if exists `scratch_share`;');
    }
}
