<?php

namespace AppBundle\Extensions\DataTag;

use AppBundle\Common\ArrayToolkit;

class TeachersDataTag extends CourseBaseDataTag implements DataTag
{
    /**
     * 获取一个用户.
     *
     * 可传入的参数：
     *   count 必需 教师数
     *
     * @param array $arguments 参数
     *
     * @return array 用户
     */
    public function getData(array $arguments)
    {
        $this->checkCount($arguments);

        $conditions = array(
            'roles' => 'ROLE_TEACHER',
            'locked' => 0,
            'promoted' => 1,
        );
        $teachers = $this->getUserService()->searchUsers(
            $conditions,
            array(
                'promoted' => 'DESC',
                'promotedSeq' => 'ASC',
                'promotedTime' => 'DESC',
                'createdTime' => 'DESC',
            ),
            0,
            $arguments['count']
        );
        $teacherIds = ArrayToolkit::column($teachers, 'id');
        $profiles = $this->getUserService()->findUserProfilesByIds($teacherIds);

        return array('teachers' => $teachers, 'profiles' => $profiles);
    }
}
