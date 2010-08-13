<?php
namespace Void\Account\Credential;

class Password extends \Void\Account\Credential
{
    public $password;

    public function getAdapter()
    {
        $adapter = parent::getAdapter();
        $adapter->setCredential( md5( $this->password ) );
        return $adapter;
    }
}
