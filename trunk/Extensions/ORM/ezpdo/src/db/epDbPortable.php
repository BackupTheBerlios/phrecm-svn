<?php

/**
 * $Id: epDbPortable.php,v 1.13 2005/12/14 21:54:48 nauhygon Exp $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1.13 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */

/**
 * Need field type definition
 */
include_once(EP_SRC_ORM . '/epClassMap.php');

/**
 * Class to handle database portability
 * 
 * This class takes care of the database portability issues. 
 * Databases of different vendors are notoriously known 
 * not to follow the standard ANSI SQL. This class provides 
 * methods to resolve differences among different SQLs. 
 * 
 * This class is by no means a panacea to all the portability
 * issues, but only those concern EZPDO. And the class is 
 * designed to be "static" and should not interact with the 
 * database directly. All actual database interactions 
 * (queries) are done through {@link epDb}. 
 * 
 * Most ideas are from ADODb author John Lim's blog 
 * ({@link http://phplens.com/phpeverywhere/?q=node/view/177}) 
 * and the slides from PEAR:DB's author Daniel Convissor 
 * ({@link http://www.analysisandsolutions.com/presentations/portability/slides/toc.htm}). 
 * Credits are due to the two development teams. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1.13 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbPortable {

    /**
     * Generate SQL code to create table
     * 
     * Sometimes extra procedure may be needed for some fields to work. 
     * For example, some database does not have a direct auto-incremental 
     * keyword and, to make it work, you need extra procedure after 
     * creating the table. Here is an example, for a table column in 
     * an Oracle database to be auto-incremental, we need to insert 
     * a sequence and a trigger (See this link for more info 
     * {@link http://webxadmin.free.fr/article.php?i=134}). If this is 
     * the case the subclass should override this method and return both
     * "create table" statement and the extra.
     * 
     * @param epClassMap $cm
     * @param epDb $db
     * @param string $indent
     * @return string|array (of strings)
     */
    public function createTable($cm, $db, $indent = '  ') {
        
        // start create table
        $sql = "CREATE TABLE " . $db->quoteId($cm->getTable()) . " (\n";
        
        // the oid field
        $fstr = $this->_defineField($db->quoteId($cm->getOidColumn()), 'integer', '12', false, true);
        $sql .= $indent . $fstr . ",\n";

        // write sql for each field
        foreach($cm->getAllFields() as $fname => $fm) {
            if ($fm->isPrimitive()) {
                // get the field definition
                $fstr = $this->_defineField(
                    $db->quoteId($fm->getColumnName()),
                    $fm->getType(),
                    $fm->getTypeParams(),
                    $fm->getDefaultValue(),
                    false
                    );

                $sql .= $indent . $fstr . ",\n";
            }
        }
        
        // write primary key
        $sql .= $indent . "PRIMARY KEY (" . $db->quoteId($cm->getOidColumn()) . ")\n";
        
        // end of table creation
        $sql .= ");\n";
        
        return $sql;
    }

    /**
     * SQL to drop table 
     * @param string $table
     * @param epDb $db
     * @return string
     */
    public function dropTable($table, $db) {
        return 'DROP TABLE IF EXISTS ' . $db->quoteId($table) . ";\n";
    }
    
    /**
     * SQL to truncate (empty) table 
     * @param string $table
     * @param epDb $db
     * @return string
     */
    public function truncateTable($table, $db) {
        return 'TRUNCATE TABLE  ' . $db->quoteId($table) . ";\n";
    }

    /**
     * Return column/field definition in CREATE TABLE (called by 
     * {@link createTable()})
     * 
     * @param string $fname 
     * @param string $type
     * @param string $params
     * @param string $default
     * @param bool $autoinc
     * @return false|string 
     */
    protected function _defineField($fname, $type, $params = false, $default = false, $autoinc = false, $notnull = false) {
        
        // get field name and type(params)
        $sql = $fname . ' ' . $this->_fieldType($type, $params);
        
        // does the field have default value?
        if ($default) {
            $sql .= ' DEFAULT ' . $default;
        }
        
        // is it not null?
        if ($notnull || $autoinc) {
            $sql .= ' NOT NULL';
        }
        
        // is it an auto-incremental?
        if ($autoinc) {
            $sql .= ' AUTO_INCREMENT';
        }
        
        return $sql;
    }

    /**
     * Translate EZPDO datatype to the field type 
     * @param string $ftype 
     * @param string $params 
     * @return false|string
     */
    protected function _fieldType($ftype, $params = false) {
        
        switch($ftype) {
            
            case epFieldMap::DT_BOOL:
            case epFieldMap::DT_BOOLEAN:
            case epFieldMap::DT_BIT:
                // let's make it simple - one-byte integer
                // return "int(1)";
                return "boolean";

            case epFieldMap::DT_DATE:
            case epFieldMap::DT_TIME:
            case epFieldMap::DT_DATETIME:
                // currently date/time/datetime are all mapped to integer 
                // this should be changed once we work out unixDate() and 
                // dbDate() (as in ADODB)
                return "int(16)";

            case epFieldMap::DT_CLOB:
                $ftype = epFieldMap::DT_TEXT;

            default:
                // concat params
                if ($params) {
                    $ftype .= '(' . $params . ')';
                }
        }

        return $ftype;
    }
}

/**
 * Exception class for epDbPortFactory
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1.13 $ $Date: 2005/12/14 21:54:48 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epExceptionDbPortFactory extends epException {
}

/**
 * Class of database portability factory
 * 
 * The factory creates one portability object for each database type
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1.13 $ $Date: 2005/12/14 21:54:48 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbPortFactory implements epFactory, epSingleton  {
    
    /**#@+
     * Used for return value to avoid reference notice in 5.0.x and up
     * @var bool
     */
    static public $false = false;
    static public $true = true;
    static public $null = null;
    /**#@-*/

    /**
     * db portabilities created
     * @var array
     */
    private $dbps = array();
    
    /**
     * Constructor
     */
    private function __construct() { 
    }
    
    /**
     * Implements factory method {@link epFactory::make()}
     * @param string $dbtype
     * @return epDbPortable|null
     * @access public
     * @static
     */
    public function &make($dbtype) {
        return $this->get($dbtype, false); // false: no tracking
    }

    /**
     * Implement factory method {@link epFactory::track()}
     * @param string $dbtype
     * @return epDbPortable
     * @access public
     */
    public function &track() {
        $args = func_get_args();
        return $this->get($args[0], true); // true: tracking
    }
    
    /**
     * Either create db portability object or find one
     * @param $dbtype
     * @param bool tracking or not
     * @return epDbPortable
     * @throws epExceptionDbPortFactory
     */
    private function & get($dbtype, $tracking = false) {
        
        // check if dsn is empty 
        if (empty($dbtype)) {
            throw new epExceptionDbPortFactory('Database type is empty');
            return self::$null;
        }
        
        // check if class map has been created
        if (isset($this->dbps[$dbtype])) {
            return $this->dbps[$dbtype];
        }
        
        // check if it's in tracking mode
        if ($tracking) {
            return self::$null;
        }
        
        // instantiate the right db port object
        $port_class = 'epDbPort' . $dbtype;
        if (!file_exists($port_class_file = EP_SRC_DB . '/port/' . $port_class . '.php')) {
            // in case we don't have a special portability class, use the default
            $dbp = new epDbPortable;
        } else {
            include_once($port_class_file);
            $dbp = new $port_class;
        }
        
        // check if portability object is created successfully
        if (!$dbp) {
            throw new epExceptionDbPortFactory('Cannot instantiate portability class for [' . $dbType . ']');
            return self::$null;
        }

        // cache it
        $this->dbps[$dbtype] = & $dbp;

        return $this->dbps[$dbtype];
    }
    
    /**
     * Implement factory method {@link epFactory::allMade()}
     * Return all db connections made by factory
     * @return array
     * @access public
     */
    public function allMade() {
        return array_values($this->dbps);
    }
    
    /**
     * Implement factory method {@link epFactory::removeAll()}
     * @return void
     */
    public function removeAll() {
        $this->dbps = array();
    }
    
    /**
     * Implements {@link cpSingleton} interface
     * @return epDbPortFactory
     * @access public
     */
    static public function &instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }
    
    /**
     * Implement {@link epSingleton} interface
     * Forcefully destroy old instance (only used for tests). 
     * After reset(), {@link instance()} returns a new instance.
     */
    static public function destroy() {
        self::$instance = null;
    }

    /**
     * epDbPortFactory instance
     */
    static private $instance; 
}

?>