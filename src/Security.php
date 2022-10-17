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
use Phalcon\Encryption\Security as PhalconSecurity;

class Security extends PhalconSecurity
{
    /**
     * @var string
     */
    private string $appHash;

    /**
     * @var string
     */
    private string $userHash;

    /**
     * @var string
     */
    private string $databaseHash;

    /**
     * Get app hash
     *
     * @return string
     */
    protected function getAppHash(): string
    {
        return $this->appHash;
    }

    /**
     * Set UserHash
     *
     * @param string $userHash User hash
     *
     * @return void
     */
    public function setAppHash(string $appHash): void
    {
        $this->appHash = $appHash;
    }

    /**
     * Get UserHash
     *
     * @return string
     */
    public function getUserHash(): string
    {
        return $this->userHash;
    }

    /**
     * Set UserHash
     *
     * @param string $userHash User hash
     *
     * @return void
     */
    public function setUserHash(string $userHash): void
    {
        $this->userHash = $userHash;
    }

    /**
     * Get DatabaseHash
     *
     * @return string
     */
    public function getDatabaseHash(): string
    {
        return $this->databaseHash;
    }

    /**
     * Set DatabaseHash
     *
     * @param string $databaseHash User hash
     *
     * @return void
     */
    public function setDatabaseHash(string $databaseHash): void
    {
        $this->databaseHash = $databaseHash;
    }

    /**
     * Get static user token
     *
     * @param string $append String to append
     *
     * @return string
     */
    public function getStaticUserToken(string $append = ''): string
    {
        return \md5($this->getAppHash() . $this->getUserHash() . $append);
    }

    /**
     * Get static database token
     *
     * @return string
     */
    public function getStaticDatabaseToken(string $append = ''): string
    {
        return $this->getStaticUserToken($this->getDatabaseHash());
    }

    /**
     * Get user token
     *
     * @param string $append String to append
     *
     * @return string
     */
    public function getUserToken(string $append = ''): string
    {
        return $this->hash($this->getAppHash() . $this->getUserHash() . $append);
    }

    /**
     * Check user token
     *
     * @param string $token    Token to check
     * @param string $append   String to append
     *
     * @return bool
     */
    public function checkUserToken(string $token, string $append = ''): bool
    {
        return $this->checkHash($this->getAppHash() . $this->getUserHash() . $append, $token);
    }

    /**
     * Get user token By Date
     *
     * @param string $append String to append
     *
     * @return string
     */
    public function getUserTokenByDate(string $append = ''): string
    {
        return $this->hash($this->getAppHash() . $this->getUserHash() . $append . date('Y-m-d'));
    }

    /**
     * Check user token
     *
     * @param string $token    Token to check
     * @param string $append   String to append
     *
     * @return bool
     */
    public function checkUserTokenByDate(string $token, string $append = ''): bool
    {
        return $this->checkHash($this->getAppHash() . $this->getUserHash() . $append . date('Y-m-d'), $token);
    }

    /**
     * Get user token by Hour
     *
     * @param string $append String to append
     *
     * @return string
     */
    public function getUserTokenByHour(string $append = ''): string
    {
        return $this->hash($this->getAppHash() . $this->getUserHash() . $append . date('Y-m-d H'));
    }

    /**
     * Check user token by Hour
     *
     * @param string $token    Token to check
     * @param string $append   String to append
     *
     * @return bool
     */
    public function checkUserTokenByHour(string $token, string $append = ''): bool
    {
        return $this->checkHash($this->getAppHash() . $this->getUserHash() . $append . date('Y-m-d H'), $token);
    }
}