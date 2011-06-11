<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @subpackage core
 * @copyright  Copyright (c) 2008-2011, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */

namespace furnace\persistance\database\providers\pdo;

class PdoProvider extends \PDO {

    protected $connection;
    protected $queries;

    public function __construct( $info ) {

        $this->queries    = array();
        $this->connection = new \PDO("{$info['driver']}:host={$info['host']};dbname={$info['db']}",
            $info['username'],
            $info['password'],
            $info['options']);
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function connect( $info ) {
        
        if (null == self::$connection) {

            self::$connection = new PdoProvider( $info );   
            
        }
    }

    public function disconnect() {

        self::$connection = null;

    }

    public function query( $query, $data, $returnObject = true ) {

    }

    public function raw() {
        return $this->connection;
    }

    public function __get($name) {
        return $this->$name();
    }


}


