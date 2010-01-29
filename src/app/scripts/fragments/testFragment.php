<?php
class TestFragment extends FPageFragment {
    
    // Usage:
    // This is the simplest example of a fragment. It returns a constant string.
    // Fragments are php scripts and thus can be arbitrarily complex. The result
    // of the "render()" function will be directly output to the view as a
    // replacement for the FPageTemplate tag that invoked this fragment.
    //
    // html: <span>Greeting: [fragment:test]</span>
    // NOTE: 'Fragment' will be appended to the value provided in the FPageTemplate
    //       tag to form the full class name of the desired fragment. So, to 
    //       access this fragment (TestFragment), the correct FPageTemplate tag is
    //       simply 'test', as above.
    // 
    // Generates: <span>Greeting: Hello, world. This is a test</span>
    
    public function __construct() {
        
    }
    
    public function render() {
        return "Hello, world. This is a test.";
    }
}
?>