<?php

/**
 * $Id: epDbObject.php,v 1.59 2005/12/15 03:48:53 nauhygon Exp $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author Trevan Richins <developer@ckiweb.com>
 * @version $Revision: 1.59 $ $Date: 2005/12/15 03:48:53 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */

/**
 * need epClassMap class
 */
include_once(EP_SRC_ORM.'/epClassMap.php');

/**
 * Class of SQL statement generator
 * 
 * This class is responsible for converting class map info 
 * ({@link epClassMap}) into SQL statements
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1.59 $ $Date: 2005/12/15 03:48:53 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epObj2Sql {
    
    /**#@+
     * Used for return value to avoid reference notice in 5.0.x and up
     * @var bool
     */
    static public $false = false;
    static public $true = true;
    static public $null = null;
    /**#@-*/

    /**
     * The cached db portability factory
     * @var epDbPortableFactory
     */
    static public $dbpf = false;
    
    /**
     * Get the portable object
     * @param string $dbtype
     * @return false|epDbPortable
     */
    static protected function &_getPortable($dbtype) {
        
        // check if we have portability factory cached already
        if (!epObj2Sql::$dbpf) {
            include_once(EP_SRC_DB."/epDbPortable.php");
            epObj2Sql::$dbpf = epDbPortFactory::instance();
        }
        
        // get the portability object for the db
        if (!($dbp = & epObj2Sql::$dbpf->make($dbtype))) {
            return self::$false;
        }

        return $dbp;
    }

    /**
     * Makes a SQL create table statement for a class map
     * @param epDbObject $db the db connection 
     * @param epClassMap the class map for the object
     * @return false|string|array
     */
    static public function sqlCreate($db, $cm, $indent = '  ') {
        
        // get the portable
        if (!($dbp = & epObj2Sql::_getPortable($db->dbType()))) {
            return false;
        }

        // call portability object to produce 
        return $dbp->createTable($cm, $db);
    }
    
    /**
     * Makes a SQL drop table if exists statement for a class map
     * @param epDbObject $db the db connection  
     * @param epClassMap the class map for the object
     * @return string
     */
    static public function sqlDrop($db, $cm) {
        
        // get the portable
        if (!($dbp = & epObj2Sql::_getPortable($db->dbType()))) {
            return false;
        }
        
        // call portability object to produce 
        return $dbp->dropTable($cm->getTable(), $db);
    }
    
    /**
     * Makes a SQL truncate table if exists statement for a class map
     * @param epDbObject $db the db connection 
     * @param epClassMap the class map for the object
     * @return string
     */
    static public function sqlTruncate($db, $cm) {
        
        // get the portable
        if (!($dbp = & epObj2Sql::_getPortable($db->dbType()))) {
            return false;
        }

        // call portability object to produce 
        return $dbp->truncateTable($cm->getTable(), $db);
    }
    
    /**
     * Makes a SQL count statement to get the total number rows in table 
     * @param epDbObject $db the db connection  
     * @param epClassMap the class map for the object
     * @return string
     */
    static public function sqlCount($db, $cm) {
        return 'SELECT COUNT(' . $db->quoteId($cm->getOidColumn()) . ') FROM ' . $db->quoteId($cm->getTable());
    }

    /**
     * Makes a SQL select statement from object variables
     * If the object is null, select all from table
     * @param epDbObject $db the db connection  
     * @param epObject the object for query
     * @param epClassMap the class map for the object
     * @param array (of integers) object ids to be excluded
     * @return false|string
     * @author Oak Nauhygon <ezpdo4php@gmail.com>
     * @author Trevan Richins <developer@ckiweb.com>
     */
    static public function sqlSelect($db, $cm, $o = null, $ex = null) {
        
        // !!!important!!! with a large db, the list of oid to be excluded
        // $ex can grown really large and can significantly slow down 
        // queries. so it is suppressed and moved to epDbObject::_rs2obj() 
        // to process.
        $ex = null;

        // arrays to collect 'from' and 'where' parts
        $from = array();
        $where = array();
        
        // add table for the object in 'from'
        $from[] = $db->quoteId($cm->getTable());
        
        // if object is specified, recursively collect 'from' and 'where' 
        // for the select statement 
        if ($o) {
            $from_where = epObj2Sql::sqlSelectChildren($db, $o, 1, $cm->getTable());
            $from = array_merge($from, $from_where['from']);
            $where = array_merge($where, $from_where['where']);
        }
        
        // any oids to exclude?
        if ($ex) {
            // add oids to be excluded (shouldn't get here. see comments above.)
            foreach($ex as $oid) {
                $where[] = $db->quoteId($cm->getOidColumn()) . ' <> ' . $db->quote($oid);
            }
        }
        
        // columns to be selected (*: all of them)
        $columns = $db->quoteId($cm->getTable() . '.*');
        
        // assemble the select statement
        return epObj2Sql::_sqlSelectAssemble($columns, $from, $where);
    }

    /**
     * Assemble a select statement from parts. 
     * Note identifiers and values in all parts should have been properly quoted.
     * @param string|array $columns 
     * @param array $from from expressions
     * @param array $where where expressions ('1=1' if empty) 
     * @return string 
     */
    static protected function _sqlSelectAssemble($columns, $from, $where = array()) {
        
        // the columns clause
        $columns = is_array($columns) ? implode(' ', $columns) : $columns;
        
        // the from caluse
        $from = implode(', ', $from);
        
        // the where clause
        $where = $where ? implode(' AND ', $where) : '1=1';
        
        // put them together
        return 'SELECT '.$columns.' FROM '.$from.' WHERE '.$where;
    }

    /**
     * Make where part of a SQL select to get children values
     * @param epDbObject $db the db connection  
     * @param epObject $o the child object for query
     * @param int $depth how many children down we are
     * @param string $parent the parent of this child
     * @return array('from', 'where')
     * @author Oak Nauhygon <ezpdo4php@gmail.com>
     * @author Trevan Richins <developer@ckiweb.com>
     */
    static public function sqlSelectChildren($db, $o, $depth, $parent) {
        
        // array to keep new tables in 'from'
        $from = array();

        // array to keep new expression in 'where'
        $where = array();
        
        // get the class map for the child object
        $cm = $o->epGetClassMap();
        
        // get all vars in the object
        $vars = $o->epGetVars();
        
        // if object has oid, select use oid
        if ($oid = $o->epGetObjectId()) {
            $where[] = $db->quoteId($cm->getOidColumn()) . ' = ' . $oid; 
            return array('from'=>$from, 'where'=>$where);
        }
        
        // mark child object under search (to avoid loops)
        $o->epSetSearching(true);

        // total number of vars (primitive or non-primitive) collected
        $n = 0;

        // new depth
        $depth ++;

        // number of non-primitive (relationship) fields collected 
        $nprim_id = 0;

        // loop through vars
        while (list($var, $val) = each($vars)) { 
            
            // get field map
            if (!($fm = & $cm->getField($var))) {
                // should not happen
                continue;
            }
            
            // exclude null values (including empty strings)
            if (is_null($val) || (!$val && $fm->getType() == epFieldMap::DT_CHAR)) {
                continue;
            }
            
            // is it a primitive var?
            if ($fm->isPrimitive()) {
                $where[] = $db->quoteId($parent) . '.' . $db->quoteId($fm->getColumnName()) . ' = ' . $db->quote($val, $fm); 
                // done for this var
                $n ++;
                continue;
            }

            // okay we are dealing with a non-primitive (relationship) var
            if ($val instanceof epArray) {

                foreach ($val as $obj) {

                    // skip object that is under searching
                    if (!$obj || $obj->epIsSearching()) {
                        continue;
                    }
                    
                    // get 'where' and 'from' from relationship 
                    $from_where = epObj2Sql::sqlSelectRelations(
                        $db, $fm, $cm, $obj->epGetClassMap()->getTable(), 
                        $depth.$nprim_id, $parent
                        );
                    $where = array_merge($where, $from_where['where']);
                    $from = array_merge($from, $from_where['from']);

                    // get 'where' and 'from' from relationship 
                    $from_where = epObj2Sql::sqlSelectChildren($db, $obj, $depth, '_'.$depth.$nprim_id);
                    $where = array_merge($where, $from_where['where']);
                    $from = array_merge($from, $from_where['from']);
                    
                    $nprim_id++;
                }

            } else if ($val instanceof epObject && !$val->epIsSearching()) {
                
                // get 'where' and 'from' from relationship 
                $from_where = epObj2Sql::sqlSelectRelations(
                    $db, $fm, $cm, $val->epGetClassMap()->getTable(), 
                    $depth.$nprim_id, $parent
                    );
                $where = array_merge($where, $from_where['where']);
                $from = array_merge($from, $from_where['from']);

                // get 'where' and 'from' from relationship 
                $from_where = epObj2Sql::sqlSelectChildren($db, $val, $depth, '_'.$depth.$nprim_id);
                $where = array_merge($where, $from_where['where']);
                $from = array_merge($from, $from_where['from']);

                $nprim_id++;
            }

            $n ++;
        } 
        
        // reset search flag on child object
        $o->epSetSearching(false);

        return array('from' => $from, 'where' => $where);
    }

    /**
     * Make where part of a SQL select for relationship fields
     * @param epDbObject $db the db connection  
     * @param epFieldMap $fm the field map
     * @param epClassMap $cm the child object for query
     * @param string $alias the alias of this table in the previous part
     * @return array('from', 'where')
     * @author Oak Nauhygon <ezpdo4php@gmail.com>
     * @author Trevan Richins <developer@ckiweb.com>
     */
    static public function sqlSelectRelations($db, $fm, $cm, $table, $alias, $parentTable) {

        $base_a = $fm->getClassMap()->getName();
        $class_a = $cm->getName();
        $var_a = $fm->getName();
        $base_b = $fm->getClass();

        // call manager to get relation table for base class a and b
        $rt = epManager::instance()->getRelationTable($base_a, $base_b);
        
        // the alias of the table we are dealing with right now
        $tbAlias = '_'.$alias;
        $rtAlias = 'rt'.$alias;
        
        // quoted aliases (avoid repeating)
        $tbAlias_q = $db->quoteId($tbAlias);
        $rtAlias_q = $db->quoteId($rtAlias);
        
        // compute 'from' parts: tables with aliases
        $from = array();
        $from[] = $db->quoteId($table) . ' AS '.$tbAlias_q;
        $from[] = $db->quoteId($rt) . ' AS '.$rtAlias_q;

        // compute expressions 'where'
        $where = array();
        
        // rt.class_a = 
        $where[] = $rtAlias_q.'.'.$db->quoteId('class_a').' = '.$db->quote($class_a);
        
        // rt.var_a = 
        $where[] = $rtAlias_q.'.'.$db->quoteId('var_a').' = '.$db->quote($var_a);
        
        // rt.base_b =
        $where[] = $rtAlias_q.'.'.$db->quoteId('base_b').' = '.$db->quote($base_b);
        
        // rt.class_b =  TODO: doesn't look like it is used
        //$where .= 'rt.'.$db->quoteId('class_b') . ' = ' . $db->quote($val->getClass());
        
        // A.oid = rt.oid_a
        $where[] = $db->quoteId($parentTable).'.'.$db->quoteId($cm->getOidColumn()).' = ' . $rtAlias_q.'.'.$db->quoteId('oid_a');
        
        // Child.oid = rt.oid_b
        $where[] = $tbAlias_q.'.'.$db->quoteId($fm->getClassMap()->getOidColumn()).' = ' . $rtAlias_q.'.'.$db->quoteId('oid_b');
        
        return array('from' => $from, 'where' => $where);
    }
    
    /**
     * Make a SQL insert statement from object variables
     * @param epDbObject $db the db connection 
     * @param epObject the object for query
     * @param epClassMap the class map for the object
     * @return false|string
     */
    static public function sqlInsert($db, $cm, $o) {
        
        // get all vars
        if (!($vars = $o->epGetVars())) {
            return false;
        }
        
        // make select statement
        $sql = 'INSERT INTO ' . $db->quoteId($cm->getTable()) . ' (' ; 

        // get column names
        $i = 0; 
        while (list($var, $val) = each($vars)) {
            
            // exclude 'oid'
            if ($var == 'oid') {
                continue;
            }

            // shouldn't happen
            if (!($fm = $cm->getField($var))) {
                continue;
            }
            
            // exclude non-primitive fields
            if (!$fm->isPrimitive()) {
                continue;
            }
            
            $sql .= $db->quoteId($fm->getColumnName()) . ', '; 
            
            $i ++;
        } 
        
        // no need to insert if we don't have any var to insert
        if ($i == 0) {
            $sql .= $db->quoteId($cm->getOidColumn()) . ') VALUES (' . $db->quote('', $fm) . ');';
            return $sql;
        }
        
        // remove the last ', '
        if ($i > 0) {
            $sql = substr($sql, 0, strlen($sql) - 2);
        } 
        
        $sql .= ') VALUES ('; 
        
        // get values
        $i = 0;
        reset($vars);
        while (list($var, $val) = each($vars)) {
            
            // exclude 'oid'
            if ($var == 'oid') {
                continue;
            }

            if (!($fm = & $cm->getField($var))) {
                continue;
            }
            
            // exclude non-primitive fields
            if (!$fm->isPrimitive()) {
                continue;
            }
            
            // get quoted field value
            $sql .= $db->quote($val, $fm) . ', '; 
            
            ++ $i;
        }   
        
        // remove the last ', '
        if ($i > 0) {
            $sql = substr($sql, 0, strlen($sql) - 2);
        }
        
        // end of statement
        $sql .= ');'; 
        
        return $sql;
    }
    
    /**
     * Make a SQL delete statement from object variables
     * @param epDbObject $db the db connection 
     * @param epObject the object for query
     * @param epClassMap the class map for the object
     * @return false|string
     */
    static public function sqlDelete($db, $cm, $o) {
        
        // get all vars
        $vars = $o->epGetVars();
        if (!$vars) {
            return false;
        }
        
        // delete row with the object id
        $sql = 'DELETE FROM ' . $db->quoteId($cm->getTable()) . ' WHERE ' . $db->quoteId($cm->getOidColumn()) . ' = ' . $o->epGetObjectId();
        
        return $sql;
    }
    
    /**
     * Make a SQL update statement from object variables
     * @param epObject the object for query
     * @param epClassMap the class map for the object
     * @return false|string
     */
    static public function sqlUpdate($db, $cm, $o) {
        
        // get the modified vars
        $vars = $o->epGetModifiedVars();
        if (!$vars) {
            return false;
        }
        
        $sql = 'UPDATE ' . $db->quoteId($cm->getTable()) . ' SET '; 
        $i = 0; 
        while (list($var, $val) = each($vars)) { 
            
            // get field map
            if (!($fm = & $cm->getField($var))) {
                // should not happen
                continue;
            }
            
            // exclude 'oid'
            if ($fm->getName() == 'oid') {
                continue;
            }
            
            // exclude non-primitive fields
            if (!$fm->isPrimitive()) {
                continue;
            }
            
            // get column name
            $sql .= $db->quoteId($fm->getColumnName()) . '=' . $db->quote($val, $fm) . ', '; 
            
            $i ++;
        } 
        
        if ($i == 0) {
            return false;
        }
        
        // remove the last ', '
        if ($i > 0) {
            $sql = substr($sql, 0, strlen($sql) - 2);
        }
        
        $sql .= ' WHERE ' . $db->quoteId($cm->getOidColumn()) . ' = ' . $o->epGetObjectId(); 
        
        return $sql; 
    }
    
    /**
     * Makes a SQL comment for a table (class map)
     * @param epClassMap the class map for the object
     * @return string
     */
    static public function sqlTableComments($db, $cm) {
        $sql = "\n";
        $sql .= "-- \n";
        $sql .= "-- Table for class " . $cm->getName() . "\n";
        $sql .= "-- Source file: " . $cm->getClassFile() . "\n";
        $sql .= "-- \n\n";
        return $sql;
    }
    
}

/**
 * Exception class for {@link epDbObject}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1.59 $ $Date: 2005/12/15 03:48:53 $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */
class epExceptionDbObject extends epException {
}

/**
 * Class for object operations with databases
 * 
 * This class provides a layer between the database access (i.e. 
 * {@link epDb}) and the persisent objects. 
 * 
 * It translates persistence-related operations into SQL statements 
 * and executes them by calling {@link epDb::execute()}. 
 * 
 * It implements the two-way conversions, from database rows to 
 * persistent objects, and vice versa. 
 * 
 * It also supports table-level operations for mapped classes - 
 * table creation, table dropping, and emptying. 
 * 
 * Objects of this class is managed by the db factory, {@link 
 * epDbFactory}. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1.59 $ $Date: 2005/12/15 03:48:53 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbObject {

    /**#@+
     * Used for return value to avoid reference notice in 5.0.x and up
     * @var bool
     */
    static public $false = false;
    static public $true = true;
    static public $null = null;
    /**#@-*/
    
    /**
     * The reference to the epDb object that connects to the database
     * @var epDb
     */
    protected $db;

    /**
     * Last inserted table
     * @var string
     */
    protected $table_last_inserted = false;
    
    /**
     * Whether to check if table exists before db operation
     * @var boolean
     */
    protected $check_table_exists = true;

    /**
     * The cached manager
     * @var epManager
     */
    protected $ep_m = false;
    
    /**
     * Constructor
     * @param epDb
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Destructor
     * Close db connection
     */
    public function __destruct() {
         $this->db->close();
    }

    /**
     * Returns whether to check a table exists before any db operation
     * @param boolean $v (default to true)
     */
    public function getCheckTableExists() {
        return $this->check_table_exists;
    }

    /**
     * Sets whether to check a table exists before any db operation
     * @param boolean $v (default to true)
     */
    public function setCheckTableExists($v = true) {
        $this->check_table_exists = $v;
    }

    /**
     * Return the database type defined in {@link epDb}
     * @return string
     */
    public function dbType() {
        return $this->db->dbType();
    }

    /**
     * Return the db connection (epDb)
     * @return epDb
     */
    public function &connection() {
        return $this->db;
    }
    
    /**
     * Fetchs objects using the variable values specified in epObject
     * If the object is null, get all objects in table. 
     * @param array $cms an array of epClassMap
     * @param array $sql_stmts an array of SQL statements
     * @param string $aggr_func
     * @param string $orderby
     * @param string $limit
     * @return false|array
     */
    public function &query($cms, $sql_stmts, $orderby = false, $limit = false, $aggr_func = false) {
        if ($aggr_func) {
            return $this->_queryAggrFunc($sql_stmts, $aggr_func);
        }
        return $this->_queryObjects($sql_stmts, $cms, $orderby, $limit);
    }

    /**
     * Fetchs objects using the variable values specified in epObject
     * If the object is null, get all objects in table. 
     * @param string $sql
     * @param array $cms an array of epClassMap
     * @param string $orderby
     * @param string $limit
     * @return false|array
     */
    protected function _queryObjects($sql_stmts, $cms, $orderby, $limit) {
        
        $result = array();
        foreach ($sql_stmts as $index => $sql_stmt) {
            
            // execute sql stmt
            if (!$this->_execute($sql_stmt)) {
                return self::$false;
            }
            
            // result conversion
            if ($r = & $this->_rs2obj($cms[$index])) {
                $result = array_merge($result, $r);
            }
        }
        
        /*
         * Please note that the order by doesn't work with child variables
         * ie. order by contact.phone asc. 
         */
        if ($orderby) {
            
            // take off the 'order by'
            $orderby = trim(substr(trim($orderby), strlen('order by')));
            
            // get all the different orders
            $orders = explode(',', $orderby);
            
            foreach ($orders as $order) {
                
                // break order into into parts to get the corresponding var name
                $parts = explode(' ', trim($order));
                
                // get the variable
                $var = explode('.', $parts[0]);
                
                // remove the quotes
                $this->orderBy = substr($var[1], 1, -1);
                
                // check for oid column
                if ($cms[0]->getOidColumn() == $this->orderBy) {
                    $this->orderBy = 'oid';
                }
                
                if (isset($parts[1])) {
                    $this->orderDir = strtolower($parts[1]);
                }
                
                usort($result, array($this, '__sort'));
            }
        }
        
        if ($limit) {
            // making sure there is on extra white space
            $limit = trim(substr(trim($limit), strlen('limit')));
            $parts = explode(',', $limit);
            if (count($parts) == 2) {
                $amount = $parts[1];
                $offset = $parts[0];
            } else {
                $amount = $parts[0];
                $offset = 0;
            }
            $result = array_slice($result, $offset, $amount);
        }

        return $result;
    }

    /**
     * Sorts two objects 
     * @param epObject $a
     * @param epObject $b
     */
    private function __sort($a, $b) {
        
        // asc or desc?
        if ($this->orderDir == 'desc') {
            $result = -1;
        } else {
            $result = 1;
        }
        
        // numeric
        if (is_numeric($a->{$this->orderBy})) {
            
            // a == b
            if ($a->{$this->orderBy} == $b->{$this->orderBy}) {
                return 0;
            }
            
            // a < b
            if (($a->{$this->orderBy} < $b->{$this->orderBy})) {
                return $result * -1;
            }
            
            // a > b
            return $result * 1;
        } 
        
        // string (use strcmp)
        return $result*strcmp($a->{$this->orderBy}, $b->{$this->orderBy});
    }

    /**
     * Execute queries with aggregate functions
     * @param array $cms an array of class maps (epClassMap)
     * @param array $sql_stmts an array of SQL statements
     * @param string $aggr_func
     * @return false|array
     */
    protected function _queryAggrFunc($sql_stmts, $aggr_func)    {
        
        // it is a single sql stmt?
        if (1 == count($sql_stmts)) {
            return $this->_queryAggrFunc1($sql_stmts[0], $aggr_func);
        }
        
        // special treatment for average func
        if (0 === stripos($aggr_func, 'AVG(')) {
            // aggreate function: AVG()
            return $this->_queryAggrFuncAverage($sql_stmts, $aggr_func);
        }
        
        // simple aggregate functions: COUNT, MAX, MIN, SUM
        return $this->_queryAggrFuncSimple($sql_stmts, $aggr_func);
    }

    /**
     * Execute a single SQL stmt with aggregate function 
     * @param array $cms an array of class maps (epClassMap)
     * @param array $sql_stmts an array of SQL statements
     * @param string $aggr_func
     * @return false|array
     */
    protected function _queryAggrFunc1($sql_stmt, $aggr_func) {

        // execute sql
        if (!$this->_execute($sql_stmt)) {
            return self::$false;
        }

        // are we dealing with an aggregation function
        return $this->_rs2aggr($aggr_func);
    }

    /**
     * Execute queries with simple aggregate functions (COUNT, MIN, MAX, SUM)
     * @param array $cms an array of class maps (epClassMap)
     * @param array $sql_stmts an array of SQL statements
     * @param string $aggr_func
     * @return false|array
     */
    protected function _queryAggrFuncSimple($sql_stmts, $aggr_func) {
        
        $result = null;
        foreach ($sql_stmts as $index => $sql_stmt) {
            
            // execute single sql stmt with aggregate func
            try {
                $r = $this->_queryAggrFunc1($sql_stmt, $aggr_func);
            }
            catch(Exception $e) {
                $r = null;
            }
            if (is_null($r)) {
                continue;
            }


            // collect results according to aggregate function
            if (0 === stripos($aggr_func, 'COUNT') || 0 === stripos($aggr_func, 'SUM')) {
                $result = is_null($result) ? $r : ($result + $r);
            }
            else if (0 === stripos($aggr_func, 'MIN')) {
                $result = is_null($result) ? $r : min($result, $r);
            }
            else if (0 === stripos($aggr_func, 'MAX')) {
                $result = is_null($result) ? $r : max($result, $r);
            }
        }
        
        return $result;
    }

    /**
     * Execute queries with aggregate function AVG (special treatment)
     * @param array $sql_stmts an array of SQL statements
     * @param string $aggr_func
     * @return false|array
     */
    protected function _queryAggrFuncAverage($sql_stmts, $aggr_func) {
        
        // sum stmts
        $sum_func = str_ireplace('AVG(', 'SUM(', $aggr_func);
        $sql_stmts_sum = array();
        foreach($sql_stmts as $sql_stmt) {
            $sql_stmts_sum[] = str_replace($aggr_func, $sum_func, $sql_stmt);
        }
        $sum = $this->_queryAggrFuncSimple($sql_stmts_sum, $sum_func);
        
        // count stmts
        $count_func = 'COUNT(*)';
        $sql_stmts_count = array();
        foreach($sql_stmts as $sql_stmt) {
            $sql_stmts_count[] = str_replace($aggr_func, $count_func, $sql_stmt);
        }
        $count = $this->_queryAggrFuncSimple($sql_stmts_count, $count_func);
        
        return $sum / $count;
    }

    /**
     * Returns the total number of stored object in a class
     * @param epClassMap
     * @return false|integer
     */
    public function count($cm) {
        
        // preapre sql statement
        if (!($sql = epObj2Sql::sqlCount($this, $cm))) {
            return false;
        }
        
        // execute sql
        if (($r = $this->_execute($sql)) === false) {
            return false;
        }
        
        // check query result
        $this->db->rsRestart();
        $count = $this->db->rsGetCol('COUNT(' . $this->quoteId($cm->getOidColumn()) . ')', 'count');
        if (!is_numeric($count)) {
            return false;
        }

        // return the number of rows found in the class table
        return $count;
    }
    
    /**
     * Fetchs objects using the variable values specified in epObject
     * If the object is null, get all objects in table. 
     * @param epObject $o
     * @param epClassMap
     * @param array (of integer) object ids to be excluded
     * @return false|array
     */
    public function &fetch($cm, $o = null, $ex = null) {
        
        // make sure the table is created
        if (!$this->create($cm, false)) {
            return self::$false;
        }

        // preapre sql statement
        if (!($sql = epObj2Sql::sqlSelect($this, $cm, $o, $ex))) {
            return self::$false;
        }
        
        // execute sql
        if (!$this->_execute($sql)) {
            return self::$false;
        }

        // result conversion
        $r = & $this->_rs2obj($cm, $ex);

        return $r;
    }
    
    /**
     * Fetchs records using the variable values specified in epObject
     * @param epObject $o
     * @param epClassMap
     * @return bool
     */
    public function insert($cm, $o) {
        
        // make sure the table is created
        if (!$this->create($cm, false)) {
            return false;
        }

        // update table for last insertion
        $this->table_last_inserted = $cm->getTable();

        // preapre sql statement
        if (!($sql = epObj2Sql::sqlInsert($this, $cm, $o))) {
            return false;
        }
        
        // execute sql
        return ($r = $this->_execute($sql));
    }
    
    /**
     * Fetchs records using the variable values specified in epObject
     * @param epObject $o
     * @param epClassMap
     * @return bool
     */
    public function delete($cm, $o) {
        
        // preapre sql statement
        $sql = epObj2Sql::sqlDelete($this, $cm, $o);
        if (!$sql) {
            return false;
        }
        
        // execute sql
        return ($r = $this->_execute($sql));
    }
    
    /**
     * Fetchs records using the variable values specified in epObject
     * @param epObject $o
     * @param epClassMap
     */
    public function update($cm, $o) {
        
        // preapre sql statement
        $sql = epObj2Sql::sqlUpdate($this, $cm, $o);
        if (!$sql) {
            // no need to update
            return true;
        }
        
        // execute sql
        return ($r = $this->_execute($sql));
    }
    
    /**
     * Create a table specified in class map if not exists
     * @param epClassMap
     * @return bool
     */
    public function create($cm, $force = false) {
        
        // check if table exists
        if (!$force && $this->_tableExists($cm->getTable())) {
            return true;
        }
        
        // preapre sql statement
        $sql = epObj2Sql::sqlCreate($this, $cm);
        if (!$sql) {
            return false;
        }
        
        // execute sql
        return ($r = $this->_execute($sql));
    }
    
    /**
     * Empty a table specified in class map
     * @param epClassMap
     * @return bool
     */
    public function truncate($cm) {
        
        // preapre sql statement
        $sql = epObj2Sql::sqlTruncate($this, $cm);
        if (!$sql) {
            return false;
        }
        
        // execute sql
        return ($r = $this->_execute($sql));
    }
    
    /**
     * Returns the last insert id
     * @param string $oid the oid column
     * @return integer
     * @access public
     */
    public function lastInsertId($oid = 'oid') {
        return $this->db->lastInsertId($this->table_last_inserted, $oid);
    }
    
    /**
     * Formats input so it can be safely used as a literal
     * Wraps around {@link epDb::quote()}
     * @param mixed $v
     * @param epFieldMap 
     * @return mixed
     */
    public function quote($v, $fm = null) {
        
        // special treatment for blob
        if ($fm) {
            
            switch ($fm->getType()) {
            
            case epFieldMap::DT_BOOL:
            case epFieldMap::DT_BOOLEAN:
            case epFieldMap::DT_BIT:
                $v = $v ? 1 : 0;

            case epFieldMap::DT_INT:
            case epFieldMap::DT_INTEGER:
            // date, time, datetime treated as integer
            case epFieldMap::DT_DATE:
            case epFieldMap::DT_TIME:
            case epFieldMap::DT_DATETIME:
                return (integer)$v;

            case epFieldMap::DT_BLOB:
            case epFieldMap::DT_TEXT:
                $v = epStr2Hex($v);
                break;
            }
        } 

        return $this->db->quote($v);
    }
    
    /**
     * Wraps around {@link epDb::quoteId()}
     * Formats a string so it can be safely used as an identifier (e.g. table, column names)
     * @param string $id
     * @return mixed
     */
    public function quoteId($id) {
        return $this->db->quoteId($id);
    }

    /**
     * Set whether to log queries
     * @param boolean $log_queries
     * @return boolean
     */
    public function logQueries($log_queries = true) {
        return $this->db->logQueries($log_queries);
    }
    
    /**
     * Returns queries logged
     * @return array
     */
    public function getQueries() {
        return $this->db->getQueries();
    }
    
    /**
     * Calls underlying db to check if table exists. Always returns
     * true if options check_table_exists is set to false
     * @param 
     */
    protected function _tableExists($table) {

        // if no checking of table existence
        if (!$this->check_table_exists) {
            // always assume table exists
            return true;
        }

        return $this->db->tableExists($table);
    }

    /**
     * Executes multiple db queries
     * @param string|array $sql either a single sql statement or an array of statemetns
     * @return mixed
     */
    protected function _execute($sql) {
        
        // make sql into an array
        if (!is_array($sql)) {
            $sql = array($sql);
        }
        
        // execute sql stmt one by one
        foreach($sql as $sql_) {
            $r = $this->db->execute($sql_);
        }
        
        return $r;
    }
    
    /**
     * Returns the aggregate function result 
     * @return integer|float
     * @throws epExceptionDb
     */
    protected function _rs2aggr($aggr_func) {
        
        // get ready to ready query result
        $this->db->rsRestart();

        // return aggregation 'column'
        $aggr_alt = substr($aggr_func, 0, strpos($aggr_func, '('));
        return $this->db->rsGetCol($aggr_func, $aggr_alt);
    }

    /**
     * Converts the last record set into epObject object(s) with class map
     * @param epClassMap $cm the class map for the conversion
     * @param array (of integers) object ids to be excluded
     * @return false|array (of epObject)
     * @throws epExceptionDbObject
     */
    protected function &_rs2obj($cm, $ex = null) {
        
        // !!!important!!! with a large db, the list of oid to be excluded
        // $ex can grown really large and can significantly slow down 
        // queries. so it is suppressed in the select statement and moved 
        // to this method to process.

        // get epManager instance and cache it
        if (!$this->ep_m) {
            $this->ep_m = & epManager::instance();
        }

        // get the class name
        $class = $cm->getName();

        // get all mapped vars
        if (!($fms = $cm->getAllFields())) {
            return self::$false;
        }

        // reset counter and return value
        $ret = array();

        // go through reach record
        $okay = $this->db->rsRestart();
        
        while ($okay) {

            // get oid column 
            $oid = $this->db->rsGetCol($cn=$cm->getOidColumn(), $class.'.'.$cn);

            // exclude it?
            if ($ex && in_array($oid, $ex)) {
                
                // next row
                $okay = $this->db->rsNext();
                
                // exclude it
                continue;
            }

            // call epManager to create an instance (false: no caching; false: no event dispatching)
            if (!($o = & $this->ep_m->_create($class, false, false))) {
                // next row
                $okay = $this->db->rsNext();
                continue;
            }
            
            // go through each field
            foreach($fms as $fname => $fm) {

                // skip non-primivite field
                if (!$fm->isPrimitive()) {
                    continue;
                }

                // get var value and set to object
                $val = $this->db->rsGetCol($cn=$fm->getColumnName(),$class.'.'.$cn);
                
                // set value to var (true: no dirty flag change)
                $o->epSet($fm->getName(), $this->_castType($val, $fm->getType()), true); 
            }

            // set oid 
            $o->epSetObjectId($oid); 

            // collect return result
            $ret[] = $o;

            // next row
            $okay = $this->db->rsNext();
        }

        return $ret;
    }

    /**
     * Cast type according to field type
     * @param mixed $val 
     * @param string $ftype
     * @return mixed (casted value)
     * @access protected
     */
    protected function _castType(&$val, $ftype) {
        
        switch($ftype) {

            case epFieldMap::DT_BOOL:
            case epFieldMap::DT_BOOLEAN:
            case epFieldMap::DT_BIT:
                $val = (boolean)$val;
                break;

            case epFieldMap::DT_DECIMAL:
            case epFieldMap::DT_CHAR:
            case epFieldMap::DT_CLOB:
                $val = (string)$val;
                break;

            case epFieldMap::DT_BLOB:
            case epFieldMap::DT_TEXT:
                $val = (string)epHex2Str($val);
                break;

            case epFieldMap::DT_INT:
            case epFieldMap::DT_INTEGER:
                $val = (integer)$val;
                break;

            case epFieldMap::DT_FLOAT:
            case epFieldMap::DT_REAL:
                $val = (float)$val;
                break;

            case epFieldMap::DT_DATE:
            case epFieldMap::DT_TIME:
            case epFieldMap::DT_DATETIME:
                $val = (integer)$val;
                break;
        }

        return $val;
    }
}

/**
 * Exception class for {@link epDbFactory}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1.59 $ $Date: 2005/12/15 03:48:53 $
 * @package ezpdo
 * @subpackage ezpdo.db 
 */
class epExceptionDbFactory extends epException {
}

/**
 * Class of database connection factory
 * 
 * The factory creates databases with given DSNs and maintains
 * a one(DSN)-to-one(epDbObject isntance) mapping.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1.59 $ $Date: 2005/12/15 03:48:53 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbFactory implements epFactory, epSingleton  {
    
    /**#@+
     * Consts for DB abstraction layer libs
     */
    const DBL_ADODB  = "adodb";
    const DBL_ADODB_PDO = "adodb_pdo";
    const DBL_PEARDB = "peardb";
    const DBL_PDO = "pdo";
    /**#@-*/

    /**#@+
     * Used for return value to avoid reference notice in 5.0.x and up
     * @var bool
     */
    static public $false = false;
    static public $true = true;
    static public $null = null;
    /**#@-*/

    /**
     * The array of DBALs supported
     * @var array
     */
    static public $dbls_supported = array(
        self::DBL_ADODB,
        self::DBL_ADODB_PDO,
        self::DBL_PEARDB,
        self::DBL_PDO,
        );
    
    /**
     * The current DB abstraction lib in use
     */
    private $dbl = epDbFactory::DBL_ADODB;
    
    /**
     * db connections created
     * @var array
     */
    private $dbs = array();
    
    /**
     * Constructor
     */
    private function __construct() { 
    }
    
    /**
     * Get the current DBA (DB abstraction lib)
     * @return string
     */
    function getDbLib() {
        return $this->dbl;
    }
    
    /**
     * Set the current DBA (DB abstraction lib)
     * @param string self::DBL_ADODB|self::DBL_PEARDB
     * @return void
     */
    function setDbLib($dbl) {
        
        // lower case dbl name
        $dbl = strtolower($dbl);
        
        // is dbl supported?
        if (!in_array($dbl, self::$dbls_supported)) {
            throw new epExceptionDbFactory('Db library [' . $dbl . '] unsupported.');
        }

        // set the current dbl
        $this->dbl = $dbl;
    }
    
    /**
     * Implements factory method {@link epFactory::make()}
     * @param string $dsn
     * @return epDbObject|null
     * @access public
     * @static
     */
    public function &make($dsn) {
        return $this->get($dsn, false); // false: no tracking
    }

    /**
     * Implement factory method {@link epFactory::track()}
     * @param string $dsn
     * @return epDbObject
     * @access public
     */
    public function &track() {
        $args = func_get_args();
        return $this->get($args[0], true); // true: tracking
    }
    
    /**
     * Either create a class map (if not tracking) or retrieve it from cache 
     * @param $dsn
     * @param bool tracking or not
     * @return null|epDbObject
     * @throws epExceptionDbFactory
     */
    private function &get($dsn, $tracking = false) {
        
        // check if dsn is empty 
        if (empty($dsn)) {
            throw new epExceptionDbFactory('DSN is empty');
            return self::$null;
        }

        // check if class map has been created
        if (isset($this->dbs[$dsn])) {
            return $this->dbs[$dsn];
        }
        
        // check if it's in tracking mode
        if ($tracking) {
            return self::$null;
        }
        
        // otherwise create
        switch($this->dbl) {
        
            case self::DBL_ADODB:
                include_once(EP_SRC_DB.'/epDbAdodb.php'); 
                $this->dbs[$dsn] = new epDbObject(new epDbAdodb($dsn));
                break;
        
            case self::DBL_ADODB_PDO:
                include_once(EP_SRC_DB.'/epDbAdodbPdo.php'); 
                $this->dbs[$dsn] = new epDbObject(new epDbAdodbPdo($dsn));
                break;
        
            case self::DBL_PEARDB:
                include_once(EP_SRC_DB.'/epDbPeardb.php');
                $this->dbs[$dsn] = new epDbObject(new epDbPeardb($dsn));
                break;

            case self::DBL_PDO:
                include_once(EP_SRC_DB.'/epDbPdo.php');
                $this->dbs[$dsn] = new epDbObject(new epDbPdo($dsn));
                break;
        }
        
        return $this->dbs[$dsn];
    }
    
    /**
     * Implement factory method {@link epFactory::allMade()}
     * Return all db connections made by factory
     * @return array
     * @access public
     */
    public function allMade() {
        return array_values($this->dbs);
    }
    
    /**
     * Implement factory method {@link epFactory::removeAll()}
     * Remove all db connections made 
     * @return void
     */
    public function removeAll() {
        
        // close all db connections
        if ($this->dbs) {
            foreach($this->dbs as $db) {
                $db->connection()->close();
            }
        }
        
        // wipe out all db connections
        $this->dbs = null;
    }
    
    /**
     * Implements {@link epSingleton} interface
     * @return epDbFactory
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
     * epDbFactory instance
     */
    static private $instance; 
}

?>
