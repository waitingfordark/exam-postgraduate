<?php

namespace AppBundle\Extensions\DataTag;

use AppBundle\Common\Paginator;
use Symfony\Component\HttpFoundation\Request;

class PaginatorDataTag extends BaseDataTag implements DataTag
{
    /**
     * 获取Paginator对象.
     *
     * 可传入的参数：
     *   total 分页总数
     *   perPage 每页数量
     *   path 分页跳转的path(可选,example:/scratch/work_explore)
     *
     * @param array $arguments 参数
     *
     * @return paginator
     */
    public function getData(array $arguments)
    {
        $this->checkArguments($arguments, array('total', 'perPage'));

        $perPage = $arguments['perPage'];
        $total = $arguments['total'];
        $request = new Request();
        $request->query->set('page', 2);
        $request->server->set('REQUEST_URI', null);
        $paginator = new Paginator($request, $total, $perPage);
        $paginator->setPageRange(100);
        $paginator->setCurrentPage(2);

        if (isset($arguments['path'])) {
            $template = $this->replacePath($request->server->get('REQUEST_URI'), $arguments['path']);
            $paginator->setBaseUrl($template);
        }

        return $paginator;
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
}
