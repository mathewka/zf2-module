<?php

namespace Api\Controller;

use Api\Mvc\Controller\RestfulController,
    Zend\View\Model\JsonModel,
    Api\Model\Orders,
    Api\Model\OrderItems,
    Zend\Json\Json,
    Zend\Http\Request,
    AP_XmlStrategy\View\Model\XmlModel;
	
  class Controller3 extends RestfulController {
      protected $ordersTable;
      protected $ordersItemsTable;

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
   * Return order list for particular ID.
   * @param mixed[] $params which will have query parameters
   * @see getOrdersTable()
   * @return mixed
   **/

    public function getList() {
        try {
           $params = array();
           $params = $this->params()->fromQuery();

            if(empty($params)){
                return $this->returnData(array("message" => "Not found Master")); 
            }

            $masterlist = $this->getOrdersTable()->getNewFullFillOrders($params['id']);
            return $this->returnData($masterlist); 
        }

        catch (\Exception $e) {
            $this->getResponse()->setStatusCode(404);
            return $this->returnData(array('message' => $e->getMessage()));
        }
    }

    /**
     * Create new order.
     *
     * @param  mixed[] $data The values sent from the user available here
     * @return mixed
     */
    
    public function create ($data) {
        try {
            $request = new Request();
            $request = $this->getRequest();
            $order = new Orders();
            $addOrerData = array();
            $error_found = 0;
            $bad_required = 0;
            $contentType = $request->getHeaders('Content-Type')->getFieldValue();
            if($this->requestHasContentType($request, self::CONTENT_TYPE_JSON)) {
                $addOrerData = $data['message'][0];
                if(!isset($addOrerData['products']) || empty($addOrerData['products'])){
                    $error_found = 1;
                    $bad_required = 1;
                    $message['products'] = "Invalid order.";
                } else {
                    $addOrderItemData = $addOrerData['products'];
                }
            } else if ($contentType == 'application/xml'){
                $addOrerData = $data['messages']['message'];
                if(!isset($addOrerData['products'])){
                    $error_found = 1;
                    $bad_required = 1;
                    $message['products'] = "Invalid order.";
                } elseif(!isset($addOrerData['products']['product'])  || empty($addOrerData['products']['product'])) {
                    $error_found = 1;
                    $bad_required = 1;
                    $message['products'] = "Invalid order.";
                } else {
                    $addOrderItemData = $addOrerData['products']['product'];
              }
          }

          if($error_found){
              if($bad_required) {
                  $this->getResponse()->setStatusCode(400);
              } else {
                  $this->getResponse()->setStatusCode(404);
              }
              return $this->returnData($message);
          }

          $order->exchangeArray($addOrerData);

          //Validation for integer
          $validator = new \Zend\Validator\Digits();
          $message = array();
          if(empty($order->delivery_date)){
              $message['delivery_date'] = "Delivery date is mandatory.";
              $error_found = 1;
              $bad_required = 1;
          } elseif(!$validator->isValid($order->delivery_date) ) {
              $message['delivery_date'] = "Date format is not valid.";
              $error_found = 1;
			  $bad_required = 1;
          } else {
              $delivery_date = (int) $order->delivery_date;
              $time = time();
              if($delivery_date < $time){
                  $message['delivery_date'] = "Delivery date should not be less than today's Date.";
                  $error_found = 1;
				  $bad_required = 1;
              }
          }

          if(!empty($order->delivery_ending_date) && !$validator->isValid($order->delivery_ending_date) ){
              $message['delivery_ending_date'] = "Date format is not valid.";
              $error_found = 1;
			  $bad_required = 1;
          }

          if(empty($order->message_type)){
              $message['message_type'] = "Message type value is mandatory.";
              $bad_required = 1;
              $error_found = 1;
          } elseif(strlen($order->message_type) > 2) {
              $message['message_type'] = "Message type is not valid.";
              $error_found = 1;
			  $bad_required = 1;
          }

          if(empty($order->message_status)) {
              $message['message_status'] = "Message status value is mandatory.";
              $bad_required = 1;
              $error_found = 1;
          } else {
              $message_status  = intval($order->message_status);
              if(!$validator->isValid($message_status)){
                  $error_found = 1;
				  $bad_required = 1;
                  $message["message_status"] = "Message Status value is not valid";				
              }
          }
		  // elseif(strlen($order->message_status) > 2) {
              // $message['message_status'] = "Message status is not valid.";
              // $error_found = 1;
          // }

          if(empty($order->fulfiller_md_number)) {
              $message['message_type'] = "Fullfiller md number is mandatory.";
              $bad_required = 1;
              $error_found = 1;
          } elseif(strlen($order->fulfiller_md_number) > 10) {
              $message['fulfiller_md_number'] = "Fullfiller md number is not valid.";
              $error_found = 1;
			  $bad_required = 1;
          }

          if(empty($order->sender_md_number)){
              $message['message_type'] = "Sender md number is mandatory.";
              $bad_required = 1;
              $error_found = 1;
          } elseif(strlen($order->sender_md_number) > 10) {
             $message['sender_md_number'] = "Sender md number is not valid.";
              $error_found = 1;
			  $bad_required = 1;
          }

          if($error_found) {
              if($bad_required) {
                  $this->getResponse()->setStatusCode(400);
              } else {
                  $this->getResponse()->setStatusCode(404);
              }
              return $this->returnData($message);
          }

          $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
          $adapter->getDriver()->getConnection()->beginTransaction();
          $this->getOrdersTable()->save($order);
          $message_number = $this->getOrdersTable()->lastInsertValue;

          if(!empty($message_number)) {
              foreach($addOrderItemData as $value){
                  if(is_array($value)) {
                      $orderItemData = $addOrderItemData;
                  } else {
                      $orderItemData[0] = $addOrderItemData;
                  }
                  break;
              }

              foreach($orderItemData as $itemData){
                  $orderItems = new OrderItems();
                  $orderItems->exchangeArray($itemData);
                  $error_found = 0;
                  if(empty($orderItems->delivery_date)) {
                      $message['products_delivery_date'] = "product item delivery date is mandatory.";
                      $bad_required = 1;
                      $error_found = 1;
                  } elseif(!$validator->isValid($orderItems->delivery_date)) {
                      $message['products_delivery_date'] = "product item delivery date format is not valid.";
                      $error_found = 1;
                  } else {
                      $item_delivery_date = (int) $orderItems->delivery_date;
                      $time = time();
                      if($item_delivery_date < $time) {
                          $message['delivery_date'] = "Item Delivery date should not be less than today's Date.";
                          $error_found = 1;
                      }
                  }

                  if(!$error_found) {
                      $orderItems->message_number = $message_number;
                      $this->getOrderItemsTable()->save($orderItems);
                  }
              }
          }
          if($error_found) {
              if($bad_required) {
                  $this->getResponse()->setStatusCode(400);
              } else {
                  $this->getResponse()->setStatusCode(404);
              }
              return $this->returnData($message);
          }
          $adapter->getDriver()->getConnection()->commit();
          $this->getResponse()->setStatusCode(201);
          $success = array(
              "message_number" => $message_number,
              "message" => "Message has been created successfully.",
          );
          return $this->returnData($success);
      }

      catch (\Exception $e) {
          $adapter->getDriver()->getConnection()->rollback();
          $this->getResponse()->setStatusCode(404);
          return $this->returnData(array('message' => $e->getMessage()));
      }
    }

   /**
   * Update an order
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
     * Getting order table details.
     * 
     * @return mixed
     */
    public function getOrdersTable() {
        if (!$this->ordersTable) {
            $sm = $this->getServiceLocator();
            $this->ordersTable = $sm->get('Api\Model\OrdersTable');
        }
        return $this->ordersTable;
    }

  /*
   * Getting order item table details.
   * 
   * @return mixed
   */
    
    public function getOrderItemsTable() {
        if (!$this->ordersItemsTable) {
            $sm = $this->getServiceLocator();
            $this->ordersItemsTable = $sm->get('Api\Model\OrdersItemsTable');
        }

        return $this->ordersItemsTable;
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
        if($this->requestHasContentType($request, self::CONTENT_TYPE_JSON)) {
            return new JsonModel(array('messages' => $data));
        }

        return new XmlModel(array("data" => array('message' => $data),"rootNode" => "messages"));
    }
}
	
	