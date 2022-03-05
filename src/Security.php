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
     * Gets static user token
     *
     * @param string $userHash User hash
     *
     * @return string
     */
    public function getStaticUserToken(string $userHash): string {
        return \md5($this->getAppHash() . $userHash);
    }

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
     * Check user token
     *
     * @param string $userHash User hash
     * @param string $token    Token to check
     *
     * @return bool
     */
    public function checkUserToken(string $userHash, string $token): bool {
        return $this->checkHash($this->getAppHash() . $userHash, $token);
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
     * Check user token
     *
     * @param string $userHash User hash
     * @param string $token    Token to check
     *
     * @return bool
     */
    public function checkUserTokenByDate(string $userHash, string $token): bool {
        return $this->checkHash($this->getAppHash() . $userHash . date('Y-m-d'), $token);
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
     * Check user token by Hour
     *
     * @param string $userHash User hash
     * @param string $token    Token to check
     *
     * @return boll
     */
    public function checkUserTokenByHour(string $userHash, string $token): bool {
        return $this->checkHash($this->getAppHash() . $userHash . date('Y-m-d H'), $token);
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