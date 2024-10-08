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

use Phalcon\Events\Manager;
use Phlexus\Event\EventException;

class EventsManagerProvider extends AbstractProvider
{
    /**
     * Provider name
     *
     * @var string
     */
    protected string $providerName = 'eventsManager';

    /**
     * Register application service.
     *
     * @param array $events
     */
    public function register(array $events = []): void
    {
        $this->getDI()->setShared($this->providerName, function () use ($events) {
            $manager = new Manager();
            $manager->enablePriorities(true);

            foreach ($events as $handler => $class) {
                if (!class_exists($class)) {
                    throw new EventException('Event class do not exists: ' . $class);
                }

                $manager->attach($handler, new $class($this));
            }

            return $manager;
        });
    }
}
