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

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Resultset\Simple AS PhalconSimple;

class Simple extends PhalconSimple
{
    public function import(PhalconSimple $object)
    {
        foreach (get_object_vars($object) as $key => $value) {
            $this->{$key} = $value;
        }
    } 

    public function setRow(Model $row) {
        $array = $row->toArray();

        $this->rows[] = $array;
    }
}