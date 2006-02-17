<?php

/**
 * $Id: epDbPortPostgres.php,v 1.10 2005/11/24 16:11:35 nauhygon Exp $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1.10 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */

/**
 * Class to handle database portability for Postgres
 * 
 * Initially contributed by sbogdan (http://www.ezpdo.net/forum/profile.php?id=34). 
 * Improved by rashid (Robert Janeczek) (http://www.ezpdo.net/forum/profile.php?id=27). 
 * 
 * @author Robert Janeczek <rashid@ds.pg.gda.pl>
 * @author sbogdan <http://www.ezpdo.net/forum/profile.php?id=34>
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * 
 * @version $Revision: 1.10 $
 * @package ezpdo
 * @subpackage ezpdo.db
 */
class epDbPortPostgres extends epDbPortable {

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
        $sql = "CREATE TABLE \"" . $cm->getTable() . "\" (";
      	$sql = $sql . "CONSTRAINT ". $cm->getTable() . "_" .$cm->getOidColumn() . " PRIMARY KEY (" .$cm->getOidColumn() ."),";
       
        // the oid field
      	$fstr = $this->_defineField($cm->getOidColumn(), 'Integer', '', false, true);

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
        // WITH OIDS - see http://www.ezpdo.net/forum/viewtopic.php?pid=750#p750
        $sql .= ") WITH OIDS;";
       
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
        return 'DROP TABLE ' . $table . ";\n";
    }

    /**
     * SQL to truncate (empty) table
     * @param string $table
     * @param epDb $db
     * @return string
     */
    public function truncateTable($table, $db) {
        //return 'DELETE FROM ' . $table . " WHERE 1=1;\n";
        return 'TRUNCATE TABLE  ' . $db->quoteId($table) . ";\n";
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
            return $fname . ' SERIAL NOT NULL ';
        }
        // get field name and type(params)
        $sql = $fname . ' ' . $this->_fieldType($type, $params);
       
        // does the field have default value?
        if ($default) {
            $sql .= ' DEFAULT ' . $default;
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
                // to simplify things
                return 'numeric(1)';

            case epFieldMap::DT_CHAR:
                // as opposed to 'char' (which has the space-padding problem in pgsql)
                $ftype = 'varchar';
                break;

            case epFieldMap::DT_INT:
            case epFieldMap::DT_INTEGER:
                $ftype = 'numeric';
                if (!$params) {
                    $params = 10;
                }
                break;

            case epFieldMap::DT_FLOAT:
            case epFieldMap::DT_REAL:
            case epFieldMap::DT_DECIMAL:
                $ftype = 'numeric';
                if (!$params) {
                    $params = "10,5";
                }
                break;
            
            case epFieldMap::DT_CLOB:
            case epFieldMap::DT_TEXT:
                // http://www.postgresql.org/docs/8.0/interactive/datatype-character.html
                return 'text';

            case epFieldMap::DT_BLOB:
                // see http://www.postgresql.org/docs/8.0/interactive/datatype-binary.html
                return 'bytea';

            case epFieldMap::DT_DATE:
            case epFieldMap::DT_TIME:
            case epFieldMap::DT_DATETIME:
                // currently date/time/datetime are all mapped to integer
                // this should be changed once we work out unixDate() and
                // dbDate() (as in ADODB)
                return "numeric(16)"; //???

        }

        // concat params
        if ($params) {
            $ftype .= '(' . $params . ')';
        }

        return $ftype;
    }
   
}

?>
