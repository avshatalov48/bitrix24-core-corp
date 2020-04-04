<?
abstract class CDavWebDav
{
	private $httpStatus = "200 OK";

	private $davPoweredBy = "Bitrix WebDav Server";	// String to be used in "X-Dav-Powered-By" header

	protected $request;
	protected $response;

	/**
	 * CDavWebDav constructor.
	 * @param CDavRequest $request
	 */
	public function __construct($request)
	{
		ini_set("display_errors", 0);
		$this->request = $request;

		$cs = CDav::GetCharset();
		if (is_null($cs) || empty($cs))
			$cs = "utf-8";

		/** @var CDavRequest $request */
		$request = $this->request;

		$this->response = new CDavResponse($request->GetParameter('REQUEST_URI'), $cs);
	}

	protected function SetDavPoweredBy($val)
	{
		$this->davPoweredBy = $val;
	}

	protected function GetDavPoweredBy()
	{
		return $this->davPoweredBy;
	}

	public function GetRequest()
	{
		return $this->request;
	}

	public function GetResponse()
	{
		return $this->response;
	}

	/**
	* Process WebDAV HTTP request.
	*
	* @param void
	* @return void
	*/
	public function ProcessRequest()
	{
		/** @var CDavRequest $request */
		$request = $this->request;
		$response = $this->response;

		if (strstr($request->GetParameter("REQUEST_URI"), '#'))
		{
			$response->SetHttpStatus("400 Bad Request");
			$response->Render();
			return;
		}

		$response->AddHeader("X-Dav-Powered-By: ".$this->davPoweredBy);

		// skip auth check for OPTIONS requests on "/" - http://pear.php.net/bugs/bug.php?id=5363
		if (($request->GetParameter('REQUEST_METHOD') != 'OPTIONS' || (/*($this instanceof CDavGroupDav) && */($request->GetPath() != "/"))) && !$this->CheckAuthWrapper())
		{
			$response->SetHttpStatus('401 Unauthorized');
			$response->AddHeader('WWW-Authenticate: Basic realm="'.$this->davPoweredBy.'"');

			if(($this instanceof CDavWebDavServer) && CDav::isDigestEnabled() && COption::GetOptionString("main", "use_digest_auth", "N") == "Y")
			{
				// On first try we found that we don't know user digest hash. Let ask only Basic auth first.
				if($_SESSION["BX_HTTP_DIGEST_ABSENT"] !== true)
					$response->AddHeader('WWW-Authenticate: Digest realm="'.$this->davPoweredBy.'", nonce="'.uniqid().'"');
			}
			$response->Render();

			return;
		}

		if (!$this->CheckIfHeaderConditions())
			return;

		$method = strtolower($request->GetParameter("REQUEST_METHOD"));
		$wrapper = $method."Wrapper";

		if ($method == "head" && !method_exists($this, "head"))
			$method = "get";

		if (method_exists($this, $wrapper) && ($method == "options" || method_exists($this, $method)))
		{
			$this->$wrapper();
			$response->Render();
		}
		else
		{
			if ($request->GetParameter("REQUEST_METHOD") == "LOCK")
			{
				$error = '412 Precondition failed';
			}
			else
			{
				$error = '405 Method not allowed';
				$response->AddHeader("Allow: ".join(",", $this->GetAllowableMethods()));
			}
			$response->GenerateError($error);
			$response->Render();
		}
	}

	/**
	* Check for implemented HTTP methods.
	*
	* @param void
	* @return array
	*/
	protected function GetAllowableMethods()
	{
		$arAllowableMethods = array("OPTIONS" => "OPTIONS");

		foreach (get_class_methods($this) as $method)
		{
			if (!strcasecmp("Wrapper", substr($method, -7)))
			{
				$method = strtoupper(substr($method, 0, -7));
				if (method_exists($this, $method))
					$arAllowableMethods[$method] = $method;
			}
		}

		if (isset($arAllowableMethods["GET"]))
			$arAllowableMethods["HEAD"] = "HEAD";

		if (!method_exists($this, "CheckLock"))
		{
			unset($arAllowableMethods["LOCK"]);
			unset($arAllowableMethods["UNLOCK"]);
		}

		return $arAllowableMethods;
	}

	protected function CheckAuthWrapper()
	{
		/** @var CDavRequest $request */
		$request = $this->request;

		if (method_exists($this, "CheckAuth"))
		{
			$authType = $request->GetParameter("AUTH_TYPE");
			$phpAuthUser = $request->GetParameter("PHP_AUTH_USER");
			$phpAuthPw = $request->GetParameter("PHP_AUTH_PW");

			$authorization = $request->GetParameter("Authorization");
			if (is_null($authorization))
				$authorization = $request->GetParameter("REMOTE_USER");
			if (is_null($authorization))
				$authorization = $request->GetParameter("REDIRECT_REMOTE_USER");

			if (is_null($phpAuthUser) && !is_null($authorization) && strpos($authorization, 'Basic ') === 0)
			{
				$hash = base64_decode(substr($authorization, 6));
				if (strpos($hash, ':') !== false)
					list($phpAuthUser, $phpAuthPw) = explode(':', $hash, 2);
			}

			return $this->CheckAuth(
				$authType,
				$phpAuthUser,
				$phpAuthPw
			);
		}
		else
		{
			return true;
		}
	}

	private function SearchIfHeaderConditionsToken($string, &$pos)
	{
		while (in_array($string{$pos}, array(' ', '\n', '\r', '\t')))
			++$pos;

		if (strlen($string) <= $pos)
			return false;

		$c = $string{$pos++};

		switch ($c)
		{
			case "<":
				$pos2 = strpos($string, ">", $pos);
				$uri = substr($string, $pos, $pos2 - $pos);
				$pos = $pos2 + 1;
				return array("URI", $uri);

			case "[":
				if ($string{$pos} == "W")
				{
					$type = "ETAG_WEAK";
					$pos += 2;
				}
				else
				{
					$type = "ETAG_STRONG";
				}
				$pos2 = strpos($string, "]", $pos);
				$etag = substr($string, $pos + 1, $pos2 - $pos - 2);
				$pos = $pos2 + 1;
				return array($type, $etag);

			case "N":
				$pos += 2;
				return array("NOT", "Not");

			default:
				return array("CHAR", $c);
		}
	}

	private function ParceIfHeaderConditions($str)
	{
		$pos = 0;
		$len = strlen($str);
		$arUri = array();

		while ($pos < $len)
		{
			$token = $this->SearchIfHeaderConditionsToken($str, $pos);

			if ($token[0] == "URI")
			{
				$uri = $token[1];
				$token = $this->SearchIfHeaderConditionsToken($str, $pos);
			}
			else
			{
				$uri = "";
			}

			if ($token[0] != "CHAR" || $token[1] != "(")
				return false;

			$arList = array();
			$level = 1;
			$not = "";
			while ($level)
			{
				$token = $this->SearchIfHeaderConditionsToken($str, $pos);
				if ($token[0] == "NOT")
				{
					$not = "!";
					continue;
				}

				switch ($token[0])
				{
					case "CHAR":
						switch ($token[1])
						{
							case "(":
								$level++;
								break;
							case ")":
								$level--;
								break;
							default:
								return false;
						}
						break;

					case "URI":
						$arList[] = $not."<".$token[1].">";
						break;

					case "ETAG_WEAK":
						$arList[] = $not."[W/'".$token[1]."']>";
						break;

					case "ETAG_STRONG":
						$arList[] = $not."['".$token[1]."']>";
						break;

					default:
						return false;
				}
				$not = "";
			}

			if (!array_key_exists($uri, $arUri))
				$arUri[$uri] = array();

			$arUri[$uri] = array_merge($arUri[$uri], $arList);
		}

		return $arUri;
	}

	/**
	* Check a single URI condition parsed from an if-header
	*
	* @abstract
	* @param string $uri URI to check
	* @param string $condition Condition to check for this URI
	* @returns bool Condition check result
	*/
	private function CheckIfHeaderUriCondition($uri, $condition)
	{
		if (!strncmp("<DAV:", $condition, 5))
			return false;

		return true;
	}

	/**
	* Check if conditions from "If:" headers are meat. The "If:" header is an extension to HTTP/1.1 defined in RFC 2518 section 9.4
	*
	* @param void
	* @return bool
	*/
	private function CheckIfHeaderConditions()
	{
		/** @var CDavRequest $request */
		$request = $this->request;

		$httpIf = $request->GetParameter("HTTP_IF");
		if ($httpIf != null)
		{
			$arIfHeaderUris = $this->ParceIfHeaderConditions($httpIf);

			foreach ($arIfHeaderUris as $uri => $arConditions)
			{
				if ($uri == "")
					$uri = $request->GetUri();

				$bMatchConditions = true;
				foreach ($arConditions as $condition)
				{
					// RFC2518 6.3 - 6.4
					if (!strncmp($condition, "<opaquelocktoken:", strlen("<opaquelocktoken")))
					{
						if (!preg_match('/^<opaquelocktoken:[[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12}>$/', $condition))
						{
							$this->HttpStatus("423 Locked");
							return false;
						}
					}
					if (!$this->CheckIfHeaderUriCondition($uri, $condition))
					{
						$this->HttpStatus("412 Precondition failed");
						$bMatchConditions = false;
						break;
					}
				}

				if ($bMatchConditions)
					return true;
			}
			return false;
		}
		return true;
	}

	protected function HttpStatus($status = "200 OK")
	{
		$this->httpStatus = $status;

		header("HTTP/1.1 ".$status);
		header("X-WebDAV-Status: ".$status, true);
	}

	public static function showOptions()
	{
		$request = new CDavRequest($_SERVER);
		$dav = new static($request);
		$dav->OPTIONSWrapper();
	}

	protected function OPTIONSWrapper()
	{
		$response = $this->response;
		$response->AddHeader("MS-Author-Via: DAV");

		$arAllowableMethods = $this->GetAllowableMethods();

		$arDav = array(1);
		if (isset($arAllowableMethods['LOCK']))
			$arDav[] = 2;

		if (method_exists($this, 'OPTIONS'))
			$this->OPTIONS($arDav, $arAllowableMethods);

		$response->SetHttpStatus("200 OK");
		$response->AddHeader("DAV: ".join(",", $arDav));
		$response->AddHeader("Allow: ".join(",", $arAllowableMethods));
		$response->AddHeader("Content-length: 0");
	}

	protected function LockDiscovery($path)
	{
		if (!method_exists($this, "Checklock"))
			return "";

		$activelocks = "";

		/** @var CDavRequest $request */
		$request = $this->request;

		$lock = $this->Checklock($path);
		if (is_array($lock) && count($lock))
		{
			if (!empty($lock["EXPIRES"]))
				$timeout = "Second-".($lock["EXPIRES"] - time());
			else
				$timeout = "Infinite";

			if ($request->IsRedundantNamespaceDeclarationsRequired())
			{
				$activelocks.= "
					<activelock>
						<lockscope><".$lock["LOCK_SCOPE"]."/></lockscope>
						<locktype><".$lock["LOCK_TYPE"]."/></locktype>
						<depth>".$lock["LOCK_DEPTH"]."</depth>
						<owner>".$lock["LOCK_OWNER"]."</owner>
						<timeout>".$timeout."</timeout>
						<locktoken><href>".$lock["ID"]."</href></locktoken>
					</activelock>
				";
			}
			else
			{
				$activelocks.= "
					<D:activelock>
						<D:lockscope><D:".$lock["LOCK_SCOPE"]."/></D:lockscope>
						<D:locktype><D:".$lock["LOCK_TYPE"]."/></D:locktype>
						<D:depth>".$lock["LOCK_DEPTH"]."</D:depth>
						<D:owner>".$lock["LOCK_OWNER"]."</D:owner>
						<D:timeout>".$timeout."</D:timeout>
						<D:locktoken><D:href>".$lock["ID"]."</D:href></D:locktoken>
					</D:activelock>
				";
			}
		}

		return $activelocks;
	}

	protected function PROPFINDWrapper($handler = 'PROPFIND')
	{
		/** @var CDavRequest $request */
		$request = $this->request;
		$response = $this->response;

		try
		{
			$requestDocument = $request->GetXmlDocument();
		}
		catch (CDavXMLParsingException $e)
		{
			$response->GenerateError("400 Error", $e->getMessage());
			return;
		}
		catch (Exception $e)
		{
			$response->SetHttpStatus("400 Error");
			return;
		}

		$arResources = array();

		$retVal = $this->$handler($arResources);

		if ($retVal === false)
		{
			if (method_exists($this, "CheckLock"))
			{
				$arLock = $this->CheckLock($request->GetPath());
				if (is_array($arLock) && count($arLock) > 0)
				{
					$resource = new CDavResource();
					$resource->ExtractFromLock($request->GetPath(), $arLock);
					$arResources[] = $resource;
				}
			}

			if (count($arResources) == 0)
			{
				$response->SetHttpStatus("404 Not Found");
				return;
			}
		}
		elseif (is_string($retVal))
		{
			$message = "";
			if (substr($retVal, 0, 3) == '501')
				$message .= "The requested feature is not supported by this server.\n";
			$response->GenerateError($retVal, $message);

			return;
		}

		$response->SetHttpStatus('207 Multi-Status');

		$dav = array(1);
		$allow = false;
		if (method_exists($this, 'OPTIONS'))
			$this->OPTIONS($dav, $allow);

		$response->AddHeader("DAV: ".join(",", $dav));
		$response->AddHeader('Content-Type: text/xml; charset="utf-8"');

		$response->AddLine("<D:multistatus xmlns:D=\"DAV:\"".($this instanceof CDavWebDavServer ? " xmlns:Office=\"urn:schemas-microsoft-com:office:office\" xmlns:Repl=\"http://schemas.microsoft.com/repl/\" xmlns:Z=\"urn:schemas-microsoft-com:\"" : "").">");

		$bRequestedAllProp = (count($requestDocument->GetPath('/*/DAV::allprop')) > 0);
		if ($this instanceof CDavWebDavServer)
			$bRequestedAllProp = true;

		$bRequestedPropName = (count($requestDocument->GetPath('/*/DAV::propname')) > 0);

		$arRequestedPropsList = array();
		if (!$bRequestedAllProp)
		{
			$ar = $requestDocument->GetPath('/*/DAV::prop/*');
			foreach ($ar as $pw)
				$arRequestedPropsList[] = array("xmlns" => $pw->GetXmlNS(), "tagname" => $pw->GetTag());
		}

		foreach ($arResources as $resource)
		{
			/** @var CDavResource $resource */
			$arResourceProps = $resource->GetProperties();

			$arRequestedProps = &$arRequestedPropsList;
			if ($bRequestedAllProp)
				$arRequestedProps = &$arResourceProps;

			$xmlnsHash = array('DAV:' => 'D');
			$xmlnsDefs = 'xmlns:ns0="urn:uuid:c2f41010-65b3-11d1-a29f-00aa00c14882/"';

			$arPropStat = array("200" => array(), "404" => array());

			foreach ($arRequestedProps as &$requestedProp)
			{
				$bFound = false;

				foreach ($arResourceProps as &$prop)
				{
					if ($requestedProp["tagname"] == $prop["tagname"] && $requestedProp["xmlns"] == $prop["xmlns"])
					{
						$arPropStat["200"][] = &$prop;
						$bFound = true;
						break;
					}
				}

				if (!$bFound)
				{
					if ($requestedProp["xmlns"] === "DAV:" && $requestedProp["tagname"] === "lockdiscovery")
					{
						$arPropStat["200"][] = CDavResource::MakeProp("lockdiscovery", $this->LockDiscovery($resource->GetPath()), "DAV:");
						$bFound = true;
					}
					elseif ($request->GetParameter('HTTP_BRIEF') != 't')
					{
						$arPropStat["404"][] = CDavResource::MakeProp($requestedProp["tagname"], "", $requestedProp["xmlns"]);
					}
				}

				if (!empty($requestedProp["xmlns"]) && ($bFound || $request->GetParameter('HTTP_BRIEF') != 't'))
				{
					$xmlns = $requestedProp["xmlns"];
					if (!isset($xmlnsHash[$xmlns]))
					{
						$n = "ns".(count($xmlnsHash) + 1);
						$xmlnsHash[$xmlns] = $n;
						$xmlnsDefs .= " xmlns:$n=\"$xmlns\"";
					}
				}
			}

			$response->AddLine(" <D:response %s>", $xmlnsDefs);

			$href = $this->UrlEncode($response->Encode(rtrim($request->GetBaseUri(), '/')."/".ltrim($resource->GetPath(), '/')));
			$response->AddLine("  <D:href>%s</D:href>", $href);

			if (count($arPropStat["200"]) > 0)
			{
				$response->AddLine("   <D:propstat>");
				$response->AddLine("    <D:prop>");

				foreach ($arPropStat["200"] as &$p)
					CDavResource::RenderProperty($p, $xmlnsHash, $response, $request);

				$response->AddLine("    </D:prop>");
				$response->AddLine("    <D:status>HTTP/1.1 200 OK</D:status>");
				$response->AddLine("   </D:propstat>");
			}

			if (count($arPropStat["404"]) > 0)
			{
				$response->AddLine("   <D:propstat>");
				$response->AddLine("    <D:prop>");

				foreach ($arPropStat["404"] as &$p)
					CDavResource::RenderProperty($p, $xmlnsHash, $response, $request);

				$response->AddLine("    </D:prop>");
				$response->AddLine("    <D:status>HTTP/1.1 404 Not Found</D:status>");
				$response->AddLine("   </D:propstat>");
			}

			$response->AddLine(" </D:response>");
		}

		$response->AddLine("</D:multistatus>");
	}

	protected function PROPPATCHWrapper()
	{
		/** @var CDavRequest $request */
		$request = $this->request;
		$response = $this->response;

		if ($this->CheckLockStatus($request->GetPath()))
		{
			try
			{
				$requestDocument = $request->GetXmlDocument();
			}
			catch (CDavXMLParsingException $e)
			{
				$response->GenerateError("400 Error", $e->getMessage());
				return;
			}
			catch (Exception $e)
			{
				$response->SetHttpStatus("400 Error");
				return;
			}

			$arResources = array();
			$responseDescr = $this->PROPPATCH($arResources);

			$response->SetHttpStatus("207 Multi-Status");

			$response->AddHeader('Content-Type: text/xml; charset="utf-8"');

			$response->AddLine("<D:multistatus xmlns:D=\"DAV:\">");

			foreach ($arResources as $resource)
			{
				/** @var CDavResource $resource */
				$arResourceProps = $resource->GetProperties();

				$xmlnsHash = array('DAV:' => 'D');
				$xmlnsDefs = 'xmlns:ns0="urn:uuid:c2f41010-65b3-11d1-a29f-00aa00c14882/"';

				$response->AddLine(" <D:response %s>", $xmlnsDefs);

				$href = /*$response->Encode(*/$this->UrlEncode(rtrim($request->GetBaseUri(), '/')."/".ltrim($resource->GetPath(), '/'))/*)*/;
				$response->AddLine("  <D:href>%s</D:href>", $href);

				foreach ($arResourceProps as &$prop)
				{
					$response->AddLine("   <D:propstat>");
					$response->AddLine("    <D:prop>");
					CDavResource::RenderProperty($prop, $xmlnsHash, $response, $request);
					$response->AddLine("    </D:prop>");
					$response->AddLine("    <D:status>HTTP/1.1 ".$prop['status']."</D:status>");
					$response->AddLine("   </D:propstat>");
				}

				if ($responseDescr)
				{
					echo "	<D:responsedescription>".
					$response->Encode(htmlspecialcharsbx($responseDescr)).
					"</D:responsedescription>\n";
				}

				$response->AddLine(" </D:response>");
			}

			$response->AddLine("</D:multistatus>");
		}
		else
		{
			$response->SetHttpStatus('423 Locked');
		}
	}

	protected function MKCOLWrapper()
	{
		$response = $this->response;
		$stat = $this->MKCOL();
		$response->SetHttpStatus($stat);
	}

	protected function GETWrapper()
	{
		/** @var CDavRequest $request */
		$request = $this->request;
		$response = $this->response;

		$arHttpRanges = null;
		$httpRange = $request->GetParameter("HTTP_RANGE");
		if (!is_null($httpRange))
		{
			if (preg_match('/bytes\s*=\s*(.+)/', $httpRange, $arMatches))
			{
				$arHttpRanges = array();
				$arRanges = explode(",", $arMatches[1]);
				foreach ($arRanges as $range)
				{
					list($start, $end) = explode("-", $range);
					$arHttpRanges[] = ($start === "") ? array("last" => $end) : array("start" => $start, "end" => $end);
				}
			}
		}

		$arResult = array();
		if (true === ($status = $this->GET($arResult)))
		{
			$status = "200 OK";
			$response->TurnOnBinaryOutput();

			if (!isset($arResult['mimetype']))
				$arResult['mimetype'] = "application/octet-stream";

			if ($arResult['mimetype'] == 'application/zip')
				ini_set('zlib.output_compression', 0);

			$response->AddHeader("Content-type: ".$arResult['mimetype']);

			if (isset($arResult['mtime']))
				$response->AddHeader("Last-modified:".gmdate("D, d M Y H:i:s ", $arResult['mtime'])."GMT");

			$response->AddHeader("Cache-Control: maxage=1");
			$response->AddHeader("Pragma: public");
			$response->AddHeader("ETag: ".($eTag = md5($arResult['id'].$arResult['name'].$arResult['mtime'])));

			if (isset($arResult['headers']))
			{
				foreach ($arResult['headers'] as $h)
					$response->AddHeader($h);
			}

			if (($rETag = $request->GetParameter('HTTP_IF_NONE_MATCH')) && trim($rETag, '"\'') == $eTag)
			{
				$response->SetHttpStatus('304 Not Modified');
				return;
			}
			elseif (isset($arResult['stream']))
			{
				if (!is_null($arHttpRanges) && (0 === fseek($arResult['stream'], 0, SEEK_SET)))
				{
					if (count($arHttpRanges) === 1)
					{
						$range = $arHttpRanges[0];

						if (isset($range['start']))
						{
							fseek($arResult['stream'], $range['start'], SEEK_SET);
							if (feof($arResult['stream']))
							{
								$response->SetHttpStatus("416 Requested range not satisfiable");
								return;
							}

							if ($range['end'])
							{
								$size = $range['end'] - $range['start'] + 1;
								$response->SetHttpStatus("206 partial");
								$response->AddHeader("Content-length: ".$size);
								$response->AddHeader("Content-range: ".$range["start"]."-".$range["end"]."/".(isset($arResult['size']) ? $arResult['size'] : "*"));
								while ($size && !feof($arResult['stream']))
								{
									$buffer = fread($arResult['stream'], 4096);
									$size -= strlen($buffer);
									$response->AddLine($buffer);
								}
							}
							else
							{
								$response->SetHttpStatus("206 partial");
								if (isset($arResult['size']))
								{
									$response->AddHeader("Content-length: ".($arResult['size'] - $range['start']));
									$response->AddHeader("Content-range: ".$range['start']."-".$range['end']."/".(isset($arResult['size']) ? $arResult['size'] : "*"));
								}
								while (!feof($arResult['stream']))
								{
									$buffer = fread($arResult['stream'], 4096);
									$response->AddLine($buffer);
								}
							}
						}
						else
						{
							$response->AddHeader("Content-length: ".$range['last']);
							fseek($arResult['stream'], -$range['last'], SEEK_END);
							while (!feof($arResult['stream']))
							{
								$buffer = fread($arResult['stream'], 4096);
								$response->AddLine($buffer);
							}
						}
					}
					else
					{
						$response->MultipartByteRangeHeader();
						foreach ($arHttpRanges as $range)
						{
							if (isset($range['start']))
							{
								$from = $range['start'];
								$to = !empty($range['end']) ? $range['end'] : $arResult['size'] - 1;
							}
							else
							{
								$from = $arResult['size'] - $range['last'] - 1;
								$to = $arResult['size'] - 1;
							}
							$total = isset($arResult['size']) ? $arResult['size'] : "*";
							$size = $to - $from + 1;
							$this->MultipartByteRangeHeader($arResult['mimetype'], $from, $to, $total);

							fseek($arResult['stream'], $from, SEEK_SET);
							while ($size && !feof($arResult['stream']))
							{
								$buffer = fread($arResult['stream'], 4096);
								$size -= strlen($buffer);
								$response->AddLine($buffer);
							}
						}
						$response->MultipartByteRangeHeader();
					}
				}
				else
				{
					if (isset($arResult['size']))
						$response->AddHeader("Content-length: ".$arResult['size']);

					while (!feof($arResult['stream']))
					{
						$buffer = fread($arResult['stream'], 4096);
						$response->AddLine($buffer);
					}

					return;
				}
			}
			elseif (isset($arResult['data']))
			{
				if (is_array($arResult['data']))
				{
				}
				else
				{
					$response->AddHeader("Content-length: ".strlen($arResult['data']));
					$response->AddLine($arResult['data']);
				}
			}
		}
		elseif (false === $status)
		{
			$response->SetHttpStatus("404 not found");
		}
		else
		{
			$response->SetHttpStatus($status);
		}
	}

	protected function HEADWrapper()
	{
		$request = $this->request;
		$response = $this->response;

		$status = false;

		if (method_exists($this, "HEAD"))
		{
			$status = $this->HEAD();
		}
		elseif (method_exists($this, "GET"))
		{
			ob_start();
			$arResult = array();
			$status = $this->GET($arResult);
			if (!isset($arResult['size']))
				$arResult['size'] = ob_get_length();
			ob_end_clean();
		}

		if (!isset($arResult['mimetype']))
			$arResult['mimetype'] = "application/octet-stream";

		$response->AddHeader("Content-type: ".$arResult["mimetype"]);

		if (isset($arResult['mtime']))
			$response->AddHeader("Last-modified:".gmdate("D, d M Y H:i:s ", $arResult['mtime'])."GMT");

		if (isset($arResult['size']))
			$response->AddHeader("Content-length: ".$arResult['size']);

		if ($status === true)
			$status = "200 OK";
		if ($status === false)
			$status = "404 Not found";

		$response->SetHttpStatus($status);
	}

	protected function POSTWrapper()
	{
		$request = $this->request;
		$response = $this->response;

		if (!method_exists($this, 'POST'))
		{
			$response->SetHttpStatus('405 Method not allowed');
			return;
		}

		$this->PUTWrapper('POST');
	}

	protected function CheckLockStatus($path, $exclusiveOnly = false)
	{
		if (method_exists($this, "CheckLock"))
		{
			$lock = $this->CheckLock($path);
			if (is_array($lock) && count($lock))
			{
				if ($this->request->GetParameter("HTTP_IF") === null || !strstr($this->request->GetParameter("HTTP_IF"), $lock["ID"]))
				{
					if (!$exclusiveOnly || ($lock["LOCK_SCOPE"] !== "shared"))
						return false;
				}
			}
		}
		return true;
	}

	protected function PUTWrapper($handler = "PUT")
	{
		/** @var CDavRequest $request */
		$request = $this->request;
		$response = $this->response;

		if (!$this->CheckLockStatus($request->GetPath()))
		{
			$response->SetHttpStatus("423 Locked");
			return;
		}

		$arContentParameters = $request->GetContentParameters();

		$errorMessage = "";
		if (!strncmp($arContentParameters["CONTENT_TYPE"], "multipart/", 10))
			$errorMessage = "The service does not support mulipart PUT requests";
		elseif (array_key_exists("CONTENT_ENCODING", $arContentParameters))
			$errorMessage = str_replace("#VAL#", $arContentParameters["CONTENT_ENCODING"], "The service does not support '#VAL#' content encoding");
		elseif (array_key_exists("HTTP_CONTENT_MD5", $arContentParameters))
			$errorMessage = "The service does not support content MD5 checksum verification";
		else
		{
			// RFC 2616 2.6: The recipient of the entity MUST NOT ignore any Content-* (e.g. Content-Range) headers that it
			// does not understand or implement and MUST return a 501 (Not Implemented) response in such cases.
			foreach ($arContentParameters as $key => $value)
			{
				if (!in_array($key, array('CONTENT_ENCODING', 'CONTENT_LANGUAGE', 'CONTENT_LENGTH', 'CONTENT_LOCATION', 'CONTENT_RANGE', 'CONTENT_TYPE', 'CONTENT_MD5')))
				{
					$errorMessage = str_replace("#VAL#", $value, "The service does not support '#VAL#'");
					break;
				}
			}
		}

		if (strlen($errorMessage) > 0)
		{
			$response->GenerateError("501 not implemented", $errorMessage);
			return;
		}

		if (array_key_exists("CONTENT_RANGE", $arContentParameters))	// RFC 2616 14.16
		{
			if (!preg_match('@bytes\s+(\d+)-(\d+)/((\d+)|\*)@', $arContentParameters["CONTENT_RANGE"], $matches))
			{
				$response->GenerateError("400 bad request", "The service does only support single byte ranges");
				return;
			}

			$range = array("START" => $matches[1], "END" => $matches[2]);
			if (is_numeric($matches[3]))
				$range["TOTAL_LENGTH"] = $matches[3];

			$arContentParameters["CONTENT_RANGE"] = $range;
		}

		$arResult = array();
		$stat = $this->$handler($arResult);

		if ($stat === false)
		{
			$stat = "403 Forbidden";
		}
		elseif (is_resource($stat) && get_resource_type($stat) == 'stream')
		{
			$inputStream = fopen('php://input', 'r');

			@set_time_limit(0);
			$stream = $stat;
			$stat = $arResult['new'] ? '201 Created' : '204 No Content';

			if (!empty($arContentParameters["CONTENT_RANGE"]))
			{
				if (0 == fseek($stream, $arContentParameters["CONTENT_RANGE"]['START'], SEEK_SET))
				{
					$length = $arContentParameters["CONTENT_RANGE"]['END'] - $arContentParameters["CONTENT_RANGE"]['START'] + 1;
					if (!fwrite($stream, fread($inputStream, $length)))
					{
						$stat = '403 Forbidden';
					}
				}
				else
				{
					$stat = '403 Forbidden';
				}
			}
			else
			{
				while (!feof($inputStream))
				{
					$xxx = fread($inputStream, 8192);
					if (false === fwrite($stream, $xxx))
					{
						$stat = '403 Forbidden';
						break;
					}
				}
			}
			fclose($stream);

			if (method_exists($this, 'PutCommit') && !$this->PutCommit($arResult))
			{
				$stat = '409 Conflict';
			}
		}

		$response->SetHttpStatus($stat);
		$response->AddHeader('Location: ' . ($request->GetParameter("HTTPS") === "on" ? "https" : "http").'://'.$request->GetParameter('HTTP_HOST').$request->getPath());
	}

	protected function DELETEWrapper()
	{
		/** @var CDavRequest $request */
		$request = $this->request;
		$response = $this->response;

		// check RFC 2518 Section 9.2, last paragraph
		$depth = $request->GetParameter("HTTP_DEPTH");
		if (!is_null($depth))
		{
			if ($depth != "infinity")
			{
				if (strpos(strtolower($request->GetParameter('HTTP_USER_AGENT')), 'webdrive') !== false)
				{
				}
				else
				{
					$response->SetHttpStatus('400 Bad Request');
					return;
				}
			}
		}

		if ($this->CheckLockStatus($request->GetPath()))
		{
			$status = $this->DELETE();
			$response->SetHttpStatus($status);
		}
		else
		{
			$response->SetHttpStatus("423 Locked");
		}
	}

	protected function COPYWrapper()
	{
		$this->CopyMove("Copy");
	}

	protected function MOVEWrapper()
	{
		/** @var CDavRequest $request */
		$request = $this->request;

		if ($this->CheckLockStatus($request->GetPath()))
			$this->CopyMove("Move");
		else
			$this->response->SetHttpStatus("423 Locked");
	}

	private function CopyMove($what)
	{
		/** @var CDavRequest $request */
		$request = $this->request;
		$response = $this->response;

		$httpDestination = $request->GetParameter("HTTP_DESTINATION");
		$pu = parse_url($httpDestination);

		$path = urldecode($pu['path']);
		$httpHost = $pu['host'];
		if (isset($pu['port']) && $pu['port'] != 80 && $pu['port'] != 443)
			$httpHost .= ":".$pu['port'];

		$httpHeaderHost = preg_replace("/:(80|443)$/", "", $request->GetParameter("HTTP_HOST"));

		if ($httpHost == $httpHeaderHost /*&& !strncmp($request->GetParameter("SCRIPT_NAME"), $path, strlen($request->GetParameter("SCRIPT_NAME")))*/)
		{
			$dest = $path;
			if (!strncmp($request->GetParameter("SCRIPT_NAME"), $path, strlen($request->GetParameter("SCRIPT_NAME"))))
				$dest = substr($path, strlen($request->GetParameter("SCRIPT_NAME")));
			if (!$this->CheckLockStatus($dest))
			{
				$response->SetHttpStatus("423 Locked");
				return;
			}
		}
		else
		{
			$response->SetHttpStatus("412 precondition failed");
			return;
		}

		// RFC 2518 Sections 9.6, 8.8.4, 8.9.3
		$httpOverwrite = $request->GetParameter("HTTP_OVERWRITE");
		if (!is_null($httpOverwrite)) 
			$overwrite = ($httpOverwrite == "T");
		else
			$overwrite = true;

		$stat = $this->$what($dest, $httpDestination, $overwrite);
		$response->SetHttpStatus($stat);
	}

	protected function getNewLockToken()
	{
		return uniqid("bxlocktoken:", true);
	}

	protected function LOCKWrapper()
	{
		/** @var CDavRequest $request */
		$request = $this->request;
		$response = $this->response;

		$httpTimeout = $request->GetParameter("HTTP_TIMEOUT");
		if (!is_null($httpTimeout))
		{
			$httpTimeout = explode(",", $httpTimeout);
			$httpTimeout = $httpTimeout[0];
		}

		$contentLength = $request->GetParameter('CONTENT_LENGTH');
		$httpIf = $request->GetParameter('HTTP_IF');
		if (empty($contentLength) && !empty($httpIf))
		{
			if (!$this->CheckLockStatus($request->GetPath()))
			{
				$response->SetHttpStatus("423 Locked");
				return;
			}

			$locktoken = substr($httpIf, 2, -2);
			$updateLock = true;
			$owner = "id:".$request->GetPrincipal()->Id();
			$scope = "exclusive";
			$type = "write";
		}
		else
		{
			try
			{
				$requestDocument = $request->GetXmlDocument();
			}
			catch (CDavXMLParsingException $e)
			{
				$response->GenerateError("400 Error", $e->getMessage());
				return;
			}
			catch (Exception $e)
			{
				$response->SetHttpStatus("400 Error");
				return;
			}

			$lockscopes = $requestDocument->GetPath('/*/DAV::lockscope/*');
			$lockscope = (is_array($lockscopes) && !empty($lockscopes)) ? $lockscopes[0]->GetTag() : "exclusive";

			if (!$this->CheckLockStatus($request->GetPath(), $lockscope !== "shared"))
			{
				$response->SetHttpStatus("423 Locked");
				return;
			}

			$locktoken = $this->getNewLockToken();
			$updateLock = false;

			$owners = $requestDocument->GetPath('/*/DAV::owner/*');
			$owner = is_array($owners) && !empty($owners) ? $owners[0]->GetContent() : "";
			$scope = $lockscope;
			$types = $requestDocument->GetPath('/*/DAV::locktype/*');
			$type = (is_array($types) && !empty($types)) ? $types[0]->GetTag() : "write";
		}

		$stat = $this->LOCK($locktoken, $httpTimeout, $owner, $scope, $type, $updateLock);

		if (is_bool($stat))
			$httpStat = $stat ? "200 OK" : "423 Locked";
		else
			$httpStat = $stat;

		$response->SetHttpStatus($httpStat);

		if (substr($httpStat, 0, 1) == 2)
		{
			// 2xx states are ok
			if (!is_null($httpTimeout))
			{
//				if (is_numeric($httpTimeout))
//				{
					// more than a million is considered an absolute timestamp less is more likely a relative value
					if ($httpTimeout > 1000000)
						$timeout = "Second-".($httpTimeout - time());
					else
						$timeout = "Second-".$httpTimeout;
//				}
//				else
//				{
//					$timeout = $httpTimeout;
//				}
			}
			else
			{
				$timeout = "Infinite";
			}

			$response->AddHeader('Content-Type: text/xml; charset="utf-8"');
			$response->AddHeader("Lock-Token: <".$locktoken.">");

			$response->AddLine("<D:prop xmlns:D=\"DAV:\">");
			$response->AddLine(" <D:lockdiscovery>");
			$response->AddLine("  <D:activelock>");
			$response->AddLine("   <D:lockscope><D:".$scope."/></D:lockscope>");
			$response->AddLine("   <D:locktype><D:".$type."/></D:locktype>");
			$response->AddLine("   <D:depth>".$request->GetDepth()."</D:depth>");
			$response->AddLine("   <D:owner>".$owner."</D:owner>");
			$response->AddLine("   <D:timeout>".$timeout."</D:timeout>");
			$response->AddLine("   <D:locktoken><D:href>".$locktoken."</D:href></D:locktoken>");
			$response->AddLine("  </D:activelock>");
			$response->AddLine(" </D:lockdiscovery>");
			$response->AddLine("</D:prop>");
		}
	}

	protected function UNLOCKWrapper()
	{
		$request = $this->request;
		$response = $this->response;

		$httpLocktoken = $request->GetParameter('HTTP_LOCK_TOKEN');
		$httpLocktoken = substr(trim($httpLocktoken), 1, -1);

		$stat = $this->UNLOCK($httpLocktoken);

		$response->SetHttpStatus($stat);
		$response->AddHeader("Content-length: 0");
	}

	protected function ACLWrapper()
	{
		$request = $this->request;
		$response = $this->response;

		$arResult = array();
		$status = $this->ACL($arResult);

		$response->SetHttpStatus($status);

		$size = 0;
		if (isset($arResult['errors']) && is_array($arResult['errors']) && count($arResult['errors']))
		{
			$response->AddHeader('Content-Type: text/xml; charset="utf-8"');

			$content = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
			$content .= "<D:error xmlns:D=\"DAV:\"> \n";
			foreach ($arResult['errors'] as $e)
				$content .= "<D:".$e."/>\n";
			$content .=  "</D:error>\n";

			$size = strlen($content);
			$response->AddLine($content);
		}

		$response->AddHeader("Content-length: ".$size);
	}

	public function UrlEncode($url)
	{
		if ($this->request->GetAgent() == 'neon')
		{
			return strtr(rawurlencode($url), array(
				'%2F' => '/',
				'%3A' => ':',
			));
		}

		return strtr($url, array(
			' ' => '%20',
			'&'	=> '%26',
			'<'	=> '%3C',
			'>'	=> '%3E',
			'+'	=> '%2B',
		));
	}
}
?>
