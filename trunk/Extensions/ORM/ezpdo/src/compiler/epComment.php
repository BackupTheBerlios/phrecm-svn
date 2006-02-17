<?php

/**
 * $Id: epComment.php,v 1.5 2005/12/11 17:00:26 nauhygon Exp $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/12/11 17:00:26 $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */

/**
 * Class of a ezpdo comment block
 * 
 * The class takes comments in source code as the input and 
 * parses it into tag-value pairs. Usage:
 * <pre>
 * $c = new epComment($comment);
 * $c->getTagValue('var');
 * </pre>
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/12/11 17:00:26 $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */
class epComment {
    
    /**
     * The array that holds tag-values
     * @var array
     */
    protected $tag_values = array();
    
    /**
     * Constructor
     * @param string
     */
    public function __construct($comment) { 
        $this->parse($comment);
    }
    
    /**
     * Check if comment has a particular tag
     * @param string tag name
     * @return bool
     */
    public function hasTag($tag_name) {
        if ($tag_name) {
            return array_key_exists($tag_name, $this->tag_values);
        }
        return false;
    }
    
    /**
     * Returns all tags
     * @return array (of string) 
     */
    public function getTags() {
        return array_keys($this->tag_values);
    }
    
    /**
     * Returns the value of a tag
     * @param string tag name
     * @return false|string false if tag not found or tag value (null if tag value not set)
     */
    public function getTagValue($tag_name) {
        if (!$this->hasTag($tag_name)) {
            return false;
        }
        return $this->tag_values[$tag_name];
    }
    
    /**
     * Preprocess comment (remove excessive space, comment boarder)
     * @param string the original comment
     * @return string the processed comment 
     */
    private function preproc($comment) {
    
        $ret = $comment;
        
        // remove comment boarders
        $ret = preg_replace(
    
            // patterns
            array(
                "/\s*\/+\**\s+/i", // /* or /** or /*** or //*.. and trailing spaces
                "/\s*\*\**\/?\s*/i", // *'s and trailing spaces on a new line 
                "/\{\s*@\w*.*\}/i", // ignore inline tags
                ), 
            
            // replacement
            array(
                " ", 
                " ", 
                "", 
                ), 
            
            $ret
            );
        
        return $ret;
    }
    
    /**
     * Parse the comment into tag-value array
     * @param string  
     * @return bool
     */
    private function parse($comment) {
        
        // check if comment is empty
        if (!$comment) {
            return false;
        }
        
        // preproc the comment
        $preproced = $this->preproc($comment);
        
        /**
         * split string into an array of tags and values. normally a 
         * value follow a tag, but it's possible a tag does not have 
         * a value following (ie an empty tag).
         */
        $pieces = preg_split("/(@\w+)\s+/", $preproced, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!$pieces) {
            return false;
        }
        
        // associate tags and values
        reset($pieces);
        $piece = next($pieces);
        do {
            
            // trim piece
            $piece = trim($piece);
            
            // is it a tag
            if (!$piece || !isset($piece[0]) || $piece[0] !== '@') {
                $piece = next($pieces);
                continue;
            }
            
            // process tag
            $tag = substr($piece, 1);
            
            // check if next piece is value
            $piece = next($pieces);
            
            // trim piece
            $piece = trim($piece);
            
            if (!$piece || $piece[0] === '@') {
                // if the next value is a tag, no value for this tag
                $this->tag_values[$tag] = null;
            } else {
                $this->tag_values[$tag] = $piece;
                $piece = next($pieces);
            }
        
        } while ($piece !== false);

        return true;
    }

}

/**
 * Class to parse an ezpdo orm tag value 
 * 
 * The class takes the tag value as the input and dissects it 
 * into orm attributes. To get the attribute value, use {@link get()}. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/12/11 17:00:26 $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 * @abstract
 */
abstract class epTag {
    
    /**
     * Attribute and values
     * @var array (keyed by attribute name)
     */
    protected $attrs;
    
    /**
     * Constructor
     * @param string $value tag value
     */
    public function __construct($value) { 
        $this->parse($value);
    }

    /**
     * Returnrs all orm attributes
     * @return array (of string) 
     */
    public function getAll() {
         return array_keys($this->attrs);
    }
    
    /**
     * Returns the value of an attribute
     * @param string attribute name
     * @return null|string
     */
    public function get($attr) {
        if (!isset($this->attrs[$attr])) {
            return null;
        }
        return $this->attrs[$attr];
    }

    /**
     * Parse the tag value string
     * @return bool
     */
    abstract protected function parse($value);
}

/**
 * Class used to parse the value of an orm tag for a class 
 * 
 * Available attributes after parsing the tag value
 * <ol>
 * <li>
 * table: the table name for the class to be mapped to (can be null if not specified)
 * </li>
 * <li>
 * dsn: the dsn ({@link http://pear.php.net/manual/en/package.database.db.intro-dsn.php}) 
 * to the database that the table can be accessed. This attribute can also 
 * be null, if so the parser ({@link epParser}) tries to use the default_dsn 
 * specified in config.
 * </li>
 * </ol>
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/12/11 17:00:26 $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */
class epClassTag extends epTag {
    
    /**
     * Constructor
     * @param string $value tag value
     */
    public function __construct($value) { 
        parent::__construct($value);
    }

    /**
     * Impelment abstract method in {@link epTag}
     * Parse the tag value string
     * @return bool
     */
    protected function parse($value) {
        
        // sanity check
        if (!$value || !is_string($value)) {
            return false;
        }
        
        // break value into pieces
        $pieces = preg_split('/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        if (!$pieces) {
            return true;
        }
        
        $table_found = false;
        foreach($pieces as $piece) {
            $piece = trim($piece);
            if (preg_match('/[\w\(\)]+:\/\//i', $piece)) {
                $this->attrs['dsn'] = $piece;
            } 
            else if (preg_match('/oid\((.*)\)/i', $piece, $matches)) {
                $this->attrs['oid'] = trim($matches[1]);
            }
            else {
                if (!$table_found) {
                    $this->attrs['table'] = $piece;
                    $table_found = true;
                }
            }
        } 
        
        return true;
    }
    
}

/**
 * Class to parse an orm tag value of a variable
 * 
 * Three attributes for an orm tag for a variable
 * <ol>
 * <li>
 * name: the name of column the variable to be mapped to which can
 * be returned as null. if empty, the parser ({@link epParser}) 
 * uses the variable name as the column name. 
 * </li>
 * <li>
 * type: the type of column (see {@link epFieldMap::getSupportedTypes()}),
 * can be empty as well. If empty, the parser ({@link epParser}) 
 * tries to figure out the type by looking at other usual docblock tags.
 * The last resort is to treat it as a string. 
 * </li>
 * <li>
 * params: the params for the column type (can be empty) 
 * </li>
 * </ol>
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/12/11 17:00:26 $
 * @package ezpdo
 * @subpackage ezpdo.compiler
 */
class epVarTag extends epTag {
    
    // array of all allowed types
    static private $alltypes = false;
    
    /**
     * Constructor
     * @param string tag value
     */
    public function __construct($value) { 
        
        // make match pattern of all supported types
        if (!self::$alltypes) {
            include_once(EP_SRC_ORM.'/epFieldMap.php');
            self::$alltypes = epFieldMap::getSupportedTypes();
        }
        
        parent::__construct($value);
    }

    /**
     * Impelment abstract method in {@link epTag}
     * Parse the tag value string
     * @return bool
     */
    protected function parse($value) {
        
        // sanity check
        if (!$value || !is_string($value)) {
            return false;
        }
        
        // replace "(" and ")" with space
        $value = str_replace(array('(', ')', '[', ']'), ' ', $value);

        // break value into pieces
        $pieces = preg_split('/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        if (!$pieces) {
            return true;
        }
        
        // the first or the second has to be data type
        $type = array_shift($pieces);
        if (!in_array($type, self::$alltypes)) {
            // if not a type, it has to be the column name
            $this->attrs['name'] = $type;
            $type = array_shift($pieces);
        } 
        
        // get field data type
        if (!in_array($type, self::$alltypes)) {
            return false;
        }
        
        // set the field data type
        $this->attrs['type'] = $type;
        
        // get field params
        if ($pieces) {
            if (count($pieces) == 1) {
                // get the single value if only one param
                $this->attrs['params'] = $pieces[0];
            } else {
                // otherwise, get the array
                $this->attrs['params'] = $pieces;
            }
        }
        
        return true;
    }
    
}

?>
