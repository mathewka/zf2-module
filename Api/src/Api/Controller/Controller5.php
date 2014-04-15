<?php

namespace Api\Controller;

use Api\Mvc\Controller\RestfulController,
    Zend\View\Model\JsonModel,
    Zend\Json\Json,
    Zend\Http\Request,
    AP_XmlStrategy\View\Model\XmlModel,
    Api\Model\Orders;
	
class Controller5 extends RestfulController {
    protected $OrdersTable;

    /**
     * Return the orders for the ID.
     * Currently this function does not have any functionality.
     * @param mixed $id
     * @return mixed Returns status code with 405
    **/
    
    public function get($id) {
        try {
            $this->getResponse()->setStatusCode(405);
            return $this->returnData(array("message"=>"Method not allowed."));
        }
        catch (\Exception $e) {
            $this->getResponse()->setStatusCode(404);
            return $this->returnData(array('message' => $e->getMessage()));
        }
    }

  /**
   * Return order based on ID and message status.
   * @param mixed[] $params which will have query parameters
   * @see getOrdersTable()
   * @return mixed
   **/
    
    public function getList() {
        try {
            $params = array();
            $params = $this->params()->fromQuery();
            if(empty($params) || !isset($params['id']) || !isset($params['message_status']) 
                    || count($params) > 2 || empty($params['message_status']) || empty($params['id'])){ 
                $this->getResponse()->setStatusCode(400);
                if(empty($params)) {
                    return $this->returnData(array("message" => "Could not find parameters in the request URL.")); 
                }

                if(!isset($params['id']) || empty($params['id'])){
                    return $this->returnData(array("message" => "ID is required.")); 
                }

                if(!isset($params['message_status']) || empty($params['message_status'])){
                    return $this->returnData(array("message" => "Message status code is required.")); 
                }

                if(count($params) > 2) {
                    return $this->returnData(array("message" => "Unidentified parameter is found.")); 
                }
            }

            $myorders = $this->getOrdersTable()->getCreatedByMeOrders($params);
            if(empty($myorders)) {
                $this->getResponse()->setStatusCode(204);
                return $this->returnData(array("message" => "Could not find products."));
            }
            return $this->returnData($myorders);
        }
        
        catch (\Exception $e) {
            $this->getResponse()->setStatusCode(500);
            return $this->returnData(array('message' => $e->getMessage()));
        }
    }

  /**
   * Create order for a specific ID.
   * This function does not have any functionality.
   *
   * @param  mixed $data
   * @return mixed Returns status code with 405.
   */
    
    public function create ($data) {
        try{
            $this->getResponse()->setStatusCode(405);
            return $this->returnData(array("message"=>"Method not allowed."));
        }
        
        catch (\Exception $e) {
            $this->getResponse()->setStatusCode(404);
            return $this->returnData(array('message' => $e->getMessage()));
        }
    }
	
  /**
   * Update order of a specify ID.
   * This function does not have any functionality.
   * @param  mixed $id   
   * @param  mixed $data
   * @return mixed Returns status code with 405.
   */
    
    public function update($id, $data) {
        try {
            $this->getResponse()->setStatusCode(405);
            return $this->returnData(array("message"=>"Method not allowed."));
        }
        catch (\Exception $e) {
            $this->getResponse()->setStatusCode(404);
            return $this->returnData(array('message' => $e->getMessage()));
        }
    }
    
    /*
     * Getting  order table details.
     * 
     * @return mixed
     */
    
    public function getOrdersTable() {
        if (!$this->OrdersTable) {
            $sm = $this->getServiceLocator();
            $this->OrdersTable = $sm->get('Api\Model\OrdersTable');
        }
        return $this->OrdersTable;
    }
  /*
   * Return data based on content type
   * 
   * @param mixed $data
   * @return mixed Contains XML or JSON structure depends on Content-Type
   */
    
    public function returnData($data) {
        $request = new Request();
        $request = $this->getRequest();
        if ($this->requestHasContentType($request, self::CONTENT_TYPE_JSON)) {
            return new JsonModel(array('message' => $data));
        }
        
        return new XmlModel(array("data" => array('message' => $data),"rootNode" => "messages"));
    }
}
	
	