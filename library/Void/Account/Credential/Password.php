<?php
namespace Void\Account\Credential;
use \Zend\Db\Adapter\AbstractAdapter as DbAdapter;
use \Zend\Authentication\Adapter\DbTable as AuthAdapter;

class Password extends \Void\Account\Credential
{
    protected $db;

    public $password;

    /**
     * @param DbAdapter $db Database adapter to use for auth look up
     **/
    public function __construct( DbAdapter $db )
    {
        $this->db = $db;
    }

    public function getAdapter()
    {
        $adapter = new AuthAdapter( $this->db,'credentials_password',
                                              'accounts.email',
                                              'credentials_password.password' );
        $adapter->getDbSelect()->join( 'accounts', 'accounts.id = credentials_password.account' );
        $adapter->setCredential( $this->getHashedPassword() );
        return $adapter;
    }

    public function setDb( DbAdapter $db )
    {
        $this->db = $db;
    }

    public function getHashedPassword() {
        return md5( $this->password );
    }
}
