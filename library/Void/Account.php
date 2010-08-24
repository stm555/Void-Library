<?php
namespace Void;

use Zend\Authentication\AuthenticationService;
use Void\Mapper;

Abstract class Account
{
    public $email;

    /**
     * @var Mapper Object Mapper for account
     **/
    protected static $mapper;

    public function __construct( $email = null )
    {
        if ( isset( $email ) ) {
            $this->email = $email;
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
        $className = get_called_class();
        if ( isset( $userId ) ) {
            $account = new $className( $userId );
            try {
                $mapper = static::getMapper();
                $mapper->find( $userId, $account );
            } catch ( \Exception $e ) {
                //unable to map account, but whatever.
            }
            return $account;
        }

        //get auth'd user, if any, from the session
        $auth = static::getAuthenticationService();
        if ( $auth->hasIdentity() ) {
            if ( !isset( $userId ) ) {
                return static::get( $auth->getIdentity() );
            }
        } else {
            return new $className();
        }
    }

    /**
     * Authenticates this user with given credential
     * @param \Void\Account\Credential $credential
     * @throws \Exception When unable to authenticate with given credential
     **/
    public function authenticate( Account\Credential $credential )
    {
        $auth = Account::getAuthenticationService();
        $adapter = $credential->getAdapter();
        $adapter->setIdentity( $this->email );
        $result = $auth->authenticate( $adapter );
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
        $auth = static::getAuthenticationService();
        return ( $auth->hasIdentity() && $auth->getIdentity() == $this->email ) ? true : false;
    }

    /**
     * Retrieves mapper to load account values with
     * @returns Mapper Object Mapper for account
     **/
    abstract static protected function getMapper();
}
