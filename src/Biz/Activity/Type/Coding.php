<?php

namespace Biz\Activity\Type;

use Biz\Activity\Config\Activity;
use Biz\Activity\Dao\CodingActivityDao;
use Biz\Activity\Service\ActivityService;
use Biz\Course\Service\CourseDraftService;

class Coding extends Activity
{
    protected function registerListeners()
    {
        return array();
    }

    public function get($targetId)
    {
        return $this->getCodingActivityDao()->get($targetId);
    }

    public function find($ids, $showCloud = 1)
    {
        return $this->getCodingActivityDao()->findByIds($ids);
    }

    public function copy($activity, $config = array())
    {
        $user = $this->getCurrentUser();
        $coding = $this->getCodingActivityDao()->get($activity['mediaId']);
        $newCoding = array(
            'finishType' => $coding['finishType'],
            'finishDetail' => $coding['finishDetail'],
            'createdUserId' => $user['id'],
        );

        return $this->getCodingActivityDao()->create($newCoding);
    }

    public function sync($sourceActivity, $activity)
    {
        $sourceCoding = $this->getCodingActivityDao()->get($sourceActivity['mediaId']);
        $coding = $this->getCodingActivityDao()->get($activity['mediaId']);
        $coding['finishType'] = $sourceCoding['finishType'];
        $coding['finishDetail'] = $sourceCoding['finishDetail'];

        return $this->getCodingActivityDao()->update($coding['id'], $coding);
    }

    public function update($targetId, &$fields, $activity)
    {
        $user = $this->getCurrentUser();
        $coding['createdUserId'] = $user['id'];
        $this->getCourseDraftService()->deleteCourseDrafts(
            $activity['fromCourseId'],
            $activity['id'],
            $user['id']
        );

        return $this->getCodingActivityDao()->update($targetId, $coding);
    }

    public function delete($targetId)
    {
        return $this->getCodingActivityDao()->delete($targetId);
    }

    public function create($fields)
    {
        $user = $this->getCurrentUser();
        $coding['createdUserId'] = $user['id'];

        $this->getCourseDraftService()->deleteCourseDrafts($fields['fromCourseId'], 0, $user['id']);

        return $this->getCodingActivityDao()->create($coding);
    }

    /**
     * @return CodingActivityDao
     */
    protected function getCodingActivityDao()
    {
        return $this->getBiz()->dao('Activity:CodingActivityDao');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->getBiz()->service('Activity:ActivityService');
    }

    /**
     * @return CourseDraftService
     */
    protected function getCourseDraftService()
    {
        return $this->getBiz()->service('Course:CourseDraftService');
    }
}
