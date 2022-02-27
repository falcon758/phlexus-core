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

namespace Phlexus;

use Phlexus\Helpers;
use Phalcon\Security as PhalconSecurity;

class Security extends PhalconSecurity
{
    /**
     * Configuration app hash key name
     */
    public const APP_HASH_PARAM_KEY = 'app_hash';

    /**
     * Sets the events manager.
     *
     * @param  ManagerInterface $eventsManager
     * @return void
     */
    public function getUserToken(string $hashCode): string {
        return $this->hash($this->getAppHash() . $hashCode);
    }

    public function getUserTokenByDate(string $hashCode): string {
        return $this->hash($this->getAppHash() . $hashCode . date('Y-m-d'));
    }

    public function getUserTokenByHour(string $hashCode): string {
        return $this->hash($this->getAppHash() . $hashCode . date('Y-m-d H'));
    }

    protected function getAppHash(): string {
        $configs = Helpers::phlexusConfig('security')->toArray();

        return isset($configs[self::APP_HASH_PARAM_KEY]) ? $configs[self::APP_HASH_PARAM_KEY] : '';
    }
}