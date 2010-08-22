<?php
namespace Void\Account;

use \Zend\Authentication\Adapter;

/**
 * Basic credential for authentication
 **/
Abstract class Credential
{
    /**
     * Credentials at a minimum must return an adapter suitable
     * for authentication and primed with the credential's information
     * @return Adapter Primed Adapter suitable for authentication
     **/
    abstract public function getAdapter();

    /**
     * Factory Method for getting a credential
     **/
    public static function get( $credentialType = 'password', array $params = null )
    {
        switch ( $credentialType )
        {
            case 'password':
                return new Credential\Password( $params['db'] );
            default:
                throw new Exception('Invalid credential type');
        }
    }
}
