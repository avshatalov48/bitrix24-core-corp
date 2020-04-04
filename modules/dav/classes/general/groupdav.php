<?
IncludeModuleLangFile(__FILE__);

class CDavGroupDav
	extends CDavWebDav
{
	const DAV = 'DAV:';			// DAV namespace
	const GROUPDAV = 'http://groupdav.org/';		// GroupDAV namespace
	const CALDAV = 'urn:ietf:params:xml:ns:caldav';		// CalDAV namespace
	const CARDDAV = 'urn:ietf:params:xml:ns:carddav';		// CardDAV namespace
	const CALENDARSERVER = 'http://calendarserver.org/ns/';		// Calendarserver namespace (eg. for ctag)
	const ICAL = 'http://apple.com/ns/ical/';		// Apple iCal namespace (eg. for calendar color)

	private $arApplications = array('calendar', 'addressbook', 'principals');

	private $principalURL;

	public function __construct($request)
	{
		parent::__construct($request);

		$this->SetDavPoweredBy("Bitrix CalDAV/CardDAV/GroupDAV server");
	}

	public function CheckAuth($authType, $phpAuthUser, $phpAuthPw)
	{
		global $APPLICATION, $USER;

		if (strlen($phpAuthUser) > 0 && strlen($phpAuthPw) > 0)
		{
			$arAuthResult = $USER->Login($phpAuthUser, $phpAuthPw, "N");
			$APPLICATION->arAuthResult = $arAuthResult;
		}

		return $USER->IsAuthorized();
	}

	protected function OPTIONS(&$arDav, &$arAllowableMethods)
	{
		$arRequestPath = self::ParsePath($this->request->GetPath());

		switch ($arRequestPath["application"])
		{
			case 'calendar':
				if (!in_array(2, $arDav))
					$arDav[] = 2;
				$arDav[] = 'access-control';
				$arDav[] = 'calendar-access';
				break;

			case 'addressbook':
				if (!in_array(2, $arDav))
					$arDav[] = 2;
				$arDav[] = 'access-control';
				$arDav[] = 'addressbook';

if (isset($arAllowableMethods["PUT"])) unset($arAllowableMethods["PUT"]);
if (isset($arAllowableMethods["POST"])) unset($arAllowableMethods["POST"]);
if (isset($arAllowableMethods["DELETE"])) unset($arAllowableMethods["DELETE"]);

				break;

			default:
				if (!in_array(2, $arDav))
					$arDav[] = 2;
				$arDav[] = 'access-control';
				$arDav[] = 'calendar-access';
				$arDav[] = 'addressbook';
		}
	}

	protected function PROPFIND(&$arResources, $method = 'PROPFIND')
	{
		$arResources = array();

		$arRequestPath = self::ParsePath($this->request->GetPath());
		$accountPrefixPath = CDav::CheckIfRightSlashAdded($arRequestPath["accountPrefix"]);

		$requestDocument = $this->request->GetXmlDocument();

		if ($this->request->getAgent() == 'lightning' && empty($arRequestPath['path']))
			return false;

		$application = $arRequestPath["application"];
		if (!$application)		// If it's the user root folder (it contains applications)
		{
			if (!$arRequestPath["account"])
				$arRequestPath["account"] = array("user", $this->request->GetPrincipal()->Id());

			$arAccount = CDavAccount::GetAccountById($arRequestPath["account"]);

			$resource = new CDavResource($accountPrefixPath);

			$resource->AddProperty('resourcetype', array('collection', ''));

			$this->GetCollectionProperties(
				$resource,
				$arRequestPath["site"],
				$arRequestPath["account"],
				$arRequestPath["path"]
			);

			foreach ($this->arApplications as $app)
			{
				if (($handler = $this->GetApplicationHandler($app)) && method_exists($handler, 'GetCollectionProperties'))
					$handler->GetCollectionProperties($resource, $arRequestPath["site"], $arRequestPath["account"], null, $arRequestPath["path"], 0);
			}

			$arResources[] = $resource;

			if ($this->request->GetDepth())
			{
				if (strlen($accountPrefixPath) == 1)
				{
					$resource = new CDavResource('/principals/');
					$resource->AddProperty('displayname', GetMessage("DAV_PRINCIPALS"));
					$resource->AddProperty('resourcetype', array('principals', ''));
					$resource->AddProperty('current-user-principal', array('href', $this->request->GetPrincipalUrl()));
					$arResources[] = $resource;
				}

				foreach ($this->arApplications as $app)
				{
					if (($handler = $this->GetApplicationHandler($app)) && method_exists($handler, 'GetHomeCollectionUrl'))
						$resourcePath = $handler->GetHomeCollectionUrl($arRequestPath["site"], $arRequestPath["account"], $arRequestPath["path"]);
					else
						$resourcePath = $accountPrefixPath.$app;

					$resource = new CDavResource($resourcePath);

					$this->GetCollectionProperties(
						$resource,
						$arRequestPath["site"],
						$arRequestPath["account"],
						$arRequestPath["path"]
					);

					if (method_exists($handler, 'GetCollectionProperties'))
					{
						$handler->GetCollectionProperties(
							$resource,
							$arRequestPath["site"],
							$arRequestPath["account"],
							$app,
							$arRequestPath["path"],
							0
						);
					}

					$resource->AddProperty('getetag', $app);

					$arResources[] = $resource;
				}
			}

			return true;
		}

		if ($handler = $this->GetApplicationHandler($application))
		{
			if ($application != "principals" && $method != 'REPORT' && $arRequestPath["id"] == null)
			{
				$resource = new CDavResource($this->request->GetPath());

				$this->GetCollectionProperties(
					$resource,
					$arRequestPath["site"],
					$arRequestPath["account"],
					$arRequestPath["path"]
				);

				if (method_exists($handler, 'GetCollectionProperties'))
				{
					$handler->GetCollectionProperties(
						$resource,
						$arRequestPath["site"],
						$arRequestPath["account"],
						$application,
						$arRequestPath["path"],
						($application == 'addressbook' && $this->request->GetAgent() == 'kde') ? BX_GW_SKIP_EXTRA_TYPES : 0
					);
				}

				$arResources[] = $resource;

				if ($this->request->GetDepth() == 0)
					return true;
			}

			return $handler->Propfind($arResources, $arRequestPath["site"], $arRequestPath["account"], $arRequestPath["path"], $arRequestPath["id"]);
		}

		return '501 Not Implemented';
	}

	protected function REPORT(&$arResources)
	{
		return $this->PROPFIND($arResources, 'REPORT');
	}

	protected function REPORTWrapper()
	{
		parent::PROPFINDWrapper('REPORT');
	}

	/*
	protected function MKCALENDAR()
	{
		return '501 Not Implemented';
	}

	protected function MKCALENDARWrapper()
	{
		$stat = $this->MKCALENDAR();
		$response->SetHttpStatus($stat);
	}
	*/

	protected function GET(&$arResult)
	{
		$request = $this->request;
		$response = $this->response;

		$arRequestPath = self::ParsePath($request->GetPath());

		if (!$arRequestPath["id"] || !$arRequestPath["account"] || !in_array($arRequestPath["application"], array('addressbook', 'calendar', 'infolog')))
		{
			$arResources = array();

			$retVal = $this->PROPFIND($arResources);
			if ($retVal !== true)
				return $retVal;

			$response->TurnOnHtmlOutput();

			$response->AddHeader('Content-type: text/html; charset=utf-8');
			$response->AddLine("<html>\n<head>\n\t<title>".$this->GetDavPoweredBy().' '.htmlspecialcharsbx($request->GetPath())."</title>");
			$response->AddLine("\t<meta http-equiv='content-type' content='text/html; charset=utf-8' />");
			$response->AddLine("\t<style type='text/css'>\n.th { background-color: #e0e0e0; }\n.row1 { background-color: #F1F1F1; }\n".
			".row2 { background-color: #ffffff; }\ntd { padding-left: 5px; }\nth { padding-left: 5px; text-align: left; }\n\t</style>");
			$response->AddLine("</head>\n<body>");

			$path = '/bitrix/groupdav.php';
			$s = '';
			$arPath = explode('/', trim($request->GetPath(), "/"));
			foreach ($arPath as $n => $name)
			{
				$path .= '/'.$name;
				$s .= "<a href=\"".htmlspecialcharsbx($path)."\">".htmlspecialcharsbx($name.'/')."</a>";
			}
			$response->AddLine('<h1>'.$this->GetDavPoweredBy().' '.$s."</h1>");

			$n = 0;
			foreach ($arResources as $resource)
			{
				$arResourceProps = $resource->GetProperties();

				if (!isset($collectionProps))
				{
					$collectionProps = $this->ConvertPropertiesToArray($arResourceProps);
					$response->AddLine("<h3>Collection listing: %s</h3>", htmlspecialcharsbx($collectionProps['DAV:displayname']));
					continue;
				}

				if (!$n++)
					$response->AddLine("<table>\n\t<tr class='th'><th>#</th><th>Name</th><th>Size</th><th>Last modified</th><th>ETag</th><th>Content type</th><th>Resource type</th></tr>");

				$props = $this->ConvertPropertiesToArray($arResourceProps);

				$class = ($class == 'row1' ? 'row2' : 'row1');

				if (substr($resource->GetPath(), -1) == '/')
					$name = basename(substr($resource->GetPath(), 0, -1)).'/';
				else
					$name = basename($resource->GetPath());

				$response->AddLine("\t<tr class='$class'>\n\t\t<td>%s</td>\n\t\t<td><a href=\"%s\">%s</td>", $n, htmlspecialcharsbx('/bitrix/groupdav.php'.$resource->GetPath()), htmlspecialcharsbx($name));
				$response->AddLine("\t\t<td>%s</td>", $props['DAV:getcontentlength']);
				$response->AddLine("\t\t<td>%s</td>", (!empty($props['DAV:getlastmodified']) ? date('Y-m-d H:i:s', $props['DAV:getlastmodified']) : ''));
				$response->AddLine("\t\t<td>%s</td>", $props['DAV:getetag']);
				$response->AddLine("\t\t<td>%s</td>", htmlspecialcharsbx($props['DAV:getcontenttype']));
				$response->AddLine("\t\t<td>%s</td>\n\t</tr>", $this->RenderPropertyValue($props['DAV:resourcetype']));
			}

			if (!$n)
				$response->AddLine("<p>Collection empty.</p>");
			else
				$response->AddLine("</table>");

			$response->AddLine("<h3>Properties</h3>");
			$response->AddLine("<table>\n\t<tr class='th'><th>Namespace</th><th>Name</th><th>Value</th></tr>");
			foreach ($collectionProps as $name => $value)
			{
				$class = ($class == 'row1' ? 'row2' : 'row1');
				$ns = explode(':', $name);
				$name = array_pop($ns);
				$ns = implode(':', $ns);
				$response->AddLine("\t<tr class='%s'>\n\t\t<td>%s</td><td>%s</td>", $class, htmlspecialcharsbx($ns), htmlspecialcharsbx($name));
				$response->AddLine("\t\t<td>%s</td>\n\t</tr>", $this->RenderPropertyValue($value));
			}
			$response->AddLine("</table>");
			$response->AddLine("</body>\n</html>");

			return "200 OK";
		}

		if ($handler = $this->GetApplicationHandler($arRequestPath["application"]))
			return $handler->Get($arResult, $arRequestPath["id"], $arRequestPath["site"], $arRequestPath["account"], $arRequestPath["path"]);

		return '501 Not Implemented';
	}

	private function RenderPropertyValue($value)
	{
		if (is_array($value))
		{
			$request = $this->request;
			$response = $this->response;

			if (isset($value[0]['ns']))
				$value = CDavResource::EncodeHierarchicalProp($value, null, $xmlnsDefs = null, $xmlnsHash = null, $response, $request);

			$value = htmlspecialcharsbx(CDav::ToString($value));
		}
		elseif (preg_match('/\<(D:)?href\>[^<]+\<\/(D:)?href\>/i', $value))
		{
			$value = preg_replace('/\<(D:)?href\>([^<]+)\<\/(D:)?href\>/i','&lt;\\1href&gt;<a href="\\2">\\2</a>&lt;/\\3href&gt;<br />', $value);
		}
		else
		{
			$value = htmlspecialcharsbx($value);
		}
		return $value;
	}

	private function ConvertPropertiesToArray(array $props)
	{
		$request = $this->request;
		$response = $this->response;

		$arr = array();
		foreach ($props as $prop)
		{
			switch ($prop['xmlns'])
			{
				case 'DAV:';
					$xmlns = 'DAV';
					break;
				case self::CALDAV:
					$xmlns = 'CalDAV';
					break;
				case self::CARDDAV:
					$xmlns = 'CardDAV';
					break;
				case self::GROUPDAV:
					$xmlns = 'GroupDAV';
					break;
				default:
					$xmlns = $prop['xmlns'];
			}

			$xmlnsDefs = '';
			$xmlnsHash = array($prop['xmlns'] => $xmlns, 'DAV:' => 'D');
			$arr[$xmlns.':'.$prop['tagname']] = is_array($prop['content']) ? CDavResource::EncodeHierarchicalProp($prop['content'], $prop['xmlns'], $xmlnsDefs, $xmlnsHash, $response, $request) : $prop['content'];
		}

		return $arr;
	}

	protected function POST()
	{
		return '501 Not Implemented';
	}

	protected function PUT(&$arResult)
	{
		$arRequestPath = self::ParsePath($this->request->GetPath());
		if (!$arRequestPath["id"] || !$arRequestPath["account"] || !in_array($arRequestPath["application"], array('addressbook', 'calendar', 'infolog', 'principals')))
			return '404 Not Found';

		if ($handler = $this->GetApplicationHandler($arRequestPath["application"]))
		{
			$status = $handler->Put($arRequestPath["id"], $arRequestPath["site"], $arRequestPath["account"], $arRequestPath["path"]);

			if (is_bool($status))
				$status = $status ? '204 No Content' : '400 Bad Request';

			return $status;
		}

		return '501 Not Implemented';
	}

	protected function DELETE()
	{
		$arRequestPath = self::ParsePath($this->request->GetPath());
		if (!$arRequestPath["id"] || !$arRequestPath["account"] || !in_array($arRequestPath["application"], array('addressbook', 'calendar', 'infolog', 'principals')))
			return '404 Not Found';

		if ($handler = $this->GetApplicationHandler($arRequestPath["application"]))
		{
			$status = $handler->Delete($arRequestPath["id"], $arRequestPath["site"], $arRequestPath["account"], $arRequestPath["path"]);

			if (is_bool($status))
				$status = $status ? '204 No Content' : '400 Something went wrong';

			return $status;
		}

		return '501 Not Implemented';
	}

	protected function MKCOL()
	{
		return '501 Not Implemented';
	}

	protected function MOVE($dest, $httpDestination, $overwrite)
	{
		return '501 Not Implemented';
	}

	protected function COPY($dest, $httpDestination, $overwrite)
	{
		return '501 Not Implemented';
	}

	protected function LOCK($locktoken, &$httpTimeout, &$owner, &$scope, &$type, $update)
	{
		$arRequestPath = self::ParsePath($this->request->GetPath());
		$path = CDavVirtualFileSystem::GetLockPath($arRequestPath["application"], $arRequestPath["id"]);

		$handler = $this->GetApplicationHandler($arRequestPath["application"]);

		if (!$arRequestPath["id"] || $this->request->GetDepth() || !$handler->CheckPrivilegesByPath("DAV:write", $this->request->GetPrincipal(), $arRequestPath["site"], $arRequestPath["account"], $arRequestPath["path"]))
			return '409 Conflict';

		$httpTimeout = time() + 300;

		if (!$update)
		{
			$ret = CDavVirtualFileSystem::Lock($path, $locktoken, $httpTimeout, $owner, $scope, $type);
			return $ret ? '200 OK' : '409 Conflict';
		}

		$ret = CDavVirtualFileSystem::UpdateLock($path, $locktoken, $httpTimeout, $owner, $scope, $type);
		return $ret;
	}

	protected function UNLOCK($httpLocktoken)
	{
		$arRequestPath = self::ParsePath($this->request->GetPath());
		$path = CDavVirtualFileSystem::GetLockPath($arRequestPath["application"], $arRequestPath["id"]);

		return (CDavVirtualFileSystem::Unlock($path, $httpLocktoken) ? '204 No Content' : '409 Conflict');
	}

	protected function ACL(&$arResult)
	{
		$arRequestPath = self::ParsePath($this->request->GetPath());

		$arResult['errors'] = array();
		switch ($arRequestPath["application"])
		{
			case 'calendar':
			case 'addressbook':
			case 'infolog':
				$status = '200 OK';
				break;
			default:
				$arResult['errors'][] = 'no-inherited-ace-conflict';
				$status = '403 Forbidden';
		}

		return $status;
	}

	private function GetApplicationHandler($app)
	{
		return CDavGroupdavHandler::GetApplicationHandler($this, $app);
	}

	// Get the properties of a collection
	private function GetCollectionProperties(&$resource, $siteId, $account = null, $arPath = null)
	{
		$resource->AddProperty('current-user-principal', array('href', $this->request->GetPrincipalUrl()));
		$resource->AddProperty('principal-collection-set', array('href', $this->request->GetBaseUri().'/principals/'));
		$resource->AddProperty('principal-URL', array('href', $this->request->GetPrincipalUrl()));

		$arAccount = null;
		if ($account != null)
		{
			$arAccount = CDavAccount::GetAccountById($account);

			$resource->AddProperty('owner', array('href', $this->request->GetBaseUri().'/principals/'.$arAccount["TYPE"].'/'.$arAccount["CODE"].'/'));
			$resource->AddProperty('alternate-URI-set', array('href', 'MAILTO:'.$arAccount['EMAIL']));
			$resource->AddProperty('email-address-set', array('email-address', $arAccount['EMAIL'], self::CALENDARSERVER), self::CALENDARSERVER);
		}

		$resource->AddProperty('getetag', 'no-etag');
		$resource->AddProperty('displayname', $arAccount != null ? $arAccount["NAME"] : "Company");
	}

	protected function CheckLock($path)
	{
		$arRequestPath = self::ParsePath($path);
		$path = CDavVirtualFileSystem::GetLockPath($arRequestPath["application"], $arRequestPath["id"]);
		return CDavVirtualFileSystem::CheckLock($path);
	}

	private function ParsePath($path)
	{
		$path = trim($path, "/");
		if (strlen($path) <= 0)
		{
			$siteId = $this->request->GetSiteId();
			$this->response->SetEncoding($siteId);
			return array(
				"site" => $siteId,
				"account" => null,
				"accountPrefix" => "/".$siteId,
				"application" => null,
				"path" => null,
				"id" => null
			);
		}

		$arParts = explode('/', trim($path, "/"));

		//	/ru/admin/calendar/25/1.ics
		//	/ru/group-7/calendar/43/4.ics
		//	/ru/calendar/12/48/3.ics

		$part = array_shift($arParts);

		$arSite = null;
		try
		{
			$dbSite = CSite::GetList($o, $b, array("LID" => $part, "ACTIVE" => "Y"));
			if (!($arSite = $dbSite->Fetch()))
				$arSite = null;
		}
		catch (Exception $e)
		{
			$arSite = null;
		}

		$site = null;
		if (!is_null($arSite))
		{
			$site = $arSite["ID"];
			$part = array_shift($arParts);
		}
		if (is_null($site))
			$site = $this->request->GetSiteId();

		$this->response->SetEncoding($site);

		try
		{
			$arAccount = CDavAccount::GetAccountByName($part);
			if (!$arAccount)
				$arAccount = CDavAccount::GetAccountByName(urldecode($part));
		}
		catch (Exception $e)
		{
			$arAccount = null;
		}

		$account = null;
		$accountPrefix = "/".$site;
		if (!is_null($arAccount))
		{
			$account = array($arAccount["TYPE"], $arAccount["ID"]);
			$accountPrefix .= '/'.$part;
			$part = array_shift($arParts);
		}

		$application = $part;

		$arPath = array();
		$id = null;

		while (count($arParts) > 0)
		{
			$part = array_shift($arParts);
			if (count($arParts) > 0 || (strcasecmp(".ics", substr($part, -4)) && strcasecmp(".vcf", substr($part, -4))))
				$arPath[] = $part;
			else
				$id = substr($part, 0, -4);
		}

		return array(
			"site" => $site,
			"account" => $account,
			"accountPrefix" => $accountPrefix,
			"application" => $application,
			"path" => $arPath,
			"id" => $id
		);
	}

}
?>