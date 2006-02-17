<?php

/**
 * $Id: epConfigurableWithLog.php,v 1.4 2005/10/19 02:03:02 nauhygon Exp $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/10/19 02:03:02 $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */ 

/**
 * Need {@link epConfigurable} as the supper class
 */
include_once(EP_SRC_BASE.'/epConfigurable.php');

/**
 * Need the logging facility, {@link epLog}
 */
include_once(EP_SRC_BASE.'/epLog.php');

/** 
 * Exception class for {@link epConfigurableWithLogger}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/10/19 02:03:02 $
 * @package ezpdo
 * @subpackage ezpdo.base
 */
class epExceptionConfigurableWithLog extends epException {
}

/** 
 * Class of ezpdo configurable objects with logging facility
 * 
 * Class of a configurable object that is also hooked up to the 
 * logging facility. You can simply call {@log()} to log anything
 * you'd like. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/10/19 02:03:02 $
 * @package ezpdo
 * @subpackage ezpdo.base
 * @see epConfigurable
 */
class epConfigurableWithLog extends epConfigurable {
    
    /**
     * The cached logger instance
     */
    protected $logger = false;
    
    /**
     * Constructor
     * @param epConfig|array|null
     */
    public function __construct($config = null) { 
        parent::__construct($config);
    }
    
    /**
     * Implement abstract method {@link epConfigurable::defConfig()}
     * @access public
     */
    public function defConfig() {
        
        return array(
            'log' => array(
                "console" => array(
                    "enabled" => false, 
                    ),
                "file" => array(
                    "enabled" => false, 
                    "log_file" => EP_LOG.'/'.epLog::DEF_LOG_FILE,
                    ), 
                "db" => array(
                    "enabled" => false, 
                    "dsn" => "mysql://ezpdo:secret@localhost/ezpdo",
                    "log_table" => epLog::DEF_LOG_TABLE,
                    ), 
                )
            );
    }
    
    /**
     * Logging
     * @param string $msg
     * @param integer $level the log level
     * @see epLog
     */
    public function log($msg, $level = epLog::LOG_INFO) {
        
        // check if we have initialized the logger
        if (!$this->logger) {
            
            // get the logger (singleton) instance
            if (!($this->logger = & epLog::instance())) {
                throw new epExceptionConfigurableWithLog('Cannot instantiate logger');
                return false;
            }
            
            // set log config for the first time
            $this->_setLogConfig();
        }
        
        // set the logging caller's identity (class name)
        $this->logger->setIdent(get_class($this));
        
        // log the message
        $this->logger->log($msg, $level);
    }
    
    /**
     * Set logger config
     * @return bool
     * @see epLog
     */
    protected function _setLogConfig() {
        
        // check logger instance
        if (!$this->logger) {
            if (!($logger = & epLog::instance())) {
                throw new epExceptionConfigurableWithLog('Cannot instantiate logger');
                return false;
            }
        }
    
        // set log config
        $this->logger->setConfig($this->getConfig());
        
        // something wrong
        return false;
    }
    
}

?>