<?php
error_reporting(E_ALL | E_STRICT);

$phrecmRoot = realpath(dirname(__FILE__) . '/../');

set_include_path($phrecmRoot . PATH_SEPARATOR . 
 $phrecmRoot  . 'Extensions');

function __autoload($class)
{
    require_once str_replace('_', '/', $class) . '.php';
}    

require_once 'Tools/PropertyManipulator.php';
?>
