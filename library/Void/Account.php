<?php
namespace Void;

use Zend\Authentication\AuthenticationService;

Abstract class Account
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
     * an account from the Authentication Service storage (ie, session)
     * If none is available there, it will return an unauthenticated account
     *
     * @param mixed $userId User Identifier
     * @return Account Instantiated Account object
     **/
    public static function get( $userId = null ) {
        $className = static::who();
        if ( isset( $userId ) ) {
            $account = new $className( $userId );
            $account->load( $userId );
        }

        //get auth'd user, if any, from the session
        $auth = static::getAuthenticationService();
        if ( $auth->hasIdentity() ) {
            if ( !isset( $userId ) ) {
                $account = static::get( $auth->getIdentity() );
                $account->load( $auth->getIdentity() );
            }
            //If the auth service had an identity stored, assume it is authenticated
            $account->authenticated = ( $auth->getIdentity() == $account->userName ) ? true : false;
            return $account;
        } else {
            $account = new $className();
        }

        return $account;
    }

    /**
     * Deceptively, this is not a singleton, but just a helper to the static get
     * method for extending classes to define what they are
     * @return string full namespace of class to instantiate
     **/
    abstract protected static function who();

    /**
     * Authenticates this user with given credential
     * @param \Void\Account\Credential $credential
     * @throws \Exception When unable to authenticate with given credential
     **/
    public function authenticate( Account\Credential $credential )
    {
        $auth = Account::getAuthenticationService();
        $adapter = $credential->getAdapter();
        $adapter->setIdentity( $this->userName );
        $result = $auth->authenticate( $adapter );
        $this->authenticated = $result->isValid();
        if ( !$result->isValid() ) {
            throw new \Exception( array_shift( $result->getMessages() ), $result->getCode() );
        }
    }

    public function clearAuthentication()
    {
        if ( $this->isAuthenticated() ) {
            Account::getAuthenticationService()->clearIdentity();
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
    abstract protected function load( $userId );

}
