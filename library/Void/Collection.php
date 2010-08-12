<?php
/**
*
* Collection
*
* extendable collection object that supports iteration, sorting and paging
* @TODO move a lot of the 'magic' members to dedicated methods to make them
*       more apparent
*
* @access public
*/
namespace Void;

class Collection implements Iterator, Countable, ArrayAccess {

    /**
     * This should be overloaded in the extending class if sorting is enabled
     * @var array Set of valid sort methods
     **/
    protected $_sortMethods = array();

    /**
     * If you want to skip sorting, just be sure and
     * set _currentParams['sortMethod'] = $this->_sortMethod when priming
     * @var string Method to sort results by
     **/
    protected $_sortMethod = null;

    /**
     * @var integer Size of result set
     **/
    protected $_pageSize = null;

    /**
     * @var integer Page to return results from
     **/
    protected $_page = 1;

    /**
     *@var array Internal sorted order of results
     **/
    protected $_order = array();

    /**
     * Implementing class should fill this array at a minimum. Indexed by item id
     * @var array Internal selection of attributes to order by for each result
     **/
    protected $_orderAttributes = array();

    /**
     * @var integer Internal pointer to current hit
     **/
    protected $_current = 0;

    /**
     *@var string Holder for the current sortMethod in use
     **/
    protected $_currentParams = array();

    /**
     *@var array Members of the objects in collection to be stored for ordering
     **/
    protected $_orderMembers = array();

    /**
     *@var array Internal container for objects
     **/
    protected $_items = array();

    protected $_countOverride;

    /**
     * Constructor
     * @param array $objects An array of objects to optionally pre-load collection with
     * @param array $orderMembers Members of objects to use for ordering purposes
     **/
    public function __construct( array $items = array(), array $orderMembers = array() )
    {
        $this->_orderMembers = $orderMembers;

        foreach ( $items as $key => $item ) {
            $this->add( $key, $item );
        }

    }

    /**
     * Add an Item to the Collection
     * @param string $key unique key for item in collection
     * @param mixed $item Item to add to the collection
     * @param integer OPTIONAL - ordered position to place this item in
     **/
    public function add( $key, $item, $pos = null ) {
        if ( is_null( $key ) ) {
            $key = $this->count();
        }
        $this->_orderAttributes[ $key ] = array();
        foreach ( $this->_orderMembers as $member ) {
            $this->_orderAttributes[ $key ][ $member ] = $item->$member;
        }
        if ( ! is_null( $pos ) ) {
            //order array starts at 0, but pos expects an order that started at 1
            $this->_order[ $pos - 1 ] = $key;
        } elseif ( !in_array( $key, $this->_order) ) { //if this item does not all ready have an order position, add it to the end
            $this->_order[] = $key;
        }
        $this->store( $key, $item );
    }

    /**
     * Stores the item in the collection
     *  -- useful for overloading with different storage mechanisms
     * @var string unique identifier for collection item
     * @var mixed item to store
     **/
    protected function store ( $key, $item )
    {
        $this->_items[ $key ] = $item;
    }

    /**
     * Removes the item from the collection
     * -- useful for overloading with different storage mechanisms
     * @var string unique identifier for collection item
     **/
    protected function unStore( $key )
    {
        unset( $this->_items[ $key ] );
        if ( isset( $this->_orderAttributes[ $key ] ) ) {
            unset( $this->_orderAttributes[ $key ] );
        }
        //clear our sorting
        $this->_order = array();
        $this->rewind();
    }

    /**
     * Main method for loading real result object and returning it. overloading
     * class is encouraged to utilize a caching methodology
     * @param integer $id Id of the result to retrieve
     * @throws Exception any inability to fetch the result should throw an exception
     **/
    protected function _getResult( $key ) {
        if ( isset( $this->_items[ $key ] ) ) {
            return $this->_items[ $key ];
        }
        throw new Exception( 'Invalid Key' );
    }

    /**
     * Sort result set into the currently set order
     * @param string $sortMember Member of collection item to sort on
     * @param string $sortDir Direction to sort in -- just checks on the first character for A (Ascending) or D (Descending)
     **/
    public function sort( $sortMember = null, $sortDir = 'Asc', $sortType = 'Int' ) {
        if ( !is_null( $sortMember ) ) {
            if ( !in_array( $sortMember, $this->_orderMembers ) ) {
                throw new Exception( "$sortMember is not available to sort by." );
            }
            $this->_sortMethod = '_sortBy' . $sortMember;
            $descending =  ( 'D' == strtoupper( substr($sortDir, 0, 1) ) );
            $this->_sortMethod .= ( $descending )
                               ? 'Descending'
                               : 'Ascending';
            $sortCompareFunction = "_dynamicCompare{$sortMember}";
            $sortCompareFunction .= ( $descending ) ? 'Descending' : null;
            $sortCompareFunction .= "As{$sortType}";
        }
        //If we're an incremental collection ( overriden count ), don't bother sorting.
        if ( is_null( $this->_countOverride ) || $this->count() == $this->count( true ) ) {
            if ( ! is_null( $this->_sortMethod ) ) {
                //If we've all ready sorted by the current method, we don't need to sort again
                if ( ! ( isset( $this->_currentParams['sortMethod'])
                      && $this->_currentParams['sortMethod'] == $this->_sortMethod )
                  || empty( $this->_order ) ) {
                    $this->_order = array_keys( $this->_orderAttributes );
                    $sortCompareFunction = ( isset( $this->_sortMethods[ $this->_sortMethod ] ) )
                                         ? $this->_sortMethods[ $this->_sortMethod ]
                                         : $sortCompareFunction;
                    if ( !empty( $sortCompareFunction ) ) {
                        usort($this->_order, array( $this, $sortCompareFunction ) );
                    }

                }
            } else if ( empty( $this->_order ) ) {
                $this->_order = array_keys( $this->_orderAttributes );
            }
        } else if ( isset( $this->_currentParams['sortMethod'] )
                 && $this->_currentParams['sortMethod'] != $this->_sortMethod ) {
            //if the sort method changed and we're an incremental sort, just clear out our order array
            $this->_order = array();
        }
        $this->_currentParams['sortMethod'] = $this->_sortMethod;
    }

    /**
     * magic call method to be used for dynamic sorting
     *  example: comparepriceDescendingAsInt
     * @param string $method Method attempting to be called
     * @param array $arguments to method attempting to be called
     **/
    protected function __call( $method, $arguments ) {
        if ( substr( $method, 0,15 ) != '_dynamicCompare' ) {
            throw new Exception( "Invalid method '$method' invoked on Collection" );
        }
        //split up the methodname to grab the pieces
        $methodParts = explode( 'As', $method );
        //the comparison type is the last part of the method name
        $compareType = $methodParts[1];
        if ( !method_exists( $this, '_compare' . $compareType) ) {
            throw new Exception( "Can not sort as $compareType dynamically" );
        }
        //determine if this is meant to be a descending sort
        $descending = stristr( $methodParts[0], 'Descending' );
        //clean out everything but the sort member from the first part of the method
        $sortMember = str_replace( '_dynamicCompare', '', str_replace( 'Descending', '', $methodParts[0] ) );

        return call_user_func( array( $this, "_compare$compareType"), $arguments[0], $arguments[1], $sortMember, $descending );
    }

    public function __isset( $member ) {
        //If we can get it, it's set.
        try { $devnull = $this->$member; }
        catch ( Exception $e ) {
            return false; //if we can't get it, it ain't set.
        }
        return true;
    }
    public function __get( $member ) {
        switch ( $member ) {
            case 'page':
                return ( is_null( $this->_page ) ) ? 1 : $this->_page;
            case 'pages':
                return ( is_null( $this->_pageSize ) || 0 === $this->count() ) ? 1 : ceil( $this->count() / $this->_pageSize );
            case 'pageSize':
                return ( is_null( $this->_pageSize ) ) ? $this->count() : $this->_pageSize;
            case 'start':
                $calculatedStart = ( ( $this->page - 1 ) * $this->pageSize ) + 1;
                return ( $calculatedStart > $this->count() ) ? null : $calculatedStart;
            case 'end':
                $calculatedStart = ( ( $this->page - 1 ) * $this->pageSize ) + 1;
                return ( $calculatedStart > $this->count() ) ? null : min( $this->page * $this->pageSize, $this->count() );
            case 'sortMethod':
                return $this->_sortMethod;
            case 'countOverride':
                return $this->_countOverride;
            case 'trueCount':
                return $this->count( true );
            default:
                trigger_error('Invalid Member: ' . $member, E_USER_NOTICE );
        }
    }
    public function __set( $member, $value ) {
        switch ( $member ) {
            case 'page':
                $this->_page = ( $value > 0 ) ? $value : 1; //page must always be positive and non-zero
                //check that the requested page is in range for this search, if not reset it to the last page of results
                if ( is_null( $this->start ) ) {
                    $this->_page = $this->pages;
                }
                break;
            case 'pageSize':
                $this->_pageSize = $value;
                break;
            case 'sortMethod':
                //null sortMethod is allowed
                if ( is_null( $value) || isset( $this->_sortMethods[ $value ] ) ) {
                    $this->_sortMethod = $value;
                    break;
                }
                throw new Exception( 'Invalid Sort Method: ' . $value );
            case 'countOverride':
                $this->_countOverride = $value;
                break;
            default:
                trigger_error('Invalid Member: ' . $member, E_USER_NOTICE );
        }
    }

    /**
     * @TODO: Possibly simplify dynamic sorting by removing the type restrictions ..
     * since the logic is the same anyway, it may not matter at all.
     **/

    /**
     * Useful function for creating extending classes sort methods
     * Compares two order attributes numerically
     * @param integer $val1 first value
     * @param integer $val2 second value
     * @param string $orderAttribute attribute from the order attributes collection to check against
     * @param boolean $descending true if comparison is for a descending sort, false if ascending
     * @return integer 0 if values are equal, for descending negative on first value being greater, positive on second being great. vice versa for ascending
     **/
    protected function _compareInt( $id1, $id2, $orderAttribute, $descending = true ) {
        $val1 = $this->_orderAttributes[$id1][ $orderAttribute ];
        $val2 = $this->_orderAttributes[$id2][ $orderAttribute ];

        if ( $val1 == $val2 ) {
            return 0;
        }
        if ( $descending ) {
            return ( $val1 > $val2 ) ? -1 : 1;
        } else {
            return ( $val1 > $val2 ) ? 1 : -1;
        }
    }

    /**
     * Useful function for creating extending classes sort methods
     * Compares two order attributes numerically
     * @param integer $val1 first value
     * @param integer $val2 second value
     * @param string $orderAttribute attribute from the order attributes collection to check against
     * @param boolean $descending true if comparison is for a descending sort, false if ascending
     * @return integer 0 if values are equal, for descending negative on first value being greater, positive on second being great. vice versa for ascending
     **/
    protected function _compareAlpha( $id1, $id2, $orderAttribute, $descending = true ) {
        $val1 = (string) $this->_orderAttributes[$id1][ $orderAttribute ];
        $val2 = (string) $this->_orderAttributes[$id2][ $orderAttribute ];

        if ( $val1 == $val2 ) {
            return 0;
        }
        if ( $descending ) {
            return ( $val1 > $val2 ) ? 1 : -1;
        } else {
            return ( $val1 > $val2 ) ? -1 : 1;
        }
    }

    /**
     * Helper method for unit tests to verify the ordering algorithim
     *
     * @param integer position of hit to get ordering information for
     * @return array Array of all the known ordering information
     **/
    public function getOrderingInformation( $pos ) {
        return $this->_orderAttributes[ $this->_order[ $pos - 1 ] ];
    }

    /**
     * Returns page buffered page numbers
     * @param int buffer # of pages
     * @param int page_count
     * @return array pages
    */
    public function getPageNumbers( $buffer )
    {
        $limitLow = $this->page - $buffer;
        $limitHigh = $this->page + $buffer;

        $limitLowExcess = ( $limitLow <= 0 ) ? abs( $limitLow ) + 1 : 0;    // the +1 is to account for the 0 spot

        $limitHighExcess = ( $limitHigh > ( $this->pages ) ) ? $limitHigh - ( $this->pages ) : 0;

        // low-side buffer run-off
        if ( $limitLowExcess && !$limitHighExcess ) {
            $limitLow = 0;
            $limitHigh = ( ( $limitHigh + $limitLowExcess ) < ( $this->pages ) )
                       ? $limitHigh + $limitLowExcess
                       : $this->pages;
        }

        // high-side buffer run-off
        if ( !$limitLowExcess && $limitHighExcess ) {
            $high_buffer = ( $this->page );
            $limitLow = ( ( $limitLow - $limitHighExcess ) > 0)
                      ? $limitLow - $limitHighExcess
                      : 0;
        }

        $pages = array();
        for ( $i = $limitLow; $i < $limitHigh;$i++ ) {
            if ( ($i > 0 ) && ( $i <= ( $this->pages ) ) ) {
            $pages[] = (int)$i;
            }
        }

        return $pages;
    }

    /**
     * Navigation information including elipses
     * @param integer buffere size
     * @return array Array of arrays with labels and page numbers in order
     **/
    public function getNavPages( $buffer, $previousString = "&lt;", $nextString = "&gt;", $previousGroupString = "&#133;", $nextGroupString = "&#133;" ) {
        $navPages = array();
        $pages = $this->getPageNumbers( $buffer );

        //Previous page
        if ( ($this->page - 1) > 0) {
            $navPages[] = array( 'page' => $this->page - 1, 'label' => $previousString );
        }

        //Previous Group and first page
        $firstPage = $pages[0];
        $numberOfPages = count( $pages );
        if ( $firstPage >= $buffer ) {
            //first page
            $navPages[] = array( 'page' => 1, 'label' => 1 );
            if ( $firstPage > $buffer ) {
                $targetPage =  ( ($firstPage - $buffer ) > 0 ) ? $firstPage - $buffer : 1;
                $navPages[] = array( 'page' => $targetPage, 'label' => $previousGroupString );
            }
        }

        //normal pages
        foreach ( $pages as $page ) {
            $nav = array( 'label' => $page );
            $nav[ 'page' ] = ($this->page == $page ) ? null : $page;
            $navPages[] = $nav;
        }

        $lastPage = $page;
        //next group and last page
        if ( $lastPage != ( $this->pages ) ) {
            if ( $lastPage < ( $this->pages - 1 ) )  {
                $targetPage = ( ( $lastPage + ( $buffer + 1 ) ) < $this->pages )
                            ? $lastPage + ( $buffer + 1 )
                            : $this->pages;
                $navPages[] = array( 'page' => $targetPage, 'label' => $nextGroupString );
            }
            //last page
            $navPages[] = array( 'page' => $this->pages, 'label' => $this->pages );
        }

        //Next Page
        if ( $this->page + 1 <= $this->pages ) {
            $navPages[] = array( 'page' => $this->page + 1, 'label' => $nextString );
        }

        return $navPages;
    }

    /**
     * Grabs the first item in the collection or returns false if the collection is empty
     * @return mixed|boolean When collection is empty, false, otherwise the first item in the collection
     **/
    public function getTop()
    {
        if( !isset( $this->_order[0] ) ){
            return false;
        }
        if( isset( $this->_items[ $this->_order[ 0 ] ] ) ){
            return $this->_items[ $this->_order[ 0 ] ];
        }
        return false;
    }

    /**
     * Converts this collection into an array
     **/
    public function toArray()
    {
        $array = array();
        foreach ( $this as $pos => $item ) {
            $array[ $this->_order[ $pos - 1 ] ] = $item;
        }
        return $array;
    }

/**
 *   --- ITERATOR INTERFACE METHODS
 *   when foreach'n through it will hit these something like this (for a two element collection):
 *
 *   rewind
 *   valid (true)
 *   current (first value)
 *   key (first index)
 *
 *   next
 *   valid (true)
 *   current (second value)
 *   key (second index)
 *
 *   next
 *   valid (false)
 *
 *   foreach ended
 **/

    /**
     * Return value of current search result
     *  @return Object Extending classes's result object
     **/
    public function current() {
        if ( empty( $this->_order ) ) {
            $this->sort();
        }

        return $this->offsetGet( $this->_order[ $this->_current ] );
    }

    /**
     * Move current position up one
     **/
    public function next() {
        ++$this->_current;
    }

    /**
     * Current position starting from 1
     **/
    public function key() {
        if ( $this->_currentParams['sortMethod'] !== $this->_sortMethod ) {
            $this->sort();
            $this->rewind();
        }

        return ( $this->_current + 1 );
    }

    /**
     * Tests whether current position is valid
     * @return boolean True when there is a result for the current position, false when there is not
     **/
    public function valid() {
        //if we haven't determined our order, or the sorting has changed, re-sort
        if ( empty( $this->_order ) || ( isset( $this->_currentParams['sortMethod'] ) && $this->_currentParams['sortMethod'] !== $this->_sortMethod ) ) {
            $this->sort();
        }

        //if a page size has been requested and we're beyond it, we're not valid
        if ( ! is_null ($this->_pageSize) AND $this->key() > ( $this->_pageSize * $this->_page ) ) {
            return false;
        }
        //If this position is not a valid order position, we're not valid
        return ( isset( $this->_order[ $this->_current  ] ) );
    }

    /**
     * Resets posion back to the beginning
     **/
    public function rewind() {
        $this->_current = ($this->_page * $this->_pageSize) - $this->_pageSize;
    }

/**
 *  --- COUNTABLE INTERFACE METHOD
 **/

    /**
     * Returns the number of results that we have
     * @return integer number of results
     **/
    public function count( $ignoreOverride = false ) {
        return ( $ignoreOverride || is_null( $this->_countOverride ) )
             ? count( $this->_orderAttributes )
             : $this->_countOverride;
    }

/**
 * --- ARRAYACCESS INTERFACE METHODS
 **/

    /**
     * @param string $index
     * @return boolean whether the index exists
     **/
    public function offsetExists( $index ) {
        return ( isset( $this->_orderAttributes[ $index ] ) );
    }

    /**
     * @param string $index
     * @return mixed Returns the value at index
     **/
    public function offsetGet( $index ) {
        try { return $this->_getResult( $index ); }
        catch ( Exception $e ) {
            Zend_Registry::get('log')->err( 'Exception accessing Collection item: ' . $e->getMessage() );
            return null;
        }
    }

    /**
     * Adds the value to the collection based on the value
     * @param string $index
     * @param mixed $value
     **/
    public function offsetSet( $index, $value ) {
        $this->add( $index, $value );
    }

    /**
     * Removes the item specified by $index from the collection
     * @param string $index
     **/
    public function offsetUnset( $index ) {
        unset( $this->_orderAttributes[ $index ] );
        if (isset( $this->_items[ $index ] ) ) {
            unset( $this->_items[ $index ] );
        }
        //shift the order up to cover the empty spot
        $shift = false;
        $collectionSize = count( $this );
        foreach( $this->_order as $pos => $itemIndex ) {
            if ( $shift ) {
                $this->_order[ $pos - 1] = $this->_order[ $pos ];
                if ( $pos === $collectionSize ) {
                    unset( $this->_order[ $pos ] );
                    break; //we're the new last position, so don't go any further
                }
            } else if ( $itemIndex === $index ) {
                $shift = true;
                if ( $pos === $collectionSize ) {
                    unset( $this->_order[ $pos ] );
                    break; //we're the new last position, so don't go any further
                }
            }
        }
    }
}
