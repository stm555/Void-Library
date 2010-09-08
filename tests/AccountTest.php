<?php

/**
 * @namespace
 **/
namespace VoidTest;

require_once __DIR__ . '/_files/Account/Mapper.php';

use \Void\Account,
    \VoidTest\Account\Mapper,
    \Zend\Authentication\AuthenticationService,
    \Void\Account\Credential\Password,
    \Zend\Authentication\Adapter\DbTable;


/**
 * @category   Void
 * @package    Void_Account
 * @subpackage UnitTests
 * @group      Void_Account
 * @copyright  Copyright (c) 22010 stm
 */
class AccountTest extends \PHPUnit_Framework_TestCase
{
    protected $authMock;
    protected $mapperMock;

    public function setUp()
    {
        $this->mapperMock = $this->getMock( 'VoidTest\Account\Mapper', array( 'find', 'save' ) );
        Account::setMapper( $this->mapperMock );
        $this->authMock = $this->getMock('Zend\Authentication\AuthenticationService', array( 'hasIdentity', 'authenticate', 'clearIdentity', 'getIdentity' ) );
        Account::setAuthenticationService( $this->authMock );
    }

    /**
     * Test that get Account with no parameters gives us back an empty account object
     **/
    public function testGetEmptyAccount()
    {
        $account = Account::get();

        $this->assertObjectHasAttribute( 'id', $account );
        $this->assertAttributeSame( null, 'id', $account );
        $this->assertObjectHasAttribute( 'email', $account );
        $this->assertAttributeSame( null, 'email', $account );
    }

    public function testGetSpecificAccount()
    {
        $email = 'stm@chach-house.net';
        $this->mapperMock
             ->expects( $this->once() )
             ->method( 'find' )
             ->with( $email );
        $account = Account::get( $email );
    }

    /**
     * Test that we get the currently logged in user when a user is logged in
     **/
    public function testGetCurrentAccountWhenIdentityExistsInStorage()
    {
        $accountId = 1;
        $this->authMock
             ->expects( $this->once() )
             ->method('hasIdentity')
             ->will( $this->returnValue( true ) );
        $this->authMock
             ->expects( $this->once() )
             ->method('getIdentity')
             ->will( $this->returnValue( $accountId ) );
        $this->mapperMock
             ->expects( $this->once() )
             ->method( 'find' )
             ->with( $accountId );
        $account = Account::get();
    }

    /**
     * Test
     **/
    public function testAuthenticateAccountWillUseAdapterFromCredential()
    {
        $credMock = $this->getMock( 'Void\Account\Credential\Password', array( 'getAdapter' ), array(), '', false );
        $authAdapterMock = $this->getMock( 'Zend\Authentication\Adapter\DbTable', array(), array(), '', false );
        $credMock->expects( $this->once() )
                 ->method( 'getAdapter' )
                 ->will( $this->returnValue( $authAdapterMock ) );
        $resultMock = $this->getMock( 'authResult', array('isValid') );
        $resultMock->expects( $this->once() )
                   ->method( 'isValid' )
                   ->will( $this->returnValue( true ) );
        $this->authMock
             ->expects( $this->once() )
             ->method( 'authenticate' )
             ->with( $authAdapterMock )
             ->will( $this->returnValue( $resultMock ) );
        $email = 'stm@chach-house.net';
        $account = Account::get( $email );
        $account->authenticate( $credMock );
    }

}
