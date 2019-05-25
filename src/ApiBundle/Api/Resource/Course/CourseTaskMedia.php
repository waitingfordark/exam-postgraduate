<?php

namespace ApiBundle\Api\Resource\Course;

use ApiBundle\Api\Annotation\Access;
use ApiBundle\Api\Annotation\ApiConf;
use ApiBundle\Api\ApiRequest;
use ApiBundle\Api\Resource\AbstractResource;
use Biz\Activity\Service\ActivityService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\MemberService;
use Biz\File\Service\UploadFileService;
use Biz\Player\Service\PlayerService;
use Biz\System\Service\SettingService;
use Biz\Task\Service\TaskService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CourseTaskMedia extends AbstractResource
{
    /**
     * @param ApiRequest $request
     * @param $courseId
     * @param $taskId
     *
     * @return array
     * @Access(roles="")
     * @ApiConf(isRequiredAuth=false)
     */
    public function get(ApiRequest $request, $courseId, $taskId)
    {
        $ssl = $request->getHttpRequest()->isSecure() ? true : false;
        $preview = $request->query->get('preview');
        if ($preview) {
            $task = $this->getTaskService()->getTask($taskId);
            $course = $this->getCourseService()->getCourse($task['courseId']);
            $this->checkPreview($course, $task);
        } else {
            $task = $this->getTaskService()->tryTakeTask($taskId);
            list($course, $member) = $this->getCourseService()->tryTakeCourse($task['courseId']);
        }

        $activity = $this->getActivityService()->getActivity($task['activityId'], true);
        $method = 'get'.$activity['mediaType'];
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException(sprintf('Unknown property "%s" on TaskMedia "%s".', $activity['mediaType'], get_class($this)));
        }
        $media = $this->$method($course, $task, $activity, $request->getHttpRequest(), $ssl);

        return array(
            'mediaType' => $activity['mediaType'],
            'media' => $media,
        );
    }

    protected function checkPreview($course, $task)
    {
        $user = $this->getCurrentUser();
        $taskCanTryLook = false;
        if ($course['tryLookable'] && 'video' == $task['type']) {
            $activity = $this->getActivityService()->getActivity($task['activityId'], true);
            if (!empty($activity['ext']) && !empty($activity['ext']['file']) && $activity['ext']['file']['storage'] === 'cloud') {
                $taskCanTryLook = true;
            }
        }

        if (empty($task['isFree']) && !$taskCanTryLook) {
            if (!$user->isLogin()) {
                throw new AccessDeniedHttpException('user must be login');
            }
            if ($course['parentId'] > 0) {
                throw new AccessDeniedHttpException('must join classroom');
            }

            if (!$this->getCourseMemberService()->isCourseMember($course['id'], $user['id'])) {
                throw new AccessDeniedHttpException('you are not course member');
            }
        }

        //在可预览情况下查看网站设置是否可匿名预览
        $allowAnonymousPreview = $this->getSettingService()->node('course.allowAnonymousPreview', 1);

        if (empty($allowAnonymousPreview) && !$user->isLogin()) {
            throw new AccessDeniedHttpException('user must be login');
        }
    }

    protected function getVideo($course, $task, $activity, $request, $ssl = false)
    {
        $config = $this->getActivityService()->getActivityConfig($activity['mediaType']);
        $video = $config->get($activity['mediaId']);
        $watchStatus = $config->getWatchStatus($activity);
        if ('error' === $watchStatus['status']) {
            throw new AccessDeniedHttpException('您的视频观看时长已达限制，无法继续观看！');
        }

        $video = $config->prepareMediaUri($video);

        if ('self' != $video['mediaSource']) {
            return $video;
        }

        $file = $this->getUploadFileService()->getFullFile($video['mediaId']);
        if (empty($file)) {
            throw new NotFoundHttpException('file not found');
        }
        if (!in_array($file['type'], array('audio', 'video'))) {
            throw new AccessDeniedHttpException("player does not support  file type: {$file['type']}");
        }

        $player = $this->getPlayerService()->getAudioAndVideoPlayerType($file);

        $agentInWhiteList = $this->getPlayerService()->agentInWhiteList($request->headers->get('user-agent'));

        $isEncryptionPlus = false;
        if ('video' == $file['type'] && 'cloud' == $file['storage']) {
            $videoPlayer = $this->getPlayerService()->getVideoFilePlayer($file, $agentInWhiteList, array(), $ssl);
            $isEncryptionPlus = $videoPlayer['isEncryptionPlus'];
            $context = $videoPlayer['context'];
            if (!empty($videoPlayer['mp4Url'])) {
                $mp4Url = $videoPlayer['mp4Url'];
            }
        }
        $user = $this->getCurrentUser();
        $isCourseMember = $this->getCourseMemberService()->isCourseMember($course['id'], $user['id']);
        if (!empty($course['tryLookable']) && !$isCourseMember) {
            $context['watchTimeLimit'] = $course['tryLookLength'] * 60;
        }
        $url = isset($mp4Url) ? $mp4Url : $this->getPlayUrl($file, $context, $ssl);

        return array(
            'resId' => $file['globalId'],
            'url' => isset($url) ? $url : null,
            'player' => $player,
            'videoHeaderLength' => isset($context['videoHeaderLength']) ? $context['videoHeaderLength'] : 0,
            'timeLimit' => isset($context['watchTimeLimit']) ? $context['watchTimeLimit'] : 0,
            'agentInWhiteList' => $agentInWhiteList,
            'isEncryptionPlus' => $isEncryptionPlus,
        );
    }

    protected function getAudio($course, $task, $activity, $request, $ssl = false)
    {
        $config = $this->getActivityService()->getActivityConfig($activity['mediaType']);
        $audio = $config->get($activity['mediaId']);
        $file = $this->getUploadFileService()->getFullFile($audio['mediaId']);
        if (empty($file)) {
            throw new NotFoundHttpException('file not found');
        }
        if (!in_array($file['type'], array('audio', 'video'))) {
            throw new AccessDeniedHttpException("player does not support  file type: {$file['type']}");
        }

        $player = $this->getPlayerService()->getAudioAndVideoPlayerType($file);

        $agentInWhiteList = $this->getPlayerService()->agentInWhiteList($request->headers->get('user-agent'));

        $url = $this->getPlayUrl($file, array(), $ssl);

        return array(
            'resId' => $file['globalId'],
            'url' => isset($url) ? $url : null,
            'player' => $player,
            'agentInWhiteList' => $agentInWhiteList,
            'isEncryptionPlus' => false,
        );
    }

    protected function getDoc($course, $task, $activity, $request, $ssl = false)
    {
        $config = $this->getActivityService()->getActivityConfig($activity['mediaType']);
        $doc = $config->get($activity['mediaId']);

        list($result, $error) = $this->getPlayerService()->getDocFilePlayer($doc, $ssl);
        if (!empty($error)) {
            throw new BadRequestHttpException($error['message']);
        }

        return $result;
    }

    protected function getPpt($course, $task, $activity, $request, $ssl = false)
    {
        $config = $this->getActivityService()->getActivityConfig('ppt');

        $ppt = $config->get($activity['mediaId']);

        list($result, $error) = $this->getPlayerService()->getPptFilePlayer($ppt, $ssl);
        if (!empty($error)) {
            throw new BadRequestHttpException($error['message']);
        }

        return $result;
    }

    protected function getLive($course, $task, $activity, $request, $ssl = false)
    {
        $config = $this->getActivityService()->getActivityConfig($activity['mediaType']);
        $live = $config->get($activity['mediaId']);
        if ($live['roomCreated']) {
            $format = 'Y-m-d H:i';

            return array(
                'entryUrl' => $this->generateUrl('task_live_entry', array('courseId' => $course['id'], 'activityId' => $activity['id']), true),
                'startTime' => date('c', $activity['startTime']),
                'endTime' => date('c', $activity['endTime']),
            );
        }
    }

    protected function getText($course, $task, $activity, $request, $ssl = false)
    {
        return array(
            'title' => $activity['title'],
            'content' => $activity['content'],
        );
    }

    protected function getPlayUrl($file, $context, $ssl)
    {
        $result = $this->getPlayerService()->getVideoPlayUrl($file, $context, $ssl);
        if (isset($result['url'])) {
            return $result['url'];
        }

        return $this->generateUrl($result['route'], $result['params'], $result['referenceType']);
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->getBiz()->service('Course:CourseService');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->getBiz()->service('Task:TaskService');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->getBiz()->service('Activity:ActivityService');
    }

    /**
     * @return PlayerService
     */
    protected function getPlayerService()
    {
        return $this->getBiz()->service('Player:PlayerService');
    }

    /**
     * @return UploadFileService
     */
    protected function getUploadFileService()
    {
        return $this->getBiz()->service('File:UploadFileService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->getBiz()->service('System:SettingService');
    }

    /**
     * @return MemberService
     */
    protected function getCourseMemberService()
    {
        return $this->getBiz()->service('Course:MemberService');
    }
}
