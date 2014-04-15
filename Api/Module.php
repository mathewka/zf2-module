<?php
/*
 * File for API module configuration
 */
namespace Api;

use Api\Model\Table,
    Api\Model\MasterTable,
    Api\Model\ItemsTable,
    Api\Model\OrdersTable,
    Api\Model\OrdersItemsTable,
    Zend\Authentication\Adapter\Http,
    Zend\Http\Request,
    Zend\Http\Response,
    Api\Controller\DbResolver,
    Zend\Authentication\AuthenticationService,
    Zend\Authentication\Adapter\DbTable as DbTableAuthAdapter,
    Api\Controller\Controller1,
    Zend\Mvc\MvcEvent,
    Zend\View\Model\JsonModel,
    Zend\Mvc\ModuleRouteListener,
    AP_XmlStrategy\View\Model\XmlModel;

class Module {
    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
	
    /*
   * The bootstrap function registers the listeners 
   * onDispatchError and preDispatchError
   * @param Object $e Event listner 
   * @see onDispatchError()
   * @see preDispatch()
   */
    
    public function onBootstrap($e) {
        $eventManager = $e->getApplication()->getEventManager();
		//Attach event error dispatch
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onDispatchError'), 0);

        $conf = array(
            'accept_schemes' => 'basic',
            'realm' => ' ',
            'nonce_timeout' => 2000
        );
		//API Authentication adapter
        $adapter = new Http($conf);
        $setRequest = $e->getRequest();
        $setResponse = $e->getResponse();
        $sm = $e->getApplication()->getServiceManager();
        $resolver = $sm->get('DbResolver');
        $adapter->setBasicResolver($resolver);
        $adapter->setRequest($setRequest);
        $adapter->setResponse($setResponse);
        $result = $adapter->authenticate();
        if (!$result->isValid()) {
            $eventManager = $e->getApplication()->getEventManager();
            // attach dispatch listener 
            $eventManager->attach('dispatch', function($e) {
                  // get response from event
                $response = $e->getResponse();
                $request = $e->getRequest();
                $contentType = $request->getHeaders('Content-Type');
                if($contentType){
                    if($contentType->value == 'application/json') {
                        $response->getHeaders()->addHeaders(array('Content-Type' => 'application/json'));
                        $response->setContent('{"error": {"message": "User name or password is invalid"}}');
                    } else {
                        $response->getHeaders()->addHeaders(array('Content-Type' => 'application/xml'));
                        $response->setContent('<?xml version="1.0" encoding="utf-8"?><error><message>User name or password is invalid</message></error>');
                    }
                } else {
                    $response->getHeaders()->addHeaders(array('Content-Type' => 'application/xml'));
                    $response->setContent('<?xml version="1.0" encoding="utf-8"?><error><message>User name or password is invalid</message></error>');
                }
                $response->setStatusCode(403);
                return $response;
            });
        }
		//Attach event dispatch for data validation
        $eventManager->attach(MvcEvent::EVENT_DISPATCH, array($this, 'preDispatch'),1);	 
    }

    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }
    
	/*
	 * Initialize service configuration
	 */
    public function getServiceConfig() {
        return array(
            'factories' => array(
                'Api\Model\BookTable' =>  function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new BookTable($dbAdapter);
                    return $table;
                },
                'Api\Model\MasterTable' =>  function($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $table = new MasterTable($dbAdapter);
                            return $table;
                        },
                'Api\Model\ItemsTable' =>  function($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $table = new ItemsTable($dbAdapter);
                            return $table;
                        },
                'Api\Model\OrdersTable' =>  function($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $table = new OrdersTable($dbAdapter);
                            return $table;
                        },
                'Api\Model\OrdersItemsTable' =>  function($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $table = new OrdersItemsTable($dbAdapter);
                            return $table;
                        },
                'AuthService' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $dbTableAuthAdapter  = new DbTableAuthAdapter($dbAdapter, 'users','user_name','pass_word', 'MD5(?)');
                    $authService = new AuthenticationService();
                    $authService->setAdapter($dbTableAuthAdapter);
                    return $authService;
                }
            ),
        );
    }
     
   /*
    * Event listner for error handling
    * @param Object $e Event listner
    * @see getJsonModelError()
    */
    
    public function onDispatchError($e) {
      return $this->getJsonModelError($e);
      exit;
    }

   /*
    * Event listner for error handling which converts to JSON
    * @param Object $e Event listner
    */
    
    public function getJsonModelError($e) {
        $error = $e->getError();
        if (!$error) {
            return;
        }

        $response = $e->getResponse();
        $request = $e->getRequest();
        $contentType = $request->getHeaders()->get('Content-Type')->value;

        $exception = $e->getParam('exception');
        $exceptionJson = array();
        if ($exception) {
            $exceptionJson = array(
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'stacktrace' => $exception->getTraceAsString()
            );
        }

        $errorJson = array(
            'message'   => 'An error occurred during execution; please try again later.'
        );
        if ($error == 'error-router-no-match') {
            $errorJson['message'] = 'Resource not found.';
        }

        $model = new JsonModel(array('errors' => array($errorJson)));

        $e->setResult($model);

        return $model;
    }
    
   /*
    * Event listner for handling validation
    * @param Object $e Event listner
    */
    public function preDispatch($event) {
        $params = array();
        $params = $event->getRequest()->getQuery();

        $pattern = '/^[a-z0-9][a-z0-9-_ ]*[a-z0-9]$/i';
        $response = $event->getResponse();
        $request = $event->getRequest();
        $contentType = $request->getHeaders('Content-Type');
        foreach($params as $key => $value) {
            if(!empty($value)) {
                if(!preg_match($pattern, $value)) {
                    if($contentType){
                        if ($contentType->value == 'application/json'){
                            $response->getHeaders()->addHeaders(array('Content-Type' => 'application/json'));
                            if(strlen($value) < 2) {
                                $response->setContent('{"error": {"message": "Length of the value parameter \''.$key.'\' must be at least 2 characters."}}');
                            } else {
                            $response->setContent('{
                                "error": {"message": "Special characters are not allowed except hyphen, underscore and space. Hyphen and underscore cannot be used as prefix or suggix."}
                            }');                                
                            }
                            $response->setStatusCode(400);
                            return $response;
                        } else if(($contentType->value == 'application/xml')) {
                            $response->getHeaders()->addHeaders(array('Content-Type' => 'application/xml'));
                            if(strlen($value) < 2) {
                                $response->setContent('<?xml version="1.0" encoding="utf-8"?>
                                    <error><message>Length of the value parameter \''.$key.'\' must be at least 2 characters.</message></error>'
                                );                                
                            } else {
                                $response->setContent('<?xml version="1.0" encoding="utf-8"?>
                                    <error><message>Special characters allowed are except hyphen, underscore and space. Allowed characters cannot be used as prefix or suggix.</message></error>'
                                );
                            }
                            $response->setStatusCode(400);
                            return $response;
                        }
                    }
                }
            }
        }
    }
}
