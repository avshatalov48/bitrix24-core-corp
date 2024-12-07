<?
class CDavRequest
{
	protected $arRequestParameters = null;

	protected $agent = null;
	protected $isUrlRequired = false;
	protected $isRedundantNamespaceDeclarationsRequired = false;

	protected $uri = "";		// complete URI for this request
	protected $baseUri = "";	// base URI for this request
	protected $path = "";		// URI path for this request

	protected $depth = 0;
	protected $arContentParameters = null;

	protected $xmlDocument = null;
	protected $rawRequestBody = null;

	protected $principal = null;

	public function __construct($arRequestParameters)
	{
		$this->arRequestParameters = $arRequestParameters;

		if (!isset($this->arRequestParameters['PATH_INFO']) && isset($this->arRequestParameters['ORIG_PATH_INFO']))
		{
			$this->arRequestParameters['PATH_INFO'] = $this->arRequestParameters['ORIG_PATH_INFO'];
		}

		static $arAgentsMap = [
			'iphone'            => 'iphone',		// Apple iPhone iCal
			'davkit'            => 'davkit',		// Apple iCal
			'mac os'            => 'davkit',		// Apple iCal (Mac Os X > 10.8)
			'macos'             => 'davkit',         // Apple iCal (Mac Os > 11)
			'mac_os_x'          => 'davkit',		// Apple iCal (Mac Os X > 10.8)
			'mac+os+x'          => 'davkit',		// Apple iCal (Mac Os X > 10.10)
			'dataaccess'        => 'dataaccess',	// Apple addressbook iPhone
			'cfnetwork'         => 'cfnetwork',		// Apple Addressbook
			'bionicmessage.net' => 'funambol',		// funambol GroupDAV connector from bionicmessage.net
			'zideone'           => 'zideone',		// zideone outlook plugin
			'lightning'         => 'lightning',		// Lighting (SOGo connector for addressbook)
			'webkit'			=> 'webkit',		// Webkit Browser (also reports KHTML!)
			'khtml'             => 'kde',			// KDE clients
			'neon'              => 'neon',
			'ical4ol'			=> 'ical4ol',		// iCal4OL client
			'sunbird'			=> 'sunbird',		// Mozilla Sunbird
			'ios/5'             => 'dataaccess',    // iOS/5
			'ios/6'             => 'dataaccess',    // iOS/6
			'ios/7'             => 'dataaccess',    // iOS/7
			'ios/8'             => 'dataaccess',    // iOS/8
			'ios/9'             => 'dataaccess',    // iOS/9
			'ios/10'            => 'dataaccess',    // iOS/10
			'ios/11'            => 'dataaccess',    // iOS/11
			'ios/12'            => 'dataaccess',    // iOS/12
			'ios/13'            => 'dataaccess',    // iOS/13
			'ios/14'            => 'dataaccess',    // iOS/14
			'ios/15'            => 'dataaccess',    // iOS/15
			'ios/16'            => 'dataaccess',    // iOS/16
			'ios/17'            => 'dataaccess',    // iOS/17
			'ios/18'            => 'dataaccess',    // iOS/18
			'carddavbitrix24'   => 'dataaccess',
			'caldavbitrix24'    => 'dataaccess',
			'coredav'           => 'davkit',    //
		];

		$httpUserAgent = mb_strtolower($this->arRequestParameters['HTTP_USER_AGENT']);
		foreach ($arAgentsMap as $pattern => $name)
		{
			if (mb_strpos($httpUserAgent, $pattern) !== false)
			{
				$this->agent = $name;
				break;
			}
		}

		$this->isUrlRequired = ($this->agent === 'kde');
		$this->isRedundantNamespaceDeclarationsRequired = in_array($this->agent, array('cfnetwork', 'dataaccess', 'davkit', 'neon', 'iphone'));

		$uri = "";
		if ($this->isUrlRequired)
		{
			$scheme = $this->GetParameter("HTTPS") === "on"
				? "https"
				: "http"
			;
			$uri = $scheme . '://' . $this->GetParameter('HTTP_HOST');
		}

		$requestUri = $this->GetParameter('REQUEST_URI');
		$requestUri = preg_replace("/%0D|%0A/i", "", $requestUri);
		$requestUri = urldecode($requestUri);
		$requestUri = preg_replace("/\r|\n/i", "", $requestUri);

		$uri .= $requestUri;

		$p = $this->GetParameter("PATH_INFO");
		if (!empty($p))
		{
			$uri = mb_substr($uri, 0, -mb_strlen($p));
		}

		$pathInfo = empty($p) ? "/" : $p;

		if (mb_substr($pathInfo, -mb_strlen("/index.php")) === "/index.php")
		{
			$pathInfo = mb_substr($pathInfo, 0, -mb_strlen("/index.php"));
		}

		$this->baseUri = $uri;
		$this->uri = str_replace("//", "/", $uri.$pathInfo);
		$this->path = strtr($pathInfo, array('%' => '%25', '#' => '%23', '?' => '%3F'));

		if (empty($this->path))
		{
			if ($this->GetParameter("REQUEST_METHOD") === "GET")
			{
				header("Location: ".$this->baseUri."/");
				die();
			}

			$this->path = "/";
		}

		if (ini_get("magic_quotes_gpc"))
		{
			$this->path = stripslashes($this->path);
		}


		if ($this->GetParameter('HTTP_DEPTH') !== null)
		{
			$this->depth = $this->GetParameter('HTTP_DEPTH');
		}
		elseif (in_array($this->GetParameter('REQUEST_METHOD'), ['PROPFIND', 'DELETE', 'MOVE', 'COPY', 'LOCK']))
		{
			$this->depth = 'infinity';
		}
		elseif ($this->GetParameter('REQUEST_METHOD') === "GET")
		{
			$this->depth = 1;
		}
		else
		{
			$this->depth = 0;
		}

		if ($this->depth !== 'infinity')
		{
			$this->depth = intval($this->depth);
		}
	}

	public function GetPrincipal()
	{
		if (is_null($this->principal))
		{
			$this->principal = new CDavPrincipal("current");
		}

		return $this->principal;
	}

	public function GetPrincipalUrl()
	{
		return $this->GetPrincipal()->GetPrincipalUrl($this);
	}

	public function GetParameter($parameterName)
	{
		$parameterName = trim($parameterName);
		if ($parameterName === '')
		{
			throw new Exception("parameterName");
		}

		if (array_key_exists($parameterName, $this->arRequestParameters))
		{
			return $this->arRequestParameters[$parameterName];
		}

		return null;
	}

	public function GetAgent()
	{
		return $this->agent;
	}

	public function IsUrlRequired()
	{
		return $this->isUrlRequired;
	}

	public function IsRedundantNamespaceDeclarationsRequired()
	{
		return $this->isRedundantNamespaceDeclarationsRequired;
	}

	public function GetUri()
	{
		return $this->uri;
	}

	public function GetBaseUri()
	{
		return $this->baseUri;
	}

	public function GetPath()
	{
		return $this->path;
	}

	public function GetSiteId()
	{
		return SITE_ID;
	}

	public function GetDepth()
	{
		return $this->depth;
	}

	public function GetXmlDocument()
	{
		if ($this->xmlDocument == null)
		{
			$rawPost = $this->GetRequestBody();
			$this->xmlDocument = CDavXmlDocument::LoadFromString($rawPost);
		}

		return $this->xmlDocument;
	}

	public function GetRequestBody()
	{
		if ($this->rawRequestBody == null)
		{
			$this->rawRequestBody = file_get_contents('php://input');

			CDav::Report(
				"<<<<<<<<<<<<<< REQUEST BODY >>>>>>>>>>>>>>>>",
				"\n".$this->rawRequestBody."\n",
				"UNDEFINED",
				true
			);
		}

		return $this->rawRequestBody;
	}

	public function GetContentParameters()
	{
		if ($this->arContentParameters != null)
			return $this->arContentParameters;

		$this->arContentParameters = array();

		if ($this->GetParameter('CONTENT_LENGTH') !== null)
			$this->arContentParameters["CONTENT_LENGTH"] = $this->GetParameter('CONTENT_LENGTH');
		elseif ($this->GetParameter('X-Expected-Entity-Length') !== null)
			$this->arContentParameters["CONTENT_LENGTH"] = $this->GetParameter('X-Expected-Entity-Length');

		$this->arContentParameters["CONTENT_TYPE"] = "application/octet-stream";
		if ($this->GetParameter("CONTENT_TYPE") !== null)
			$this->arContentParameters["CONTENT_TYPE"] = $this->GetParameter("CONTENT_TYPE");

		foreach ($this->arRequestParameters as $key => $val)
		{
			if (strncmp($key, "HTTP_CONTENT", 12))
				continue;
			if (in_array($key, array('HTTP_CONTENT_LENGTH', 'HTTP_CONTENT_TYPE')))
				continue;

			$this->arContentParameters[mb_strtoupper(mb_substr($key, 5))] = $val;
		}

		return $this->arContentParameters;
	}
}
?>