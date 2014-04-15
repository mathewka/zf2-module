<?php
 
namespace Api\Factory;
 
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Api\Controller\DbResolver;
 use Zend\Authentication\Adapter\DbTable as AuthAdapter;
 
class AuthAdapterFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $sl)
    {
        $db = $sl->get('Zend\Db\Adapter\Adapter');
        $adapter = new AuthAdapter($db,
          'users',
          'user_login',
          'user_password',
          'MD5(?)'
         );
	  return $adapter;
    }
}