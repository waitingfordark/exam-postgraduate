<?php

namespace ApiBundle\Api\Resource\Classroom;

use ApiBundle\Api\ApiRequest;
use ApiBundle\Api\Exception\ErrorCode;
use ApiBundle\Api\Resource\AbstractResource;
use AppBundle\Common\ArrayToolkit;
use Biz\Classroom\Service\ClassroomService;
use ApiBundle\Api\Annotation\ApiConf;
use Biz\User\Service\UserService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Classroom extends AbstractResource
{
    /**
     * @ApiConf(isRequiredAuth=false)
     */
    public function get(ApiRequest $request, $classroomId)
    {
        $classroom = $this->getClassroomService()->getClassroom($classroomId);

        if (empty($classroom)) {
            throw new NotFoundHttpException('班级不存在', null, ErrorCode::RESOURCE_NOT_FOUND);
        }

        $this->getOCUtil()->single($classroom, array('creator', 'teacherIds', 'assistantIds', 'headTeacherId'));

        if (!empty($classroom['headTeacher'])) {
            $this->mergeProfile($classroom['headTeacher']);
        }

        $classroom['access'] = $this->getClassroomService()->canJoinClassroom($classroomId);

        return $classroom;
    }

    /**
     * @ApiConf(isRequiredAuth=false)
     */
    public function search(ApiRequest $request)
    {
        $conditions = $request->query->all();
        $conditions['status'] = 'published';
        $conditions['showable'] = 1;
        if (isset($conditions['title'])) {
            $conditions['titleLike'] = $conditions['title'];
            unset($conditions['title']);
        }

        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $classrooms = $this->getClassroomService()->searchClassrooms(
            $conditions,
            $this->getSort($request),
            $offset,
            $limit
        );

        $this->getOCUtil()->multiple($classrooms, array('creator', 'teacherIds', 'headTeacherId', 'assistantIds'));

        $this->mergeProfilesInClassroom($classrooms, 'headTeacher');

        $total = $this->getClassroomService()->countClassrooms($conditions);

        return $this->makePagingObject($classrooms, $total, $offset, $limit);
    }

    private function mergeProfile(&$user)
    {
        $profile = $this->getUserService()->getUserProfile($user['id']);
        $user = array_merge($profile, $user);
    }

    private function mergeProfilesInClassroom(&$classrooms, $column)
    {
        $users = ArrayToolkit::column($classrooms, $column);
        $userIds = ArrayToolkit::column($users, 'id');
        $profiles = $this->getUserService()->findUserProfilesByIds($userIds);
        $profiles = ArrayToolkit::index($profiles, 'id');
        foreach ($classrooms as &$classroom) {
            if (!empty($classroom[$column]['id']) && !empty($profiles[$classroom[$column]['id']])) {
                $classroom[$column] = array_merge($classroom[$column], $profiles[$classroom[$column]['id']]);
            }
        }
    }

    /**
     * @return ClassroomService
     */
    private function getClassroomService()
    {
        return $this->service('Classroom:ClassroomService');
    }

    /**
     * @return UserService
     */
    private function getUserService()
    {
        return $this->service('User:UserService');
    }
}
