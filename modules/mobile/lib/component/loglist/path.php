<?php
namespace Bitrix\Mobile\Component\LogList;

use Bitrix\Socialnetwork\Component\LogList\Util;

class Path
{
	public function __construct($params)
	{
		if (!empty($params['component']))
		{
			$this->component = $params['component'];
		}

		if (!empty($params['request']))
		{
			$this->request = $params['request'];
		}
		else
		{
			$this->request = Util::getRequest();;
		}
	}

	public function getRequest()
	{
		return $this->request;
	}

	public function getComponent()
	{
		return $this->component;
	}


	public function preparePathParams(&$componentParams)
	{
		$result = [];


		$componentParams['PATH_TO_USER'] = trim($componentParams['PATH_TO_USER']);
		$componentParams['PATH_TO_GROUP'] = trim($componentParams['PATH_TO_GROUP']);

		Util::checkEmptyParamString($componentParams, 'PATH_TO_SMILE', '/bitrix/images/socialnetwork/smile/');

		if (!empty($componentParams['PATH_TO_USER_BLOG_POST']))
		{
			$componentParams["PATH_TO_USER_MICROBLOG_POST"] = $componentParams['PATH_TO_USER_BLOG_POST'];
		}

/*
		$mobileContext = new \Bitrix\Mobile\Context();
		$componentParams['PATH_TO_LOG_ENTRY_EMPTY'] .= (mb_strpos($componentParams['PATH_TO_LOG_ENTRY_EMPTY'], '?') !== false ? '&' : '?') . 'version=' . $mobileContext->version;
*/
		return $result;
	}

	public function setPaths(&$params)
	{
		$pathResult = $this->preparePathParams($params);
	}
}
?>