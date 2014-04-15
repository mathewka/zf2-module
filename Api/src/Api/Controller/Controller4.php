<?php

namespace Api\Controller;

use Api\Mvc\Controller\RestfulController,
    Zend\View\Model\JsonModel,
    Zend\Json\Json,
    Zend\Http\Request,
    AP_XmlStrategy\View\Model\XmlModel,
    Zend\Debug\Debug,
    Api\Model\Orders;
	
class Controller4 extends RestfulController {
  
    protected $OrdersTable;
    
	/**
   * Return the new order details for a id.
   * @param mixed $id
   * @see getOrdersTable()
   * @return mixed
	**/
    
    public function get($id)
    {
        try {
            $messages = $this->getOrdersTable()->find($id);
            if(empty($messages)) {
                $this->getResponse()->setStatusCode(204);
            }
            return $this->returnData($messages);
        }
        
        catch (\Exception $e) {
            $this->getResponse()->setStatusCode(404);
            return $this->returnData(array('message' => $e->getMessage()));
        }
    }
	
  /**
   * Return new order list for particular ID.
   * @param mixed[] $params which will have query parameters
   * @see getMasterTable()
   * @return mixed
   **/
    
    public function getList() {
        try {
            $params = array();
            $params = $this->params()->fromQuery();
            if(empty($params) || !isset($params['id']) || count($params) > 1 || empty($params['id'])){
                $this->getResponse()->setStatusCode(400);
                if(count($params) > 1) {
                    return $this->returnData(array("message" => "API expects only one parameter"));
                }
                
                if(!isset($params['id']) || empty($params['id'])) {
                    return $this->returnData(array("message" => "ID required."));
                }
            }
            $orders = $this->getOrdersTable()->getNewFullFillOrders($params);

            if(empty($orders)) {
                $this->getResponse()->setStatusCode(204);
                return $this->returnData(array("message" => "Could not find any orders"));
            }
            return $this->returnData($orders);
        }
        
        catch (\Exception $e) {
            $this->getResponse()->setStatusCode(404);
            return $this->returnData(array('message' => $e->getMessage()));
        }
    }
	
  /**
   * Create order.
   * This function does not have any functionality.
   *
   * @param  mixed $data
   * @return mixed Returns status code with 405.
   */
    
    public function create ($data) {
        try {
            $this->getResponse()->setStatusCode(405);
            return $this->returnData(array("message" => "Method not allowed."));
        }
        
        catch (\Exception $e) {
            $this->getResponse()->setStatusCode(404);
            return $this->returnData(array('message' => $e->getMessage()));
        }
		
    }
	
  /**
   * Update an order
   * @param  mixed $id   
   * @param  mixed $data
   * @see getOrdersTable()
   * @return mixed
   */
    
	public function update($id, $data) {
	    try {
          $error_found = 0;
          $request = new Request();
          $request = $this->getRequest();
          $validator = new \Zend\Validator\Digits();
          $bad_request = 0;
          
          if(empty($id)) {
              $error_found = 1;
              $this->getResponse()->setStatusCode(404);
              $message["message"] = "Bad Request";
          } else if(!$validator->isValid($id) ) {
              $error_found = 1;
              $this->getResponse()->setStatusCode(404);
              $message["order"] = "URL parameter Order id is not valid";
          }
          
          if(empty($data)){
              $error_found = 1;
              $bad_request = 1;
              $message["data"] = "input parameter(json/xml) is mandatory.";
          }
          
          $contentType = $request->getHeaders('Content-Type')->getFieldValue();
          if ($this->requestHasContentType($request, self::CONTENT_TYPE_JSON)) {
              $updateData = $data['messages'][0];
          } else if ($contentType == 'application/xml'){
              $updateData = $data['messages']['message'];
          }
          
          if(!isset($updateData['value']) || empty($updateData['value'])){
              $error_found = 1;
              $bad_request = 1;
              $message["value"] = "value is mandatory"; 
          }
          
          if(!isset($updateData['value1'] ) || empty($updateData['value1'])){
              $error_found = 1;
              $bad_request = 1;
              $message["value1"] = "value1 is mandatory"; 
          } elseif (!$validator->isValid($updateData['value1']) ){
              $error_found = 1;
              $message["order"] = "Input Parameter Order id is not valid";				
          } else {
              if($id != $updateData['value1']){
                  $error_found = 1;
                  $this->getResponse()->setStatusCode(404);
                  $message["order_number"] = "URL parameter and input parameter Order Number mismatch.";
              }
          }
			
			
          if($bad_request) {
              $this->getResponse()->setStatusCode(400);
          } else {
              $id  = (int) $id;
              $orders = $this->getOrdersTable()->findOrders($id);
              if (empty($orders)) {
                  $error_found = 1;
                  $this->getResponse()->setStatusCode(404);
                  $message["order_status"] = "Invalid Order Details.";
              } elseif(isset($updateData['value3'])) {
                  if($orders[0]['value3'] != $updateData['value3']){
                      $error_found = 1;
                      $this->getResponse()->setStatusCode(404);
                      $message["value3"] = "Invalid Order Details.";
                  }
              }
          }
          
          if($error_found){
              return $this->returnData($message);
          }
          $masterlist = $this->getOrdersTable()->confirmOrder($updateData,$id);
		  return $this->returnData(array("message_number" => $id, "message_status" => $updateData['message_status']));
      }
      
      catch (\Exception $e) {
          $this->getResponse()->setStatusCode(404);
    	    return $this->returnData(array('message' => $e->getMessage()));
      }
	}
  
  /*
   * Getting items table details.
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
      return new XmlModel(array("data" => array('message' => $data), "rootNode" => "messages"));
	}	
}
	
	