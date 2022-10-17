<?php

/**
 * This file is part of the Phlexus CMS.
 *
 * (c) Phlexus CMS <cms@phlexus.io>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phlexus\Providers;

use Phalcon\Config\Config;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Router\GroupInterface;
use Phlexus\Application;
use Phlexus\Helpers;

class RouterProvider extends AbstractProvider
{
    /**
     * Provider name
     *
     * @var string
     */
    protected string $providerName = 'router';

    /**
     * Register application service.
     *
     * @param array $parameters
     */
    public function register(array $parameters = []): void
    {
        $app = Helpers::phlexusContainer(Application::APP_CONTAINER_NAME);

        // Not necessary for command line
        if ($app->getMode() === Application::MODE_CLI) {
            return;
        } 

        $this->getDI()->setShared($this->providerName, function () {
            $router = new Router(false);
            $router->removeExtraSlashes(true);

            /** @var Config $modules */
            $modules = Helpers::phlexusContainer('modules');
            $modules = $modules->toArray();
            foreach ($modules as $module) {
                if (empty($module->router)) {
                    continue;
                }

                if (!file_exists($module->router)) {
                    continue;
                }

                $group = require $module->router;
                if (!$group instanceof GroupInterface) {
                    continue;
                }

                $router->mount($group);
            }

            $router->setDI(Helpers::phlexusContainer());

            $router->setDefaultModule('Landing');

            return $router;
        });
    }
}
