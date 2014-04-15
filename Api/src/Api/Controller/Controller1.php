<?php

namespace Api\Controller;

use Api\Mvc\Controller\RestfulController,
    Zend\View\Model\JsonModel,
    Zend\Json\Json,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Debug\Debug,
    AP_XmlStrategy\View\Model\XmlModel,
    Api\Model\Master,
    Zend\Db\Adapter\Adapter as DbAdapter;
	
use Zend\Authentication\Adapter\DbTable as AuthAdapter;


class Controller1 extends RestfulController
{
    protected $MasterTable;
    
    
	/**
   * Return the master details for the id
   * @param mixed $id
   * @see getMasterTable() 
   * @return mixed
	**/
    
	public function get($id)
	{
	    try {
        if(empty($id)) {
            $this->getResponse()->setStatusCode(400);
            return $this->returnData(array("message" => "ID is required."));
        }
        
		    $value = $this->getMasterTable()->find($id);
        if(empty($value)) {
            $this->getResponse()->setStatusCode(204);
        }
			
		    return $this->returnData($value);
		}
		catch (\Exception $e) {
            $this->getResponse()->setStatusCode(404);
    	    return $this->returnData(array('message' => $e->getMessage()));
        }
	}
	
  /*
   * Return .
   * @param mixed[] $params which will have query parameters
   * @return mixed
   */
  
	public function getList()
	{
	     try {
            $params = array();
            $params = $this->params()->fromQuery();
            $params = array_change_key_case($params, CASE_LOWER);
            $inputParameters = array('value' => 'value');

            if(empty($params)){
                $this->getResponse()->setStatusCode(400);
                return $this->returnData(array("message" => "Please input all mandatory parameters.")); 
            }

            if(!array_key_exists('value', $params)) {
                $this->getResponse()->setStatusCode(400);
                return $this->returnData(array("message" => "value parameter is mandatory"));
            } else if(empty($params['value'])) {
                $this->getResponse()->setStatusCode(400);
                return $this->returnData(array("message" => "value parameter is mandatory"));
            }



            foreach($params as $key => $value) {
                if(array_key_exists($key, $inputParameters)) {
                } else {
                    $this->getResponse()->setStatusCode(400);
                    return $this->returnData(array("message" => "Unidentified parameter is found"));
                }
            }

            $masterlist = $this->getMasterTable()->fetchMasterList($params);
            if(empty($masterlist)) {
                $this->getResponse()->setStatusCode(204);
                return $this->returnData(array("message" => "Could not find masterlist"));
            }
            return $this->returnData($masterlist); 
        }
        catch (\Exception $e) {
            $this->getResponse()->setStatusCode(404);
            return $this->returnData(array('message' => $e->getMessage()));
        }
	}
	
  /**
   * Create a new masterlist.
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
          $this->getResponse()->setStatusCode(405);
    	    return $this->returnData(array('message' => $e->getMessage()));
      }
	}
	
  /**
   * Update an existing masterlist
   * This function does not have any functionality.
   * @param  mixed $id   
   * @param  mixed $data
   * @return mixed Returns status code with 405.
   */
  
	public function update($id, $data)
	{
	    try {
          return $this->returnData(array("message" => "Method not allowed"));
      }
      catch (\Exception $e) {
          $this->getResponse()->setStatusCode(405);
    	    return $this->returnData(array('message' => $e->getMessage()));
      }
	}
  
  /*
   * Getting masterlist master table details.
   * 
   * @return mixed
   */
  
	public function getMasterTable() {
      if (!$this->MasterTable) {
          $sm = $this->getServiceLocator();
          $this->MasterTable = $sm->get('Api\Model\MasterTable');
      }
      return $this->MasterTable;
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
          return new JsonModel(array('masterlist' => $data));
      }
      return new XmlModel(array("data" => array('masterlist' => $data),"rootNode" => "masterlist"));
	}
}