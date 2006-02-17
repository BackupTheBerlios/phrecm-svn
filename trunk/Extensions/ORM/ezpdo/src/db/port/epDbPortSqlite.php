<?php

/**
 * $Id: epDbPortSqlite.php,v 1.6 2005/11/24 16:11:35 nauhygon Exp $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$
 * @package ezpdo
 * @subpackage ezpdo.db
 */

/**
 * Class to handle database portability for Sqlite
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbPortSqlite extends epDbPortable {

    /**
     * Override {@link epDbPort::createTable()}
     * 
     * Generate SQL code to create table
     * 
     * @param epClassMap $cm
     * @param string $indent
     * @param epDb $db
     * @return string|array (of strings)
     */
    public function createTable($cm, $db, $indent = '  ') {
        
        // start create table
        $sql = "CREATE TABLE " . $db->quoteId($cm->getTable()) . " (";
        
        // the oid field
        $fstr = $this->_defineField($db->quoteId($cm->getOidColumn()), 'INTEGER', '12', false, true);
        $sql .= $fstr . ",";

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
                $sql .= $fstr . ",";
            }
        }
        
        // remove the last ','
        $sql = substr($sql, 0, strlen($sql) - 1);
        
        // end of table creation
        $sql .= ");";
        
        return $sql;
    }

    /**
     * Override {@link epDbPort::dropTable()}
     * SQL to drop table 
     * @param string $table
     * @param epDb $db
     * @return string
     */
    public function dropTable($table, $db) {
        return 'DROP TABLE ' . $db->quoteId($table) . ";\n";
    }

    /**
     * SQL to truncate (empty) table 
     * @param string $table
     * @param epDb $db
     * @return string
     */
    public function truncateTable($table, $db) {
        return 'DELETE FROM  ' . $db->quoteId($table) . " WHERE 1;\n";
    }
    
    /**
     * Override {@link epDbPortable::_defineField}
     * 
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
        
        // is it an auto-incremental?
        if ($autoinc) {
            //return $fname . ' INTEGER AUTOINCREMENT';
            return $fname . ' INTEGER PRIMARY KEY';
        }
        
        // get field name and type(params)
        $sql = $fname . ' ' . $this->_fieldType($type, $params);
        
        // does the field have default value?
        if ($default) {
            $sql .= ' DEFAULT ' . $default;
        }
        
        return $sql;
    }
    
}

?>
