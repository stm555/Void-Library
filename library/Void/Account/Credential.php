<?php
namespace Void\Account;

use \Zend\Authentication\Adapter;

/**
 * Basic credential for authentication
 **/
Abstract class Credential
{
    protected $adapter;

    /**
     * Factory Method for getting a credential
     **/
    public static function get( $credentialType = 'password' )
    {
        switch ( $credentialType )
        {
            case 'password':
                return new Credential\Password();
            default:
                throw new Exception('Invalid credential type');
        }
    }

    public function setAdapter( Adapter $adapter )
    {
        $this->adapter = $adapter;
    }

    public function getAdapter()
    {
        if ( !isset( $this->adapter ) ) {
            throw new Exception( 'No Adapter set for this credential' );
        }
        return $this->adapter;
    }
}
