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
     * Gets user token
     *
     * @param string $userHash User hash
     *
     * @return string
     */
    public function getUserToken(string $userHash): string {
        return $this->hash($this->getAppHash() . $userHash);
    }

    /**
     * Gets user token By Date
     *
     * @param string $userHash User hash
     *
     * @return string
     */
    public function getUserTokenByDate(string $userHash): string {
        return $this->hash($this->getAppHash() . $userHash . date('Y-m-d'));
    }

    /**
     * Gets user token by Hour
     *
     * @param string $userHash User hash
     *
     * @return string
     */
    public function getUserTokenByHour(string $userHash): string {
        return $this->hash($this->getAppHash() . $userHash . date('Y-m-d H'));
    }

    /**
     * Gets app hash
     *
     * @return string
     */
    protected function getAppHash(): string {
        $configs = Helpers::phlexusConfig('security')->toArray();

        return isset($configs[self::APP_HASH_PARAM_KEY]) ? $configs[self::APP_HASH_PARAM_KEY] : '';
    }
}