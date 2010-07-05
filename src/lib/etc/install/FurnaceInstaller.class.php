<?php
class FurnaceInstaller {
    
    
    // Ensure that the provided actual version of PHP is greater than 
    // or equal to the provided minimum version of PHP
    public static function phpOK($min_version,$actual_version) {
        if ($actual_version[0] >= $min_version[0]) {
            if (empty($min_version[2]) || $min_version[2] == '*') {
                return true;
            } else if ($actual_version[2] >= $min_version[2]) {
                if (empty($min_version[4]) || $min_version[4] == '*' || $actual_version[4] >= $min_version[4]) {
                    return true;
                }
            }
        }
        return false;
    }
    
    public function __construct() {
        
    }
    
    
}
?>