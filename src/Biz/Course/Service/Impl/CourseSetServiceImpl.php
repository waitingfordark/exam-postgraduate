<?php

namespace Biz\Course\Service\Impl;

use Biz\AppLoggerConstant;
use Biz\BaseService;
use Biz\Course\Dao\CourseDao;
use Biz\Course\Dao\FavoriteDao;
use Biz\Course\Dao\CourseSetDao;
use Biz\User\Service\UserService;
use AppBundle\Common\ArrayToolkit;
use Biz\System\Service\LogService;
use Biz\Content\Service\FileService;
use Biz\Taxonomy\Service\TagService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\MemberService;
use Biz\Course\Service\ReviewService;
use Biz\Course\Service\MaterialService;
use Codeages\Biz\Framework\Event\Event;
use Biz\Course\Service\CourseSetService;
use Biz\Course\Service\CourseNoteService;
use Biz\Classroom\Service\ClassroomService;
use Biz\Course\Service\CourseDeleteService;
use Biz\User\UserException;

class CourseSetServiceImpl extends BaseService implements CourseSetService
{
    public function findCourseSetsByParentIdAndLocked($parentId, $locked)
    {
        return $this->getCourseSetDao()->findCourseSetsByParentIdAndLocked($parentId, $locked);
    }

    public function tryManageCourseSet($id)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('user not login');
        }

        $courseSet = $this->getCourseSetDao()->get($id);

        if (empty($courseSet)) {
            throw $this->createNotFoundException("CourseSet#{$id} Not Found");
        }

        if (!$this->hasCourseSetManageRole($id)) {
            throw $this->createAccessDeniedException('can not access');
        }

        return $courseSet;
    }

    public function hasCourseSetManageRole($courseSetId = 0)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            return false;
        }

        if ($this->hasAdminRole()) {
            return true;
        }

        if (empty($courseSetId)) {
            return $user->isTeacher();
        }

        $courseSet = $this->getCourseSetDao()->get($courseSetId);
        if (empty($courseSet)) {
            return false;
        }

        if ($courseSet['creator'] == $user->getId()) {
            return true;
        }

        $teachers = $this->getCourseMemberService()->findCourseSetTeachers($courseSetId);
        $teacherIds = ArrayToolkit::column($teachers, 'userId');

        $courses = $this->getCourseService()->findCoursesByCourseSetId($courseSetId);
        foreach ($courses as $course) {
            if (in_array($user->getId(), $teacherIds)) {
                $canManageRole = $this->getCourseService()->hasCourseManagerRole($course['id']);
                if ($canManageRole) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function hasAdminRole()
    {
        $user = $this->getCurrentUser();

        return $user->hasPermission('admin_course_content_manage');
    }

    public function searchCourseSets(array $conditions, $orderBys, $start, $limit)
    {
        $orderBys = $this->getOrderBys($orderBys);
        $preparedCondtions = $this->prepareConditions($conditions);

        return $this->getCourseSetDao()->search($preparedCondtions, $orderBys, $start, $limit);
    }

    public function countCourseSets(array $conditions)
    {
        $conditions = $this->prepareConditions($conditions);

        return $this->getCourseSetDao()->count($conditions);
    }

    // Refactor: countLearnCourseSets
    public function countUserLearnCourseSets($userId)
    {
        $sets = $this->findLearnCourseSetsByUserId($userId);
        $ids = ArrayToolkit::column($sets, 'id');

        if (empty($ids)) {
            return 0;
        }

        $count = $this->countCourseSets(
            array(
                'ids' => $ids,
                'status' => 'published',
                'excludeTypes' => array('reservation'),
                'parentId' => 0,
            )
        );

        return $count;
    }

    // Refactor: searchLearnCourseSets
    public function searchUserLearnCourseSets($userId, $start, $limit)
    {
        $sets = $this->findLearnCourseSetsByUserId($userId);
        $ids = ArrayToolkit::column($sets, 'id');

        if (empty($ids)) {
            return array();
        }

        //屏蔽预约课程
        return $this->searchCourseSets(
            array(
                'ids' => $ids,
                'status' => 'published',
                'excludeTypes' => array('reservation'),
                'parentId' => 0,
            ),
            array(
                'createdTime' => 'DESC',
            ),
            $start,
            $limit
        );
    }

    // Refactor: countTeachingCourseSets
    public function countUserTeachingCourseSets($userId, array $conditions)
    {
        $members = $this->getCourseMemberService()->findTeacherMembersByUserId($userId);
        $ids = ArrayToolkit::column($members, 'courseSetId');

        if (empty($ids)) {
            return 0;
        }

        $conditions = array_merge($conditions, array('ids' => $ids));
        //屏蔽预约课程
        $conditions['excludeTypes'] = array('reservation');

        return $this->countCourseSets($conditions);
    }

    // Refactor: searchTeachingCourseSets
    public function searchUserTeachingCourseSets($userId, array $conditions, $start, $limit)
    {
        $members = $this->getCourseMemberService()->findTeacherMembersByUserId($userId);
        $ids = ArrayToolkit::column($members, 'courseSetId');

        if (empty($ids)) {
            return array();
        }

        $conditions = array_merge($conditions, array('ids' => $ids));
        //屏蔽预约课程
        $conditions['excludeTypes'] = array('reservation');

        return $this->searchCourseSets($conditions, array('createdTime' => 'DESC'), $start, $limit);
    }

    public function findCourseSetsByCourseIds(array $courseIds)
    {
        $courses = $this->getCourseService()->findCoursesByIds($courseIds);
        $courseSetIds = ArrayToolkit::column($courses, 'courseSetId');

        $sets = $this->findCourseSetsByIds($courseSetIds);

        return $sets;
    }

    public function findCourseSetsByIds(array $ids)
    {
        $courseSets = $this->getCourseSetDao()->findByIds($ids);

        return ArrayToolkit::index($courseSets, 'id');
    }

    public function findCourseSetsLikeTitle($title)
    {
        return $this->getCourseSetDao()->findLikeTitle($title);
    }

    public function getCourseSet($id)
    {
        return $this->getCourseSetDao()->get($id);
    }

    public function createCourseSet($courseSet)
    {
        if (!$this->hasCourseSetManageRole()) {
            throw $this->createAccessDeniedException('You have no access to Course Set Management');
        }

        $created = $this->addCourseSet($courseSet);
        $defaultCourse = $this->addDefaultCourse($courseSet, $created);

        //update courseSet defaultId
        $created = $this->getCourseSetDao()->update($created['id'], array('defaultCourseId' => $defaultCourse['id']));

        return $created;
    }

    public function updateCourseSet($id, $fields)
    {
        if (!ArrayToolkit::requireds($fields, array('title', 'categoryId', 'serializeMode'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }
        if (!in_array($fields['serializeMode'], array('none', 'serialized', 'finished'))) {
            throw $this->createInvalidArgumentException('Invalid Param: serializeMode');
        }

        $courseSet = $this->tryManageCourseSet($id);

        $fields = ArrayToolkit::parts(
            $fields,
            array(
                'title',
                'subtitle',
                'tags',
                'categoryId',
                'serializeMode',
                'smallPicture',
                'middlePicture',
                'largePicture',
                'teacherIds',
                'orgCode',
                'summary',
                'goals',
                'audiences',
            )
        );

        $fields = $this->filterFields($fields);
        $isCoursesSummaryEmpty = $this->getCourseService()->isCourseSetCoursesSummaryEmpty($courseSet['id']);
        if ($isCoursesSummaryEmpty && $courseSet['summary'] != $fields['summary']) {
            $this->updateCourseSummary($courseSet);
        }
        $this->updateCourseSerializeMode($courseSet, $fields);

        $courseSet = $this->getCourseSetDao()->update($courseSet['id'], $fields);

        $this->dispatchEvent('course-set.update', new Event($courseSet));

        return $courseSet;
    }

    protected function updateCourseSerializeMode($courseSet, $fields)
    {
        if (isset($fields['serializeMode']) && $fields['serializeMode'] !== $courseSet['serializeMode']) {
            $courses = $this->getCourseDao()->findByCourseSetIds(array($courseSet['id']));
            foreach ($courses as $course) {
                $this->getCourseService()->updateCourse(
                    $course['id'],
                    array(
                        'serializeMode' => $fields['serializeMode'],
                    )
                );
            }
        }
    }

    protected function updateCourseSummary($courseSet)
    {
        $courses = $this->getCourseService()->findCoursesByCourseSetId($courseSet['id']);
        foreach ($courses as $course) {
            $this->getCourseService()->updateCourse(
                $course['id'],
                array(
                    'summary' => '',
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateCourseSetMarketing($id, $fields)
    {
        $courseSet = $this->tryManageCourseSet($id);
        $oldCourseSet = $courseSet;
        $fields = ArrayToolkit::parts(
            $fields,
            array(
                'discountId',
                'discount',
            )
        );

        $courseSet = $this->getCourseSetDao()->update($courseSet['id'], $fields);

        $this->dispatchEvent(
            'course-set.marketing.update',
            new Event($courseSet, array('oldCourseSet' => $oldCourseSet, 'newCourseSet' => $courseSet))
        );

        return $courseSet;
    }

    public function updateCourseSetTeacherIds($id, $teacherIds)
    {
        $courseSet = $this->tryManageCourseSet($id);
        $courseSet['teacherIds'] = $teacherIds;
        $courseSet = $this->getCourseSetDao()->update($courseSet['id'], $courseSet);
        $this->dispatchEvent('course-set.update', new Event($courseSet));
    }


    public function deleteCourseSet($id)
    {
        $courseSet = $this->tryManageCourseSet($id);
       
        $this->getCourseDeleteService()->deleteCourseSet($courseSet['id']);
    }

    public function findTeachingCourseSetsByUserId($userId, $onlyPublished = true)
    {
        $courses = $this->getCourseService()->findTeachingCoursesByUserId($userId, $onlyPublished);
        $setIds = ArrayToolkit::column($courses, 'courseSetId');

        if ($onlyPublished) {
            return $this->findPublicCourseSetsByIds($setIds);
        } else {
            return $this->findCourseSetsByIds($setIds);
        }
    }

    public function findLearnCourseSetsByUserId($userId)
    {
        $courses = $this->getCourseService()->findLearnCoursesByUserId($userId);
        $setIds = ArrayToolkit::column($courses, 'courseSetId');

        return $this->findPublicCourseSetsByIds($setIds);
    }

    public function findPublicCourseSetsByIds(array $ids)
    {
        if (empty($ids)) {
            return array();
        }

        $conditions = array(
            'ids' => $ids,
            'status' => 'published',
        );
        $count = $this->countCourseSets($conditions);

        return $this->searchCourseSets($conditions, array('createdTime' => 'DESC'), 0, $count);
    }

    public function updateCourseSetStatistics($id, array $fields)
    {
        if (empty($fields)) {
            throw $this->createInvalidArgumentException('Invalid Arguments');
        }

        $updateFields = array();
        foreach ($fields as $field) {
            if ('ratingNum' === $field) {
                $ratingFields = $this->getReviewService()->countRatingByCourseSetId($id);
                $updateFields = array_merge($updateFields, $ratingFields);
            } elseif ('noteNum' === $field) {
                $noteNum = $this->getNoteService()->countCourseNoteByCourseSetId($id);
                $updateFields['noteNum'] = $noteNum;
            } elseif ('studentNum' === $field) {
                $updateFields['studentNum'] = $this->countStudentNumById($id);
            } elseif ('materialNum' === $field) {
                $updateFields['materialNum'] = $this->getCourseMaterialService()->countMaterials(
                    array('courseSetId' => $id, 'source' => 'coursematerial')
                );
            }
        }

        $courseSet = $this->getCourseSetDao()->update($id, $updateFields);
        $this->dispatchEvent('course-set.update', new Event($courseSet));

        return $courseSet;
    }

    public function publishCourseSet($id)
    {
        $courseSet = $this->tryManageCourseSet($id);

        if (empty($courseSet)) {
            throw $this->createNotFoundException('course set not found');
        }

        $publishedCourses = $this->getCourseService()->findPublishedCoursesByCourseSetId($id);

        $courses = $this->getCourseService()->findCoursesByCourseSetId($courseSet['id']);

        $this->beginTransaction();
        try {

            if (empty($publishedCourses)) {
                if (1 === count($courses)) {
                    $this->getCourseService()->publishCourse($courses[0]['id']);
                }
            }

            $courseSet = $this->getCourseSetDao()->update($courseSet['id'], array('status' => 'published'));

            $this->commit();

        } catch (\Exception $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    public function closeCourseSet($id)
    {
        $courseSet = $this->tryManageCourseSet($id);
        if ('published' !== $courseSet['status']) {
            throw $this->createAccessDeniedException('CourseSet has not bean published');
        }


        try {
            $this->beginTransaction();

            if (!empty($classroomRef)) {
                $this->getCourseService()->closeCourse($classroomRef['courseId']);
            }
            $courseSet = $this->getCourseSetDao()->update($courseSet['id'], array('status' => 'closed'));

            $this->commit();
        } catch (\Exception $exception) {
            $this->rollback();
            throw $exception;
        }

    }

    public function countUserFavorites($userId)
    {
        return $this->getFavoriteDao()->countByUserId($userId);
    }

    public function searchUserFavorites($userId, $start, $limit)
    {
        return $this->getFavoriteDao()->searchByUserId($userId, $start, $limit);
    }

    public function searchFavorites(array $conditions, array $orderBys, $start, $limit)
    {
        return $this->getFavoriteDao()->search($conditions, $orderBys, $start, $limit);
    }

    /**
     * 根据排序规则返回排序数组.
     *
     * @param string $order
     *
     * @return array
     */
    protected function getOrderBys($order)
    {
        if (is_array($order)) {
            return $order;
        }

        $typeOrderByMap = array(
            'hitNum' => array('hitNum' => 'DESC'),
            'recommended' => array('recommendedTime' => 'DESC'),
            'rating' => array('rating' => 'DESC'),
            'studentNum' => array('studentNum' => 'DESC'),
            'recommendedSeq' => array('recommendedSeq' => 'ASC', 'recommendedTime' => 'DESC'),
            'hotSeq' => array('hotSeq' => 'DESC', 'studentNum' => 'DESC', 'id' => 'DESC'),
        );
        if (isset($typeOrderByMap[$order])) {
            return $typeOrderByMap[$order];
        } else {
            return array('createdTime' => 'DESC');
        }
    }

    public function findCourseSetIncomesByCourseSetIds(array $courseSetIds)
    {
        return $this->getCourseDao()->findCourseSetIncomesByCourseSetIds($courseSetIds);
    }

    public function batchUpdateOrg($courseSetIds, $orgCode)
    {
        if (!is_array($courseSetIds)) {
            $courseSetIds = array($courseSetIds);
        }

        $fields = $this->fillOrgId(array('orgCode' => $orgCode));

        foreach ($courseSetIds as $courseSetId) {
            $this->getCourseSetDao()->update($courseSetId, $fields);
        }
    }

    public function unlockCourseSet($id, $shouldClose = false)
    {
        $courseSet = $this->tryManageCourseSet($id);

        if (!(bool) $courseSet['locked']) {
            return $courseSet;
        }

        if ($courseSet['parentId'] <= 0 || 0 == $courseSet['locked']) {
            throw $this->createAccessDeniedException('Invalid Operation');
        }
        $courses = $this->getCourseService()->findCoursesByCourseSetId($id);
        try {
            $this->beginTransaction();

            $fields = array('locked' => 0);
            if ($shouldClose) {
                $fields['status'] = 'closed';
            }
            $courseSet = $this->getCourseSetDao()->update($id, $fields);

            $this->getCourseDao()->update($courses[0]['id'], $fields);

            $this->dispatchEvent('course-set.unlock', new Event($courseSet));

            $this->commit();

            $this->getLogService()->info(
                'course',
                'unlock_course',
                "解除班级课程同步《{$courseSet['title']}》(#{$courseSet['id']})"
            );

            return $courseSet;
        } catch (\Exception $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    // Refactor: 函数意图不明显
    public function analysisCourseSetDataByTime($startTime, $endTime)
    {
        return $this->getCourseSetDao()->analysisCourseSetDataByTime($startTime, $endTime);
    }

    public function updateCourseSetMinAndMaxPublishedCoursePrice($courseSetId)
    {
        $courses = $this->getCourseService()->findCoursesByCourseSetId($courseSetId);
        //只有一个计划时，直接同步计划的价格到课程上
        if (1 === count($courses)) {
            $course = array_shift($courses);
            $price = array('minPrice' => $course['price'], 'maxPrice' => $course['price']);
        } else {
            $price = $this->getCourseService()->getMinAndMaxPublishedCoursePriceByCourseSetId($courseSetId);
        }

        return $this->getCourseSetDao()->update(
            $courseSetId,
            array('minCoursePrice' => $price['minPrice'], 'maxCoursePrice' => $price['maxPrice'])
        );
    }

    public function updateCourseSetDefaultCourseId($id)
    {
        $course = $this->getCourseService()->getFirstPublishedCourseByCourseSetId($id);
        //如果计划都尚未发布，则获取第一个创建的
        if (empty($course)) {
            $course = $this->getCourseService()->getFirstCourseByCourseSetId($id);
        }
        if (empty($course)) {
            throw $this->createNotFoundException('No Avaliable Course in CourseSet#{$id}');
        }
        $this->getCourseSetDao()->update($id, array('defaultCourseId' => $course['id']));
    }

    public function updateMaxRate($id, $maxRate)
    {
        $courseSet = $this->getCourseSetDao()->update($id, array('maxRate' => $maxRate));
        $this->dispatchEvent(
            'courseSet.maxRate.update',
            new Event(array('courseSet' => $courseSet, 'maxRate' => $maxRate))
        );

        return $courseSet;
    }

    public function hitCourseSet($id)
    {
        $courseSet = $this->getCourseSet($id);

        if (empty($courseSet)) {
            throw $this->createNotFoundException('course set not found');
        }

        return $this->getCourseSetDao()->wave(array($courseSet['id']), array('hitNum' => 1));
    }

    protected function validateCourseSet($courseSet)
    {
        if (!ArrayToolkit::requireds($courseSet, array('title', 'type'))) {
            throw $this->createInvalidArgumentException('Lack of Required Fields');
        }
        if (!in_array($courseSet['type'], array('normal', 'live', 'liveOpen', 'open'))) {
            throw $this->createInvalidArgumentException('Invalid Param: type');
        }
    }

    protected function prepareConditions($conditions)
    {
        $conditions = array_filter($conditions, function ($value) {
            if (is_numeric($value)) {
                return true;
            }

            return !empty($value);
        });

        if (!empty($conditions['creatorName'])) {
            $user = $this->getUserService()->getUserByNickname($conditions['creatorName']);
            $conditions['creator'] = $user ? $user['id'] : -1;
        }


        if (isset($conditions['recommendedSeq'])) {
            $conditions['recommended'] = 1;
            unset($conditions['recommendedSeq']);
        }

        return $conditions;
    }

    protected function countStudentNumById($id)
    {
        $courseSet = $this->getCourseSet($id);
        $courses = $this->getCourseService()->findCoursesByCourseSetId($courseSet['id']);

        return array_reduce(
            $courses,
            function ($studentNum, $course) {
                $studentNum += $course['studentNum'];

                return $studentNum;
            }
        );
    }

    public function findRelatedCourseSetsByCourseSetId($courseSetId, $count)
    {
        $courseSet = $this->getCourseSet($courseSetId);
        $tags = $courseSet['tags'];
        if (empty($tags)) {
            return array();
        }
        $courseSetIds = $this->getRelatedCourseSetDao()->pickRelatedCourseSetIdsByTags($tags, $count, $courseSet['id']);

        $courseSets = $this->findCourseSetsByIds($courseSetIds);
        $courseSets = ArrayToolkit::index($courseSets, 'id');

        $relatedCourseSets = array();
        foreach ($courseSetIds as $key => $courseId) {
            $relatedCourseSets[] = $courseSets[$courseId];
        }

        return $relatedCourseSets;
    }

    public function refreshHotSeq()
    {
        return $this->getCourseSetDao()->refreshHotSeq();
    }

    protected function getRelatedCourseSetDao()
    {
        return $this->createDao('Course:RelatedCourseSetDao');
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

    /**
     * @return CourseNoteService
     */
    protected function getNoteService()
    {
        return $this->createService('Course:CourseNoteService');
    }

    /**
     * @return MemberService
     */
    protected function getCourseMemberService()
    {
        return $this->biz->service('Course:MemberService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->biz->service('Course:CourseService');
    }

    /**
     * @return TagService
     */
    protected function getTagService()
    {
        return $this->biz->service('Taxonomy:TagService');
    }

    /**
     * @return ReviewService
     */
    protected function getReviewService()
    {
        return $this->biz->service('Course:ReviewService');
    }

    /**
     * @return FileService
     */
    protected function getFileService()
    {
        return $this->biz->service('Content:FileService');
    }

    /**
     * @return FavoriteDao
     */
    protected function getFavoriteDao()
    {
        return $this->biz->dao('Course:FavoriteDao');
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    /**
     * @return MaterialService
     */
    protected function getCourseMaterialService()
    {
        return $this->createService('Course:MaterialService');
    }

    /**
     * @return CourseDeleteService
     */
    protected function getCourseDeleteService()
    {
        return $this->createService('Course:CourseDeleteService');
    }

    /**
     * @return \Biz\Taxonomy\Service\CategoryService
     */
    protected function getCategoryService()
    {
        return $this->createService('Taxonomy:CategoryService');
    }

    /**
     * @return ClassroomService
     */
    protected function getClassroomService()
    {
        return $this->createService('Classroom:ClassroomService');
    }

    /**
     * @return \Biz\System\Service\SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    protected function generateDefaultCourse($created)
    {
        $defaultTitle = '';
        $defaultCourse = array(
            'courseSetId' => $created['id'],
            'title' => $defaultTitle,
            'expiryMode' => 'forever',
            'learnMode' => empty($created['learnMode']) ? CourseService::FREE_LEARN_MODE : $created['learnMode'],
            'courseType' => empty($created['courseType']) ? CourseService::DEFAULT_COURSE_TYPE : $created['courseType'],
            'isDefault' => 1,
            'isFree' => 1,
            'serializeMode' => $created['serializeMode'],
            'status' => 'draft',
            'type' => $created['type'],
            'showServices' => isset($created['showServices']) ? $created['showServices'] : 0,
        );

        return $defaultCourse;
    }

    protected function filterFields($fields)
    {
        if (isset($fields['tags'])) {
            if (empty($fields['tags'])) {
                $fields['tags'] = array();
            } else {
                $tags = explode(',', $fields['tags']);
                $tags = $this->getTagService()->findTagsByNames($tags);
                $tagIds = ArrayToolkit::column($tags, 'id');
                $fields['tags'] = $tagIds;
            }
        }
        foreach ($fields as $key => $value) {
            if (in_array($key, array('summary', 'subtitle'))) {
                continue;
            }
            if ('' === $value || null === $value) {
                unset($fields[$key]);
            }
        }

        if (!empty($fields['summary'])) {
            $fields['summary'] = $this->purifyHtml($fields['summary'], true);
        }

        if (!empty($fields['goals'])) {
            $fields['goals'] = json_decode($fields['goals'], true);
        }

        if (!empty($fields['audiences'])) {
            $fields['audiences'] = json_decode($fields['audiences'], true);
        }

        return $fields;
    }

    /**
     * @param $courseSet
     *
     * @return mixed
     *
     * @throws \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    protected function addCourseSet($courseSet)
    {
        if (!ArrayToolkit::requireds($courseSet, array('title', 'type'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        $courseSet = ArrayToolkit::parts(
            $courseSet,
            array(
                'type',
                'title',
                'orgCode',
            )
        );

        $courseSet['status'] = 'draft';

        $coinSetting = $this->getSettingService()->get('coin', array());
        if (!empty($coinSetting['coin_enabled']) && (bool) $coinSetting['coin_enabled']) {
            $courseSet['maxRate'] = 100;
        }

        $courseSet['creator'] = $this->getCurrentUser()->getId();

        $created = $this->getCourseSetDao()->create($courseSet);

        return $created;
    }

    protected static function courseSetTypes()
    {
        return array(
            CourseSetService::NORMAL_TYPE,
            CourseSetService::LIVE_TYPE,
        );
    }

    /**
     * @param $courseSet
     * @param $created
     *
     * @return array
     */
    protected function addDefaultCourse($courseSet, $created)
    {
        $created = array_merge($created, $courseSet);
        $defaultCourse = $this->generateDefaultCourse($created);

        return $this->getCourseService()->createCourse($defaultCourse);
    }
}
