<?php
namespace Void;

class User
{
    public $userName;

    public function __construct( $userName = null )
    {
        if ( isset( $userName ) ) {
            $this->userName = $userName;
        }
    }

}
