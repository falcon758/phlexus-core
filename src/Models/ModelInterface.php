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

use Phalcon\Mvc\Model as PhalconModel;

interface ModelInterface
{
    /**
     * Get encrypt fields
     * 
     * @return array Fields
     */
    public static function getEncryptFields() : array;
}