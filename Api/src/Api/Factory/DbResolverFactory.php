<?php
 
namespace Api\Factory;
 
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Api\Controller\DbResolver;
 
class DbResolverFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $sl)
    {
        $authAdapter = $sl->get('AuthAdapter');
		$resolver = new DbResolver($authAdapter);
        return $resolver;
    }
}