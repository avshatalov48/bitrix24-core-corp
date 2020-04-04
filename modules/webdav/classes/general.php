<?php
##############################################
# Bitrix Site Manager WebDav				 #
# Copyright (c) 2002-2008 Bitrix			 #
# http://www.bitrixsoft.com					 #
# mailto:admin@bitrixsoft.com				 #
##############################################
IncludeModuleLangFile(__FILE__);
include_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/xml.php');

class CWebDavBase
{
	const TRASH = "TRASH";
	const DROPPED = "DROPPED";
	const SAVED = "SAVED";
	const OLD_DROPPED = "OLD_DROPPED";

	const MIME_GROUP_OFFICE = 'OFFICE';
	const MIME_GROUP_OPEN_OFFICE = 'OPEN_OFFICE';
	const MIME_GROUP_ARCHIVE = 'ARCHIVE';
	const MIME_GROUP_ONLY_LOADING = 'ONLY_LOADING';
	const MIME_GROUP_IMAGE = 'IMAGE';

	var $base_url;
	var $base_url_full; 

	var $_path;
	var $multipart_separator = false;
	var $preg_modif = "i";
	var $uri = '';
	var $http_method;
	var $http_user_agent = "undefined";
	
	var $allow = array(
		"POST"		=> array("rights" => "U", "min_rights" => "U"),
		"OPTIONS"	=> array("rights" => "A", "min_rights" => "A"),
		"PROPFIND"	=> array("rights" => "R", "min_rights" => "R"),
		"GET"		=> array("rights" => "R", "min_rights" => "R"),
		"PUT"		=> array("rights" => "U", "min_rights" => "U"),
		"MKCOL"		=> array("rights" => "U", "min_rights" => "U"),
		"DELETE"	=> array("rights" => "W", "min_rights" => "U"),
		"UNDELETE"	=> array("rights" => "W", "min_rights" => "W"),
		"MOVE"		=> array("rights" => "W", "min_rights" => "U"),
		"PROPPATCH"	=> array("rights" => "U", "min_rights" => "U"),
		"HEAD"		=> array("rights" => "R", "min_rights" => "R"),
		"LOCK"		=> array("rights" => "U", "min_rights" => "U"),
		"UNLOCK"	=> array("rights" => "U", "min_rights" => "U"),
	);
	static $methods = array(
		"POST", "OPTIONS", "PROPFIND", "GET", "PUT", "MKCOL", "DELETE", 
		"UNDELETE", "MOVE", "PROPPATCH", "HEAD", "LOCK", "UNLOCK",
	);
	static $levels = array(1, 2);

	var $arParams = array();
	
	var $Type = "undefined"; 

	var $arRootSection = false;

	var $permission = "D";
	
	var $workflow = false;
	
	var $wfParams = array();
	
	var $CACHE = array();
	
	var $USER = array();
	
	var $meta_names = array();
	var $meta_state = null;
	var $events_enabled = true;

	var $arError = array();

	//new
	static $io = null;

	protected static $foldersMetaData = null;

	function OnBeforeProlog()
	{
		global $USER, $APPLICATION;
		
		if (isset($_SERVER["PHP_AUTH_USER"]) &&
			(!defined("NOT_CHECK_PERMISSIONS") || NOT_CHECK_PERMISSIONS!==true) &&
			(CWebDavBase::IsDavHeaders("check_all") ||
			!$USER->IsAuthorized()))
		{
			if (strlen($_SERVER["PHP_AUTH_USER"]) > 0 and
				strlen($_SERVER["PHP_AUTH_PW"]) > 0)
			{
				if (strpos($_SERVER["PHP_AUTH_USER"], $_SERVER['HTTP_HOST']."\\") === 0)
				{
					$_SERVER["PHP_AUTH_USER"] = str_replace($_SERVER['HTTP_HOST']."\\", "", $_SERVER["PHP_AUTH_USER"]);
				}
				elseif (strpos($_SERVER["PHP_AUTH_USER"], $_SERVER['SERVER_NAME']."\\") === 0)
				{
					$_SERVER["PHP_AUTH_USER"] = str_replace($_SERVER['SERVER_NAME']."\\", "", $_SERVER["PHP_AUTH_USER"]);
				}
				$arAuthResult = $USER->Login($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"], "N");
				$APPLICATION->arAuthResult = $arAuthResult;
			}
		}

		if (
			($_SERVER['REQUEST_METHOD']=='OPTIONS' || $_SERVER['REQUEST_METHOD']=='PROPFIND') &&
			(
				(
					strlen($_SERVER["REAL_FILE_PATH"])<=0 &&
					substr($_SERVER['REQUEST_URI'], -1, 1)=='/'
				) || (
					strpos($_SERVER['REQUEST_URI'], 'personal')!==false &&
					strlen($_SERVER["REAL_FILE_PATH"])<=0 &&
					!file_exists($_SERVER['DOCUMENT_ROOT'].$_SERVER['REQUEST_URI'])
				) // windows scans all the path up to the root, fails if 404, and we have it in /company/personal/...
			)
		)
		{
			$res = CUrlRewriter::GetList(Array("QUERY"=>$_SERVER['REQUEST_URI']));
			$good_res = true;
			$file_path = "";
			foreach($res as $res_detail)
			{
				if(strpos($res_detail["ID"], "webdav")!==false || strpos($res_detail["ID"], "socialnetwork")!==false)
				{
					$good_res = (!$USER->IsAuthorized()/* && $APPLICATION->GetFileAccessPermission(Array(SITE_ID, $res_detail["PATH"]), Array(2)) < "R"*/);
					break;
				}
			}

			if($good_res)
			{
				header("MS-Author-Via: DAV");
				if ( ( strpos($_SERVER['HTTP_USER_AGENT'], "Microsoft-WebDAV-MiniRedir") !== false ) && // for office 2007, windows xp
					($_SERVER['REQUEST_METHOD'] == "OPTIONS") ) {
						CWebDavBase::base_OPTIONS();
						die();
				}

				if($_SERVER['REQUEST_METHOD']!='PROPFIND')
				{
					if(!$USER->IsAuthorized())
					{
						CWebDavBase::SetAuthHeader();
						die();
					}
					CWebDavBase::base_OPTIONS();
					die();
				}

				if($_SERVER['REQUEST_METHOD']=='PROPFIND')
				{
					if(!$USER->IsAuthorized())
					{
						CWebDavBase::SetAuthHeader();
						die();
					}

				CWebDavBase::SetStatus('207 Multi-Status');
echo '<?xml version="1.0" encoding="utf-8" ?>
<D:multistatus xmlns:D="DAV:" xmlns:Office="urn:schemas-microsoft-com:office:office" xmlns:Repl="http://schemas.microsoft.com/repl/" xmlns:Z="urn:schemas-microsoft-com:">
<D:response>
	<D:href>http://'.htmlspecialcharsbx($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']).'</D:href>
	<D:propstat>
		<D:prop>
			<D:displayname></D:displayname>
			<D:lockdiscovery/><D:supportedlock/>
			<D:isFolder>t</D:isFolder>
			<D:iscollection>1</D:iscollection>
			<D:ishidden>0</D:ishidden>
			<D:getcontenttype>application/octet-stream</D:getcontenttype>
			<D:getcontentlength>0</D:getcontentlength>
			<D:resourcetype><D:collection/></D:resourcetype>
			<Repl:authoritative-directory>t</Repl:authoritative-directory>
			<D:getlastmodified>2008-10-29T13:58:59Z</D:getlastmodified>
			<D:creationdate>2008-10-29T13:58:59Z</D:creationdate>
			<Repl:repl-uid>rid:{D77F5F6A-44A9-4015-AB49-4D3A439808C1}</Repl:repl-uid>
			<Repl:resourcetag>rt:D77F5F6A-44A9-4015-AB49-4D3A439808C1@00000000000</Repl:resourcetag>
			<D:getetag>&quot;{D77F5F6A-44A9-4015-AB49-4D3A439808C1},0&quot;</D:getetag>
		</D:prop>
		<D:status>HTTP/1.1 200 OK</D:status>
	</D:propstat>
</D:response>
</D:multistatus>';
					die();
				}
			}
		}
		elseif (CWebDavBase::IsDavHeaders("check_all"))
		{
			if (!$USER->IsAuthorized())
			{
				$res = CUrlRewriter::GetList(Array("QUERY"=>$_SERVER['REQUEST_URI']));
				$good_res = true;
				$file_path = "";
				foreach($res as $res_detail)
				{
					if(strpos($res_detail["ID"], "webdav")!==false || strpos($res_detail["ID"], "socialnetwork")!==false)
					{
						$good_res = (!$USER->IsAuthorized()/* && $APPLICATION->GetFileAccessPermission(Array(SITE_ID, $res_detail["PATH"]), Array(2)) < "R"*/);
						break;
					}
				}
				if ($good_res)
				{
					CWebDavBase::SetAuthHeader();
					die();
				}
			}
			return true;
		}
	}

	static function GetIP()
	{
		$result = "";

		if (getenv("HTTP_CLIENT_IP")
			&& strtolower(getenv("HTTP_CLIENT_IP")) !== "unknown")
				$result = getenv("HTTP_CLIENT_IP");

		elseif (getenv("HTTP_X_FORWARDED_FOR")
			&& strtolower(getenv("HTTP_X_FORWARDED_FOR")) !==  "unknown")
			$result = getenv("HTTP_X_FORWARDED_FOR");

		elseif (getenv("REMOTE_ADDR"
			&& strtolower(getenv("REMOTE_ADDR")) !==  "unknown"))
			$result = getenv("REMOTE_ADDR");

		elseif (!empty($_SERVER['REMOTE_ADDR'])
			&& strtolower($_SERVER['REMOTE_ADDR']) !==  "unknown")
			$result = $_SERVER['REMOTE_ADDR'];

		return $result;
	}

	static function get_request_url($str='') // 'cos of https://bugs.php.net/bug.php?id=52923
	{
		if ($str === "")
			$str = $_SERVER['REQUEST_URI'];
		$page_encoded = (str_replace(array("%25", "%2F", "%3D", "%26", "%3F", "%3A"), 
			array("%", "/", "=", "&", "?", ":"), urlencode($str)));
		return $page_encoded;
	}

	function CWebDavBase($base_url = "")
	{
		$this->http_method = $_SERVER['REQUEST_METHOD'];
		$this->http_user_agent = "undefined"; 
		$ua = strtolower($_SERVER["HTTP_USER_AGENT"]); 
		if (strpos($ua, "opera") === false && (strpos($ua, "msie") !== false) || strpos($_SERVER['HTTP_USER_AGENT'], 'Trident/7.0; rv:11.0') !== false )
			$this->http_user_agent = "ie";

		$this->meta_names = $this->getFoldersMetaData();

		$this->SetBaseUrl($base_url);		
		
		$page = $_SERVER['REQUEST_URI'];

		if ($arParsedUrl = parse_url(CWebDavBase::get_request_url()))
			$page = $arParsedUrl['path'];

		$this->uri = ($GLOBALS["APPLICATION"]->IsHTTPS() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$page;
		
		$path = $this->GetCurrentPath($page);
		$path = (empty($path) || $path == "index.php" ? "/" : $path); 
		$this->SetPath($path); 
		$this->USER["GROUPS"] = $GLOBALS["USER"]->GetUserGroupArray(); 
		$this->CACHE["PATHS"] = (is_array($this->CACHE["PATHS"]) ? $this->CACHE["PATHS"] : array()); 
		if (COption::GetOptionString("webdav", "webdav_log", "N") == "Y")
		{
			$this->events = new CWebDavEventLog;
			$this->events->InitLogEvents($this);
		}

		self::GetWindowsVersion();
	}

	static public function GetWindowsVersion()
	{
		static $MODULE = 'webdav';
		static $PARAM = 'windows_version';
		static $savedValues = null;

		$result = '';
		$userIP = self::GetIP();
		if (empty($userIP))
			return $result;

		if ($savedValues === null)
		{
			$savedValues = @unserialize(COption::GetOptionString($MODULE,$PARAM,''));
			if (!is_array($savedValues))
				$savedValues = array();
		}

		$ua = $_SERVER['HTTP_USER_AGENT'];
		if (preg_match('#Windows NT (\d{1})#', $ua, $matches) > 0)
		{
			$result = (int) $matches[1];
			if ($result > 0)
			{
				if (
					! isset($savedValues[$userIP])
					|| ($result !== (int) $savedValues[$userIP])
				)
				{
					$savedValues[$userIP] = $result;
					COption::SetOptionString($MODULE,$PARAM,serialize($savedValues));
				}
			}
		}
		else // seems to be webdav request, try to get os from history
		{
			if (isset($savedValues[$userIP]))
				$result = (int) $savedValues[$userIP];
		}

		return $result;
	}

	public function SetAuthHeader()
	{
		$digest = true;
		if (strpos($_SERVER['HTTP_USER_AGENT'], "Microsoft-WebDAV-MiniRedir") !== false)
		{
			if (preg_match("/([^\/]*)\/(\d+).(\d+).(\d+)/", $_SERVER['HTTP_USER_AGENT'], $matches) > 0) // Redir/5.1.2600
			{
				if (intval($matches[2]) < 6) // less then vista
				{
					$digest = false;
				}
			}
		}
		elseif (
			(strpos($_SERVER['HTTP_USER_AGENT'], "Microsoft Data Access Internet Publishing Provider") !== false)
			|| (
				(self::GetWindowsVersion() === 5)
				&& (strpos($_SERVER['HTTP_USER_AGENT'], "Microsoft Office Protocol Discovery") !== false)
			)
		)
		{
			$digest = false;
		}
		else
		{
			$digest = true;
		}
		CHTTP::SetAuthHeader($digest);
	}

	function MetaNamesReverse($alias, $from='alias', $to='name')
	{
		if (is_array($alias))
		{
			$arPath = $alias;
			foreach ($arPath as $pathID => $pathName)
			{
				foreach($this->meta_names as $metaName => $metaArr)
				{
					if ($pathName == $metaArr[$from])
						$arPath[$pathID] = $metaArr[$to];
				}
			}
			return implode("/", $arPath);
		}
		else
		{
			foreach($this->meta_names as $metaName => $metaArr)
			{
				if ($alias == $metaArr[$from])
					$alias = $metaArr[$to];
			}
			return $alias;
		}
	}

	function MetaNames(&$res, $strict = false)
	{
		static $hideSystemFiles = null;
		foreach($this->meta_names as $metaName => $metaArr)
		{
			if ($res["NAME"] == $metaArr["name"])
			{
				$res["~NAME"] = $res["NAME"];
				$res["NAME"] = $metaArr["alias"];
				if ($this->Type === "folder")
				{
					$arPath = explode("/", $res["PATH"]);
					$arPath[sizeof($arPath)-1] = $metaArr["alias"];
					$res["PATH"] = implode("/", $arPath);
				}
				elseif ($this->Type == "iblock" && $this->e_rights)
				{
					if($metaName == self::TRASH)
					{
						return (($this->arRootSection !== false) ?
							$this->GetPermission('SECTION', $this->arRootSection['ID'], 'section_rights_edit') :
							$this->GetPermission('IBLOCK', $this->IBLOCK_ID, 'iblock_rights_edit'));
					}
					else
					{
						return (($this->arRootSection !== false) ?
							$this->GetPermission('SECTION', $this->arRootSection['ID'], 'section_read') :
							$this->GetPermission('IBLOCK', $this->IBLOCK_ID, 'element_read'));
					}

				}
				return ($this->permission >= $metaArr["rights"]);
			}
		}
		if ($hideSystemFiles === null)
			$hideSystemFiles = (COption::GetOptionString("webdav", "hide_system_files", "Y") == "Y");

		if ((strpos($_SERVER['HTTP_USER_AGENT'], "WebDAVFS/1") !== false) &&
			(strpos($_SERVER['HTTP_USER_AGENT'], "Darwin/") !== false) &&
			($strict === false)
		) // mac os x 10.7
			$hideSystemFiles = false;

		if ((preg_match('/^\..*/', $res['NAME']) !== 0) && $hideSystemFiles)
			return false;

		return true;
	}

	function SetPath($path)
	{
		$this->_path = (substr($path, -9, 9) === "index.php" ? substr($path, 0, strlen($path) - 9) : $path);

		if (
			defined('BX_UTF')
			&& isset($_SERVER['SERVER_SOFTWARE'])
			&& (strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false)
			&& ! CUtil::DetectUTF8($_SERVER["REQUEST_URI"])
		)
		{
			$charset = 'windows-1251';
			if (
				defined('BX_DEFAULT_CHARSET')
				&& ( strlen('BX_DEFAULT_CHARSET')>0 )
			)
				$charset = BX_DEFAULT_CHARSET; 

			$this->_path = CharsetConverter::ConvertCharset($this->_path, $charset, "utf-8");
		}

		$arPath = explode('/',$this->_udecode($this->_path));
		foreach ($this->meta_names as $metaName => $metaValue)
		{
			if ($arPath[1] == $metaValue['alias'] || $arPath[1] == $metaValue['name'])
				$this->meta_state = $metaName;
		}
		if (isset($this->meta_state) && method_exists($this, 'GetMetaID'))
			$this->GetMetaID($this->meta_state);
	}

	function IsMethodAllow($method)
	{
		return array_key_exists($method, $this->allow);
	}

	function Init()
	{
		global $APPLICATION, $USER;

		if (!$this->CheckIfHeader())
		{
			return false;
		}

		return true;
	}

	function Work()
	{
		if ($this->IsMethodAllow($_SERVER['REQUEST_METHOD']))
		{
			$fn = 'base_' . $_SERVER['REQUEST_METHOD'];
			$this->$fn();
		}
		else
		{
			$this->Error('405 Method not allowed', 'WEBDAV_INVALID_METHOD', '', __LINE__);
			header('Allow: ' . join(',', array_keys($this->allow)));
		}
	}

	static function base_OPTIONS()
	{
		CWebDavBase::SetStatus('200 OK');

		header('MS-Author-Via: DAV');
		header('DAV: '	.join(',', CWebDavBase::$levels));
		header('Allow: '.join(',', CWebDavBase::$methods));
		header('Content-length: 0');
	}

	function urlencode($str)
	{
		$arPath = explode("/", $str);
		foreach ($arPath as $i => $sElm) {
			$arPath[$i] = rawurlencode($sElm);
		}
		return implode("/", $arPath);
	}

	function base_PROPFIND()
	{
		global $APPLICATION;
		$options = Array();
		$files	 = Array();

		$options['path'] = $this->_path;
		$options['depth'] = (array_key_exists('HTTP_DEPTH', $_SERVER) ? $_SERVER['HTTP_DEPTH'] : 'infinity');

		$propinfo = new __CParsePropfind;
		$propinfo->LoadFromPhpInput();
		if (!$propinfo->success)
		{
			$this->ThrowError('400 Error', 'WEBDAV_PROPFIND_PARSE', '', __FILE__ .' '. __LINE__);
			return;
		}

		$options['props'] = $propinfo->props;
		if (is_string($result = $this->PROPFIND($options, $files)))
		{
			$this->SetStatus($result);
			header('Content-length: 0');
			header('Connection: close');		
			return;
		}

		$ns_hash = array();
		$ns_defs = 'xmlns:ns0="urn:uuid:c2f41010-65b3-11d1-a29f-00aa00c14882/"';

		foreach ($files['files'] as $filekey => $file)
		{
			if (!isset($file['props']) /*|| !is_array($file['props'])*/) // always array from _get_file_info_arr
				continue;

			foreach ($file['props'] as $key => $prop)
			{
				switch($options['props'])
				{
					case 'all':
						break;
					case 'names':
						unset($files['files'][$filekey]['props'][$key]['val']);
						break;
					default:
						break;
				}

				if (empty($prop['ns']))
					continue;
				$ns = $prop['ns'];
				if ($ns == 'DAV:')
					continue;
				if (isset($ns_hash[$ns]))
					continue;

				$ns_name = 'ns' . (count($ns_hash) + 1);
				$ns_hash[$ns] = $ns_name;
				$ns_defs .= " xmlns:$ns_name=\"$ns\"";
			}

			if (is_array($options['props']))
			{
				foreach ($options['props'] as $reqprop)
				{
					if ($reqprop['name']=='')
						continue;

					$found = false;

					foreach ($file['props'] as $prop)
					{
						if ($reqprop['name'] == $prop['name'] && @$reqprop['xmlns'] == $prop['ns'])
						{
							$found = true;
							break;
						}
					}

					if (!$found)
					{
						if ($reqprop['xmlns']==='DAV:' && $reqprop['name']==='lockdiscovery')
						{
							$files['files'][$filekey]['props'][] = $this->_mkprop('DAV:', 'lockdiscovery', $this->lockdiscovery($files['files'][$filekey]['path']));
						}
						else
						{
							if ($reqprop['name'] != 'save-profile-form-location')
							{
								$files['files'][$filekey]['noprops'][] = $this->_mkprop($reqprop['xmlns'], $reqprop['name'], '');

								if ($reqprop['xmlns'] != 'DAV:' && !isset($ns_hash[$reqprop['xmlns']]))
								{
									$ns_name = 'ns' . (count($ns_hash) + 1);
									$ns_hash[$reqprop['xmlns']] = $ns_name;
									$ns_defs .= ' xmlns:' . $ns_name . '="' . $reqprop['xmlns'] . '"';
								}
							}
						}
					}
				}
			}
		}

		$this->SetStatus('207 Multi-Status');
		header('Content-Type: text/xml; charset="utf-8"');
		echo "<?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
		echo "<D:multistatus xmlns:D=\"DAV:\" xmlns:Office=\"urn:schemas-microsoft-com:office:office\" xmlns:Repl=\"http://schemas.microsoft.com/repl/\" xmlns:Z=\"urn:schemas-microsoft-com:\">\n";

		foreach ($files['files'] as $file)
		{
			if (!is_array($file) || empty($file) || !isset($file['path']))
				continue;
			$path = $file['path'];
			if (!is_string($path) || $path==='')
				continue;

			echo " <D:response $ns_defs>\n";

			$href = $this->urlencode($APPLICATION->ConvertCharset($this->base_url, SITE_CHARSET, 'UTF-8') . $path);

			echo "	<D:href>$href</D:href>\n";

			if (isset($file['props']) && is_array($file['props']))
			{
				echo "	 <D:propstat>\n";
				echo "	  <D:prop>\n";

				foreach ($file['props'] as $key => $prop) {

					//if (!is_array($prop)) // from _get_file_info_arr - always array, if _mkprop
						//continue;
					if (!isset($prop['name']))
						continue;
					if ($prop['name'] == 'UNDELETE')
						continue;

					if (!isset($prop['val']) || $prop['val'] === '' || $prop['val'] === false)
					{
						if ($prop['ns']=='DAV:')
						{
							echo "	   <D:".$prop['name']."/>\n";
						}
						else if (!empty($prop["ns"]))
						{
							echo "	   <".$ns_hash[$prop['ns']].":".$prop['name']."/>\n";
						}
						else
						{
							echo "	   <".$prop['name']." xmlns=\"\"/>";
						}
					}
					else if ($prop['ns'] == 'DAV:')
					{
						switch ($prop['name'])
						{
							case 'creationdate':
								echo "	   <D:creationdate ns0:dt=\"dateTime.tz\">" ,
									gmdate('Y-m-d\\TH:i:s\\Z', $prop['val']),
									"</D:creationdate>\n";
								break;
							case 'getlastmodified':
								echo "	   <D:getlastmodified ns0:dt=\"dateTime.rfc1123\">",
									gmdate('D, d M Y H:i:s ', $prop['val']),
									"GMT</D:getlastmodified>\n";
								break;
							case 'resourcetype':
								echo "	   <D:resourcetype><D:".$prop['val']."/></D:resourcetype>\n";
								break;
							case 'supportedlock':
								echo "	   <D:supportedlock>".$prop['val']."</D:supportedlock>\n";
								break;
							case 'lockdiscovery':
								echo "	   <D:lockdiscovery>\n";
								echo $prop["val"];
								echo "	   </D:lockdiscovery>\n";
								break;
							default:
								echo "	   <D:".$prop['name'].">"
								//. $this->_prop_encode(htmlspecialcharsbx($prop['val']))
								. htmlspecialcharsbx($prop['val'])
								.	  "</D:".$prop['name'].">\n";
								break;
						}
					}
					else
					{
						if ($prop['ns'])
						{
							echo "	   <" . $ns_hash[$prop["ns"]] . ":".$prop['name'].">",
								htmlspecialcharsbx($prop['val']) ,
								"</" . $ns_hash[$prop["ns"]] . ":".$prop['name'].">\n";
						}
						else
						{
							echo "	   <".$prop['name']." xmlns=\"\">",
								htmlspecialcharsbx($prop['val']),
								"</".$prop['name'].">\n";
						}
					}
				}

				echo "	 </D:prop>\n";
				echo "	 <D:status>HTTP/1.1 200 OK</D:status>\n";
				echo "	</D:propstat>\n";
			}

			if (isset($file['noprops']))
			{
				echo "	 <D:propstat>\n";
				echo "	  <D:prop>\n";

				foreach ($file['noprops'] as $key => $prop)
				{
					if ($prop['ns'] == 'DAV:')
					{
						echo "	   <D:".$prop['name']."/>\n";
					}
					else if ($prop['ns'] == '')
					{
						echo "	   <".$prop['name']." xmlns=\"\"/>\n";
					}
					else
					{
						echo "	   <" . $ns_hash[$prop["ns"]] . ":".$prop['name']."/>\n";
					}
				}

				echo "	 </D:prop>\n";
				echo "	 <D:status>HTTP/1.1 404 Not Found</D:status>\n";
				echo "	</D:propstat>\n";
			}

			echo " </D:response>\n";
		}

		echo "</D:multistatus>\n";
	}

	function base_GET()
	{
		$options = Array();
		$options['path'] = $this->_path;

		$this->_get_ranges($options);

		if (true === ($status = $this->GET($options)))
		{
			$x = $this->SendFile($options);
			if (!is_null($x))
			{
				$status = $x;
			}
		}

		if (!headers_sent())
		{
			if (false === $status)
			{
				$this->ThrowError('404 not found', 'WEBDAV_FILE_NOT_FOUND', '', __FILE__.' '.__LINE__);
			}
			else
			{
				$this->SetStatus($status);
			}
		}
	}

	static function set_header($str, $force=true) // safe from response splitting
	{
		header(str_replace(array("\r", "\n"), "", $str), $force);
	}

	function SendFile(&$options, $download = null)
	{
		if($download == null)
		{
			$download = array_key_exists("force_download", $_REQUEST);
		}
		
		$status = null;
		if (!headers_sent())
		{
			$fullPath = "";
			if(array_key_exists("logica_full_path", $options))
			{
				$fullPath = $options["logica_full_path"];
			}
			if($this->Type == "iblock" && isset($this->arParams["fullpath"]) && strlen($this->arParams["fullpath"]) > 0)
			{
					$fullPath = $this->arParams["fullpath"];
			}
			elseif($this->Type == "folder" && strlen($this->real_path_full) > 0)
			{
					$fullPath = $this->real_path_full;
			}

			if(strlen($fullPath) > 0)
			{
				$arT = self::GetMimeAndGroup($fullPath);
				$options['mimetype'] = $arT["mime"];
			}
			else
			{
				$options['mimetype'] = 'application/octet-stream';
				$download = true;
			}

			/*
			if ($options['mimetype'] == 'application/zip') // fix old magic.mime
				$options['mimetype'] = $this->get_mime_type($options['path']);*/

			if (
				(
					$GLOBALS["APPLICATION"]->IsHTTPS()
					&& substr($options['mimetype'], 0, 11) == "application"
				)
				|| $this->http_user_agent == "ie"
			)
			{
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Pragma: public");
				$options["cache_time"] = 0;
			}

			$status = '200 OK';

			$name = "";
			if (!empty($options["name"]))
			{
				$name = $options["name"];
			}
			else
			{
				$res = explode("/", $this->_udecode($options["path"]));
				$name = end($res);
			}

			if ($this->http_user_agent == "ie")
			{
				$name = $this->_uencode($name, array("utf8" => "Y", "convert" => "full"));
			}
			elseif (SITE_CHARSET != 'UTF-8')
			{
				$name = $GLOBALS['APPLICATION']->ConvertCharset($name, SITE_CHARSET, 'UTF-8');
			}

			self::set_header('Content-type: ' . $options['mimetype']);
			self::set_header('Content-Disposition: filename="'.$name.'"', true);
			self::set_header('ETag: "' . $this->_get_etag() . '"');
			if(array_key_exists("cache_time", $options) && $options["cache_time"] > 0 && substr($options['mimetype'], 0, 6) == "image/")
			{
				//Handle ETag
				if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && $this->_check_etag($_SERVER['HTTP_IF_NONE_MATCH']))
				{
					$this->SetStatus('304 Not Modified');
					self::set_header("Cache-Control: private, max-age=".$options["cache_time"].", pre-check=".$options["cache_time"]);
					die();
				}
				//Handle Last Modified
				if($options["mtime"] > 0)
				{
					$lastModified = gmdate('D, d M Y H:i:s', $options["mtime"]).' GMT';
					if(array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER) && ($_SERVER['HTTP_IF_MODIFIED_SINCE'] === $lastModified))
					{
						$this->SetStatus('304 Not Modified');
						self::set_header("Cache-Control: private, max-age=".$options["cache_time"].", pre-check=".$options["cache_time"]);
						die();
					}
				}
				self::set_header("Cache-Control: private, max-age=".$options["cache_time"].", pre-check=".$options["cache_time"]);
			}

			$userNavigator = CUserOptions::GetOption('webdav', 'navigator', array('platform'=>'Win'));
			if (isset($_SERVER['HTTP_IF_NONE_MATCH']) &&				// for correct save action in MS Office 2010
				$this->_check_etag($_SERVER['HTTP_IF_NONE_MATCH']) &&
				(
					strpos($_SERVER['HTTP_USER_AGENT'], 'Microsoft') !== false &&
					strpos($userNavigator['platform'], 'Win') !== false
					)
			)
			{
				$this->SetStatus('304 Not Modified');
				die();
			}

			if (!$download)
			{
				if(strlen($fullPath) > 0)
				{
					$download = !self::CanViewFile($fullPath, false);
				}
				else
				{
					$download = true;
				}
			}

			if ($download == true)
			{
				self::set_header('Content-Disposition: attachment; filename="'.$name.'"', true);
			}

			if (isset($options['mtime']))
			{
				self::set_header('Last-modified: '.gmdate('D, d M Y H:i:s ', $options['mtime']).'GMT');
			}

			session_write_close();
			if (isset($options['stream']))
			{
				$sizeO = intval($options['size']);
				$ranges = (isset($options['ranges']) && is_array($options['ranges'])) ? $options['ranges'] : array();
				$this->SendFileFromStream($options['stream'], $options['mimetype'], $sizeO, $ranges);
			}
			elseif (isset($options['data']) && !is_array($options['data']))
			{
				self::set_header('Content-length: ' . $this->strlen($options['data']));
				echo $options['data'];
			}
		}

		return $status;
	}

	function base_PUT()
	{
		if ($this->_check_lock_status($this->_path))
		{
			$options = array(
				'path' => $this->_path,
				'content_length' => $_SERVER['CONTENT_LENGTH']);

			if (isset($_SERVER['CONTENT_TYPE']))
			{
				if (!strncmp($_SERVER['CONTENT_TYPE'], 'multipart/', 10))
				{
					$errMessage = 'The service does not support mulipart PUT requests';
					$this->ThrowError('501 not implemented', 'WEBDAV_PUT_MULTIPART', $errMessage, __FILE__.' '.__LINE__);
					echo $errMessage;
					return;
				}
				$options['content_type'] = $_SERVER['CONTENT_TYPE'];
			}
			else
			{
				$options['content_type'] = 'application/octet-stream';
			}

			foreach ($_SERVER as $key => $val)
			{
				if (strncmp($key, "HTTP_CONTENT", 11))
					continue;
				switch ($key)
				{
					case 'HTTP_CONTENT_ENCODING':
						$errMessage = "The service does not support '".htmlspecialcharsEx($val)."' content encoding";
						$this->ThrowError('501 not implemented', 'WEBDAV_PUT_ENCODING', $errMessage, __FILE__.' '.__LINE__);
						echo $errMessage;
						return;
					case 'HTTP_CONTENT_LANGUAGE':
						$options["content_language"] = $val;
						break;
					case 'HTTP_CONTENT_LENGTH':
						if (empty($options["content_length"])):
							$options["content_length"] = $_SERVER['HTTP_CONTENT_LENGTH'];
							$_SERVER['CONTENT_LENGTH'] = $_SERVER['HTTP_CONTENT_LENGTH'];
						endif;
						break;
					case 'HTTP_CONTENT_LOCATION':
						break;
					case 'HTTP_CONTENT_TYPE':
						break;
					case 'HTTP_CONTENT_RANGE':
						if (!preg_match('@bytes\s+(\d+)-(\d+)/((\d+)|\*)@', $val, $matches)) {
							$errMessage = "The service does only support single byte ranges";
							$this->ThrowError("400 bad request", 'WEBDAV_PUT_MULTIRANGE', $errMessage, __FILE__.' '.__LINE__);
							echo $errMessage;
							return;
						}
						$range = array(
							"start"=>$matches[1],
							"end"=>$matches[2]);
						if (is_numeric($matches[3]))
						{
							$range["total_length"] = $matches[3];
						}
						$options["ranges"][] = $range;

						break;
					case 'HTTP_CONTENT_MD5':
						$errMessage = 'The service does not support content MD5 checksum verification';
						$this->ThrowError('501 not implemented', 'WEBDAV_PUT_MD5', $errMessage, __FILE__.' '.__LINE__);
						echo $errMessage;
						return;
					default:
						$errMessage = "The service does not support '".htmlspecialcharsEx($key)."'";
						$this->ThrowError('501 not implemented', 'WEBDAV_PUT_MISC', $errMessage, __FILE__.' '.__LINE__);
						echo $errMessage;
						return;
				}
			}

			$arPath = explode("/", $this->_udecode($options['path']));
			foreach ($this->meta_names as $sMetaType => $arMetaProps)
			{
				if ($arPath[1] == $arMetaProps["alias"] && 
					isset($arMetaProps["disable"]) && 
					strpos($arMetaProps["disable"], 'PUT') !== false
				)
				{
					$this->ThrowAccessDenied();
					return false;
				}
			}

			$options['stream'] = fopen('php://input', 'r');
			$stat = $this->PUT($options);
			if ($stat === false)
			{
				$stat = $this->ThrowError('403 Forbidden', 'WEBDAV_PUT_PUT_FORBIDDEN', '', __FILE__.' '.__LINE__);
			}
			elseif (is_resource($stat) && get_resource_type($stat) == 'stream')
			{
				@set_time_limit(0);
				$stream = $stat;
				$stat = $options['new'] ? '201 Created' : '204 No Content';

				if (!empty($options['ranges']))
				{
					if (0 == fseek($stream, $range[0]['start'], SEEK_SET))
					{
						$length = $range[0]['end']-$range[0]['start']+1;
						if (!fwrite($stream, fread($options['stream'], $length)))
						{
							$stat = $this->ThrowError('403 Forbidden', 'WEBDAV_PUT_FWRITE_FAIL', '', __FILE__.' '.__LINE__);
						}
					}
					else
					{
						$stat = $this->ThrowError('403 Forbidden', 'WEBDAV_PUT_SEEK_FAIL', '', __FILE__.' '.__LINE__);
					}
				}
				else
				{
					while (!feof($options['stream']))
					{
						if (false === fwrite($stream, fread($options['stream'], 8192)))
						{
							$stat = $this->ThrowError('403 Forbidden', 'WEBDAV_PUT_FWRITE_FAIL2', '', __FILE__.' '.__LINE__);
							break;
						}
					}
				}
				fclose($stream);
				if (method_exists($this, 'put_commit') && !$this->put_commit($options))
				{
					$stat = $this->ThrowError('409 Conflict', 'WEBDAV_PUT_COMMIT_FAIL', '', __FILE__.' '.__LINE__);
				}
			}

			self::set_header('Content-length: 0');
			self::set_header('Location: ' . $this->base_url_full.$this->_path);
			$this->SetStatus($stat);
		}
		else
		{
			$this->ThrowError('423 Locked', 'WEBDAV_PUT_LOCKED', '', __FILE__.' '.__LINE__);
		}
	}

	function base_PROPPATCH()
	{
		if ($this->_check_lock_status($this->_path))
		{
			global $APPLICATION;
			$options = array(
				'path' => $this->_udecode($this->_path));

			$propinfo = new __CParseProppatch();
			$propinfo->LoadFromPhpInput();
			if (!$propinfo->success)
			{
				$this->ThrowError('400 Error', 'WEBDAV_PROPPATCH_PARSE', '', __FILE__.' '.__LINE__);
				return;
			}
			$options['props'] = $propinfo->props;

			$responsedescr = $this->PROPPATCH($options);

			$this->SetStatus("207 Multi-Status");
			header('Content-Type: text/xml; charset="utf-8"');

			echo "<?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
			echo "<D:multistatus xmlns:D=\"DAV:\">\n";
			echo " <D:response>\n";
			echo "	<D:href>" . $APPLICATION->ConvertCharset($this->base_url . $options['path'], SITE_CHARSET, 'UTF-8'). "</D:href>\n";

			foreach ($options["props"] as $prop)
			{
				echo "	 <D:propstat>\n";
				echo "	  <D:prop><".$prop['name']." xmlns=\"".$prop['ns']."\"/></D:prop>\n";
				echo "	  <D:status>HTTP/1.1 ".$prop['status']."</D:status>\n";
				echo "	 </D:propstat>\n";
			}

			if ($responsedescr)
			{
				echo "	<D:responsedescription>".
				$APPLICATION->ConvertCharset(htmlspecialcharsbx($responsedescr), SITE_CHARSET, 'UTF-8').
				"</D:responsedescription>\n";
			}

			echo " </D:response>\n";
			echo "</D:multistatus>\n";
		}
		else
		{
			$this->ThrowError("423 Locked", 'WEBDAV_PROPPATCH_LOCKED', '', __FILE__.' '.__LINE__);
		}
	}

	function base_MKCOL()
	{
		$options = array(
			'path' => $this->_path);

		$stat = $this->MKCOL($options);

		self::set_header('Content-length: 0');
		self::set_header('Location: ' . $this->base_url_full.$this->_path);
		
		$this->SetStatus($stat);
	}

	function base_DELETE()
	{
		if (isset($_SERVER['HTTP_DEPTH']) && $_SERVER['HTTP_DEPTH'] != 'infinity')
		{
			$this->ThrowError('400 Bad Request', 'WEBDAV_DELETE_DEPTH', '', __FILE__.' '.__LINE__);
			return;
		}

		if ($this->_check_lock_status($this->_path))
		{
			$options = array(
				'path' => $this->_path);
			$stat = $this->DELETE($options);
			$this->SetStatus($stat);
		}
		else
		{
			$this->ThrowError('423 Locked', 'WEBDAV_DELETE_LOCKED', '', __FILE__.' '.__LINE__);
		}
		header('Content-length: 0');
		die();
	}

	function base_MOVE()
	{
		$options = array(
			'path' => $this->_path,
			'depth' => (isset($_SERVER['HTTP_DEPTH']) ? $_SERVER['HTTP_DEPTH'] : 'infinity'),
			'overwrite' => (isset($_SERVER['HTTP_OVERWRITE']) ? ($_SERVER['HTTP_OVERWRITE'] == 'T') : true));

		$pu = parse_url(CWebDavBase::get_request_url($_SERVER['HTTP_DESTINATION']));
		if (intVal($pu['port']) == 80 && strpos($_SERVER["HTTP_HOST"], ":80") === false)
		{
			$_SERVER['HTTP_DESTINATION'] = str_replace($pu['host'].":".$pu['port'], $pu['host'], $_SERVER['HTTP_DESTINATION']);
			$pu = parse_url(CWebDavBase::get_request_url($_SERVER['HTTP_DESTINATION']));
		}
		$pu['host_name'] = $pu['host'].(!empty($pu['port']) ? ":".$pu['port'] : "");
		if (strToLower($pu['host_name']) == strToLower($_SERVER["HTTP_HOST"]) || strToLower($pu['host_name']) == strToLower($_SERVER['SERVER_NAME']))
		{
			$options['dest_url'] = $this->GetCurrentPath(urldecode($pu['path']));
			$stat = $this->MOVE($options);
		}
		else 
		{
			$stat = $this->ThrowError("412 precondition failed", "WEBDAV_MOVE_PRECONDITION", '', __FILE__.' '.__LINE__);
		}
		
		$this->SetStatus($stat);
		self::set_header('Content-length: 0');
		if (substr($stat, 0, 1) == '2')
		{
			self::set_header('Location: ' . $this->base_url_full.$this->GetCurrentPath($pu['path']));
		}
	}
	
	function base_COPY()
	{
		$options = array(
			'path' => $this->_path,
			'depth' => (isset($_SERVER['HTTP_DEPTH']) ? $_SERVER['HTTP_DEPTH'] : 'infinity'),
			'overwrite' => (isset($_SERVER['HTTP_OVERWRITE']) ? ($_SERVER['HTTP_OVERWRITE'] == 'T') : true));
		$pu = parse_url(CWebDavBase::get_request_url($_SERVER['HTTP_DESTINATION']));
		if (intVal($pu['port']) == 80 && strpos($_SERVER["HTTP_HOST"], ":80") === false)
		{
			$_SERVER['HTTP_DESTINATION'] = str_replace($pu['host'].":".$pu['port'], $pu['host'], $_SERVER['HTTP_DESTINATION']);
			$pu = parse_url(CWebDavBase::get_request_url($_SERVER['HTTP_DESTINATION']));
		}
		$pu['host_name'] = $pu['host'].(!empty($pu['port']) ? ":".$pu['port'] : "");
		if (strToLower($pu['host_name']) == strToLower($_SERVER["HTTP_HOST"]) || strToLower($pu['host_name']) == strToLower($_SERVER['SERVER_NAME']))
		{
			$options['dest_url'] = $this->GetCurrentPath($pu['path']);
			$stat = $this->COPY($options);
		}
		else 
		{
			$stat = $this->ThrowError("412 precondition failed", "WEBDAV_COPY_PRECONDITION", '', __FILE__.' '.__LINE__);
		}
		
		$this->SetStatus($stat);
		self::set_header('Content-length: 0');
		if (substr($stat, 0, 1) == '2')
		{
			self::set_header('Location: ' . $this->base_url_full.$pu['path']);
		}
	}

	function base_HEAD()
	{
		$status = false;
		$options = array(
			'path' => $this->_path);

		if (method_exists($this, 'HEAD'))
		{
			$status = $this->HEAD($options);
		}
		elseif (method_exists($this, 'GET'))
		{
			ob_start();
			$status = $this->base_GET();
			ob_end_clean();
		}
	}

	function SetStatus($status)
	{
		$bCgi = (stristr(php_sapi_name(), "cgi") !== false);
		$bFastCgi = ($bCgi && (array_key_exists('FCGI_ROLE', $_SERVER) || array_key_exists('FCGI_ROLE', $_ENV)));
		if (defined("BITRIX_FORCE_STATUS")): 
			self::set_header("Status: ".$status);
		elseif ($bCgi && !$bFastCgi): 
			self::set_header("Status: ".$status);
		else: 
			self::set_header($_SERVER["SERVER_PROTOCOL"]." ".$status);
		endif;

		self::set_header('X-WebDAV-Status: ' . $status, true);
	}

	function SetBaseUrl($url)
	{
		$url = rtrim(str_replace("//", "/", "/".$url), '/');
		$this->base_url = $url;
		
		$url = str_replace("//", "/", $_SERVER['HTTP_HOST'].$url);
		$this->base_url_full = ($GLOBALS["APPLICATION"]->IsHTTPS() ? 'https' : 'http').'://'.$url;
	}

	function _mkprop()
	{
		$args = func_get_args();
		if (count($args) == 3)
		{
			return array(
				'ns'	=> $args[0],
				'name'	=> $args[1],
				'val'	=> $args[2]);
		}
		else
		{
			return array(
				'ns'	=> 'DAV:',
				'name'	=> $args[0],
				'val'	=> $args[1]);
		}
	}

	function _slashify($path)
	{
		if ($path[strlen($path)-1] != '/')
			$path = $path . '/';
		return $path;
	}

	function _unslashify($path)
	{
		if ($path[strlen($path)-1] == '/')
			$path = substr($path, 0, strlen($path)-1);
		return $path;
	}

	static function _get_file_hash($file)
	{
		$fileName = $file;
		if (intval($file) > 0)
		{
			$arTmpFile = CFile::MakeFileArray($file);
			$fileName = '';
			if (isset($arTmpFile['tmp_name']))
				$fileName = $arTmpFile['tmp_name'];
		}

		$result = null;

		$io = CBXVirtualIo::GetInstance();
		$fileNameX = $io->GetPhysicalName($fileName);

		if (file_exists($fileNameX) && (filesize($fileNameX) < 4000000000))
			$result = md5_file($fileNameX);
		elseif (file_exists($fileName) && (filesize($fileName) < 4000000000))
			$result = md5_file($fileName);

		return $result;
	}

	function _prop_encode($text)
	{
		global $APPLICATION;
		$res = $text;
		if (strtolower($this->_prop_encoding) != 'utf-8')
			$res = $APPLICATION->ConvertCharset($text, SITE_CHARSET, 'UTF-8');
		return $res;
	}

	function _get_ranges(&$options)
	{
		if (isset($_SERVER['HTTP_RANGE']))
		{
			if (preg_match('/bytes\s*=\s*(.+)/', $_SERVER['HTTP_RANGE'], $matches))
			{
				$options["ranges"] = array();

				foreach (explode(",", $matches[1]) as $range)
				{
					list($start, $end) = explode("-", $range);
					$options["ranges"][] = ($start==="")
						? array("last"=>$end)
						: array("start"=>$start, "end"=>$end);
				}
			}
		}
	}

	function _multipart_byterange_header($mimetype = false, $from = false, $to=false, $total=false)
	{
		if ($mimetype === false)
		{
			if (empty($this->multipart_separator))
			{
				$this->multipart_separator = "SEPARATOR_".md5(microtime());

				self::set_header("Content-type: multipart/byteranges; boundary=".$this->multipart_separator);
			}
			else
			{
				echo "\n--{".$this->multipart_separator."}--";
			}
		}
		else
		{
			echo "\n--{".$this->multipart_separator."}\n";
			echo "Content-type: $mimetype\n";
			echo "Content-range: $from-$to/". ($total === false ? "*" : $total);
			echo "\n\n";
		}
	}

	public static function getExtensionByMimeType($mimeType)
	{
		$arMimes = self::getMimeTypeExtensionList();
		$arMimes = array_flip($arMimes);
		$mimeType = strtolower($mimeType);
		if(!empty($arMimes[$mimeType]))
		{
			return $arMimes[$mimeType];
		}

		return false;
	}

	public static function getMimeTypeExtensionList()
	{
		static $mimeTypeList = array(
			'.html' => 'text/html',
			'.gif' => 'image/gif',
			'.jpg' => 'image/jpeg',
			'.jpeg' => 'image/jpeg',
			'.txt' => 'text/plain',
			'.xml' => 'application/xml',
			'.pdf' => 'application/pdf',
			'.doc' => 'application/msword',
			'.docm' => 'application/vnd.ms-word.document.macroEnabled.12',
			'.docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'.dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
			'.dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
			'.potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
			'.potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
			'.ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
			'.ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
			'.ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
			'.pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
			'.pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'.xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
			'.xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
			'.xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
			'.xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'.xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
			'.xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'.zip'	=> 'application/zip'
		);

		return $mimeTypeList;
	}

	public static function get_mime_type($fspath)
	{
		$arMimes = self::getMimeTypeExtensionList();

		$ext = strtolower(strrchr($fspath, '.'));
		if (array_key_exists($ext, $arMimes))
		{
			$mime_type = $arMimes[$ext];
		}
		else
		{
			$mime_type = 'application/octet-stream';
		}

		return $mime_type;
	}

	function CheckIfHeader()
	{
		if (isset($_SERVER['HTTP_IF']))
		{
			$arUris = $this->_if_header_parser($_SERVER['HTTP_IF']);

			foreach ($arUris as $uri => $conditions)
			{
				if ($uri == '')
				{
					$uri = $this->uri;
				}

				$state = true;
				foreach ($conditions as $condition)
				{
					if (!strncmp($condition, '<opaquelocktoken:', strlen('<opaquelocktoken')))
					{
						if (!preg_match('/^<opaquelocktoken:[[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12}>$/' . BX_UTF_PCRE_MODIFIER, $condition))
						{
							$this->ThrowError('423 Locked', 'WEBDAV_HEADER_TOCKEN', '', __FILE__.' '.__LINE__);
							return false;
						}
					}
					if (!$this->_check_uri_condition($uri, $condition))
					{
						$this->ThrowError('412 Precondition failed', 'WEBDAV_HEADER_URI', '', __FILE__.' '.__LINE__);
						$state = false;
						break;
					}
				}

				if ($state == true)
				{
					return true;
				}
			}
			return false;
		}
		return true;
	}

	function _if_header_parser($str)
	{
		$pos  = 0;
		$len  = strlen($str);
		$uris = array();

		while ($pos < $len)
		{
			$token = $this->_if_header_lexer($str, $pos);

			if ($token[0] == 'URI')
			{
				$uri   = $token[1];
				$token = $this->_if_header_lexer($str, $pos);
			}
			else
			{
				$uri = '';
			}

			if ($token[0] != 'CHAR' || $token[1] != '(')
			{
				return false;
			}

			$list  = array();
			$level = 1;
			$not   = '';
			while ($level)
			{
				$token = $this->_if_header_lexer($str, $pos);
				if ($token[0] == 'NOT')
				{
					$not = '!';
					continue;
				}
				switch ($token[0])
				{
					case 'CHAR':
						switch ($token[1])
						{
							case '(':
								$level++;
								break;
							case ')':
								$level--;
								break;
							default:
								return false;
						}
						break;
					case 'URI':
						$list[] = $not."<" . $token[1] . ">";
						break;
					case 'ETAG_WEAK':
						$list[] = $not . "[W/'".$token[1] . "']>";
						break;
					case 'ETAG_STRONG':
						$list[] = $not . "['".$token[1] . "']>";
						break;
					default:
						return false;
				}
				$not = '';
			}
			$uris[$uri] = (@is_array($uris[$uri]) ? array_merge($uris[$uri], $list) : $list);
		}

		return $uris;
	}

	function _if_header_lexer($string, &$pos)
	{
		while (preg_match('/^\s+$/', substr($string, $pos, 1)))
		{
			++$pos;
		}

		if (strlen($string) <= $pos)
		{
			return false;
		}

		$c = substr($string, $pos++, 1); 

		switch ($c)
		{
			case '<':
				$pos2 = strpos($string, '>', $pos);
				$uri  = substr($string, $pos, $pos2 - $pos);
				$pos  = $pos2 + 1;
				return array('URI', $uri);
			case '[':
				if (substr($string, $pos, 1) == 'W')
				{
					$type = 'ETAG_WEAK';
					$pos += 2;
				}
				else
				{
					$type = 'ETAG_STRONG';
				}
				$pos2 = strpos($string, ']', $pos);
				$etag = substr($string, $pos + 1, $pos2 - $pos - 2);
				$pos  = $pos2 + 1;
				return array($type, $etag);
			case 'N':
				$pos += 2;
				return array('NOT', 'Not');
			default:
				return array('CHAR', $c);
		}
	}

	function _check_uri_condition($uri, $condition)
	{
		if (!strncmp('<DAV:', $condition, 5))
		{
			return false;
		}
		return true;
	}

	function base_LOCK()
	{
		$options = array(
			'path' => $this->_path,
			'depth' => (isset($_SERVER['HTTP_DEPTH']) ? $_SERVER['HTTP_DEPTH'] : 'infinity'));

		if (isset($_SERVER['HTTP_TIMEOUT']))
		{
			$options['timeout'] = explode(',', $_SERVER['HTTP_TIMEOUT']);
		}

		if (empty($_SERVER['CONTENT_LENGTH']) && !empty($_SERVER['HTTP_IF']))
		{
			if (!$this->_check_lock_status($this->_path))
			{
				$this->ThrowError("423 Locked", "WEBDAV_LOCK_LOCKED", '', __FILE__.' '.__LINE__);
				return;
			}

			$options["locktoken"] = substr($_SERVER['HTTP_IF'], 2, -2);
			$options["update"]	  = $options["locktoken"];

			$options['owner']	  = "unknown";
			$options['scope']	  = "exclusive";
			$options['type']	  = "write";

			$stat = $this->LOCK($options);
		}
		else
		{
			$lockinfo = new __CParseLockinfo();
			$lockinfo->LoadFromPhpInput();

			if (!$lockinfo->success)
			{
				$this->ThrowError("400 bad request", "WEBDAV_LOCK_PARSE", '', __FILE__.' '.__LINE__);
			}

			if (!$this->_check_lock_status($this->_path, $lockinfo->lockscope !== "shared"))
			{
				$this->ThrowError("423 Locked", "WEBDAV_LOCK_LOCKED", '', __FILE__.' '.__LINE__);
				return;
			}

			$options["scope"]	  = $lockinfo->lockscope;
			$options["type"]	  = $lockinfo->locktype;
			$options["owner"]	  = $lockinfo->owner;
			$options["locktoken"] = $this->_new_locktoken();

			$stat = $this->LOCK($options);
		}

		if (is_bool($stat))
		{
			$http_stat = ($stat ? '200 OK' : '423 Locked');
		}
		else
		{
			$http_stat = $stat;
		}
		$this->SetStatus($http_stat);

		if (substr($http_stat, 0 , 1) == '2')
		{
			if ($options['timeout'])
			{
				if ($options['timeout'] > 1000000)
				{
					$timeout = 'Second-' . ($options['timeout'][0] - time());
				}
				else
				{
					$timeout = 'Second-' . $options['timeout'][0];
				}
			}
			else
			{
				$timeout = 'Infinite';
			}

			self::set_header('Content-Type: text/xml; charset="utf-8"');
			self::set_header("Lock-Token: <".$options['locktoken'].">");
			$strOutput = "<?xml version=\"1.0\" encoding=\"utf-8\"?".">\n" .
				"<D:prop xmlns:D=\"DAV:\">\n" .
				" <D:lockdiscovery>\n" .
				"  <D:activelock>\n" .
				"	<D:lockscope><D:" . htmlspecialcharsEx($options['scope']) . "/></D:lockscope>\n" .
				"	<D:locktype><D:" . htmlspecialcharsEx($options['type']) . "/></D:locktype>\n" .
				"	<D:depth>" . htmlspecialcharsEx($options['depth']) . "</D:depth>\n" .
				"	<D:owner>" . htmlspecialcharsEx($options['owner']) . "</D:owner>\n" .
				"	<D:timeout>" . htmlspecialcharsEx($timeout) . "</D:timeout>\n" .
				"	<D:locktoken><D:href>" . $options['locktoken'] . "</D:href></D:locktoken>\n" .
				"  </D:activelock>\n" .
				" </D:lockdiscovery>\n".
				"</D:prop>\n\n";
			self::set_header('Content-Length: ' . $this->strlen($strOutput));
			echo $strOutput;
		}
	}

	function _get_etag()
	{
		$utag = '';
		if (isset($this->arParams['element_array']))
		{
			$utag = md5(
				$this->arParams['element_array']['ID'].
				$this->arParams['element_array']['NAME'].
				$this->arParams['element_array']['TIMESTAMP_X']);
		} 
		return $utag;
	}

	function _check_etag($reqtag)
	{
		return (trim($reqtag, "\"'") == $this->_get_etag());
	}

	function _new_uuid()
	{
		if (function_exists('uuid_create'))
		{
			return uuid_create();
		}

		$uuid = md5(microtime().getmypid());

		$uuid{12} = '4';
		$n = 8 + (ord($uuid{16}) & 3);
		$hex = '0123456789abcdef';
		$uuid{16} = substr($hex, $n, 1);

		return substr($uuid,  0, 8).'-'.
			substr($uuid,  8, 4).'-'.
			substr($uuid, 12, 4).'-'.
			substr($uuid, 16, 4).'-'.
			substr($uuid, 20);
	}

	function _new_locktoken()
	{
		return 'opaquelocktoken:'.$this->_new_uuid();
	}

	function _check_lock_status($path, $exclusive_only = false)
	{
		if (method_exists($this, 'checkLock'))
		{
			$lock = $this->checkLock($path);

			if (is_array($lock) && count($lock))
			{
				if (!isset($_SERVER['HTTP_IF']) || (strpos($_SERVER['HTTP_IF'], $lock['token']) === false))
				{
					if (  (!$exclusive_only || ($lock['scope'] !== 'shared'))  &&
						($lock['owner'] !== $GLOBALS["USER"]->GetLogin())  )
					{
						return false; // locked
					}
				}
			}
		}
		return true;
	}
	
	function _get_lock_prop()
	{
		return CWebDavBase::_mkprop("supportedlock", 
					"<D:lockentry>
						<D:lockscope><D:exclusive/></D:lockscope>
						<D:locktype><D:write/></D:locktype>
					</D:lockentry>
					<D:lockentry>
						<D:lockscope><D:shared/></D:lockscope>
						<D:locktype><D:write/></D:locktype>
					</D:lockentry>");
	}
	
	function _udecode($t)
	{
		global $APPLICATION;
		$t = rawurldecode($t); //urldecode($t);
		$t = str_replace("%20", " ", $t);
		if (preg_match("/^.{1}/su", $t) == 1 && SITE_CHARSET != "UTF-8")
		{
			$t = $APPLICATION->ConvertCharset($t, "UTF-8", SITE_CHARSET);
			if (preg_match("/^.{1}/su", $t) == 1 ) // IE
				$t = $APPLICATION->ConvertCharset($t, "UTF-8", SITE_CHARSET);
		}
		return $t;
	}
	
	function _uencode($t, $params = array("utf8" => "Y", "convert" => "allowed"))
	{
		global $APPLICATION, $WEBDAV;
		
		$params = (is_array($params) ? $params : array($params));
		$params["utf8"] = ($params["utf8"] == "N" ? "N" : "Y");
		$params["convert"] = (in_array($params["convert"], array("allowed", "full")) ? $params["convert"] : "allowed");
		
		if ($params["convert"] == "allowed")
		{
			foreach ($WEBDAV["ALLOWED_SYMBOLS"] as $symbol)
			{
				$t = str_replace($symbol, urlencode($symbol), $t);
			}
		}
		else 
		{
			if ($params["utf8"] == "Y" && SITE_CHARSET != "UTF-8")
			{
				$t = $APPLICATION->ConvertCharset($t, SITE_CHARSET, "UTF-8");
			}
			if ($params["urlencode"] != "N")
			{
				$t = str_replace(" ", "%20", $t);
				$t = urlencode($t);
				$t = str_replace(array("%2520", "%2F"), array("%20", "/"), $t);
			}
		}
		return $t;
	}

	function base_UNLOCK()
	{

		$token = trim($_SERVER['HTTP_LOCK_TOKEN']);
		if (substr($token, 0, 1) == "<")
			$token = substr($token, 1);
		if (substr($token, (strLen($token) - 1), 1) == ">")
			$token = substr($token, 0, -1);

		$options = array(
			'path' => $this->_path,
			'depth' => (isset($_SERVER['HTTP_DEPTH']) ? $_SERVER['HTTP_DEPTH'] : 'infinity'),
			'token' => $token);

		$stat = $this->UNLOCK($options);
		header('Content-length: 0');
		$this->SetStatus($stat);
	}

	function lockdiscovery($path)
	{
		if (!method_exists($this, 'checklock'))
		{
			return '';
		}

		$activelocks = '';

		$lock = $this->checklock($path);

		if (is_array($lock) && count($lock))
		{
			if (!empty($lock['expires']))
			{
				$timeout = 'Second-' . ($lock['expires'] - time());
			}
			elseif (!empty($lock['timeout']))
			{
				$timeout = 'Second-' . $lock['timeout'];
			}
			else
			{
				$timeout = 'Infinite';
			}

			$activelocks.= '
			<D:activelock>
			<D:lockscope><D:' . $lock['scope'] . '/></D:lockscope>
			<D:locktype><D:' . $lock['type'] . '/></D:locktype>
			<D:depth>' . $lock['depth'] . '</D:depth>
			<D:owner>' . $lock['owner'] . '</D:owner>
			<D:timeout>' . $timeout . '</D:timeout>
			<D:locktoken><D:href>' . $lock['token'] . '</D:href></D:locktoken>
			</D:activelock>
';
		}

		return $activelocks;
	}

	function strlen(&$str)
	{
		return (function_exists('mb_strlen') ? mb_strlen($str, 'latin1') : strlen($str));
	}

	function ThrowError($status, $code, $message='', $line=0)
	{
		$errArr = array(
			'STATUS' => $status,
			'CODE' => $code,
			'MESSAGE' => $message,
			'LINE' => $line
		);
		$GLOBALS['APPLICATION']->ThrowException($message, $code);
		if (CWebDavBase::IsDavHeaders("check_all"))
			$this->SetStatus($status);
		return $status;
	}

	function ThrowAccessDenied($code = 'ACCESS_DENIED', $line=0)
	{
		if (intval($code) > 0)
		{
			$line = $code;
			$code = 'ACCESS_DENIED';
		}
		return $this->ThrowError('403 Forbidden', $code, GetMessage('WD_ACCESS_DENIED'), $line);
	}

	function CheckRights($method = "", $strong = false, $returnCodeError = false)
	{
		if (is_array($method)) return; // TODO: from components - fixit
		if (strlen($method) <= 0)
			$method = $this->http_method;

		$result = true; $errorCode = "";
		if (!array_key_exists($method, $this->allow)) {
			$result = false;
			$errorCode = "WD348";
		} elseif ($this->permission < $this->allow[$method]["min_rights"]) {
			$result = false;
			$errorCode = "WD349";
		} elseif ($strong == true && $this->permission < $this->allow[$method]["rights"]) {
			$result = false;
			$errorCode = "WD350";
		}
		return ($returnCodeError === true && $result === false ? $errorCode : $result);
	}

	function CheckName($name)
	{
		if (in_array(strtolower($name), array(".ds_store", ".trashes")))
		{
			return false;
		}
		if (preg_match($GLOBALS["WEBDAV"]["FORBIDDEN_SYMBOLS_PATTERN"], $name))
		{
			return false;
		}

		return true;
	}

	function CorrectName($name = "", $replace = "_")
	{
		$name = trim($name);
		if(empty($name))
		{
			return $name;
		}
		$pr = 0;
		while(substr($name, 0, 1) == ".")
		{
			$pr++;
			$name = substr($name, 1);
		}
		$po = 0;
		while(substr($name, -1) == ".")
		{
			$po++;
			$name = substr($name, 0, -1);
		}
		$name = str_repeat("_", $pr) . $name . str_repeat("_", $po);
		return preg_replace($GLOBALS["WEBDAV"]["FORBIDDEN_SYMBOLS_PATTERN"], $replace, $name);
	}

	function GetCurrentPath($page)
	{
		$page = str_replace("//", "/", "/".$this->_udecode($page));
		$res = CUtil::ConvertToLangCharset(substr($page, strlen($this->base_url)));
		return $res;
	}

	function IsDavHeaders($params = "empty")
	{
		static $result = array();

		if ( ! isset($result[$params]))
			$result[$params] = self::_isDavHeaders($params);

		return $result[$params];
	}

	function _isDavHeaders($params = "empty")
	{
		$aDavHeaders = array(
			"DAV",
			"IF",
			"DEPTH",
			"OVERWRITE",
			"DESTINATION",
			"LOCK_TOKEN",
			"TIMEOUT",
			"STATUS_URI"
		);

		foreach ($aDavHeaders as $header)
		{
			if (array_key_exists("HTTP_".$header, $_SERVER))
			{
				return true;
			}
		}

		$aDavMethods = array(
			"PROPFIND",
			"PROPPATCH",
			"MKCOL",
			"COPY",
			"MOVE",
			"LOCK",
			"UNLOCK"
		);

		if ($params == "check_options"):
			$aDavMethods[] = "OPTIONS";
		elseif ($params == "check_all"):
			$aDavMethods[] = "OPTIONS";
			$aDavMethods[] = "HEAD";
			$aDavMethods[] = "PUT";
		endif;

		foreach ($aDavMethods as $method)
		{
			if ($_SERVER["REQUEST_METHOD"] == $method)
			{
				return true;
			}
		}

		if (strpos($_SERVER["HTTP_USER_AGENT"], "Microsoft Office") !== false &&
			strpos($_SERVER['HTTP_USER_AGENT'], "Outlook") === false)
		{
			return true;
		}

		return false;
	}

	static function CustomDataCache($path, $id, $value = null, $tags = null, $time=86400)
	{
		static $data = array();
		$CACHE_PATH = str_replace(array("///", "//"), "/", "/".SITE_ID."/".$path."/");
		$CACHE_ID = $id;
		$CACHE_TIME = intval($time);

		static $arOCache = array();
		if (!isset($arOCache[$path]))
			$arOCache[$path] = new CPHPCache;
		$docCache =& $arOCache[$path];

		if ($value === null) // GET
		{
			if (!isset($data[$path][$id]))
			{
				$value = false;
				if ($docCache->InitCache($CACHE_TIME, $CACHE_ID, $CACHE_PATH))
					$value = $docCache->GetVars();
				$data[$path][$id] = $value;
			}

			if (isset($data[$path][$id]))
				return $data[$path][$id];
			else
				return false;
		}
		else
		{
			if (empty($docCache->basedir))
				$docCache->InitCache($CACHE_TIME, $CACHE_ID, $CACHE_PATH);

			if ($docCache->StartDataCache())
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->StartTagCache($CACHE_PATH);
				if (is_string($tags))
					$tags = explode(',', $tags);
				foreach ($tags as $tag)
					$CACHE_MANAGER->RegisterTag(trim($tag));
				$CACHE_MANAGER->EndTagCache();
				$docCache->EndDataCache($value);
			}
		}
	}

	/********** new code **********/

	static function GetIo()
	{
		if(self::$io == null)
		{
			self::$io = CBXVirtualIo::GetInstance();
		}
		return self::$io;
	}

	static function CleanRelativePathString($filePath, $fullPath = null)
	{
		$io = self::GetIo();
		$filePath = $io->CombinePath("/", $filePath);
		if($fullPath !== null)
		{
			//$filePath = $io->CombinePath($fullPath, $filePath);
			$filePath = str_replace(array("///", "//"), "/", $fullPath . "/" .$filePath);
		}
		if(!$io->ValidatePathString($filePath))
		{
			return false;
		}
		return $filePath;
	}

	static function ConvertPathToRelative($filePath, $fullPath)
	{
		$res = $filePath;
		if(strpos($res, $fullPath) === 0)
		{
			$res = substr($res, strlen($fullPath));
		}
		return $res;
	}

	/********** Mime **********/

	static function GetMimeArray()
	{
		static $arMimes = array(

			'html' => array( 'mime' => 'text/html', 'group' => self::MIME_GROUP_ONLY_LOADING ),
			'htm' => array( 'mime' => 'text/html', 'group' => self::MIME_GROUP_ONLY_LOADING ),
			'mht' => array( 'mime' => 'message/rfc822', 'group' => self::MIME_GROUP_ONLY_LOADING ),
			'xhtml' => array( 'mime' => 'application/xhtml+xml', 'group' => self::MIME_GROUP_ONLY_LOADING ),
			'xml' => array( 'mime' => 'application/xml', 'group' => self::MIME_GROUP_ONLY_LOADING ),
			'swf' => array( 'mime' => 'application/x-shockwave-flash', 'group' => self::MIME_GROUP_ONLY_LOADING ),
			'svg' => array( 'mime' => 'image/svg+xml', 'group' => self::MIME_GROUP_ONLY_LOADING ),
			'txt' => array( 'mime' => 'text/plain', 'group' => self::MIME_GROUP_ONLY_LOADING ),
			'pdf' => array( 'mime' => 'application/pdf', 'group' => self::MIME_GROUP_ONLY_LOADING ),

			'rar' => array( 'mime' => 'application/x-rar-compressed', 'group' => self::MIME_GROUP_ARCHIVE ),
			'zip' => array( 'mime' => 'application/zip', 'group' => self::MIME_GROUP_ARCHIVE ),

			'gif' => array( 'mime' => 'image/gif', 'group' => self::MIME_GROUP_IMAGE ),
			'jpg' => array( 'mime' => 'image/jpeg', 'group' => self::MIME_GROUP_IMAGE ),
			'jpeg' => array( 'mime' => 'image/jpeg', 'group' => self::MIME_GROUP_IMAGE ),
			'jpe' => array( 'mime' => 'image/jpeg', 'group' => self::MIME_GROUP_IMAGE ),
			'bmp' => array( 'mime' => 'image/bmp', 'group' => self::MIME_GROUP_IMAGE ),
			'png' => array( 'mime' => 'image/png', 'group' => self::MIME_GROUP_IMAGE ),

			'doc' => array( 'mime' => 'application/msword', 'group' => self::MIME_GROUP_OFFICE ),
			'docm' => array( 'mime' => 'application/vnd.ms-word.document.macroEnabled.12', 'group' => self::MIME_GROUP_OFFICE ),
			'docx' => array( 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'group' => self::MIME_GROUP_OFFICE ),
			'dotm' => array( 'mime' => 'application/vnd.ms-word.template.macroEnabled.12', 'group' => self::MIME_GROUP_OFFICE ),
			'dotx' => array( 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template', 'group' => self::MIME_GROUP_OFFICE ),
			'ppt' => array( 'mime' => 'application/powerpoint', 'group' => self::MIME_GROUP_OFFICE ),
			'potm' => array( 'mime' => 'application/vnd.ms-powerpoint.template.macroEnabled.12', 'group' => self::MIME_GROUP_OFFICE ),
			'potx' => array( 'mime' => 'application/vnd.openxmlformats-officedocument.presentationml.template', 'group' => self::MIME_GROUP_OFFICE ),
			'ppam' => array( 'mime' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12', 'group' => self::MIME_GROUP_OFFICE ),
			'ppsm' => array( 'mime' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12', 'group' => self::MIME_GROUP_OFFICE ),
			'ppsx' => array( 'mime' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow', 'group' => self::MIME_GROUP_OFFICE ),
			'pptm' => array( 'mime' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12', 'group' => self::MIME_GROUP_OFFICE ),
			'pptx' => array( 'mime' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'group' => self::MIME_GROUP_OFFICE ),
			'xls' => array( 'mime' => 'application/vnd.ms-excel', 'group' => self::MIME_GROUP_OFFICE ),
			'xlam' => array( 'mime' => 'application/vnd.ms-excel.addin.macroEnabled.12', 'group' => self::MIME_GROUP_OFFICE ),
			'xlsb' => array( 'mime' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12', 'group' => self::MIME_GROUP_OFFICE ),
			'xlsm' => array( 'mime' => 'application/vnd.ms-excel.sheet.macroEnabled.12', 'group' => self::MIME_GROUP_OFFICE ),
			'xlsx' => array( 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'group' => self::MIME_GROUP_OFFICE ),
			'xltm' => array( 'mime' => 'application/vnd.ms-excel.template.macroEnabled.12', 'group' => self::MIME_GROUP_OFFICE ),
			'xltx' => array( 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template', 'group' => self::MIME_GROUP_OFFICE ),

			'csv' => array( 'mime' => 'application/vnd.ms-excel', 'group' => self::MIME_GROUP_OFFICE ),

			'odt' => array( 'mime' => 'application/vnd.oasis.opendocument.text', 'group' => self::MIME_GROUP_OPEN_OFFICE ),
			'ott' => array( 'mime' => 'application/vnd.oasis.opendocument.text-template', 'group' => self::MIME_GROUP_OPEN_OFFICE ),
			'odg' => array( 'mime' => 'application/vnd.oasis.opendocument.graphics', 'group' => self::MIME_GROUP_OPEN_OFFICE ),
			'otg' => array( 'mime' => 'application/vnd.oasis.opendocument.graphics-template', 'group' => self::MIME_GROUP_OPEN_OFFICE ),
			'odp' => array( 'mime' => 'application/vnd.oasis.opendocument.presentation', 'group' => self::MIME_GROUP_OPEN_OFFICE ),
			'otp' => array( 'mime' => 'application/vnd.oasis.opendocument.presentation-template', 'group' => self::MIME_GROUP_OPEN_OFFICE ),
			'ods' => array( 'mime' => 'application/vnd.oasis.opendocument.spreadsheet', 'group' => self::MIME_GROUP_OPEN_OFFICE ),
			'ots' => array( 'mime' => 'application/vnd.oasis.opendocument.spreadsheet-template', 'group' => self::MIME_GROUP_OPEN_OFFICE ),
			'odc' => array( 'mime' => 'application/vnd.oasis.opendocument.chart', 'group' => self::MIME_GROUP_OPEN_OFFICE ),
			'otc' => array( 'mime' => 'application/vnd.oasis.opendocument.chart-template', 'group' => self::MIME_GROUP_OPEN_OFFICE ),
			'odi' => array( 'mime' => 'application/vnd.oasis.opendocument.image', 'group' => self::MIME_GROUP_OPEN_OFFICE ),
			'oti' => array( 'mime' => 'application/vnd.oasis.opendocument.image-template', 'group' => self::MIME_GROUP_OPEN_OFFICE ),
			'odf' => array( 'mime' => 'application/vnd.oasis.opendocument.formula', 'group' => self::MIME_GROUP_OPEN_OFFICE ),
			'otf' => array( 'mime' => 'application/vnd.oasis.opendocument.formula-template', 'group' => self::MIME_GROUP_OPEN_OFFICE ),
			'odm' => array( 'mime' => 'application/vnd.oasis.opendocument.text-master', 'group' => self::MIME_GROUP_OPEN_OFFICE ),
			'oth' => array( 'mime' => 'application/vnd.oasis.opendocument.text-web', 'group' => self::MIME_GROUP_OPEN_OFFICE ),

		);

		return $arMimes;
	}

	static function GetMimeAndGroup($fullPath)
	{
		$arM = self::GetMimeArray();
		$fExtQ = strtolower(GetFileExtension($fullPath));

		if(array_key_exists($fExtQ, $arM))
		{
			if($arM[$fExtQ]["group"] == self::MIME_GROUP_IMAGE)
			{
				$arF = CFile::MakeFileArray($fullPath);
				$res = CFile::CheckImageFile($arF);
				if(strlen($res) <= 0)
				{
					return $arM[$fExtQ];
				}
			}
			else
			{
				return $arM[$fExtQ];
			}
		}
		return array( 'mime' => 'application/octet-stream', 'group' => self::MIME_GROUP_ONLY_LOADING );
	}

	//Returns FALSE if file must be downloaded, and TRUE if file can be opened
	static function CanViewFile($path, $pathIsShort = true)
	{
		if($pathIsShort)
		{
			$path = $_SERVER["DOCUMENT_ROOT"] . $path;
		}
		$arM = self::GetMimeAndGroup($path);
		$arView = array(self::MIME_GROUP_IMAGE);
		if(in_array($arM["group"], $arView))
		{
			return true;
		}
		return false;
	}

	/********** Mime End **********/

	static function GetClientOS()
	{
		$clientOS = null;
		$client = $_SERVER['HTTP_USER_AGENT'];
		if (preg_match("/(msie) ([0-9]{1,2}.[0-9]{1,3})/i", $client, $match)) {
		$browser['name'] = "MSIE";
		$browser['version'] = $match[2];
		}
		if(preg_match("/linux/i", $client))
		{
			$clientOS = "Linux";
		}
		elseif(preg_match("/(windows nt)( ){0,1}([0-9]{1,2}.[0-9]{1,2}){0,1}/i", $client, $match))
		{
			if (isset($match[3]))
			{
				if ($match[3] == '5.0') $clientOS = "Windows 2000";
				elseif ($match[3] == '5.1') $clientOS = "Windows XP";
				elseif ($match[3] == '5.2') $clientOS = "Windows 2003";
				elseif ($match[3] == '6.0' && strpos($client, 'SLCC1') !== false) $clientOS = "Windows Vista";
				elseif ($match[3] == '6.0' && strpos($client, 'SLCC2') !== false) $clientOS = "Windows 2008";
				elseif ($match[3] == '6.0') $clientOS = "Windows Vista"; // may be 2008
				elseif ($match[3] == '6.1') $clientOS = "Windows 7";
				elseif ($match[3] == '6.2') $clientOS = "Windows 8";
				else $clientOS = "Windows";
			} else {
				$clientOS = "Windows";
			}
		}
		elseif(!!preg_match("/mac/i", $client) || !!preg_match("/darwin/i", $client))
		{
			$clientOS = "Mac";
		}		
		return $clientOS;
	}
	
	function SendFileFromStream($stream, $mimetype, $sizeO = 0, $ranges=array())
	{		
		if((count($ranges) == 0) || (0!==fseek($stream, 0, SEEK_SET)))
		{
			if ($sizeO > 0)
			{
				self::set_header('Content-length: '.$sizeO);
				self::set_header('Content-range: 0-' . ($sizeO-1) . '/'. $sizeO);
			}

			while (@ob_end_clean());
			if(is_resource($stream))
			{
				fpassthru($stream);
			}
			return;
		}
		
		$multipart = (count($ranges) > 1);
		
		if($multipart)
		{
			$this->_multipart_byterange_header();
		}
		$to = 0;
		
		foreach($ranges as $range)
		{
			$isStart = isset($range['start']);
			if($isStart)
			{
				$from = intval($range['start']);
				$to = (!empty($range['end']) && intval($range['end']) > 0) ? intval($range['end']) : $sizeO-1;
			}
			else
			{
				$from = $sizeO - $range['last']-1;
				$to = $sizeO -1;
			}
											
			fseek($stream, $from, SEEK_SET);
			
			$size = $to - $from + 1;
			if($multipart)
			{
				$total = isset($sizeO) ? $sizeO : '*';
				$this->_multipart_byterange_header($mimetype, $from, $to, $total);
			}
			else
			{
				if(feof($stream))
				{
					$this->ThrowError("416 Requested range not satisfiable", 'WEBDAV_RANGES_ERROR', '', __FILE__.' '.__LINE__);
					return;
				}
				
				if($isStart)
				{
					$this->SetStatus('206 partial');
					self::set_header('Content-range: ' . $from . '-' . $to . '/'. $sizeO);
				}
				self::set_header('Content-length: '.$size);
				
				while (@ob_end_clean());
			}

			while($size && !feof($stream))
			{
				$s = ($size < 8192) ? $size : 8192;
				$buffer = fread($stream, $s);
				$size -= $this->strlen($buffer);
				echo $buffer;
			}
		}
		if($multipart)
		{
			$this->_multipart_byterange_header();
		}

	}

	public static function getFoldersMetaData()
	{
		if(static::$foldersMetaData === null)
		{
			static::$foldersMetaData = array(
				static::TRASH => array(
					"name" => ".Trash",
					"alias" => GetMessage("WD_TRASH"),
					"rights" => "W",
					"disable" => "PUT",
					"show_in_list" => false,
					"show_in_list_disk" => false,
					"auto_create" => true,
				),
				static::DROPPED => array(
					"name" => GetMessage("WD_DOWNLOADED"),
					"alias" => GetMessage("WD_DOWNLOADED"),
					"rights" => "U",
					"disable" => "",
					"show_in_list" => true,
					"show_in_list_disk" => false,
					"auto_create" => true,
				),
				static::SAVED => array(
					"name" => GetMessage("WD_SAVED"),
					"alias" => GetMessage("WD_SAVED"),
					"rights" => "U",
					"disable" => "",
					"show_in_list" => true,
					"show_in_list_disk" => true,
					"auto_create" => true,
				),
				static::OLD_DROPPED => array(
					"name" => ".Dropped",
					"alias" => GetMessage("WD_DROPPED"),
					"rights" => "U",
					"disable" => "",
					"show_in_list" => false,
					"show_in_list_disk" => false,
					"auto_create" => false,
				),
			);
		}
		return static::$foldersMetaData;
	}

	protected static function getMetaDataByName($name)
	{
		$foldersMetaData = static::getFoldersMetaData();
		return isset($foldersMetaData[$name])? $foldersMetaData[$name] : array();
	}

	public static function getTrashMetaData()
	{
		return static::getMetaDataByName(static::TRASH);
	}

	public static function getDroppedMetaData()
	{
		return static::getMetaDataByName(static::DROPPED);
	}

	public static function getSavedMetaData()
	{
		return static::getMetaDataByName(static::SAVED);
	}

	public static function getOldDroppedMetaData()
	{
		return static::getMetaDataByName(static::OLD_DROPPED);
	}
}

class __CWebdavRequestParser
{
	var $success = true;
	var $props = false;
	var $_array;
	var $namespaces = array();

	function __CWebdavRequestParser()
	{
	}

	function LoadFromPhpInput()
	{
		$f_in = fopen('php://input', 'r');
		if (!$f_in)
		{
			$this->success = false;
			return false;
		}	

		$xml = '';
		while ($this->success && !feof($f_in))
		{
			$line = fgets($f_in);
			if (is_string($line))
			{
				$xml .= $line;
			}
		}
		fclose($f_in);
		return $this->LoadFromStr($xml);
	}

	function LoadFromStr($strXML)
	{
		$objXML = new CDataXML();
		$objXML->delete_ns = false;

		if (!$objXML->LoadString($strXML))
		{
			$this->success = false;
			return false;
		}

		$this->_array = $objXML->GetArray();
		$this->parse_array($this->_array);
		return true;
	}

	function parse_ns(&$root)
	{
		if (isset($root['@']) && is_array($root['@']) && sizeof($root['@']) > 0)
		{
			foreach ($root['@'] as $n => $v)
			{
				if(strpos($n, 'xmlns:') !== false)
				{
					$this->namespaces[str_replace('xmlns:', '', $n)] = $v;
				}
			}
		}
	}
}

class __CParsePropfind extends __CWebdavRequestParser
{

	function __CParsePropfind()
	{
	}

	function LoadFromStr($strXML)
	{
		if (strlen($strXML) <= 0)
		{
			$this->props = 'all';
			return true;
		}

		$objXML = new CDataXML();
		$objXML->delete_ns = false;

		if (!$objXML->LoadString($strXML))
		{
			$this->success = false;
			return false;
		}

		$this->_array = $objXML->GetArray();
		$this->parse_array($this->_array);
		return $this->success;
	}

	function parse_array(&$array, $depth = 0)
	{
		if (!is_array($array))
			return;

		foreach ($array as $name => $node)
		{
			$ns = '';
			if (isset($node[0]))
			{
				$this->parse_ns($node[0]);
			}
			else
			{
				$this->parse_ns($node);
			}

			if (strpos($name, ':') !== false)
			{
				foreach ($this->namespaces as $nscode => $ns)
				{
					if (strpos($name, $nscode.':') !== false)
					{
						$name = str_replace($nscode.':', '', $name);
						break;
					}
				}
				if (strlen($ns) <= 0)
				{
					$this->success = false;
					return ;
				}
			}
			else
			{
				$ns = 'DAV:';
			}


			if ($depth == 1)
			{
				if ($name == 'allprop')
					$this->props = 'all';
				if ($name == 'propname')
					$this->props = 'names';
			}

			if ($depth == 2)
			{
				$prop = array('name' => $name);
				if ($ns)
				{
					$prop['xmlns'] = $ns;
				}
				$this->props[] = $prop;
			}

			if (array_key_exists('#', $node) && is_array($node['#']) && sizeof($node['#']) > 0)
			{
				$this->parse_array($node['#'], $depth + 1);
			}
			elseif (isset($node[0]['#']) && is_array($node[0]['#']) && sizeof($node[0]['#']) > 0)
			{
				$this->parse_array($node[0]['#'], $depth + 1);
			}
		}
	}
}

class __CParseProppatch extends __CWebdavRequestParser
{
	var $mode;
	var $current;

	function __CParseProppatch()
	{
	}

	function parse_array(&$array, $depth = 0)
	{
		if (!is_array($array))
			return;
		foreach ($array as $name => $node)
		{
			$ns = '';
			if (isset($node[0]))
			{
				$this->parse_ns($node[0]);
			}
			else
			{
				$this->parse_ns($node);
			}
			if (strpos($name, ':') !== false)
			{
				foreach ($this->namespaces as $nscode => $ns)
				{
					if (strpos($name, $nscode.':') !== false)
					{
						$name = str_replace($nscode.':', '', $name);
						break;
					}
				}
				if (strlen($ns) <= 0)
				{
					$this->success = false;
					return ;
				}
			}

			if ($depth == 1)
			{
				$this->mode = $name;
			}

			if ($depth == 3)
			{
				$val = '';
				if (isset($node[0]['#']) && is_scalar($node[0]['#']))
				{
					$val = $node[0]['#'];
				}
				$this->props[] = array(
					'name' => $name,
					'ns' => $ns,
					'status'=> 200,
					'val' => $val);
			}

			if (array_key_exists('#', $node) && is_array($node['#']) && sizeof($node['#']) > 0)
			{
				$this->parse_array($node['#'], $depth + 1);
			}
			elseif (isset($node[0]['#']) && is_array($node[0]['#']) && sizeof($node[0]['#']) > 0)
			{
				$this->parse_array($node[0]['#'], $depth + 1);
			}
		}
	}
}

class __CParseLockinfo extends __CWebdavRequestParser
{
	var $locktype = '';
	var $lockscope = '';
	var $owner = '';
	var $collect_owner = false;

	function __CParseLockinfo()
	{
	}

	function parse_array(&$array, $depth = 0)
	{
		if (!is_array($array))
			return ;
		foreach ($array as $name => $node)
		{
			$ns = '';
			if (isset($node[0]))
			{
				$this->parse_ns($node[0]);
			}
			else
			{
				$this->parse_ns($node);
			}

			if (strpos($name, ':') !== false)
			{
				foreach ($this->namespaces as $nscode => $ns)
				{
					if (strpos($name, $nscode.':') !== false)
					{
						$name = str_replace($nscode.':', '', $name);
						break;
					}
				}
				if (strlen($ns) <= 0)
				{
					$this->success = false;
					return ;
				}
			}

			switch ($name)
			{
				case 'write':
					$this->locktype = $name;
					break;
				case 'exclusive':
				case 'shared':
					$this->lockscope = $name;
					break;
				case 'owner':
					if (is_array($node[0]["#"]) && array_key_exists("D:href", $node[0]["#"]))
						$node = $node[0]["#"]["D:href"];
					$slashPos = strpos($node[0]['#'], '\\');
					if ($slashPos === false)
					{ 
						$this->owner = $node[0]['#'];
					} 
					else 
					{
						$this->owner = substr($node[0]['#'], $slashPos+1);
					}

					break;
			}


			if (array_key_exists('#', $node) && is_array($node['#']) && sizeof($node['#']) > 0)
			{
				$this->parse_array($node['#'], $depth + 1);
			}
			elseif (isset($node[0]['#']) && is_array($node[0]['#']) && sizeof($node[0]['#']) > 0)
			{
				$this->parse_array($node[0]['#'], $depth + 1);
			}
		}
	}
}