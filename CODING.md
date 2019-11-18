# Coding and features

## Constants:

 * __EXTRA_SETUP__
 
   Contains default configurations for various object types
   
 * __DEFAULT_DEFN__
 
   Default definition for class - gets merged in with the class specific configuration - defines locations for core admin css and javascript
   
## Class attributes:

### Template functionality:

 * __$switchers__ []
 
   For each named template - this function returns one of three values:
   
     * _nothing_ - continue with the pre/template/post functions for this template;
     * "" - template is ignored;
     * "_name_" - switch to template _name_.
    
 * __$preprocessors__ []
 
 
 * __$temlpates__ []
 
 
 * __$postprocessors__ []
 
 
 * __$array_methods__ []
 
 
 * __$scalar_methods__ []
 
 
 * __$date_format__
 
 
 * __$range_format__ []

### Other functionality 
 
 * __$debug__
 
   If true $this->show_error function pushes error message to the screen as well as the error_log

 * __$custom_types__ []
 
   Associate array of all the custom types defined...
   
    * _key_: type code
    
    * _value_: [ 'icon' => _icon-name_, 'name' => _singular_name_, 'names' => _plural_name_
    
    