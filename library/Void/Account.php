<?php
namespace Void;

use Zend\Authentication\AuthenticationService;

class Account
{
    public $userName;

    protected $authenticated = false;


    public function __construct( $userName = null )
    {
        if ( isset( $userName ) ) {
            $this->userName = $userName;
        }
    }

    /**
     * Grabs a given account.
     * If no specific account is requested, it will attempt to load
     * an account from the Authentication Service storate (ie, session)
     * If none is available there, it will return an unauthenticated account
     *
     * @param mixed $userId User Identifier
     * @return Account Instantiated Account object
     **/
    public static function get( $userId = null ) {
        if ( isset( $userId ) ) {
            return new Account( $userId );
        }

        //get auth'd user, if any, from the session
        $auth = Account::getAuthenticationService();
        if ( $auth->hasIdentity() ) {
            $account = Account::get( $auth->getIdentity() );
            //If the auth service had an identity stored, assume it is authenticated
            $account->authenticated = true;
            return $account;
        } else {
            return new Account();
        }
    }

    /**
     * Authenticates this user with given credential
     * @param \Void\Account\Credential $credential
     **/
    public function authenticate( Account\Credential $credential )
    {
        $auth = Account::getAuthenticationService();
        $adapter = $credential->getAdapter();
        $adapter->setIdentity( $this->userName );
        $result = $auth->authenticate( $adapter );
        $this->authenticated = $result->isValid();
        if ( !$result->isValid() ) {
            throw new Exception( $result->getMessage() );
        }
    }

    protected static function getAuthenticationService()
    {
        return new AuthenticationService();
    }

    public function isAuthenticated()
    {
        return ( $this->authenticated === true );
    }

    /**
     * Loads User details from storage
     * @param mixed $userId User Identifier
     * @throws Exception Unable to get user information
     **/
    protected function load( $userId )
    {
        //implement this
    }

}
