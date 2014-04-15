<?php
namespace Api\Mvc\Controller;

use Zend\Mvc\Controller\AbstractRestfulController,
    Zend\Stdlib\RequestInterface as Request,
    Zend\Json\Json,
    Zend\Mvc\MvcEvent;

class RestfulController extends AbstractRestfulController
{

    /**
     * Return list of resources
     *
     * @return mixed
     */
    public function getList()
    {
        return parent::getList();
    }

    /**
     * Return single resource
     *
     * @param  mixed $id
     * @return mixed
     */
    public function get($id)
    {
        return parent::get($id);
    }

    /**
     * Create a new resource
     *
     * @param  mixed $data
     * @return mixed
     */
    public function create($data)
    {
        return parent::create($data);
    }

    /**
     * Update an existing resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return mixed
     */
    public function update($id, $data)
    {
        return parent::update($id, $data);
    }

    /**
     * Delete an existing resource
     *
     * @param  mixed $id
     * @return mixed
     */
    public function delete($id)
    {
        return parent::delete($id);
    }


    /**
     * Process post data and call create
     *
     * @param Request $request
     * @return mixed
     */
    public function processPostData(Request $request)
    {
        $contentType = $request->getHeaders('Content-Type')->getFieldValue();
        if ($contentType == 'application/xml'){
		    $content = $request->getContent();
			$jsonContent = Json::fromXml($content, true);
			$data = Json::decode($jsonContent, Json::TYPE_ARRAY);
		}
		else if ($this->requestHasContentType($request, self::CONTENT_TYPE_JSON)) {
		 $jsonContent = $request->getContent();
		 $data = Json::decode($jsonContent, Json::TYPE_ARRAY);
		}
		else{
         $data = $request->getPost()->toArray();   
		 }

        // return $this->create($request->getPost()->toArray());
		
		
		return $this->create($data);
    }
	
	/**
     * Process post data and call create
     *
     * @param Request $request
     * @return mixed
     */
    protected function processBodyContent($request)
    {
        $content = $request->getContent();
		
		$contentType = $request->getHeaders('Content-Type')->getFieldValue();
		if ($contentType == 'application/xml'){
		    $jsonContent = Json::fromXml($content, true);
			return Json::decode($jsonContent, Json::TYPE_ARRAY);
		}

        // JSON content? decode and return it.
        if ($this->requestHasContentType($request, self::CONTENT_TYPE_JSON)) {
            return Json::decode($content, Json::TYPE_ARRAY);
        }

        parse_str($content, $parsedParams);

        // If parse_str fails to decode, or we have a single element with key
        // 0, return the raw content.
        if (!is_array($parsedParams)
            || (1 == count($parsedParams) && isset($parsedParams[0]))
        ) {
            return $content;
        }

        return $parsedParams;
    }

    /**
     * Process put data and call update
     *
     * @param Request $request
     * @param $routeMatch
     * @return mixed
     * @throws Exception\DomainException
     */
    public function processPutData(Request $request, $routeMatch)
    {
        if (null === $id = $routeMatch->getParam('id')) {
            if (!($id = $request->getQuery()->get('id', false))) {
                throw new Exception\DomainException('Missing identifier');
            }
        }

        $contentType = $request->getHeaders('Content-Type')->getFieldValue();
        $content = $request->getContent();
        if ($contentType == 'application/json')
            $parsedParams = Json::decode($content, Json::TYPE_ARRAY);
        else
            parse_str($content, $parsedParams);

        return $this->update($id, $parsedParams);
    }
}
