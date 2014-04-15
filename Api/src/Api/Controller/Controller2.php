<?php

namespace Api\Controller;

use Api\Mvc\Controller\RestfulController,
    Zend\View\Model\JsonModel,
    Zend\Json\Json,
    Zend\Http\Request,
    AP_XmlStrategy\View\Model\XmlModel;
	
class Controller2 extends RestfulController {
    protected $ItemsTable;
    
	/**
   * Return the item details for the id.
   * Currently this function does not have any functionality.
   * @param mixed $id
   * @return mixed Returns status code with 405.
	**/
    
	public function get($id) {
      try {
          $this->getResponse()->setStatusCode(405);
          return $this->returnData(array("message"=>"Method not allowed."));   
      }
      
      catch (\Exception $e) {
          $this->getResponse()->setStatusCode(404);
    	    return new JsonModel(array('message' => $e->getMessage()));
      }
	}
	
  /**
   * Return products available and its details of a based on ID.
   * @param mixed[] $params which will have query parameters
   * @see getMasterTable()
   * @return mixed
   **/
  
	public function getList() {
      try {
          $params = array();
          $params = $this->params()->fromQuery();
          $params = array_change_key_case($params, CASE_LOWER);
          if(empty($params) || (count($params) > 1) || !array_key_exists('id', $params)){
              $this->getResponse()->setStatusCode(400);
              return $this->returnData(array("message" => "Bad Request"));
          }
          if(empty($params['id'])){
              $this->getResponse()->setStatusCode(400);
              return $this->returnData(array("message" => "ID is missing."));
          }
          $Items = $this->getMasterTable()->fetchItems($params);
          if(empty($Items)) {
            $this->getResponse()->setStatusCode(204);
          }
          return $this->returnData($Items); 
      }
      
      catch (\Exception $e) {
          $this->getResponse()->setStatusCode(404);
    	    return new returnData(array('message' => $e->getMessage()));
      }
	}
	
  /**
   * Create a new product.
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
    	    return new returnData(array('message' => $e->getMessage()));
      }	
	}
	
  
  /**
   * Update a product
   * This function does not have any functionality.
   * @param  mixed $id   
   * @param  mixed $data
   * @return mixed Returns status code with 405.
   */
  
	public function update($id, $data) {
      try {
			    $this->getResponse()->setStatusCode(405);
          return $this->returnData(array("message" => "Method not allowed."));
      }
      
      catch (\Exception $e) {
          $this->getResponse()->setStatusCode(404);
    	    return new returnData(array('message' => $e->getMessage()));
      }
	}
  
  /*
   * Getting items table details.
   * 
   * @return mixed
   */
  
	public function getMasterTable() {
      if (!$this->ItemsTable) {
          $sm = $this->getServiceLocator();
          $this->ItemsTable = $sm->get('Api\Model\ItemsTable');
      }
      return $this->ItemsTable;
  }
  
  /*
   * Return data based on content type
   * 
   * @param mixed $data
   * @return mixed Contains XML or JSON structure depends on Content-Type
   */
  
	public function returnData($data)
	{
      $request = new Request();
      $request = $this->getRequest();
      if ($this->requestHasContentType($request, self::CONTENT_TYPE_JSON)) {
          return new JsonModel(array('product' => $data));
      }
      
      return new XmlModel(array("data" => array('product' => $data),"rootNode" => "products"));
	}
}
	
	