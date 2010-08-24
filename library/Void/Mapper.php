<?php
namespace Void;

interface Mapper
{
    /**
     * Stores object in storage
     * @param object $object Object to save
     * @throws Exception on failure to save
     **/
    //public function save( object $object );

    /**
     * Finds objected identified by $id and loads it into $object
     * @param mixed $id Identifier for object in storage
     * @param object $object Object to load with data
     * @throws Exception on failure to find object
     **/
    //public function find( $id, object $object );
}
