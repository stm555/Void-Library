<?php
namespace Void\Collection;

/**
 * Class for an object which is not a collection itself,
 * but is composed of one and implements all the same interfaces that just pass
 * through to the internal collection
 *
 * @TODO convert this to just an interface with the implementations commented out
 *       as 'suggestions'
 **/
use Void\Collection;

Abstract class Composite implements Iterator, Countable, ArrayAccess
{
    /**
     * @var Collection that this object is composed of
     *                 all interface methods are proxied to this object
     **/
    protected $collection;

    /**
     * Proxies any members not defined by this class proxied to the internal collection
     * @param string $member class member to proxy
     * @return mixed Proxied value from internal collection
     **/
    public function __get( $member )
    {
        try {
            return $this->collection->$member;
        } Catch ( Exception $e ) {
            trigger_error( "Unable to proxy member $member to internal collection", E_USER_NOTICE );
        }
    }

    /**
     * Proxies any members not defined by this class proxied to the internal collection
     * @param string $member class member to proxy
     * @param mixed $value, Value to set on internal collection
     **/
    public function __set( $member, $value )
    {
        try {
            return $this->collection->$member = $value;
        } Catch ( Exception $e ) {
            trigger_error( "Unable to proxy member $member to internal collection", E_USER_NOTICE );
        }
    }

    /**
     * Proxies any methods not defined by this class to the internal collection
     * @param string $function Function not defined by this class to proxy to the internal collection
     * @param array $arguments Arguments to the function to be proxied
     * @return mixed Results from the proxied function
     **/
    public function __call( $function, $arguments )
    {
        return call_user_func_array( array( $this->collection, $function ), $arguments );
    }

    /**
     * Specific interface methods are defined below
     **/

    /**
     *   --- ITERATOR INTERFACE METHODS
     **/

    /**
     * Return value of current search result
     *  @return mixed Current Element in collection
     **/
    public function current() {
        return $this->collection->current();
    }

    /**
     * Move current position up one
     **/
    public function next() {
        $this->collection->next();
    }

    /**
     * Current position starting from 1
     * @return Current key for collection
     **/
    public function key() {
        return $this->collection->key();
    }

    /**
     * Tests whether current position is valid
     * @return boolean True when there is a result for the current position, false when there is not
     **/
    public function valid() {
        return $this->collection->valid();
    }

    /**
     * Resets posion back to the beginning
     **/
    public function rewind() {
        return $this->collection->rewind();
    }

    /**
     *  --- COUNTABLE INTERFACE METHOD
     **/

    /**
     * Returns the number of results that we have
     * @param $ignoreOverride Flag for whether to ignore the count override
     * @return integer number of results
     **/
    public function count( $ignoreOverride = false ) {
        return $this->collection->count( $ignoreOverride );
    }

    /**
     * --- ARRAYACCESS INTERFACE METHODS
     **/

    /**
     * @param string $index
     * @return boolean whether the index exists
     **/
    public function offsetExists( $index ) {
        return $this->collection->offsetExists( $index );
    }

    /**
     * @param string $index
     * @return mixed Returns the value at index
     **/
    public function offsetGet( $index ) {
        return $this->collection->offsetGet( $index );
    }

    /**
     * Adds the value to the collection based on the value
     * @param string $index
     * @param mixed $value
     **/
    public function offsetSet( $index, $value ) {
        $this->collection->offsetSet( $index, $value );
    }

    /**
     * Removes the item specified by $index from the collection
     * @param string $index
     **/
    public function offsetUnset( $index ) {
        $this->collection->offsetUnset( $index );
    }
}
