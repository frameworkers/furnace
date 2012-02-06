<?php
/**
 * This file is part of the Furnace framework.
 * (c) Frameworkers Software Foundation http://furnace.frameworkers.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Furnace
 * @subpackage response
 * @copyright  Copyright (c) 2008-2012, Frameworkers.org
 * @license    http://furnace.frameworkers.org/license
 *
 */
 
namespace furnace\response;

class JsonResponse extends Response {

  public function finalize() {
    $this->add( json( $this->data )
              ,true); // true because this is the _final_ addition   
  }
  
}
