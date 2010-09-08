<?php
namespace Void;

use Zend\Authentication\AuthenticationService;
use Void\Mapper;

class Account
{
    public $id;

    public $email;

    /**
     * @var Mapper Object Mapper for account
     **/
    static protected $mapper;

    static protected $authService;

    /**
     * Grabs a given account.
     * If no specific account is requested, it will attempt to load
     * an account from the Authentication Service storage (ie, session)
     * If none is available there, it will return an unauthenticated account
     *
     * @param mixed $userId User Identifier
     * @return Account Instantiated Account object
     **/
    public static function get( $accountId = null ) {
        $className = get_called_class();
        if ( isset( $accountId ) ) {
            $account = new $className();
            try {
                $mapper = static::getMapper();
                $mapper->find( $accountId, $account );
            } catch ( \Exception $e ) {
                //unable to map account, but whatever.
            }
            return $account;
        }

        //get auth'd user, if any, from the session
        $auth = static::getAuthenticationService();
        if ( $auth->hasIdentity() ) {
            return static::get( $auth->getIdentity() );
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
        $result = $auth->authenticate( $adapter );
        if ( !$result->isValid() ) {
            throw new \Exception( array_shift( $result->getMessages() ), $result->getCode() );
        }
        $storage = $auth->getStorage();
        $storage->write( $this->id );
    }

    public function clearAuthentication()
    {
        if ( $this->isAuthenticated() ) {
            Account::getAuthenticationService()->clearIdentity();
        }
    }

    protected static function getAuthenticationService()
    {
        if ( !isset( static::$authService ) ) {
            static::$authService = new AuthenticationService();
        }
        return static::$authService;
    }

    public static function setAuthenticationService( AuthenticationService $authService )
    {
        static::$authService = $authService;
    }

    public function isAuthenticated()
    {
        $auth = static::getAuthenticationService();
        return ( $auth->hasIdentity() && $auth->getIdentity() == $this->id ) ? true : false;
    }

    /**
     * Retrieves mapper to load account values with
     * @returns Void\Mapper Object Mapper for account
     **/
    static protected function getMapper()
    {
        if ( !isset( static::$mapper ) ) {
            static::$mapper = new Mapper();
        }
        return static::$mapper;
    }

    /**
     * Sets the Mapper object for the Account object
     * @param Void\Mapper Mapper object for Account
     **/
    static public function setMapper( $mapper )
    {
        static::$mapper = $mapper;
    }
}
