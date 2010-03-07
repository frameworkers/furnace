<?php
// Renders an HTML list from the provided data array
class MenuListHelper extends FPageFragment {
    
    private $menuItems;
    private $id;
    private $class;
    
    public function __construct($controller,$menuItems = array()) {
        parent::__construct($controller,dirname(__FILE__));
        $this->menuItems = $menuItems;
        
        $this->id    = '';
        $this->class = '';
    }
    
    public function setDomId($id) {
        $this->id = $id;
    }
    
    public function setCssClass($className) {
        $this->class = $className;
    }
    
    public function render() {

        $menu = "<ul id=\"{$this->id}\" class=\"{$this->class}\">";
        foreach ($this->menuItems as $mi) {
            // Determine if this is the active top-level menu item
            $active = (_furnace()->request == $mi[1])
                ? 'class="active"'
                : '';
            // Draw the item and any nested sub items
            $menu .= "<li {$active}><a href=\"{$mi[1]}\" title=\"{$mi[2]}\">{$mi[0]}</a>";
            // Handle nested menus
            if (is_array($mi[3]) && count($mi[3]) > 0) {
                $menu .= '<ul>';
                foreach ($mi[3] as $nmi) {
                    $menu .= "<li><a href=\"{$nmi[1]}\" title=\"{$nmi[2]}\">{$nmi[0]}</a>";
                }
                $menu .= '</ul>';
            }
            $menu .= '</li>';
        }
        $menu .= '</ul>';
        
        return $menu;
    }
}
?>