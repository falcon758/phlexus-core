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
use Phalcon\Paginator\Adapter\Model as PaginatorModel;

abstract class Model extends PhalconModel implements ModelInterface
{
    protected const ACTIVE_FIELD = 'active';
    
    protected const PAGE_LIMIT = 25;
    
    protected static array $encryptFields = [];

    private static Security $security;

    /**
     * Get encrypt fields
     * 
     * @return array Fields
     */
    public static function getEncryptFields() : array
    {
        return [];
    }

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

        $modelToken = self::getModelToken();

        foreach ($encryptFields as $field) {
            if (!isset($model->{$field})) { continue; }

            $model->{$field} = $this->getSecurity()->encrypt($modelToken, $model->{$field});
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

        $modelToken = self::getModelToken();

        foreach ($encryptFields as $field) {
            if (!isset($model->{$field})) { continue; }

            $model->{$field} = $this->getSecurity()->decrypt($modelToken, $model->{$field});
        }

        return $model;
    }
    
    /**
     * Find records override
     * 
     * @param mixed $parameters Parameters to search
     * 
     * @return ResultsetInterface
     */
    public static function find($parameters = null): ResultsetInterface
    {
        $result = parent::find(self::injectActiveParameter($parameters));

        $encryptFields = static::getEncryptFields();

        if (count($encryptFields) === 0) {
            return $result;
        }

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
     * Find first records override
     * 
     * @param mixed $parameters Parameters to search
     * 
     * @return mixed
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst(self::injectActiveParameter($parameters));
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
     * Get Model Paginator
     * 
     * @param array $parameters Parameters to query
     * @param array $page       Current page
     * @param int   $pageLimit  Page limit to apply
     * 
     * @return string
     */
    public static function getModelPaginator(array $parameters = [], int $page = 1, int $pageLimit = self::PAGE_LIMIT)
    {
        $activeField = self::ACTIVE_FIELD;

        $m_class = static::class;

        if (!isset($parameters[$activeField]) && property_exists($m_class, $activeField)) {
            $parameters[$activeField] = defined('ENABLED') ? static::ENABLED : 1;
        }

        $paginator = new PaginatorModel(
            [
                'model'      => static::class,
                'parameters' => self::arrayToParameters($parameters),
                'limit'      => $pageLimit,
                'page'       => $page,
            ]
        );

        return $paginator;
    }

    /**
     * Convert array to model parameters
     * 
     * @param array $parameters Parameters to convert
     * 
     * @return array|null
     */
    public static function arrayToParameters(array $parameters): ?array {
        if (count($parameters) === 0) {
            return null;
        }

        $assign = array_map(function($k){
            return "$k = :$k:";
        }, array_keys($parameters));

        return [
            'conditions' => implode(' AND ', $assign),
            'bind'       => $parameters
        ];
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
     * Get Model Token
     * 
     * @return string
     */
    private static function getModelToken()
    {
        return self::getSecurity()->getStaticDatabaseToken();
    }

    /**
     * Inject active parameter
     * 
     * @param mixed $parameters Parameters to search
     * 
     * @return array
     */
    private static function injectActiveParameter($parameters = null): array {
        if ($parameters === null) {
            $parameters = [];
        }

        $activeField = self::ACTIVE_FIELD;

        $m_class = static::class;

        if (property_exists($m_class, $activeField)) {
            $inserted = false;
            if (isset($parameters[0]) && strpos($parameters[0], $activeField) === false) {
                $parameters[0] .= " AND $m_class.$activeField = :injectedActive:";

                $inserted = true;
            } else if (
                !isset($parameters[0]) &&
                (!isset($parameters['conditions']) || strpos($parameters['conditions'], $activeField) === false)
            ) {
                $conditions = isset($parameters['conditions']) ? $parameters['conditions'] . ' AND ' : '';
                $parameters['conditions'] = $conditions . "$m_class.$activeField = :injectedActive:";

                $inserted = true;
            }

            if ($inserted) {
                $bind = isset($parameters['bind']) ? $parameters['bind'] : [];
                $bind['injectedActive'] = defined('ENABLED') ? static::ENABLED : 1;

                $parameters['bind'] = $bind;
            }
        }

        return $parameters;
    }
}