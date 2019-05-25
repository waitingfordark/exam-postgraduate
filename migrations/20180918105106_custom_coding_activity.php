<?php

use Phpmig\Migration\Migration;

class CustomCodingActivity extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $biz = $this->getContainer();
        $db = $biz['db'];
        $db->exec('
        CREATE TABLE IF NOT EXISTS `activity_coding` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `createdTime` int(10) NOT NULL,
          `createdUserId` int(11) NOT NULL,
          `updatedTime` int(11) DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
        ');

        $db->exec("ALTER TABLE `course_task_result` ADD COLUMN `scratchProjectId` int(10) NOT NULL DEFAULT 0 COMMENT 'scratchProjectId';");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $biz = $this->getContainer();
        $db = $biz['db'];
        $db->exec('DROP TABLE IF EXISTS `doc_activity`');
        $db->exec('ALTER TABLE `course_task_result` DROP COLUMN `scratchProjectId`;');
    }
}
