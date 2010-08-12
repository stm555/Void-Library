<?php
/**
 * Collection that uses a Zend\Cache as it's internal object container
 *  -- should be less memory intensive for persistent collections
 **/
namespace Void\Collection;

use Void\Collection;

class Cache extends Collection
{
    static protected $_cacheId = 0;

    protected $cachePrefix;

    protected $cache;

    /**
     * Create the cache and cachePrefix to be used by this collection
     **/
    public function __construct( array $items = array(), array $orderMembers = array(), $backend = 'Apc', array $backendOptions = array() )
    {
        //increment the cacheId so the next collection_cache gets a different prefix
        $this->cachePrefix = 'Collection' . self::$_cacheId++ . '_';
        $this->cache = \Zend\Cache::factory( 'Core', $backend, array( 'automatic_serialization' => true ), $backendOptions );
        //items array is not used in the Collection_Cache object
        unset($this->_items);

        parent::__construct( $items, $orderMembers );
    }

    protected function generateCacheKey( $key )
    {
        return $this->cachePrefix . md5( $key );
    }

    protected function store( $key, $item )
    {
        $this->cache->save( $item, $this->generateCacheKey($key) );
    }

    /**
     * Main method for loading real result object and returning it. overloading
     * class is encouraged to utilize a caching methodology
     * @param integer $id Id of the result to retrieve
     * @throws Exception any inability to fetch the result should throw an exception
     **/
    protected function _getResult( $key ) {
        if ( $item = $this->cache->load( $this->generateCacheKey( $key ) ) ) {
            return $item;
        }
        throw new Exception( 'Invalid Key: ' . $key );
    }

    /**
     * @param string $index
     * @return boolean whether the index exists
     **/
    public function offsetExists( $index ) {
        return ( isset( $this->_orderAttributes[ $index ] )
             &&  $this->cache->test( $this->generateCacheKey( $index ) ) );
    }

    public function getTop()
    {
        if( !isset( $this->_order[0] ) ){
            return false;
        }
        try { return $this->_getResult( $this->_order[0] ); }
        catch ( Exception $e ) {
            return false;
        }
    }
}

//tell firebug not to encode some members
include_once( 'FirePHPCore/fb.php' );
FB::setObjectFilter( 'Collection_Cache', array( '_orderAttributes', 'cache' ) );
