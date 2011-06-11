<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @copyright  Copyright (c) 2008-2011, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */

namespace furnace\persistance\database;


class ActiveRecord {

    protected $select_fields;


    public function select ($select = '*', $escape = NULL) {

        if (is_string($select)) {
            $select = explode(',', $select);
        }

        foreach ($select as $field) {
            $field = trim($field);

            if ($field != '') {
                $this->select_fields[] = $val;
            }
        }
        return $this;
    }

    public function from ($from) {

    }

}
