<?php
/**
 * @namespace
 **/
namespace VoidTest\Account\Credential;

use \Void\Account\Credential\Password;

class PasswordTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Void\Account\Credential\Password PAssword Credential used in tests
     **/
    protected $pwCred;
    protected $email = 'chach@chach-house.net';
    protected $password = 'smartone';
    //MD5 of password
    protected $passwordHash = 'f0c79697f4867ff34b64ea165bae08d1';

    public function setUp()
    {
        $mockDb = $this->getMock( '\Zend\Db\Adapter\PdoMysql', array('blah'), array(), '', false );
        $this->pwCred = new Password( $mockDb );
        $this->pwCred->email = $this->email;
        $this->pwCred->password = $this->password;
    }

    public function testGetAuthAdapterReturnsAnAdapterWithIdentityAndCredentialSet()
    {
        $authAdapter = $this->pwCred->getAdapter();
        $this->assertType( '\Zend\Authentication\Adapter', $authAdapter );
        //how to test that the identity and credential where properly set?
    }

    public function testGetHashedPasswordHashesPassword()
    {
        $this->assertEquals( $this->passwordHash, $this->pwCred->getHashedPassword() );
    }
}
