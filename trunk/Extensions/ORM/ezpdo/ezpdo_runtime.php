<?php

/**
 * $Id: ezpdo_runtime.php,v 1.9 2005/11/24 20:47:34 nauhygon Exp $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1.9 $ $Date: 2005/11/24 20:47:34 $
 * @package ezpdo
 * @subpackage base
 */

/**
 * Need basic definitions to src and lib directories
 */
include_once(dirname(__FILE__).'/ezpdo.php');

/**
 * Need persistence manager ({@link epManager}) 
 */
include_once(EP_SRC_RUNTIME.'/epManager.php');

/**
 * Setup class autoloading 
 * 
 * Change it only if it conflicts with your own autoloading method.
 * 
 * @param string $class_name 
 */
if (!function_exists('__autoload')) {
    function __autoload($class_name) {
        epManager::instance()->autoload($class_name);
    }
}

/**
 * Load configuration from a file and set it to the EZPDO manager. 
 * 
 * If config file is not specified, it tries to load config.xml first. 
 * Then config.ini if config.xml not found from the current directory. 
 * 
 * @param string $file
 * @return bool 
 */
function epLoadConfig($file = false) {
    
    // use default config file?
    if (!$file) {
        // try config.ini first
        if (file_exists($file = 'config.ini')) {
            $file = 'config.ini';
        } else if (file_exists('config.xml')) {
            $file = 'config.xml';
        } else {
            return false;
        }
    } else {
        // check if the specified config file exists
        if (!file_exists($file)) {
            return false;
        }
    }
    
    // load the config file
    include_once(EP_SRC_BASE.'/epConfig.php');
    if (!($cfg = & epConfig::load($file))) {
        return false;
    }
    
    // set config to the EZPDO manager
    return epManager::instance()->setConfig($cfg);
}

/**
 * Register auto flush as the shutdown function
 */
register_shutdown_function(array(epManager::instance(), 'autoFlush'));

/**
 * If we have config.xml or config.ini in the current directory, load it 
 * and set it to the manager
 */
epLoadConfig();

?>
