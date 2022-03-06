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

namespace Phlexus\Models;

use Phlexus\Security;
use Phalcon\Mvc\Model as PhalconModel;
use Phalcon\Mvc\Model\Resultset\Simple as ResultSimple;
use Phalcon\Mvc\Model\ResultsetInterface;

abstract class Model extends \Phalcon\Mvc\Model implements ModelInterface
{
    private const SECUREKEY = 'd8dc2d6eeb2b3350c0434ece7d9a9c6fcbd51f3da094527d8185c5425380eea7';

    protected static array $encryptFields = [];

    private static Security $security;

    private static string $userToken;

    /**
     * Encrypt all specified fields
     *
     * @param PhalconModel $model Model to encrypt
     *
     * @return PhalconModel
     */
    public static function encrypt(PhalconModel $model): PhalconModel
    {
        $encryptFields = static::getEncryptFields();

        if (count($encryptFields) === 0) {
            return $model;
        }

        $token = self::getToken();

        $query = 'SELECT ';
        foreach ($encryptFields as $field) {
            if (!isset($model->{$field})) { continue; }

            $query .= 'FN_Encrypt("' . base64_encode($model->{$field}) . '", "' . base64_encode($token) .'") AS ' . $field . ',';
        }
       
        $encryptedFields = new ResultSimple(null, $model, $model->getReadConnection()->query(rtrim(trim($query), ',')));

        if (!$encryptedFields) { return $model; }

        foreach (current($encryptedFields->toArray()) as $field => $value) {
            $model->{$field} = $value;
        }

        return $model;
    }
    
    /**
     * Decrypt all specified fields
     *
     * @param PhalconModel $model Model to decrypt
     * 
     * @return PhalconModel
     */
    public static function decrypt(PhalconModel $model): PhalconModel
    {
        $encryptFields = static::getEncryptFields();
        
        if (count($encryptFields) === 0) {
            return $model;
        }

        $token = self::getToken();

        $query = 'SELECT ';
        foreach ($encryptFields as $field) {
            if (!isset($model->{$field})) { continue; }

            $query .= 'FN_Decrypt("' . base64_encode($model->{$field}) . '", "' . base64_encode($token) .'") AS ' . $field . ',';
        }

        $decryptedFields = new ResultSimple(null, $model, $model->getReadConnection()->query(rtrim(trim($query), ',')));

        if (!$decryptedFields) { return $model; }

        foreach (current($decryptedFields->toArray()) as $field => $value) {
            $model->{$field} = $value;
        }

        return $model;
    }
    
    /**
     * Find records override
     * 
     * @param mixed $parameters Parameters to search
     * 
     * @return void
     */
    public static function find($parameters = null): ResultsetInterface
    {
        $result = parent::find($parameters);

        $resultSet = new Simple(null, null, null);

        $resultSet->import($result);

        $result->rewind();

        while ($result->valid()) {
            $model = $result->current();

            $resultSet->setRow(self::decrypt($model));

            $result->next();
        }

        $resultSet->rewind();

        return $resultSet;
    }

    /**
     * Get user token
     * 
     * @return string
     */
    public static function getUserToken(): string {
        $userToken = $this->userToken;

        if (!isset($userToken)) {
            $userToken = self::SECUREKEY;
        }

        return $userToken;
    }

    /**
     * Set user token
     * 
     * @param string $userToken
     * 
     * @return void
     */
    public static function setUserToken(string $userToken): void {
        $this->userToken = $userToken;
    }

    /**
     * After Fetch
     * 
     * @return void
     */
    public function afterFetch()
    {
        self::decrypt($this);
    }
   
    /**
     * Before Save
     * 
     * @return void
     */
    public function beforeSave()
    {
        self::encrypt($this);
    }

    /**
     * Get security
     * 
     * @return Security
     */
    private static function getSecurity(): Security
    {
        if (!isset(self::$security)) {
            self::$security = new Security();
        }

        return self::$security;
    }

    /**
     * Get Token
     * 
     * @return string
     */
    private static function getToken() {
        return self::getSecurity()->getStaticUserToken(self::getUserToken());
    }
}