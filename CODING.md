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
  
    Template support functions included with [[...]] which act on the element of the structure supplied as an array...
 
    The default list of methods are:
    
      * __size__ - Size of the array
      * __json__ - The array as a jason function
      * __dump__ - Development function - dump datastructure
      * __templates__ - Apply named template to each element of the elements in turn
      * __template__ - Apply named template to an individual element of array or the array itself 
 
    You can extend this array by using the method $this->add_scalar_method( $_key_, _function()_ );
    
 * __$scalar_methods__ []
 
    Template support functions included with [[...]] which act on the element of the structure supplied as an scalar...
    
      * __ucfirst__
      * __uc__
      * __lc__
      * __cr__
      * __hr__
      * __raw__
      * __date__
      * __enc__
      * __rand_enc__
      * __integer__
      * __boolean__
      * __shortcode__
      * __strip__
      * __html__
      * __rand_html__
      * __email__
      * __wp__

    You can extend this array by using the method $this->add_scalar_method( $_key_, _function()_ );
 
 * __$date_format__
 
    Format used for dates included with the __date__ template code, defaults to "F jS Y" e.g. "March 2nd 2010", can change it to any PHP date template see https://www.php.net/manual/en/function.date.php
    
 * __$range_format__ []
 
    Format used for date ranges - this array contains for values - each of which are arrays of two values:
     
     * The elements of the array are used if
         * The start and end dates are in different years
         * The start and end dates are in different months
         * The start and end dates are different
         * The start and end dates are the same.
         
     * Each pair contains a format for the start date, and a format for the end date.

### Other functionality 
 
 * __$debug__
 
    If true $this->show_error function pushes error message to the screen as well as the error_log

 * __$custom_types__ []
 
    Associate array of all the custom types defined...
   
     * _key_: type code
    
     * _value_: [ 'icon' => _icon-name_, 'name' => _singular_name_, 'names' => _plural_name_
    
    