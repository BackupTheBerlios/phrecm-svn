<?php

/**
 * $Id: epQueryBuilder.php,v 1.28 2005/12/15 03:48:53 nauhygon Exp $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1.28 $
 * @package ezpdo
 * @subpackage ezpdo.query
 */

/**
 * need epQueryNode (in epQueryParser.php)
 */
include_once(EP_SRC_QUERY.'/epQueryParser.php');

/**
 * Exception class for {@link epDb}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1.28 $
 * @package ezpdo
 * @subpackage ezpdo.base 
 */
class epExceptionQueryBuilder extends epException {
}

/**
 * The EZOQL SQL builder
 * 
 * The class builds standard SQL statement from the syntax tree parsed 
 * by {@link epQueryParser}. This class talks to {@link epManager} to 
 * get class/var mapping information for the  SQL generation. Name 
 * and identifier quoting is also delegated to database ({@link epDbObject}). 
 * 
 * @author Oak Nauhygon <slimjs@gmail.com>
 * @version $Revision: 1.28 $
 * @package ezpdo
 * @subpackage ezpdo.query
 */
class epQueryBuilder extends epBase {

    /**
     * The dummy alias name 
     */
    const DUMMY_ALIAS = '__ALIAS__';

    /**
     * Wether to print out debugging info
     * @var boolean
     */
    protected $verbose = false;

    /**
     * The cached EZPDO manager
     * @var epManager
     */
    protected $em = false;

    /**
     * The cached db for the root class
     * @var epManager
     */
    protected $db = false;

    /**
     * The root of the EZOQL syntax tree
     * @var epQueryNode
     */
    protected $root = false;

    /**
     * The root class
     * @var string
     */
    protected $root_class = '';

    /**
     * The root classes (for children classes)
     * @var array of strings
     */
    protected $root_classes = array(); 

    /**
     * The class map for the root class
     * @var array of epClassMap
     */
    protected $root_cms = array();

    /**
     * The alias for the root class
     * @var string
     */
    protected $root_alias = '';

    /**
     * The original query string
     * @var epQueryNode
     */
    protected $query = '';

    /**
     * The argument array for the query
     * @var array
     */
    protected $args = false;

    /**
     * Associative array to keep aliases and their classes 
     * @var array (keyed by alias name, value is class name) 
     */
    protected $aliases = array();

    /**
     * Associative array to keep aliases for relationship tables 
     * @var array (keyed by alias name, value is relationship tables) 
     */
    protected $rt_aliases = array();

    /**
     * The current alias id
     * @var integer 
     */
    protected $alias_id = 0;

    /**
     * Aggreation function involved in the query
     * @var false|string 
     */
    protected $aggr_func = false;

    /**  
     * Order by in the query
     * @var false|string
     */
    protected $orderby  = false;
    
    /**
     * Limit in the query
     * @var false|string
     */
    protected $limit = false;

    /**
     * Array to keep where comparison expressions
     * @var array
     */
    protected $wheres = array();

    /**
     * Constructor
     * @param epQueryNode $root the root node of the syntax tree
     * @param string &$query the query string
     * @param array $args the arguments for the query
     * @param boolean whether to print out debugging info
     * @throws epExceptionQueryBuilder
     */
    public function __construct(epQueryNode &$root, &$query = '', $args = array(), $verbose = false) {
        
        // get runtime manager
        $this->em = epManager::instance();

        // get root and arguments for the query
        $this->root = & $root;
        $this->args = & $args;
        $this->query = & $query;
        $this->verbose = $verbose;
    }
    
    /**
     * Returns the root class
     * @return array epClassMap
     */
    public function &getRootClassMaps() {
        return $this->root_cms;
    }

    /**
     * Returns whether the query has aggregate function
     * @return boolean|string
     */
    public function getAggregateFunction() {
        return $this->aggr_func;
    }

    /**
     * Returns whether the query has a limit
     * @return boolean|string
     */
    public function getLimit() {
        return $this->limit;
    }

    /**
     * Returns whether the query has an order by
     * @return boolean|string
     */
    public function getOrderBy() {
        return $this->orderby;
    }

    /**
     * Build the SQL query from syntax tree
     * @return false|string 
     */
    public function build() {

        // check if root is set
        if (!$this->root || !$this->query) {
            throw new epExceptionQueryBuilder('The syntax tree or the query is not set');
            return false;
        }
        
        // prepare before writing sql
        if (!$this->prepare()) {
            return false;
        }

        return $this->outputSql();
    }

    /**
     * The preprocess before writing SQL statement
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function prepare() {
        
        // reset the aliases arrays
        $this->aliases = array();
        $this->rt_aliases = array();
        $this->alias_id = 0;

        // reset has_aggregate flag
        $this->has_aggregate = false;
        
        // reset the array for where comparison expressions
        $this->wheres = array();

        // get root alias
        if (!($this->root_alias = $this->rootAlias())) {
            return false;
        }
        
        // collect aliases
        if (!$this->walk($this->root, 'collectAliases')) {
            return false;
        }
        
        // normalize variables
        if (!$this->walk($this->root, 'normalizeVariable')) {
            return false;
        }
        
        // process 'contains' and 'varialbe' nodes 
        while (!$this->walk($this->root, 'processVariable')) {
            // loop until all aliases and vars are set up
        }
        
        return true;
    }

    /**
     * Computes and returns the sql statement 
     * @return string
     */
    protected function outputSql() {
        return $this->buildSqlSelect($this->root); 
    }

    /**
     * Returns the alias for the root class in the EPQ_N_FROM node
     * and set it into the alias list
     * @return false|string 
     * @throws epExceptionQueryBuilder
     */
    private function rootAlias() {
        
        // already set?
        if ($this->root_alias) {
            return $this->root_alias;
        }

        // set root alias
        if (!$this->_rootAlias()) {
            return false;
        }

        return $this->root_alias;
    }

    /**
     * Returns the alias for the root class in the EPQ_N_FROM node
     * (Also set the root class)
     * @return false|string 
     * @throws epExceptionQueryBuilder
     */
    private function _rootAlias() {
        
        // get from node
        if (!($from = $this->root->getChild('from'))) {
            throw new epExceptionQueryBuilder($this->_e("Cannot found 'from' clause"));
            return false;
        }

        // get from items
        if (!($items = $from->getChildren())) {
            throw new epExceptionQueryBuilder($this->_e("No from items specified"));
            return false;
        }

        // go through each item
        $isRoot = true;
        foreach($items as &$item) {

            // get class name
            $class = $item->getParam('class');
            
            // get class map
            if (!($cm = $this->em->getClassMap($class))) {
                throw new epExceptionQueryBuilder($this->_e("No class map for class [".$class."]"));
                continue;
            }

            // does it have param 'alias' set?
            if (!($alias = $item->getParam('alias'))) {
                $alias = $this->generateAlias($class);
            }

            // put it into aliases array
            $this->aliases[$alias] = $class;

            // set up root class 
            if ($isRoot) {
                $isRoot = false;

                $this->root_alias = $alias;
                $this->root_class = $class;
                $this->root_cms[] = $cm;

                // get the db for the class
                if (!($this->db = $this->em->getDb($class, $cm))) {
                    throw new epExceptionQueryBuilder($this->_e("Cannot found db for root class [".$class."]"));
                    continue;
                }

                $this->root_classes[] = $cm->getName();

                // combine all the children and the root class, so we can
                // get results for all children 
                $rootClasses = $cm->getChildren(true);
                foreach ($rootClasses as $rootCm) {
                    $this->root_classes[] = $rootCm->getName();
                    $this->root_cms[] = $rootCm;
                }
            }
            
        }

        // if not generate one
        return $this->root_alias;
    }

    /**
     * Walks through the syntax tree in either depth-first (by default)
     * or breath-first mode. A process method is applied on each node
     * visited. 
     * @param epQueryNode &$node the starting node
     * @param string $proc the node process method
     * @param boolean $df whether it is depth-first or breath-firth
     * @return boolean 
     * @throws epExceptionQueryBuilder
     */
    protected function walk(epQueryNode &$node, $proc, $df = true) {
        
        // make sure we have proc
        if (!$proc) {
            throw new epExceptionQueryBuilder($this->_e('Empty node process method'));
            return false;
        }

        $status = true;

        // breath-first: process current node before walking the children
        if (!$df) {
            $status &= call_user_func_array(array($this, $proc), array(&$node));
        }

        // process all children (recursion)
        if ($children = $node->getChildren()) {
            foreach($children as &$child) {
                $status &= $this->walk($child, $proc, $df);
            }
        }

        // depth-first: process current node after walking the children
        if ($df) {
            $status &= call_user_func_array(array($this, $proc), array(&$node));
        }

        return $status;
    }

    /**
     * Collect aliases from nodes
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function collectAliases(epQueryNode &$node) {

        // is it a 'contains' node?
        if ($node->getType() != EPQ_N_CONTAINS) {
            return true;
        }

        // get alias name 
        if (($alias = $this->getContainsArg($node)) && is_string($alias)) {
            // put alias into alias array if not already
            if (!isset($this->aliases[$alias])) {
                // init'ed false. class name will be set in processVariable()
                $this->aliases[$alias] = false; 
            }
        } 
        
        return true;
    }

    /**
     * Normalize variable so it always starts with an alias
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function normalizeVariable(epQueryNode &$node) {

        // is it a 'variable' node?
        if ($node->getType() != EPQ_N_VARIABLE) {
            return true;
        }

        // get children (var parts)
        if (!($children = $node->getChildren())) {
            throw new epExceptionQueryBuilder($this->_e('Invalid variable definition', $node));
            return false;
        }

        // collect an array of var parts (string)
        $parts = array();
        foreach($children as &$child) {
            
            // if placeholder, get its acutally value
            if (($t = $child->getType()) == EPQ_N_PLACEHOLDER) {
                
                // get placeholder value
                $part = $this->getPlaceholderValue($child);
                
                // type checking
                if (!$part || !is_string($part)) {
                    throw new epExceptionQueryBuilder($this->_e('Invalid placeholder value type (string required)', $child));
                    return false;
                }

            } else {
                // get identifier val
                $part = $child->getParam('val');
            }

            $parts[] = $part;
        }

        // replace root class with root alias
        if ($parts[0] == $this->root_class) {
            // remove alias in parts array and update 
            array_shift($parts);
            $node->setParam('alias', $this->root_alias);
            $node->setParam('parts', $parts);
            return true;
        }

        // check if the first var is an alias
        if (isset($this->aliases[$parts[0]])) {
            $node->setParam('alias', array_shift($parts));
        } else {
            $node->setParam('alias', $this->root_alias);
        }
        $node->setParam('parts', $parts);
        
        return true;
    }

    /**
     * Process variable/contains 
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function processVariable(epQueryNode &$node) {

        // is it a 'variable' node?
        if ($node->getType() != EPQ_N_VARIABLE) {
            return true;
        }
        
        // check if we have processed this node
        if ($node->getParam('fmaps')) {
            // done if so
            return true;
        }

        // get the alias
        if (!($alias = $node->getParam('alias'))) {
            throw new epExceptionQueryBuilder($this->_e('Invalid variable (no starting alias)', $node));
            return false;
        }

        // check if alias is set up
        if (!$this->_isAliasReady($alias)) {
            // cannot process if not ready
            return false;
        }

        // get the parts
        if (!($parts = $node->getParam('parts'))) {
            throw new epExceptionQueryBuilder($this->_e('Invalid variable (no following vars)', $node));
            return false;
        }

        // process the variable and get the fieldmaps
        if (!($fms = $this->_processVariable($node, $alias, $parts))) {
            return false;
        }
        
        // set the field maps as a node param
        $node->setParam('fmaps', $fms);

        // now see if this variable node is part of a 'contains' node
        if ($node->getParent()->getType() == EPQ_N_CONTAINS) {
            // get alias name 
            $alias = $this->getContainsArg($node->getParent());
            if ($alias && is_string($alias)) {
                // set class to the alias of contains
                $this->aliases[$alias] = $fms[count($fms)-1]->getClass();
            }
        }

        return true;
    }

    /**
     * Actually processes a variable by consulting epManager
     * @param string $alias the starting alias for a variable
     * @param array $parts the array of attributes for the variable
     * @return false|array
     * @throws epExceptionQueryBuilder
     */
    protected function _processVariable($node, $alias, $parts) {
        
        // get class name for alias
        if (!($class = $this->aliases[$alias])) {
            throw new epExceptionQueryBuilder($this->_e('Class for alias ['.$alias.'] not found', $node));
            return false;
        }

        // get class map
        if (!($cm = $this->em->getMap($class))) {
            throw new epExceptionQueryBuilder($this->_e('Class ['.$class.'] not found', $node));
            return false;
        }

        // go through each var
        $fms = array();
        foreach($parts as $part) {

            // make sure we have class map
            if (!$cm) {
                throw new epExceptionQueryBuilder($this->_e('Variable ['.$class.'::'.$part.'] not existing', $node));
                return false;
            }

            // fake a fieldmap if part is 'oid'
            if ($part == 'oid') {
                $fm = new epFieldMapPrimitive('oid', epFieldMap::DT_INTEGER, array(), $cm);
                $fm->setColumnName($cm->getOidColumn());
            }
            // get the field map for the part
            else if (!($fm = $cm->getField($part))) {
                throw new epExceptionQueryBuilder($this->_e('Variable ['.$class.'::'.$part.'] not existing', $node));
                return false;
            }

            // collect this field map
            $fms[] = $fm;

            // must be a relationship field map
            $cm = null;
            if ($fm instanceof epFieldMapRelationship) {
                $cm = $this->em->getClassMap($fm->getClass());
                $class = $cm->getName();
            }
        }
        
        return $fms;
    }

    /**
     * Get argument value in 'contains' node
     * @param epQueryNode &$node
     * @return mixed
     * @throws epExceptionQueryBuilder
     */
    protected function getContainsArg(epQueryNode &$node) {

        // get arg param 
        if ($arg = $node->getParam('arg')) {
            return $arg;
        } 

        // get arg child (placeholder)
        if ($arg = $node->getChild('arg')) {
            if ($arg->getType() == EPQ_N_PLACEHOLDER) {
                return $this->getPlaceholderValue($arg);
            }
        }
        
        // something wrong
        throw new epExceptionQueryBuilder($this->_e("Invalid 'contains' expression", $node));

        return false;
    }

    /**
     * Process placeholders in a node
     * @return mixed
     * @throws epExceptionQueryBuilder
     */
    protected function &getPlaceholderValue(epQueryNode &$node) {

        // get arg index: aindex
        if (is_null($aindex = $node->getParam('aindex'))) {
            throw new epExceptionQueryBuilder($this->_e('No argument index for placeholder', $node));
            return self::$null;
        }

        // check if argument exists
        if (!isset($this->args[$aindex])) {
            throw new epExceptionQueryBuilder($this->_e('No argument for placeholder', $node));
            return self::$null;
        }
        
        // check if argument is empty
        if (is_null($arg = & $this->args[$aindex])) {
            throw new epExceptionQueryBuilder($this->_e('Empty argument for placeholder', $node));
            return self::$null;
        }

        return $arg;
    }

    /**
     * Checks if alias is ready
     * @param string $alias
     * @return boolean
     */
    private function _isAliasReady($alias) {
        return isset($this->aliases[$alias]) && $this->aliases[$alias];
    }

    /**
     * Builds SQL statement from a node. 
     * 
     * The method uses node type to dispatche actual SQL generation to the 
     * node's proper handler - a method start with buildSql and appended 
     * with the node type. Examples: 
     * 
     * buildSqlAdd() handles all nodes with type EXP_N_EXPR_ADD, and 
     * buildSqlSelect() handles the EXP_N_EXPR_SELECT node.
     * 
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSql(epQueryNode &$node) {
        
        // get type without 
        $type = str_replace(array('EPQ_N_EXPR_', 'EPQ_N_'), '', $node->getType());
        
        // the build sql method for this type
        $method = 'buildSql' . ucfirst(strtolower($type));
        
        // call the method
        $sql = $this->$method($node);
        
        // debug info if in verbose mode
        if ($this->verbose) {
            echo "\n";
            echo "method: $method\n";
            echo "node:\n"; echo $node ; echo "\n";;
            echo "result: " . print_r($sql, true) . "\n";
            echo "\n";
        }

        return $sql;
    }

    /**
     * Builds SQL statement from 'aggregate' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlAggregate(epQueryNode &$node) {
        
        // build sql for 'arg'. may return either a string (ie '*') or array
        if ($c = & $node->getChild('arg')) {
            $argv = $this->buildSql($c);
        } else {
            $argv = $node->getParam('arg');
        }

        // if array, get argument and keep where expression 
        if (is_array($argv)) {
            // get !!only!! the first item in array
            foreach($argv as $pvar => $expr) {
                // keep where expression (will be collected in buildSqlWhere)
                if ($expr) {
                    $this->wheres[] = $expr;
                }
                
                // quote pvar properly
                $dummy_v = '';
                $this->_qq($dummy_v, $pvar);

                // stop at the first item
                $argv = $pvar;
                break;
            }
        } 
        
        return $node->getParam('func') . '(' . $argv . ')';
    }

    /**
     * Builds SQL statement from 'add' node
     * @return 
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlAdd(epQueryNode &$node) {
        $left_exprs = $this->buildSql($left = & $node->getChild('left'));
        $right_exprs = $this->buildSql($right = & $node->getChild('right'));
        return $this->_buildSqlOpera($left_exprs, $left->getType(), $right_exprs, $right->getType(), $node->getParam('op'));
    }

    /**
     * Builds SQL statement from 'between' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlBetween(epQueryNode &$node) {
        
        // get expressions and node types (var)
        $vexprs = $this->buildSql($c = & $node->getChild('var'));
        if (!is_array($vexprs)) {
            $vexprs = array($vexprs => '');
        }
        $vtype = $c->getType();

        // get expressions and node types (expr1)
        $expr1s = $this->buildSql($c = & $node->getChild('expr1'));
        if (!is_array($expr1s)) {
            $expr1s = array($expr1s => '');
        }
        $type1 = $c->getType();
        
        // get expressions and node types (expr2)
        $expr2s = $this->buildSql($c = & $node->getChild('expr2'));
        if (!is_array($expr2s)) {
            $expr2s = array($expr2s => '');
        }
        $type2 = $c->getType();

        // array to collect expressions
        $op_exprs = array();
        foreach($vexprs as $vpvar => $vexpr) {
            foreach($expr1s as $pvar1 => $expr1) {
                foreach($expr2s as $pvar2 => $expr2) {
                    
                    // collect exprs
                    $exprs = array();

                    // quote values (var, expr1)
                    $emsg = $this->qq($vpvar, $vtype, $pvar1, $type1);
                    if (is_string($emsg)) {
                        throw new epExceptionQueryBuilder($this->_e($emsg, $node));
                        continue;
                    }

                    // quote values (var, expr2)
                    $emsg = $this->qq($vpvar, $vtype, $pvar2, $type2);
                    if (is_string($emsg)) {
                        throw new epExceptionQueryBuilder($this->_e($emsg, $node));
                        continue;
                    }

                    // put exprs into array
                    if ($vexpr) {
                        $exprs[] = $vexpr;
                    } 
                    if ($expr1) {
                        $exprs[] = $expr1;
                    } 
                    if ($expr2) {
                        $exprs[] = $expr2;
                    } 

                    // between v1 and v2
                    $exprs[] = $vpvar.' BETWEEN '.$pvar1.' AND '.$pvar2;

                    // append to comparsion exprs
                    $op_exprs[] = implode(' AND ', $exprs);
                }
            }
        }

        return implode(' OR ', $op_exprs);
    }

    /**
     * Builds SQL statement from 'comparison' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlComparison(epQueryNode &$node) {
        
        // get operation
        $op = $node->getParam('op');

        // left and right expressions/node types
        $left_exprs = $this->buildSql($left = & $node->getChild('left'));
        $left_type = $left->getType();
        $right_exprs = $this->buildSql($right = & $node->getChild('right'));
        $right_type = $right->getType();

        // check if operation is an assignment to relationship var
        if ($hs = $this->_isRelationshipOp($left_exprs, $left_type, $right_exprs, $right_type, $op)) {
            
            // get arg and the starting field map  
            if ($hs == 'LHS') {
                $var_exprs = $right_exprs;
                $fms = $right->getParam('fmaps');
                $arg = $left_exprs;
            } else {
                $var_exprs = $left_exprs;
                $fms = $left->getParam('fmaps');
                $arg = $right_exprs;
            }

            // the starting field map
            $fm = is_array($fms) ? $fms[count($fms) - 1] : null;
            
            return $this->_buildSqlRelationship($node, $fm, $var_exprs, $arg);
        }

        return $this->_buildSqlOpera($left_exprs, $left->getType(), $right_exprs, $right->getType(), $node->getParam('op'));
    }

    /**
     * Builds SQL statement from 'is' node
     * @return string
     * @throws epExceptionQueryBuilder
     * @todo incomplete
     */
    protected function buildSqlIs(epQueryNode &$node) {
        $var_exprs = $this->buildSql($var = & $node->getChild('var'));
        $is_what = $node->getParam('op'); 
        $is_what = strtoupper(str_replace('is ', '', $is_what));
        // use node type EPQ_N_EXPR_UNARY to force no quoting
        return $this->_buildSqlOpera($var_exprs, $var->getType(), $is_what, EPQ_N_EXPR_UNARY, ' IS ');
    }

    /**
     * Builds SQL statement from 'like' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlLike(epQueryNode &$node) {
        // process as a two-side operation. get left and right exprs
        $left_exprs = $this->buildSql($left = & $node->getChild('var'));
        $right_exprs = $this->buildSql($right = & $node->getChild('pattern'));
        return $this->_buildSqlOpera($left_exprs, $left->getType(), $right_exprs, $right->getType(), ' LIKE ');
    }

    /**
     * Builds SQL statement from logic ('and', 'or') node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlLogic(epQueryNode &$node) {
        return $this->_buildSqlChildren($node, ' '.strtoupper($node->getParam('op')).' ');
    }

    /**
     * Builds SQL statement from 'mul' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlMul(epQueryNode &$node) {
        $left_exprs = $this->buildSql($left = & $node->getChild('left'));
        $right_exprs = $this->buildSql($right = & $node->getChild('right'));
        return $this->_buildSqlOpera($left_exprs, $left->getType(), $right_exprs, $right->getType(), $node->getParam('op'));
    }

    /**
     * Builds SQL statement from 'not' node
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlNot(epQueryNode &$node) {
        return ' NOT ' . $this->buildSql($node->getChild(0));
    }

    /**
     * Builds SQL statement from 'paren' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqParen(epQueryNode &$node) {
        return '(' . $this->buildSql($node->getChild('expr')) . ')';
    }

    /**
     * Builds SQL statement from 'unary' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlUnary(epQueryNode &$node) {
        return $node->getParam('op') . $this->buildSql($node->getChild('expr'));
    }

    /**
     * Builds SQL statement from 'contains' node
     * @return false|string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlContains(epQueryNode &$node) {
        $var_exprs = $this->buildSql($var = & $node->getChild('var'));
        $arg = $this->getContainsArg($node);
        $fms = $var->getParam('fmaps');
        $fm = is_array($fms) ? $fms[count($fms) - 1] : null;
        return $this->_buildSqlRelationship($node, $fm, $var_exprs, $arg);
    }

    /**
     * Builds SQL statement from 'from' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlFrom(epQueryNode &$node) {
        
        $froms = array();
        
        // concat all class aliases
        foreach($this->aliases as $alias => $class) {
            $class = $this->uq($class);
            $froms[] = $this->qid($this->em->getClassMap($class)->getName()) . ' AS ' . $this->qid($alias);
        }
        
        // concat all aliases of relation table
        foreach($this->rt_aliases as $alias => $table) {
            $froms[] = $this->qid($table) . ' AS ' . $this->qid($alias);
        }

        return 'FROM ' . implode(', ', $froms);
    }

    /**
     * Builds SQL statement from 'identifier' node
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlIdentifier(epQueryNode &$node) {
        return $node->getParam('val');
    }

    /**
     * Builds SQL statement from 'limit' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlLimit(epQueryNode &$node) {
        
        // append start 
        $sql = 'LIMIT ' . $this->buildSql($node->getChild('start'));
        
        // get length if exists
        if ($length_node = & $node->getChild('length')) {
            $sql .= ',' . $this->buildSql($length_node);
        }
        
        return $sql;
    }

    /**
     * Builds SQL statement from 'number' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlNumber(epQueryNode &$node) {
        return $node->getParam('val');
    }

    /**
     * Builds SQL statement from 'orderby' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlOrderby(epQueryNode &$node) {
        return 'ORDER BY ' . $this->_buildSqlChildren($node, ', ');
    }

    /**
     * Builds SQL statement from 'orderby_item' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlOrderby_item(epQueryNode &$node) {
        $var = $this->buildSql($node->getChild('var'));
        $keys = array_keys($var); // use the first alias 
        $var = $keys[0]; $dummy_v = '';
        $this->_qq($dummy_v, $var); // $var will altered
        return  $var . ' ' . $node->getParam('direction');
    }

    /**
     * Builds SQL statement for a 'paren' node
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlParen(epQueryNode &$node) {
        return '(' . $this->_buildSqlChildren($node) . ')';
    }

    /**
     * Builds SQL statement from 'pattern'
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlPattern(epQueryNode &$node) {
        if ($pattern = $node->getParam('val')) {
            return $pattern;
        }
        return $this->_buildSqlChildren($node);
    }

    /**
     * Builds SQL statement from 'placeholder' node
     * @return mixed
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlPlaceholder(epQueryNode &$node) {
        return $this->getPlaceholderValue($node);
    }

    /**
     * Builds SQL statement from 'select' node
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlSelect(epQueryNode &$node) {

        // order matters!!
        
        // build aggregate
        $aggregate = $this->qid($this->root_alias.'.*');
        if ($n = $node->getChild('aggregate')) {
            $aggregate = $this->aggr_func = $this->buildSqlAggregate($n);
        }

        // build where 
        $where = array();
        $n = $node->getChild('where');
        foreach($this->root_classes as $root_class) {
            if ($n) {
                $this->aliases[$this->root_alias] = $root_class;
                $where[] = $w = $this->buildSqlWhere($n);
            } else {
                $where[] = 'WHERE 1=1';
            }
        }

        // build limit
        $limit = '';
        if ($n = $node->getChild('limit')) {
            $limit = $this->limit = $this->buildSqlLimit($n);
        }

        // build orderby
        $orderby = '';
        if ($n = $node->getChild('orderby')) {
            $orderby = $this->orderby = $this->buildSqlOrderby($n);
        }

        // build from (must be last)
        $from = array();
        if ($n = $node->getChild('from')) {
            foreach($this->root_classes as $root_class) {
                $this->aliases[$this->root_alias] = $root_class;
                $from[] = $this->buildSqlFrom($n);
            }
        }

        // get the first part select+distinct+aggregate
        $sql1 = "SELECT DISTINCT $aggregate ";

        // get the second part order by + limit
        $sql2 = '';
        if ($orderby) {
            $sql2 .= ' '.$orderby;
        }
        if ($limit) {
            $sql2 .= ' '.$limit;
        }
        
        // add the froms and wheres
        for ($i = 0; $i < count($this->root_classes); $i ++) {
            $sql[] = $sql1.$from[$i].' '.$where[$i];
        }

        // only one sql stmt, so orderby and limit won't mess up
        if (count($this->root_classes) == 1) {
            $this->limit = $this->orderby = false;
            $sql[0] .= $sql2;
        }
        
        return $sql;
    }

    /**
     * Builds SQL statement from 'string' node
     * @return string
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlString(epQueryNode &$node) {
        return $node->getParam('val');
    }

    /**
     * Builds SQL statement from 'variable' node
     * 
     * The returning array is an associative array keyed by the primitive
     * variable in the form of '<alias>.<var_name>' and the value is the
     * condition for the relationship fields. 
     * 
     * @return false|array
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlVariable(epQueryNode &$node) {

        // get all field maps
        if (!($fms = $node->getParam('fmaps'))) {
            throw new epExceptionQueryBuilder($this->_e("[Internal] Cannot find field maps", $node));
            return false;
        }

        // array to keep wheres for reltaionship field
        $wheres_r = array();
        $where_prim = false;

        // go through each field map
        foreach($fms as $fm) {
            
            // is it a primary field?
            if ($fm instanceof epFieldMapPrimitive) {
                // if not, time to break 
                $where_prim = $this->_buildSqlFieldMapPrimitive($fm);
                break;
            }
            
            // build sql from field map
            if (!($wheres = $this->_buildSqlFieldMapRelationship($fm))) {
                throw new epExceptionQueryBuilder($this->_e("[Internal] empty where expressions", $node));
                continue;
            }
            
            // is it an error message?
            if (is_string($wheres)) {
                throw new epExceptionQueryBuilder($this->_e($wheres, $node));
                continue;
            }
            
            // collect wheres
            $wheres_r[] = $wheres;
        }

        // compute Cartesian product for relationship field
        $wheres = array($node->getParam('alias') => '');
        foreach($wheres_r as $wheres_) {
            $wheres = $this->_cartesianProduct($wheres, $wheres_);
        }

        // no primitive 'where' for?
        if (!$where_prim) {
            // return now if so
            return $wheres;
        }
        
        // finally put together with primitive var
        $results = array();
        foreach($wheres as $alias => $where) {
            $where_prim = str_replace(self::DUMMY_ALIAS, $alias, $where_prim);
            $results[$where_prim] = $where;
        }
        
        return $results;
    }

    /**
     * Builds SQL statement from 'variable' node
     * @return boolean
     * @throws epExceptionQueryBuilder
     */
    protected function buildSqlWhere(epQueryNode &$node) {
        
        // string for where clause
        $where = '';
        
        // any extra wheres from aggregate function etc?
        if ($this->wheres) {
            $where .= implode(' AND ', $this->wheres);
        }
        
        // get where expression from children nodes
        $where = $this->_buildSqlChildren($node);

        // if where empty, make sure we at least have '1=1'
        $where = trim($where) ? $where : '1=1';

        // prepend 'where '
        return 'WHERE ' . $where;
    }

    /**
     * Call children's SQL builders
     * @return boolean
     */
    protected function _buildSqlChildren(epQueryNode &$node, $seperator = ' ') {
        $results = array();
        if ($children = $node->getChildren()) {
            foreach($children as &$child) {
                $results[] = $this->buildSql($child);
            }
        }
        return implode($seperator, $results);
    }


    /**
     * Builds SQL statement from an object
     * @param epObject &$o the object 
     * @param epFieldMap &$fm the field map of the var that the object is assigned to
     * @param string $alias
     * @return array
     */
    protected function _buildSqlObject(epObject &$o, epFieldMap &$fm, $alias = self::DUMMY_ALIAS) {
        
        $o->epSetSearching(true);

        // get class map
        $cm = $this->em->getClassMap($fm->getClass());

        // for a committed object, simply use OID
        if ($oid = $o->epGetObjectId()) {
            $sql = $this->qid($alias).'.'.$this->qid($cm->getOidColumn());
            $sql .= '='.$this->q($oid);
            return array($sql);
        }

        // arrays to keep expressions for primitve and relationship vars
        $exprs_prm = array(''); 
        $exprs_rel = array(''); 
        
        // go through each var
        foreach($o as $var => $val) {

            // get field map for var
            if (!($fm = $cm->getField($var))) {
                continue;
            }

            // primitive var
            if ($fm->isPrimitive() && !is_null($val)) {
                $expr = $this->qid($alias).'.'.$this->qid($fm->getColumnName());
                $expr .= '=' . $this->q($val, $fm);
                $exprs_prm[] = $expr;
                continue;
            }
            
            // relationship var
            if (!$val) {
                // done if empty value or has been visited
                continue;
            }

            // is it a many-valued var?
            $vals = $val;
            if (!$fm->isMany()) {
                // arrayize it if not an array
                $vals = array($val);
            }
            
            // go through each value in array
            foreach($vals as $val) {
                
                // build sql for the relationship field map
                $exprs_rf = $this->_buildSqlFieldMapRelationship($fm, $alias);
                
                // is it an error message?
                if (is_string($exprs_rf)) {
                    throw new epExceptionQueryBuilder($this->_e($exprs));
                    continue;
                }
                
                
                // concat sql for field map ($exprs_rf) and sql for var value ($exprs_rv)
                foreach($exprs_rf as $alias_ => $expr_rf) {
                    
                    // recursion: build sql for the relationship var
                    $exprs_rv = $this->_buildSqlObject($val, $fm, $alias_);

                    // collect expression for relationship var
                    foreach($exprs_rv as $expr_rv) {
                        $exprs_rel[] = $expr_rf . ' AND ' . $expr_rv;
                    }
                }
            }
        }

        // array to hold final results
        $exprs_obj = array();

        // concat sql for primitive and relationship fields
        foreach($exprs_rel as $expr_rel) {
            foreach($exprs_prm as $expr_prm) {
                if (!$expr_rel && $expr_prm) {
                    $exprs_obj[] = $expr_prm;
                } 
                else if ($expr_rel && !$expr_prm) {
                    $exprs_obj[] = $expr_rel;
                } 
                else if ($expr_rel && $expr_prm) {
                    $exprs_obj[] = $expr_rel . ' AND ' . $expr_prm;
                }
            }
        }
        
        $o->epSetSearching(false);

        return $exprs_obj;
    }

    /**
     * Builds SQL statements for a primary field map.
     * @param epFieldMap $fm
     * @param string $alias_a the alias of the parent class
     * @param string $op the operation that follows this var
     * @return string|array (error message if string)
     */
    protected function _buildSqlFieldMapPrimitive($fm, $alias_a = self::DUMMY_ALIAS) {
        return $this->qid($alias_a) . '.' . $this->qid($fm->getName());
    }

    /**
     * Builds SQL statements for a relationship field map.
     * @param epFieldMap $fm
     * @param string $alias_a the alias of the parent class
     * @return string|array (error message if string)
     */
    protected function _buildSqlFieldMapRelationship($fm, $alias_a = self::DUMMY_ALIAS) {
        
        // get cm_a, base_a, var_a, base_b
        $cm_a = $fm->getClassMap();
        $base_a = $cm_a->getName();
        $var_a = $fm->getName();
        $base_b = $fm->getClass();

        // call manager to get relation table for base class a and b
        if (!($rt = $this->em->getRelationTable($base_a, $base_b))) {
            return "[Internal] No relationship table for [$base_a] and [$base_b]";
        }

        // change base_a to the actual child being used
        if ($base_a == $this->root_class) {
            $base_a = $this->aliases[$this->root_alias];
        }

        // alias for rt relation table
        $rta = $this->qid($this->generateRtAlias($rt));
        
        // get class map for base_b
        if (!($cm_base_b = $this->em->getClassMap($base_b))) {
            return "[Internal] No class map for [$base_b]";
        }
        
        // get all subclasses of base_b (true: recursive)
        if (!($cms_b = $cm_base_b->getChildren(true))) { 
            // make sure we have an array even if no subclasses
            $cms_b = array();
        }
        // add base_b into array
        $cms_b[] = $cm_base_b;
        
        // quote value or id (avoid repeating!)
        $alias_a = $this->qid($alias_a);
        $class_a = $this->q($base_a);
        $var_a = $this->q($var_a);
        $oid_a = $this->qid($cm_a->getOidColumn());
        $base_b = $this->q($base_b);
        
        // array to keep OR conditions
        $wheres = array();
        
        // go through each subclass
        foreach($cms_b as $cm_b) {

            // get class_b and generate an alias 
            $class_b = $this->q($cm_b->getName());
            $alias_b = $this->qid($alias_b_ = $this->generateAlias($class_b));
            $oid_b = $this->qid($cm_b->getOidColumn());
            
            // array to keep all AND where conditions
            $where = array();
            $where[] = $rta.'.class_a = '.$class_a;
            $where[] = $rta.'.var_a = '.$var_a;
            $where[] = $rta.'.oid_a = '.$alias_a.'.'.$oid_a;
            $where[] = $rta.'.base_b = '.$base_b;
            $where[] = $rta.'.class_b = '.$class_b;
            $where[] = $rta.'.oid_b = '.$alias_b.'.'.$oid_b;
            
            // put into AND array
            $wheres[$alias_b_] = implode(' AND ', $where); // $alias_b_: alias_b before quoting
        }
        
        return $wheres;
    }

    /**
     * Computes the Cartesian product of two where arrays
     * Example: 
     * <code>
     * 
     *   // input a
     *   $wheres_a = array(
     *       'a1' => 'where_a1',
     *       'a2' => 'where_a2',
     *   );
     * 
     *   // input b
     *   $wheres_b = array(
     *       'b1' => 'where_b1',
     *       'b2' => 'where_b2',
     *       'b3' => 'where_b3',
     *   );
     * 
     *   // results
     *   $results = array(
     *        'b1' => 'where_a1 AND where_b1',
     *        'b1' => 'where_a2 AND where_b1',
     *        'b2' => 'where_a1 AND where_b2',
     *        'b2' => 'where_a2 AND where_b2',
     *        'b3' => 'where_a1 AND where_b3',
     *        'b3' => 'where_a2 AND where_b3',
     *   );
     * 
     * </code>
     * 
     * @param array $wheres
     * @param array $wheres_
     * @return array
     */
    private function _cartesianProduct($wheres_a, $wheres_b, $op = 'AND') {
        $results = array();
        foreach($wheres_a as $alias_a => $where_a) {
            foreach($wheres_b as $alias_b => $where_b) {
                // replace dummy alias id in $where_ with alias
                $where_b = str_replace(self::DUMMY_ALIAS, $alias_a, $where_b);
                // collect concat'ed (i.e. $where_a . $where_b)
                if ($where_a) {
                    $results[$alias_b] = $where_a . ' ' . $op . ' ' . $where_b;
                } else {
                    $results[$alias_b] = $where_b;
                }
            }
        }
        return $results;
    }

    /**
     * Build SQL for an operation that involves LHS and RHS operands for a primitive var
     * @param array|string $left_exprs the LHS expression
     * @param string $left_type the LHS node type
     * @param array|string $right_exprs the RHS expression
     * @param string $right_type the RHS node type
     * @param string $op the operation
     * @return string
     */
    protected function _buildSqlOpera($left_exprs, $left_type, $right_exprs, $right_type, $op) {

        // arrayize exprs
        if (!is_array($left_exprs)) {
            $left_exprs = array($left_exprs => '');
        }
        if (!is_array($right_exprs)) {
            $right_exprs = array($right_exprs => '');
        }

        $op_exprs = array();
        foreach($left_exprs as $left_pvar => $left_expr) {
            foreach($right_exprs as $right_pvar => $right_expr) {
                
                // collect exprs
                $exprs = array();

                // quote values
                $emsg = $this->qq($left_pvar, $left_type, $right_pvar, $right_type);
                if (is_string($emsg)) {
                    throw new epExceptionQueryBuilder($this->_e($emsg, $node));
                    continue;
                }


                // put left and right exprs
                if ($left_expr) {
                    $exprs[] = $left_expr;
                } 
                if ($right_expr) {
                    $exprs[] = $right_expr;
                }
                $exprs[] = $left_pvar.$op.$right_pvar;

                // append to comparsion exprs
                $op_exprs[] = implode(' AND ', $exprs);
            }
        }

        return implode(' OR ', $op_exprs);
    }
    
    /**
     * @param array|string $left_exprs the LHS expression
     * @param string $left_type the LHS node type
     * @param array|string $right_exprs the RHS expression
     * @param string $right_type the RHS node type
     * @param string $op the operation
     * @return false|string (either 'left' or 'right' to indicate value is on which side)
     */
    private function _isRelationshipOp($left_exprs, $left_type, $right_exprs, $right_type, $op) {
        
        // must be an assignment operation
        if ($op != '=') {
            return false;
        }

        // if left node type is placeholder
        if ($left_type == EPQ_N_PLACEHOLDER 
            && (is_array($left_exprs) || $left_exprs instanceof epObject)) {
            if ($right_type == EPQ_N_VARIABLE && is_array($right_exprs)) {
                foreach($right_exprs as $right_pvar => $right_expr) {
                    if (false === strpos($right_pvar, '.')) {
                        return 'LHS'; // value is on LHS
                    }
                }
            }
        }

        // if right node type is placeholder
        if ($right_type == EPQ_N_PLACEHOLDER 
            && (is_array($right_exprs) || $right_exprs instanceof epObject)) {
            if ($left_type == EPQ_N_VARIABLE && is_array($left_exprs)) {
                foreach($left_exprs as $left_pvar => $left_expr) {
                    if (false === strpos($left_pvar, '.')) {
                        return 'RHS'; // value is on RHS
                    }
                }
            }
        }

        return false;
    }

    /**
     * Builds SQL statement from 'contains' or alias assignment node
     * @param epQueryNode &$node either a 'contains' node or an assignment node
     * @param epFieldMap $fm the starting field map
     * @param array $var_exprs expressions for var
     * @param array|epObject $arg the argument|value for the operation
     * @return false|string
     * @throws epExceptionQueryBuilder
     */
    protected function _buildSqlRelationship(epQueryNode &$node, $fm, $var_exprs, $arg) {
        
        // is arg a string
        if (is_string($arg)) {
            // go through each where (notice &)
            foreach($var_exprs as $alias => &$expr) {
                // remove last alias 
                $expr = str_replace($alias, $arg, $expr);
                // remove this alias (important)
                unset($this->aliases[$alias]);
            }
            return implode(' OR ', $var_exprs);
        } 
        
        // or an array or epObject
        else if (is_array($arg) || ($arg instanceof epObject)) {

            // convert array to an object
            $o = $arg;
            if (is_array($arg)) {
                
                // false: no event dispatching, true: null vars if no value
                $o = & $this->em->createFromArray($fm->getClass(), $arg, false, true);
                
                // object and children not to be committed
                $o->epSetCommittable(false, true);
            }
            
            // array to keep all expressions
            $exprs = array();

            // go through each var exprs
            foreach($var_exprs as $var_alias => $var_expr) {
                
                // build sql for array or object
                $obj_exprs = $this->_buildSqlObject($o, $fm, $var_alias);
                
                // go through each returned expr from the array or object
                foreach($obj_exprs as $obj_expr) {
                    $exprs[] = $obj_expr . ' AND ' . $var_expr;
                }
            }

            return implode(' OR ', $exprs);
        }

        // unrecognized
        throw new epExceptionQueryBuilder($this->_e("Unrecognized argument in 'contains()'", $node));
        return false;
    }

    /**
     * Calls database to quote value
     * @param string $v
     * @param epFieldMap $fm
     * @return string
     */
    private function q($v, $fm = null) {
        return $this->db->quote($v, $fm);
    }

    /**
     * Calls database to quote identifier
     * @param string $id
     * @return string
     */
    private function qid($id) {
        return $this->db->quoteId($id);
    }

    /**
     * Quotes left and right hand primitive values according to node type
     * @param mixed $left_v the lhs value (will be modified)
     * @param string $left_nt the lhs node type
     * @param mixed $right_v  the rhs value (will be modified)
     * @param string $right_nt the rhs node type
     * @return string|true
     */
    protected function qq(&$left_v, $left_nt, &$right_v, $right_nt) {
        // case one: left variable, right value
        if ($left_nt == EPQ_N_VARIABLE 
            && $right_nt != EPQ_N_VARIABLE 
            && false === strpos($right_nt, 'EPQ_N_EXPR_')) {
            return $this->_qq($right_v, $left_v);
        } 
        // case two: left value, right variable
        else if ($left_nt != EPQ_N_VARIABLE 
                 && false === strpos($left_nt, 'EPQ_N_EXPR_')
                 && $right_nt == EPQ_N_VARIABLE) {
            return $this->_qq($left_v, $right_v);
        }
        // done for all else
        return true;
    }

    /**
     * Quotes primitive value with its primitve variable (alias.var)
     * @param mixed $v
     * @param string $pvar
     * @return true|string (error message if string)
     */
    private function _qq(&$v, &$pvar) {

        // unquote pvar
        $pvar_ = $this->uq($pvar);

        // split primitive var to alias and var
        list($alias, $var) = explode('.', $pvar_);
        if (!$alias || !$var) {
            return "Invalid primitive variable [$pvar]";
        }

        // get the class for alias
        if (!($class = $this->aliases[$alias])) {
            return "No class found for alias [$alias]";
        }

        // get class map 
        if (!($cm = $this->em->getClassMap($class))) {
            return "No class map for [$class]";
        }

        // is var 'oid'?
        if ($var == 'oid') {
            // replace var name with column name
            $pvar = $this->qid($alias).'.'.$this->qid($cm->getOidColumn());
        } 
        // then a regular var
        else {

            // get field map (for non-oid field)
            if (!($fm = $cm->getField($var))) {
                return "No field map for [$class::$var]";
            }

            // replace var name with column name
            $pvar = $this->qid($alias).'.'.$this->qid($fm->getColumnName());

            // quote value
            $v = $this->q($v, $fm);
        }
        
        return true;
    }

    /**
     * Unquote a sting
     * @param string $s
     * @return string
     */
    protected function uq($s) {
        return str_replace(array("'",'"','`'), '', $s);
    }

    /**
     * Generates a unique alias name for a class
     * @param string $class the relationship table name
     * @return string
     */
    protected function generateAlias($class) {
        
        // auto genarete a unique alias
        $alias = '_'.$this->alias_id ++;
        
        // put alias into relation alias table for later use
        $this->aliases[$alias] = $this->uq($class);
        
        return $alias;
    }

    /**
     * Generates a unique alias name for a relationship table
     * @param string $rtable the relationship table name
     * @return string
     */
    protected function generateRtAlias($rtable) {
        
        // auto genarete a unique alias
        $alias = '_'.$this->alias_id ++;

        // put alias into relation alias table for later use
        $this->rt_aliases[$alias] = $rtable;
        
        return $alias;
    }

    /**
     * Returns error message with pointer to the original query
     * @param string $msg
     * @param epQueryNode $node
     * @return epExceptionQueryBuilder
     */
    private function _e($msg, $node = false) {
        
        if (!$node) {
            $node = $this->root;
        }

        $l = $node->getParam('line') - 1;
        $c = $node->getParam('char');
        
        // find the right line
        $pos = 0;
        while ($l && false !== ($pos_ = strpos($this->query, "\n"))) {
            $pos = $pos_;
            $l --;
        }
        $pos += $c;

        // find word start and end
        $start = $pos;
        while ($start && $this->query[$start] != ' ') {
            $start --;
        }
        
        $len = strlen($this->query);

        $end = $pos;
        while ($end < $len && $this->query[$end] != ' ') {
            $end ++;
        }
        
        $s = substr($this->query, $start = max(0, $start - 10), $pos-1-$start);
        $s .= '###' . substr($this->query, $pos-1, min($end + 10, $len));
        
        // append pointer
        $msg .= ' (near "... ' . $s . ' ...")';

        return $msg;
    }

}

?>
