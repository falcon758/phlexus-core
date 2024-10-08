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

use Phalcon\Cli\Dispatcher as CliDi;
use Phalcon\Mvc\Dispatcher as MvcDi;
use Phlexus\Application;
use Phlexus\Helpers;

class DispatcherProvider extends AbstractProvider
{
    /**
     * Provider name
     *
     * @var string
     */
    protected string $providerName = 'dispatcher';

    /**
     * Register application provider
     *
     * @param array $parameters
     */
    public function register(array $parameters = []): void
    {
        $this->getDI()->setShared($this->providerName, function () {
            /** @var Application $app */
            $app = Helpers::phlexusContainer(Application::APP_CONTAINER_NAME);

            if ($app->getMode() === Application::MODE_CLI) {
                $dispatcher = new CliDi();
            } else {
                $dispatcher = new MvcDi();
            }

            $dispatcher->setDI(Helpers::phlexusContainer());
            $dispatcher->setEventsManager(Helpers::phlexusContainer('eventsManager'));

            return $dispatcher;
        });
    }
}
