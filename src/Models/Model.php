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
use Phalcon\Di\Di;

abstract class Model extends PhalconModel implements ModelInterface
{
    protected const ACTIVE_FIELD = 'active';
    
    protected const PAGE_LIMIT = 25;
    
    protected static array $encryptFields = [];

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

        // Hydration already decrypted the record through afterFetch()
        while ($result->valid()) {
            $resultSet->setRow($result->current());

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
        self::decryptFields($this);
    }
   
    /**
     * Before Save
     *
     * @return void
     */
    public function beforeSave()
    {
        self::encryptFields($this);
    }

    /**
     * Before create
     *
     * @return void
     */
    public function beforeCreate()
    {
        $this->applyTimestampRulesOnCreate();
    }

    /**
     * Before update
     *
     * @return void
     */
    public function beforeUpdate()
    {
        $this->applyTimestampRulesOnUpdate();
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
     * Encrypt field
     *
     * @param string $encrypt
     * 
     * @return string
     */
    protected static function encrypt(string $encrypt): string
    {
        $modelToken = self::getModelToken();

        return self::getSecurity()->encrypt($modelToken, $encrypt);
    }

    /**
     * Decrypt field
     *
     * @param string $decrypt
     * 
     * @return string
     */
    protected static function decrypt(string $decrypt): string
    {
        $modelToken = self::getModelToken();

        return self::getSecurity()->decrypt($modelToken, $decrypt);
    }
    
    /**
     * Encrypt all specified fields
     *
     * @param PhalconModel $model Model to encrypt
     *
     * @return PhalconModel
     */
    protected static function encryptFields(PhalconModel $model): PhalconModel
    {
        $encryptFields = static::getEncryptFields();

        if (count($encryptFields) === 0) {
            return $model;
        }

        foreach ($encryptFields as $field) {
            if (!isset($model->{$field})) { continue; }

            $model->{$field} = self::encrypt($model->{$field});
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
    protected static function decryptFields(PhalconModel $model): PhalconModel
    {
        $encryptFields = static::getEncryptFields();
        
        if (count($encryptFields) === 0) {
            return $model;
        }

        foreach ($encryptFields as $field) {
            if (!isset($model->{$field})) { continue; }

            $model->{$field} = self::decrypt($model->{$field});
        }

        return $model;
    }

    /**
     * Apply timestamp defaults before insert
     * 
     * @return void
     */
    protected function applyTimestampRulesOnCreate(): void
    {
        $now = date('Y-m-d H:i:s');

        if (property_exists($this, 'createdAt') && empty($this->createdAt)) {
            $this->createdAt = $now;
        }

        if (property_exists($this, 'modifiedAt') && empty($this->modifiedAt)) {
            $this->modifiedAt = $now;
        }
    }

    /**
     * Apply timestamp defaults before update
     * 
     * @return void
     */
    protected function applyTimestampRulesOnUpdate(): void
    {
        if (property_exists($this, 'createdAt')) {
            $this->skipAttributesOnUpdate(['createdAt']);
        }

        if (property_exists($this, 'modifiedAt')) {
            $this->modifiedAt = date('Y-m-d H:i:s');
        }
    }

    /**
     * Convert array to model parameters
     * 
     * @param array $parameters Parameters to convert
     * 
     * @return array|null
     */
    private static function arrayToParameters(array $parameters): ?array {
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
        return Di::getDefault()->getShared('security');
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
    private static function injectActiveParameter($parameters = null): array
    {
        if ($parameters === null) {
            $parameters = [];
        }

        $activeField = self::ACTIVE_FIELD;

        $m_class = static::class;

        if (property_exists($m_class, $activeField)) {
            $inserted = false;
            if (isset($parameters[0]) && strpos($parameters[0], $activeField) === false) {
                $existing = trim((string) $parameters[0]);
                if ($existing === '') {
                    $parameters[0] = "$m_class.$activeField = :injectedActive:";
                } else {
                    $parameters[0] .= " AND $m_class.$activeField = :injectedActive:";
                }

                $inserted = true;
            } else if (
                !isset($parameters[0]) &&
                (!isset($parameters['conditions']) || strpos($parameters['conditions'], $activeField) === false)
            ) {
                $existing = isset($parameters['conditions']) ? trim((string) $parameters['conditions']) : '';
                if ($existing === '') {
                    $parameters['conditions'] = "$m_class.$activeField = :injectedActive:";
                } else {
                    $parameters['conditions'] = $existing . ' AND ' . "$m_class.$activeField = :injectedActive:";
                }

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