<?php
class errorsController extends Controller {
    
    
    public function noController($suppliedPath) {
        $realSuppliedPath = trim(str_replace('+','/',$suppliedPath),'+');
        $name = str_replace('.php','',substr($realSuppliedPath,strrpos($realSuppliedPath,'/')+1));
        $this->set('controllerName',$name);
        $this->set('controllerFilePath',$realSuppliedPath);   
    }
    
    public function noControllerFunction($suppliedPath,$action) {
        $realSuppliedPath = trim(str_replace('+','/',$suppliedPath),'+');
        $name = str_replace('.php','',substr($realSuppliedPath,strrpos($realSuppliedPath,'/')+1));
        $this->set('controllerName',$name);
        $this->set('controllerFilePath',$realSuppliedPath); 
        
        $this->set('action',$action); 
    }
    
    public function noTemplate($path) {
        $realPath = trim(str_replace('+','/',$path),'+');
        $this->set('path',$realPath);
        $file     = substr($realPath,strrpos($realPath,'/')+1);
        $this->set('file',$file);
    }
}
?>