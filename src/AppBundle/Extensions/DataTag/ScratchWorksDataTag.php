<?php

namespace AppBundle\Extensions\DataTag;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\Paginator;
use Biz\Scratch\Service\WorkService;
use Biz\User\Service\UserService;

class ScratchWorksDataTag extends BaseDataTag implements DataTag
{
    /**
     * 获取作品列表.
     *
     * 可传入的参数：
     *   orderBy 排序方式:最新(latest)、最热(hotSeq)、推荐(recommendedSeq)
     *   page 页码
     *   request request对象
     *
     * @param array $arguments 参数
     *
     * @return array 作品
     */
    public function getData(array $arguments)
    {
        $this->checkArguments($arguments, array('orderBy', 'page'));
        $orderList = array(
            'latest' => 'publishTime',
            'recommendedSeq' => 'recommendedSeq',
            'hotSeq' => 'hotSeq',
        );
        if (!array_key_exists($arguments['orderBy'], $orderList)) {
            throw new \InvalidArgumentException('orderBy invalid');
        }

        $conditions = array('status' => 'published');
        if ('recommendedSeq' == $arguments['orderBy']) {
            $conditions['recommended'] = 1;
        }

        $request = $arguments['request'];

        $total = $this->getWorkService()->searchWorkCount($conditions);
        $paginator = new Paginator($request, $total, 4);

        $template = $this->replacePath($request->server->get('REQUEST_URI'), '/scratch/work_explore');
        $paginator->setBaseUrl($template);

        $work = $this->getWorkService()->searchWorks(
            $conditions,
            array($orderList[$arguments['orderBy']] => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $creatorIds = ArrayToolkit::column($work, 'userId');
        $creators = $this->getUserService()->findUsersByIds($creatorIds);

        $allWorkNum = $this->getWorkService()->searchWorkCount(array('status' => 'published'));
        $todayWorkNum = $this->getWorkService()->searchWorkCount(
            array(
                'status' => 'published',
                'publishStartTime' => strtotime(date('Y-m-d')),
            )
        );

        return array(
            'work' => $work,
            'creators' => $creators,
            'allWorkNum' => $allWorkNum,
            'todayWorkNum' => $todayWorkNum,
            'paginator' => $paginator,
        );
    }

    protected function replacePath($url, $path)
    {
        $urls = parse_url($url);
        $template = '';
        $template .= empty($urls['scheme']) ? '' : $urls['scheme'].'://';
        $template .= empty($urls['host']) ? '' : $urls['host'];
        $template .= empty($urls['path']) ? '' : $path;
        $template .= empty($urls['query']) ? '' : '?'.$urls['query'];

        return $template;
    }

    /**
     * @return WorkService
     */
    protected function getWorkService()
    {
        return $this->createService('Scratch:WorkService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }
}
