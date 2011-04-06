<?php

use org\frameworkers\furnace\auth\Auth;
/**
 * Example Database-driven Authentication using the Flame ORM driver
 * 
 */
Auth::init(new org\frameworkers\flame\AuthenticationProvider(),
	array(
	  /***
	   * The fully qualified class name of the Flame object  to use
	   * when storing / representing authenticated users
	   **/
	  "modelClass"       => "\models\User",
	  /***
	   * The attribute to interpret as the username for users
	   **/
      "identityAttr"     => "username",
	  /***
	   * The attribute to interpret as the password for users
	   **/
	  "credentialAttr"   => "password",
	  /***
	   * The attribute to interpret as the unique identifier 
	   * for users. In some cases this may be the same as the
	   * identityAttr, but in all cases this must be specified
	   **/
	  "identifierAttr"   => "id",
	
	  /*** 
	   * The following will be used to salt the encryption method used
	   * to encrypt user passwords on account creation 
	   **/
	  "passwordSalt"     => ""
    )
);

