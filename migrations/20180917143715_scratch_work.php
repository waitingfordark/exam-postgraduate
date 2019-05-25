<?php

use Phpmig\Migration\Migration;

class ScratchWork extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];

        $connection->exec(
            "
            create table if not exists `scratch_work` ( 
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '作品ID' ,
                `title` VARCHAR(256) NOT NULL DEFAULT '' COMMENT '作品名' , 
                `smallPicture` VARCHAR(256) NOT NULL DEFAULT '' COMMENT '小图' , 
                `middlePicture` VARCHAR(256) NOT NULL DEFAULT '' COMMENT '中图' , 
                `largePicture` VARCHAR(256) NOT NULL DEFAULT '' COMMENT '大图' , 
                `userId` INT(10) UNSIGNED NOT NULL COMMENT '创作人ID' , 
                `status` VARCHAR(32) NOT NULL DEFAULT 'draft' COMMENT '状态draft,verify, published' , 
                `hits` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '点击量' , 
                `projectId` INT(10) UNSIGNED NOT NULL COMMENT '项目ID' , 
                `upsNum` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '点赞量' , 
                `hotSeq` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '热度排序' , 
                `recommended` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否为推荐作品' , 
                `recommendedSeq` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '推荐序号' , 
                `recommendedTime` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '推荐时间' , 
                `createdTime` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间' , 
                `updatedTime` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间' , 
                `publishTime` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '发布时间' , 
                `verifyTime` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '审核时间' , 
                `verifyUserId` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '审核人' , 
                PRIMARY KEY (`id`), 
                INDEX (`status`),
                INDEX (`userId`,`status`)
            ) ENGINE = InnoDB charset=utf8;
        "
        );
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $connection = $biz['db'];
        $connection->exec('drop table if exists `scratch_work`;');
    }
}
