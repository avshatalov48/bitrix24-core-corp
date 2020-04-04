<?
IncludeModuleLangFile(__FILE__);

class CWebDavExtLinks
{

	const MODULE_NAME = "webdav";
	const SESSION_UIN = "webdav.extlinks";
	const SESSION_OPTIONS = "arOptions";
	const HASH_LEN = 32;
	const URL_DEF = "/docs/pub";
	const DEMO_HASH = "c35c8b730b059659";
	const EXT_LINKS_TABLE = "b_webdav_ext_links";
	const LINK_TYPE_MANUAL = 'M';
	const LINK_TYPE_AUTO = 'A';
	const LIFETIME_TYPE_AUTO = 15; //minutes

	static protected $arTableFields = array(
		"URL",
		"RESOURCE_TYPE",
		"FOLDER",
		"IBLOCK_TYPE",
		"IBLOCK_ID",
		"BASE_URL",
		"HASH",
		"CREATION_DATE",
		"USER_ID",
		"SALT",
		"PASSWORD",
		"LIFETIME",
		"F_SIZE",
		"DESCRIPTION",
		"ROOT_SECTION_ID",
		"URL_HASH",
		'SINGLE_SESSION',
		'LINK_TYPE',
		'DOWNLOAD_COUNT',
		'VERSION_ID',
		'ELEMENT_ID',
		'FILE_ID',
	);
	static protected $url = null;

	static $icoRepStr = '<span id="ext-link-icon" style="display:none;"></span>';

	static $maxSizeForView = 4194304; //4*1024*1024 b
	static $urlGoogleViewer = 'https://drive.google.com/viewerng/viewer?embedded=true&url=';
	//todo refactor. Move to webdav.doc.edit.google component
	static $convertFormatInGoogle = array(
		'doc'  => 'docx',
		'.doc' => '.docx',
		'xls'  => 'xlsx',
		'.xls' => '.xlsx',
		'ppt'  => 'pptx',
		'.ppt' => '.pptx',
	);
	static $allowedExtensionsGoogleViewer = array(
		'txt',
		'css',
		//'html',
		'php',
		'c',
		'cpp',
		'h',
		'hpp',
		'rtf',
		'doc',
		'docx',
		'xls',
		'xlsx',
		'pptx',
		'ppt',
		'pdf',
		'ai',
//		'psd',
		'tiff',
		'dxf',
		'svg',
		'eps',
		'ps',
		'ttf',
		'xps',
//		'zip',
//		'rar'
	);

	static $linkTypes = array(
		self::LINK_TYPE_AUTO => self::LINK_TYPE_AUTO,
		self::LINK_TYPE_MANUAL => self::LINK_TYPE_MANUAL,
	);

	protected static function ErrMess($f, $l)
	{
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/" . self::MODULE_NAME . "/install/version.php");
		return "<br>Module: " . self::MODULE_NAME . " <br>Class: " . __CLASS__ . "<br>File: " . __FILE__ . "<br>Function: $f<br>Line: $l";
	}

	protected static function GetUserID($userID = null)
	{
		$userID = intval($userID);
		if($userID > 0)
		{
			return $userID;
		}
		elseif(array_key_exists("USER", $GLOBALS))
		{
			if($GLOBALS["USER"]->IsAuthorized())
			{
				return intval($GLOBALS["USER"]->GetID());
			}
		}
		return 0;
	}

	public static function IsAdmin($userId = null)
	{
		$result = false;
		if($userId === null)
		{
			$user = self::GetCurrentUser();
			if($user->IsAdmin())
			{
				return true;
			}
			$userId = $user->getId();
		}

		$userId = (int)$userId;
		if($userId <= 0)
		{
			return false;
		}

		try
		{
			if(IsModuleInstalled('bitrix24') && CModule::IncludeModule('bitrix24'))
			{
				if(class_exists('CBitrix24') && method_exists('CBitrix24', 'IsPortalAdmin'))
				{
					// New style check
					$result = CBitrix24::IsPortalAdmin($userId);
				}
				else
				{
					// HACK: Check user group 1 ('Portal admins')
					$arGroups = CUser::GetUserGroup($userId);
					$result = in_array(1, $arGroups);
				}
			}
		}
		catch(Exception $e)
		{
		}

		return $result;
	}

	private static function getCurrentUser()
	{
		return isset($USER) && ((get_class($USER) === 'CUser') || ($USER instanceof CUser))
			? $USER : new CUser();
	}

	static function VerifyFields($arFieldsNames)
	{
		$res = array_intersect($arFieldsNames, self::$arTableFields);
		return $res;
	}

	static function GetUrl()
	{
		if(self::$url == null)
		{
			self::$url = COption::GetOptionString("webdav", "webdav_ext_links_url", null);
			$urlOld = COption::GetOptionString("webdav", "webdav_ext_links_old", null);
			if(self::$url == null)
			{
				self::$url = self::URL_DEF;
				COption::SetOptionString("webdav", "webdav_ext_links_url", self::$url);
			}
			if(self::$url != $urlOld)
			{				
				COption::SetOptionString("webdav", "webdav_ext_links_old", self::$url);
				self::UpdateUrlRewrite(self::$url);
			}
		}
		return self::$url;
	}

	static function UpdateUrlRewrite($url)
	{
		CUrlRewriter::Delete(array("ID" => "bitrix:webdav.extlinks"));
		$arU = array(
			"CONDITION"	=>	"#^" . $url . "/#",
			"RULE"	=>	"",
			"ID"	=>	"bitrix:webdav.extlinks",
			"PATH"	=>	$url . "/extlinks.php",
		);
		CUrlRewriter::Add($arU);
	}

	static function GetDemoFileName()
	{	
		$lang = defined("LANGUAGE_ID") ? LANGUAGE_ID : "en";		
		return "bitrix24" . $lang . ".docx";
	}

	static function GetDemoLoadFileUrl()
	{
		$url = self::GetFullURL("/bitrix/components/bitrix/webdav.extlinks/demo/" . self::GetDemoFileName());
		return $url;
	}

	static function GetDemoList()
	{
		$arT = array(
			"URL" => "/" . self::GetDemoFileName(),
			"F_SIZE" => 125589,
			"DESCRIPTION" => GetMessage("WD_EXT_LINKS_DEMO_DOCUMENT_DESCRIPTION"),
			"PASSWORD" => null,
			"HASH" => self::DEMO_HASH,
		);
		$arr = array();
		$arr[] = $arT;
		$rs = new CDBResult;
		$rs->InitFromArray($arr);
		return $rs;
	}

	static function __SortMethod($a, $b)
	{
		return ($a["PRIORITY"] < $b["PRIORITY"]) ? -1 : 1;
	}

	static function PrintDialogDiv($ob)
	{
		$url = $ob->_path;
		$urlFull = $ob->base_url_full . "/";
		$fileOptT = CWebDavExtLinks::GetFileOptions($ob);


		$GLOBALS["APPLICATION"]->RestartBuffer();
		$resUrl = self::GetList(array("URL" => $url, "BASE_URL" => $ob->base_url, "ONLY_CURRENT_USER" => true,));
		$linksNum = 0;
		$arLinks = array();
		$description = "";
		if(array_key_exists("DESCRIPTION", $fileOptT) && strlen($fileOptT["DESCRIPTION"]) > 0)
		{
			$description = HTMLToTxt($fileOptT["DESCRIPTION"]);
		}
		$arUsers = array();
		$currUserID = self::GetUserID();
		while($arU = $resUrl->Fetch())
		{
/*	
'URL' => '/1.doc',
'RESOURCE_TYPE' => 'IBLOCK',
'FOLDER' => NULL,
'IBLOCK_TYPE' => 'library',
'IBLOCK_ID' => '19',
'BASE_URL' => '/docs/shared',
'HASH' => 'f9b22cf913a644c0d23b0c88e3c0407e',
'CREATION_DATE' => '1353762751',
'USER_ID' => '1',
'PASSWORD' => 'e10adc3949ba59abbe56e057f20f883e',
'LIFETIME' => '1669122751',
*/
			$priority = 0;
			$arCurrStr = array( "HASH" => $arU["HASH"]);
			if(($arU["LIFETIME"] - time()) < 31536000) //365*24*60*60
			{
				$arCurrStr["TIME_LEFT"] = GetTime($arU["LIFETIME"] + CTimeZone::GetOffset(), "FULL");
				$priority++;
			}
			if($arU["PASSWORD"] != null)
			{
				$arCurrStr["PASSWORD"] = true;
				$priority += 2;
			}
			$arUsers[] = $arCurrStr["USER_ID"] = intval($arU["USER_ID"]);

			if($arCurrStr["USER_ID"] == $currUserID)
			{
				$priority -= 100;
			}

			$arCurrStr["PRIORITY"] = $priority;
			$arLinks[] = $arCurrStr;
			$linksNum++;
		}

		$arProfilesLinks = array();
		if(self::IsAdmin())
		{
			$arProfilesLinks = self::GetUserProfilesLinks($arUsers);
		}



		usort($arLinks, array(self, '__SortMethod'));
		$fileName = htmlspecialcharsbx(GetFileName(CHTTP::urndecode($url)));
		$size = "";
		$sizeI = intval($fileOptT["F_SIZE"]);
		if($sizeI > 0)
		{
			$size .= " (" . htmlspecialcharsbx(CFile::FormatSize($sizeI)) . ")";
		}	
		$changeTime = "";
		if(strlen($fileOptT["CHANGE_TIME"]) > 0)
		{
			$changeTime = " " . GetMessage("WD_EXT_LINKS_DIALOG_CHANGE_TIME") . " " . $fileOptT["CHANGE_TIME"];
		}

		$demoLink = self::GetFullURL(self::GetUrl() . "/" . self::DEMO_HASH . '/' . self::GetDemoFileName());

?>		
	<div class="ext-link-dialog-content">
		<div class="ext-link-section">
			<span><span class="ext-link-dialog-file-name"><? echo $fileName; ?></span><? echo $size . $changeTime; ?></span>
		</div>

<?
		if(self::IsFirstView())
		{
?>
		<div id="ext-link-green-window">
			<div class="ext-link-dialog-wrap">
				<div class="ext-link-dialog-info-block">
					<div class="ext-link-dialog-img"></div>
					<div class="ext-link-dialog-title"><? echo GetMessage("WD_EXT_LINKS_DIALOG_GREEN_WINDOW_TITLE");?></div>
					<div class="ext-link-dialog-text">
						<? echo GetMessage("WD_EXT_LINKS_DIALOG_GREEN_WINDOW_TEXT");?>
					</div>
					<a href="<? echo $demoLink; ?>" class="ext-link-dialog-link"><? echo GetMessage("WD_EXT_LINKS_DIALOG_GREEN_WINDOW_LINK");?></a>
					<span class="ext-link-dialog-close-btn" onclick="ExtLinkDialogCloseGreenWindow(this)"></span>
				</div>
			</div>
		</div>
<?
		}
		if($linksNum > 0)
		{
?>
		<div id="ext-link-section" class="ext-link-section">
		<div class="ext-link-list-spoiler-div">
			<table class="ext-link-list-spoiler">
				<thead onclick="ExtLinkDialogInitSpoiler(this)">
					<tr>
						<th>
							<div><? echo ( str_replace("#n#", '<span>(<span id="ext-link-spoiler-amount">' . $linksNum . '</span>)</span>', GetMessage("WD_EXT_LINKS_DIALOG_SPOILER_TITLE")) ); ?></div>
						</th>
					</tr>
				</thead>
				<tbody style="display:none;" class="learning-spoiler">
					<tr>
						<td>
							<div id="ext-link-list-div" class="ext-link-list-div">
<?
			$n = 0;
			foreach($arLinks as $v)
			{
				$n++;
				$fileNameT = str_replace(" ", "_", $fileName);
				$urlT = self::GetFullExternalURL() . $v["HASH"] . "/" . $fileNameT;
				$optionsStr = '';
				if(array_key_exists("TIME_LEFT", $v))
				{
					$optionsStr .= "  " . GetMessage("WD_EXT_LINKS_DIALOG_TIME_LEFT") . $v["TIME_LEFT"] . ".";
				}
				if(array_key_exists("PASSWORD", $v))
				{
					$optionsStr .= "  " . GetMessage("WD_EXT_LINKS_DIALOG_PASSWORD");
				}
				if(isset($arProfilesLinks[$v["USER_ID"]]))
				{
					$optionsStr .= " " .GetMessage("WD_EXT_LINKS_DIALOG_USER_NAME") . ": " . $arProfilesLinks[$v["USER_ID"]];
				}
				if(strlen($optionsStr) > 0)
				{
					$optionsStr = '<span class="ext-link-list-options">' . $optionsStr . '</span>';
				}
				echo '
								<p id="ext-link-list-p-' . $n . '" class="ext-link-list-row">
									<span class="ext-link-list-options-dash">&ndash;</span>
									<span class="ext-link-list-link">' . htmlspecialcharsbx($urlT) . '
									<span class="ext-link-list-delete" onclick="ExtLinkDialogDeleteLink(\'' . htmlspecialcharsbx($urlFull) . '\',\'ext-link-list-p-' . $n . '\', {IFRAME: \'Y\', DeleteLink: \'' . $v["HASH"] . '\'})"></span>
									</span>' . $optionsStr . '
								</p>
				';
			}
?>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							<span class="ext-link-delite-all" onclick="ExtLinkDialogDeleteAllLinks('<? echo htmlspecialcharsbx($urlFull); ?>','<? echo htmlspecialcharsbx($url); ?>')"><span class="ext-link-comments-link-text"> <? echo GetMessage("WD_EXT_LINKS_DIALOG_DELETE_ALL_LINKS"); ?></span><i></i></span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		</div>
<?
		}
?>
		<div id="ext-link-time-div" class="ext-link-section">
			<input name="ext-link-time-cb" class="ext-link-time-checkbox" type="checkbox" id="ext-link-time-cb"/><label for="ext-link-time-cb"><? echo GetMessage("WD_EXT_LINKS_DIALOG_FILE_ACCESS_TIME_TITLE"); ?></label>
			<span class="mdl-pwd" id="e2p">
				<span class="ext-link-dash">&ndash;</span>
				<input name="ext-link-time-inp" id="ext-link-time-inp" type="text"/>
				<select name="ext-link-time-sel" id="ext-link-time-sel">
					<option value="day"><? echo GetMessage("WD_EXT_LINKS_DIALOG_FILE_ACCESS_TIME_DAY"); ?></option>
					<option value="hour" selected><? echo GetMessage("WD_EXT_LINKS_DIALOG_FILE_ACCESS_TIME_HOUR"); ?></option>
					<option value="minute"><? echo GetMessage("WD_EXT_LINKS_DIALOG_FILE_ACCESS_TIME_MIN"); ?></option>
				</select>
			</span>
		</div>
		<div id="ext-link-pass-div" class="ext-link-section">
			<input name="ext-link-pass-cb" id="ext-link-pass-cb" class="ext-link-time-checkbox" type="checkbox"/><label for="ext-link-pass-cb"><? echo GetMessage("WD_EXT_LINKS_DIALOG_FILE_ACCESS_PASS_TITLE"); ?></label>
			<div class="mdl-pwd" id="e3p">
				<span><? echo GetMessage("WD_EXT_LINKS_DIALOG_FILE_ACCESS_PASS1"); ?>:</span><input name="ext-link-pass-inp1" class="ext-link-pass-inp" id="ext-link-pass-inp1" type="password" onKeyUp="onKeyPress();"/><br/>
				<span><? echo GetMessage("WD_EXT_LINKS_DIALOG_FILE_ACCESS_PASS2"); ?>:</span><input name="ext-link-pass-inp2" class="ext-link-pass-inp" id="ext-link-pass-inp2" type="password" onKeyUp="onKeyPress();"/>
					<div id="ext-link-pass-ico-ok"></div>
					<div id="ext-link-pass-text-wrong"><? echo GetMessage("WD_EXT_LINKS_DIALOG_PASS_WRONG"); ?></div>
					<div id="ext-link-pass-text-empty"><? echo GetMessage("WD_EXT_LINKS_DIALOG_PASS_EMPTY"); ?></div>
			</div>
		</div>

		<div class="ext-link-comments">
			<span id="ext-link-comments-link" class="ext-link-comments-link" onClick="ExtLinkDialogAddDescription();"><span class="ext-link-comments-link-text"><? echo GetMessage("WD_EXT_LINKS_DIALOG_ADD_COMENT"); ?></span></span>
			<textarea id="text-link-comments-textarea" class="text-link-comments-textarea ext-link-hidden-comments"></textarea>
			<textarea id="text-link-comments-textarea2" style="display:none;"><? echo $description; ?></textarea>
			<span id="ext-link-comments-link-remove" class="ext-link-comments-link-remove ext-link-hidden-comments" onClick="ExtLinkDialogDeleteDescription();"><span class="ext-link-comments-link-text"><? echo GetMessage("WD_EXT_LINKS_DIALOG_DELETE_COMENT"); ?></span></span>
			</div>
		<div id="ext-link-url-div" class="ext-link-section ext-link-link ext-link-hidden">
			<input id="ext-link-res-url" type="text" class="mdl-result"/>
		</div>
		<div id="ext-link-white-block" class="ext-link-white-block"></div>
	</div>

<?
		die();

	}

	static function GetHttpHostUrl()
	{
		$protocol = (CMain::IsHTTPS() ? "https" : "http");
		$host = $_SERVER['HTTP_HOST'];
		$port = $_SERVER['SERVER_PORT'];
		if($port <> 80 && $port <> 443 && $port > 0 && strpos($host, ":") === false)
		{
			$host .= ":".$port;
		}
		elseif($protocol == "http" && $port == 80)
		{
			$host = str_replace(":80", "", $host);
		}
		elseif($protocol == "https" && $port == 443)
		{
			$host = str_replace(":443", "", $host);
		}

		return ($protocol . "://" . $host);
	}

	static function GetFullExternalURL()
	{
		$fullExtURL = self::GetHttpHostUrl() . self::GetUrl() . "/";
		return $fullExtURL;
	}

	static function GetFullURL($p)
	{
		$fullURL = self::GetHttpHostUrl() . $p;
		return $fullURL;
	}

	protected function GetUserProfilesLinks($arUsers)
	{
		$path = trim(COption::GetOptionString("intranet", "path_user", "", SITE_ID));		
		/* /company/personal/user/#USER_ID#/ */
		if($path == "")
		{
			return array();
		}

		$arUsers = array_unique($arUsers);
		$strUsers = implode("|", $arUsers);
		$arProfilesLinks = array();

		$f = "ID";
		$o = "asc";
		$rs = CUser::GetList($f, $o, array( "ID" => $strUsers), array("FIELDS"=>array("NAME","LAST_NAME","SECOND_NAME","LOGIN","EMAIL","ID")));
		$nameFormat = CSite::GetNameFormat();
		while($ar = $rs->Fetch())
		{
			$url = str_replace("#USER_ID#", $ar["ID"], $path);
			$name = CUser::FormatName($nameFormat, $ar);
			$arProfilesLinks[$ar["ID"]] = '<a href="' . self::GetFullURL($url) . '"> ' . $name . ' </a>';
		}

		return $arProfilesLinks;
	}

	function IsFirstView($userID = null)
	{
		$userID = self::GetUserID($userID);
		$OpFV = CUserOptions::GetOption("webdav", "ext-link-dialog-first-run", false, $userID);
		if($OpFV != "OK")
		{
			CUserOptions::SetOption("webdav", "ext-link-dialog-first-run", "OK", false, $userID);
			return true;
		}
		return false;
	}

	function GetExtensionIcon($urlT)
	{
		$arIco = array(
			"doc" => "doc.jpg",
			"docx" => "doc.jpg",
			"pdf" => "pdf.jpg",
			"pic.jpg" => "pdf.jpg",
			"ppt" => "ppt.jpg",
			"pptx" => "ppt.jpg",
			"xls" => "xls.jpg",
			"xlsx" => "xls.jpg",
			"txt" => "txt.jpg",
			/*'rar' => 'rar.png',
			'zip' => 'zip.png',*/
		);
		$res = "empty.jpg";

		$arIm = explode(",", CFile::GetImageExtensions());
		foreach($arIm as $ci)
		{
			if(strlen($ci) > 0)
			{
				$arIco[$ci] = "pic.jpg";
			}
		}
		$fExtQ = strtolower(GetFileExtension($urlT));
		if(array_key_exists($fExtQ, $arIco))
		{
			$res = $arIco[$fExtQ];
		}

		return $res;
	}

	function GetChangeTime($arT)
	{
		$t1 = "";
		$t2 = "";
		$ts1 = 0;
		$ts2 = 0;
		if(array_key_exists("TIMESTAMP_X", $arT) && strlen($arT["TIMESTAMP_X"]) > 0)
		{
			$t1 = $arT["TIMESTAMP_X"];
			$ts1 = MakeTimeStamp($arT["TIMESTAMP_X"]);
		}
		if(array_key_exists("FILE", $arT) && array_key_exists("TIMESTAMP_X", $arT["FILE"]) && strlen($arT["FILE"]["TIMESTAMP_X"]) > 0)
		{
			$t2 = $arT["FILE"]["TIMESTAMP_X"];
			$ts2 = MakeTimeStamp($arT["FILE"]["TIMESTAMP_X"]);
		}

		if($ts1 > $ts2)
		{
			return FormatDateFromDB($t1);
		}
		else
		{
			return FormatDateFromDB($t2);
		}

	}

	protected function checkHash($string)
	{
		return preg_match('/^[0-9a-f]{16,32}$/i', $string);
	}

	function GenerateHash($val=null, $salt="")
	{
		if($val == null)
		{
			$val = time() . $GLOBALS["APPLICATION"]->GetServerUniqID();
		}
		$hash = md5($val . $salt);
		return $hash;
	}

	function GenerateExtLinkHash()
	{
		do
		{
			$hash = substr(self::GenerateHash(), 0, self::HASH_LEN);
			$isNotUnique = self::CheckLink($hash);
		}
		while($isNotUnique);
		return $hash;
	}

	function GetLifeTime($options)
	{
		$n = intval($options["LIFETIME_NUMBER"]);
		$r = 0;
		$max = 315360000; //10*365*24*60*60
		if(array_key_exists("LIFETIME_TYPE", $options))
		{
			switch($options["LIFETIME_TYPE"])
			{
				case 'day':
					$r = $n*86400; //24*60*60
					break;
				case 'hour':
					$r = $n*3600; //60*60
					break;
				case 'minute':
					$r = $n*60;
					break;
				default:
					return $max;
			}
		}
		if($r <= 0 || $r > $max)
		{
			return $max;
		}
		return  $r;
	}

	function GetHashFromURL($link = '')
	{
		if(!$link)
		{
			$link = $_SERVER['REQUEST_URI'];
		}
		$partUri = substr($link, strpos($link, self::GetUrl() . "/"));
		$partUri = substr($partUri, strlen(self::GetUrl() . "/"));
		foreach (explode('/', $partUri) as $part)
		{
			if(self::checkHash($part))
			{
				return $part;
			}
		}
		unset($part);

		return false;
	}

	function GetList($arFilter, $arFields = array(), $arOptions = array())
	{
		global $DB;
		$fields = "*";
		$groupBy = "";
		if(isset($arFilter["HASH"]) && self::DEMO_HASH == $arFilter["HASH"])
		{
			return self::GetDemoList();
		}
		$arSqlSearch = Array();
		if(!is_array($arFilter))
		{
			$arFilter = Array();
		}
		if(!array_key_exists("LINK_TYPE", $arFilter))
		{
			$arFilter['LINK_TYPE'] = self::LINK_TYPE_MANUAL;
		}

		if(array_key_exists("ONLY_CURRENT_USER", $arFilter) && !self::IsAdmin())
		{
			$arFilter["USER_ID"] = self::GetUserID();
		}
		if(array_key_exists("BASE_URL", $arFilter) && array_key_exists("URL", $arFilter) && is_string($arFilter["BASE_URL"]) && is_string($arFilter["URL"]))
		{
			$arFilter["URL_HASH"] = md5($arFilter["BASE_URL"] . $arFilter["URL"]);
			unset($arFilter["URL"]);
			unset($arFilter["BASE_URL"]);
		}
		if(is_array($arFields) && count($arFields) > 0)
		{
			$arFieldsV = self::VerifyFields($arFields);
			if(count($arFieldsV) > 0)
			{
				$fields = implode(",", $arFieldsV);
			}
		}
		if(isset($arOptions["COUNT"]) && $arOptions["COUNT"] === true)
		{
			$groupBy = $fields;
			$fields = ($fields == "*" ? "" : $fields . ", " ) . "count( " . $fields . " ) AS CT";
		}

		foreach($arFilter as $key => $val)
		{
			if((is_array($val) && count($val) <= 0) || (!is_array($val) && strlen($val) <= 0))
			{
				continue;
			}
			$key = strtoupper($key);
			if (is_array($val))
			{
				$val = implode(" | ",$val);
			}
			$strSqlSearch2 = "";
			switch($key)
			{
				case "LINK_TYPE":
					$arSqlSearch[] = GetFilterQuery("EL.LINK_TYPE", $val, "N");
					break;
				case "URL_HASH":
					$arSqlSearch[] = GetFilterQuery("EL.URL_HASH", $val, "N");
					break;
				case "URL":
					$arSqlSearch[] = ("EL.URL = '" .  $DB->ForSql($val) . "'");
					break;
				case "BASE_URL":
					$arSqlSearch[] = ("EL.BASE_URL = '" .  $DB->ForSql($val) . "'");
					break;
				case "HASH":
					$arSqlSearch[] = GetFilterQuery("EL.HASH", $val, "N");
					break;
				case "RESOURCE_TYPE":
					$arSqlSearch[] = GetFilterQuery("EL.RESOURCE_TYPE", $val, "N");
					break;
				case "ACTUAL":
					$strSqlSearch2 .= "
				AND EL.LIFETIME >= ".time();
					break;
				case "USER_ID":
					$arSqlSearch[] = GetFilterQuery("USER_ID", $val, "N");
					break;
			}	
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);

		if(strlen($groupBy) > 0)
		{
			$groupBy = "GROUP BY " . $groupBy;
		}

		$strSql = "
			SELECT
				$fields
			FROM
				" . self::EXT_LINKS_TABLE . " EL
			WHERE
			$strSqlSearch
			$strSqlSearch2
			$groupBy";
		$rs = $DB->Query($strSql, false, self::ErrMess( __FUNCTION__ , __LINE__ ));
		return $rs;
	}

	function CheckLink($hash)
	{
		$res = self::GetList(array("HASH" => $hash));
		if($res->Fetch())
		{
			return true;
		}
		return false;
	}

	function Delete($arFilter)
	{
		global $DB;
		$arSqlSearch = Array();
		if(!is_array($arFilter))
		{
			$arFilter = Array();
		}
		if(!array_key_exists("USER_ID", $arFilter) && empty($arFilter['SKIP_USER_FILTER']))
		{
			$arFilter["USER_ID"] = self::GetUserID();
		}
		foreach($arFilter as $key => $val)
		{
			if((is_array($val) && count($val) <= 0) || (!is_array($val) && strlen($val) <= 0))
			{
				continue;
			}
			$key = strtoupper($key);
			if (is_array($val))
			{
				$val = implode(" | ",$val);
			}
			switch($key)
			{
				case "LINK_TYPE":
					$arSqlSearch[] = GetFilterQuery("LINK_TYPE", $val, "N");
					break;
				case "IRRELEVANT":
					$arSqlSearch[] = 'LIFETIME < ' . strtotime('tomorrow');
					break;
				case "URL_HASH":
					$arSqlSearch[] = GetFilterQuery("URL_HASH", $val, "N");
					break;
				case "USER_ID":
					$arSqlSearch[] = GetFilterQuery("USER_ID", $val, "N");
					break;
				case "SINGLE_SESSION":
					$arSqlSearch[] = GetFilterQuery("SINGLE_SESSION", $val, "N");
					break;
				case "HASH":
					$arSqlSearch[] = GetFilterQuery("HASH", $val, "N");
					break;
				case "URL":
					$arSqlSearch[] = ("URL = '" .  $DB->ForSql($val) . "'");
					break;
				case "BASE_URL":
					$arSqlSearch[] = ("BASE_URL = '" .  $DB->ForSql($val) . "'");
					break;
			}	
		}
		if(count($arSqlSearch) <= 0)
		{
			return false;
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);

		$strSql = "DELETE FROM " . self::EXT_LINKS_TABLE . " WHERE $strSqlSearch";
		$DB->Query($strSql, false, self::ErrMess( __FUNCTION__ , __LINE__ ));
		return true;
	}

	function DeleteSingleSessionLink($hash)
	{
		if(empty($hash) || !is_string($hash))
		{
			return false;
		}
		$filter = array(
			'HASH' => $hash,
			'SINGLE_SESSION' => 1,
			'SKIP_USER_FILTER' => true,
		);
		self::Delete($filter);
	}

	function DeleteLink($hash, $userID = null)
	{
		if(strlen($hash) <= 0)
		{
			$GLOBALS["APPLICATION"]->RestartBuffer();
			echo "error";
			die();
		}
		$arFilter = array("HASH" => $hash);
		if(!self::IsAdmin())
		{
			$arFilter["USER_ID"] = self::GetUserID($userID);
		}
		self::Delete($arFilter);
		$GLOBALS["APPLICATION"]->RestartBuffer();
		echo "ok";
		die();
	}

	function DeleteAllLinks($url, $ob, $userID = null)
	{			
		if(strlen($url) <= 0)
		{
			$GLOBALS["APPLICATION"]->RestartBuffer();
			echo "error";
			die();
		}

		$url = self::ConvertCharsetIfNeed($url);
		$hash = md5($ob->base_url . $url);
		$arFilter = array("URL_HASH" => $hash);
		if(!self::IsAdmin())
		{
			$arFilter["USER_ID"] = self::GetUserID($userID);
		}
		self::Delete($arFilter);
		$GLOBALS["APPLICATION"]->RestartBuffer();
		echo "ok";
		die();
	}

	static function ConvertCharsetIfNeed($t)
	{
		$charsetFrom = CUtil::DetectUTF8($t) ? "utf-8" : 'windows-1251';
		$charsetTo = (defined('BX_UTF') && BX_UTF == true) ? "utf-8" : 'windows-1251';
		if($charsetFrom !== $charsetTo)
		{
			$t = CharsetConverter::ConvertCharset($t, $charsetFrom, $charsetTo);
		}
		return $t;
	}

	//$options = array("URL", "IBLOCK_TYPE", "IBLOCK_ID", "FOLDER", "BASE_URL", "USER_ID", "PASSWORD", "LIFETIME", "F_SIZE", "DESCRIPTION", "ROOT_SECTION_ID")
	function AddExtLink($options)
	{
		global $DB;
		$arFields_i = array();
		$clob = array();
		$arFields_i["URL"] = $options["URL"];
		$arFields_i["F_SIZE"] = $options["F_SIZE"];
		$arFields_i["DESCRIPTION"] = $options["DESCRIPTION"];
		$clob["DESCRIPTION"] = $options["DESCRIPTION"];
		$arFields_i["HASH"] = self::GenerateExtLinkHash();
		if(!array_key_exists("USER_ID", $options))
		{
			$arFields_i["USER_ID"] = self::GetUserID();
		}
		else
		{
			$arFields_i["USER_ID"] = intval($options["USER_ID"]);
		}
		if(strlen($options["PASSWORD"]) > 0)
		{
			$arFields_i["SALT"] = self::GenerateHash();
			$arFields_i["PASSWORD"] = self::GenerateHash($options["PASSWORD"], $arFields_i["SALT"]);
		}
		$arFields_i["CREATION_DATE"] = time();
		$arFields_i["LIFETIME"] = $arFields_i["CREATION_DATE"] + $options["LIFETIME"];
		$arFields_i["BASE_URL"] = $options["BASE_URL"];
		$arFields_i["URL_HASH"] = md5($arFields_i["BASE_URL"] . $arFields_i["URL"]);
		$arFields_i["SINGLE_SESSION"] = (int)!empty($options['SINGLE_SESSION']);
		if(isset($options["FILE_ID"]))
		{
			$arFields_i["FILE_ID"] = intval($options["FILE_ID"]);
		}
		if(isset($options["VERSION_ID"]))
		{
			$arFields_i["VERSION_ID"] = intval($options["VERSION_ID"]);
		}
		if(isset($options["ELEMENT_ID"]))
		{
			$arFields_i["ELEMENT_ID"] = intval($options["ELEMENT_ID"]);
		}
		if(!empty($options['LINK_TYPE']) && isset(self::$linkTypes[$options['LINK_TYPE']]))
		{
			$arFields_i["LINK_TYPE"] = $options['LINK_TYPE'];
		}

		if(array_key_exists("IBLOCK_ID", $options))
		{
			$arFields_i["RESOURCE_TYPE"] = "IBLOCK";
			$arFields_i["IBLOCK_TYPE"] = (strlen($options["IBLOCK_TYPE"]) > 0 && $options["IBLOCK_TYPE"] != 0) ? $options["IBLOCK_TYPE"] : "";
			$arFields_i["IBLOCK_ID"] = intval($options["IBLOCK_ID"]);
		}
		else
		{
			$arFields_i["RESOURCE_TYPE"] = "FOLDER";
			$arFields_i["FOLDER"] = $options["FOLDER"];
		}
		if(array_key_exists("ROOT_SECTION_ID", $options))
		{
			$arFields_i["ROOT_SECTION_ID"] = $options["ROOT_SECTION_ID"];
		}

		$arInsert = $DB->PrepareInsert(self::EXT_LINKS_TABLE, $arFields_i);
		$strSql =
			"INSERT INTO " . self::EXT_LINKS_TABLE . " (".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$id = $DB->QueryBind($strSql, $clob, true);
		return ($id != false ? $arFields_i["HASH"] : false);
	}

	function CheckPassword($arLink, $inputPass)
	{
		$inputPassH = self::GenerateHash($inputPass, $arLink["SALT"]);		
		return ($arLink["PASSWORD"] === $inputPassH);
	}

	public static function getHashLink($arParams, $options, $userId = null)
	{
		$o = array_merge(
			array_intersect_key($arParams, array(
				 'IBLOCK_TYPE' => true,
				 'IBLOCK_ID' => true,
				 'FOLDER' => true,
				 'ROOT_SECTION_ID' => true
			)),
			array_intersect_key($options, array(
				'PASSWORD' => true,
				'URL' => true,
				'BASE_URL' => true,
				'F_SIZE' => true,
				'DESCRIPTION' => true,
				'LIFETIME_TYPE' => true,
				'LIFETIME_NUMBER' => true,
				'SINGLE_SESSION' => true,
				'LINK_TYPE' => true,
				'VERSION_ID' => true,
				'FILE_ID' => true,
				'ELEMENT_ID' => true,
			)),
			array(
				'USER_ID' => self::getUserID($userId),
				'LIFETIME' => self::getLifeTime($options),
			));

		$fileName = str_replace(' ', '_', getFileName($o['URL']));
		if(strlen($fileName) > 25)
		{
			$fileName = substr($fileName, 0, 25) . '...';
		}
		$fileName = CWebDavTools::urlEncode($fileName);
		$hash = self::AddExtLink($o);

		return $hash != false? self::getFullExternalURL() . $hash . '/' . $fileName: false;
	}

	function GetExtLink($arParams, $options, $userID = null)
	{
		$hashLink = self::getHashLink($arParams, $options, $userID);
		$GLOBALS["APPLICATION"]->RestartBuffer();
		if($hashLink != false)
		{
			if(empty($options['PASSWORD']))
			{
				$hashLink = CHTTP::urlAddParams($hashLink, array('LoadFile' => '1'));
			}
			echo $hashLink;
		}
		else
		{
			echo "error";
		}
		die;
	}

	function InsertDialogCallText($urlT)
	{
		CUtil::InitJSCore(array('popup'));
		$arMessT = array();
		$arTrans = array('WD_EXT_LINKS_DIALOG_TITLE', "WD_EXT_LINKS_DIALOG_CLOSE_BUTTON", "WD_EXT_LINKS_DIALOG_LOADING", "WD_EXT_LINKS_DIALOG_ERROR", "WD_EXT_LINKS_DIALOG_GET");
		foreach($arTrans as $v)
		{
			$arMessT[$v] = GetMessage($v);
		}

		$GLOBALS["APPLICATION"]->AddHeadString(
			'<link href="' . CUtil::GetAdditionalFileURL('/bitrix/js/webdav/css/style_el_dialog.css') . '" type="text/css" rel="stylesheet" />' . "\n" .
			'<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL('/bitrix/js/webdav/extlinks.js') . '"></script>' . "\n" . 
			'<script>BX.message(' . CUtil::PhpToJsObject($arMessT) . ')</script>',
			true
		);
		//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
		$urlT .= "?" . bitrix_sessid_get();
		return "ShowExtLinkDialog('" . $urlT . "&GetExtLink=1&IFRAME=Y','" . $urlT . "&GetDialogDiv=1')";
	}

	function LoadFile($arLink)
	{
		$hash = $arLink;
		if(is_array($arLink))
		{
			$hash = $arLink["HASH"];
		}
		if(strlen($hash) <= 0)
		{
			return true;
		}
		if(self::DEMO_HASH == $hash)
		{
			LocalRedirect(self::GetDemoLoadFileUrl());
		}

		$arF = $arLink;
		if(!is_array($arLink))
		{
			$resF = self::GetList(array("HASH" => $hash, "ACTUAL" => true,), array("URL","RESOURCE_TYPE","FOLDER","IBLOCK_TYPE","IBLOCK_ID","BASE_URL","USER_ID","ROOT_SECTION_ID", "DOWNLOAD_COUNT", "VERSION_ID", "FILE_ID", "ELEMENT_ID"));
			$arF = $resF->Fetch();
		}
		if(is_array($arF))
		{
			if(intval($arF["ROOT_SECTION_ID"]) <= 0)
			{
				unset($arF["ROOT_SECTION_ID"]);
			}
			$arF['check_permissions'] = false;
			if($arF["RESOURCE_TYPE"] == "IBLOCK")
			{
				$ob = new CWebDavIblock($arF['IBLOCK_ID'], $arF['BASE_URL'], array_intersect_key($arF, array(
					"URL" => true,
					"RESOURCE_TYPE" => true,
					"FOLDER" => true,
					"IBLOCK_TYPE" => true,
					"IBLOCK_ID" => true,
					"BASE_URL" => true,
					"USER_ID" => true,
					"ROOT_SECTION_ID" => true,
					"DOWNLOAD_COUNT" => true,
				)));
				$ob->withoutAuthorization = true;
			}
			else
			{
				$ob = new CWebDavFile($arF, $arF["BASE_URL"]);
			}

			self::incDownloadCount($arF['HASH'], $arF['LINK_TYPE']);

			//for get history document (don't have url
			if($arF['LINK_TYPE'] == self::LINK_TYPE_AUTO || !empty($arF['ELEMENT_ID']))
			{
				$arF['VERSION_ID'] = intval($arF['VERSION_ID']);
				$arF['FILE_ID'] = intval($arF['FILE_ID']);
				$entityType = self::getEntityType(CIBlock::GetArrayByID($arF['IBLOCK_ID'], 'CODE'));
				$ob->wfParams['DOCUMENT_TYPE'] = self::getEntityIdDocumentData($entityType, array('ELEMENT_ID' => $arF['ELEMENT_ID']));

				if($arF['FILE_ID'] && !$arF['VERSION_ID'])
				{
					$document = $ob->findHistoryDocumentByFileId(
						$arF['ELEMENT_ID'],
						$arF['FILE_ID'],
						$ob->wfParams['DOCUMENT_TYPE']
					);
					if(!empty($document['ID']))
					{
						$arF['VERSION_ID'] = $document['ID'];
					}
				}


				$ob->SendHistoryFile($arF['ELEMENT_ID'], (int)$arF['VERSION_ID']);
				die;
			}

			$ob->SetPath($arF["URL"]);
			$options = array();
			$options['path'] = $arF["URL"];
			$options['check_permissions'] = false;
			if (true === ($status = $ob->GET($options)))
			{
				$x = $ob->SendFile($options, true);
				if (is_null($x))
				{
					echo "error";
				}
				die;
			}
		}
		else
		{
			echo "error";
			die;
		}
		global $APPLICATION;
		LocalRedirect(CHTTP::urlAddParams($APPLICATION->GetCurPage(), array('notfoud' => true)));
	}

	function incDownloadCount($hash, $linkType)
	{
		global $DB;

		$sqlSearch = GetFilterSqlSearch(array(
			GetFilterQuery("LINK_TYPE", $linkType, "N"),
			GetFilterQuery("HASH", $hash, "N"),
		));

		$strSql = 'UPDATE ' . self::EXT_LINKS_TABLE . ' SET DOWNLOAD_COUNT = DOWNLOAD_COUNT + 1 WHERE ' . $sqlSearch;
		$DB->Query($strSql, false, self::ErrMess( __FUNCTION__ , __LINE__ ));
	}

	public static function getDownloadCountForLink($link)
	{
		$hash = self::GetHashFromURL($link);
		if($hash === false)
		{
			return false;
		}
		$dbQuery = self::GetList(array("HASH" => $hash, "ACTUAL" => true, 'LINK_TYPE' => null), array('DOWNLOAD_COUNT'));
		$extLink = $dbQuery->Fetch();

		return !empty($extLink['DOWNLOAD_COUNT'])? $extLink['DOWNLOAD_COUNT'] : 0;
	}

	/**
	 * @param $iblockCode
	 * @return string
	 */
	protected static function getEntityType($iblockCode)
	{
		$entityType = explode('_', $iblockCode);
		$entityType = strtolower(array_shift($entityType));

		return $entityType;
	}

	protected static function getEntityIdDocumentData($entityType, $params = array())
	{
		if ($entityType == 'group')
		{
			$documentId  = array(
				'webdav',
				'CIBlockDocumentWebdavSocnet',
				$params['ELEMENT_ID']
			);

		}
		elseif ($entityType == 'shared')
		{
			$documentId  = array(
				'webdav',
				'CIBlockDocumentWebdav',
				$params['ELEMENT_ID']
			);
		}
		else
		{
			$documentId  = array(
				'webdav',
				'CIBlockDocumentWebdavSocnet',
				$params['ELEMENT_ID']
			);
		}
		return $documentId;

	}

	function GetFileOptions($ob)
	{
		$res = array("F_SIZE" => "", "DESCRIPTION" => "");
		$optionsQ = array (
			'path' => $ob->_path,
			'depth' => 0,
			'check_permissions' => false,
		);
		$arSelectedFieldsQ = array();//array('NAME','FILE_TUPES');
		$filesQ = null;
		$arFilterQ = array();
		$resQ = $ob->PROPFIND($optionsQ, $filesQ, array("FILTER" => $arFilterQ, "COLUMNS" => $arSelectedFieldsQ, "return" => "nav_result", "get_clones" => "Y"));
		if(is_array($resQ) && array_key_exists("NAV_RESULT", $resQ) && is_object($resQ["NAV_RESULT"]))
		{
			if($arQ = $resQ["NAV_RESULT"]->Fetch())
			{
				$res["F_SIZE"] = $arQ["PROPERTY_WEBDAV_SIZE_VALUE"];
				$res["DESCRIPTION"] = $arQ["PREVIEW_TEXT"];
				$res["CHANGE_TIME"] = self::GetChangeTime($arQ);
			}
		}
		return $res;
	}

	function CheckSessID()
	{
		if(!check_bitrix_sessid())
		{
			$GLOBALS["APPLICATION"]->RestartBuffer();
			ShowError(GetMessage("WD_ACCESS_DENIED"));
			die();
		}
	}

	function CheckRights($ob)
	{
		$ob->IsDir();
		if(!$ob->CheckWebRights("", array('action' => 'read', 'arElement' => $ob->arParams), false))
		{
			$GLOBALS["APPLICATION"]->RestartBuffer();
			ShowError(GetMessage("WD_ACCESS_DENIED"));
			die();
		}
	}

	function RemoveExpired()
	{
		self::Delete(array(
			'SKIP_USER_FILTER' => true,
			'IRRELEVANT' => true,
			'LINK_TYPE' => self::LINK_TYPE_AUTO,
		));

		return "CWebDavExtLinks::RemoveExpired();";
	}
}