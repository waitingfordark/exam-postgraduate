<?php

namespace AppBundle\Controller\Admin;

use AppBundle\System;
use Biz\Theme\Service\ThemeService;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;

class ThemeController extends BaseController
{

    public function saveConfigAction(Request $request)
    {
        $config = $request->request->get('config');
        $this->getThemeService()->saveCurrentThemeConfig($config);

        return $this->createJsonResponse(true);
    }

    public function themeConfigEditAction(Request $request)
    {
        $config = $request->query->get('config');

        return $this->edit($config['code'], $config);
    }

    private function edit($code, $config)
    {
        if (!empty($config['isPlugin']) && $this->getWebExtension()->isPluginInstalled($config[
            'pluginName'])) {
            $template = $config['edit'];
        } elseif (empty($config['isPlugin'])) {
            $template = 'admin/theme/edit-modal/edit-'.$code.'-modal.html.twig';
        }

        return $this->render(
            $template,
            array(
                'config' => $config,
            )
        );
    }

    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * @return ThemeService
     */
    protected function getThemeService()
    {
        return $this->createService('Theme:ThemeService');
    }

    protected function getNavigationService()
    {
        return $this->createService('Content:NavigationService');
    }

    protected function getWebExtension()
    {
        return $this->get('web.twig.extension');
    }
}
