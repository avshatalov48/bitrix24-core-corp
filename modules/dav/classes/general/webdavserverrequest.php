<?php

class CDavWebDavServerRequest
	extends CDavRequest
{
	protected $entityType = null;
	protected $entityId = null;

	public function __construct($arRequestParameters)
	{
		$this->arRequestParameters = $arRequestParameters;

		if (!isset($this->arRequestParameters['PATH_INFO']) && isset($this->arRequestParameters['ORIG_PATH_INFO']))
			$this->arRequestParameters['PATH_INFO'] = $this->arRequestParameters['ORIG_PATH_INFO'];

		/*
		static $arAgentsMap = array(
			'iphone'            => 'iphone',
		);

		$httpUserAgent = strtolower($this->arRequestParameters['HTTP_USER_AGENT']);
		foreach ($arAgentsMap as $pattern => $name)
		{
			if (strpos($httpUserAgent, $pattern) !== false)
			{
				$this->agent = $name;
				break;
			}
		}
		*/

		$this->isUrlRequired = false; //($this->agent == 'kde');
		$this->isRedundantNamespaceDeclarationsRequired = false; //in_array($this->agent, array('cfnetwork', 'dataaccess', 'davkit', 'neon', 'iphone'));

		$uri = "";
		if ($this->isUrlRequired)
			$uri = ($this->GetParameter("HTTPS") === "on" ? "https" : "http").'://'.$this->GetParameter('HTTP_HOST');

		$requestUri = $this->GetParameter('REQUEST_URI');
		$requestUri = preg_replace("/%0D|%0A/i", "", $requestUri);
		$requestUri = preg_replace("/\+/i", "%2B", $requestUri);
		$requestUri = urldecode($requestUri);
		$requestUri = preg_replace("/\r|\n/i", "", $requestUri);

		$uri .= $requestUri;

		$this->baseUri = "";//$uri;
		$this->uri = str_replace("//", "/", $uri);
		$this->path = $requestUri;

		if (ini_get("magic_quotes_gpc"))
			$this->path = stripslashes($this->path);

		if ($this->GetParameter('HTTP_DEPTH') !== null)
		{
			$this->depth = $this->GetParameter('HTTP_DEPTH');
		}
		else
		{
			if (in_array($this->GetParameter('REQUEST_METHOD'), array('PROPFIND', 'DELETE', 'MOVE', 'COPY', 'LOCK')))
				$this->depth = 'infinity';
			elseif ($this->GetParameter('REQUEST_METHOD') == "GET")
				$this->depth = 1;
			else
				$this->depth = 0;
		}
		if ($this->depth != 'infinity')
			$this->depth = intval($this->depth);


//		$patterns = array(
//			"user1" => "(/company/personal/user/(\\d+)/files/lib)(.*)$",
//			"user2" => "(/company/personal/user/(\\d+)/disk/path)(.*)$",
//			"group1" => "(/workgroups/group/(\\d+)/files)(.*)$",
//			"group2" => "(/workgroups/group/(\\d+)/disk/path)(.*)$",
//			"docs1" => "(/docs/disk/path)(.*)$",
//			"docs2" => "(/docs)(.*)$",
//		);
//		$patternMap = array('user1' => 'user', 'user2' => 'user', 'group1' => 'group', 'group2' => 'group', 'docs1' => 'docs', 'docs2' => 'docs');
//
//		foreach ($patterns as $key => $pattern)
//		{
//			$matches = array();
//			if (preg_match("#".$pattern."#i", $this->path, $matches))
//			{
//				$this->entityType = $patternMap[$key];
//				$this->entityId = $matches[2];
//				$this->path = $matches[3];
//				$this->baseUri = $matches[1];
//				if ($this->entityType == 'docs')
//				{
//					$this->path = $this->entityId;
//					$this->entityId = null;
//				}
//				break;
//			}
//		}
	}

	public function GetEntityId()
	{
		return $this->entityId;
	}

	public function GetEntityType()
	{
		return $this->entityType;
	}
}
