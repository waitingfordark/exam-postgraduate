<?php

namespace Biz\Course\Service\Impl;

use Biz\BaseService;
use Biz\Course\Dao\CourseDao;
use Biz\Course\Dao\ReviewDao;
use Biz\Course\Dao\ThreadDao;
use Biz\Course\Dao\FavoriteDao;
use Biz\Course\Dao\CourseSetDao;
use Biz\Course\Dao\CourseNoteDao;
use Biz\Course\Dao\ThreadPostDao;
use Biz\Task\Service\TaskService;
use AppBundle\Common\ArrayToolkit;
use Biz\Course\Dao\CourseMemberDao;
use Biz\User\Service\StatusService;
use Biz\Course\Dao\CourseChapterDao;
use Biz\Course\Dao\CourseNoteLikeDao;
use Biz\System\Service\SettingService;
use Biz\IM\Service\ConversationService;
use Biz\Course\Service\CourseDeleteService;
use Biz\Testpaper\Service\TestpaperService;
use Biz\Announcement\Service\AnnouncementService;

class CourseDeleteServiceImpl extends BaseService implements CourseDeleteService
{
    public function deleteCourseSet($courseSetId)
    {
        try {
            $this->beginTransaction();

            $this->deleteCourseSetCourse($courseSetId);

            $this->deleteTestpaper($courseSetId);

            $this->deleteQuestion($courseSetId);

            $this->getCourseSetDao()->delete($courseSetId);

            $this->commit();

            return $courseSetId;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    protected function deleteCourseSetCourse($courseSetId)
    {
        $courses = $this->getCourseDao()->findByCourseSetIds(array($courseSetId));
        if (empty($courses)) {
            return;
        }

        foreach ($courses as $course) {
            $this->deleteCourse($course['id']);
        }
    }

    protected function deleteQuestion($courseSetId)
    {
        $questions = $this->getQuestionDao()->findQuestionsByCourseSetId($courseSetId);
        if (empty($questions)) {
            return;
        }

        $this->getQuestionDao()->deleteByCourseSetId($courseSetId);

    }


    protected function deleteTestpaper($courseSetId)
    {
        $testpapers = $this->getTestpaperService()->searchTestpapers(array('courseSetId' => $courseSetId), array(), 0, PHP_INT_MAX);
        if (empty($testpapers)) {
            return;
        }

        $testpaperIds = ArrayToolkit::column($testpapers, 'id');
        $this->getTestpaperService()->deleteTestpapers($testpaperIds);
    }

    public function deleteCourse($courseId)
    {
        try {
            $this->beginTransaction();

            $this->deleteCourseChapter($courseId);

            $this->deleteTask($courseId);
            $this->deleteTaskResult($courseId);

            $this->deleteCourseMember($courseId);

            $this->getCourseDao()->delete($courseId);

            $this->commit();

            return $courseId;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    protected function deleteCourseChapter($courseId)
    {
        $this->getChapterDao()->deleteChaptersByCourseId($courseId);
    }

    protected function deleteTask($courseId)
    {
        $tasks = $this->getTaskService()->findTasksByCourseId($courseId);
        if (!empty($tasks)) {
            $this->getTaskDao()->deleteByCourseId($courseId);
            foreach ($tasks as $task) {
                //delete activity
                $this->getActivityService()->deleteActivity($task['activityId']);
                // $this->deleteJob($task);
            }
        }
    }

    protected function deleteTaskResult($courseId)
    {
        $this->getTaskResultDao()->deleteByCourseId($courseId);
    }

    protected function deleteCourseMember($courseId)
    {
        $this->getMemberDao()->deleteByCourseId($courseId);
    }


    /**
     * @return CourseSetDao
     */
    protected function getCourseSetDao()
    {
        return $this->createDao('Course:CourseSetDao');
    }

    /**
     * @return CourseDao
     */
    protected function getCourseDao()
    {
        return $this->createDao('Course:CourseDao');
    }

    protected function getTaskDao()
    {
        return $this->createDao('Task:TaskDao');
    }

    /**
     * @return MaterialService
     */
    protected function getMaterialService()
    {
        return $this->createService('Course:MaterialService');
    }

    /**
     * @return TestpaperService
     */
    protected function getTestpaperService()
    {
        return $this->createService('Testpaper:TestpaperService');
    }

    /**
     * @return CourseChapterDao
     */
    protected function getChapterDao()
    {
        return $this->createDao('Course:CourseChapterDao');
    }

    /**
     * @return CourseMemberDao
     */
    protected function getMemberDao()
    {
        return $this->createDao('Course:CourseMemberDao');
    }

    protected function getTaskResultDao()
    {
        return $this->createDao('Task:TaskResultDao');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    protected function getQuestionDao()
    {
        return $this->createDao('Question:QuestionDao');
    }

    /**
     * @return CourseNoteDao
     */
    protected function getNoteDao()
    {
        return $this->createDao('Course:CourseNoteDao');
    }

    /**
     * @return CourseNoteLikeDao
     */
    protected function getNoteLikeDao()
    {
        return $this->createDao('Course:CourseNoteLikeDao');
    }

    /**
     * @return ThreadDao
     */
    protected function getThreadDao()
    {
        return $this->createDao('Course:ThreadDao');
    }

    /**
     * @return ThreadPostDao
     */
    protected function getThreadPostDao()
    {
        return $this->createDao('Course:ThreadPostDao');
    }

    /**
     * @return ReviewDao
     */
    protected function getReviewDao()
    {
        return $this->createDao('Course:ReviewDao');
    }


    /**
     * @return FavoriteDao
     */
    protected function getFavoriteDao()
    {
        return $this->createDao('Course:FavoriteDao');
    }

    /**
     * @return AnnouncementService
     */
    protected function getAnnouncementService()
    {
        return $this->createService('Announcement:AnnouncementService');
    }

    /**
     * @return ConversationService
     */
    protected function getConversationService()
    {
        return $this->createService('IM:ConversationService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    protected function getSchedulerService()
    {
        return $this->createService('Scheduler:SchedulerService');
    }

    /**
     * @return ActivityService
     */
    public function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getUploadFileService()
    {
        return $this->createService('File:UploadFileService');
    }
}
