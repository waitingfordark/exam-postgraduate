<?php

namespace AppBundle\Controller\Question;

use Symfony\Component\HttpFoundation\Request;

class SingleChoiceQuestionController extends BaseQuestionController
{

    public function editAction(Request $request, $courseSetId, $questionId)
    {
        list($courseSet, $question) = $this->tryGetCourseSetAndQuestion($courseSetId, $questionId);
        $user = $this->getUser();

        $parentQuestion = array();

        $manageCourses = $this->getCourseService()->findUserManageCoursesByCourseSetId($user['id'], $courseSetId);
        $courseTasks = $this->getTaskService()->findTasksByCourseId($question['courseId']);

        return $this->render('question-manage/single-choice-form.html.twig', array(
            'courseSet' => $courseSet,
            'question' => $question,
            'parentQuestion' => '',
            'type' => $question['type'],
            'courseTasks' => $courseTasks,
            'courses' => $manageCourses,
            'request' => $request,
        ));
    }

    public function createAction(Request $request, $courseSetId, $type)
    {
        $user = $this->getUser();
        $courseSet = $this->getCourseSetService()->getCourseSet($courseSetId);
        $manageCourses = $this->getCourseService()->findUserManageCoursesByCourseSetId($user['id'], $courseSetId);

        $parentId = $request->query->get('parentId', 0);
        $parentQuestion = $this->getQuestionService()->get($parentId);

        return $this->render('question-manage/single-choice-form.html.twig', array(
            'courseSet' => $courseSet,
            'parentQuestion' => $parentQuestion,
            'type' => $type,
            'courses' => $manageCourses,
        ));
    }
}
