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

use Phalcon\Session\Manager;
use Phalcon\Session\Adapter\Stream;

class SessionProvider extends AbstractProvider
{
    protected const SESSION_NAME = 'session_name';

    /**
     * Provider name
     *
     * @var string
     */
    protected string $providerName = 'session';

    /**
     * Register application service.
     *
     * @psalm-suppress UndefinedMethod
     *
     * @param array $parameters Custom parameters for Service Provider
     */
    public function register(array $parameters = []): void
    {
        $this->getDI()->setShared($this->providerName, function () use ($parameters) {
            $session = new Manager();
            $session->setAdapter(new Stream(['savePath' => '/tmp']));

            $sessionNameKey = self::SESSION_NAME;
            if (isset($parameters[$sessionNameKey])) {
                $session->setName($parameters[$sessionNameKey]);
            }

            $session->start();

            return $session;
        });
    }
}
