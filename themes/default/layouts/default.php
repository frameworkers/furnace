<?php 

use furnace\core\Config;

?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>

	<!-- Stylesheets -->
	<?php echo load_region('stylesheets')?>

	<!-- Javascripts -->
	<?php echo load_region('javascripts')?>
    
    <title><?php echo Config::Get('app.title') - Config::Get('page.title') ?></title>
    
  </head>
  
  <body>
  
    <?php echo load_region('flashes');?>
    
    <?php echo load_region('content');?>
    
  </body>
  
</html>