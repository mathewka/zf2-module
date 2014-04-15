<?php
namespace Api;

return array(
    'controllers' => array(
        'invokables' => array(
            'Api\Controller\Controller1'  => 'Api\Controller\Controller1',
            'Api\Controller\Controller2'  => 'Api\Controller\Controller2Controller',
            'Api\Controller\Controller3'  => 'Api\Controller\Controller3Controller',
            'Api\Controller\Controller4'  => 'Api\Controller\Controller4Controller',
            'Api\Controller\Controller5'  => 'Api\Controller\Controller5Controller',
         ),
    ),
    'router' => array(
        'routes' => array(
            'api' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/api',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Api\Controller',
                        'controller'    => 'Controller1',
                        //'action' => 'index'
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route' => '/[:controller][/:id]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id' => '[a-zA-Z-0-9][a-zA-Z0-9_-]*'
                            ),
                            'defaults' => array(
                            ),
                        ),
                    ),
                ),
            ),
        ),

    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'api' => __DIR__ . '/../view',
        ),
        'strategies' => array(
        	'ViewXmlStrategy',
        	'ViewJsonStrategy'
        )
    ),
	'service_manager' => array(
		'factories' => array(
			'AuthAdapter' => 'Api\Factory\AuthAdapterFactory',
			'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory',
			'DbResolver' => 'Api\Factory\DbResolverFactory',
		),
	),
);
