<?php

/**
 * $Id: epObject.php,v 1.71 2005/11/09 12:47:40 nauhygon Exp $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/11/09 12:47:40 $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */

/**
 * need epBase
 */
include_once(EP_SRC_BASE.'/epBase.php');

/**
 * Need epOverload for epObjectWrapper
 */
include_once(EP_SRC_BASE.'/epOverload.php');

/**
 * Exception class for {@link epObject}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/11/09 12:47:40 $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epExceptionObject extends epException {
}

/**
 * The Countable interface is introduced in PHP 5.1.0.For PHP versions 
 * earlier than 5.1.0, we need to declare the interface. 
 */
if (!interface_exists('Countable', false)) {
    
    /**
     * Interface Countable 
     * @author Oak Nauhygon <ezpdo4php@gmail.com>
     * @version $Revision$ $Date: 2005/11/09 12:47:40 $
     * @package ezpdo
     * @subpackage ezpdo.runtime
     */
    interface Countable {

        /**
         * Returns the number of items
         * @return integer
         */
        public function count();
    }

}

/**
 * Interface of an ezpdo persistent object 
 * 
 * For an object to be persisted, it needs to implement this 
 * interface through which the persistence manager ({@link 
 * epManager}) can access its data members. 
 * 
 * If an object does not implement this interface and still wants 
 * to be persisted, the persistence manager is able to wrap it in 
 * {@link epObjectWrapper}. The wrapper implements this interface
 * and serves as the interface between the object and the manager. 
 * One limitation of this approach is that it can only access 
 * the object's public data members. 
 * 
 * A persistent object has an object id (OID) which is used to 
 * identify an object within a class and is persisted in datastore. 
 * You can think of the OID as the auto-incremental field "oid" 
 * in a relational table. Keep in mind that its significance is 
 * only within a class. 
 * 
 * The object also has a uid (Unique Id), which is a -transient- 
 * id for identifying an object amoung all living objects in 
 * the memory. 
 * 
 * The object also implements the SPL interfaces, {@link 
 * http://www.php.net/~helly/php/ext/spl/interfaceIteratorAggregate.html 
 * IteratorAggregate} and 
 * {@link http://www.php.net/~helly/php/ext/spl/interfaceArrayAccess.html 
 * ArrayAccess} so the object can be easily accessed as an array. 
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/11/09 12:47:40 $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
interface epObject extends IteratorAggregate, ArrayAccess, Countable {
    
    /**
     * Gets the persistence manager of the object's class 
     * @return epManager
     */
    public function epGetManager();
    
    /**
     * Gets the class (name) of the object 
     * @return string
     */
    public function epGetClass();
    
    /**
     * Gets the class mapping info of the object's class 
     * @return epClassMap
     */
    public function epGetClassMap();
    
    /**
     * Gets object uid 
     * @return integer
     */
    public function epGetUId();
    
    /**
     * Gets object id 
     * @return integer
     */
    public function epGetObjectId();
    
    /**
     * Sets object id
     * @return integer
     */
    public function epSetObjectId($oid);
    
    /**
     * Returns all variable (names and values)
     * @return array
     * @access public
     */
    public function epGetVars(); 
    
    /**
     * Checks if the object has the variable of the given name
     * @param string $var_name (the variable name)
     * @return bool
     * @access public
     */
    public function epIsVar($var_name); 
    
    /**
     * Checks if a variable is a primitive field
     * @param string $var_name variable name
     * @return bool
     * @access public
     */
    public function epIsPrimitive($var_name); 

    /**
     * Checks if a variable is single-valued relationship field
     * @param string $var_name variable name
     * @return bool
     * @access public
     */
    public function epIsSingle($var_name); 
    
    /**
     * Checks if a variable is many-valued relationship field
     * @param string $var_name variable name
     * @return bool
     * @access public
     */
    public function epIsMany($var_name); 
    
    /**
     * Returns the class of an relational field
     * @param string $var_name variable name
     * @return bool
     * @access public
     */
    public function epGetClassOfVar($var_name);

    /**
     * Returns the value of a variable 
     * @param string $var_name variable name
     * @return mixed
     * @access public
     * @throws epExceptionObject
     */
    public function &epGet($var_name); 
    
    /**
     * Sets the value to a variable
     * @param string $var_name variable name
     * @param mixed value 
     * @access public
     * @throws epExceptionObject
     */
    public function epSet($var_name, $value); 
    
    /**
     * Checks if object has been deleted 
     * @return bool
     * @access public
     */
    public function epIsDeleted();

    /**
     * Set object being deleted 
     * @return bool
     * @access public
     */
    public function epSetDeleted();

    /**
     * Checks if object is 'dirty' (different than what's in datastore)
     * @return bool
     * @access public
     */
    public function epIsDirty();

    /**
     * Explicitly sets whether object is dirty or not.
     * If the param $is_dirty is false,  all modified vars are cleared
     * @param bool
     * @access public
     */
    public function epSetDirty($is_dirty = true);
    
    /**
     * Notify the manager before and after change(s) to the object 
     * @param string either 'onPreChange' or 'onPostChange'
     * @param array 
     * @return bool
     * @access public
     */
    public function epNotifyChange($event, $vars);
    
    /**
     * Gets the vars that have been modified. 
     * Modified vars are cleared in {@link epSetDirty()}
     * @access public
     * @throws epExceptionObject
     */
    public function epGetModifiedVars();
    
    /**
     * Copies the values of the vars from another object or an associative array
     * @param epObject|array $from
     * @return bool 
     * @access public
     */
    public function epCopyVars($from);
    
    /**
     * Checks if the variables of this object matches all the non-null variables in another object
     * @param epObject $o the example object
     * @param bool $deep should this be a deep search
     * @return bool 
     * @access public
     */
    public function epMatches($o, $deep = false);
    
    /**
     * Checks if object is a committable object 
     * 
     * Only a committable will be commited. An example object used in find() 
     * is rendered as uncommittable (See {@link epManagerBase::find()}).
     * 
     * @return bool
     * @access public
     */
    public function epIsCommittable();
    
    /**
     * Explicitly sets whether the object is committable or not.
     * @param bool
     * @access public
     */
    public function epSetCommittable($is_committable = true, $children = false);

    /**
     * Checks if object is updating value to its variable
     * 
     * Inverse vars need to maintain consistency between them when either 
     * one is updated. This flag is used during updating values to inverse 
     * to prevent endless loops.
     * 
     * @param string $var the variable that is being set a new value
     * @return bool
     * @access public
     */
    public function epIsUpdating();

    /**
     * Set flag of inverse updating
     * 
     * @param string $var the variable that is being set a new value
     * @return bool
     * @access public
     */
    public function epSetUpdating();

    /**
     * Checks if object is being searched for
     * 
     * Building search queries with children objects need
     * to know which objects are currently being used
     * so an infinite loop doesn't occur
     * 
     * @return bool
     * @access public
     */
    public function epIsSearching();

    /**
     * Set flag of searching status
     * 
     * @param bool $var the new status
     * @return bool
     * @access public
     */
    public function epSetSearching($status = true);

    /**
     * Checks if object is being matched against
     * 
     * Deep matches can cause infinite recursion
     * 
     * @return bool
     * @access public
     */
    public function epIsMatching();

    /**
     * Set flag of matching status
     * 
     * @param bool $var the new status
     * @return bool
     * @access public
     */
    public function epSetMatching($status = true);

    /**
     * Checks if object is being committed
     * 
     * @return bool
     * @access public
     */
    public function epIsCommitting();
    
    /**
     * Set the object is being committed
     * 
     * Setting this flag is important for persisting object relationships. 
     * A committable object is committed after all its relational fields
     * (vars) are committed. And this flag prevents the same object from 
     * being commited more than once.
     * 
     * @param bool
     * @access public
     */
    public function epSetCommitting($committing = true);

    /**
     * Check if a method exists in the object
     * @param string 
     * @return bool
     */
    public function epMethodExists($method);

    /**
     * Check if the object needs to be commited
     * 
     * An object needs to be committed when it is dirty ({@link epIsDirty()})
     * or new ({@link epGetObjectId()} == false), and it is committable ({@link 
     * epIsCommittable()}), not being committed ({@link epIsCommitting}), nor
     * being deleted ({@link epIsDeleted()}).
     */
    public function epNeedsCommit();

    /**
     * Returns whether the object in transition
     * @return bool
     */
    public function epInTransaction();
    
    /**
     * Signals the object to prepare for a transaction
     * 
     * In this method, the object needs to make a backup of its current state,
     * which later can be used to rollback this state should the transaction
     * fall at the end of the transaction (@link epEndTransaction())
     * 
     * @return bool
     */
    public function epStartTransaction();
    
    /**
     * Signals the object to end the current transaction
     * 
     * In this method, the object marks the end of the current transaction and
     * if rollback is required, it should fall back to the state before the current
     * transaction was started (@link epStartTransaction). 
     * 
     * @return bool
     */
    public function epEndTransaction($rollback = false);

}

/**
 * Class of an EZPDO array
 * 
 * This class is used for the "many" fields in an epObject
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/11/09 12:47:40 $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epArray implements IteratorAggregate, ArrayAccess, Countable {
    
    /**#@+
     * Actions for updating inverse
     */
    const UPDATE_INVERSE_ADD = 'update_inverse_add'; // add
    const UPDATE_INVERSE_REMOVE = 'update_inverse_remove'; // remove
    /**#@-*/

    /**
     * The internal array
     * @var array
     */
    protected $array = array();
    
    /**
     * The epObject this array is associated to 
     * @var epObject
     */
    protected $o = false;

    /**
     * The associated class
     * @var string
     */
    protected $class = false;

    /**
     * The field map
     * @var epFieldMap
     */
    protected $fm = false;

    /**
     * Whether to mark the associated object dirty
     * @var boolean
     */
    protected $clean = false;

    /**
     * Constructor
     * @param null|epObject (the epObject this array is associated to)
     * @param array (the array to be copied)
     * @param string (the associated class)
     */
    public function __construct($o = null, $array = false, $class = false, $fm = false) {
        
        if (is_array($array) && $array) {
            $this->array = $array;
        }
        
        $this->setObject($o);
        $this->setClass($class);
        $this->setFieldMap($fm);
    }

    /**
     * Returns the associated object
     * @return epObject
     * @access public
     */
    public function &getObject() {
        return $this->o;
    }
    
    /**
     * Set the class name of the objects to contained in the array
     * @param epObject $o
     * @return void
     * @access public
     */
    public function setObject($o) {
        if ($o && $o instanceof epObject) {
            $this->o = & $o;
        }
    }

    /**
     * Returns the class name of the objects to contained in the array
     * @return string
     * @access public
     */
    public function getClass() {
        return $this->class;
    }
    
    /**
     * Set the associated class
     * @param string $class
     * @return void
     * @access public
     */
    public function setClass($class) {
        $this->class = $class;
    }
    
    /**
     * Returns the field map
     * @return epFieldMap
     * @access public
     */
    public function getFieldMap() {
        return $this->fm;
    }
    
    /**
     * Set the field map
     * @param epFieldMap
     * @return void
     * @access public
     */
    public function setFieldMap($fm) {
        $this->fm = $fm;
    }
    
    /**
     * Returns whehter to flag object dirty
     * @return boolean
     * @access public
     */
    public function getClean() {
        return $this->clean;
    }
    
    /**
     * Set whether to flag the associated object dirty
     * @param boolean $clean
     * @return void
     * @access public
     */
    public function setClean($clean = true) {
        $this->clean = $clean;
    }
    
    /**
     * Implements Countable::count(). 
     * Returns the number of vars in the object
     * @return integer
     */
    public function count() {
        
        // clean up deleted objects
        $this->_cleanUp();
        
        return count($this->array); 
    }
     
    /**
     * Implements IteratorAggregate::getIterator()
     * Returns the iterator which is an ArrayIterator object connected to the array
     * @return ArrayIterator
     */
    public function getIterator() {
        
        // clean up deleted objects
        $this->_cleanUp();
        
        // convert encoded oid into objects before "foreach" is called
        foreach($this->array as $k => $v) {
            $this->offsetGet($k);
        }

        // return the array iterator
        return new ArrayIterator($this->array);
    }
     
    /**
     * Implements ArrayAccess::offsetExists()
     * @return boolean
     */
    public function offsetExists($index) {
        
        // clean up deleted objects
        $this->_cleanUp();
        
        return isset($this->array[$index]);
    }
    
    /**
     * Implements ArrayAccess::offsetGet()
     * @return mixed
     */
    public function offsetGet($index) {
        
        // clean up deleted objects
        $this->_cleanUp();
        
        // check if value of index is set
        if (!isset($this->array[$index])) {
            return null;
        }
        
        // convert eoid (if string) into 
        if (is_string($this->array[$index])) {
            $this->array[$index] = $this->_oid2Object($this->array[$index]);
        }
        
        // return the object
        return $this->array[$index];
    }
     
    /**
     * Implements ArrayAccess::offsetSet()
     * @return void
     */
    public function offsetSet($index, $newval) {
        
        // clean up deleted objects
        $this->_cleanUp();
        
        // if new value is in array
        if ($this->inArray($newval)) {
            // done
            return;
        }

        // notify on pre change
        if ($this->o && !$this->clean) {
            $this->_notifyChange('onPreChange');
        }

        // set new value to index
        if (!$index) {
            $this->array[] = & $newval;
            $this->_updateInverse($newval, self::UPDATE_INVERSE_ADD);
        } else {
            if (count($this->array) > $index) {
                $oldval = $this->array[$index];
                $this->_updateInverse($oldval, self::UPDATE_INVERSE_REMOVE);
            }
            $this->array[$index] = & $newval;
            $this->_updateInverse($newval, self::UPDATE_INVERSE_ADD);
        }

        // notify the associated object that array has changed
        if ($this->o && !$this->clean) {
            $this->o->epSetDirty(true);
            $this->_notifyChange('onPostChange');
        }
    }

    /**
     * Implements ArrayAccess::offsetUnset()
     * @return mixed
     */
     public function offsetUnset($index) {
        
         // clean up deleted objects
         $this->_cleanUp();
         
         if (isset($this->array[$index])) {
             
             // notify on pre change
             if ($this->o && !$this->clean) {
                 $this->_notifyChange('onPreChange');
             }

             // update inverse
             $this->_updateInverse($this->array[$index], self::UPDATE_INVERSE_REMOVE);
             
             // unset the index
             unset($this->array[$index]);
             
             // notify the associated object that array has changed
             if ($this->o && !$this->clean) {
                 $this->o->epSetDirty(true);
                 $this->_notifyChange('onPostChange');
             }
         }
     }

    /**
     * Remove an object (using UId as identifier)
     * @return void
     * @access protected
     */
    public function remove($o) {
        
        // done if no items
        if (!$this->array) {
            return false;
        }
        
        // go through each object
        foreach($this as $k => $v) {
            
            // check if value is an epObject
            if (!($v instanceof epObject)) {
                continue;
            }
            
            // check if this is the object to be deleted
            if ($o->epGetUId() == $v->epGetUId()) {
                
                // notify on pre change
                if ($this->o && !$this->clean) {
                    $this->_notifyChange('onPreChange');
                }

                // update inverse
                $this->_updateInverse($v, self::UPDATE_INVERSE_REMOVE);

                unset($this->array[$k]);

                // notify the associated object that array has changed
                if ($this->o && !$this->clean) {
                    $this->o->epSetDirty(true);
                    $this->_notifyChange('onPostChange');
                }
                
                return true;
            }
        }

        return false;
    }

    /**
     * Remove all items
     * @return void
     * @access public
     */
    public function removeAll() {
        
        if ($this->array) {
            
            // notify on pre change
            if ($this->o && !$this->clean) {
                $this->_notifyChange('onPreChange');
            }

            // go through each object
            foreach($this as $k => $v) {
                // update inverse
                $this->_updateInverse($v, self::UPDATE_INVERSE_REMOVE);
            }

            // remove everything in array
            $this->array = array();

            // notify the associated object that array has changed
            if ($this->o && !$this->clean) {
                $this->o->epSetDirty(true);
                $this->_notifyChange('onPostChange');
            }
        }
    }

    /**
     * Checks if value is in array
     * @param mixed $v
     * @return bool
     * @access public
     */
    public function inArray($val) {
        
        // for non-object, do the usual matching
        if (!is_object($val)) {
            return in_array($val, $this->array);
        } 
        
        // if value is an epObject
        if ($val instanceof epObject) {
            foreach($this->array as $k => $v) {
                $v = $this->offsetGet($k);
                if ($v->epGetUId() == $val->epGetUId()) {
                    return true;
                }
            }
        }

        return false;
    }
    
    /**
     * Convert the encoded oid
     * @param string $eoid (encoded object id)
     * @return epObject
     */
    protected function &_oid2Object($eoid) {
        
        // is this array associated with an object
        if (!$this->o) {
            return $eoid;
        }
        
        // get manager
        if (!($m = $this->o->epGetManager())) {
            return $eoid;
        }
        
        // call manager to get object by encoded id
        if ($o = $m->getByEncodeOid($eoid)) {
            return $o;
        }
        
        // return encode oid if can't get object
        return $eoid;
    }
    
    /**
     * Clean up all deleted objects
     * @return void
     * @access protected
     */
    protected function _cleanUp() {
        
        // done if no items
        if (!$this->array) {
            return;
        }
        
        // go through each object
        foreach($this->array as $k => $v) {
            if (($v instanceof epObject) && $v->epIsDeleted()) {
                unset($this->array[$k]);
            }
        }
    }
    
    /**
     * Update the value of the inverse var
     * @param epObject $o the opposite object
     * @param string $actoin UPDATE_INVERSE_ADD or UPDATE_INVERSE_REMOVE
     * @return boolean
     */
    protected function _updateInverse(&$o, $action = self::UPDATE_INVERSE_ADD) {
        
        // check if object is epObject
        if (!$o || !$this->fm || !($o instanceof epObject)) {
            return false;
        }

        // get inverse var
        if (!($ivar = $this->fm->getInverse())) {
            return true;
        }

        // no action if an object is updating (to prevent endless loop)
        if ($o->epIsUpdating()) {
            return true;
        }

        // set inverse updating flag
        $o->epSetUpdating(true);

        // a single-valued field 
        if (!$o->epIsMany($ivar)) {
            switch ($action) {
                case self::UPDATE_INVERSE_ADD:
                    $o[$ivar] = $this->o;
                    break;
                case self::UPDATE_INVERSE_REMOVE:
                    $o[$ivar] = null;
                    break;
            }
        }

        // a many-valued field
        else {
            switch ($action) {
                case self::UPDATE_INVERSE_ADD:
                    $o[$ivar][] = $this->o;
                    break;
                case self::UPDATE_INVERSE_REMOVE:
                    $o[$ivar]->remove($this->o);
                    break;
            }
        }
        
        // reset inverse updating flag
        $o->epSetUpdating(false);

        return true;
    }

    /**
     * Notify the object change has been made to the var this array is associtated to
     * @param string $event (either 'onPreChange' or 'onPostChange')
     * @return bool
     */
    protected function _notifyChange($event) {
        
        // get var name
        $var = '';
        if ($this->fm) {
            $var = $this->fm->getName();
        }
        
        // call object to notify the manager this change
        return $this->o->epNotifyChange($event, array($var));
    }
}

/**
 * Exception class for {@link epObject}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/11/09 12:47:40 $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epExceptionObjectWrapperBase extends epExceptionObject {
}

/**
 * A wrapper class that provides the {@link epObject} interface
 * to an object who wants to be persisted. 
 * 
 * Since it's drived from {@link epOverload}, all methods in the 
 * original class of the object being wrapped can be called 
 * directly. 
 * 
 * This is the base class for object wrapping for persistency 
 * and does not concern object relationships, which are due in
 * the subclass {@link epObjectWrapper}.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/11/09 12:47:40 $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epObjectWrapperBase extends epOverload implements epObject {
    
    /**
     * Constant: not a getter or setter
     */
    const EP_NEITHER_GETTER_NOR_SETTER = "EP_NEITHER_GETTER_NOR_SETTER";
    
    /**#@+
     * Used for return value to avoid reference notice in 5.0.x and up
     * @var bool
     */
    static public $false = false;
    static public $true = true;
    static public $null = null;
    /**#@-*/

    /**
     * The object wrapped (cached)
     */
    protected $ep_object = false;
    
    /**
     * The object's uid (used to id objects in memory)
     */
    protected $ep_uid = false;
    
    /**
     * The object id
     * @var false|integer
     */
    protected $ep_object_id = false;
    
    /**
     * The deleted flag
     * @var bool
     */
    protected $ep_is_deleted = false; 

    /**
     * The dirty flag
     * @var bool
     */
    protected $ep_is_dirty = false; 
    
    /**
     * The committable flag (true by default)
     * @var bool
     */
    protected $ep_is_committable = true; 
    
    /**
     * The inverse updating flag
     * @var bool
     */
    protected $ep_is_updating = false;
    
    /**
     * The searching flag
     * @var bool
     */
    protected $ep_is_searching = false;
    
    /**
     * The matching flag
     * @var bool
     */
    protected $ep_is_matching = false;

    /**
     * The flag of object being committing (false by default)
     * @var bool
     */
    protected $ep_is_committing = false; 

    /**
     * Array to keep vars before __call()
     * @var array (keyed by var names)
     */
    protected $ep_vars_before = false; 
    
    /**
     * Cached persistence manager
     * @var epManager
     */
    protected $ep_m = false;
    
    /**
     * Cached class map for this object
     * @var epClassMap
     */
    protected $ep_cm = false;
    
    /**
     * Cached field maps for the variables
     * @var array (of epFieldMap) 
     */
    protected $ep_fms = array();

    /**
     * The cached var names
     * @var array
     */
    protected $ep_cached_vars = array();
    
    /**
     * The modified vars
     * @var array
     */
    protected $ep_modified_vars = array();

    /**
     * Constructor
     * @param object  
     * @param epManager
     * @param epClassMap
     * @see epOverload
     */
    public function __construct($o, $m = null, $cm = null) {
        
        if (!is_object($o)) {
            throw new epExceptionObjectWrapperBase("Cannot wrap a non-object");
        }
        
        $this->_wrapObject($o);
        $this->_collectVars();

        $this->ep_m = & $m;
        $this->ep_cm = & $cm;
    }
    
    /**
     * Gets the persistence manager
     * @return epManager
     * @access public
     */
    public function epGetManager() {
        return $this->ep_m;
    }
    
    /**
     * Gets the class (name) of the object 
     * @return string
     */
    public function epGetClass() {
        return $this->ep_cm ? $this->ep_cm->getName() : get_class($this->ep_object);
    }
    
    /**
     * Gets class map for this object
     * @return epClassMap
     * @throws epExceptionManager, epExceptionObject
     */
    public function epGetClassMap() {
        return $this->ep_cm;
    }
    
    /**
     * Gets object uid 
     * @return integer
     */
    public function epGetUId() {
        return $this->ep_uid;
    }
    
    /**
     * Gets object id
     * @return integer
     */
    public function epGetObjectId() {
        return $this->ep_object_id;
    }
    
    /**
     * Sets object id. Should only be called by {@link epManager}
     * Note that object id can be set only once (when it is persisted for the first time).
     * @return void
     * @throws epExceptionObjectWrapperBase
     */
    public function epSetObjectId($oid) {
        if ($this->ep_object_id === false) {
            $this->ep_object_id = $oid;
        } else {
            throw new epExceptionObjectWrapperBase("Cannot alter object id");
        }
    }
    
    /**
     * Returns all variable names and values
     * @return array (var_name => var_value)
     * @access public
     */
    public function epGetVars() {
        
        // get vars from the wrapped object
        $vars = get_object_vars($this->ep_object);
        
        // append oid to array
        $vars['oid'] = $this->ep_object_id;
        
        return $vars;
    }
    
    /**
     * Checks if the object has the variable 
     * @param string $var_name variable name
     * @return bool
     * @access public
     */
    public function epIsVar($var_name) {
        // check if we can find a best-matched var name
        return in_array($var_name, $this->ep_cached_vars);
    }

    /**
     * Checks if a variable is a primitive field
     * @param string $var_name variable name
     * @return bool
     * @access public
     */
    public function epIsPrimitive($var_name) {
        
        // get field map of the variable 
        if (!($fm = & $this->_getFieldMap($var_name))) {
            // without field map, we assume the var is -always- primitive
            return true;
        }

        // return whether var is a single valued field
        return $fm->isPrimitive();
    }

    /**
     * Checks if a variable is single-valued relationship field
     * @param string $var_name variable name
     * @return bool
     * @access public
     */
    public function epIsSingle($var_name) {
        
        // get field map of the variable 
        if (!($fm = & $this->_getFieldMap($var_name))) {
            return false;
        }

        // return whether var is a single valued field
        return $fm->isSingle();
    }
    
    /**
     * Checks if a variable is many-valued relationship field
     * @param string $var_name variable name
     * @return bool
     * @access public
     */
    public function epIsMany($var_name) {
        
        // get field map of the variable 
        if (!($fm = & $this->_getFieldMap($var_name))) {
            return false;
        }
        
        // return whether var is a many valued field
        return $fm->isMany();
    }

    /**
     * Returns the class of an relational field
     * @param string $var_name variable name
     * @return bool
     * @access public
     */
    public function epGetClassOfVar($var_name) {
        
        // get field map of the variable 
        if (!($fm = & $this->_getFieldMap($var_name))) {
            return false;
        }
        
        // return the related class
        return $fm->getClass();
    }

    /**
     * Returns the value of a variable 
     * @param string $var_name variable name
     * @return mixed
     * @access public
     */
    public function &epGet($var_name) {
        
        // is var oid? 
        if ($var_name == 'oid') {
            return $this->ep_object_id;
        }
        
        // get the best-matched var name
        if (!in_array($var_name, $this->ep_cached_vars)) {
            throw new epExceptionObjectWrapperBase('Variable [' . $var_name . '] does not exist or is not accessible by ezpdo');
            return self::$false;
        }
        
        return $this->ep_object->$var_name;
    }
    
    /**
     * Sets the value to a variable
     * @param string $var_name variable name
     * @param mixed value
     * @param boolean clean (if true no dirty flag change)
     * @return bool
     * @access public
     */
    public function epSet($var_name, $value, $clean = false) {
        
        // find the best-matched var name
        if (!in_array($var_name, $this->ep_cached_vars)) {
            throw new epExceptionObjectWrapperBase('Variable [' . $var_name . '] does not exist or is not accessible by ezpdo');
            return false;
        }
        
        // only when old and new value differ
        if ($this->ep_object->$var_name !== $value) {
            
            // notify change
            if (!$clean) {
                $this->epNotifyChange('onPreChange', array($var_name));
            }

            // change var value
            $this->ep_object->$var_name = $value;
            
            // keep track of which var has been modified
            $this->ep_modified_vars[$var_name] = $value;
            
            // flag object dirty if it's not a clean 'set' 
            if (!$clean) {
                $this->epSetDirty(true);
                $this->epNotifyChange('onPostChange', array($var_name));
            }
        }
        
        return true;
    }
    
    /**
     * Checks if object has been deleted 
     * @return bool
     * @access public
     */
    public function epIsDeleted() {
        return $this->ep_is_deleted;
    }

    /**
     * Set object being deleted 
     * @return bool
     * @access public
     */
    public function epSetDeleted($is_deleted = true) {
        $this->ep_is_deleted = $is_deleted;
    }

    /**
     * Checks if object is 'dirty' (ie whether it's different than what's in datastore)
     * @return bool
     * @access public
     */
    public function epIsDirty() {
        return $this->ep_is_dirty;
    }
    
    /**
     * Explicitly sets object is dirty or not. 
     * @param bool
     * @return void
     * @access public
     */
    public function epSetDirty($is_dirty = true) {
        
        // set the dirty flag
        $this->ep_is_dirty = $is_dirty;
        
        // if not dirty 
        if (!$this->ep_is_dirty) {
            // clear modified vars
            $this->ep_modified_vars = array();
        }

    }
    
    /**
     * Notify the manager before and after object change  
     * @param string either 'onPreChange' or 'onPostChange'
     * @param array 
     * @return bool
     * @access public
     */
    public function epNotifyChange($event, $vars){
        if (!$this->ep_m) {
            return false;
        }
        return $this->ep_m->notifyChange($this, $event, $vars);
    }

    /**
     * Checks if object is a committable object 
     * Only a commmitable object can be commited.
     * @return bool
     * @access public
     */
    public function epIsCommittable() {
        return $this->ep_is_committable;
    }

    /**
     * Explicitly sets whether object is a committable object or not.
     * @param bool
     * @access public
     */
    public function epSetCommittable($is_committable = true, $children = false) {
        $this->ep_is_committable = $is_committable;
        if ($children) {
            $this->epSetSearching(true);
            $vars = $this->epGetVars();
            while (list($var, $val) = each($vars)) { 
                
                // get field map
                if (!($fm = & $this->_getFieldMap($var))) {
                    // should not happen
                    continue;
                }
                
                // exclude null values (including empty strings)
                if (is_null($val) || (!$val && $fm->getType() == epFieldMap::DT_CHAR)) {
                    continue;
                }
                
                // exclude non-primitive fields
                if (!$fm->isPrimitive()) {
                    if ($val instanceof epArray) {

                        /*
                         * Explanation of alias naming:
                         * _{Depth}{Count}
                         * Depth is where are we in the child level (0 being parent)
                         * Count is which field are we on at the moment (not used on the parent since there is only one)
                         */

                        foreach ($val->getIterator() as $obj) {
                            if (!$obj || $obj->epIsSearching()) {
                                continue;
                            }
                            $obj->epSetCommittable($is_committable, true);
                        }
                    } elseif ($val instanceof epObjectWrapper && !$val->epIsSearching()) {
                        $val->epSetCommittable($is_committable, true);
                    }
                }
            }
            $this->epSetSearching(false);
        }
    }
    
    /**
     * Checks if object is setting value to a variable (a relationship field)
     * @return bool
     * @access public
     */
    public function epIsUpdating() {
        return  $this->ep_is_updating;
    }

    /**
     * Set flag of inverse updating
     * @param bool $updating
     * @return void
     * @access public
     */
    public function epSetUpdating($updating = true) {
        $this->ep_is_updating = $updating;
    }

    /**
     * Checks if object is being used in a select query
     * @return bool
     * @access public
     */
    public function epIsSearching() {
        return  $this->ep_is_searching;
    }

    /**
     * Set flag of searching status
     * @param bool $updating
     * @return void
     * @access public
     */
    public function epSetSearching($status = true) {
        $this->ep_is_searching = $status;
    }

    /**
     * Checks if object is being matched against
     * 
     * Deep matches can cause infinite recursion
     * 
     * @return bool
     * @access public
     */
    public function epIsMatching() {
        return $this->ep_is_matching;
    }

    /**
     * Set flag of matching status
     * 
     * @param bool $var the new status
     * @return bool
     * @access public
     */
    public function epSetMatching($status = true) {
        $this->ep_is_matching = $status;
    }

    /**
     * Checks if object is being committed
     * @return bool
     * @access public
     */
    public function epIsCommitting() {
        return $this->ep_is_committing;
    }
    
    /**
     * Set the object is being committed
     * 
     * Setting this flag is important for persisting object relationships. 
     * A committable object is committed after all its relational fields
     * (vars) are committed. And this flag prevents the same object from 
     * being commited more than once.
     * 
     * @param bool $is_committing
     * @access public
     */
    public function epSetCommitting($is_committing = true) {
        $this->ep_is_committing = $is_committing;
    }

    /**
     * Gets the vars that have been modified. 
     * Modified vars are cleared when {@link epSetDirty(false)} is called.
     * @access public
     * @throws epExceptionObject
     */
    public function epGetModifiedVars() {
        return $this->ep_modified_vars;
    }
    
    /**
     * Copy the values of the vars from another object. Note object id
     * is not copied. 
     * @param epObject|array $from
     * @return bool 
     */
    public function epCopyVars($from) {
        
        // the argument must be either an array or an epObject
        if (!is_array($from) && !($from instanceof epObject)) {
            return false;
        }
        
        // array-ize if epObject
        $var_values = $from;
        if ($from instanceof epObject) {
            $var_values = $from->epGetVars();
        }
        
        // copy over values from input
        $status = true;
        foreach($var_values as $var => $value) {
            //  to vars that exist in this object
            if (in_array($var, $this->ep_cached_vars)) {
                $status &= $this->epSet($var, $value);
            }
        }
        
        return $status;
    }
    
    /**
     * Checks if the variables of this object matches all the 
     * non-null variables in another object
     * 
     * <b>
     * Note: for now we only match primitive fields. For relational 
     * fields, things can get complicated as we may be dealing with 
     * very "deep" comparisons and recursions.
     * </b>
     * 
     * @param epObject $o the example object
     * @param bool $deep should this be a deep search
     * @return bool 
     */
    public function epMatches($o, $deep = false) {
        
        if (!($o instanceof epObject)) {
            return false;
        }
        
        // same object if same object uid
        if ($this->ep_uid == $o->epGetUId()) {
            return true;
        }
        
        // matches if the example object does not have any non-null variable
        if (!($vars_o = $o->epGetVars())) {
            return true;
        }
        $this->epSetMatching(true);
        $o->epSetMatching(true);
        
        // get vars of this object
        $vars = $this->epGetVars();
        
        // go through each var from the example object
        foreach($vars_o as $var => $value) {

            if ($var == 'oid') {
                continue;
            }
            
            // ignore null values (including empty string)
            if (is_null($value) || (is_string($value) && !$value)) {
                continue;
            }
            
            // skip non-primitive fields (see note in method comment above)
            // unless we are doing a deep search
            if (!$this->epIsPrimitive($var)) {
                if ($deep) {
                    if ($value instanceof epArray) {
                        // the epArray can have different order between the
                        // two objects, so we have to do an n^2 loop to see
                        // if they are the same (ugly)
                        foreach ($value->getIterator() as $obj) {
                            if (!$obj) {
                                continue;
                            }
                            $matches = array();
                            $matched = false;
                            // we are working on this one already, don't mess with it
                            if ($obj->epIsMatching()) {
                                continue;
                            }

                            foreach ($vars[$var]->getIterator() as $temp) {
                                if (in_array($temp->epGetUId(), $matches)) {
                                    continue;
                                }

                                if (!$temp->epIsMatching() && !$obj->epIsMatching()) {
                                    if ($temp->epMatches($obj, true)) {
                                        // we have matched this temp object
                                        // we don't want to check it again
                                        $matches[] = $temp->epGetUId();
                                        $matched = true;
                                        break;
                                    }
                                }
                            }
                            if (!$matched) {
                                $this->epSetMatching(false);
                                $o->epSetMatching(false);
                                return false;
                            }
                        }
                    } elseif ($value instanceof epObjectWrapper && !$value->epIsMatching()) {
                        if (!isset($vars[$var])) {
                            // we need to check this
                            $vars[$var] = $this->epGet($var);
                        }
                        if (!$value->epIsMatching() && !$vars[$var]->epIsMatching()) {
                            if (!$vars[$var]->epMatches($value, true)) {
                                $this->epSetMatching(false);
                                $o->epSetMatching(false);
                                return false;
                            }
                        } elseif ($value->epIsMatching() && !$vars[$var]->epIsMatching()) {
                            // if one is matching and the other is not, they are not equal
                            $this->epSetMatching(false);
                            $o->epSetMatching(false);
                            return false;
                        } elseif (!$value->epIsMatching() && $vars[$var]->epIsMatching()) {
                            $this->epSetMatching(false);
                            $o->epSetMatching(false);
                            return false;
                        }
                    }
                }
                continue;
            }
            
            // is var in the objects's vars?
            if (!isset($vars[$var])) {
                $this->epSetMatching(false);
                $o->epSetMatching(false);
                return false;
            }
            
            // are the values same?
            if ($vars[$var] != $value) {
                $this->epSetMatching(false);
                $o->epSetMatching(false);
                return false;
            }
        
        }
        $this->epSetMatching(false);
        $o->epSetMatching(false);
        
        return true;
    }

    /**
     * Checks if a method exists in the object
     * @param string 
     * @return bool
     */
    public function epMethodExists($method) {
        
        // sanity check: method name should not be empty 
        if (!$method || !$this->ep_object) {
            return false;
        }
        
        // call the cached object to check if method exists
        return method_exists($this->ep_object, $method);
    }

    /**
     * Check if the object needs to be commited
     * 
     * An object needs to be committed when it is either dirty or new (object 
     * id is not set), and it is committable, not being committed, nor being 
     * deleted.
     */
    public function epNeedsCommit() {
        return (
            ($this->ep_is_dirty 
             || !$this->ep_object_id) 
            && $this->ep_is_committable 
            && !$this->ep_is_committing 
            && !$this->ep_is_deleted
            );
    }

    /**
     * Implemented in subclass {@link epObjectWrapper}
     * Returns whether the object in transition
     * 
     * Implemented in subclass {@link epObjectWrapper}
     * 
     * @return bool
     */
    public function epInTransaction() {
        return false;
    }
    
    /**
     * Signals the object to prepare for a transaction
     * 
     * In this method, the object needs to make a backup of its current state,
     * which later can be used to rollback this state should the transaction
     * fall at the end of the transaction (@link epEndTransaction())
     * 
     * Implemented in subclass {@link epObjectWrapper}
     * 
     * @return bool
     */
    public function epStartTransaction() {
        return true;
    }
    
    /**
     * Signals the object to end the current transaction
     * 
     * In this method, the object marks the end of the current transaction and
     * if rollback is required, it should fall back to the state before the current
     * transaction was started (@link epStartTransaction). 
     * 
     * Implemented in subclass {@link epObjectWrapper}
     * 
     * @return bool
     */
    public function epEndTransaction($rollback = false) {
        return true;
    }
    
    /**
     * Persist the object into datastore. 
     * Calls epManager to persist the object
     * @return bool
     */
    public function commit() { 
        if (!$this->_checkManager()) {
            return false;
        }
        return $this->ep_m->commit($this);
    }

    /**
     * Delete the object from the datastore and memory 
     * Calls epManager to delete the object.
     * @return bool
     */
    public function delete() { 
        if (!$this->_checkManager()) {
            return false;
        }
        return $this->ep_m->delete($this);
    }
    
    /**
     * Implements magic method __sleep()
     * Sets cached class map, field maps, and manager to null or empty
     * to prevent them from being serialized
     */
    public function __sleep() {
        
        // set cached class map to null so it's not serialized
        $this->ep_cm = NULL;

        // empty cached field maps so they are not serialized
        $this->ep_fms = array();

        // set manager to null so it's not serialized
        $this->ep_m = NULL;

        // return vars to be serialized
        return array_keys(get_object_vars($this));
    }

    /**
     * Implements magic method __wakeup()
     * Recovers cached manager and class map
     */
    public function __wakeup() {
        
        // recover manager when waking up
        $this->ep_m = epManager::instance();
        
        // recover class map
        $this->ep_cm = $this->ep_m->getMap(get_class($this->ep_object));

        // cache this object in manager (important for flush)
        $this->ep_m->cache($this, true); // true: force replace
    }

    /**
     * Implements magic method __get(). 
     * This method calls {@link epGet()}.
     * @param $var_name
     */
    final public function &__get($var_name) {
        return $this->epGet($var_name);
    }
    
    /**
     * Implements magic method __set(). 
     * This method calls {@link epSet()}.
     * @param $var_name
     */
    final public function __set($var_name, $value) {
        $this->epSet($var_name, $value);
    }
    
    /**
     * Intercepts getter/setters to manage persience state. 
     * @param string $method method name 
     * @param array arguments
     * @return mixed
     * @see epObject::__call()
     */
    final public function __call($method, $args) {
        
        try {
            // try getters/setters first 
            $ret = $this->_intercept($method, $args);
            if ($ret !== self::EP_NEITHER_GETTER_NOR_SETTER) {
                return $ret;
            }
        } catch (epExceptionObjectWrapperBase $e) {
            // exception: var cannot found in setter and getter
        }
        
        // 
        // after getters and setters, call epOverload::__call()
        // 
        
        // preprocess before __call()
        $this->_pre_call();
        
        // get old argument conversion flag
        $ca = $this->getConvertArguments();
        
        // force -no- argument conversion (fix bug 69)
        $this->setConvertArguments(false);
        
        // actually call method in original class
        $ret =  parent::__call($method, $args);
        
        // recover old argument conversion flag
        $ca = $this->setConvertArguments($ca);
        
        // post process after __call() 
        $this->_post_call();
        
        return $ret;
    }
    
    /**
     * Preprocess before __call()
     * @return void
     */
    protected function _pre_call() {

        // keep track of old values
        $this->ep_vars_before = get_object_vars($this->ep_object);

        // notify changes that will be made
        // !!! since we don't know which vars exactly will be changed, report none !!!
        $this->epNotifyChange('onPreChange', array());
    }
    
    /**
     * Post process after __call()
     * @return void
     */
    protected function _post_call() {
        
        // get vars after _call()
        $vars_after = get_object_vars($this->ep_object);
        
        // collect modified vars
        $changed_vars = array();
        foreach($vars_after as $var => $value) {
            
            // skip relationship field
            if (!$this->epIsPrimitive($var)) {
                continue;
            }
            
            // has var been changed?
            if (!isset($this->ep_vars_before[$var]) || $this->ep_vars_before[$var] !== $value) {
                $this->ep_modified_vars[$var] = $value;
                $changed_vars[] = $var;
                $this->epSetDirty(true);
            }
        }

        // notify changes made
        if ($changed_vars) {
            $this->epNotifyChange('onPostChange', $changed_vars);
        }
        
        // release old values
        $this->ep_vars_before = false;
    }
    
    /**
     * Intercept getters/setters
     * @param string $method method name 
     * @param array $args arguments
     * @param bool $okay
     * @return mixed
     */
    protected function _intercept($method, $args) {
        // intercept getter/setter
        if (substr($method, 0, 4) == 'get_') {
            $vn = substr($method, 4);
            return  $this->epGet($vn);
        } else if (substr($method, 0, 3) == 'get') {
            $vn = strtolower(substr($method, 3, 1)) . substr($method, 4);
            return   $this->epGet($vn);
        } if (substr($method, 0, 4) == 'set_') {
            $vn = substr($method, 4);
            return  $this->epSet($vn, isset($args[0]) ? $args[0] : null);
        } else if (substr($method, 0, 3) == 'set') {
            $vn = strtolower(substr($method, 3, 1)) . substr($method, 4);
            return  $this->epSet($vn, isset($args[0]) ? $args[0] : null);
        } 
        return self::EP_NEITHER_GETTER_NOR_SETTER;
    }

    /**
     * Wraps an object. 
     * The method checks if the arg is an object. It also checks whether it has 
     * interface {@link epObject} already. If so, it throws an exception. 
     * @param $o object
     * @return bool
     * @throws epExceptionObjectWrapperBase
     */
    protected function _wrapObject($o) {
        
        // no need to wrap an epObject instance 
        if ($o instanceof epObject) {
            throw new epExceptionObjectWrapperBase('No wrapper is needed for instance of epObject');
            return false;
        }
        
        // set the wrapped object (the foreign object)
        $this->setForeignObject($o);
        
        // cache the object for quick access
        $this->ep_object = $o;
        
        // create the uid for this object
        $this->ep_uid = uniqid();
        
        return true;
    }
    
    /**
     * Collect and cache all vars for the object
     * @return void
     * @access protected
     */
    protected function _collectVars() {
        $this->ep_cached_vars = array_keys(get_object_vars($this->ep_object));
    }
    
    /**
     * Checks persistence manager 
     * @return bool
     * @throws epExceptionObjectWrapperBase
     */
    public function _checkManager() { 
        if (!$this->ep_m) {
            throw new epExceptionObjectWrapperBase('This object is not associated with persistence manager');
            return false;
        }
        return true;
    }

    /**
     * Returns the field map for the variable 
     * Note that param $var_name will be replaced with the best matched var name
     * @param string $var_name
     * @return false|epFieldMap
     */
    protected function &_getFieldMap($var_name) {
        
        // check if we have class map
        if (!$this->ep_cm || !$var_name) {
            return self::$false;
        }
        
        // check if field map is cached
        if (isset($this->ep_fms[$var_name])) {
            return $this->ep_fms[$var_name];
        }

        // get field map
        $fm = $this->ep_cm->getField($var_name);
        
        // cache it 
        $this->ep_fms[$var_name] = $fm;
        
        // return the field map for the var
        return $fm;
    }
    
    /**
     * Implements Countable::count(). 
     * Returns the number of vars in the object
     * @return integer
     */
    public function count() {
        return count($this->epGetVars()); // oid included
    }
     
     /**
     * Implements IteratorAggregate::getIterator()
     * Returns the iterator which is an ArrayIterator object connected to the vars
     * @return ArrayIterator
     */
    public function getIterator () {
        
        // get all vars of the object
        $vars = array();
        foreach($this->epGetVars() as $k => $v) { 
            $vars[$k] = $this->epGet($k);
        }
        
        // return the array iterator
        return new ArrayIterator($vars);
    }
     
    /**
     * Implements ArrayAccess::offsetExists()
     * @return boolean
     */
    public function offsetExists ($index) {
        
        // is the index oid?
        if ($index == 'oid') {
            return true;
        }

        return in_array($index, $this->ep_cached_vars);
    }
    
    /**
     * Implements ArrayAccess::offsetGet()
     * @return mixed
     */
    public function offsetGet ($index) {
        return $this->epGet($index);
    }
     
    /**
     * Implements ArrayAccess::offsetSet()
     * @return mixed
     */
    public function offsetSet ($index, $newval) {
        if ($index == 'oid') {
            throw new epExceptionObjectWrapperBase('Object ID cannot be set through array access');
        }
        $this->epSet($index, $newval);
    }

    /**
     * Implements ArrayAccess::offsetUnset()
     * @return mixed
     * @throws epExceptionObjectWrapperBase
     */
     public function offsetUnset ($index) {
        if ($index == 'oid') {
            throw new epExceptionObjectWrapperBase('Object ID cannot be set through array access');
        }
        $this->o->epSet($index, null);
     }
     
     /**
      * Implement magic method __toString()
      * 
      * This method can be handily used for debugging purpose. 
      * Simply use "echo" to dump the object's info. 
      * 
      * @return string
      */
     public function __toString() {
         
         // indentation
         $indent = '  ';

         // the output string
         $s = '';

         // class for the object
         $s .= 'object (' . $this->epGetClassMap()->getName() . ')' . "\n";
         
         // object id
         $s .= $indent . 'oid : ' . $this->epGetObjectId() . "\n";

         // object uid
         $s .= $indent . 'uid : ' . $this->epGetUId() . "\n";

         // dirty flag
         $s .= $indent . 'is dirty?  : ';
         if ($this->epIsDirty()) {
             $s .= 'yes';
         } else {
             $s .= 'no';
         }
         $s .= "\n";

         // dirty flag
         $s .= $indent . 'is committable?  : ';
         if ($this->epIsCommittable()) {
             $s .= 'yes';
         } else {
             $s .= 'no';
         }
         $s .= "\n";

         // delete flag
         $s .= $indent . 'is deleted?  : ';
         if ($this->epIsDeleted()) {
             $s .= 'yes';
         } else {
             $s .= 'no';
         }
         $s .= "\n";

         // vars
         $vars = $this->epGetVars();

         // go through each var from the example object
         $s .= $indent . 'vars' . "\n";
         $indent .= $indent;
         foreach($vars as $var => $value) {
             
             // skip oid
             if ($var == 'oid') {
                 continue;
             }

             // output var name
             $s .= $indent . '[' . $var . ']: ';
             
             // re-get value so objects are loaded
             $value = $this->epGet($var);
             
             if ($value instanceof epObject) {
                 $s .= $this->ep_m->encodeClassOid($value); 
             } 
             else if ($value instanceof epArray) {
                 $s .= $value->getClass() . '(' . $value->count() . ')';
             } 
             else {
                 $s .= print_r($value, true);
             }
             
             $s .= "\n";
         }

         // return the string
         return $s;
     }

}

/**
 * Exception class for {@link epObjectWrapper}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/11/09 12:47:40 $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epExceptionObjectWrapper extends epExceptionObjectWrapperBase {
}

/**
 * Class of epObjectWrapper
 * 
 * This class is derived from {@link epObjectWrapperBase}. The base class 
 * does not deal with object relationship mapping, which is addressed 
 * here.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/11/09 12:47:40 $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epObjectWrapper extends epObjectWrapperBase {
    
    /**#@+
     * Actions for updating inverse
     */
    const UPDATE_INVERSE_ADD = 'update_inverse_add'; // add
    const UPDATE_INVERSE_REMOVE = 'update_inverse_remove'; // remove
    /**#@-*/

    /**
     * Array to hold the names of the fetched relational variables
     * @var array
     */
    protected $ep_rvars_fetched = array();
    
    /**
     * The array to keep fall-back state when a transaction starts.
     * It is also used as the indicator of whether the object is 
     * currently in transaction (true if not false). 
     * @var array
     * @see epStartTransaction
     */
    protected $ep_trans_backup = false;

    /**
     * Constructor
     * @param object  
     * @param epManager
     * @param epClassMap
     * @see epOverload
     */
    public function __construct($o, $m = null, $cm = null) {
        parent::__construct($o, $m, $cm);
    }
    
    /**
     * Overrides {@link epObjectWrapperBase::epGet()}
     * 
     * If the variable is a relational field (non-primitive), check if
     * it's set to oid (an integer) or an array of oids. If so, convert
     * the oid(s) into related objects. This is doing the lazy loading, 
     * or the "proxy" pattern: fetching the object by its oid only when 
     * it's needed.
     * 
     * @param string $var_name variable name
     * @return mixed
     * @access public
     * @throws epExceptionManager, epExceptionObject
     */
    public function &epGet($var_name) {
        
        // do the usual stuff 
        $val = & parent::epGet($var_name);
        
        // done if either manger, class map or field map not found
        if (!$this->ep_m || !$this->ep_cm || !($fm = $this->_getFieldMap($var_name))) {
            return $val;
        }
        
        // return now if primitive field
        if ($fm->isPrimitive()) {
            return $val;
        }

        // get oid(s) for the datastore only if all the following are true
        // (1) the relational var's value is empty, 
        // (2) this object has been stored in db, and
        // (3) this var has not be retrieved from db yet 
        if (!$val || !($val instanceof epObject) && !($val instanceof epArray)) {
            
            // get oids from db only if object has been persisted and the var
            // has not been fetched - condition (2) and (3)
            $oids = array();
            if ($this->epGetObjectId() && !in_array($var_name, $this->ep_rvars_fetched)) {
                
                // call manager to get relational oids (single string if only one)
                $oids = $this->ep_m->getRelationIds($this, $fm, $this->ep_cm);
                
                // mark the relational var fetched
                $this->ep_rvars_fetched[] = $fm->getName();
            } 

            // if no oids found and its a one-valued field, make it null
            if ($fm->isSingle() && !$oids) {
                $oids = null;
            }

            // set oids to the varialbe (no dirty flag change)
            $this->epSet($var_name, $oids, true); // true: no change of dirty flag

            // reget var value
            $val = & parent::epGet($var_name);
        }
        
        // simply convert oid string into object for one-valued relationship field
        if (is_string($val)) {
            $val = & $this->_oid2Object($val);
            return $val;
        }

        return $val;
    }
    
    /**
     * Override {@link epObjectWrapperBase::epSet()}
     * Sets the value to a variable
     * Special treatment for "many" fields: replace with epArray
     * @param string $var_name variable name
     * @param mixed $value
     * @param boolean $clean (dirty flag is not set if true) 
     * @return bool
     * @access public
     * @throws epExceptionManager, epExceptionObject
     */
    public function epSet($var_name, $value, $clean = false) {
        
        // do the usual stuff if manger or class map or field map not found
        if (!$this->ep_m || !$this->ep_cm || !($fm = & $this->_getFieldMap($var_name))) {
            return parent::epSet($var_name, $value, $clean);
        }
        
        // check type for non-primitive
        if (!$this->_checkValueType($value, $fm)) {
            $varinfo = $this->ep_cm->getName() . '::' . $fm->getName();
            throw new epExceptionObject('Value be set to ' . $varinfo . ' has invalid type');
            return false;
        }

        // return now if not a relatiionship field
        if ($fm->isPrimitive()) {
            // do the usual stuff
            return parent::epSet($var_name, $value, $clean);
        }

        // return now if not a "many" field
        if (!$fm->isMany()) {
            
            $status = true;
            
            if ($value0 = parent::epGet($var_name)) {
                // udpate inverse remove
                $status &= $this->_updateInverse($fm, $value0, self::UPDATE_INVERSE_REMOVE);
            }

            // do the usual stuff
            $status &= parent::epSet($var_name, $value, $clean);
            
            // udpate inverse
            if ($value && $value instanceof epObject) {
                $status &= $this->_updateInverse($fm, $value, self::UPDATE_INVERSE_ADD);
            }

            return $status;
        }

        // if value is false, null, or neither an array nor an epObject
        if ($value === false || is_null($value) 
            || !is_array($value) && !($value instanceof epObject)) {

            $status = true;
            
            if ($value0 = parent::epGet($var_name)) {
                // udpate inverse remove
                $status &= $this->_updateInverse($fm, $value0, self::UPDATE_INVERSE_REMOVE);
            }

            // do the usual stuff
            $status &= parent::epSet($var_name, $value, $clean);

            return $status;
        }
        
        $status = true;
        
        // make sure we have allocate an array for the var
        if (!($var = & parent::epGet($var_name)) || !($var instanceof epArray)) {
            
            // if var is not epArray, make one
            $var_ = new epArray($this, $var, $fm->getClass(), $fm);

            // set it (the new epArray) back
            $status &= parent::epSet($var_name, $var_, true); // true: no change in dirty flag
            
            // replace var with newly created epArray
            $var = $var_;
        }
        
        // make value an array if not
        if (!is_array($value)) {
            $value = array($value);
        }

        // backup clean flag in epArray
        $clean0 = $var->getClean();

        // set clean flag
        $var->setClean($clean);
        
        // remove all items before copying array
        $var->removeAll();

        // if so, add items one by one
        foreach($value as $v) {
            $var[] = $v;
        }

        // restore clean flag
        $var->setClean($clean0);

        return $status;
    }

    /**
     * Returns whether the object in transition
     * @return bool
     */
    public function epInTransaction() {
        return $this->ep_trans_backup !== false;
    }
    
    /**
     * Signals the object to prepare for a transaction
     * 
     * In this method, the object needs to make a backup of its current state,
     * which later can be used to rollback this state should the transaction
     * fail at the end of the transaction (@link epEndTransaction())
     * 
     * @return bool
     */
    public function epStartTransaction() {
        
        // array to hold wrapper vars and object vars
        $this->ep_trans_backup = array();

        // keep serialized wrapper vars
        $this->ep_trans_backup['wrapper_vars'] = $this->_backupWrapperVars();

        // keep serialized object vars
        $this->ep_trans_backup['object_vars'] = $this->_backupObjectVars();

        return true;
    }

    /**
     * Signals the object to end the current transaction
     * 
     * In this method, the object marks the end of the current transaction and
     * if rollback is required, it should fall back to the state before the current
     * transaction was started (@link epStartTransaction). 
     * 
     * @return bool
     */
    public function epEndTransaction($rollback = false) {
        
        // need to roll back?
        if ($rollback) {
            
            // restore (unserialize) wrapper vars
            $this->_restoreWrapperVars($this->ep_trans_backup['wrapper_vars']);
            
            // restore (unserialize) object vars
            $this->_restoreObjectVars($this->ep_trans_backup['object_vars']);
        }
        
        // reset backup to false (not in transaction)
        $this->ep_trans_backup = false;
        
        return true;
    }
    
    /**
     * Backup the wrapper vars
     * @return array 
     */
    protected function _backupWrapperVars() {
        
        // vars in this wrapper to be backed up 
        static $wrapper_vars = array(
            'ep_uid',
            'ep_object_id',
            'ep_is_dirty',
            'ep_modified_vars',
            );
        
        // put wrapper vars into an array
        $backup = array();
        foreach($wrapper_vars as $var) {
            $backup[$var] = $this->$var;
        }

        return $backup;
    }

    /**
     * Restore the wrapper vars
     * @param array $backup
     * @return void
     */
    protected function _restoreWrapperVars($backup) {
        
        // set vars back to wrapper
        foreach($backup as $var => $value) {
            $this->$var = $value;
        }
    }

    /**
     * Backup the object vars
     * @return array 
     */
    protected function _backupObjectVars() {
        $backup = array();
        foreach($this->ep_cached_vars as $var) {
            // is var primitve?
            if ($this->epIsPrimitive($var)) {
                // primitive var: simply keep value
                $backup[$var] = $this->ep_object->$var;
            }
            else {
                // relationship var: reduce value to eoid
                $backup[$var] = $this->_reduceRelationshipVar($var);
            }
        }
        return $backup;
    }

    /**
     * Restore (unserialize) the wrapper vars
     * @param string $backup
     * @return void
     */
    protected function _restoreObjectVars($backup) {
        // set vars back to wrapped object
        foreach($backup as $var => $value) {
            $this->ep_object->$var = $value;
        }
    }

    /**
     * Reduce relationship vars to encoded oids ({@see epManager::encodeClassOid})
     * @param string $var_name
     * @return string|array (of string)
     */
    protected function _reduceRelationshipVar($var_name) {
        
        // get the var
        $var = $this->ep_object->$var_name;
        
        // many-valued vars
        if (is_array($var) || $var instanceof epArray) {
            
            // go through items in array
            $rvar = array();
            foreach($var as $v) {
                if ($v instanceof epObject) {
                    $rvar[] = $this->ep_m->encodeClassOid($v);
                }
                else {
                    $rvar[] = $v;
                }
            }
        }
        
        // one-valued var
        else {
            if ($var instanceof epObject) {
                $rvar = $this->ep_m->encodeClassOid($var);
            }
            else {
                $rvar = $var;
            }
        }
        
        return $rvar;
    }

    /**
     * Override {@link epObjectWrapperBase::_pre_call()}
     * Convert all relation oids to objects
     * Preprocess before __call()
     * @return void
     */
    protected function _pre_call() {
        
        // call parent::_pre_call() first
        parent::_pre_call();
        
        // if manager and class map cannot be found, done
        if (!$this->ep_m || !$this->ep_cm) {
            return;
        }
        
        // if class map does not have any non-primitive fields, done
        if (!($npfs = $this->ep_cm->getNonPrimitive())) {
            return;
        }
        
        // get vars so relation oids are converted to objects
        foreach($npfs as $var_name => $fm) {
            $this->epGet($var_name);
        }
    }
    
    /**
     * Convert oid(s) into object(s)
     * 
     * Called by epGet() and _pre_call()
     * 
     * @param string $eoid
     * @param epFieldMap
     * @return mixed
     * @access protected
     */
    protected function &_oid2Object(&$val) {
        
        // call manager to get object by encoded id
        if (!($val_ = $this->ep_m->getByEncodeOid($val))) {
            // return old value if failed 
            return $val;
        }
        
        // return object 
        return ($val = $val_);
    }
    
    /**
     * Check if the value to be set matches the type set in orm tag
     * @param epObject|string $value
     * @param epFieldMap $fm
     * @return boolean
     */
    protected function _checkValueType($value, $fm) {
        
        // no check if no manager
        if (!$this->ep_m) {
            return true;
        }
        
        // no checking on primitve, ignore false|null|empty, and non-epObject
        if ($fm->isPrimitive() || !$value) {
            // always true
            return true;
        }
        
        // epObject
        if ($value instanceof epObject) {
            return $this->_typeMatch($this->ep_m->getClass($value), $fm->getClass());
        }
        
        // array
        else if (is_array($value) || $value instanceof epArray) {
            
            foreach($value as $k => $v) {
                
                if (!($v instanceof epObject)) {
                    continue;
                }
                
                if (!$this->_typeMatch($this->ep_m->getClass($v), $fm->getClass())) {
                    return false;
                }
            }
            return true;
        }
        
        return true;
    }
    
    /**
     * Check whether class a is class b or a subclass of class b
     * @param string $class
     * @param string $base
     * @return boolean
     */
    protected function _typeMatch($class, $base) {
        if ($class == $base) {
            return true;
        }
        return is_subclass_of($class, $base);
    }
    
    /**
     * Update the value of the inverse var
     * @param epFieldMap
     * @param epObject $o the opposite object
     * @param string $actoin UPDATE_INVERSE_ADD or UPDATE_INVERSE_REMOVE
     * @return boolean
     */
    protected function _updateInverse($fm, &$o, $action = self::UPDATE_INVERSE_ADD) {
        
        // check if object is epObject
        if (!$o || !($o instanceof epObject)) {
            return false;
        }

        // no action if an object is updating (to prevent endless loop)
        if ($o->epIsUpdating()) {
            return true;
        }

        // get inverse var
        if (!($ivar = $fm->getInverse())) {
            return true;
        }

        // set inverse updating flag
        $o->epSetUpdating(true);

        // a single-valued field 
        if (!$o->epIsMany($ivar)) {
            switch ($action) {

                case self::UPDATE_INVERSE_ADD:
                    $o[$ivar] = $this;
                    break;

                case self::UPDATE_INVERSE_REMOVE:
                    $o[$ivar] = null;
                    break;
            }
        }

        // a many-valued field
        else {
            switch ($action) {
                case self::UPDATE_INVERSE_ADD:
                    $o[$ivar][] = $this;
                    break;

                case self::UPDATE_INVERSE_REMOVE:
                    $o[$ivar]->remove($this);
                    break;
            }
        }
        
        // reset inverse updating flag
        $o->epSetUpdating(false);

        return true;
    }

}

?>
