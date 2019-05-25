<?php

use Phpmig\Migration\Migration;

class CustomLeaveMessage extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec("
            create table if not exists `leave_message` (
             `id` int(10) unsigned not null auto_increment comment 'ID',
             `name` varchar(32) not null default '' comment 'name', 
             `email` varchar(256) not null default '' comment 'email',
             `phone` varchar(32) not null default '' comment 'phone',
              `content` text COMMENT 'content',
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
        $connection->exec('drop table if exists `leave_message`;');
    }
}
