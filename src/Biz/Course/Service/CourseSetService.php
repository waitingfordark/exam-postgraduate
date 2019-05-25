<?php

namespace Biz\Course\Service;

use Codeages\Biz\Framework\Service\Exception\AccessDeniedException;
use Biz\System\Annotation\Log;

interface CourseSetService
{
    const NONE_SERIALIZE_MODE = 'none';
    const SERIALIZE_SERIALIZE_MODE = 'serialized';
    const FINISH_SERIALIZE_MODE = 'finished';

    const DRAFT_STATUS = 'draft';
    const PUBLISH_STATUS = 'published';
    const CLOSE_STATUS = 'closed';

    const NORMAL_TYPE = 'normal';
    const LIVE_TYPE = 'live';

    /**
     * collect course set.
     *
     * @param  $id
     *
     * @throws AccessDeniedException
     *
     * @return bool
     */
    public function favorite($id);

    /**
     * cancel collected course set.
     *
     * @param  $id
     *
     * @throws AccessDeniedException
     *
     * @return bool
     */
    public function unfavorite($id);

    /**
     * @param int $userId
     * @param int $courseSetId
     *
     * @return bool
     */
    public function isUserFavorite($userId, $courseSetId);

    public function tryManageCourseSet($id);

    public function hasCourseSetManageRole($courseSetId = 0);

    /**
     * @param int $userId
     *
     * @return int
     */
    public function countUserLearnCourseSets($userId);

    /**
     * @param int $userId
     * @param int $start
     * @param int $limit
     *
     * @return array[]
     */
    public function searchUserLearnCourseSets($userId, $start, $limit);

    /**
     * @param int   $userId
     * @param array $conditions
     *
     * @return int
     */
    public function countUserTeachingCourseSets($userId, array $conditions);

    /**
     * @param int   $userId
     * @param array $conditions
     * @param int   $start
     * @param int   $limit
     *
     * @return array[]
     */
    public function searchUserTeachingCourseSets($userId, array $conditions, $start, $limit);

    /**
     * @param int[] $courseIds
     *
     * @return array[]
     */
    public function findCourseSetsByCourseIds(array $courseIds);

    /**
     * @param array $ids
     *
     * @return array[]
     */
    public function findCourseSetsByIds(array $ids);

    /**
     * @param array        $conditions
     * @param array|string $orderBys
     * @param int          $start
     * @param int          $limit
     *
     * @return array[]
     */
    public function searchCourseSets(array $conditions, $orderBys, $start, $limit);

    /**
     * @param array $conditions
     *
     * @return int
     */
    public function countCourseSets(array $conditions);

    public function getCourseSet($id);

    /**
     * @param $courseSet
     *
     * @return mixed
     * @Log(module="course",action="create")
     */
    public function createCourseSet($courseSet);

    /**
     * 复制课程到班级.
     *
     * @param int $classroomId
     * @param int $courseSetId 要复制的课程
     * @param int $courseId    要复制的教学计划
     *
     * @return mixed
     */
    public function copyCourseSet($classroomId, $courseSetId, $courseId);

    /**
     * @param $id
     * @param $fields
     *
     * @return mixed
     * @Log(module="course",action="update",param="id")
     */
    public function updateCourseSet($id, $fields);

    /**
     * 更新课程营销设置.
     *
     * @param  $id
     * @param  $fields
     *
     * @return mixed
     */
    public function updateCourseSetMarketing($id, $fields);

    public function updateCourseSetTeacherIds($id, $teacherIds);

    /**
     * @param $id
     * @param $fields
     *
     * @return mixed
     * @Log(module="course",action="update_picture",funcName="getCourseSet",param="id")
     */
    public function changeCourseSetCover($id, $fields);

    /**
     * @param $id
     *
     * @return mixed
     * @Log(module="course",action="delete")
     */
    public function deleteCourseSet($id);

    /**
     * @param int  $userId
     * @param bool $onlyPublished 是否只需要发布的课程
     *
     * @return array[]
     */
    public function findTeachingCourseSetsByUserId($userId, $onlyPublished = true);

    /**
     * @param int $userId
     *
     * @return array[]
     */
    public function findLearnCourseSetsByUserId($userId);

    /**
     * @param array $ids
     *
     * @return array[]
     */
    public function findPublicCourseSetsByIds(array $ids);

    /**
     * @param int $userId
     *
     * @return int
     */
    public function countUserFavorites($userId);

    /**
     * @param int $userId
     * @param int $start
     * @param int $limit
     *
     * @return array[]
     */
    public function searchUserFavorites($userId, $start, $limit);

    /**
     * @param array $conditions
     * @param array $orderBys
     * @param int   $start
     * @param int   $limit
     *
     * @return array[]
     */
    public function searchFavorites(array $conditions, array $orderBys, $start, $limit);

    /**
     * 更新课程统计属性.
     *
     * 如: 学员数、笔记数、评价数量
     *
     * @param  $id
     * @param array $fields
     *
     * @return mixed
     */
    public function updateCourseSetStatistics($id, array $fields);

    /**
     * @param $id
     *
     * @return mixed
     * @Log(module="course",action="publish",funcName="getCourseSet")
     */
    public function publishCourseSet($id);

    /**
     * @param $id
     *
     * @return mixed
     * @Log(module="course",action="close",funcName="getCourseSet")
     */
    public function closeCourseSet($id);

    public function findCourseSetsByParentIdAndLocked($parentId, $locked);

    /**
     * @param $id
     * @param $number
     *
     * @return mixed
     * @Log(module="course",action="recommend",funcName="getCourseSet",param="id")
     */
    public function recommendCourse($id, $number);

    /**
     * @param $id
     *
     * @return mixed
     * @Log(module="course",action="cancel_recommend",funcName="getCourseSet")
     */
    public function cancelRecommendCourse($id);

    /**
     * 根据查询条件随机取指定个数的课程.
     *
     * @param  $conditions
     * @param int $num
     *
     * @return mixed
     */
    public function findRandomCourseSets($conditions, $num = 3);

    /**
     * 返回课程的营收额.
     *
     * @param array $ids
     *
     * @return array[]
     */
    public function findCourseSetIncomesByCourseSetIds(array $courseSetIds);

    public function analysisCourseSetDataByTime($startTime, $endTime);

    public function batchUpdateOrg($courseSetIds, $orgCode);

    public function updateCourseSetMinAndMaxPublishedCoursePrice($courseSetId);

    /**
     * 计划发布，关闭，删除 均需要计算 默认计划ID.
     *
     * @param $courseSetId
     *
     * @return mixed
     */
    public function updateCourseSetDefaultCourseId($courseSetId);

    public function unlockCourseSet($id, $shouldClose = false);

    public function updateMaxRate($id, $maxRate);

    public function hitCourseSet($id);

    public function findRelatedCourseSetsByCourseSetId($courseSetId, $count);

    /**
     * 克隆一个课程
     *
     * @param $courseSetId
     *
     * @return mixed
     */
    public function cloneCourseSet($courseSetId, $params);

    public function refreshHotSeq();
}
