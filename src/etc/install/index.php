<?php

    /*
     * FURANCE Installer
     * 
     */
    // Load the FurnaceInstaller class
    require_once('./FurnaceInstaller.class.php');

    // Load the Tadpole page template engine
    require_once('../../lib/furnace/facade/tadpole/TadpoleEngine.class.php');
    $tp = new TadpoleEngine();

    // Determine where we are in the installation process
    if (!isset($_GET['step'])) {
        $step = 'welcome';
    } else {
        $step = $_GET['step'];
    }
    
    // Generate some output to the user, depending on the current step
    $stepTitle = '';
    $stepInstructions = '';
    switch ($step) {
        
        
        case 'permissions':
            $stepTitle = 'Configure Permissions';
            $stepInstructions = 'Furnace can do a lot of things for you if you 
            					configure permissions correctly. Please ensure 
            					that all of the conditions listed on the right 
            					are satisfied. ';
            $input  = file_get_contents('./templates/permissions.html');
            $previousStep = 'welcome';
            $nextStep     = '';
            
            $perms = array(
                array('[rootdir]/app/config',is_readable('../../app/config'),is_writeable('../../app/config')),
                array('[rootdir]/app/model',is_readable('../../app/model'),is_writeable('../../app/model'))
            );
            $tp->set('perms',$perms);
            $tp->set('app-rootdir',dirname(dirname(dirname(__FILE__))));
            
            break;        
        case 'welcome':
            $stepTitle = 'Final Setup and Configuration:';
            $stepInstructions = 'There are just a few things that need to be
            					configured before you begin developing your web
            					application with Furnace. This installer will guide
            					you through each step.';
            $components = array(
                array('PHP','5.2',phpversion(),FurnaceInstaller::phpOK('5.2',phpversion()))
            
            );
            $tp->set('components',$components);
            $tp->set('os',php_uname('s'));
            $tp->set('iface',php_sapi_name());
            $previousStep = '';
            $nextStep     = 'permissions';
        default:
            $input = file_get_contents('./templates/welcome.html');
            $output = $tp->compile($input);
    }
    
    $tp->set('stepTitle',$stepTitle);
    $tp->set('stepInstructions',$stepInstructions);
    $output = $tp->compile($input);
    
    $previous = (!empty($previousStep)) ? '<a href="./index.php?step=' . $previousStep . '">Previous</a>' : '';
    $next     = (!empty($nextStep))     ? '<a href="./index.php?step=' . $nextStep     . '">Next</a>'     : '';
    
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Furnace Installer</title>
<link rel="stylesheet" type="text/css" href="../../app/themes/default/css/blueprint/screen.css"/>
<!--[if lt IE 8]><link rel="stylesheet" href="[_theme_]/css/blueprint/ie.css" type="text/css" media="screen, projection"><![endif]-->

</head>

<body>
<div class="container">
    <hr class="space"/>
    <h1><img src="../../app/themes/default/images/furnace-f.png" style="text-align:middle"/>Thank you for using Furnace</h1>
    <hr/>
    <h2>Your installation is almost complete...</h2>
    <hr/>
    <div class="span-7 colborder">
    	<h6><?php echo $stepTitle;?></h6>
    	<p><?php  echo $stepInstructions;?></p>
    	<?php echo $previous;?>
    	<?php echo $next;?>
    
    </div>
    <div class="span-14 last">
        <?php echo $output; ?>
    </div>
</div>
</body>
</html>