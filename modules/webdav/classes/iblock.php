<?
##############################################
# Bitrix Site Manager WebDav				 #
# Copyright (c) 2002-2010 Bitrix			 #
# http://www.bitrixsoft.com					 #
# mailto:admin@bitrixsoft.com				 #
##############################################
use Bitrix\Main\Config\Option;

IncludeModuleLangFile(__FILE__);

class CWebDavIblock extends CWebDavBase
{
	var $IBLOCK_ID;

	var $IBLOCK_TYPE;

	var $Type = "iblock";

	var $allow = array(
		"POST"		=> array("rights" => "U", "min_rights" => "R"),
		"HEAD"		=> array("rights" => "R", "min_rights" => "R"),
		"OPTIONS"	=> array("rights" => "A", "min_rights" => "A"),
		"PROPPATCH"	=> array("rights" => "U", "min_rights" => "U"),
		"PROPFIND"	=> array("rights" => "R", "min_rights" => "R"),
		"GET"		=> array("rights" => "R", "min_rights" => "R"),
		"PUT"		=> array("rights" => "U", "min_rights" => "U"),
		"LOCK"		=> array("rights" => "U", "min_rights" => "U"),
		"MOVE"		=> array("rights" => "W", "min_rights" => "U"),
		"COPY"		=> array("rights" => "W", "min_rights" => "U"),
		"UNLOCK"	=> array("rights" => "U", "min_rights" => "U"),
		"MKCOL"		=> array("rights" => "W", "min_rights" => "W"),
		"DELETE"	=> array("rights" => "W", "min_rights" => "U"),
		"DESTROY"	=> array("rights" => "X", "min_rights" => "X"),
		"UNDELETE"	=> array("rights" => "W", "min_rights" => "W"),
	);

	var $preg_modif = "i";

	var $iblock_permission = "D";

	var $iblock_permission_real = "D";

	var $check_creator = false;

	var $file_prop = "FILE";

	var $permission_real = "D";

	var $short_path_template = "/folder#SECTION_ID#/#ELEMENT_ID#";

	var $attributes = array();

	var $arMetaIDs = array();

	var $e_rights = null;

	var $INT_MAX = 2147483647; // php < 5.0.5

	var $FORUM_ID = null;

	var $UF_ENTITY = null;

	var $UF_FIELDS = null;

	var $withoutAuthorization = false;

	private static $first_run = true;

	private static $trashCache = array();

	//new
	const OBJ_TYPE_IBLOCK  = 'IBLOCK';
	const OBJ_TYPE_SECTION = 'SECTION';
	const OBJ_TYPE_ELEMENT = 'ELEMENT';

	const GET_ALL_CACHED_SETTINGS = 'GET_ALL_CACHED_SETTINGS';
	const TYPE = 'IBLOCK';
	const PROPERTY_VERSION = 'WEBDAV_VERSION';

	const UF_LINK_IBLOCK_ID       = 'UF_LINK_IBLOCK_ID';
	const UF_LINK_SECTION_ID      = 'UF_LINK_SECTION_ID';
	const UF_LINK_ROOT_SECTION_ID = 'UF_LINK_RSECTION_ID';
	const UF_LINK_CAN_FORWARD     = 'UF_LINK_CAN_FORWARD';

	protected static $arSettingsCache = array();
	protected static $arSectionCache = array();
	public static $lastActionMoveWithSymlink = false;
	public static $possibleUseSymlinkByInternalSections = false;

	protected $_originalParams = array();
	protected $_symlinkMode = false;
	protected $_symlinkSectionData = array();
	protected $_symlinkRealRootSectionData = array();

	var $CACHE_TIME = 0;
	protected $cacheType = "N";
	protected $cachePathBase = "";
	var $CACHE_PATH = "";
	var $CACHE_OBJ = false;
	private static $_fetchUFSymlinkMode = true;
	private static $_metaSectionData = array();
	//new end

	function CWebDavIblock($IBLOCK_ID, $base_url, $arParams = array())
	{
		$arParams = (is_array($arParams) ? $arParams : array());

		$this->_originalParams = $arParams;
		$this->_symlinkMode = !empty($arParams['symlinkMode']);
		$this->_symlinkSectionData = empty($arParams['symlinkSectionData'])? array() : $arParams['symlinkSectionData'];
		$this->_symlinkRealRootSectionData = empty($arParams['symlinkRealRootSectionData'])? array() : $arParams['symlinkRealRootSectionData'];

		$this->IBLOCK_ID = $IBLOCK_ID;
		$this->IBLOCK_TYPE = $arParams["IBLOCK_TYPE"];
		$this->FORUM_ID = ((isset($arParams['FORUM_ID']) && (intval($arParams['FORUM_ID']) > 0)) ? intval($arParams['FORUM_ID']) : null);

		$this->cachePathBase = str_replace(array("///", "//"), "/", "/".SITE_ID."/webdav/".$this->IBLOCK_ID."/");
		$this->CACHE_PATH = $this->cachePathBase;
		$this->CACHE_TIME = array_key_exists("CACHE_TIME", $arParams) ? $arParams["CACHE_TIME"] : 0;
		$this->cacheType = array_key_exists("CACHE_TYPE", $arParams) ? $arParams["CACHE_TYPE"] : "N";

		if ($this->cacheType == "Y" ||
			(
				$this->cacheType == "A" &&
				COption::GetOptionString("main", "component_cache_on", "Y") == "Y"
			)
		)
		{
			$this->CACHE_TIME = intval($arParams["CACHE_TIME"]);
		}
		else
		{
			$this->CACHE_TIME = 0;
		}
		$arParams["CACHE_TIME"] = $this->CACHE_TIME;

		if ($this->CACHE_TIME > 0)
		{
			$this->CACHE_OBJ = new CPHPCache();
		}

		if (!$this->SetRootSection($arParams["ROOT_SECTION_ID"], false, empty($arParams['PLEASE_DO_NOT_MAKE_REDIRECT']))) // socnet
		{
			$this->arError[] = array(
				"id" => "root_section_is_not_found",
				"text" => GetMessage("WD_ROOT_SECTION_NOT_FOUND"));
		}
		else
		{
			if (self::$first_run)
				AddEventHandler("iblock", "OnAfterIBlockSectionAdd", array($this, "UpdateRootSection"));
		}

		if (
			(COption::GetOptionString('webdav', 'webdav_socnet', 'Y') === 'Y')
			&& (self::$first_run)
			&& CModule::IncludeModule("socialnetwork")
		)
		{
			$obDavEventHandler = CWebDavSocNetEvent::GetRuntime();
			$obDavEventHandler->object = $this;
			AddEventHandler("webdav", "OnFileAdd",				array($obDavEventHandler, "SocnetLogFileAdd"));
			AddEventHandler("webdav", "OnFileAdd",				array($obDavEventHandler, "SocnetNotify"));
			AddEventHandler("webdav", "OnFileUpdate",			array($obDavEventHandler, "SocnetLogFileUpdate"));
			AddEventHandler("webdav", "OnFileMoveFinished",		array($obDavEventHandler, "SocnetLogFileMove"));
			AddEventHandler("webdav", "OnFileTrash",			array($obDavEventHandler, "SocnetLogFileDelete"));
			AddEventHandler("webdav", "OnFileDelete",			array($obDavEventHandler, "SocnetLogFileDelete"));
			if (CModule::IncludeModule('forum'))
			{
				AddEventHandler("forum", "onAfterMessageAdd",	 array($obDavEventHandler, "SocnetLogMessageAdd"));
				AddEventHandler("forum", "onAfterMessageDelete", array($obDavEventHandler, "SocnetLogMessageDelete"));
				AddEventHandler("forum", "onAfterMessageUpdate", array($obDavEventHandler, "SocnetLogMessageUpdate"));
			}
		}

		$this->CWebDavBase($base_url);

		if (!IsModuleInstalled("iblock"))
		{
			$this->arError[] = array(
				"id" => "module iblock is not installed. ",
				"text" => GetMessage("W_IBLOCK_IS_NOT_INSTALLED"));
			return false;
		}

		$this->e_rights = (CIBlock::GetArrayByID($IBLOCK_ID, "RIGHTS_MODE") === "E");

		if ($this->e_rights)
			$this->permission_real = 'X';
		else
			$this->permission_real = $this->GetPermission('IBLOCK', $this->IBLOCK_ID);

		$this->permission = (empty($arParams["PERMISSION"]) ? $this->permission_real : $arParams["PERMISSION"]);

		if ($GLOBALS['USER']->CanDoOperation('webdav_change_settings'))
			$this->permission = $this->permission_real = 'X';

		$this->check_creator = false;
		if ($this->permission_real < "W" && $this->permission > $this->permission_real)
			$this->check_creator = ($arParams["CHECK_CREATOR"] == "Y" ? true : false);

		$this->USER["GROUPS"] = $GLOBALS["USER"]->GetUserGroupArray();

		$this->workflow = false;
		if (CIBlock::GetArrayByID($IBLOCK_ID, "WORKFLOW") != "N" && IsModuleInstalled("workflow"))
		{
			CModule::IncludeModule("workflow");
			$this->workflow = 'workflow';
			$this->permission_wf_edit = true;
			if ($this->permission == "U" && !CWorkflow::IsAdmin())
			{
				$this->permission_wf_edit = false;
				$db_res = CWorkflowStatus::GetDropDownList("Y",  "desc");
				if ($db_res && $res = $db_res->Fetch())
				{
					do
					{
						if (CIBlockElement::WF_GetStatusPermission($res["REFERENCE_ID"]) >= 2)
						{
							$this->permission_wf_edit = true;
							break;
						}
					} while ($res = $db_res->Fetch());
				}
			}
		}
		elseif (IsModuleInstalled("bizproc"))
		{
			CModule::IncludeModule("bizproc");
			$this->workflow = (CIBlock::GetArrayByID($IBLOCK_ID, "BIZPROC") != "N" ? 'bizproc' : 'bizproc_limited');
			$this->wfParams['AUTO_PUBLISH'] = "N";
			if (isset($arParams['AUTO_PUBLISH']))
				$this->wfParams['AUTO_PUBLISH'] = (($arParams['AUTO_PUBLISH'] == "Y") ? "Y" : "N");
			if (isset($arParams['FILES_AUTO_PUBLISH']))
				$this->wfParams['AUTO_PUBLISH'] = (($arParams['FILES_AUTO_PUBLISH'] == "Y") ? "Y" : "N");
			$this->wfParams['DOCUMENT_TYPE'] = array("webdav", "CIBlockDocumentWebdav", "iblock_".$IBLOCK_ID);
			if (is_array($arParams["DOCUMENT_TYPE"]))
			{
				$this->wfParams['DOCUMENT_TYPE'][1] = $arParams["DOCUMENT_TYPE"][1];
				$this->wfParams['DOCUMENT_TYPE'][2] = $arParams["DOCUMENT_TYPE"][2];
			}

			if (self::$first_run)
				AddEventHandler('bizproc', 'OnBeforeDeleteFileFromHistory', array('CWebdavDocumentHistory', 'OnBeforeDeleteFileFromHistory'));

			if ($this->wfParams['DOCUMENT_TYPE'][1] == "CIBlockDocumentWebdavSocnet")
			{
				if ($this->workflow == 'bizproc')
				{
					$tmp = explode("_", $this->wfParams['DOCUMENT_TYPE'][2]);
					if (intval($tmp[3]) > 0)
					{
						$arFilter = array(
							"IBLOCK_ID" => $IBLOCK_ID,
							"SOCNET_GROUP_ID" => false,
							"SECTION_ID" => 0);
						if ($tmp[2] == "user")
						{
							$arFilter["CREATED_BY"] = $tmp[3];
							//we don't use BP in user lib;
							$this->workflow = 'bizproc_limited';
						}
						else
						{
							$arFilter["SOCNET_GROUP_ID"] = $tmp[3];
							$db_res = CIBlockSection::GetList(array(), $arFilter, false, array("ID", "UF_USE_BP"));
							if ($db_res && $res = $db_res->Fetch())
							{
								$this->workflow = ($res["UF_USE_BP"] == "N" ? 'bizproc_limited' : 'bizproc');
							}
						}
					}
				}
				if (method_exists($this->wfParams['DOCUMENT_TYPE'][1], "GetUserGroups"))
				{
					$this->USER["GROUPS"] = call_user_func_array(
						array($this->wfParams['DOCUMENT_TYPE'][1], "GetUserGroups"),
						array($this->wfParams['DOCUMENT_TYPE'], null, $GLOBALS["USER"]->GetID()));
				}
			}
			if ($this->workflow == 'bizproc')
			{
				AddEventHandler('webdav', 'OnBizprocPublishDocument', array($this, 'OnBizprocPublishDocument'));
			}
		}

		$this->file_prop = strtoupper(isset($arParams["NAME_FILE_PROPERTY"]) ? $arParams["NAME_FILE_PROPERTY"] : "FILE");

		if ($this->permission == "U" && $this->workflow != "workflow" && $this->workflow != "bizproc")
			$this->permission = "R";

		if ($arParams["SHORT_PATH_TEMPLATE"])
			$this->short_path_template = $arParams["SHORT_PATH_TEMPLATE"];

		if ($arParams["ATTRIBUTES"])
			$this->attributes = $arParams["ATTRIBUTES"];

		$this->_check_iblock_prop(array(
			"WEBDAV_INFO" => array(
				"name" => GetMessage("WD_PROPERTY_WEBDAV_INFO"),
				"type" => "S"
			),
			"WEBDAV_SIZE" => array(
				"name" => GetMessage("WD_PROPERTY_FILE_SIZE"),
				"type" => "N"
			),
			static::PROPERTY_VERSION => array(
				"name" => GetMessage("WD_PROPERTY_VERSION"),
				"type" => "N"
			),
			$this->file_prop => array(
				"name" => GetMessage("WD_PROPERTY_NAME_FILE"),
				"type" => "F",
				"properties" => array("SEARCHABLE" => "Y")
			)
		));

		$oIB = new CIBlock;
		$arIBlock = $oIB->GetArrayByID($this->IBLOCK_ID);
		if(!array_key_exists("NOT_SAVE_SUG_FILES", $arParams))
		{
			if (strpos($arIBlock['CODE'], 'shared_files') !== false)
			{
				// without trailing slash redirects POST request to GET request
				if (strlen($this->base_url) > 0)
					$this->LibOptions('shared_files', false, SITE_ID, array('id' => $this->IBLOCK_ID, 'base_url' => $this->base_url.'/'));
			}
			elseif (strpos($arIBlock['CODE'], 'group_files') !== false)
			{
				$this->LibOptions('group_files', false, SITE_ID, array('id' => $this->IBLOCK_ID));
			}
			elseif (strpos($arIBlock['CODE'], 'user_files') !== false)
			{
				$this->LibOptions('user_files', false, SITE_ID, array('id' => $this->IBLOCK_ID));
			}
		}

		if ($arIBlock['INDEX_SECTION'] == 'Y' && strlen(trim($arIBlock['SECTION_PAGE_URL'])) == 0)
		{
			$arIBlock['SECTION_PAGE_URL'] = str_replace(array("///", "//"), "/", $arIBlock['LIST_PAGE_URL'] . "/folder/view/#SECTION_ID#/");
			$oIB->Update($this->IBLOCK_ID, $arIBlock);
		}

		$this->GetUfEntity();

		self::$first_run = false;
	}

	public static function GetULRsFromIBlockID($IBLOCK_ID, $params = array())
	{
		$IBLOCK_ID = intval($IBLOCK_ID);
		if ($IBLOCK_ID <= 0)
			return false;
		$params = (is_array($params) ? $params : array());
		$params["path"] = trim(!!$params["path"] ? $params["path"] : (!!$params["PATH"] ? $params["PATH"] : ""));
		if (empty($params["path"]))
		{
			$params["path"] = CIBlock::GetArrayByID($IBLOCK_ID, "DETAIL_PAGE_URL");
		}
		// Params for socialnetwork
		static $arExtranetSite = false;
		static $defSite = false;
		$params["SECTION_ID"] = intval($params["SECTION_ID"]); // root section id
		$params["ELEMENT_ID"] = intval($params["ELEMENT_ID"]);
		$arSection = (is_array($params["SECTION"]) ? $params["SECTION"] : array()); // root section
		$arElement = (is_array($params["ELEMENT"]) ? $params["ELEMENT"] : array());
		if (empty($arSection))
		{
			if ($params["SECTION_ID"] > 0)
			{
				$arSection = CIBlockSection::GetList(array(),
					array(
						"ID" => $params["SECTION_ID"],
						'CHECK_PERMISSIONS' => 'N'
					),
					false,
					array('ID', 'IBLOCK_ID', 'SOCNET_GROUP_ID', 'CREATED_BY')
				)->fetch();
			}
			else
			{
				if ($params["ELEMENT_ID"] > 0 && empty($arElement))
					$arElement = CIBlockElement::GetList(array(), array('ID' => $params["ELEMENT_ID"]), false, false,
						array('ID', 'IBLOCK_SECTION_ID', 'IBLOCK_CODE', 'IBLOCK_ID'))->fetch();
				if (!empty($arElement) && $arElement["IBLOCK_SECTION_ID"] > 0)
				{
					$res = CWebDavSymlinkHelper::getNavChain($IBLOCK_ID, $arElement["IBLOCK_SECTION_ID"]);
					if (!!$res)
						$arSection = reset($res);
				}
			}
		}
		if (empty($arSection) || $arSection["IBLOCK_ID"] != $IBLOCK_ID)
		{
			$arSection = array(); $arElement = array();
		}
		// Params for socialnetwork /
		if (strpos($params["path"], "#SITE_DIR#") !== false)
			$params["path"] = str_replace("#SITE_DIR#", SITE_DIR, $params["path"]);
		else if (array_key_exists("SITE_ID", $params) && CModule::IncludeModule('extranet') && (CExtranet::GetExtranetSiteID() == $params["SITE_ID"]))
		{
			if($arExtranetSite === false)
			{
				$rsSites = CSite::GetByID(SITE_ID);
				$arExtranetSite = $rsSites->Fetch();
				unset($rsSites);
			}
			if ( $arExtranetSite )
			{
				if($defSite === false)
				{
					$defSite = CSite::GetDefSite();
				}
				$params["path"] = $arExtranetSite["DIR"] . $params["path"];
			}
		}
		$SEF_FOLDER = "/";
		$SEF_URL_TEMPLATES = array();
		$arUrlRewrite = CUrlRewriter::GetList(!empty($params["path"]) ? array("QUERY" => str_replace("//", "/", $params["path"])) : array());
		$entity = false;

		foreach($arUrlRewrite as $arRule)
		{
			if (! in_array($arRule["ID"], array(
				"bitrix:webdav",
				"bitrix:socialnetwork",
				"bitrix:socialnetwork_user",
				"bitrix:socialnetwork_group")))
				continue;
			$arComponents = WDGetComponentsOnPage($arRule["PATH"]);
			$firstMet = !empty($params["path"]);

			foreach ($arComponents as $arComponent)
			{
				if ($arComponent["COMPONENT_NAME"] == $arRule["ID"])
				{
					$SEF_FOLDER = $arComponent["PARAMS"]["SEF_FOLDER"];
					if (strpos($arRule["ID"], "bitrix:socialnetwork") === 0)
					{
						if ($arRule["ID"] == "bitrix:socialnetwork" &&
							$arComponent["PARAMS"]["FILES_GROUP_IBLOCK_ID"] == $arComponent["PARAMS"]["FILES_USER_IBLOCK_ID"] &&
							($firstMet || $arComponent["PARAMS"]["FILES_USER_IBLOCK_ID"] == $IBLOCK_ID))
						{
							$entity = ($arSection["SOCNET_GROUP_ID"] > 0 ? "group" : "user");
						}
						else if ( ($firstMet || $arComponent["PARAMS"]["FILES_USER_IBLOCK_ID"] == $IBLOCK_ID) &&
							($arRule["ID"] == "bitrix:socialnetwork_user" || $arRule["ID"] == "bitrix:socialnetwork") )
						{
							$entity = "user";
						}
						else if ( ($firstMet || $arComponent["PARAMS"]["FILES_GROUP_IBLOCK_ID"] == $IBLOCK_ID) &&
							($arRule["ID"] == "bitrix:socialnetwork_group" || $arRule["ID"] == "bitrix:socialnetwork") )
						{
							$entity = "group";
						}
						if (!!$entity)
						{
							$SEF_URL_TEMPLATES = ($entity == "user" ?
								array(
									"path" => $arComponent["PARAMS"]["SEF_URL_TEMPLATES"]["user_files"],
									"view" => "user/#user_id#/files/element/view/#element_id#/",
									"edit" => "user/#user_id#/files/element/edit/#element_id#/#action#/",
									"history" => "user/#user_id#/files/element/history/#element_id#/",
									"history_get" => "user/#user_id#/files/element/historyget/#element_id#/#element_name#"
								) :
								array(
									"path" => $arComponent["PARAMS"]["SEF_URL_TEMPLATES"]["group_files"],
									"view" => "group/#group_id#/files/element/view/#element_id#/",
									"edit" => "group/#group_id#/files/element/edit/#element_id#/#action#/",
									"history" => "group/#group_id#/files/element/history/#element_id#/",
									"history_get" => "group/#group_id#/files/element/historyget/#element_id#/#element_name#"
								)
							);
						}
					}
					else if ($arRule["ID"] == "bitrix:webdav" && ($firstMet || $arComponent["PARAMS"]["IBLOCK_ID"] == $IBLOCK_ID))
					{

						$entity = "lib";
						$SEF_URL_TEMPLATES = array(
							"path" => $arComponent["PARAMS"]["SEF_URL_TEMPLATES"]["sections"],
							"view" => $arComponent["PARAMS"]["SEF_URL_TEMPLATES"]["element"],
							"edit" => $arComponent["PARAMS"]["SEF_URL_TEMPLATES"]["element_edit"],
							"history" => $arComponent["PARAMS"]["SEF_URL_TEMPLATES"]["element_history"],
							"history_get" => $arComponent["PARAMS"]["SEF_URL_TEMPLATES"]["element_history_get"]
						);
					}
					if (!!$entity)
					{
						$SEF_URL_TEMPLATES["component"] = $arRule["ID"];
						break 2;
					}
				}
			}
		}

		$repl = array("#id#", "#ELEMENT_ID#", "#element_id#", "#name#", "#ELEMENT_NAME#", "#element_name#", "#action#", "//");
		$patt = array("#ELEMENT_ID#", "#ELEMENT_ID#", "#ELEMENT_ID#", "#ELEMENT_NAME#", "#ELEMENT_NAME#", "#ELEMENT_NAME#", "#ACTION#", "/");

		if ($entity != "lib")
		{
			$repl[] = "#SOCNET_USER_ID#"; 	$patt[] = "#USER_ID#";
			$repl[] = "#socnet_user_id#"; 	$patt[] = "#USER_ID#";
			$repl[] = "#user_id#"; 			$patt[] = "#USER_ID#";
			$repl[] = "#SOCNET_GROUP_ID#"; 	$patt[] = "#GROUP_ID#";
			$repl[] = "#socnet_group_id#"; 	$patt[] = "#GROUP_ID#";
			$repl[] = "#group_id#"; 		$patt[] = "#GROUP_ID#";
			$repl[] = "#SOCNET_OBJECT_ID#"; $patt[] = "#SOCNET_OBJECT#";
			$repl[] = "#socnet_object_id#"; $patt[] = "#SOCNET_OBJECT#";
			$repl[] = "#socnet_object#"; 	$patt[] = "#SOCNET_OBJECT#";
			if (!empty($arSection))
			{
			$repl[] = "#USER_ID#"; 			$patt[] = $arSection["CREATED_BY"];
			$repl[] = "#GROUP_ID#"; 		$patt[] = $arSection["SOCNET_GROUP_ID"];
			$patt[] = "#SOCNET_OBJECT#";	$patt[] = ($arSection["SOCNET_GROUP_ID"] > 0 ? "group" : "user");
			}
		}
		if (!empty($arElement))
		{
			$repl[] = "#ID#"; $patt[] = $arElement["ID"];
			$repl[] = "#ELEMENT_ID#"; $patt[] = $arElement["ID"];
			$repl[] = "#NAME#"; $patt[] = $arElement["NAME"];
			$repl[] = "#ELEMENT_NAME#"; $patt[] = $arElement["NAME"];
		}

		foreach($SEF_URL_TEMPLATES as $key => $val)
			$SEF_URL_TEMPLATES[$key] = str_replace($repl, $patt, $SEF_FOLDER ."/". $val);

		$SEF_URL_TEMPLATES["path"] = str_replace(array("#path#", "#PATH#"), "", $SEF_URL_TEMPLATES["path"]);
		$SEF_URL_TEMPLATES["delete_dropped"] = str_replace("#ACTION#", "delete_dropped", $SEF_URL_TEMPLATES["edit"]);
		$SEF_URL_TEMPLATES["edit"] = str_replace("#ACTION#", "edit", $SEF_URL_TEMPLATES["edit"]);
		$SEF_URL_TEMPLATES["entity"] = $entity;

		return $SEF_URL_TEMPLATES;
	}

	static function LibOptions($title, $user = true, $key = false, $value = false)
	{
		$arLibOptions = array();
		$user = (!!$user);
		if ($user)
		{
			$arLibOptions = CUserOptions::GetOption('webdav', $title, '');
		}
		else
		{
			$sLibOptions = COption::GetOptionString('webdav', $title, '');
			if (CheckSerializedData($sLibOptions))
				$arLibOptions = @unserialize($sLibOptions);
		}

		if (!is_array($arLibOptions))
			$arLibOptions = array();

		if (($key !== false) && ($value !== false))
		{
			if (!isset($arLibOptions[$key]) || $arLibOptions[$key] != $value)
			{
				$arLibOptions[$key] = $value;
				if ($user)
				{
					CUserOptions::SetOption('webdav', $title, $arLibOptions);
				}
				else
				{
					$sLibOptions = serialize($arLibOptions);
					COption::SetOptionString('webdav', $title, $sLibOptions);
				}
			}
		}

		$result = ((!! $key ) ? ( isset($arLibOptions[$key]) ? $arLibOptions[$key] : false ) : $arLibOptions);
		return $result;
	}

	/*static*/ function _get_ib_rights_object($type, $id, $IBLOCK_ID=null)
	{
		if ($type !== 'SECTION' && $type !== 'ELEMENT' && $type !== 'IBLOCK')
			throw new Exception("_get_ib_rights_object invalid type \"".htmlspecialcharsbx($type)."\"");

		$ibRights = null;

		if ($IBLOCK_ID === null && isset($this))
			$IBLOCK_ID = $this->IBLOCK_ID;

		if ($IBLOCK_ID === null)
			throw new Exception("_get_ib_rights_object called statically, but no IBLOCK_ID is set");

		if ($type == 'SECTION')
			$ibRights = new CIBlockSectionRights($IBLOCK_ID, $id);
		elseif ($type == 'ELEMENT')
			$ibRights = new CIBlockElementRights($IBLOCK_ID, $id);
		else
			$ibRights = new CIBlockRights($IBLOCK_ID);

		return $ibRights;
	}

	function GetERights($type, $id)
	{
		$ibRights = $this->_get_ib_rights_object($type, $id);
		return $ibRights->GetRights();
	}

	//function SetERights()
	//{
	//}

	static function CheckRight($IBlockPermission, $permission)
	{
		if ($GLOBALS['USER']->CanDoOperation('webdav_change_settings'))
			return 'Z';
		if (is_array($IBlockPermission))
			return (isset($IBlockPermission[trim($permission)]) ? 'Z' : 'A');
		else
			return $IBlockPermission;
	}

	function GetPermissionRules($type, $arID)
	{
		if ($this->e_rights)
		{
			$id = 0;
			$arResult = array();
			$ibRights = $this->_get_ib_rights_object($type, $id);
			$dbRules = $ibRights->GetList(array("=IBLOCK_ID" => $this->IBLOCK_ID, "=ITEM_ID" => $arID));
			if (!$dbRules)
				return false;
			while ($arRule = $dbRules->Fetch())
				$arResult[$arRule['ITEM_ID']][] = $arRule;
			return $arResult;
		}
		else
			return false;
	}

	/*static*/ function GetPermissions($type, $arID, $IBLOCK_ID=null)
	{
		static $cache = array();
		if ($IBLOCK_ID === null && isset($this))
			$IBLOCK_ID = $this->IBLOCK_ID;

		if ($IBLOCK_ID === null)
			throw new Exception("GetPermissions called statically, but no IBLOCK_ID is set");

		if (!isset($cache[$IBLOCK_ID]))
			$cache[$IBLOCK_ID] = array();

		if (!is_array($arID) && ($type=='ELEMENT'))
		{
			if (isset($cache[$IBLOCK_ID][$arID]))
				return $cache[$IBLOCK_ID][$arID];
		}


		if($type == 'SECTION' && !is_array($arID) && isset($this) && is_object($this) && $this instanceof CWebDavBase)
		{
			list($contextType, $contextEntityId) = $this->getContextData();
			$sectionData = $this->getSectionDataForLinkAnalyze($arID);
			//if this element is link.
			if($sectionData && $sectionData['ID'] != $arID)
			{
				return self::GetPermissions($type, $sectionData['ID'], $sectionData['IBLOCK_ID']);
			}
		}

		$ibRights = CWebDavIblock::_get_ib_rights_object($type, 0, $IBLOCK_ID);
		$result = $ibRights->GetUserOperations($arID);
		if ($type=='ELEMENT')
		{
			if (is_array($arID))
				$cache[$IBLOCK_ID] = array_merge($cache[$IBLOCK_ID], $result);
			else
				$cache[$IBLOCK_ID][$arID] = $result;
		}

		return $result;
	}

	function GetPermission($type, $id, $op='', $runSymlinkProxy = true)
	{
		if ($this->e_rights)
		{
			if ($op === '')
				return $this->GetPermissions($type, $id, $this->IBLOCK_ID);
			else
			{
				if($type == 'SECTION')
				{
					if($runSymlinkProxy)
					{
						list($contextType, $contextEntityId) = $this->getContextData();
						$sectionData = $this->getSectionDataForLinkAnalyze($id);
						//if this element is link.
						if($sectionData && $sectionData['ID'] != $id)
						{
							return self::GetPermission($type, $sectionData['ID'], $op);
						}
					}
				}

				$ibRights = $this->_get_ib_rights_object($type, $id);
				$result = $ibRights->UserHasRightTo($this->IBLOCK_ID, $id, trim($op));
				return $result;
			}
		}
		else
		{
			return CIBlock::GetPermission($this->IBLOCK_ID);
		}
	}

	function PROPPATCH(&$options)
	{
		$ID = 0;
		$strProps = "";
		$is_dir = false;
		$object = $this->GetObject($options);
		if ($object["not_found"] === true)
		{
			return "404 Not Found";
		}
		elseif (!$this->CheckWebRights("", array("action" => "proppatch", "arElement" => $object)))
		{
			return $this->ThrowAccessDenied(__LINE__);
		}
		elseif ($object["is_dir"] === true)
		{
			$strProps = $object["dir_array"]["DESCRIPTION"];
			$is_dir = true;
		}
		else
		{
			$strProps = $object["element_array"]["PROPERTY_WEBDAV_INFO_VALUE"];
		}

		$ID = $object["item_id"];
		$arProps = @unserialize($strProps);
		$arProps = (!is_array($arProps) ? array() : $arProps);

		foreach ($options["props"] as $key => $prop)
		{
			if ($prop["ns"] == "DAV:")
				$options["props"][$key]["status"] = "403 Forbidden";
			else
			{
				if (isset($prop["val"]))
					$arProps["PROPS"][$prop["ns"]][$prop["name"]] = $prop["val"];
				else
					unset($arProps["PROPS"][$prop["ns"]][$prop["name"]]);
			}
		}

		foreach ($arProps["PROPS"] as $nsName => $arNs)
			if (sizeof($arNs) < 1)
				unset($arProps["PROPS"][$nsName]);

		if (sizeof($arProps['PROPS']) < 1)
			unset($arProps['PROPS']);

		if (sizeof($arProps) > 0)
			$strProps = serialize($arProps);
		else
			$strProps = '';

		if ($is_dir)
		{
			$se = new CIBlockSection();
			$se->Update($ID, array("DESCRIPTION" => $strProps));
		}
		else
		{
			CIBlockElement::SetPropertyValues($ID, $this->IBLOCK_ID, $strProps, "WEBDAV_INFO");
		}
		$this->_clear_cache_for_object($options);
		return "";
	}

	function PROPFIND(&$options, &$files, $arParams = array())
	{
		global $DB;
		if(empty($files))
		{
			$files = array();
		}
		$files["files"] = array();
		$arParams = (is_array($arParams) ? $arParams : array());
		if ($this->e_rights)
			$options['check_permissions'] = false;
		$this->IsDir($options);
		$arParamsIsDir = $this->arParams;
		$arResult = array("NAV_RESULT" => false, "RESULT" => array());

		if ($arParamsIsDir["not_found"] === true)
		{
			$msg = (($arParamsIsDir['parent_id'] === false) ? GetMessage("WD_FOLDER_NOT_FOUND") : GetMessage("WD_FILE_NOT_FOUND"));
			return $this->ThrowError( "404 Not Found", "DESTINATION_FILE_OR_FOLDER_IS_NOT_FOUND", $msg, __LINE__);
		}
		elseif($arParamsIsDir["is_dir"] != true)
		{
			$db_res = $this->_get_mixed_list(intVal($arParamsIsDir["parent_id"]), $arParams, intVal($arParamsIsDir["item_id"]));
			if ($db_res && $res = $db_res->Fetch())
			{
				if ($this->MetaNames($res))
				{
					$files["files"]["F".$res["ID"]] = $this->_get_file_info_arr($res, $arParams);
					if ($arParams["return"] == "array")
						$files["files"]["F".$res["ID"]] = $res;
					$arResult["RESULT"]["E".$res["ID"]] = $res; // dummy
				}
			}
		}
		else
		{
			if(!empty($arParamsIsDir['dir_array'][self::UF_LINK_SECTION_ID]))
			{
				$linkWebdav = new self($arParamsIsDir['dir_array'][self::UF_LINK_IBLOCK_ID], $this->base_url . $this->_path, array_merge($this->_originalParams, array(
					'ROOT_SECTION_ID' => $arParamsIsDir['dir_array'][self::UF_LINK_SECTION_ID],
					'symlinkMode' => true,
					'symlinkSectionData' => $arParamsIsDir['dir_array'],
					'symlinkRealRootSectionData' => $this->arRootSection,
				)));
				$options = array(
					'path' => '/',
					'depth' => 1,
					'check_permissions' => false,
				);
				$params = array_merge($arParams, array('PARENT_ID' => $arParamsIsDir['dir_array'][self::UF_LINK_SECTION_ID]));
				$linkWebdav->_path = $this->_path;

				return $linkWebdav->PROPFIND($options, $files, $params);
			}

			//simple detect symlink
			list($contextType, $contextEntityId) = $this->getContextData();
			if($arParamsIsDir['dir_array'])
			{
				$sectionData = $this->getSectionDataForLinkAnalyze($arParamsIsDir['dir_array']['ID'], array(
					'IBLOCK_ID' => $arParamsIsDir['dir_array']['IBLOCK_ID'],
					'ID' => $arParamsIsDir['dir_array']['ID'],
				));

				if(!$this->_symlinkMode && CWebDavSymlinkHelper::isLink($contextType, $contextEntityId, $sectionData))
				{
					$symlinkSectionData = CWebDavSymlinkHelper::getLinkData($contextType, $contextEntityId, $sectionData);
				}
			}
			if(!empty($symlinkSectionData))
			{
				$linkWebdav = new self($symlinkSectionData[self::UF_LINK_IBLOCK_ID], $this->base_url . $this->_path, array_merge($this->_originalParams, array(
					'ROOT_SECTION_ID' => $symlinkSectionData[self::UF_LINK_SECTION_ID],
					'symlinkMode' => true,
					'symlinkSectionData' => $symlinkSectionData,
					'symlinkRealRootSectionData' => $this->arRootSection,
				)));
				$options = array(
					'path' => '/',
					'depth' => 1,
					'check_permissions' => false,
				);
				$params = array_merge($arParams, array('PARENT_ID' => $arParamsIsDir['dir_array']['ID']));
				$linkWebdav->_path = $this->_path;

				return $linkWebdav->PROPFIND($options, $files, $params);
			}

			if ($arParamsIsDir["item_id"] <= 0 || $this->arRootSection["ID"] == $arParamsIsDir["item_id"])
			{
				$files["files"]["iblock"] = $this->get_iblock_info($arr);
				if($this->_symlinkMode)
				{
					$files["files"]["iblock"]['path'] = $this->_path;
					if (SITE_CHARSET != "UTF-8")
						$files["files"]["iblock"]['path'] = $GLOBALS["APPLICATION"]->ConvertCharset($this->_path, SITE_CHARSET, "UTF-8");

				}
				$arResult["IBLOCK"] = $arr;
			}
			else
			{
				$arResult["SECTION"] = $this->arParams["dir_array"];
				if ($this->MetaNames($arResult["SECTION"]))
					$files["files"]["section"] = $this->_get_section_info_arr($arResult["SECTION"]);
				else
					unset($arResult["SECTION"]);
			}

			if (!empty($options["depth"]))
			{
				if (intVal($arParamsIsDir["item_id"]) <= 0)
					$arParamsIsDir["item_id"] = ($this->arRootSection ? $this->arRootSection["ID"] : $arParamsIsDir["item_id"]);

				// content search
				$arSearchResults = array();
				if (isset($arParams["FILTER"]["content"]) && strlen($arParams["FILTER"]["content"])>0 && IsModuleInstalled('search') && CModule::IncludeModule('search'))
				{
					$obSearch = new CSearch;
					if (preg_match("/\\.[a-zA-Z]{3,4}$/", $arParams["FILTER"]["content"])) // search by file name?
					{
						$arParams["FILTER"]["content"] = '"'.$arParams["FILTER"]["content"].'"';
					}
					$arSearchParams = array(
						"QUERY" => $arParams["FILTER"]["content"]);
					if (!$this->arRootSection)
					{
						$arSearchParams += array(
							"MODULE_ID" => "iblock",
							"PARAM_2" => $this->IBLOCK_ID);
					}
					else
					{
						if (isset($this->attributes['user_id']))
						{
							$arSearchParams += array(
								"PARAMS" => array("socnet_user" => $this->attributes['user_id']));
						}
						elseif (isset($this->attributes['group_id']))
						{
							$arSearchParams += array(
								"PARAMS" => array("socnet_group" => $this->attributes['group_id']));
						}
					}

					$obSearch->Search($arSearchParams);
					if ($obSearch->errorno != 0)
					{
						$arResult["ERROR_MESSAGE"] = $obSearch->error;
					}
					else
					{
						while($arSearchResultItem = $obSearch->GetNext())
						{
							$arSearchResults[$arSearchResultItem['ITEM_ID']] = true;
						}
					}
				}
				$arSearchOptParams = array_flip(array("SHOW_NEW", "SHOW_HISTORY", "FILE_SIZE_multiply"));

				$arParams["function"] = "propfind";
				$parentID = isset($arParams["FILTER"]) ? ((sizeof(array_diff_key($arParams["FILTER"], $arSearchOptParams))==0) ? $arParamsIsDir["item_id"] : null ) : $arParamsIsDir["item_id"];
				if (isset($arParams["PARENT_ID"]) && intval($arParams["PARENT_ID"]) > 0)
					$parentID = $arParams["PARENT_ID"];

				if (!empty($arParams["FILTER"]))
				{
					$arParams["FILTER"]["SHOW_NEW"] = "Y";
				}
				if ($this->meta_state == "TRASH" && $this->workflow == "bizproc")
				{
					$arParams["FILTER"]["SHOW_HISTORY"] = "Y";
				}


				if (isset($arParams["FILTER"]["doctype"]))
				{
					$arFileTypes = @unserialize(COption::GetOptionString("webdav", "file_types"));
					if ($arFileTypes !== false)
					{
						foreach ($arFileTypes as $arFileType)
						{
							if ($arParams["FILTER"]["doctype"] == $arFileType["ID"])
								$arParams["FILTER"]["extension"] = str_replace(".","",$arFileType["EXTENSIONS"]);
						}
					}

					unset($arParams["FILTER"]["doctype"]);
				}
				if (isset($arParams["FILTER"]["extension"]))
				{
					$arFltExtensions = array_map('strtoupper', explode(" ", $arParams["FILTER"]["extension"]));
				}

				//$arResult["NAV_RESULT"] = $db_res;

				if (isset($arParams["FILTER"]["content"]) && (sizeof(array_diff_key($arParams["FILTER"], $arSearchOptParams)) === 1))
				{
					// there is only content search
					unset($arParams["FILTER"]["content"]);
					unset($arParams["FILTER"]["FILE_SIZE_multiply"]);
					foreach ($arSearchResults as $itemID => $nopValue)
					{
						$arParams["FILTER"]["ID"] = $itemID;
						$db_res = $this->_get_mixed_list(null, $arParams);

						if ($db_res && $res = $db_res->Fetch())
						{
							if ($this->MetaNames($res))
							{
								if ($res["TYPE"] == "S")
								{
									if (!$this->MetaSectionHide($res))
									{
										$files["files"]["S".$res["ID"]] = $this->_get_section_info_arr($res);
										$arResult["RESULT"]["S".$res["ID"]] = $res;
									}
								}
								else
								{
									$files["files"]["F".$res["ID"]] = $this->_get_file_info_arr($res, $arParams);
									$arResult["RESULT"]["E".$res["ID"]] = $res;
								}
							}
						}
					}
				}
				else
				{
					// there are some other fields to search
					$db_res = $this->_get_mixed_list($parentID, $arParams);
					if ($db_res && $res = $db_res->Fetch())
					{
						do
						{
							if (!empty($arSearchResults) && !isset($arSearchResults[$res["ID"]]))
								continue;
							$this->_parse_webdav_info($res);
							if ($this->meta_state != 'TRASH' && isset($res['PROPS']['BX:']['UNDELETE']))
								continue;
							if ($this->MetaNames($res))
							{
								if ($res["TYPE"] == "S")
								{
									if (!$this->MetaSectionHide($res))
									{
										if (empty($arParams["FILTER"]) || $this->meta_state == "TRASH" || !empty($arParams['FILTER']['SHOW_SECTIONS']))
										{
											$files["files"]["S".$res["ID"]] = $this->_get_section_info_arr($res);
											$arResult["RESULT"]["S".$res["ID"]] = $res;
										}
									}
								}
								else
								{
									if (isset($arFltExtensions))
									{
										$ext = GetFileExtension($res["NAME"]);
										if (! in_array(strtoupper($ext), $arFltExtensions))
											continue;
									}

									$files["files"]["F".$res["ID"]] = $this->_get_file_info_arr($res, $arParams);
									$arResult["RESULT"]["E".$res["ID"]] = $res;
								}
							}
						} while ($res = $db_res->Fetch());
					}
				}
			}
		}

		if ($this->e_rights && (sizeof($arResult['RESULT']) <= 0) && (!$this->GetPermission('SECTION', $arParamsIsDir['item_id'], 'section_read'))) // prevent path disclosure
		{
			$options['check_permissions'] = true;
			$arParamsIsDir = $this->GetObject($options);
			if ($arParamsIsDir["not_found"] === true)
			{
				return $this->ThrowError( "404 Not Found", "DESTINATION_FILE_OR_FOLDER_IS_NOT_FOUND", GetMessage("WD_FOLDER_NOT_FOUND"), __LINE__);
			}
		}
		if ($arParams["return"] == "array")
		{
			return $arResult;
		}
		elseif ($arParams["return"] == "nav_result")
		{
			$arResult["NAV_RESULT"] = new CDBResult;
			$arResult["NAV_RESULT"]->InitFromArray($arResult["RESULT"]);
			return $arResult;
		}
		return true;
	}

	function GET(&$options)
	{
		if (count($this->arParams) <= 0)
		{
			$this->IsDir($options);
		}
		$arParams = $this->arParams;
		$this->_get_file_info_arr($arParams);
		if ($this->arParams["not_found"])
		{
			return false;
		}
		elseif($this->withoutAuthorization !== true)
		{
			if (!$this->CheckWebRights("", array('action' => 'read', 'arElement' => $arParams), false))
			{
				return $this->ThrowAccessDenied(__LINE__);
			}
			elseif ($this->arParams["element_array"]["WF_NEW"] == "Y" ||
				(intVal($this->arParams["element_array"]["WF_PARENT_ELEMENT_ID"]) > 0 &&
					$this->arParams["element_array"]["WF_PARENT_ELEMENT_ID"] != $this->arParams["element_array"]["ID"]) ||
				$this->arParams["element_array"]["BP_PUBLISHED"] != "Y")
			{
				$res = $this->arParams["element_array"];
				$this->_get_file_info_arr($res);
				if ($this->CheckRight($res["PERMISSION"], 'element_read') <= "D")
				{
					return false;
				}
			}
		}

		$arElement = $this->arParams['element_array'];
		if ($this->workflow == 'workflow' && $this->permission < "W" && $res["STATUS_PERMISSION"] < 2) // permissions are not enouph to edit
		{
			$arElement = $this->arParams['original'];
			//$arParams["fullpath"] = CFile::GetPath($arElement["PROPERTY_".$this->file_prop."_VALUE"]);
		}
		$options["mimetype"] = $arParams["file_mimetype"];
		$options["mtime"] = MakeTimeStamp($arElement["TIMESTAMP_X"]);
		$options["size"] = $arParams["file_size"];
		$options["name"] = $arElement["NAME"];

		$arTmpFile = CFile::MakeFileArray($arParams['file_id']);
		if (!(is_array($arTmpFile) && is_set($arTmpFile, 'tmp_name')))
		{
			return false;
		}

		$io = self::GetIo();
		if(!empty($options['getContent']))
		{
			if(file_exists($io->GetPhysicalName($arTmpFile['tmp_name'])))
			{
				$options['content'] = $io->GetFile($io->GetPhysicalName($arTmpFile['tmp_name']))->GetContents();
			}
			elseif(file_exists($arTmpFile['tmp_name']))
			{
				$options['content'] = file_get_contents($arTmpFile['tmp_name']);
			}
			else
			{
				$options['content'] = null;
			}
		}
		else
		{
			if(file_exists($io->GetPhysicalName($arTmpFile['tmp_name'])))
			{
				$options['stream'] = fopen($io->GetPhysicalName($arTmpFile['tmp_name']), 'rb');
			}
			elseif(file_exists($arTmpFile['tmp_name']))
			{
				$options['stream'] = fopen($arTmpFile['tmp_name'], 'rb');
			}
			else
			{
				return false;
			}
		}

		if (empty($options["mimetype"]) || $options["mimetype"] == "unknown" || $options["mimetype"] == "application/octet-stream")
		{
			$options["mimetype"] = $this->get_mime_type($arParams["file_name"]);
		}
		return true;
	}

	function PUT(&$options)
	{
		WDUnpackCookie();
		$_SESSION["WEBDAV_DATA"]["PUT_EMPTY"] = (is_string($_SESSION["WEBDAV_DATA"]["PUT_EMPTY"]) ? $_SESSION["WEBDAV_DATA"]["PUT_EMPTY"] : "");
		$_SESSION["WEBDAV_DATA"]["PUT_MAC_OS"] = (is_string($_SESSION["WEBDAV_DATA"]["PUT_MAC_OS"]) ? $_SESSION["WEBDAV_DATA"]["PUT_MAC_OS"] : "");

		$this->IsDir($options, true);

		if ($this->arParams["is_dir"] === true)
			return "409 Conflict";
		if ($this->arParams["parent_id"] === false)
			return false;

		$options["new"] = ($this->arParams["not_found"] === true);
		$options["ELEMENT_ID"] = $this->arParams["item_id"];
		if (!$this->CheckWebRights("PUT", array('arElement' => $this->arParams)))
		{
			$this->ThrowAccessDenied(__LINE__);
			return false;
		}

		$options["FILE_NAME"] = (!empty($this->arParams["file_name"]) ? $this->arParams["file_name"] : $this->arParams["basename"]);
		$options["FILE_NAME"] = $this->CorrectName($options["FILE_NAME"]);
		$options["~path"] = $this->_udecode($options["path"]);
		$options["set_now"] = false;

		if (($_SERVER['REQUEST_METHOD'] == "PUT" || $_SERVER['REQUEST_METHOD'] == "LOCK") &&
			/*empty($_SESSION["WEBDAV_DATA"]["PUT_EMPTY"]) && */ intVal($options["content_length"]) <= 0)
		{
			// Sometimes file is uploaded in 2 steps: 1) PUT with content_length == 0, when PUT with content_length >= 0.
			$_SESSION["WEBDAV_DATA"]["PUT_EMPTY"] = $options["~path"];
			$options["set_now"] = true;
		}
		if (!empty($_SESSION["WEBDAV_DATA"]["PUT_EMPTY"]) && $_SESSION["WEBDAV_DATA"]["PUT_EMPTY"] != $options["~path"])
		{
			if ($_SESSION["WEBDAV_DATA"]["PUT_MAC_OS"] == $options["~path"])
			{
			}
			elseif (substr($options["FILE_NAME"], 0, 2) == "._" &&
				$_SESSION["WEBDAV_DATA"]["PUT_EMPTY"] == str_replace($options["FILE_NAME"], substr($options["FILE_NAME"], 2), $options["~path"]))
			{
				// Mac OS uploads in 4 PUT: <br>
				// 1. PUT with content_length == 0;
				// 2. PUT with content_length == 0 && file_name == "._".file_name;
				// 3. PUT with content_length >= 0 && file_name == "._".file_name;
				// 4. PUT with content_length >= 0.
				$_SESSION["WEBDAV_DATA"]["PUT_MAC_OS"] = $options["~path"];
			}
			else
			{
				$_SESSION["WEBDAV_DATA"] = array();
			}
		}

		WDPackCookie();

		if (!empty($_SESSION["WEBDAV_DATA"]["PUT_MAC_OS"]) && $_SESSION["WEBDAV_DATA"]["PUT_MAC_OS"] == $options["~path"])
		{
			"no need to check data";
		}
		elseif (!$options["set_now"] && !empty($_SESSION["WEBDAV_DATA"]["PUT_EMPTY"]) &&
			$_SESSION["WEBDAV_DATA"]["PUT_EMPTY"] == $options["~path"])
		{
			"no need to check data";
		}
		elseif (!$this->CheckWebRights("", $options = array_merge($options, array( "action" => ($this->arParams["not_found"] ? "create" : "edit"), "arElement" => $this->arParams)), false))
		{
			$this->ThrowAccessDenied(__LINE__);
			return false;
		}
		elseif ($this->check_creator && $this->arParams["is_file"] === true &&
			$this->arParams["element_array"]["CREATED_BY"] != $GLOBALS["USER"]->GetID())
		{
			$this->ThrowAccessDenied(__LINE__);
			return false;
		}

		$options["IBLOCK_SECTION_ID"] = $this->arParams["parent_id"];
		if ($this->arParams["parent_id"] <= 0 && $this->arRootSection)
			$options["IBLOCK_SECTION_ID"] = $this->arRootSection["ID"];

		$options["TMP_FILE"] = CTempFile::GetFileName($options["FILE_NAME"]);
		CheckDirPath($options["TMP_FILE"]);
		$fp = fopen($options["TMP_FILE"], "w");
		return $fp;
	}

	function _ib_elm_update($id, $arFields, $workflow=false)
	{
		global $APPLICATION;
		global $USER_FIELD_MANAGER;

		if (is_string($workflow))
			$workflow = ($workflow == 'workflow');

		$id = intval($id);
		if ($id <= 0)
			return false;

		$bUF = (isset($arFields['USER_FIELDS']));
		if ($bUF)
		{
			$UF_ENTITY = $this->GetUfEntity();

			if ( ! $USER_FIELD_MANAGER->CheckFields($UF_ENTITY, $id, $arFields['USER_FIELDS']))
			{
				if(is_object($APPLICATION) && $APPLICATION->GetException())
				{
					$e = $APPLICATION->GetException();
					$this->LAST_ERROR .= $e->GetString();
					return false;
				}
			}
		}

		$el = new CIBlockElement;

		if ($workflow)
		{
			if ($bUF)
				$handlerID = AddEventHandler('search', 'BeforeIndex', array($this, 'IndexUfValues'));

			$result = $el->Update($id, $arFields, $workflow);

			if ($bUF)
				RemoveEventHandler('search', 'BeforeIndex', $handlerID);

			if(!$result)
			{
				$this->LAST_ERROR = $el->LAST_ERROR;
				return false;
			}
			else
			{
				if (isset($arFields['USER_FIELDS']))
				{
					$USER_FIELD_MANAGER->Update($UF_ENTITY, $id, $arFields['USER_FIELDS']);
				}
			}
		}
		else
		{
			$arFields['WF_STATUS_ID'] = 1;
			$arProperties = (isset($arFields['PROPERTY_VALUES']) ? $arFields['PROPERTY_VALUES'] : false);
			unset($arFields['PROPERTY_VALUES']);

			if ($bUF)
				$handlerID = AddEventHandler('search', 'BeforeIndex', array($this, 'IndexUfValues'));

			if ($arProperties !== false)
			{
				if(
					isset($this->arParams['element_array']['ID']) &&
					isset($this->arParams['element_array']['IBLOCK_ID']) &&
					$this->arParams['element_array']['ID'] == $id
				)
				{
					$iblockId = $this->arParams['element_array']['IBLOCK_ID'];
				}
				else
				{
					$iblockId = $this->IBLOCK_ID;
				}
				foreach ($arProperties as $code => $value)
				{
					if($code === 'FILE')
					{
						$el->SetPropertyValuesEx($id, $iblockId, array($code => $value));
					}
					else
					{
						$el->SetPropertyValues($id, $iblockId, $value, $code);
					}
				}

				if (! $this->ValidatePropertyValues($id, $arProperties, $iblockId)) // as SetPropertyValues doesn't handle SaveFile errors
				{
					$this->LAST_ERROR = GetMessage("WD_FILE_ERROR111");
					return false;
				}
			}

			if (isset($arFields['USER_FIELDS'])
				&& !empty($arFields['USER_FIELDS'])
			)
			{
				$USER_FIELD_MANAGER->Update($UF_ENTITY, $id, $arFields['USER_FIELDS']);
			}

			$result = $el->Update($id, $arFields, $workflow, true);

			if(!$result)
			{
				if ($bUF)
					RemoveEventHandler('search', 'BeforeIndex', $handlerID);

				$this->LAST_ERROR = $el->LAST_ERROR;
				return false;
			}

			if ($bUF)
				RemoveEventHandler('search', 'BeforeIndex', $handlerID);
		}
		$element = CIBlockElement::GetList(array(), array('ID' => $id), false, false,
			array('ID', 'IBLOCK_SECTION_ID', 'IBLOCK_CODE', 'IBLOCK_ID'))->fetch();
		CWebDavDiskDispatcher::sendEventToOwners($element, null, 'update');

		return $result;
	}

	function put_commit(&$options)
	{
		if(!empty($options["IBLOCK_SECTION_ID"]))
		{
			$sectionData = $this->getSectionDataForLinkAnalyze($options["IBLOCK_SECTION_ID"], $this->getObject(array('section_id' => $options["IBLOCK_SECTION_ID"])));
			//simple detect link
			list($contextType, $contextEntityId) = $this->getContextData();
			$isSymlink = !$this->_symlinkMode && CWebDavSymlinkHelper::isLink($contextType, $contextEntityId, $sectionData);
			if($isSymlink)
			{
				$symlinkSectionData = CWebDavSymlinkHelper::getLinkData($contextType, $contextEntityId, $sectionData);
				if($symlinkSectionData)
				{
					$linkWebdav = new self($symlinkSectionData[self::UF_LINK_IBLOCK_ID], $this->base_url . $this->_path, array_merge($this->_originalParams, array(
						'ROOT_SECTION_ID' => $symlinkSectionData[self::UF_LINK_SECTION_ID],
						'symlinkMode' => true,
						'symlinkSectionData' => $symlinkSectionData,
						'symlinkRealRootSectionData' => $this->arRootSection,
					)));
					$options = array_merge($options, array(
						'IBLOCK_ID' => $symlinkSectionData[self::UF_LINK_IBLOCK_ID],
						'IBLOCK_SECTION_ID' => $sectionData['ID'],
					));
					return $linkWebdav->put_commit($options);
				}
			}
		}

		$ID = intVal($options["ELEMENT_ID"]);
		$arError = array();
		$arFile = array(
			"name" => $options["FILE_NAME"],
			"size" => $options["content_length"],
			"tmp_name" => $options["TMP_FILE"],
			"type" => "",
			"error" => 0);

		if(!empty($options['HEIGHT']))
		{
			$arFile['HEIGHT'] = $options['HEIGHT'];
		}
		if(!empty($options['WIDTH']))
		{
			$arFile['WIDTH'] = $options['WIDTH'];
		}

		if (is_set($options, "arFile"))
			$arFile = $options["arFile"];

		if (isset($arFile['tmp_name']) && is_file($arFile['tmp_name']))
		{
			if (intval($arFile["size"]) <= 0)
				$arFile["size"] = filesize($arFile["tmp_name"]);

			if (($arFile['type'] == '') && ($arFile["size"] > 0))
			{
				$arFile["type"] = CFile::GetContentType($arFile['tmp_name'], true);
			}

			if (($arFile['type'] == '')
				|| ($arFile['type'] == 'application/zip') // fix old magic.mime
			)
				$arFile['type'] = $this->get_mime_type($arFile['name']);
		}
		if (isset($arFile['tmp_name']) && strlen($arFile["type"])<=0)
			$arFile["type"] = $this->get_mime_type($options["FILE_NAME"]);
		$bDropped = isset($options['dropped']) ? $options['dropped'] : false;

		@set_time_limit(1000);

		if (is_set($options, "~path") && $_SESSION["WEBDAV_DATA"]["PUT_MAC_OS"] == $options["~path"])
		{
//			"For Mac OS";
			if ($options["new"])
			{
				$arr = array(
					"ACTIVE" => "N",
					"IBLOCK_ID" => $this->IBLOCK_ID,
					"IBLOCK_SECTION_ID" => $options["IBLOCK_SECTION_ID"],
					"NAME" => $options["FILE_NAME"],
					"MODIFIED_BY" => $GLOBALS["USER"]->GetID(),
					"PROPERTY_VALUES" => array(
						$this->file_prop => $arFile,
						"WEBDAV_SIZE" => $arFile['size']
					));
				if ($arFile["size"] <= 0)
					unset($arr["PROPERTY_VALUES"]);
				$ID = $this->_ib_elm_add($arr, false, false, false);
				if (!$ID)
				{
					return false;
				}
				else
				{
					$this->arParams["changed_element_id"] = $ID;
				}
			}
			else
			{
				$arr = array(
					"MODIFIED_BY" => $GLOBALS["USER"]->GetID(),
					"PROPERTY_VALUES" => array(
						$this->file_prop => $arFile,
						"WEBDAV_SIZE" => $arFile['size']
					));
				$res = $this->_ib_elm_update($options["ELEMENT_ID"], $arr, false);
				if(!$res) return false;
			}
		}
		elseif (is_set($options, "~path") && $this->workflow && !$options["set_now"] && $_SESSION["WEBDAV_DATA"]["PUT_EMPTY"] == $options["~path"])
		{
			$arr = array(
				"MODIFIED_BY" => $GLOBALS["USER"]->GetID(),
				"PROPERTY_VALUES" => array(
					$this->file_prop => $arFile,
					"WEBDAV_SIZE" => $arFile['size']
				));
			$res = $this->_ib_elm_update($options["ELEMENT_ID"], $arr, ($this->workflow == 'workflow'));
			if(!$res) return false;
			//if ($this->workflow == 'bizproc_limited' || $this->workflow == 'bizproc')
			//{
				/**
				 * Update history comments
				 */
				//$documentId = array($this->wfParams['DOCUMENT_TYPE'][0], $this->wfParams['DOCUMENT_TYPE'][1], $options["ELEMENT_ID"]);
				//$history = new CBPHistoryService();
				//$db_res = $history->GetHistoryList(
					//array("ID" => "DESC"),
					//array("DOCUMENT_ID" => $documentId),
					//false,
					//false,
					//array("ID", "DOCUMENT", "NAME")
				//);
				//if ($db_res && $arr = $db_res->Fetch())
				//{
					//$fullpath = $_SERVER["DOCUMENT_ROOT"].$arr["DOCUMENT"]["PROPERTIES"][$this->file_prop]["VALUE"];
					//if (!file_exists($fullpath) || filesize($fullpath) <= 0)
						//CBPHistoryService::Delete($arr["ID"], $documentId);
					//CBPDocument::AddDocumentToHistory($documentId, $arr["NAME"], $GLOBALS["USER"]->GetID());
				//}
			//}
			$_SESSION["WEBDAV_DATA"] = array();
			WDPackCookie();
		}
		else
		{
			if ($this->workflow == "workflow" && $this->permission < "W")
			//  && (is_set($options, "WF_STATUS_ID") || $options["ELEMENT_ID"] <= 0))
			{
				$fw_status_id = (is_set($options, "WF_STATUS_ID") ? $options["WF_STATUS_ID"] : false);
				$arStatusesPermission = array();
				$db_res = CWorkflowStatus::GetDropDownList("N", "desc");
				while ($db_res && $res = $db_res->Fetch())
				{
					$perm = CIBlockElement::WF_GetStatusPermission($res["REFERENCE_ID"]);
					if ($perm < 1)
						continue;
					$arStatusesPermission[intVal($res["REFERENCE_ID"])] = $perm;
					if ((($fw_status_id === false) && $perm >= 2) || $fw_status_id == $res["REFERENCE_ID"])
					{
						$fw_status_id = $res["REFERENCE_ID"];
						break;
					}
				}
				if (empty($arStatusesPermission))
				{
					$this->LAST_ERROR = GetMessage("WD_FILE_ERROR_EMPTY_STATUSES");
					return false;
				}
				elseif ($options["WF_STATUS_ID"] == $fw_status_id && !array_key_exists($fw_status_id, $arStatusesPermission))
				{
					$res = array_flip($arStatusesPermission);
					$fw_status_id = (isset($res[2]) ? $res[2] : reset($res));
				}
				$options["WF_STATUS_ID"] = $fw_status_id;
			}
			/**
			 * Validate bizproc input parameters for begining BP
			 */
			elseif (
				($this->workflow == 'bizproc')
				&& (! $bDropped)
			)
			{
				$arDocumentStates = array();
				if (is_set($options, "arDocumentStates"))
				{
					$arDocumentStates = $options["arDocumentStates"];
				}
				elseif (is_set($this->wfParams, "arDocumentStates"))
				{
					$arDocumentStates = $this->wfParams["arDocumentStates"];
				}
				else
				{
					$arDocumentStates = CBPDocument::GetDocumentStates(
						$this->wfParams['DOCUMENT_TYPE'],
						(
							$options["ELEMENT_ID"] > 0 ?
								array(
									$this->wfParams['DOCUMENT_TYPE'][0],
									$this->wfParams['DOCUMENT_TYPE'][1],
									$options["ELEMENT_ID"]) :
								null
						)
					);
				}

				foreach ($arDocumentStates as $key => $arDocumentState)
				{
					if (strlen($arDocumentState["ID"]) <= 0 && is_array($arDocumentState["TEMPLATE_PARAMETERS"]))
					{
						$templateId = $arDocumentState["TEMPLATE_ID"];
						foreach ($arDocumentState["TEMPLATE_PARAMETERS"] as $key => $arWorkflowParameters)
							$_REQUEST["bizproc".$templateId."_".$key] = (array_key_exists("bizproc".$templateId."_".$key, $_REQUEST) ?
								$_REQUEST["bizproc".$templateId."_".$key] : $arWorkflowParameters["Default_printable"]);
					}
				}
				$arBizProcParametersValues = $arErrors = array();
				if (!CIBlockDocumentWebdav::StartWorkflowsParametersValidate($this->wfParams["DOCUMENT_TYPE"], $arDocumentStates, $arBizProcParametersValues, $arErrors))
				{
					$e = new CAdminException($arErrors);
					$this->LAST_ERROR = $e->GetString();
					return false;
				}
			}
			/**
			 * Add or update or clone elements
			 */

			if ($options["clone"])
			{
				$arr = array(
					"CREATED_BY" => $GLOBALS["USER"]->GetID(),
					"MODIFIED_BY" => $GLOBALS["USER"]->GetID(),
					"PROPERTY_VALUES" => array(
						$this->file_prop => $arFile,
						"WEBDAV_SIZE" => $arFile['size'],
						static::PROPERTY_VERSION => 1,
					));
				foreach (array("NAME", "TAGS", "PREVIEW_TEXT", "PREVIEW_TEXT_TYPE", "ACTIVE", "WF_COMMENTS", "WF_STATUS_ID") as $key)
					if (is_set($options, $key))
						$arr[$key] = $options[$key];
				if ($arFile["size"] <= 0)
					unset($arr["PROPERTY_VALUES"]);
				$options["ELEMENT_ID"] = $ID = call_user_func_array(
						array($this->wfParams['DOCUMENT_TYPE'][1], "CloneElement"),
						array($options["PARENT_ELEMENT_ID"], $arr));
				CIBlockElement::SetPropertyValues($options["ELEMENT_ID"], $this->IBLOCK_ID, array(0), "FORUM_MESSAGE_CNT");
				CIBlockElement::SetPropertyValues($options["ELEMENT_ID"], $this->IBLOCK_ID, array(0), "FORUM_TOPIC_ID");
			}
			elseif ($options["new"])
			{
				$arr = array(
					"ACTIVE" => "Y",
					"IBLOCK_ID" => $this->IBLOCK_ID,
					"IBLOCK_SECTION_ID" => $options["IBLOCK_SECTION_ID"],
					"NAME" => $options["FILE_NAME"],
					"TAGS" => $options["TAGS"],
					"MODIFIED_BY" => $GLOBALS["USER"]->GetID(),
					"PREVIEW_TEXT_TYPE" => "html",
					"PREVIEW_TEXT" => $options["PREVIEW_TEXT"],
					"WF_COMMENTS" => (!empty($options["WF_COMMENTS"]) ? $options["WF_COMMENTS"] : GetMessage("WD_FILE_IS_CREATED")),
					"PROPERTY_VALUES" => array(
						$this->file_prop => $arFile,
						"WEBDAV_SIZE" => $arFile['size'],
						static::PROPERTY_VERSION => 1,
					),
				);
				if (isset($options['USER_FIELDS']))
					$arr["USER_FIELDS"] = $options['USER_FIELDS'];

				if (
					($this->workflow == 'workflow')
					&& isset($options["WF_STATUS_ID"])
				)
					$arr["WF_STATUS_ID"] = $options["WF_STATUS_ID"];
				else
					$arr["WF_STATUS_ID"] = 1;

				if ($this->workflow == 'bizproc')
					$arr["BP_PUBLISHED"] = (($this->wfParams["AUTO_PUBLISH"] == "Y") || $bDropped) ? "Y" : "N";

				$options["ELEMENT_ID"] = $ID = $this->_ib_elm_add(
					$arr,
					(($_SERVER['REQUEST_METHOD'] != "LOCK" || $this->workflow != "workflow") && $this->workflow != false),
					($arFile['size'] > 0),
					false
				);

				if (!$ID)
				{
					return false;
				}
				else
				{
					$this->arParams["changed_element_id"] = $ID;
				}
			}
			else // update
			{
				$arUpdateType = array();

				$arr = array(
					"MODIFIED_BY" => $GLOBALS["USER"]->GetID(),
					"WF_COMMENTS" => GetMessage("WD_FILE_IS_MODIFIED")
				);

				if (isset($options['USER_FIELDS']))
					$arr["USER_FIELDS"] = $options['USER_FIELDS'];

				if (!empty($arFile))
				{
					$arr["PROPERTY_VALUES"] = array(
						$this->file_prop => $arFile,
						"WEBDAV_SIZE" => $arFile['size'],
					);
					$arUpdateType[] = 'FILE';
				}

				$element = $this->GetObject(array('element_id' => $options["ELEMENT_ID"]));

				foreach (array("NAME", "TAGS", "PREVIEW_TEXT", "ACTIVE", "IBLOCK_SECTION_ID", "WF_COMMENTS", "WF_STATUS_ID") as $key)
				{
					if (is_set($options, $key))
					{
						$arr[$key] = $options[$key];
						if (
							isset($element['element_array'][$key])
							&& ($element['element_array'][$key] != $options[$key])
						)
						{
							$arUpdateType[] = $key;
						}
					}
				}
				//if ($arFile["size"] <= 0)
					//unset($arr["PROPERTY_VALUES"]);
				if (is_set($arr, "PREVIEW_TEXT"))
					$arr["PREVIEW_TEXT_TYPE"] = 'html';
				if (is_set($arr, "IBLOCK_SECTION_ID"))
				{
					$arr["IBLOCK_SECTION_ID"] = intval($arr["IBLOCK_SECTION_ID"]);
					if ($this->arRootSection && $arr["IBLOCK_SECTION_ID"] <= 0)
						$arr["IBLOCK_SECTION_ID"] = $this->arRootSection["ID"];
				}

				if ($_SERVER['REQUEST_METHOD'] != "LOCK" && !$options["clone"] &&
					($this->workflow == 'bizproc_limited' || $this->workflow == 'bizproc'))
				{
					if(empty($arr["PROPERTY_VALUES"]))
					{
						$arr["PROPERTY_VALUES"] = array();
					}
					if(empty($element['element_array']['PROPERTY_' . static::PROPERTY_VERSION . '_VALUE']))
					{
						//+1 - version from HEAD
						$countAlreadyInHistory = (int)$this->countHistoryDocumentByFileId($options["ELEMENT_ID"]) + 1;
					}
					else
					{
						$countAlreadyInHistory = $element['element_array']['PROPERTY_' . static::PROPERTY_VERSION . '_VALUE'];
					}
					$arr["PROPERTY_VALUES"][static::PROPERTY_VERSION] = $countAlreadyInHistory + 1;
					$options['THROUGH_VERSION'] = $arr["PROPERTY_VALUES"][static::PROPERTY_VERSION];

					$this->AddDocumentToHistory($ID, $options['FILE_NAME']);
				}

				$res = $this->_ib_elm_update($options["ELEMENT_ID"], $arr, ($this->workflow == "workflow"));
				if(!$res)
				{
					return false;
				}

				$ID = $options["ELEMENT_ID"];

				$this->_onEvent('Update', $ID, 'FILE', array('UPDATE_TYPE' => $arUpdateType));

				if ($this->workflow == 'bizproc' && is_set($arr, "IBLOCK_SECTION_ID"))
				{
					$db_res2 = CIBlockElement::GetList(array(), array("WF_PARENT_ELEMENT_ID" => $options["ELEMENT_ID"], "SHOW_HISTORY" => "Y"), false, false, array("ID"));
					if ($db_res2 && $res2 = $db_res2->Fetch())
					{
						do
						{
							$ar = array("IBLOCK_SECTION_ID" => $arr["IBLOCK_SECTION_ID"]);
							$res = $this->_ib_elm_update($res2["ID"], $ar, false);
						} while ($res2 = $db_res2->Fetch());
					}
				}
			}

			$documentId = $this->wfParams["DOCUMENT_TYPE"];
			$documentId[2] = $ID;

			if (
				($this->workflow == 'bizproc')
				&& (! $bDropped)
			)
			{
				if (!CIBlockDocumentWebdav::StartWorkflowsExecuting($documentId, $arDocumentStates, $arBizProcParametersValues, $options["arUserGroups"], $arErrors, $this->wfParams))
				{
					$e = new CAdminException($arErrors);
					$this->LAST_ERROR = $e->GetString();
				}
			}
		}

		$this->CACHE['CWebDavIblock::GetObject'] = array();
		return true;
	}

	function AddDocumentToHistory($docID, $fileName)
	{
		global $USER;

		$documentId = $this->wfParams["DOCUMENT_TYPE"];
		$documentId[2] = $docID;
		$userID = $USER->GetID();

		$rDoc = CIBlockElement::GetList(
			array(),
			array(
				'ID' => $docID,
			),
			false,
			false,
			array('MODIFIED_BY')
		);
		if ($rDoc && $arDoc = $rDoc->Fetch())
		{
			$userID = $arDoc['MODIFIED_BY'];
		}

		$historyDoc = CWebdavDocumentHistory::IsHistoryUpdate($documentId);
		$historyIndex = false;
		if ($historyDoc)
		{
			$historyIndex = CWebdavDocumentHistory::UpdateDocumentHistory($documentId, $historyDoc['ID']);
		}
		else
		{
			$historyIndex = CBPDocument::AddDocumentToHistory($documentId, $fileName, $userID);
		}
		return $historyIndex;
	}

	function _get_lock(&$options, array $element = array())
	{
		$strProps = "";
		if($element)
		{
			$strProps = $element["PROPERTY_WEBDAV_INFO_VALUE"];
			$arProps = @unserialize($strProps);
			$arProps = (!is_array($arProps) ? array() : $arProps);

			return $arProps;
		}

		$this->IsDir($options, true);
		if ($this->arParams["not_found"] === true)
		{
			return "404 Not Found";
		}
		elseif ($this->arParams["is_dir"] === true)
		{
			$strProps = $this->arParams["dir_array"]["DESCRIPTION"];
		}
		else
		{
			$strProps = $this->arParams["element_array"]["PROPERTY_WEBDAV_INFO_VALUE"];
		}
		$arProps = @unserialize($strProps);
		$arProps = (!is_array($arProps) ? array() : $arProps);

		return $arProps;
	}

	function _set_lock(&$options, $op)
	{
		$lock = ($op == 'LOCK');

		$is_dir = false;
		$ID = 0;
		$bFirstElement = false;

		$arProps = $this->_get_lock($options);

		if(!$this->CheckWebRights("", array('action' => 'lock', 'arElement' => $this->arParams)))
			return $this->ThrowAccessDenied(__LINE__);

		if (!is_array($arProps)) // arProps == 404 not found
		{
			if ($lock && $this->arParams['not_found'] === true) // create file by lock
			{
				if (!$this->CheckName($this->arParams["basename"]))
				{
					return "400 bad request";
				}
				elseif ($this->check_creator && $this->arParams["is_file"] === true &&
					$this->arParams["element_array"]["CREATED_BY"] != $GLOBALS["USER"]->GetID())
				{
					return $this->ThrowAccessDenied(__LINE__);
				}
				elseif ($this->arParams["parent_id"] !== false)
				{
					$options1 = array(
						'path' => $options["path"],
						'content_length' => 0,
						'content_type' => "",
						'WF_COMMENTS' => GetMessage("WD_FILE_IS_CREATED_BY_LOCK"));

					$stat = $this->PUT($options1);

					if ($stat === false)
					{
						return $this->ThrowAccessDenied(__LINE__);
					}
					elseif (is_string($stat))
					{
						return $stat;
					}
					else
					{
						if (
							is_resource($stat)
							&& get_resource_type($stat) == 'stream'
						)
							fclose($stat);

						if (
							!$this->CheckWebRights(
								"",
								array(
									"action" => "create",
									"arElement" => $this->arParams
								)
							)
						)
							return $this->ThrowAccessDenied(__LINE__);

						$this->put_commit($options1);
					}

					$ID = intVal($options1["ELEMENT_ID"]);

					if ($ID <= 0)
						return "409 Conflict";
					else
						return "200 OK";
				}
				else
				{
					return $arProps; // 404 not found
				}
			}
			return $arProps; // error in _get_lock
		}
		$ID = $this->arParams['item_id'];
		$is_dir = $this->arParams["is_dir"];

		if ($lock)
		{
			if ($is_dir && !empty($options["depth"]))
			{
				return "409 Conflict";
			}
			elseif (!$is_dir && CIBlockElement::WF_IsLocked($ID, $locked_by, $date_lock))
			{
				return false;
			}
			$options["timeout"] = time() + 300; // 5min. hardcoded

			if (isset($options["update"]) )
			{
				$token = $options["update"];
				$arProps["LOCK"] = (is_array($arProps["LOCK"]) ? $arProps["LOCK"] : array());
				if (array_key_exists($token, $arProps["LOCK"]) && strlen($arProps["LOCK"][$token]["exclusivelock"]) > 0)
				{
					$arProps["LOCK"][$token]["expires"] = $options["timeout"];
					$arProps["LOCK"][$token]["modified"] = time();

					if (array_key_exists("owner", $arProps["LOCK"][$token]))
					{
						$options["owner"] = $arProps["LOCK"][$token]["owner"];
					}
					$options["scope"] = $arProps["LOCK"][$token]["exclusivelock"] ? "exclusive" : "shared";
					$options["type"]  = $arProps["LOCK"][$token]["exclusivelock"] ? "write"		: "read";

					if ($bFirstElement)
					{
						$arProps["FIRST"] = "Y";
					}

					CIBlockElement::SetPropertyValues($ID, $this->IBLOCK_ID, serialize($arProps), "WEBDAV_INFO");
					CIBlockElement::WF_Lock($ID);
					$this->_onEvent('Lock', $ID);
					return true;
				}
				else
				{
					return false;
				}
			}

			$arProps["LOCK"][$options["locktoken"]] = array(
				"created" => time(),
				"modified" => time(),
				"owner" => $options["owner"],
				"expires" => $options["timeout"],
				"locktoken" => $options["locktoken"],
				"exclusivelock" => ($options["scope"] === "exclusive" ? 1 : 0)
			);
		}
		else
		{
			if (!empty($options["token"]))
			{
				$token = $options["token"];
				unset($arProps["LOCK"][$token]);
			}
			else
			{
				unset($arProps["LOCK"]);
			}

			if ($this->workflow == 'bizproc'
				&& $GLOBALS['USER']->CanDoOperation('webdav_change_settings')) // admin can remove bizproc locks
			{
				$arDocId = $this->wfParams["DOCUMENT_TYPE"];
				$arDocId[2] = $ID;
				$arStates = CBPDocument::GetDocumentStates($this->wfParams["DOCUMENT_TYPE"], $arDocId);
				foreach ($arStates as $workflowId => $arState)
				{
					CIBlockDocumentWebdav::UnlockDocument($ID, $workflowId);
				}
			}
		}

		if ($is_dir)
		{
			$se = new CIBlockSection();
			$x = $se->Update($ID, array("DESCRIPTION" => serialize($arProps)));
		}
		else
		{
			if ($lock && $bFirstElement)
				$arProps["FIRST"] = "Y";

			CIBlockElement::SetPropertyValues($ID, $this->IBLOCK_ID, serialize($arProps), "WEBDAV_INFO");

			if ($lock)
				CIBlockElement::WF_Lock($ID, ($this->workflow == "workflow"));
			else
				CIBlockElement::WF_UnLock($ID, ($this->workflow == "workflow"));

			$this->_onEvent(($lock ? 'Lock' : 'Unlock'), $ID);
			$x = true;
		}
		return $x ? ($lock ? "200 OK" : "204 No Content") : "409 Conflict";
	}

	function LOCK(&$options)
	{
		return $this->_set_lock($options, 'LOCK');
	}

	function UNLOCK(&$options)
	{
		return $this->_set_lock($options, 'UNLOCK');
	}

	function MOVE($options)
	{
		return $this->COPY($options, true);
	}

	function _move_form_section_to_section($arData = array(), $iFromSectionID = 0, $iToSectionID = 0, $arParams = array()) // out of date
	{

	}

	static function _move_from_iblock_to_iblock($elementId, $targetIblockId, $targetSectionId = 0, $delete = true, $setNewNameIfNonUnique = false)
	{
		$elementId = intval($elementId);
		$targetIblockId = intval($targetIblockId);
		$targetSectionId = intval($targetSectionId);
		if (($elementId <= 0) || ($targetIblockId <= 0))
			return false;

		$dbElement = CIBlockElement::GetList(array(), array("ID"=>$elementId, "SHOW_HISTORY"=>"Y"), false, false,
			array(
				"ID",
				"MODIFIED_BY",
				"DATE_CREATE",
				"CREATED_BY",
				"IBLOCK_ID",
				"ACTIVE",
				"ACTIVE_FROM",
				"ACTIVE_TO",
				"SORT",
				"NAME",
				"PREVIEW_PICTURE",
				"PREVIEW_TEXT",
				"PREVIEW_TEXT_TYPE",
				"DETAIL_PICTURE",
				"DETAIL_TEXT",
				"DETAIL_TEXT_TYPE",
				"WF_STATUS_ID",
				"WF_PARENT_ELEMENT_ID",
				"WF_COMMENTS",
				"IN_SECTIONS",
				"CODE",
				"TAGS",
				"XML_ID",
				"TMP_ID",
			)
		);
		if($element = $dbElement->Fetch())
		{
			$IBLOCK_ID = $element["IBLOCK_ID"];
			if($element["WF_PARENT_ELEMENT_ID"] > 0)
			{
				return false;
			}
			else
			{
				//public doc.
				$element["WF_STATUS_ID"] = 1;
				unset($element["WF_NEW"]);

				if($element["PREVIEW_PICTURE"])
					$element["PREVIEW_PICTURE"] = CFile::MakeFileArray($element["PREVIEW_PICTURE"]);

				if($element["DETAIL_PICTURE"])
					$element["DETAIL_PICTURE"] = CFile::MakeFileArray($element["DETAIL_PICTURE"]);

				$element["IBLOCK_SECTION_ID"] = $targetSectionId;
				$element["IN_SECTIONS"] = "N";

				$element["PROPERTY_VALUES"] = array();

				$arProps = &$element["PROPERTY_VALUES"];

				//Add new property values
				$rsProps = CIBlockElement::GetProperty($element["IBLOCK_ID"], $element["ID"], array("value_id" => "asc"));
				$i = 0;
				while($arProp = $rsProps->Fetch())
				{
					$i++;
					if(!array_key_exists($arProp["CODE"], $arProps))
						$arProps[$arProp["CODE"]] = array();

					if($arProp["PROPERTY_VALUE_ID"])
					{
						if($arProp["PROPERTY_TYPE"] == "F")
							$arPropValue = array(
								"VALUE" => CFile::MakeFileArray($arProp["VALUE"]),
								"DESCRIPTION" => $arProp["DESCRIPTION"],
							);
						else
						{
							if ($arProp["VALUE_XML_ID"] != null)
							{
								$arPropValue = array(
									"VALUE_XML_ID" => $arProp["VALUE_XML_ID"],
									"DESCRIPTION" => $arProp["DESCRIPTION"],
								);
							}
							else
							{
								$arPropValue = array(
									"VALUE" => $arProp["VALUE"],
									"DESCRIPTION" => $arProp["DESCRIPTION"],
								);
							}
						}
						$arProps[$arProp["CODE"]]["n".$i] = $arPropValue;
					}
				}
				$element["IBLOCK_ID"] = $targetIblockId;

				if($setNewNameIfNonUnique)
				{
					$mainPartName = $element['NAME'];
					$newName = $mainPartName;
					$count = 0;
					while(!self::sCheckUniqueName($targetIblockId, $targetSectionId, '', $newName, $res))
					{
						$count++;
						$newName = strstr($mainPartName, '.', true) . " ({$count})" . strstr($mainPartName, '.');
					}
					$element['NAME'] = $newName;
				}


				$newElement = new CIBlockElement;
				$newId = $newElement->Add($element);
				if(!$newId)
				{
					return false;
				}
				else
				{
					$element['ID'] = $newId;
					CWebDavDiskDispatcher::sendEventToOwners($element, null, 'move_from_block_to_block');
					if ($delete === true)
					{
						$ibe = new CIBlockElement();
						$result = $ibe->Delete($elementId);
					}
					return $newId;
				}
			}
		}
		else
		{
			return false;
		}
	}

	function COPY($options, $drop = false)
	{
		$statusSymlinkDelete = false;
		$arCacheCleanID = array();
		if(!$this->CheckWebRights("", array("action" => "create"), true))
		{
			return $this->ThrowAccessDenied(__LINE__);
		}
		elseif($_SERVER['REQUEST_METHOD'] == "MOVE" && !empty($_SERVER["CONTENT_LENGTH"]))
		{
			return "415 Unsupported media type";
		}
		elseif($options["path"] == $options["dest_url"])
		{
			return "204 No Content";
		}
		elseif(empty($options["dest_url"]))
		{
			return $this->ThrowError("502 bad gateway", "EMPTY_DESTINATION_URL", GetMessage("WD_FILE_ERROR2"), __LINE__);
		}

		$destUrl = $options["dest_url"];
		if(substr($destUrl, -1) === "/")
		{
			$destUrl = substr($destUrl, 0, -1);
		}
		$destName = GetFileName($destUrl);
		if($destUrl !== "" && $destName !== "")
		{
			$destParentDir  = GetDirPath($destUrl);
			$destParentDir = (count($destParentDir) > 0 ? $destParentDir : "/");

			$o = array("path" => $destParentDir, "depth" => 1);
			$result = $this->PROPFIND($o, $files, array("COLUMNS" => array("ID", "NAME"), "return" => "array"));
			if (!empty($result["RESULT"]))
			{
				foreach ($result["RESULT"] as $key => $res)
				{
					if($res["NAME"] === $destName)
					{
						if(strlen(GetFileExtension($destName)) > 0)
						{
							return $this->ThrowError("400 Bad Request", "FOLDER_IS_EXISTS", str_replace("#FILE#", '"' .$res["NAME"] . '"', GetMessage("WD_FILE_ERROR8")), __LINE__);
						}
						elseif(isset($options['section_id']) && $res['ID'] == $options['section_id'])
						{
							return $this->ThrowError("400 Bad Request", "SAME_FOLDER_IS_EXISTS", str_replace("#FOLDER#", '"' .$res["NAME"] . '"', GetMessage("WD_FILE_ERROR5")), __LINE__);
						}
						else
						{
							return $this->ThrowError("400 Bad Request", "FOLDER_IS_EXISTS", str_replace("#FOLDER#", '"' .$res["NAME"] . '"', GetMessage("WD_FILE_ERROR5")), __LINE__);
						}
					}
				}
			}
		}

		//$this->CheckUniqueName($basename, $section_id, &$res)
		//GetFileName()

		$arFrom = array();
		$arTo = array();

		$is_dir = false;

		////////////// CHECK FROM
		$this->IsDir($options);
		$arFrom = $this->arParams;
		if($this->arParams["not_found"])
		{
			return $this->ThrowError("404 Not Found", "DESTINATION_FILE_OR_FOLDER_IS_NOT_FOUND", GetMessage("WD_FILE_ERROR3"), __LINE__);
		}
		elseif($this->arParams["is_dir"] === true)
		{
			$is_dir = true;
			if ($_SERVER['REQUEST_METHOD'] == "MOVE" && ($options["depth"] != "infinity"))
			{
				return "400 Bad request";
			}
			elseif($this->check_creator)
			{
				return $this->ThrowAccessDenied("USER_IS_NOT_CREATOR", __LINE__);
			}
			elseif(empty($options["path"]))
			{
				$options["path"] = $this->_get_path($arFrom["item_id"], false);
			}

			$res = $this->_udecode($options["dest_url"]);
			$res2 = str_replace("//", "/", $res."/"); $res1 = str_replace("//", "/", $options["path"]."/");
			if ($res1 === $res2)
			{
				return "204 No Content";
			}
			elseif (
				strtolower(substr($res2, 0, strlen($res1))) == strtolower($res1)
				&& (strlen($res1) != strlen($res2)) // is not the same dir rename
			)
			{
				return $this->ThrowError("400 Bad Request", "SECTION_IS_NOT_UPDATED", GetMessage("WD_FILE_ERROR100"), __LINE__);
			}
		}
		else
		{ // found and is_file
		}

		if(!empty($arFrom['parent_id']))
		{
			list($contextType, $contextEntityId) = $this->getContextData();
			$sectionData = $this->getSectionDataForLinkAnalyze($arFrom['parent_id']);
			if(CWebDavSymlinkHelper::isLink($contextType, $contextEntityId, $sectionData))
			{
				$arFrom['is_symlink'] = true;
				$arFrom['symlink_section_data'] = $sectionData;
				$arFrom['symlink_section_data_link'] = CWebDavSymlinkHelper::getLinkData($contextType, $contextEntityId, $sectionData);
			}
		}

		////////////// CHECK TO
		$arToParams = array("path" => $options['dest_url']);
		if (strpos($options['dest_url'], '.Trash') !== false)
		{
			$arToParams['check_permissions'] = false;
		}
		$this->IsDir($arToParams);
		$arTo = $this->arParams;

		if(!empty($arTo['parent_id']))
		{
			list($contextType, $contextEntityId) = $this->getContextData();
			$sectionData = $this->getSectionDataForLinkAnalyze($arTo['parent_id']);
			if(CWebDavSymlinkHelper::isLink($contextType, $contextEntityId, $sectionData))
			{
				$arTo['is_symlink'] = true;
				$arTo['symlink_section_data'] = $sectionData;
				$arTo['symlink_section_data_link'] = CWebDavSymlinkHelper::getLinkData($contextType, $contextEntityId, $sectionData);
			}
		}

		if($this->arParams["not_found"] == true)
		{
			if (
				$this->e_rights
				&& (strpos($options['dest_url'], '.Trash') === false)
				&& !$this->CheckWebRights(
					"COPY",
					array(
						'action' => ($drop?'move':'copy'),
						'from' => array($arFrom),
						'to' => array($arTo)
					),
					false
				)
			)
			{
				return $this->ThrowAccessDenied(__LINE__);
			}
			//$arTo = false;
		}
		elseif (
			$arFrom["is_dir"] === true
			&& $arTo["is_file"] === true
			|| $arFrom["is_file"] === true
			&& $arTo["is_dir"] === true
		)
		{
			return $this->ThrowError("400 Bad Request", "FOLDER_IS_EXISTS", str_replace("#FOLDER#", $this->arParams["item_id"], GetMessage("WD_FILE_ERROR5")), __LINE__);
		}
		elseif (
			!$this->CheckWebRights(
				"COPY",
				array(
					'action' => ($drop?'move':'copy'),
					'from' => array($arFrom),
					'to' => array($arTo)
				),
				false)
		)
		{
			return $this->ThrowAccessDenied(__LINE__);
		}
		elseif(($arFrom["item_id"] == $arTo["item_id"]) && ($arFrom['basename'] == $arTo['basename']))
		{
			// else - trying to change case in name
			return "204 No Content";
		}
		elseif($arFrom["element_array"]["WF_PARENT_ELEMENT_ID"] > 0)
		{
			unset($arTo["item_id"]);
		}
		elseif(isset($options['rename']) && $options['rename'] === true)
		{
			// fix fast delete to trash from different folders with the same file name
				$nameSuffix = 1;

				do
				{
					$tmpName = $options["dest_url"]." (". $nameSuffix++ .")";
					$this->IsDir(array("path" => $tmpName));
					$arTo = $this->arParams;
				} while ($arTo["not_found"] !== true);

				$options['dest_url'] = $tmpName;
		}
		elseif(!$options["overwrite"])
		{
				return $this->ThrowError('412 Precondition failed', "FILE_OR_FOLDER_ALREADY_EXISTS", GetMessage("WD_FILE_ERROR4"), __LINE__);
		}
		elseif(!$this->CheckName($arTo["basename"]))
		{
				return $this->ThrowError("400 bad request", "BAD_NAME", GetMessage("WD_FILE_ERROR101"), __LINE__);
		}
		elseif(
			$arTo["is_file"]
			&& $this->check_creator
			&& $arTo["element_array"]["CREATED_BY"] != $GLOBALS["USER"]->GetID()
		)
		{
				return $this->ThrowAccessDenied("USER_IS_NOT_CREATOR", __LINE__);
		}

		if(
			($this->workflow == 'workflow')
			&& $arFrom["is_file"]
			&& (! CWorkflow::IsAdmin())
			&& (! $GLOBALS['USER']->CanDoOperation('webdav_change_settings'))
		)
		{
			$bNeedCheckWfRights = false;

			if ($this->e_rights)
			{
				$arToParent = $this->GetObject(array('section_id' => $arTo['parent_id']));
				if ($arToParent['is_dir'])
				{
					$bNeedCheckWfRights = ! $this->GetPermission('SECTION', $arToParent['item_id'], 'element_edit_any_wf_status');
				}
			}
			else
			{
				$bNeedCheckWfRights = ($this->permission < 'W');
			}

			if (
				$bNeedCheckWfRights
				&& (CIBlockElement::WF_GetStatusPermission($arFrom["element_array"]["WF_STATUS_ID"]) != 2)
			)
			{
				return $this->ThrowError("400 bad request", "BAD_WF_RIGHTS", GetMessage("WD_FILE_ERROR110"), __LINE__);
			}
		}

		if ($arTo['parent_id'] == $this->GetMetaID('TRASH'))
		{
			$arCheckTrashElement = $arFrom[($arFrom['is_dir']?'dir_array':'element_array')];
			if ( $this->_parse_webdav_info($arCheckTrashElement) && (! isset($arCheckTrashElement['PROPS']['BX:']['UNDELETE'])))
			{
				return $this->ThrowAccessDenied("BAD_NAME", __LINE__);
			}
		}
		if ($arFrom["is_file"])
		{
			$el = new CIBlockElement();

			if (
				$arTo["item_id"]
				&& ($arTo['item_id'] !== $arFrom['item_id']) // rename
			)
			{
				$this->_ib_elm_delete($arTo['item_id']); // TODO: need to check permissions ?
			}

			//drop == true if this action is @move@
			//is file
			if ($drop)
			{
				$actionRename = $arFrom['parent_id'] == $arTo['parent_id'];

				$arFields = array(
					"NAME" => $arTo["basename"],
					"MODIFIED_BY" => $GLOBALS['USER']->GetID(),
					"IBLOCK_SECTION_ID" => $arTo["parent_id"]);

				$this->_onEvent(
						(($arFrom['parent_id'] != $arTo['parent_id'])?'Move':'Rename'),
						$arFrom['element_id'],
						'FILE',
						array( 'TO' => ( ($arFrom['parent_id'] != $arTo['parent_id']) ? $arTo["parent_id"] : $arTo["basename"] ))
					);

				//from symlink move. Not rename!!!!
				if(!$actionRename && (!empty($arFrom['is_symlink']) || !empty($arTo['is_symlink'])))
				{
					$targetIblockId = $this->IBLOCK_ID;
					if(!empty($arTo['is_symlink']))
					{
						$targetIblockId = $arTo['symlink_section_data']['IBLOCK_ID'];
					}

					//move and don't delete item
					if(self::_move_from_iblock_to_iblock($arFrom['item_id'], $targetIblockId, $arTo['parent_id'], false, true))
					{
						$statusSymlinkDelete = $this->DELETE(array("element_id" => $arFrom['item_id']));
					}
				}
				else
				{
					if ($this->workflow == 'workflow')
					{
						if ($arTo["parent_id"] != $arFrom["parent_id"])
						{
							$arFields["WF_COMMENTS"] = GetMessage("WD_FILE_IS_MOVED");
							$el->SetElementSection($arFrom["item_id"], $arTo["parent_id"]); // TODO: need to check permissions ???
						}
						else
						{
							$arFields["WF_COMMENTS"] = GetMessage("WD_FILE_IS_RENAMED");
						}

						if ($arTo["parent_id"] != $arFrom["parent_id"] && $arTo["basename"] != $arFrom["element_name"])
							$arFields["WF_COMMENTS"] = GetMessage("WD_FILE_IS_MOVED_AND_RENAMED");
					}
					if ($this->workflow == 'bizproc' || $this->workflow == 'bizproc_limited')
					{
						$this->AddDocumentToHistory($arFrom['item_id'], $arFrom['element_name']);
					}
					$el->Update($arFrom["item_id"], $arFields, $this->workflow == 'workflow', true, false, false); // TODO: need to check permissions ???
					$arCacheCleanID[] = 'element'.$arFrom["item_id"];
					if ($this->workflow == 'bizproc' || $this->workflow == 'bizproc_limited')
					{
						$db_res2 = CIBlockElement::GetList(array(), array("WF_PARENT_ELEMENT_ID" => $arFrom["item_id"], "SHOW_HISTORY" => "Y"), false, false, array("ID"));
						if ($db_res2 && $res2 = $db_res2->Fetch())
						{
							do
							{
								$res = $el->Update($res2["ID"], array("IBLOCK_SECTION_ID" => $arFields["IBLOCK_SECTION_ID"]), false, true, false, false);
								$arCacheCleanID[] = 'element'.$res2["ID"];
							} while ($res2 = $db_res2->Fetch());
						}
					}
				}
			}
			else
			{
				//from symlink copy
				if(!empty($arFrom['is_symlink']) || !empty($arTo['is_symlink']))
				{
					$targetIblockId = $this->IBLOCK_ID;
					if(!empty($arTo['is_symlink']))
					{
						$targetIblockId = $arTo['symlink_section_data']['IBLOCK_ID'];
					}

					//move and don't delete item
					if(!self::_move_from_iblock_to_iblock($arFrom['item_id'], $targetIblockId, $arTo['parent_id'], false, true))
					{
						return '403 Forbidden';
					}
				}
				else
				{
					$options = array(
						'path' => $options["dest_url"],
						'content_length' => $arFrom["file_array"]['FILE_SIZE'],
						'content_type' => $arFrom["file_array"]['CONTENT_TYPE']);
					$stat = $this->PUT($options);
					if ($stat === false)
					{
						return '403 Forbidden';
					}
					elseif (is_resource($stat) && get_resource_type($stat) == 'stream')
					{
						fclose($stat);

						$arTmpFile = CFile::MakeFileArray($arFrom['element_array']['PROPERTY_FILE_VALUE']); // since CopyDirFiles doesn't support clouds
						if (!(is_array($arTmpFile) && is_set($arTmpFile, 'tmp_name')))
							return false;

						CopyDirFiles($arTmpFile['tmp_name'], $options["TMP_FILE"]);
						clearstatcache();

						$options['USER_FIELDS'] = $this->GetUfFieldsSimpleArray($arFrom['item_id']);

						if (!$this->put_commit($options))
						{
							return $this->ThrowError('409 Conflict', "BAD_BP_PERMISSIONS", GetMessage("WD_FILE_ERROR110"), __LINE__);
						}
					}
				}
			}

			$this->_onEvent(
				(($arFrom['parent_id'] != $arTo['parent_id'])?'Move':'Rename').'Finished',
				$arFrom['element_id'],
				'FILE'
			);
		}
		else
		{
			$se = new CIBlockSection();

			$actionRename = $arFrom['parent_id'] == $arTo['parent_id'];
			$actionWithSymlink = !empty($arFrom['is_symlink']) || !empty($arTo['is_symlink']);
			$actionMoveInSymlink = false;
			if($actionWithSymlink)
			{
				$actionMoveInSymlink = $arFrom['symlink_section_data_link'] == $arTo['symlink_section_data_link'];
			}

			//drop == true if this action is @move@
			//not symlink and move! but if action rename in symlink - run this code block
			if (!$actionWithSymlink && $drop || $actionWithSymlink && $actionRename || $actionMoveInSymlink)
			{
				$this->_onEvent(
					(($arFrom['parent_id'] != $arTo['parent_id'])?'Move':'Rename'),
					$arFrom['item_id'],
					'FOLDER',
					array('TO' => (	($arFrom['parent_id'] != $arTo['parent_id']) ? $arTo["parent_id"] : $arTo["basename"]) )
				);

				$GLOBALS['DB']->StartTransaction();

				if (
					isset($options['overwrite'])
					&& ($arTo['is_dir'] === true)
					&& ($arTo['item_id'] !== $arFrom['item_id']) // rename
				)
				{
					$se->Delete($arTo['item_id']);
				}

				$result = $se->Update($arFrom["item_id"], array("NAME" => $arTo["basename"], "IBLOCK_SECTION_ID" => $arTo["parent_id"])); // TODO: need to check permissions ???

				if ($result == false)
				{
					$GLOBALS['DB']->Rollback();

					return $this->ThrowError("409 Conflict",  "SECTION_IS_NOT_UPDATED", ($se->LAST_ERROR ? $se->LAST_ERROR : GetMessage("WD_FILE_ERROR102")), __LINE__);
				}
				else
				{
					$arCacheCleanID[] = 'section'.$arFrom["item_id"];
					$this->ClearCache("section");

					$GLOBALS['DB']->Commit();
				}
			}
			else
			{
				if (isset($options['overwrite']) && ($arTo['is_dir'] === true))
					$se->Delete($arTo['item_id']);

				if ($arTo["item_id"] === false)
				{
					$arPath = explode("/", $options["dest_url"]);
					$this->IsDir(array('path' => "/".implode("/", array_slice($arPath, 0, -1))));

					if ($this->arParams["not_found"] === false)
					{
						if ($this->arParams["item_id"] == 0) // root
							$arTo["dir_array"] = array("LEFT_MARGIN" => 0, "RIGHT_MARGIN" => $this->INT_MAX);

						if (($arTo["dir_array"]["LEFT_MARGIN"] - 1) < $arFrom["dir_array"]["LEFT_MARGIN"] &&
							$arFrom["dir_array"]["RIGHT_MARGIN"] < ($arTo["dir_array"]["RIGHT_MARGIN"] + 1))
						{
							// If folder moved to upper folder
						}
						elseif (
							$arTo["dir_array"]["RIGHT_MARGIN"] < $arFrom["dir_array"]["LEFT_MARGIN"] ||
							$arFrom["dir_array"]["RIGHT_MARGIN"] < $arTo["dir_array"]["LEFT_MARGIN"])
						{
							// if folder moved to neighbourhood folder
						}
						elseif (
							(
								(($arFrom["dir_array"]["LEFT_MARGIN"] - 1) <= $arTo["dir_array"]["LEFT_MARGIN"])
								&&
								($arTo["dir_array"]["RIGHT_MARGIN"] <= ($arFrom["dir_array"]["RIGHT_MARGIN"] + 1))
							)
							||
							(
								$arTo["dir_array"]["ID"] == $arFrom["dir_array"]["ID"]
							)
						)
						{
							return $this->ThrowError( "400 Bad Request", "SECTION_IS_NOT_UPDATED", GetMessage("WD_FILE_ERROR100"), __LINE__);
						}

						if(!empty($arTo['is_symlink']))
						{
							$parentSectionId = $this->arParams["item_id"];
							if($this->arParams["item_id"] == $arTo['symlink_section_data_link']['ID'])
							{
								$parentSectionId = $arTo['symlink_section_data_link'][self::UF_LINK_SECTION_ID];
							}
							$arTo["dir_array"]["ID"] = $se->Add(array(
								"IBLOCK_ID" => $arTo['symlink_section_data']['IBLOCK_ID'],
								"IBLOCK_SECTION_ID" => $parentSectionId,
								"NAME" => end(array_slice($arPath, -1, 1))
							));
							$arTo["dir_array"]['IBLOCK_ID'] = $arTo['symlink_section_data']['IBLOCK_ID'];
						}
						else
						{
							$arTo["dir_array"]["ID"] = $se->Add(array(
								"IBLOCK_ID" => $this->IBLOCK_ID,
								"IBLOCK_SECTION_ID" => $this->arParams["item_id"],
								"NAME" => end(array_slice($arPath, -1, 1))
							));
						}

						if ($arTo["dir_array"]["ID"] === false)
						{
							return $this->ThrowError("409 Conflict", "FOLDER_IS_NOT_MOVED", str_replace(array(
									"#FOLDER#",
									"#TEXT_ERROR#"
								), array(
									"/" . implode("/", $arPath),
									$se->LAST_ERROR
								), GetMessage("WD_FILE_ERROR103")), __LINE__);
						}
						else
						{
							$returnSection = $arTo["dir_array"]["ID"];
							$this->_onEvent('Add', $returnSection, 'FOLDER');
						}
					}
				}
				else
				{
					return $this->ThrowError( "409 Conflict", "FOLDER_IS_NOT_MOVED", str_replace(
						array("#FOLDER#", "#TEXT_ERROR#"),
						array($options["dest_url"], $se->LAST_ERROR), GetMessage("WD_FILE_ERROR103")), __LINE__);
				}

				$arFrom["dir_array"]['is_symlink'] = !empty($arFrom['is_symlink']);
				$arFrom["dir_array"]['symlink_section_data'] = empty($arFrom['symlink_section_data'])? array() : $arFrom['symlink_section_data'];

				$arTo["dir_array"]['is_symlink'] = !empty($arTo['is_symlink']);
				$arTo["dir_array"]['symlink_section_data'] = empty($arTo['symlink_section_data'])? array() : $arTo['symlink_section_data'];

				$result = $this->copy_commit($arFrom["dir_array"], $arTo["dir_array"], $options, $drop);

				if ($result === true && $drop === true)
				{
					if($actionWithSymlink)
					{
						$this::$lastActionMoveWithSymlink = true;
						$this->DELETE(array("section_id" => $arFrom["item_id"]));
					}
					else
					{
						CIBlockSection::Delete($arFrom["item_id"]);
					}

					$this->ClearCache("section");
				}
				elseif (is_string($result) && strpos($result, "403")!==false)
					return $this->ThrowAccessDenied(__LINE__);
			}

			if ($result !== true)
				return $result;
		}

		if($arFrom['element_id'])
		{
			CWebDavDiskDispatcher::sendEventToOwners($arFrom['element_array'], null, 'copy');
		}
		elseif($arFrom['is_dir'])
		{
			CWebDavDiskDispatcher::sendEventToOwners(null, $arFrom['dir_array'], 'copy');
		}
		$this->ClearCache($arCacheCleanID, 'local');

		if (isset($returnSection))
		{
			$this->arParams["changed_section_id"] = $returnSection;
		}

		if($statusSymlinkDelete !== false)
		{
			return $statusSymlinkDelete;
		}
		return ($arTo["not_found"]) ? "201 Created" : "204 No Content";
	}

	function copy_commit($arFrom, $arTo, $options, $drop = false)
	{
		$result = true;
		$actionWithSymlink = !empty($arFrom['is_symlink']) || !empty($arTo['is_symlink']);
		$action = ($drop ? 'move' : 'copy');
		if (!$this->CheckWebRights("", array('action' => $action, 'from' => $arFrom, 'to' => $arTo))) // warning: array param
			return $this->ThrowAccessDenied(__LINE__);


		$toIblockId = $this->IBLOCK_ID;
		if(!empty($arTo['IBLOCK_ID']))
		{
			$toIblockId = $arTo['IBLOCK_ID'];
		}

		$fromIblockId = $this->IBLOCK_ID;
		if(!empty($arFrom['IBLOCK_ID']))
		{
			$fromIblockId = $arFrom['IBLOCK_ID'];
		}

		$arFromSections = $this->GetSectionsTree(array("section_id" => $arFrom["ID"]));
		$arToSections = $this->GetSectionsTree(array("section_id" => $arTo["ID"], 'not_check_permissions' => true));
		$res = array();
		foreach ($arToSections as $key => $val)
			$res[$val["PATH"]] = $val;
		$arToSections = $res;

		$arSectionsToCopyElements[$arFrom["ID"]] = array("TO_ID" => $arTo["ID"], "NEED_CHECK_NAME" => true);
		$result = true;
		$bSectionsWasUpdated = false;
		$res = reset($arFromSections);
		$arParentId = array(intVal($arTo["ID"]), $res["ID"]);
		$iParentDeep = intVal($res["DEPTH_LEVEL"]);
		if (!empty($arFromSections))
		{
			$se = new CIBlockSection();
			do
			{
				$res["DEPTH_LEVEL"] = intVal($res["DEPTH_LEVEL"]);
				while ($res["DEPTH_LEVEL"] <= $iParentDeep)
				{
					array_pop($arParentId);
					$iParentDeep--;
				}
				if (!array_key_exists($res["PATH"], $arToSections))
				{
					if (!$actionWithSymlink && $drop === true) // Moving folders
					{
						$GLOBALS['DB']->StartTransaction();
						$se_result = $se->Update($res["ID"], array("IBLOCK_SECTION_ID" => end($arParentId)));

						if (!$se_result)
						{
							$GLOBALS['DB']->Rollback();
							$result = $this->ThrowError("409 Conflict", "FOLDER_IS_NOT_MOVED", str_replace(
								array("#FOLDER#", "#TEXT_ERROR#"),
								array($res["PATH"], $se->LAST_ERROR), GetMessage("WD_FILE_ERROR103")), __LINE__);
							break;
						}
						else
						{
							$GLOBALS['DB']->Commit();
						}
						$this->_onEvent( 'Move', $res["ID"], "FOLDER", array( 'TO' => end($arParentId)));
						$bSectionsWasUpdated = true;
						// Remove brunch elements as we move all the brunch
						$deep = intVal($res["DEPTH_LEVEL"]);
						while ($res = next($arFromSections))
						{
							$res["DEPTH_LEVEL"] = intVal($res["DEPTH_LEVEL"]);
							if ($deep <= $res["DEPTH_LEVEL"])
							{
								prev($arFromSections);
								break;
							}
						}
					}
					else // Copy folders
					{
						$se_result = $se->Add(array("IBLOCK_ID" => $toIblockId, "IBLOCK_SECTION_ID" => end($arParentId), "NAME" => $res["NAME"]));
						if (!$se_result)
						{
							$result = $this->ThrowError("409 Conflict", "FOLDER_IS_NOT_MOVED", str_replace(
								array("#FOLDER#", "#TEXT_ERROR#"),
								array($res["PATH"], $se->LAST_ERROR), GetMessage("WD_FILE_ERROR104")), __LINE__);
							break;
						}
						$this->_onEvent('Add', $se_result, 'FOLDER');
						$bSectionsWasUpdated = true;
						$arSectionsToCopyElements[$res["ID"]] = array("TO_ID" => $se_result, "NEED_CHECK_NAME" => false);
						$arParentId[] = $se_result;
						$iParentDeep++;
					}
				}
				else
				{
					$arParentId[] = $arToSections[$res["PATH"]]["ID"];
					$iParentDeep++;

					if (!$options["overwrite"])
					{
						$result = $this->ThrowError("409 Conflict", "FOLDER_ALREADY_EXISTS", str_replace("#FOLDER#", $res["PATH"], GetMessage("WD_FILE_ERROR5")), __LINE__);
						break;
					}
					else
					{
						$arSectionsToCopyElements[$res["ID"]] = array("TO_ID" => $arToSections[$res["PATH"]]["ID"], "NEED_CHECK_NAME" => true);
					}
				}
			} while ($res = next($arFromSections));

			if ($bSectionsWasUpdated)
			{
				$this->ClearCache("section");
			}
		}

		if ($result === true)
		{
			$el = new CIBlockElement();
			foreach ($arSectionsToCopyElements as $iFromSectionID => $arElement)
			{
				$arToElements = array();
				if ($arElement["NEED_CHECK_NAME"] != false)
				{
					$db_res = CIBlockElement::GetList(
						array("NAME" => "ASC"),
						array("IBLOCK_ID" => $toIblockId, "SECTION_ID" => $arElement["TO_ID"], "SHOW_NEW" => "Y", "CHECK_PERMISSIONS" => "N"),
						false,
						false,
						array("ID", "NAME"));
					if ($db_res && $res = $db_res->Fetch())
					{
						do
						{
							$arToElements[strtolower($res["NAME"])] = $res;
						} while ($res = $db_res->Fetch());
					}
				}

				$db_res = CIBlockElement::GetList(
					array("NAME" => "ASC"),
					array("IBLOCK_ID" => $fromIblockId, "SECTION_ID" => $iFromSectionID, "SHOW_NEW" => "Y"),
					false,
					false,
					array("ID", "NAME", "IBLOCK_SECTION_ID", "PROPERTY_".$this->file_prop));

				if ($db_res && $res = $db_res->Fetch())
				{
					do
					{
						$res["NAME"] = strtolower($res["NAME"]);

						if (array_key_exists($res["NAME"], $arToElements))
						{
							if (!$options["overwrite"])
							{
								$result = $this->ThrowError( "409 Conflict", "FILE_ALREADY_EXISTS", str_replace("#FILE#", $res["NAME"], GetMessage("WD_FILE_ERROR8")), __LINE__);
								break;
							}
							else
							{ // TODO: check permission to destroy elements
								$tmp = $arToElements[$res["NAME"]];
								$this->_onEvent('Delete', $tmp["ID"]);
								$this->_ib_elm_delete($tmp["ID"]);
							}
						}

						if (!$actionWithSymlink && $drop == true)
						{
							if ($this->workflow == 'bizproc' || $this->workflow == 'bizproc_limited')
							{
								$this->AddDocumentToHistory($res['ID'], $res['NAME']);
							}
							$el->Update(
								$res["ID"],
								array("IBLOCK_SECTION_ID" => $arElement["TO_ID"], "WF_COMMENTS" => GetMessage("WD_FILE_IS_MOVED")),
								($this->workflow == 'workflow'));
							$this->_onEvent( 'Move', $res["ID"], "FILE", array( 'TO' => $arElement["TO_ID"]));
							if ($this->workflow == 'bizproc' || $this->workflow == 'bizproc_limited')
							{
								$db_res2 = CIBlockElement::GetList(array(), array("WF_PARENT_ELEMENT_ID" => $res["ID"], "SHOW_HISTORY" => "Y"), false, false, array("ID"));
								if ($db_res2 && $res2 = $db_res2->Fetch())
								{
									do
									{
										$res = $el->Update($res2["ID"], array("IBLOCK_SECTION_ID" => $arElement["TO_ID"]), false, true, false, false);
									} while ($res2 = $db_res2->Fetch());
								}
							}
						}
						else
						{
							if($actionWithSymlink)
							{
								//move and don't delete item
								if(!self::_move_from_iblock_to_iblock($res["ID"], $toIblockId, $arElement["TO_ID"], false, true))
								{
									return '409 Conflict';
								}
								elseif($drop) //move action
								{
									//$this->DELETE(array("element_id" => $res["ID"]));
									//delete section in method COPY
								}
							}
							else
							{
								$arFile = CFile::GetFileArray($res["PROPERTY_".$this->file_prop."_VALUE"]);
								$optionsPut = array(
									'path' => $this->_udecode($this->_get_path($arElement["TO_ID"]))."/".$res["NAME"],
									'content_length' => $arFile['FILE_SIZE'],
									'content_type' => $arFile['CONTENT_TYPE']);
								$stat = $this->PUT($optionsPut);
								if ($stat === false)
								{
									return '403 Forbidden';
								}
								elseif (is_resource($stat) && get_resource_type($stat) == 'stream')
								{
									fclose($stat);

									$arTmpFile = CFile::MakeFileArray($arFile['ID']); // since CopyDirFiles doesn't support clouds
									if (!(is_array($arTmpFile) && is_set($arTmpFile, 'tmp_name')))
										return '409 Conflict';

									CopyDirFiles($arTmpFile['tmp_name'], $optionsPut["TMP_FILE"]);
									clearstatcache();

									$optionsPut['USER_FIELDS'] = $this->GetUfFields($res["ID"]);
									if (!$this->put_commit($optionsPut))
									{
										return '409 Conflict';
									}
								}
							}
						}
					} while ($res = $db_res->Fetch());
				}
				if ($result !== true)
				{
					break;
				}
			}
		}
		return $result;
	}

	function MKCOL($options)
	{
		$this->IsDir($options, true);

		if ($this->check_creator || !$this->CheckWebRights("", array('action' => 'mkcol', 'arElement' => $this->arParams), false))
		{
			return $this->ThrowAccessDenied(__LINE__);
		}
		elseif ($_SERVER['REQUEST_METHOD'] == "MKCOL" && !empty($_SERVER["CONTENT_LENGTH"]))
		{
			return "415 Unsupported media type";
		}
		if ($this->arParams["is_dir"] == true)
		{
			return $this->ThrowError( "405 Method not allowed", "FOLDER_IS_EXISTS", str_replace("#FOLDER#", $this->arParams["basename"], GetMessage("WD_FILE_ERROR5")), __LINE__);
		}
		elseif ($this->arParams["is_file"] === true)
		{
			return $this->ThrowError( "405 Method not allowed", "FILE_ALREADY_EXISTS", str_replace("#FILE#", $this->arParams["basename"], GetMessage("WD_FILE_ERROR8")), __LINE__);
		}
		elseif ($this->arParams["parent_id"] === false)
			return $this->ThrowError("409 Conflict" , "NO_PARENT_FOLDER", __LINE__);

		$iblockId = $this->IBLOCK_ID;
		if($this->arParams["parent_id"])
		{
			list($contextType, $contextEntityId) = $this->getContextData();
			$sectionData = $this->getSectionDataForLinkAnalyze($this->arParams["parent_id"]);
			$iblockId = $sectionData['IBLOCK_ID'];
			if(CWebDavSymlinkHelper::isLink($contextType, $contextEntityId, $sectionData))
			{
				$parentSectionData = CWebDavSymlinkHelper::getLinkData($contextType, $contextEntityId, $sectionData);
				if($parentSectionData)
				{
					$iblockId = $parentSectionData[self::UF_LINK_IBLOCK_ID];
				}
			}
		}


		$se = new CIBlockSection();
		$b = $se->Add(array(
			"IBLOCK_SECTION_ID" => $this->arParams["parent_id"],
			"NAME" => $this->CorrectName($this->arParams["basename"]),
			"IBLOCK_ID" => $iblockId,
			"ACTIVE" => "Y"));
		if (!$b)
		{
			return "403 Forbidden";
		}
		else
		{
			$this->arParams["changed_element_id"] = $b;
			$this->_onEvent('Add', $b, 'FOLDER');

			CWebDavDiskDispatcher::sendEventToOwners(null, array(
				'IBLOCK_ID' => (int)$iblockId,
				'ID' => (int)$b,
			), 'mkcol');
		}
		$this->ClearCache("section");
		return "201 Created";
	}

	function Undelete($options)
	{
		$this->IsDir($options);

		if(!$this->CheckWebRights("UNDELETE", array('action' => 'undelete', 'arElement' => $this->arParams)))
			return $this->ThrowAccessDenied(__LINE__);

		$bIsDir = ($this->arParams['is_dir']);

		if ($this->arParams["not_found"] === true)
			return "404 Not Found";

		if (!isset($options['dest_url']))
		{
			$arElement = $this->arParams[($this->arParams['dir_array']?'dir_array':'element_array')];
			if ( $this->_parse_webdav_info($arElement) &&
				isset($arElement['PROPS']['BX:']['UNDELETE']))
				$options['dest_url'] = $arElement['PROPS']['BX:']['UNDELETE'];
		}

		if (!isset($options['dest_url']))
			return "404 Not Found";

		$this->events_enabled = false;
		$result = $this->MOVE($options);
		$this->events_enabled = true;
		if (in_array(intval($result), array(201, 204)))   // remove the "UNDELETE" in WEBDAV_INFO
		{
			$options["props"] = array(array("ns"=>"BX:","name"=>"UNDELETE"));
			$this->PROPPATCH($options);
			if ($bIsDir)
			{
				$this->_onEvent('Restore', $options["section_id"], 'FOLDER');
				$arUndeleteSections = array($options["section_id"]);
				reset($arUndeleteSections);
				while($sectionID = current($arUndeleteSections))
				{
					$dbUndeleteItems = $this->_get_mixed_list($sectionID);
					while ($arUndeleteItem = $dbUndeleteItems->Fetch())
					{
						if ($arUndeleteItem["TYPE"] == "S")
						{
							$arUndeleteSections[] = $arUndeleteItem["ID"];
						}

						$arUndeleteOptions = array("props" => array( array("ns"=>"BX:", "name"=>"UNDELETE")));
						if ($arUndeleteItem["TYPE"] == "S")
							$arUndeleteOptions["section_id"] = $arUndeleteItem["ID"];
						else
							$arUndeleteOptions["element_id"] = $arUndeleteItem["ID"];
						$this->PROPPATCH($arUndeleteOptions);

						if ($arUndeleteItem["TYPE"] == "S")
							$this->_onEvent('Restore', $arUndeleteItem["ID"], 'FOLDER');
						else
						{
							$this->_onEvent('Restore', $arUndeleteItem["ID"]);
							$el = new CIBlockElement;
							$el->Update($arUndeleteItem["ID"], array('TIMESTAMP_X' => true), false, false);
						}
					}
					next($arUndeleteSections);
				}
			}
			else
			{
				$this->_onEvent('Restore', $options['element_id']);
			}

			$this->ClearCache("section");
			return "204 No Content";
		}
		else
		{
			return "404 Not Found";
		}
	}

	function _move_to_trash(&$options)
	{
		self::$trashCache = array();
		$this->IsDir($options);

		$bIsDir = $this->arParams["is_dir"];
		if ($bIsDir)
			$arElement = $this->arParams['dir_array'];
		else
			$arElement = $this->arParams['element_array'];
		if ($arElement['IBLOCK_SECTION_ID'] === null)
			$arElement['IBLOCK_SECTION_ID'] = 0;
		$sOriginalName = str_replace(array('///', '//'), '/', $this->_get_path($arElement['IBLOCK_SECTION_ID'], false) .'/'.$arElement['NAME']); // false in _get_path - for correct ru names in trash recovery param

		$arUndeleteOptions = $options;
		$arUndeleteOptions["props"][] = array("ns"=>"BX:", "name"=>"UNDELETE", "val"=>$sOriginalName);
		$this->PROPPATCH($arUndeleteOptions);
		$this->CACHE['CWebDavIblock::GetObject'] = array();
		if ($bIsDir)
		{
			$arDeleteSections = array($options["section_id"]);
			$this->_onEvent('Trash', $arElement["ID"], 'FOLDER');

			$markDeleteSection = null;
			$markDeleteSection = CWebDavDiskDispatcher::addElementForDeletingMark($arElement, $arElement);
			reset($arDeleteSections);
			while($sectionID = current($arDeleteSections))
			{
				$dbDeleteItems = $this->_get_mixed_list($sectionID);
				while ($arDeleteItem = $dbDeleteItems->Fetch())
				{
					$sOriginalName = str_replace(array('///', '//'), '/', $this->_get_path($arDeleteItem["IBLOCK_SECTION_ID"], false) .'/'.$arDeleteItem['NAME']);
					$arUndeleteOptions = array("props" => array( array("ns"=>"BX:", "name"=>"UNDELETE", "val"=>$sOriginalName)));
					if ($arDeleteItem["TYPE"] == "S")
					{
						$arUndeleteOptions["section_id"] = $arDeleteItem["ID"];
						$arDeleteSections[] = $arDeleteItem["ID"];
						if($markDeleteSection)
						{
							CWebDavDiskDispatcher::addElementForDeletingMark($arDeleteItem, $arElement);
						}
						$this->_onEvent('Trash', $arDeleteItem['ID'], 'FOLDER');
					}
					else
					{
						$arUndeleteOptions["element_id"] = $arDeleteItem["ID"];
						if($markDeleteSection)
						{
							CWebDavDiskDispatcher::addElementForDeletingMark($arDeleteItem, $arElement, false);
						}
						$this->_onEvent('Trash', $arDeleteItem['ID']);
					}
					$this->PROPPATCH($arUndeleteOptions);
				}
				next($arDeleteSections);
			}
		}
		else
		{
			CWebDavDiskDispatcher::addElementForDeletingMark($arElement, null, false);
			$this->_onEvent('Trash', $this->arParams['element_id']);
		}

		if ($bIsDir)
		{
			CWebDavSymlinkHelper::setSectionOriginalName($this->arParams['dir_array']['ID'], $this->arParams['dir_array']['NAME']);
			$destName = $this->arParams['dir_array']['NAME'] . " " . $this->CorrectName(ConvertTimeStamp(time(), "FULL"));
		}
		else
		{
			if (strlen($this->arParams['file_extention']) > 1)
			{
				$destName = substr($this->arParams['element_name'], 0, -strlen($this->arParams['file_extention'])) . " " . $this->CorrectName(ConvertTimeStamp(time(), "FULL")) . $this->arParams['file_extention'];
			}
			else
			{
				$destName = $this->arParams['element_name'] . " " . $this->CorrectName(ConvertTimeStamp(time(), "FULL")) . ".noext";
			}
		}
		$destName = str_replace("/", "_", $destName);
		$options['dest_url'] = "/".$this->meta_names['TRASH']['name']."/".$destName;
		$options['rename'] = true;
		$this->events_enabled = false;
		$status = $this->MOVE($options);
		$this->events_enabled = true;

		if(intval($status) > 200 && intval($status) < 300)
		{
			CWebDavDiskDispatcher::markDeleteBatch();
		}
		else
		{
			CWebDavDiskDispatcher::clearDeleteBatch();
		}

		if (intval($status) == 201)
		{
			return "204 No Content";
		}
		else
		{
			$GLOBALS["APPLICATION"]->ResetException();
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("WD_FILE_ERROR16"), "FILE_OR_FOLDER_TRASH_ERROR");
			return $status;
		}
	}

	function _isInMeta($id, $metaId, $type = 'FILE')
	{
		if($type == 'FILE')
		{
			$elementData = $this->GetObject(array('element_id' => intval($id)));
			if ($elementData['not_found'])
			{
				return false;
			}
			$sectionId = $elementData['parent_id'];
		}
		else
		{
			$sectionData = $this->GetObject(array('section_id' => intval($id)));
			$sectionId = $sectionData['item_id'];
		}

		list($contextType, $contextEntityId) = $this->getContextData();
		$sectionData = $this->getSectionDataForLinkAnalyze($sectionId, array(), false);
		if(CWebDavSymlinkHelper::isLink($contextType, $contextEntityId, $sectionData))
		{
			$parentSectionData = CWebDavSymlinkHelper::getLinkData($contextType, $contextEntityId, $sectionData);
			if($parentSectionData)
			{
				$linkWebdav = new self($parentSectionData[self::UF_LINK_IBLOCK_ID], $this->base_url . $this->_path, array(
					'ROOT_SECTION_ID' => $parentSectionData[self::UF_LINK_ROOT_SECTION_ID],
					'symlinkMode' => true,
					'symlinkSectionData' => $parentSectionData,
					'symlinkRealRootSectionData' => $this->arRootSection,
				));
				return $linkWebdav->_isInMeta($id, $metaId, $type);
			}
		}

		$sectionMetaId = $this->GetMetaID($metaId, false);
		foreach (CWebDavSymlinkHelper::getNavChain($this->IBLOCK_ID, $sectionId) as $res)
		{
			if($sectionMetaId == $res['ID'])
			{
				return true;
			}
		}
		unset($res);

		return false;
	}

	function DeleteDroppedFile($fileId)
	{
		$fileId = (int)$fileId;
		if($this->_isInMeta($fileId, "DROPPED"))
		{
			$this->DELETE(array(
				'element_id' => $fileId,
				'force' => true,
			));
		}
	}

	function CleanUpDropped()
	{
		$dropTargetID = $this->GetMetaID("DROPPED");
		// remove empty 'dropped' folders
		$ise = new CIBlockSection;
		//if (!$checked)
		//{
			$rsParentSection = $ise->GetByID($dropTargetID);
			if ($arParentSection = $rsParentSection->GetNext())
			{
				$arFilter = array('IBLOCK_ID' => $this->IBLOCK_ID,
					'>LEFT_MARGIN' => $arParentSection['LEFT_MARGIN'],
					'<RIGHT_MARGIN' => $arParentSection['RIGHT_MARGIN'],
					'>DEPTH_LEVEL' => $arParentSection['DEPTH_LEVEL'],
					'CHECK_PERMISSIONS' => 'N'
				);
				$dbFolders = $ise->GetList(array(),$arFilter, false, array('ID', 'IBLOCK_ID'));
				while($arFolder = $dbFolders->Fetch())
				{
					$count = CIBlockElement::GetList(array(), array(
						'IBLOCK_SECTION_ID' => $arFolder['ID'],
						'IBLOCK_ID' => $arFolder['IBLOCK_ID'],
						'INCLUDE_SUBSECTIONS' => 'Y'), array(), false, array('ID'));
					if(intval($count) < 1)
					{
						$ise->Delete($arFolder['ID']);
					}
				}
			}
		//}
	}

	function InTrash($arElement)
	{
		static $trashID = false;
		if($trashID === false)
		{
			$trashID = $this->GetMetaID("TRASH");
		}

		$id = (
			isset($arElement['ID'])
				? intval($arElement['ID'])
				: (isset($arElement['item_id'])
					? intval($arElement['item_id'])
					: null
				)
			);

		$parentID = (
			isset($arElement['parent_id'])
				? intval($arElement['parent_id'])
				: (isset($arElement['IBLOCK_SECTION_ID'])
					? intval($arElement['IBLOCK_SECTION_ID'])
					: null
				)
			);

		if($id === null)
		{
			return false;
		}

		$isDir = isset($arElement['is_dir']) ? $arElement['is_dir'] : false;

		if (($id === $trashID && $isDir === true) || $parentID === $trashID)
		{
			return true;
		}

		if($parentID === null)
		{
			return false;
		}

		if(array_key_exists($parentID, self::$trashCache))
		{
			return self::$trashCache[$parentID];
		}

		$result = false;
		foreach (CWebDavSymlinkHelper::getNavChain($this->IBLOCK_ID, $parentID) as $res)
		{
			if($res["ID"] == $trashID)
			{
				$result = true;
				break;
			}
		}
		self::$trashCache[$parentID] = $result;
		return $result;
	}

	function DELETE($options)
	{
		$options["~path"] = $this->_udecode($options["path"]);
		WDUnpackCookie();

		if (isset($options['path']))
		{
			$arPath = explode('/', $options['path']);  // ms excel tries to create and delete ~\$FileName.xls
			$basename = array_pop($arPath);
			$basename = $this->CorrectName($this->_udecode($basename));
			array_push($arPath, $basename);
			$options['path'] = implode('/', $arPath);
		}

		if (!empty($options["~path"]) && $_SESSION["WEBDAV_DATA"]["PUT_MAC_OS"] == $options["~path"])
		{
			$this->IsDir($options);
			$_SESSION["WEBDAV_DATA"]["PUT_MAC_OS"] = "";
			WDPackCookie();
			if (intVal($this->arParams["element_id"]) > 0)
			{
				$this->_ib_elm_delete($this->arParams["element_id"]);
				return "204 No Content";
			}
			return "404 Not Found";
		}
		else
		{
			$this->IsDir($options);

			//symlink logic
			list($contextType, $contextEntityId) = $this->getContextData();
			//we can't forward delete symlink. Only delete symlink section.
			$sectionData = $this->getSectionDataForLinkAnalyze($this->arParams['is_dir']? $this->arParams['dir_array']['ID'] : $this->arParams['element_array']['IBLOCK_SECTION_ID'], array(), false);
			if(CWebDavSymlinkHelper::isLink($contextType, $contextEntityId, $sectionData))
			{
				//we don't enable symlink mode, because we move to real trash
				$parentSectionData = CWebDavSymlinkHelper::getLinkData($contextType, $contextEntityId, $sectionData);
				if($parentSectionData)
				{
					$linkWebdav = new self($parentSectionData[self::UF_LINK_IBLOCK_ID], $this->base_url . $this->_path, array(
						'ROOT_SECTION_ID' => $parentSectionData[self::UF_LINK_ROOT_SECTION_ID],
					));

					return $linkWebdav->DELETE($options);
				}
				else
				{
					return "404 Not Found";
				}
			}

			if(!$this->CheckWebRights("DELETE", array('action' => 'delete', 'arElement' => $this->arParams), false))
				return $this->ThrowAccessDenied(__LINE__);

			$resLock = array();
			$lockedBy = $lockedDate = null;
			$resLock["LOCK_STATUS"] = CIBlockElement::WF_GetLockStatus($this->arParams["item_id"], $lockedBy, $lockedDate);
			if ($resLock['LOCK_STATUS'] == 'red' && !$GLOBALS['USER']->CanDoOperation('webdav_change_settings'))
				return $this->ThrowAccessDenied(__LINE__);

			//delete symlink. Not move to trash
			if(!empty($this->arParams['dir_array'][self::UF_LINK_SECTION_ID]))
			{
				$options['force'] = true;
			}

			if (!isset($options['force']) && ($this->arParams["is_dir"] === true || intval($this->arParams["element_id"]) > 0))
			{
				if ($trashID = $this->GetMetaID("TRASH"))
				{
					$item_id = $this->arParams['item_id'];

					$arSectionsChain = array();
					$bRootFounded = (empty($this->arRootSection) ? true : false);
					foreach (CWebDavSymlinkHelper::getNavChain($this->IBLOCK_ID, $this->arParams['parent_id']) as $res)
					{
						if (!$bRootFounded && $res["ID"] == $this->arRootSection["ID"])
						{
							$bRootFounded = true;
							continue;
						}
						if (!$bRootFounded)
							continue;

						$arSectionsChain[] = $res["ID"];
					}

					if ($item_id == $trashID && $this->arParams["is_dir"]) // empty trash
					{
						if($this->CheckWebRights("DESTROY", array('action' => 'destroy', 'arElement' => $this->arParams), false))
						{
							if(isset($this->attributes['user_id']) && ($this->attributes['user_id'] == $GLOBALS['USER']->GetID()))
							{
								//now we clean own bin so simple, so fast
								$this->runCleaningTrash($trashID);
							}
							else
							{
								//old cleaning. So-so
								$this->_delete_section($options["section_id"]);
							}
							$this->GetMetaID('TRASH'); // create
							return "204 No Content";
						}
						else
						{
							return $this->ThrowAccessDenied(__LINE__);
						}
					}
					elseif ( (!in_array($trashID, $arSectionsChain)) &&
							(strpos($this->arParams['file_name'], "_$") !== 0) // ms office special files
					) // move to trash
					{
						return $this->_move_to_trash($options);
					}
				}
				else
				{
					return "404 Not Found";
				}
			}

			$this->IsDir($options);
			if(!$this->CheckWebRights("DESTROY", array('action' => 'destroy', 'arElement' => $this->arParams), false))
				return $this->ThrowAccessDenied(__LINE__);
			if ($this->arParams["is_dir"] === true)
			{
				if ($this->check_creator)
					return $this->ThrowAccessDenied(__LINE__);

				if($this->_delete_section($options["section_id"]) === true && !empty($this->arParams['dir_array'][self::UF_LINK_SECTION_ID]) && $this->attributes['user_id'])
				{
					if(!empty($this->attributes['user_id']))
					{
						CWebDavDiskDispatcher::sendChangeStatus($this->attributes['user_id'], 'delete_symlink');
					}
					\Bitrix\Webdav\FolderInviteTable::deleteByFilter(array(
						'=INVITE_USER_ID' => $this->attributes['user_id'],
						'=IS_APPROVED' => true,
						'=IBLOCK_ID' => $this->arParams['dir_array'][self::UF_LINK_IBLOCK_ID],
						'=SECTION_ID' => $this->arParams['dir_array'][self::UF_LINK_SECTION_ID],
					));
				}
			}
			elseif (intVal($this->arParams["element_id"]) > 0)
			{
				CWebDavDiskDispatcher::addElementForDeletingMark($this->arParams['element_array'], null, false);

				$el = new CIBlockElement();
				$this->_onEvent('Delete', $this->arParams["element_id"]);
				if($this->_ib_elm_delete($this->arParams["element_id"]))
				{
					CWebDavDiskDispatcher::markDeleteBatch();
					CWebDavDiskDispatcher::sendEventToOwners($this->arParams['element_array'], null, 'force_delete_element');
				}
				else
				{
					CWebDavDiskDispatcher::clearDeleteBatch();
				}
			}
			else
			{
				return "404 Not Found";
			}
			if ($_SESSION["WEBDAV_DATA"]["PUT_EMPTY"] == $options["~path"])
			{
				$_SESSION["WEBDAV_DATA"]["PUT_EMPTY"] = "";
			}
		}
		return "204 No Content";
	}

	function _eventObjectParams()
	{
		$res = array(
			"TYPE" => $this->Type,
			"IBLOCK_ID" => $this->IBLOCK_ID,
			"SECTION_ID" => null,//$arElement["IBLOCK_SECTION_ID"],
			"ATTRIBUTES" => $this->attributes
		);
		if(is_array($this->arRootSection) && array_key_exists("ID", $this->arRootSection))
		{
			$res["SECTION_ID"] = $this->arRootSection["ID"];
		}
		return $res;
	}

	function _eventElementParams($elementID, $elementType)
	{
		$result = null;

		if ($elementType == 'FILE')
			$options = array('element_id' => $elementID);
		else
			$options = array('section_id' => $elementID);

		$arElement = $this->GetObject($options);

		$sFileName = '';
		if (isset($arElement['element_array']['NAME']))
			$sFileName = $arElement['element_array']['NAME'];
		elseif (isset($arElement['dir_array']['NAME']))
			$sFileName = $arElement['dir_array']['NAME'];

		if ($sFileName !== '.Trash')
		{
			$result = array(
				"name" => $sFileName,
				"id" => $elementID,
				"element" => $arElement
			);
			if (isset($arElement['element_array']))
				$result['hidden'] = !$this->MetaNames($arElement['element_array'], true);
			if (isset($arElement['dir_array']))
				$result['hidden'] = !$this->MetaNames($arElement['dir_array'], true);
			if ($this->_isInMeta($arElement['item_id'], 'DROPPED', $elementType))
				$result['dropped'] = true;

			$result['url'] = $this->GetPath($options);
		}
		return $result;
	}

	function _eventParams($operation, $elementID, $elementType, $arParams)
	{
		$arEventParams = null;
		if ($arElement = $this->_eventElementParams($elementID, $elementType))
		{
			$arEventParams = array(
				'OPERATION' => $arParams + array('NAME' => $operation),
				'OBJECT' => $this->_eventObjectParams($operation),
				'ELEMENT' => $arElement,
			);
		}
		return $arEventParams;
	}

	function _onEvent($eventTitle, $elementID, $elementType = 'FILE', $arParams = array())
	{
		if (!in_array($eventTitle, array('Delete', 'Trash', 'Lock', 'Unlock', 'Restore', 'Rename', 'Move', 'MoveFinished', 'Add', 'Update')))
			return;
		if (!$this->events_enabled)
			return;

		$objectTitle = ($elementType=='FILE'?'File':'Folder');
		$eventName = 'On'.$objectTitle.$eventTitle;
		//$rsEvents = GetModuleEvents("webdav", $eventName);
		$arEventAll = GetModuleEvents("webdav", $eventName, true);
		$arEventParams = $this->_eventParams(strtoupper($eventTitle), $elementID, $elementType, $arParams);
		if (count($arEventAll) > 0 && ($arEventParams))
		{
			$cMethod = '_onBefore'.$objectTitle.$eventTitle.'Event';
			if (method_exists($this, $cMethod))
				call_user_func_array(array($this, $cMethod), array($elementID, &$arEventParams));

			//while ($arEvent = $rsEvents->Fetch())
			foreach($arEventAll as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($arEventParams));
			}
		}

		$cMethod = '_onAfter'.$objectTitle.$eventTitle.'Event';
		if (method_exists($this, $cMethod))
			call_user_func_array(array($this, $cMethod), array($elementID, &$arEventParams));
	}

	function _onBeforeFileDeleteEvent($elementID, &$arEventParams)
	{
		$io = self::GetIo();
		$arElement = $arEventParams['ELEMENT']['element'];
		$this->_parse_webdav_info($arElement['element_array']);
		if (isset($arElement['element_array']['PROPS']['BX:']['UNDELETE']))
		{
			$sFileName = $arElement['element_array']['PROPS']['BX:']['UNDELETE'];
			$sFileName = $io->ExtractNameFromPath($sFileName);
			$arElement['element_array']["~NAME"] = $arElement['element_array']["NAME"];
			$arElement['element_array']["NAME"] = $sFileName;
			$arEventParams['ELEMENT']['element'] = $arElement;
			$arEventParams['ELEMENT']['~name'] = $arEventParams['ELEMENT']['name'];
			$arEventParams['ELEMENT']['name'] = $sFileName;
		}
	}

	function _onBeforeFolderDeleteEvent($elementID, &$arEventParams)
	{
		$io = self::GetIo();
		$arElement = $arEventParams['ELEMENT']['element'];
		$this->_parse_webdav_info($arElement['dir_array']);
		if (isset($arElement['dir_array']['PROPS']['BX:']['UNDELETE']))
		{
			$sFileName = $arElement['dir_array']['PROPS']['BX:']['UNDELETE'];
			$sFileName = $io->ExtractNameFromPath($sFileName);
			$arElement['dir_array']["~NAME"] = $arElement['dir_array']["NAME"];
			$arElement['dir_array']["NAME"] = $sFileName;
			$arEventParams['ELEMENT']['element'] = $arElement;
			$arEventParams['ELEMENT']['~name'] = $arEventParams['ELEMENT']['name'];
			$arEventParams['ELEMENT']['name'] = $sFileName;
		}
	}

	function _onDeleteElement($elementID)
	{
		global $USER_FIELD_MANAGER;
		$USER_FIELD_MANAGER->Delete($this->GetUfEntity(), $elementID);

		$this->_onEvent('Delete', $elementID);
	}

	function _onDeleteSection($sectionID)
	{
		$this->_onEvent('Delete', $sectionID, 'FOLDER');
	}

	/**
	 * Clean own trash. Check rights only on root (bin folder)
	 * Without transaction
	 * @param $sectionId
	 * @return bool
	 */
	private function runCleaningTrash($sectionId)
	{
		//copy-past from  _delete_section()
		if($this->workflow == 'bizproc' || $this->workflow == 'bizproc_limited')
		{
			AddEventHandler("iblock", "OnBeforeIBlockElementDelete", Array($this, "_onDeleteElement"));
			AddEventHandler("iblock", "OnBeforeIBlockSectionDelete", Array($this, "_onDeleteSection"));
		}

		$se = new CIBlockSection();
		if(!$se->Delete($sectionId, false))
		{
			return $this->ThrowError("423 Locked", "423", GetMessage('WD_FILE_ERROR16'));
		}

		return true;
	}

	function _delete_section($sectionID)
	{
		static $onDeleteEventSet = false;

		$result = true;

		if ($onDeleteEventSet == false && ($this->workflow == 'bizproc' || $this->workflow == 'bizproc_limited'))
		{
			AddEventHandler("iblock", "OnBeforeIBlockElementDelete", Array($this, "_onDeleteElement"));
			AddEventHandler("iblock", "OnBeforeIBlockSectionDelete", Array($this, "_onDeleteSection"));
		}

		$arSections = array();
		$sectionID = intval($sectionID);

		$s = CIBlockSection::GetList(array(),
			array(
				"ID" => $sectionID,
				'CHECK_PERMISSIONS' => 'N'
			),
			false,
			array('ID', 'NAME', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'DEPTH_LEVEL', 'IBLOCK_ID')
		);
		if ($arParentSection = $s->Fetch())
		{
			CWebDavSymlinkHelper::setSectionOriginalName($sectionID, $arParentSection['NAME']);
			if (! $this->GetPermission('SECTION', $sectionID, 'section_delete', false)) // check perms on target folder
			{
				$result = GetMessage('WD_ERR_DELETE_FOLDER_NO_PERMS', array(
					'ID' => $sectionID, //$arSection['ID'],
					'NAME' => "", //htmlspecialcharsbx($arSection['NAME'])
				));
			}
			else
			{
				$arSections[] = $sectionID;

				$rSection = CIBlockSection::GetList( // get all subsections
					array('LEFT_MARGIN' => 'DESC'),
					array(
						'CHECK_PERMISSIONS' => 'N',
						'IBLOCK_ID' => $arParentSection['IBLOCK_ID'],
						'>LEFT_MARGIN' => $arParentSection['LEFT_MARGIN'],
						'<RIGHT_MARGIN' => $arParentSection['RIGHT_MARGIN'],
						'>DEPTH_LEVEL' => $arParentSection['DEPTH_LEVEL']
					),
					false,
					array('ID', 'NAME')
				);

				if ($rSection)
				{
					while ($arSection = $rSection->Fetch())
					{
						if (! $this->GetPermission('SECTION', $arSection['ID'], 'section_delete')) // check perms on subsection
						{
							$result = GetMessage('WD_ERR_DELETE_FOLDER_NO_PERMS', array(
								'ID' => $arSection['ID'],
								'NAME' => htmlspecialcharsbx($arSection['NAME'])
							));
							break;
						}
						else
						{
							$arSections[] = $arSection['ID'];
						}
					}
				}
			}
			if ($result === true) // no errors
			{
				$se = new CIBlockSection();
				if (!$se->Delete($arParentSection['ID'], false)) // delete each section in separate transaction
				{
					$result = GetMessage('WD_FILE_ERROR16');
				}
			}

		}
		return (($result === true) ? $result : $this->ThrowError("423 Locked", "423", $result));
	}

	function get_iblock_info(&$arr)
	{
		$info = array();
		$info["path"]  = "/";
		$info["props"] = array();
		$info["props"][] = CWebDavBase::_mkprop("resourcetype", "collection");
		$info["props"][] = CWebDavBase::_mkprop("getcontenttype", "httpd/unix-directory");

		$arr = CIBlock::GetArrayByID($this->IBLOCK_ID);
		$time = MakeTimeStamp($arr["TIMESTAMP_X"]);
		$year = date("Y");

		$info["props"][] = CWebDavBase::_mkprop("creationdate", mktime(0, 0, 0, 1, 1, $year));
		$info["props"][] = CWebDavBase::_mkprop("getlastmodified", $time);
		$info["props"][] = CWebDavBase::_mkprop("iscollection", 1);

		return $info;
	}

	function getDocCount($arFilter = array())
	{
		static $dataType = 'DocCount';

		$itemsCount = $this->_dataCache($dataType);
		if ($itemsCount === false)
		{

			if ($this->arRootSection)
				$arFilter['SECTION_ID'] = $this->arRootSection['ID'];

			$arFilter = $arFilter + Array("IBLOCK_ID" => $this->IBLOCK_ID, "INCLUDE_SUBSECTIONS" => "Y", "ACTIVE" => "Y");
			$itemsCount = 0;
			$res = CIBlockElement::GetList(array(), $arFilter, false, false, array('ID'));
			if ($res)
			{
				$res->NavStart();
				$itemsCount = $res->NavRecordCount;

				$arFilter["SECTION_ID"] = $this->GetMetaID("TRASH");

				$res = CIBlockElement::GetList( array(), $arFilter, false, false, array('ID'));
				if ($res)
				{
					$res->NavStart();
					$trashItemsCount = $res->NavRecordCount;
					$itemsCount -= $trashItemsCount;
				}
			}
			$this->_dataCache($dataType, $itemsCount);
		}

		return $itemsCount;
	}

	function checkLock($path, array $element = array())
	{
		$result =  false;
		$options = array("path" => $path);
		$arProps = $this->_get_lock($options, $element);
		if (!is_array($arProps)) return $arProps; // error in _get_lock

		if (array_key_exists("LOCK", $arProps) && is_array($arProps["LOCK"]) && count($arProps["LOCK"]) > 0)
		{
			$k = reset(array_keys($arProps["LOCK"]));

			$row = $arProps["LOCK"][$k];

			$result = array(
				"type"	  => "write",
				"scope"   => $row["exclusivelock"] ? "exclusive" : "shared",
				"depth"   => 0,
				"owner"   => $row["owner"],
				"token"   => $k,
				"created" => $row["created"],
				"modified" => $row["modified"],
				"expires" => $row["expires"]);
		}
		return $result;
	}

	function GetProperties()
	{
		static $dataType = 'GetIblockProperties';
		$arProps = $this->_dataCache($dataType);
		if (!$arProps)
		{
			$rIBProps = CIBlockProperty::GetList(
				Array(
					"SORT" => "ASC",
					"NAME" => "ASC"
				),
				Array(
					"ACTIVE" => "Y",
					"IBLOCK_ID" => $this->IBLOCK_ID
				)
			);
			if (!$rIBProps)
				return false;

			$arProps = array();
			while ($arIBProp = $rIBProps->Fetch())
			{
				$arProps[$arIBProp['CODE']] = $arIBProp;
			}

			$this->_dataCache($dataType, $arProps);
		}
		return $arProps;
	}

	function _check_iblock_prop($arProperty = false)
	{
		static $dataType = 'IblockProperties';
		$checked = $this->_dataCache($dataType);

		if (!$checked)
		{
			$arProps = $this->GetProperties();

			foreach ($arProps as $code => &$arIBProp)
			{
				if (!isset($arProperty[$code]))
					continue;

				if (($arIBProp['PROPERTY_TYPE'] != $arProperty[$code]['type']) || ($arIBProp['USER_TYPE'] != null))
					continue;

				unset($arProperty[$code]);
			}

			$prp = new CIBlockProperty();

			foreach ($arProperty as $sPropertyCode => $arPropertyParams)
			{
				$properties = array(
					"IBLOCK_ID" => $this->IBLOCK_ID,
					"ACTIVE" => "Y",
					"CODE" => $sPropertyCode,
					"MULTIPLE" => "N",
					"PROPERTY_TYPE" => $arPropertyParams["type"],
					"NAME" => $arPropertyParams["name"]);

				if (isset($arPropertyParams["properties"]))
					$properties+=$arPropertyParams["properties"];

				$res = $prp->Add($properties);
			}

			if (($this->FORUM_ID !== null) && empty($this->arRootSection) && CModule::IncludeModule('forum'))
			{
				$arForum = CForumNew::GetByID($this->FORUM_ID);
				if ($arForum['ACTIVE'] == 'Y')
				{
					$arSites = CForumNew::GetSites($this->FORUM_ID);
					foreach ($arSites as $siteID => $forumUrl)
					{
						if (strpos($forumUrl, '/community/forum/') === 0)
						{
							$arSites[$siteID] = str_replace(array('///', '//'), '/', $this->base_url . "/element/comment/#TOPIC_ID#/#MESSAGE_ID#/");
						}
					}

					$arUpForum = array();
					$arUpForum["SITES"] = $arSites;
					CForumNew::Update($this->FORUM_ID, $arUpForum);
				}
			}

			$this->_dataCache($dataType, true);
		}
	}

	function IsDir($options = array(), $replace_symbols = false)
	{
		$this->arParams = $this->GetObject($options, $replace_symbols);
		return $this->arParams["is_dir"];
	}

	function _get_element_cacheID($path, $replace_symbols = false)
	{
		return md5(intval($this->IBLOCK_ID).'|'.intval($this->arRootSection['ID']).'|'.$path.($replace_symbols ? "Y" : "N"));
	}

	function GetObject($options = array(), $replace_symbols = false, $force = false)
	{
		$options = (is_array($options) ? $options : array());
		if (!isset($options['check_permissions']))
			$options['check_permissions'] = true;

		if(array_key_exists("path", $options))
		{
			$path = $this->_udecode($options["path"]);
		}
		else
		{
			if(array_key_exists('section_id', $options))
			{
				$path = 'section' . $options['section_id'];
			}
			elseif(array_key_exists('element_id', $options))
			{
				$path = 'element' . $options['element_id'];
			}
			else
			{
				$path = $this->_path;
			}
		}

		$arSelectFields = array("ID", "NAME", "IBLOCK_ID", "IBLOCK_SECTION_ID", "PREVIEW_TEXT",
			"CREATED_BY", 'MODIFIED_BY', 'DATE_CREATE', "TIMESTAMP_X", "PROPERTY_" . static::PROPERTY_VERSION, "PROPERTY_".$this->file_prop, "PROPERTY_WEBDAV_INFO",
			"WF_NEW", "WF_PARENT_ELEMENT_ID", "WF_STATUS_ID",
			"IBLOCK_CODE", "BP_PUBLISHED");

		$contextCache = '';
		if(!empty($this->attributes['user_id']))
		{
			$contextCache = $this->attributes['user_id'] . (empty($options['section_id'])? '' : 'section' . $options['section_id']);
		}
		elseif(!empty($this->attributes['group_id']))
		{
			$contextCache = $this->attributes['group_id'] . (empty($options['section_id'])? '' : 'section' . $options['section_id']);
		}

		$id = $this->_get_element_cacheID($path, $replace_symbols).($options['check_permissions']?'Y':'N').'_'.$contextCache;

		//todo!!!!!
		if (!$force && isset($this->CACHE[__METHOD__][$id]))
		{
			$arParams = array_merge($this->arParams, $this->CACHE[__METHOD__][$id]);
			return $arParams;
		}

		/**
		 * Analize path part "folder#section_id#/#element_id#"
		 */
		$arKeys = array();
		if (!empty($this->short_path_template) &&
			preg_match_all("/(?<=\#)([a-z_]+)(?=\#)/is", strToLower($this->short_path_template), $arKeys))
		{
			$arKeys = (is_array($arKeys) ? $arKeys[0] : false);
			if (!empty($arKeys) && is_array($arKeys))
			{
				$pageTemplateReg = "'^".preg_replace("'#[^#]+?#'", "([^/]*)", $this->short_path_template)."'";
				$arValues = array();
				if (preg_match($pageTemplateReg, $path, $arValues))
				{
					array_shift($arValues);
					$arValues = @array_combine($arKeys, $arValues);
					if (is_array($arValues))
					{
						if (!empty($arValues["element_id"]) && intval($arValues["element_id"]) > 0)
							$options["element_id"] = (int)$arValues["element_id"];
						elseif (!empty($arValues["section_id"]) && intval($arValues["section_id"]) > 0)
							$options["section_id"] = (int)$arValues["section_id"];
					}
				}
			}
		}

		$params = array(
			"item_id" => (is_array($this->arRootSection) ? $this->arRootSection["ID"] : 0),
			"not_found" => false,
			"is_dir" => false,
			"is_file" => false,
			"parent_id" => false,
			"basename" => "");

		$arFilter = array("IBLOCK_ID" => $this->IBLOCK_ID);
		if($this->arRootSection && is_array($this->arRootSection))
		{
			$arFilter["RIGHT_MARGIN"] = intVal($this->arRootSection["RIGHT_MARGIN"]) - 1;
			$arFilter["LEFT_MARGIN"] = intVal($this->arRootSection["LEFT_MARGIN"]) + 1;
		}
		if ($options["show_history"] == "Y")
			$arFilter["SHOW_HISTORY"] = "Y";
		$arFilter['CHECK_PERMISSIONS'] = ($options['check_permissions'] ? 'Y' : 'N');

		$arPath = explode("/", trim($path, "/"));
		$arNewPath = array();
		foreach ($arPath as $p)
		{
			if (strlen(trim($p)) > 0)
				$arNewPath[] = $p;
		}
		$arPath = $arNewPath;

		$arElement = array();

		foreach ($arPath as $pathID => $pathName)
		{
			foreach($this->meta_names as $metaName => $metaArr)
			{
				if ($pathName == $metaArr["alias"])
					$arPath[$pathID] = $metaArr["name"];
			}
		}

		if (is_set($options, "section_id"))
		{
			if (empty($options["section_id"]) || ($this->arRootSection && $options["section_id"] == $this->arRootSection["ID"]))
			{
				$params["is_dir"] = true;
				$params["dir_array"] = array("ID" => 0, "DEPTH_LEVEL" => 0, "LEFT_MARGIN" => 0, "RIGHT_MARGIN" => $this->INT_MAX);
			}
			else
			{
				$arFilter["ID"] = $options["section_id"];
				//todo check if section_id in symlink section
				if(1)
				{
					if($this->arParams['is_dir'] && !empty($this->arParams['dir_array']['IBLOCK_ID']))
					{
						$arFilter['IBLOCK_ID'] = $this->arParams['dir_array']['IBLOCK_ID'];
					}
					$iblockId = CWebDavSymlinkHelper::getIblockIdForSectionId($options["section_id"]);
					if($iblockId)
					{
						$arFilter['IBLOCK_ID'] = $iblockId;
					}
					unset($arFilter["RIGHT_MARGIN"], $arFilter["LEFT_MARGIN"]);
				}
				$rs = CIBlockSection::GetList(array(), $arFilter, false, self::getUFNamesForSectionLink());
				if ($arr = $rs->Fetch())
				{
					$params["item_id"] = intval($arr["ID"]);
					$params["parent_id"] = intval($arr["IBLOCK_SECTION_ID"]);
					$params["is_dir"] = true;
					$params["dir_array"] = $arr;
					$params["basename"] = $arr['NAME'];
				}
				else
				{
					$params["not_found"] = true;
				}
			}
		}
		elseif (is_set($options, "element_id"))
		{
			$arFilter = array(
				"SHOW_NEW" => "Y",
				"SHOW_HISTORY" => "Y",
				"ID" => $options["element_id"],
				"IBLOCK_ID" => $this->IBLOCK_ID,
				"CHECK_PERMISSIONS" => ($options['check_permissions'] ? 'Y' : 'N')
			);

			list($contextType, $contextEntityId) = $this->getContextData();
			if(CWebDavSymlinkHelper::isLinkElement($contextType, $contextEntityId, $options["element_id"]))
			{
				$parentSectionData = CWebDavSymlinkHelper::getLinkDataOfElement($contextType, $contextEntityId, $options["element_id"]);
				if(!empty($parentSectionData[self::UF_LINK_IBLOCK_ID]))
				{
					$arFilter['IBLOCK_ID'] = $parentSectionData[self::UF_LINK_IBLOCK_ID];
				}
			}

			$db_res = CIBlockElement::GetList(
				null,
				$arFilter,
				false,
				false,
				$arSelectFields
			);
			if (!($db_res && $arElement = $db_res->Fetch()))
			{
				$params["not_found"] = true;
			}
			else
			{
				$params["is_file"] = true;
				$params["parent_id"] = intval($arElement["IBLOCK_SECTION_ID"]);
				$params["item_id"] = $options["element_id"];
				$params["basename"] = $arElement['NAME'];
				if ($this->e_rights)
				{
					$arElement['E_RIGHTS'] = $this->GetPermission('ELEMENT', $arElement['ID']);
				}
				if ($this->arRootSection && $this->arRootSection["ID"] != $arElement["IBLOCK_SECTION_ID"])
				{
					$arFilter["ID"] = $arElement["IBLOCK_SECTION_ID"];
					$rs = CIBlockSection::GetList(array(), $arFilter, false, array('ID'));
					if ($arr = $rs->Fetch())
					{
						$params["item_id"] = intval($arr["ID"]);
					}
					else
					{
						$params["not_found"] = true;
					}
				}
			}
		}
		elseif (count($arPath) > 0)
		{
			$basename = array_pop($arPath);
			$params['basename'] = ($replace_symbols == true ? $this->CorrectName($basename) : $basename);

			foreach ($arPath as $dir)
			{
				$arFilter["SECTION_ID"] = $params["item_id"];
				$arFilter["=NAME"] = $dir;

				$rs = CIBlockSection::GetList(array(), $arFilter, false, array_merge(array('ID'), self::getUFNamesForSectionLink()));
				if ($rs && $arr = $rs->Fetch())
				{
					$params["item_id"] = intval($arr["ID"]);
					if(!empty($arr[self::UF_LINK_SECTION_ID]))
					{
						$params["item_id"] = intval($arr[self::UF_LINK_SECTION_ID]);
						$arFilter['IBLOCK_ID'] = $arr[self::UF_LINK_IBLOCK_ID];
						unset($arFilter["RIGHT_MARGIN"], $arFilter["LEFT_MARGIN"]);
					}
				}
				else
				{
					$params["not_found"] = true;
					break;
				}
			}

			if ($params["not_found"] == false)
			{
				$params["parent_id"] = intVal($params["item_id"]);

				$arFilter["SECTION_ID"] =  $params["item_id"];
				$arFilter["=NAME"] = $params["basename"];

				$rs = CIBlockSection::GetList(array(), $arFilter, false, self::getUFNamesForSectionLink());
				if ($arr = $rs->Fetch())
				{
					$params["item_id"] = intval($arr["ID"]);
					$params["is_dir"] = true;
					$params["dir_array"] = $arr;
				}
				else
				{
					$arOrder = array("ID" => "ASC");
					$arFilter = array(
						"IBLOCK_ID" => empty($arFilter['IBLOCK_ID'])? $this->IBLOCK_ID : $arFilter['IBLOCK_ID'], //set iblockId to iblockId in prev.lookup (symlink logic)
						"SECTION_ID" =>  $params["item_id"],
						"CHECK_PERMISSIONS" => ($options['check_permissions'] ? 'Y' : 'N'),
						"=NAME" => $params["basename"],
						"SHOW_HISTORY" => "N",
						"SHOW_NEW" => "Y");

					if ($this->workflow == 'workflow')
					{
						$arOrderWF = array("ID" => "DESC");
						$arFilterWF = $arFilter;
						$arFilterWF["SHOW_HISTORY"] = "Y";
						$arFilterWF[">WF_PARENT_ELEMENT_ID"] = 0;

						$db_res = CIBlockElement::GetList($arOrderWF, $arFilterWF, false, false, $arSelectFields);
						if ($db_res && $res = $db_res->Fetch())
						{
							$arBuff = array();
							do
							{
								$res["LAST_ID"] = CIBlockElement::WF_GetLast($res["ID"]);
								if (!in_array($res["LAST_ID"], $arBuff))
								{
									$db_res = CIBlockElement::GetList($arOrderWF, array("ID" => $res["LAST_ID"], "SHOW_HISTORY" => "Y"), false, false, $arSelectFields);
									if ($db_res && $res = $db_res->Fetch()):
										if ($res["NAME"] == $params["basename"] && (intval($res["IBLOCK_SECTION_ID"]) == intval($params["item_id"]))):
											$original = $arElement = $res;
											if ($res["WF_PARENT_ELEMENT_ID"] > 0)
											{
												$arFilterWF = array("ID" => $arElement["WF_PARENT_ELEMENT_ID"], "SHOW_HISTORY" => "Y");
												$db_res = CIBlockElement::GetList($arOrderWF, $arFilterWF, false, false, $arSelectFields);
												if ($db_res)
													$original = $db_res->Fetch();
											}
											$arElement["REAL_ELEMENT_ID"] = $arElement["ID"];
											$arElement["ID"] = $original["ID"];
											$arElement["PROPERTY_WEBDAV_INFO_VALUE"] = $original["PROPERTY_WEBDAV_INFO_VALUE"];
											$arElement["PROPERTY_WEBDAV_INFO_VALUE_ID"] = $original["PROPERTY_WEBDAV_INFO_VALUE_ID"];
											$arElement["original"] = $original;
											break;
										endif;
									endif;
									$arBuff[] = $res["LAST_ID"];
								}
							} while ($res = $db_res->Fetch());
						}
					}

					if (empty($arElement))
					{
						$db_res = CIBlockElement::GetList($arOrder, $arFilter, false, false, $arSelectFields);
						if ($db_res && $res = $db_res->Fetch()):
							$arElement = $res;
						endif;
					}

					if (!empty($arElement))
					{
						$params["is_file"] = true;
					}
					else
					{
						$params["not_found"] = true;
					}
				}
			}
		}
		else
		{
			$params["is_dir"] = true;
		}

		if (!$params["not_found"] && !empty($arElement) && $params["is_file"])
		{
			$params["element_id"] = $params["item_id"] = $arElement["ID"];
			$params["original"] = $arElement["original"];
			unset($arElement["original"]);
			$params["element_array"] = $arElement;
			$params["element_name"] = $arElement["NAME"];
			$params["file_name"] = $arElement["NAME"];
			$params["file_extention"] = strtolower(strrchr($arElement["NAME"] , '.'));

			$arFile = CFile::GetFileArray($arElement["PROPERTY_".$this->file_prop."_VALUE"]);

			$params["file_id"] = $arFile["ID"];
			$params["file_mimetype"] = $arFile["CONTENT_TYPE"];
			$params["file_size"] = $arFile["FILE_SIZE"];
			$params["fullpath"] = $arFile["SRC"];
			$params["file_array"] = $arFile;
		}
		elseif ($params["not_found"])
		{
			$params["item_id"] = false;
		}

		$this->CACHE[__METHOD__][$id] = $params;
		return $params;
	}

	function GetObjectPath($arObject, $convert = false)
	{
		$result = null;
		if ($arObject['is_dir'])
		{
			$result = $this->_get_path($arObject['item_id'], $convert);
		}
		elseif ($arObject['is_file'])
		{
			$result = str_replace(array("///","//"), "/", $this->_get_path($arObject['parent_id'], false) . "/" . $arObject['element_name']);
			if ($convert)
				$result = $this->_uencode($result, array("utf8" => "Y", "convert" => "full"));
		}
		return $result;
	}

	function IsLocked($ID, $IBLOCK_ID, &$params) // not used
	{
		$params = array("locked_by" => 0, "date_lock" => "");
		$bLocked = true;
		if ($ID <= 0 || $IBLOCK_ID <= 0):
			$bLocked = false;
		elseif (!CIBlockElement::WF_IsLocked($ID, $locked_by, $date_lock)):
			$bLocked = false;
		elseif (intVal($locked_by) == $GLOBALS["USER"]->GetID()):
			$bLocked = false;
		elseif (IsModuleInstalled("workflow")):
			if (CModule::IncludeModule("workflow") && CIBlock::GetArrayByID($IBLOCK_ID, "WORKFLOW") != "N"):
				$bLocked = (CWorkflow::IsAdmin() ? false : true);
			endif;
		endif;
		$db_res = CUser::GetByID($locked_by);
		if ($db_res && $arUser = $db_res->GetNext())
		{
			$locked_by = '['.$arUser["ID"].'] '.trim($arUser["LAST_NAME"]." ".$arUser["NAME"]);
		}

		$params = array("locked_by" => $locked_by, "date_lock" => $date_lock);
		return $bLocked;
	}

	function IsTrashEmpty()
	{
		static $dataType = 'TrashEmpty';
		$trashEmpty = $this->_dataCache($dataType);

		if ($trashEmpty === false)
		{
			$trashEmpty = 'Y';
			$trashSectionID = $this->GetMetaID('TRASH');
			$cnt = CIBlockElement::GetList(
				Array("ID"=>"DESC"),
				$arFilter=Array("SHOW_HISTORY"=>"Y", "IBLOCK_ID"=>$this->IBLOCK_ID, "SECTION_ID"=>$trashSectionID),
				array(),
				false,
				array('ID')
			);
			if ($cnt > 0)
			{
				$trashEmpty = 'N';
			}
			else
			{
				$dbTrash = CIBlockSection::GetList(Array("ID"=>"DESC"), $arFilter=Array("SHOW_HISTORY"=>"Y", "IBLOCK_ID"=>$this->IBLOCK_ID, "SECTION_ID"=>$trashSectionID), false, array("ID"));
				if ($dbTrash && $arTrashItem = $dbTrash->Fetch())
				{
					$trashEmpty = 'N';
				}
			}

			$this->_dataCache($dataType, $trashEmpty);
		}
		return ($trashEmpty==='Y');
	}

	/**
	 * This method send file to client by CFile::ViewByUser()
	 * And it is used for send documents to the client Bitrix24.Disk
	 * All doc's is published
	 * @param       $id
	 * @param array $misc
	 * @return bool
	 */
	function fileViewByUser($id, array $misc = array())
	{
		$id = (int)$id;
		$iblockId = $this->IBLOCK_ID;
		if(!empty($misc['IBLOCK_ID']))
		{
			$iblockId = $misc['IBLOCK_ID'];
		}
		$filter = array('IBLOCK_ID' => $iblockId, 'ID' => $id, 'CHECK_PERMISSIONS' => 'Y', 'SHOW_NEW' => 'Y');
		if ($this->permission < 'U')
		{
			$filter['SHOW_HISTORY'] = 'N';
		}

		$object = $this->GetObject(array('element_id' => $id, 'check_permissions' => false), false);
		if(!$this->CheckWebRights('',  array('action' => 'read', 'arElement' => $object), false))
		{
			return false;
		}

		$dbRows = CIBlockElement::GetList(array(), $filter,
			false, array('nTopCount' => 1), array('ID', 'NAME', 'TIMESTAMP_X', 'IBLOCK_ID', 'PROPERTY_' . $this->file_prop));
		if ($dbRows && $row = $dbRows->Fetch())
		{
			return CFile::ViewByUser($row['PROPERTY_FILE_VALUE'], array('content_type' => 'application/octet-stream', 'force_download' => true));
		}
		return false;
	}

	function countHistoryDocumentByFileId($elementId)
	{
		$elementId = (int)$elementId;
		if($elementId <= 0)
		{
			return 0;
		}

		if(($this->workflow != 'bizproc' && $this->workflow != 'bizproc_limited'))
		{
			return 0;
		}

		$filter  = array(
			"DOCUMENT_ID" => array(
				$this->wfParams['DOCUMENT_TYPE'][0],
				$this->wfParams['DOCUMENT_TYPE'][1],
				$elementId
			),
		);

		$history = new CBPHistoryService();

		return $history->GetHistoryList(array(), $filter, array());
	}

	function findHistoryDocumentByFileId($elementId, $fileId, $documentId)
	{
		$elementId = (int)$elementId;
		$fileId = (int)$fileId;

		if($fileId <= 0 || $elementId <= 0 || empty($documentId))
		{
			return array();
		}

		if(($this->workflow != 'bizproc' && $this->workflow != 'bizproc_limited'))
		{
			return array();
		}

		$by      = "modified";
		$order   = "desc";
		$history = new CBPHistoryService();
		$dbDocumentHistory = $history->GetHistoryList(
			array(strtoupper($by) => strtoupper($order)),
			array(
				"DOCUMENT_ID" => $documentId,
			),
			false,
			array('nTopCount' => 20), //todo we search by OLD_FILE_ID this is not true. 20 - magic. And we trust in him
			array(
				"ID",
				"DOCUMENT_ID",
				"NAME",
				"MODIFIED",
				"USER_ID",
				"USER_NAME",
				"USER_LAST_NAME",
				"USER_LOGIN",
				"DOCUMENT",
				"USER_SECOND_NAME"
			)
		);

		while($document = $dbDocumentHistory->fetch())
		{
			if(!empty($document['DOCUMENT']['OLD_FILE_ID']) && $document['DOCUMENT']['OLD_FILE_ID'] == $fileId)
			{
				return $document;
			}
		}

		return $document;
	}

	/**
	 * @param       $ID
	 * @param int   $WF_ID
	 * @param bool  $NotCheckWebRights
	 * @param array $params
	 */
	function SendHistoryFile($ID, $WF_ID = 0, $NotCheckWebRights = false, $params = array()) // wrong
	{
		$ID = intval($ID);
		$WF_ID = intval($WF_ID);
		if($ID <=0)
		{
			return;
		}

		list($contextType, $contextEntityId) = $this->getContextData();
		if(CWebDavSymlinkHelper::isLinkElement($contextType, $contextEntityId, $ID))
		{
			$parentSectionData = CWebDavSymlinkHelper::getLinkDataOfElement($contextType, $contextEntityId, $ID);
			if($parentSectionData)
			{
				$linkWebdav = new self($parentSectionData[self::UF_LINK_IBLOCK_ID], $this->base_url . $this->_path, array(
					'ROOT_SECTION_ID' => $parentSectionData[self::UF_LINK_SECTION_ID],
					'symlinkMode' => true,
					'symlinkSectionData' => $parentSectionData,
					'symlinkRealRootSectionData' => $this->arRootSection,
				));
				if($this->withoutAuthorization)
				{
					$linkWebdav->withoutAuthorization = true;
				}
				return $linkWebdav->SendHistoryFile($ID, $WF_ID, $NotCheckWebRights, $params);
			}
		}

		$io = self::GetIo();
		list($arFile, $options, $fullpath, $filename) = $this->getHistoryFileData($ID, $WF_ID, $params);

		if (empty($arFile))
		{
			return;
		}

		$options["logica_full_path"] = $fullpath;
		if (!file_exists($fullpath) && file_exists($io->GetPhysicalName($fullpath)))
			$fullpath = $io->GetPhysicalName($fullpath);

		$options["path"] = $this->_path;
		$options["mimetype"] = (
		(!empty($arFile["CONTENT_TYPE"]) && $arFile["CONTENT_TYPE"] != "unknown" && $arFile["CONTENT_TYPE"] != "application/octet-stream") ?
			$arFile["CONTENT_TYPE"] : $this->get_mime_type($filename));
		$options["size"] = !empty($arFile["FILE_SIZE"]) ? $arFile["FILE_SIZE"] : 0;
		$options["name"] = $filename;
		if(!$NotCheckWebRights)
		{
			$arElementData = $this->GetObject(array("element_id" => $ID, "check_permissions" => false), false);
			if(!$this->CheckWebRights("",  array("action" => "read", "arElement" => $arElementData), false))
			{
				return;
			}
		}
		if ($options["resized"] == "Y" || $params["cache_image"] == "Y")
		{
			CFile::ViewByUser($arFile, array("content_type" => $options["mimetype"], "cache_time" => $options["cache_time"]));
		}
		if (file_exists($fullpath))
		{
			if(empty($options['size']))
			{
				$options['size'] = filesize($fullpath);
			}
			$options["mtime"] = filemtime($fullpath);
			$options["stream"] = fopen($fullpath, "r");
		}
		$x = $this->SendFile($options); //, true
	}

	/**
	 * @param $id
	 * @param $wfId
	 * @param $params
	 * @return array
	 */
	public function getHistoryFileData($id, $wfId, &$params)
	{
		$fullpath = '';
		$options = $arFile = $arr = array();
		$io = self::GetIo();
		$arFilter = array(
			"IBLOCK_ID" => (isset($this->arParams['element_array']['ID']) && $this->arParams['element_array']['ID'] == $id)? $this->arParams['element_array']['IBLOCK_ID'] : $this->IBLOCK_ID,
			"ID" => $id,
			"SHOW_HISTORY" => "Y",
			"CHECK_PERMISSIONS" => "Y"
		);
		if ($this->permission < "U")
		{
			$arFilter["SHOW_HISTORY"] = "N";
		}
		if (($this->workflow == 'bizproc' || $this->workflow == 'bizproc_limited') && $wfId > 0)
		{
			$history = new CBPHistoryService();
			$db_res  = $history->GetHistoryList(array("ID" => "DESC"),
				array(
					"DOCUMENT_ID" => array(
						$this->wfParams['DOCUMENT_TYPE'][0],
						$this->wfParams['DOCUMENT_TYPE'][1],
						$id
					),
					"ID" => $wfId
				), false, false, array("ID", "DOCUMENT", "NAME"));
			if ($db_res && $arr = $db_res->Fetch())
			{
				$arFile      = array(
					"SRC" => $arr["DOCUMENT"]["PROPERTIES"][$this->file_prop]["VALUE"],
					"NAME" => $arr["DOCUMENT"]["NAME"]
				);
				$arr["NAME"] = $arr["DOCUMENT"]["NAME"];

				$fullpath = urldecode($arFile["SRC"]);
				if (substr($fullpath, 0, 4) != "http")
				{
					$fullpath = $io->GetPhysicalName($_SERVER['DOCUMENT_ROOT'] . $arFile["SRC"]);
				}
				else
				{
					$fullpath = CWebDavTools::urlEncode($fullpath);
				}
				$arTmpFile = CFile::MakeFileArray($fullpath);
				$fullpath = $arTmpFile['tmp_name'];
				$arFile["FILE_SIZE"] = $arTmpFile["size"];
			}
		}
		else
		{
			$arFilter['CHECK_PERMISSIONS'] = 'N'; //check permissions manual! While infoblock perm's check by every section's (in dropped - we have error).
			$rs = CIBlockElement::GetList(array(), $arFilter, false, array("nTopCount" => 1), array(
				"ID",
				"NAME",
				"TIMESTAMP_X",
				"IBLOCK_ID",
				"PROPERTY_" . $this->file_prop
			));
			if ($rs && $arr = $rs->Fetch())
			{
				if (empty($this->arParams["element_array"]))
					$this->arParams["element_array"] = $arr;
				$arFile                = CFile::GetFileArray($arr["PROPERTY_FILE_VALUE"]);
				$options["from_cloud"] = (intval($arFile['HANDLER_ID']) > 0 ? "Y" : "N");
				$arTmpFile             = array();
				if (CFile::IsImage($arFile["FILE_NAME"], $arFile["CONTENT_TYPE"]))
				{
					if ($params["width"] > 0 || $params["height"] > 0)
					{
						$arTmpFile = CFile::ResizeImageGet($arFile, array(
							"width" => $params["width"],
							"height" => $params["height"]
						), ($params["exact"] == "Y" ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL), true, false, true);

						$arTmpFile['tmp_name'] = ($options["from_cloud"] == "Y" ? "" : $_SERVER["DOCUMENT_ROOT"]) . $arTmpFile["src"];
						$options["resized"]    = "Y";
						$arFile["FILE_SIZE"]   = $arTmpFile["size"];
						$arFile["SRC"]         = $arTmpFile["src"];
					}
					if ($params["cache_image"] == "Y")
					{
						$options["cache_time"] = 86400;
					}
				}
				else
				{
					$params["cache_image"] = "N";
				}
				if (empty($arTmpFile))
				{
					$arTmpFile = CFile::MakeFileArray($arr["PROPERTY_FILE_VALUE"]);
				}
				$fullpath = $arTmpFile['tmp_name'];
			}
		}
		$elementName = $arr['NAME'];

		return array($arFile, $options, $fullpath, $elementName);
	}

	function GetHistoryFileID($elementID)
	{
		$elementID = intval($elementID);
		if($elementID <= 0)
		{
			return 0;
		}

		$arFilter = array("IBLOCK_ID" => $this->IBLOCK_ID, "ID" => $elementID, "SHOW_HISTORY"=>"Y", "CHECK_PERMISSIONS" => "Y");
		if ($this->permission < "U")
		{
			$arFilter["SHOW_HISTORY"] = "N";
		}

		$dbElement = CIBlockElement::GetList(array(), $arFilter,
			false, array("nTopCount" => 1), array("ID", "NAME", "IBLOCK_ID", "PROPERTY_" . $this->file_prop));

		$arElement = $dbElement->Fetch();
		return $arElement ? intval($arElement["PROPERTY_FILE_VALUE"]) : 0;
	}

	function UpdateRootSection()
	{
		if ($this->arRootSection !== false)
			$this->SetRootSection($this->arRootSection['ID'], true); // MARGINs changed
		if (isset($this->CACHE['CWebDavIblock::GetObject']))
			$this->CACHE['CWebDavIblock::GetObject'] = array();
	}

	static function GetReaders($ID, $iblockID = null)
	{
		static $arValidTasks = null;
		static $readersCache = array();

		$arReaders = array();

		$ID = (int) $ID;
		if ($ID <= 0)
			return $arReaders;

		if (isset($readersCache[$ID]))
			return $readersCache[$ID];

		if ($arValidTasks == null)
		{
			$arTasks = CWebDavIblock::GetTasks();
			$arValidTasks = array();
			foreach ($arTasks as $taskLetter => $taskID)
			{
				$arOperations = CTask::GetOperations($taskID, true);
				if (array_search('element_read', $arOperations) !== false)
					$arValidTasks[$taskID] = true;
			}
		}

		if ($iblockID === null)
		{
			$rElement = CIBlockElement::GetList(
				array(),
				array(
					'ID' => $ID,
					'SHOW_NEW' => 'Y',
				),
				false,
				false,
				array(
					'ID',
					'IBLOCK_ID',
				)
			);

			if ($rElement && $arElement = $rElement->Fetch())
			{
				$iblockID = $arElement['IBLOCK_ID'];
			}
		}

		$iblockID = (int) $iblockID;
		if ($iblockID <= 0)
			return $arReaders;

		$bSocNet = (CModule::IncludeModule('socialnetwork'));

		if (CIBlock::GetArrayByID($iblockID, "RIGHTS_MODE") === "E")
		{
			$ibRights = new CIBlockElementRights($iblockID, $ID);
			$arRights = $ibRights->GetRights();
			foreach($arRights as $rightID => $arRight)
			{
				if (isset($arValidTasks[$arRight['TASK_ID']]))
				{
					$arReaders[] = $arRight['GROUP_CODE'];
					if (
						$bSocNet
						&& preg_match(
								'/^SG(\d+)_['.SONET_ROLES_OWNER.SONET_ROLES_MODERATOR.SONET_ROLES_USER.']$/',
								$arRight['GROUP_CODE'],
								$matches
							)
						)
							$arReaders[] = "SG".$matches[1];
				}
			}
		}
		else
		{
			$gr_res = CIBlock::GetGroupPermissions($iblockID);
			foreach($gr_res as $group_id => $perm)
			{
				if ($perm >= 'R')
					$arReaders[] = 'G'.$group_id;
			}
		}

		$readersCache[$ID] = array_unique($arReaders);

		return $readersCache[$ID];
	}

	static function UpdateSearchRights($ID, $iblockID = null)
	{
		if (!CModule::IncludeModule('search'))
			return true;

		$ID = (int) $ID;
		if ($ID <= 0)
			return false;

		if ($iblockID === null)
		{
			$rElement = CIBlockElement::GetList(
				array(),
				array(
					'ID' => $ID,
					'SHOW_NEW' => 'Y',
				),
				false,
				false,
				array(
					'ID',
					'IBLOCK_ID',
				)
			);

			if ($rElement && $arElement = $rElement->Fetch())
			{
				$iblockID = $arElement['IBLOCK_ID'];
			}
		}

		$iblockID = (int) $iblockID;
		if ($iblockID <= 0)
			return false;

		$code = CIBlock::GetArrayByID($iblockID, "CODE");

		$bSocNet = (
			(strpos($code, "user_files") === 0)
			|| (strpos($code, "group_files") === 0)
		);

		if (
			! $bSocNet
			&& CIBlock::GetArrayByID($iblockID, "INDEX_ELEMENT") === "N")
		{
			return false;
		}

		$arReaders = self::GetReaders($ID, $iblockID);

		CSearch::ChangePermission(
			($bSocNet ? 'socialnetwork' : 'iblock'),
			$arReaders,
			$ID
		);

		return true;
	}

	public static function sCheckUniqueName($iblockId, $sectionId, $workflow, $basename, &$res)
	{
		$iblockId = intVal($iblockId);
		$sectionId = intVal($sectionId);
		$basename = trim($basename);
		if (empty($basename))
			return false;
		$arObject = array();
		$arSelectFields = array("ID", "NAME", "IBLOCK_ID", "IBLOCK_SECTION_ID", "WF_PARENT_ELEMENT_ID", "WF_STATUS_ID", "CREATED_BY");
		$arFilter = array(
			"IBLOCK_ID" => $iblockId,
			"SECTION_ID" => $sectionId,
			"=NAME" => $basename,
			'CHECK_PERMISSIONS' => 'N',
			"SHOW_NEW" => ($workflow == 'workflow' || $workflow == 'bizproc' ? "Y" : "N")
		);
		$arOrder = array("ID" => "ASC");

		$db_res = CIBlockElement::GetList($arOrder, $arFilter, false, false, $arSelectFields);
		if ($db_res && $res = $db_res->Fetch())
		{
			$arObject = array(
				"object" => "element",
				"id" => $res["ID"],
				"data" => $res);
		}
		else
		{
			$db_res = CIBlockSection::GetList($arOrder, $arFilter, false, $arSelectFields);
			if ($db_res && $res = $db_res->Fetch())
			{
				$arObject = array(
					"object" => "section",
					"id" => $res["ID"],
					"data" => $res);
			}
		}

		if (!empty($arObject))
		{}
		elseif ($workflow == 'workflow' || $workflow == 'bizproc')
		{
			$arFilter["SHOW_HISTORY"] = "Y";
			$db_res = CIBlockElement::GetList($arOrder, $arFilter, false, false, $arSelectFields);
			if ($db_res && $res = $db_res->Fetch())
			{
				$arBuff = array();
				do
				{
					$res["LAST_ID"] = CIBlockElement::WF_GetLast($res["WF_PARENT_ELEMENT_ID"]);
					if (empty($arBuff[$res["LAST_ID"]]))
					{
						$db_res = CIBlockElement::GetList($arOrder, array("ID" => $res["LAST_ID"], "SHOW_HISTORY" => "Y"), false, false, $arSelectFields);
						if (!($db_res && $res = $db_res->Fetch())):
							$arObject = array(
								"object" => "element",
								"id" => $res["LAST_ID"],
								"data" => "element_is_not_found");
							break;
						elseif ($arBuff[$res["LAST_ID"]]["NAME"] == $basename &&  $arBuff[$res["LAST_ID"]]["IBLOCK_SECTION_ID"] == $sectionId):
							$arObject = array(
								"object" => "element",
								"id" => $res["ID"],
								"data" => $res);
							break;
						else:
							$arBuff[$res["LAST_ID"]] = $res;
						endif;
					}
				} while ($res = $db_res->Fetch());
			}
		}
		if (empty($arObject))
		{
			return true;
		}

		$res = $arObject;
		return false;
	}

	function CheckUniqueName($basename, $sectionId, &$res)
	{
		$iblockId = $this->IBLOCK_ID;
		list($contextType, $contextEntityId) = $this->getContextData();
		$sectionData = $this->getSectionDataForLinkAnalyze($sectionId);
		if(CWebDavSymlinkHelper::isLink($contextType, $contextEntityId, $sectionData))
		{
			$parentSectionData = CWebDavSymlinkHelper::getLinkData($contextType, $contextEntityId, $sectionData);
			if($parentSectionData)
			{
				$iblockId = $parentSectionData[self::UF_LINK_IBLOCK_ID];
				if($parentSectionData['ID'] == $sectionId)
				{
					$sectionId = $parentSectionData[self::UF_LINK_SECTION_ID];
				}
			}
		}

		return self::sCheckUniqueName($iblockId, $sectionId, $this->workflow, $basename, $res);
	}

	function _get_mixed_list($section_id, $arParams = array(), $element_id = 0)
	{
		list($contextType, $contextEntityId) = $this->getContextData();
		if($section_id)
		{
			$sectionData = $this->getSectionDataForLinkAnalyze($section_id);
			if(CWebDavSymlinkHelper::isLink($contextType, $contextEntityId, $sectionData))
			{
				$parentSectionData = CWebDavSymlinkHelper::getLinkData($contextType, $contextEntityId, $sectionData);
				if($parentSectionData)
				{
					$linkWebdav = new self($parentSectionData[self::UF_LINK_IBLOCK_ID], $this->base_url . $this->_path, array(
						'ROOT_SECTION_ID' => $parentSectionData[self::UF_LINK_SECTION_ID],
						'symlinkMode' => true,
						'symlinkSectionData' => $parentSectionData,
						'symlinkRealRootSectionData' => $this->arRootSection,
					));
					return $linkWebdav->_get_mixed_list($section_id, $arParams, $element_id);
				}
			}
		}
		else
		{
			if(CWebDavSymlinkHelper::isLinkElement($contextType, $contextEntityId, $element_id))
			{
				$parentSectionData = CWebDavSymlinkHelper::getLinkDataOfElement($contextType, $contextEntityId, $element_id);
				if($parentSectionData)
				{
					$linkWebdav = new self($parentSectionData[self::UF_LINK_IBLOCK_ID], $this->base_url . $this->_path, array(
						'ROOT_SECTION_ID' => $parentSectionData[self::UF_LINK_SECTION_ID],
						'symlinkMode' => true,
						'symlinkSectionData' => $parentSectionData,
						'symlinkRealRootSectionData' => $this->arRootSection,
					));
					return $linkWebdav->_get_mixed_list($section_id, $arParams, $element_id);
				}
			}
		}

		global $by, $order;
		InitSorting();
		if (empty($by))
		{
			$by = "NAME"; $order = "ASC";
		}
		$by = strtoupper($by);
		$order = strtoupper($order);

		$section_id = ($section_id === null ? null : intVal($section_id));
		$element_id = intVal($element_id);
		$arParams = (is_array($arParams) ? $arParams : array());
		$arParams["COLUMNS"] = (is_array($arParams["COLUMNS"]) ? $arParams["COLUMNS"] : array());
		$arFilter = array(
			"IBLOCK_ID" => $this->IBLOCK_ID,
			"IBLOCK_ACTIVE" => "Y",
			"CHECK_PERMISSIONS" => "Y",
			"MIN_PERMISSION" => "R",
			"SHOW_NEW" => "Y");

		if ($section_id !== null)
			$arFilter["SECTION_ID"] = $section_id;

		$perms = $this->permission;

		if (
			$this->e_rights
			&& !$GLOBALS['USER']->CanDoOperation('webdav_change_settings')
		)
		{
			if ($element_id > 0)
			{
				$arParentPerms = $this->GetPermissions('ELEMENT', (int) $element_id);
			}
			else
			{
				$arParentPerms = $this->GetPermissions(
					'SECTION',
					(
						($section_id !== null)
						? $section_id
						: (
							($this->arRootSection)
							? $this->arRootSection
							: 0
						)
					)
				);
			}

			if (is_array($arParentPerms))
			{
				if (isset($arParentPerms['element_rights_edit']))
					$perms = 'X';
				elseif (isset($arParentPerms['element_edit_any_wf_status']))
					$perms = 'W';
				elseif (isset($arParentPerms['element_edit']))
					$perms = 'U';
				elseif (isset($arParentPerms['element_read']))
					$perms = 'R';
				else
					$perms = 'A';
			}
		}

		if ($perms < "U")
		{
			$arFilter["ACTIVE"] = "Y";
			$arFilter["GLOBAL_ACTIVE"] = "Y";
			$arFilter["SHOW_HISTORY"] = "N";
			$arFilter["SHOW_NEW"] = "N";

			$arParams["COLUMNS"] = array_diff($arParams["COLUMNS"], array("ACTIVE", "GLOBAL_ACTIVE", "SORT", "CODE", "EXTERNAL_ID", "DATE_ACTIVE_FROM", "DATE_ACTIVE_TO"));
		}

		if ($perms < "W" && $this->workflow == "bizproc")
		{
			$arFilter["SHOW_BP_NEW"] = array(
				"GROUPS" => $this->USER["GROUPS"],
				"MODULE_ID" => $this->wfParams["DOCUMENT_TYPE"][0],
				"ENTITY" => $this->wfParams["DOCUMENT_TYPE"][1],
				"PERMISSION" => "read",
				"USER_ID" => $GLOBALS["USER"]->GetID());
		}

		if ($element_id > 0 && $arParams["SHOW_VERSIONS"] == "Y")
		{
			$arFilter["SHOW_HISTORY"] = "Y";
			$arFilter["WF_PARENT_ELEMENT_ID"] = $element_id;
			if ($perms >= "U" && $perms < "W")
				$arFilter["CHECK_BP_PERMISSIONS"] = $arFilter["SHOW_BP_NEW"];
		}
		elseif ($element_id > 0)
		{
			$arFilter["ID"] = $element_id;
		}

		if ($this->arRootSection && ($element_id <= 0))
		{
			if ($arFilter["SECTION_ID"] === null)
				$arFilter["SECTION_ID"] = $this->arRootSection["ID"];
			if (sizeof($arParams['FILTER'])>0)
				$arFilter["INCLUDE_SUBSECTIONS"] = "Y";
			$arFilter["RIGHT_MARGIN"] = $this->arRootSection["RIGHT_MARGIN"];
			$arFilter["LEFT_MARGIN"] = $this->arRootSection["LEFT_MARGIN"];
		}

		if ($arFilter["SECTION_ID"] === null)
			unset($arFilter["SECTION_ID"]);

		if (isset($arParams["FILTER"]) && sizeof($arParams["FILTER"])>0)
		{
			if (isset($arParams["FILTER"]["timestamp_2"]) && strlen($arParams["FILTER"]["timestamp_2"]) > 0 && $arParams["FILTER"]["timestamp_datesel"]!="before")
				$arParams["FILTER"]["timestamp_2"] .= " 23:59:59";

			$arFilter = array_merge($arFilter, $arParams["FILTER"]);
		}
		if (isset($arFilter["NAME"]) && isset($arFilter["DESCRIPTION"]))
		{
			$arFilter[] = array("LOGIC" => "OR", "NAME"=>$arFilter["NAME"], "DESCRIPTION"=>$arFilter["DESCRIPTION"]);
			unset($arFilter["NAME"]);
			unset($arFilter["DESCRIPTION"]);
		}

		$arSelectedFields = $arParams["COLUMNS"];

		if(in_array("LOCKED_USER_NAME", $arSelectedFields))
			$arSelectedFields[] = "WF_LOCKED_BY";
		if(in_array("USER_NAME", $arSelectedFields))
			$arSelectedFields[] = "MODIFIED_BY";
		if(in_array("CREATED_USER_NAME", $arSelectedFields))
			$arSelectedFields[] = "CREATED_BY";
		if(in_array("DETAIL_TEXT", $arSelectedFields))
			$arSelectedFields[] = "DETAIL_TEXT_TYPE";

		$arSelectedFields = array_merge($arSelectedFields, array(
			"ACTIVE",
			"CODE",
			"CREATED_BY",
			"DATE_CREATE",
			"DATE_CREATE_UNIX",
			"DESCRIPTION",
			"DETAIL_PAGE_URL",
			"EXTERNAL_ID",
			"IBLOCK_ID",
			"IBLOCK_CODE",
			"IBLOCK_SECTION_ID",
			"ID",
			"LANG_DIR",
			"XML_ID",
			"LID",
			"LOCKED_USER_NAME",
			"LOCK_STATUS",
			"MODIFIED_BY",
			"NAME",
			"PREVIEW_TEXT",
			"PREVIEW_TEXT_TYPE",
			//"PROPERTY_FORUM_MESSAGE_CNT",
			"PROPERTY_FORUM_TOPIC_ID",
			"PROPERTY_".$this->file_prop,
			"PROPERTY_WEBDAV_INFO",
			"PROPERTY_WEBDAV_SIZE",
			"SITE_ID",
			"TAGS",
			"TIMESTAMP_X",
			"TIMESTAMP_X_UNIX",
			"WF_DATE_LOCK",
			"WF_NEW",
			"WF_PARENT_ELEMENT_ID",
			"WF_STATUS_ID",
			"WF_LOCKED_BY"
		));

		if ($this->workflow == "bizproc")
		{
			$arSelectedFields[] = "BP_PUBLISHED";
			$arSelectedFields[] = "BIZPROC";
		}
		elseif ($this->workflow == 'workflow')
		{
			$arSelectedFields[] = "WF_COMMENTS";
		}
		$arSelectedFields = array_unique($arSelectedFields);
		$arSelectedFields = array_filter($arSelectedFields);
		$this->wfParams["selected_fields"] = $arSelectedFields;
		if ($element_id > 0)
		{
			$arElementResult = array();
			$arFilter["SHOW_HISTORY"] = "Y";
			$db_res = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelectedFields);
			if ($db_res)
			{
				while ($arElement = $db_res->Fetch())
				{
					$arElement["TYPE"] = "E";
					$arElement["FILE"] = CFile::GetFileArray($arElement["PROPERTY_" . $this->file_prop . "_VALUE"]);
					$arElement["FILE"] = (is_array($arElement["FILE"]) ? $arElement["FILE"] : array("FILE_SIZE" => 0, "CONTENT_TYPE" => "text/plain", "MTIME" => 0));
					if ($this->e_rights)
					{
						$arElement['E_RIGHTS'] = $this->GetPermission('ELEMENT', $arElement['ID']);
					}
					$arElementResult[] = $arElement;
				}
			}
			$db_res = new CDBResult;
			$db_res->InitFromArray($arElementResult);
		}
		else
		{
			$arOrder = array($by => $order);

			$arResult = array();
			if (!isset($arFilter["ID"]))
			{
				$arSectionFilter = array (
					"IBLOCK_ID"		=> $arFilter["IBLOCK_ID"],
					"?NAME"			=> $arFilter["NAME"],
					"SECTION_ID"	=> $arFilter["SECTION_ID"],
					">=ID"			=> $arFilter["ID_1"],
					"<=ID"			=> $arFilter["ID_2"],
					">=TIMESTAMP_X"	=> $arFilter["TIMESTAMP_X_1"],
					"<=TIMESTAMP_X"	=> $arFilter["TIMESTAMP_X_2"],
					"MODIFIED_BY"	=> $arFilter["MODIFIED_USER_ID"]? $arFilter["MODIFIED_USER_ID"]: $arFilter["MODIFIED_BY"],
					">=DATE_CREATE"	=> $arFilter["DATE_CREATE_1"],
					"<=DATE_CREATE"	=> $arFilter["DATE_CREATE_2"],
					"CREATED_BY"	=> $arFilter["CREATED_USER_ID"]? $arFilter["CREATED_USER_ID"]: $arFilter["CREATED_BY"],
					"ACTIVE"		=> $arFilter["ACTIVE"],
					"CHECK_PERMISSIONS" => "Y",
				);
				if (isset($arFilter["content"]))
					$arSectionFilter["?NAME"] = $arFilter["content"];
				$arSectionIDs = array();

				$excludeDropped = $excludeOldDropped = $excludeTrash = $excludeSectionMargin = array();
				if(!empty($arParams['NON_OLD_DROPPED_SECTION']))
				{
					if($excludeId = $this->GetMetaID(self::OLD_DROPPED))
					{
						$excludeOldDropped = self::$_metaSectionData[$excludeId];
						$excludeSectionMargin[] = array(
							$excludeOldDropped['LEFT_MARGIN'],
							$excludeOldDropped['RIGHT_MARGIN'],
						);
					}

				}
				if(!empty($arParams['NON_DROPPED_SECTION']))
				{
					if($excludeId = $this->GetMetaID(self::DROPPED, false))
					{
						$excludeDropped = self::$_metaSectionData[$excludeId];
						$excludeSectionMargin[] = array(
							$excludeDropped['LEFT_MARGIN'],
							$excludeDropped['RIGHT_MARGIN'],
						);
					}

				}
				if(!empty($arParams['NON_TRASH_SECTION']))
				{
					if($excludeId = $this->GetMetaID(self::TRASH))
					{
						$excludeTrash = self::$_metaSectionData[$excludeId];
						$excludeSectionMargin[] = array(
							$excludeTrash['LEFT_MARGIN'],
							$excludeTrash['RIGHT_MARGIN'],
						);
					}
				}

				$obSection = new CIBlockSection;
				$rsSection = $obSection->GetList($arOrder, $arSectionFilter, false, array_merge($arSelectedFields, self::getUFNamesForSectionLink(), array('LEFT_MARGIN', 'RIGHT_MARGIN')));
				while($arSection = $rsSection->Fetch())
				{
					if($excludeOldDropped && $arSection['LEFT_MARGIN'] >= $excludeOldDropped['LEFT_MARGIN'] && $arSection['RIGHT_MARGIN'] <= $excludeOldDropped['RIGHT_MARGIN'])
					{
						continue;
					}
					if($excludeTrash && $arSection['LEFT_MARGIN'] >= $excludeTrash['LEFT_MARGIN'] && $arSection['RIGHT_MARGIN'] <= $excludeTrash['RIGHT_MARGIN'])
					{
						continue;
					}
					if($excludeDropped && $arSection['LEFT_MARGIN'] >= $excludeDropped['LEFT_MARGIN'] && $arSection['RIGHT_MARGIN'] <= $excludeDropped['RIGHT_MARGIN'])
					{
						continue;
					}
					$arSection["TYPE"] = "S";
					$arResult[] = $arSection;
					if ($this->e_rights)
					{
						$arSectionIDs[] = $arSection['ID'];
					}
				}
				if ($this->e_rights)
				{
					$arSectionRights = $this->GetPermissions('SECTION', $arSectionIDs);
					$arSectionRules = $this->GetPermissionRules('SECTION', $arSectionIDs);
					foreach($arResult as $id => &$arSection)
					{
						if ( // if "shared folder" access is restricted
							($arSection['XML_ID'] == 'SHARED_FOLDER') &&
							(! isset($arSectionRights[$arSection['ID']]['element_read']))
						)
						{
							unset($arResult[$id]);
							continue;
						}

						if (isset($arSectionRights[$arSection['ID']]))
							$arSection['E_RIGHTS'] = $arSectionRights[$arSection['ID']];
						if (isset($arSectionRules[$arSection['ID']]) && $this->IsShared($arSectionRules[$arSection['ID']]))
						{
							$arSection['SHARED'] = true;
						}
					}
					unset($arSection);
				}
			}

			$arElementFilter = array (
				"IBLOCK_ID"			=> $arFilter["IBLOCK_ID"],
				"?NAME"				=> $arFilter["NAME"],
				"SECTION_ID"		=> $arFilter["SECTION_ID"],
				"ID"				=> $arFilter["ID"],
				">=ID"				=> $arFilter["ID_1"],
				"<=ID"				=> $arFilter["ID_2"],
				">=TIMESTAMP_X"		=> $arFilter["TIMESTAMP_X_1"],
				"<=TIMESTAMP_X"		=> $arFilter["TIMESTAMP_X_2"],
				"EXTERNAL_ID"		=> $arFilter["EXTERNAL_ID"],
				"MODIFIED_USER_ID"	=> $arFilter["MODIFIED_USER_ID"],
				"MODIFIED_BY"		=> $arFilter["MODIFIED_BY"],
				">=DATE_CREATE"		=> $arFilter["DATE_CREATE_1"],
				"<=DATE_CREATE"		=> $arFilter["DATE_CREATE_2"],
				"CREATED_BY"		=> $arFilter["CREATED_BY"],
				"CREATED_USER_ID"	=> $arFilter["CREATED_USER_ID"],
				">=DATE_ACTIVE_FROM"=> $arFilter["DATE_ACTIVE_FROM_1"],
				"<=DATE_ACTIVE_FROM"=> $arFilter["DATE_ACTIVE_FROM_2"],
				">=DATE_ACTIVE_TO"	=> $arFilter["DATE_ACTIVE_TO_1"],
				"<=DATE_ACTIVE_TO"	=> $arFilter["DATE_ACTIVE_TO_2"],
				"ACTIVE"			=> $arFilter["ACTIVE"],
				"?SEARCHABLE_CONTENT"=> $arFilter["DESCRIPTION"],
				"?TAGS"				=> $arFilter["?TAGS"],
				"WF_STATUS"			=> $arFilter["WF_STATUS"],
				"WF_LOCK_STATUS"	=> $arFilter["WF_LOCK_STATUS"],
				"CHECK_PERMISSIONS" => "Y",
				"MIN_PERMISSION" => "R",

				"SHOW_NEW"			=>	($arFilter["SHOW_NEW"] !== "N"? "Y": "N"),
				"SHOW_HISTORY"		=>	($arFilter["SHOW_HISTORY"] !== "Y"? "N": "Y"),
				"SHOW_BP_NEW"		=>	$arFilter["SHOW_BP_NEW"]
			);

			if ($arElementFilter["SHOW_HISTORY"] == "Y") $arElementFilter["SHOW_NEW"] = "N";

			$fltSizeFrom = isset($arParams["FILTER"]["FILE_SIZE_1"]) ? $arParams["FILTER"]["FILE_SIZE_1"] : null;
			$fltSizeTo = isset($arParams["FILTER"]["FILE_SIZE_2"]) ? $arParams["FILTER"]["FILE_SIZE_2"] : null;
			if (($fltSizeFrom !== null || $fltSizeTo !== null) && isset($arParams["FILTER"]["FILE_SIZE_multiply"]) && in_array($arParams["FILTER"]["FILE_SIZE_multiply"], array("b","kb","mb","gb")))
			{
				$multiply = 1;
				if ($arParams["FILTER"]["FILE_SIZE_multiply"] === "kb")
					$multiply = 1024;
				elseif ($arParams["FILTER"]["FILE_SIZE_multiply"] === "mb")
					$multiply = 1024*1024;
				elseif ($arParams["FILTER"]["FILE_SIZE_multiply"] === "gb")
					$multiply = 1024*1024*1024;

				if ($fltSizeFrom !== null)
				{
					$fltSizeFrom = $fltSizeFrom * $multiply;
					$arElementFilter[">=PROPERTY_WEBDAV_SIZE"] = $fltSizeFrom;
				}
				if ($fltSizeTo !== null)
				{
					$fltSizeTo = $fltSizeTo * $multiply;
					$arElementFilter["<=PROPERTY_WEBDAV_SIZE"] = $fltSizeTo;
				}
			}


			if (isset($arFilter["timestamp_1"]) && isset($arFilter["timestamp_2"]))
			{
				$arElementFilter[] = array("LOGIC" => "OR",
					array("LOGIC" => "AND",
						">=DATE_CREATE"=>$arFilter["timestamp_1"],
						"<=DATE_CREATE"=>$arFilter["timestamp_2"]),
					array("LOGIC" => "AND",
						">=TIMESTAMP_X"=>$arFilter["timestamp_1"],
						"<=TIMESTAMP_X"=>$arFilter["timestamp_2"]),
				);
				unset($arFilter["timestamp_1"]);
				unset($arFilter["timestamp_2"]);
			}
			elseif (isset($arFilter["timestamp_1"]))
			{
				$arElementFilter[] = array("LOGIC" => "OR", ">=DATE_CREATE"=>$arFilter["timestamp_1"], ">=TIMESTAMP_X"=>$arFilter["timestamp_1"]);
				unset($arFilter["timestamp_2"]);
			}
			elseif (isset($arFilter["timestamp_2"]))
			{
				$arElementFilter[] = array("LOGIC" => "OR", "<=DATE_CREATE"=>$arFilter["timestamp_2"], "<=TIMESTAMP_X"=>$arFilter["timestamp_2"]);
				unset($arFilter["timestamp_2"]);
			}

			if (isset($arFilter["user"]))
			{
				$arElementFilter[] = array("LOGIC" => "OR", "MODIFIED_BY"=>$arFilter["user"], "CREATED_BY"=>$arFilter["user"]);
				unset($arFilter["user"]);
			}

			if(strlen($arFilter["SECTION_ID"])<= 0)
				unset($arElementFilter["SECTION_ID"]);

			if(!is_array($arSelectedFields))
			{
				$arSelectedFields = Array("*");
			}
			elseif(!in_array("PROPERTY_WEBDAV_SIZE", $arSelectedFields))
			{
				$arSelectedFields[] = "PROPERTY_WEBDAV_SIZE";
			}

			if(isset($arFilter["INCLUDE_SUBSECTIONS"]))
				$arElementFilter["INCLUDE_SUBSECTIONS"] = $arFilter["INCLUDE_SUBSECTIONS"];

			if(isset($arFilter["CHECK_BP_PERMISSIONS"]))
				$arElementFilter["CHECK_BP_PERMISSIONS"] = $arFilter["CHECK_BP_PERMISSIONS"];

			if(!empty($excludeSectionMargin))
			{
				$arElementFilter['!SUBSECTION'] = $excludeSectionMargin;
			}

			$arNavParams = isset($arParams["NAV_PARAMS"]) ? $arParams["NAV_PARAMS"] : false;
			$arElementIDs = array();
			$arElementResult = array();
			$obElement = new CIBlockElement;
			$rsElement = $obElement->GetList($arOrder, $arElementFilter, false, $arNavParams, $arSelectedFields);
			while($arElement = $rsElement->Fetch())
			{
				$arElement["TYPE"] = "E";
				$arElement["FILE"]["FILE_SIZE"] = $this->GetFileSize($arElement); //$arElement['ID']

				$arElementResult[] = $arElement;
				if ($this->e_rights)
				{
					$arElementIDs[] = $arElement['ID'];
				}
			}
			if ($this->e_rights)
			{
				$arElementRights = $this->GetPermissions('ELEMENT', $arElementIDs);
				$arElementRules = $this->GetPermissionRules('ELEMENT', $arElementIDs);
				foreach($arElementResult as $id=>&$arElement)
				{
					if (isset($arElementRights[$arElement['ID']]))
						$arElement['E_RIGHTS'] = $arElementRights[$arElement['ID']];
					if (isset($arElementRules[$arElement['ID']]) && $this->IsShared($arElementRules[$arElement['ID']]))
						$arElement['SHARED'] = true;
				}
				unset($arElement);
			}

			$arResult = array_merge($arResult, $arElementResult);
			$db_res = new CDBResult;
			$db_res->InitFromArray($arResult);
		}
		return $db_res;
	}

	public function setThroughVersion($version)
	{
		if($this->arParams['element_array']['ID'])
		{
			CIBlockElement::SetPropertyValuesEx($this->arParams['element_array']['ID'], $this->IBLOCK_ID, (int)$version, static::PROPERTY_VERSION);
		}
	}

	public function GetFileSize($element)
	{
		static $PROP_SIZE = "PROPERTY_WEBDAV_SIZE";
		$elementID = 0;
		$arElement = array();
		if (is_array($element) && isset($element['ID']))
		{
			$arElement =& $element;
			$elementID = $arElement['ID'];
		}
		elseif( ($elementID = intval($element)) > 0)
		{
		}
		else
		{
			return 0;
		}

		$result = 0;

		if ((!is_array($arElement) || empty($arElement)) && $elementID > 0)
		{
			$arSelectedFields = array(
				"ID",
				"NAME",
				"PROPERTY_".$this->file_prop,
				$PROP_SIZE
			);

			$arElementFilter = array(
				'ID' => $elementID,
				'IBLOCK_ID' => $this->IBLOCK_ID,
				'SHOW_HISTORY' => 'Y'
			);
			$rsElement = CIBlockElement::GetList(array(), $arElementFilter, false, false, $arSelectedFields);
			$arElement = $rsElement->Fetch();
		}

		if (is_array($arElement))
		{
			$result = $arElement[$PROP_SIZE.'_VALUE'];
			if (
				empty($result)
				&& isset($arElement['PROPERTY_'.$this->file_prop.'_VALUE'])
				&& intval($arElement['PROPERTY_'.$this->file_prop.'_VALUE'])>0
			)
			{
				$arFile = CFile::GetFileArray(intval($arElement['PROPERTY_'.$this->file_prop.'_VALUE']));
				$result = $arFile['FILE_SIZE'];
				CIBlockElement::SetPropertyValues($elementID, $this->IBLOCK_ID, $result, 'WEBDAV_SIZE');
			}
		}

		return $result;
	}

	function _get_path($section_id, $convert = true)
	{
		$res = $this->GetNavChain(array("section_id" => $section_id), $convert);
		return "/".implode("/", $res);
	}

	function _get_section_info_arr(&$arr)
	{
		$arr["SHOW"] = array(
			"EDIT" => ($this->permission > "U" && !$this->check_creator ? "Y" : "N"),
			"DELETE" => ($this->permission > "U" && !$this->check_creator ? "Y" : "N"),
			"PERMISSIONS" => ($this->permission > "W"),
			"UNDELETE" => "N",
			"RIGHTS" => ($this->e_rights ? "Y" : "N")
		);

		$info = array();
		if (!isset($arr["~NAME"])) $arr["~NAME"] = $arr["NAME"];
		$arr["PATH"] = str_replace("//", "/", CWebDavIblock::_get_path($arr["IBLOCK_SECTION_ID"], false)."/".$arr["NAME"]);
		if ($_SERVER['REQUEST_METHOD'] == 'PROPFIND')
		{
			$info["path"]  = $arr["PATH"];
			if (SITE_CHARSET != "UTF-8")
				$info["path"] = $GLOBALS["APPLICATION"]->ConvertCharset($info["path"], SITE_CHARSET, "UTF-8");
			$info["props"] = array();

			$info["props"][] = array('ns'=>'DAV:', 'name'=>"resourcetype", 'val'=>"collection");
			$info["props"][] = array('ns'=>'DAV:', 'name'=>"getcontenttype", 'val'=>"httpd/unix-directory");
			$info["props"][] = array('ns'=>'DAV:', 'name'=>"creationdate", 'val'=>
				(isset($arr["DATE_CREATE_UNIX"]) ? $arr["DATE_CREATE_UNIX"] : MakeTimeStamp($arr["DATE_CREATE"])));
			$info["props"][] = array('ns'=>'DAV:', 'name'=>"getlastmodified", 'val'=>
				(isset($arr["TIMESTAMP_X_UNIX"]) ? $arr["TIMESTAMP_X_UNIX"] : MakeTimeStamp($arr["TIMESTAMP_X"])));
			$info["props"][] = array('ns'=>'DAV:', 'name'=>"iscollection", 1);
			$info["props"][] = $this->_get_lock_prop();
		}

		if ($this->_parse_webdav_info($arr) && is_array($arr["PROPS"]))
		{
			foreach ($arr["PROPS"] as $ns_name => $ns_props)
			{
				foreach ($ns_props as $prop_name => $prop_val)
				{
					if(is_scalar($prop_val))
					{
						if ($ns_name == "BX:" && $prop_name == "UNDELETE")
						{
							$arr["SHOW"]["EDIT"] = "N";
							if ($this->permission > "W")
							{
								$arr["SHOW"]["DELETE"] = "Y";
								$arr["SHOW"]["UNDELETE"] = "Y";
							}
							else
							{
								$arr["SHOW"]["DELETE"] = "N";
							}
							$arr["UNDELETE"] = $prop_val;
						}
						$info["props"][] = CWebDavBase::_mkprop($ns_name, $prop_name, $prop_val);
					}
				}
			}
		}
		if ($this->e_rights)
		{
			$bSuperUser = $GLOBALS['USER']->CanDoOperation('webdav_change_settings');
			$arShow = array(
				'PERMISSIONS' => 'section_edit',
				'EDIT' => 'section_edit',
				'DELETE' => 'section_edit',
				'UNDELETE' => 'section_delete',
				"RIGHTS" => "element_rights_edit"
			);

			foreach($arShow as $action => $right)
			{
				if (!isset($arr['E_RIGHTS'][$right]) && !$bSuperUser)
					$arr['SHOW'][$action] = 'N';
			}
			$arr['SHOW']['SHARED'] = $arr['SHARED'];
		}
		return $info;
	}

	function IsShared($arRights)
	{
		//static $arTasks = array();
		//static $arPublicGroups = array();
		if (!$this->e_rights)
			return false;

		//if (empty($arTasks))
			//$arTasks = $this->GetTasks();

		//if (empty($arPublicGroups))
		//{
			//$arPublicGroups = array('AU', 'G2');
			//if (($dbEmployees = CGroup::GetList($by, $order, array("STRING_ID" => "EMPLOYEES%"))) && ($arEmployees = $dbEmployees->Fetch()))
				//$arPublicGroups[] = 'G'.$arEmployees['ID'];
		//}

		//$arRights = $this->GetERights(($arr['TYPE'] === 'S' ? 'SECTION' : 'ELEMENT'), $arr["ID"]);
		global $USER;
		$self = !empty($this->attributes['user_id']) && $this->attributes['user_id'] == $USER->getId();
		$selfGroupCode = 'U' . $USER->getId();
		foreach ($arRights as $rID => $right)
		{
			if (
				//in_array($right['GROUP_CODE'], $arPublicGroups) &&
				//(in_array($right['TASK_ID'], array($arTasks['R'], $arTasks['W'], $arTasks['U'], $arTasks['X']))) &&
				$right['IS_INHERITED'] == 'N' && (!$self || $self && $selfGroupCode != $right['GROUP_CODE']))
			{
				return true;
			}
		}
		return false;
	}

	function BPParameterRequired() // for doc
	{
		if ($this->workflow != 'bizproc')
			return false;
		$arDocumentStates = CBPDocument::GetDocumentStates($this->wfParams["DOCUMENT_TYPE"], null);

		$result = false;
		foreach ($arDocumentStates as $key => $arDocumentState)
		{
			if (is_array($arDocumentState["TEMPLATE_PARAMETERS"]))
			{
				foreach ($arDocumentState["TEMPLATE_PARAMETERS"] as $tplID => $tplParam)
				{
					if (($tplParam['Required'] == true) && (empty($tplParam['Default'])))
					{
						$result = true;
						break;
					}
				}

				if ($result)
					break;
			}
		}
		return $result;
	}

	function GetMetaID($sMetaName, $createIfNotExist = true)
	{
		static $DataType = 'MetaIDS';
		$arMetaIDs = $this->_dataCache($DataType);
		if (!$arMetaIDs || empty($arMetaIDs[$sMetaName]))
		{
			if (!isset($this->meta_names[$sMetaName]))
			{
				return null;
			}
			$rootSectionId = empty($this->arRootSection['ID'])? 0 : $this->arRootSection['ID'];

			//non create meta folder in depth! hack
			if($rootSectionId && !empty($this->arRootSection['DEPTH_LEVEL']) && $this->arRootSection['DEPTH_LEVEL'] > 1)
			{
				return null;
			}

			$rootIblockId = $this->IBLOCK_ID;

			$sectionId = $this->findMetaSection(
				$this->meta_names[$sMetaName]['name'],
				$rootIblockId,
				$rootSectionId
			);
			if(!$sectionId && $createIfNotExist && !empty($this->meta_names[$sMetaName]['auto_create']))
			{
				$sectionId = $this->createMetaSection(
					$this->meta_names[$sMetaName]['name'],
					$rootIblockId,
					$rootSectionId
				);

				if (intval($sectionId) > 0)
				{
					$sectionId = intval($sectionId);
				}
				else
				{
					return null;
				}
			}

			if (!is_array($arMetaIDs))
			{
				$arMetaIDs = array();
			}
			$arMetaIDs[$sMetaName] = $sectionId;
			$this->_dataCache($DataType, $arMetaIDs);
		}

		return $arMetaIDs[$sMetaName];
	}

	final public static function findMetaSection($metaName, $iblockId, $parentSectionId = 0)
	{
		$filter = array(
			"IBLOCK_ID" => $iblockId,
			"SECTION_ID" => $parentSectionId,
			"NAME" => $metaName,
			"CHECK_PERMISSIONS" => "N"
		);
		$query = CIBlockSection::GetList(array(), $filter, false, array("ID", "LEFT_MARGIN", "RIGHT_MARGIN"));
		if ($query && $metaSection = $query->GetNext())
		{
			self::$_metaSectionData[$metaSection['ID']] = $metaSection;
			return $metaSection['ID'];
		}
		return false;
	}

	final public static function createMetaSection($metaName, $iblockId, $parentSectionId = 0, array $additionalData = array())
	{
		$section = new CIBlockSection();
		return $section->Add(array_merge(array(
			"NAME" => $metaName,
			"IBLOCK_ID" => $iblockId,
			"IBLOCK_SECTION_ID" => $parentSectionId,
			"ACTIVE" => "Y",
		), $additionalData));
	}

	function get_file(&$res)
	{
		$res["FILE"] = CFile::GetFileArray($res["PROPERTY_" . $this->file_prop . "_VALUE"]);
		$res["FILE"] = (is_array($res["FILE"]) ? $res["FILE"] : array("FILE_SIZE" => 0, "CONTENT_TYPE" => "text/plain", "MTIME" => 0));
		if (isset($res["FILE"]["CONTENT_TYPE"]) && ($res["FILE"]["CONTENT_TYPE"] == "application/zip"))
		{
			$ext = GetFileExtension($res["FILE"]["FILE_NAME"]);
			switch ($ext)  // http://en.wikipedia.org/wiki/Microsoft_Office_2007_filename_extensions
			{
				case "docx":
				case "docm":
				case "dotx":
				case "dotm":
					$res["FILE"]["CONTENT_TYPE"] = "application/msword";
					break;
				case "xlsx":
				case "xlsm":
				case "xltx":
				case "xltm":
				case "xlsb":
				case "xlam":
				case "xll":
					$res["FILE"]["CONTENT_TYPE"] = "application/vnd.ms-excel";
					break;
				case "pptx":
				case "pptm":
				case "potx":
				case "potm":
				case "ppam":
				case "ppsx":
				case "ppsm":
					$res["FILE"]["CONTENT_TYPE"] = "application/vnd.ms-powerpoint";
					break;
			}
		}
	}

	static function GetTasks()
	{
		static $arTasks = null;
		if ($arTasks == null)
		{
			$rs = CTask::GetList(
				array("LETTER"=>"asc"),
				array(
					"MODULE_ID" => "iblock",
					"BINDING" => "iblock",
					"SYS" => "Y",
				)
			);
			$arTasks = array();
			while($ar = $rs->Fetch())
				$arTasks[$ar["LETTER"]] = $ar["ID"];
		}

		return $arTasks;
	}


	function MetaSectionHide(&$sectionData, $excludeByDiskCondition = false)
	{
		static $cacheIdMetaSection = array();

		if(empty($this->arRootSection))
		{
			//common lib
			$userLibNeedCreateDropped = false;
		}
		elseif(!empty($this->arRootSection['SOCNET_GROUP_ID']))
		{
			//group lib
			$userLibNeedCreateDropped = false;
		}
		else
		{
			//user lib
			$userLibNeedCreateDropped = true;
		}

		$rootId = isset($this->arRootSection['ID'])?  $this->arRootSection['ID'] : 0;
		$key = $this->IBLOCK_ID . '_' . $rootId;

		if (!isset($cacheIdMetaSection[$key]))
		{
			$cacheIdMetaSection[$key] = array();
			foreach ($this->meta_names as $metaID => $arMeta)
			{
				if ($arMeta['show_in_list'] === false || $excludeByDiskCondition && $arMeta['show_in_list_disk'] === false)
				{
					// GetMetaID is already cached
					//We don't auto create DROPPED folder in common (=shared), group storages.
					$sectionId = intval($this->GetMetaID($metaID, !($userLibNeedCreateDropped && $metaID == self::DROPPED)));
					if($sectionId)
					{
						$cacheIdMetaSection[$key][$sectionId] = true;
					}
				}
			}
		}
		return isset($cacheIdMetaSection[$key][intval($sectionData['ID'])]);
	}

	function OnBizprocPublishDocument($ID)
	{
		$ID = (int) $ID;
		if ($ID > 0)
		{
			$this->_onEvent('Update', $ID, 'FILE', array('UPDATE_TYPE' => array('PUBLISH')));
		}
	}

	function _get_file_info_arr(&$res, $arParams = array())
	{
		global $USER;
		static $arFiles = array();
		static $arBPTemplateStates = array();
		static $arBPParamRequired = array();

		$trashID = $this->GetMetaID('TRASH');

		if ($this->workflow == 'bizproc')
		{
			if (!isset($arBPParamRequired[$this->IBLOCK_ID]))
			{
				$arBPParamRequired[$this->IBLOCK_ID] = $this->IsBpParamRequired();
			}
		}

		$info = array();
		if (!in_array($res["ID"], $arFiles))
		{
			$res["SHOW"] = array();
			$res["PERMISSION"] = $this->permission;

			$res['SHOW']['SHARED'] = $res['SHARED'];

			$res["~NAME"] = $res["NAME"];

			$res['USER_FIELDS'] = $this->GetUfFields($res['ID']);

			if ($this->permission >= "U") // 'X' if e_rights
			{
				$res["SHOW"]["RIGHTS"] =
					(($this->e_rights &&
					(isset($res['E_RIGHTS']['element_rights_edit']) || $GLOBALS['USER']->CanDoOperation('webdav_change_settings')))
					? "Y" : "N");
				if ($this->workflow == 'workflow')
				{
					$original = $res;
					$LAST_ID = CIBlockElement::WF_GetLast($res['ID']);
					if($LAST_ID != $res['ID'])
					{
						$db_res = CIBlockElement::GetList(
							array(),
							array("ID" => $LAST_ID, "SHOW_HISTORY" => "Y"),
							false,
							array("nTopCount" => 1),
							$this->wfParams["selected_fields"]);
						$res = $db_res->GetNext();
						$res["FILE"]["FILE_SIZE"] = $this->GetFileSize($res);
						if ($this->e_rights)
						{
							$res['E_RIGHTS'] = $this->GetPermission('ELEMENT', $res['ID']);
						}
					}

					$res["ORIGINAL"] = $original;
					$res["PERMISSION"] = $original["PERMISSION"];
					$res["REAL_ID"] = $res["ID"];
					$res["ID"] = $original["ID"];
					$res["PROPERTY_WEBDAV_INFO_VALUE"] = $original["PROPERTY_WEBDAV_INFO_VALUE"];
					$res["PROPERTY_WEBDAV_INFO_VALUE_ID"] = $original["PROPERTY_WEBDAV_INFO_VALUE_ID"];
					$res["TAGS"] = $original["TAGS"];
					$res["~TAGS"] = $original["~TAGS"];
					$res["PREVIEW_TEXT"] = $original["PREVIEW_TEXT"];
					$res["~PREVIEW_TEXT"] = $original["~PREVIEW_TEXT"];
					$res["NAME"] = $original["NAME"];
					$res["~NAME"] = $original["~NAME"];
					$res["LOCK_STATUS"] = $original["LOCK_STATUS"];
					$res["LOCKED_USER_NAME"] = $original["LOCKED_USER_NAME"];
					$res["WF_LOCKED_BY"] = $original["WF_LOCKED_BY"];
					$res["WF_DATE_LOCK"] = $original["WF_DATE_LOCK"];
					$res["LAST_ID"] = $LAST_ID;
					$res["TYPE"] = "E";
					$res["SHOW"]["COPY"] = "Y";

					$res["STATUS_ID"] = CIBlockElement::WF_GetCurrentStatus($LAST_ID, $res["STATUS_TITLE"]);
					$res["STATUS_PERMISSION"] = CIBlockElement::WF_GetStatusPermission($res["STATUS_ID"]);

					if ($res["STATUS_PERMISSION"] >= 2 || ($this->e_rights ? isset($res['E_RIGHTS']['element_edit_any_wf_status']) : ($this->permission >= "W"))) // permissions enouph to edit, draft is shown if any
					{
						$res["SHOW"]["UNLOCK"] = ($res['LOCK_STATUS'] == "yellow" || ($res['LOCK_STATUS'] == "red" && CWorkflow::IsAdmin()) ? "Y" : "N");
						$res["SHOW"]["LOCK"] = (($res['LOCK_STATUS'] == "green") ? "Y" : "N");
						$res["PERMISSION"] = ($res['LOCK_STATUS'] == "red" && !CWorkflow::IsAdmin() ? "R" : $res["PERMISSION"]);
						// Edit History Delete
						if ($res['ORIGINAL']['WF_NEW'] == "Y" || $res["WF_STATUS_ID"] <= 1) // Unpublished || Published and has no changes
						{
							$res["SHOW"]["HISTORY"] = $res["SHOW"]["DELETE"] = $res["SHOW"]["EDIT"] =
								($this->check_creator && $res["CREATED_BY"] != $GLOBALS["USER"]->GetId() ? "N" : "Y");
						}
						elseif ($res["WF_STATUS_ID"] > 1) // Published element has unpublished changes
						{
							$res["SHOW"]["HISTORY"] = $res["SHOW"]["EDIT"] =
								($this->check_creator && $res["CREATED_BY"] != $GLOBALS["USER"]->GetId() ? "N" : "Y");
							$res["SHOW"]["DELETE"] = ($res["SHOW"]["EDIT"] == "Y" && $res["PERMISSION"] > "U" ? "Y" : "N");
						}

						if ($res["LOCK_STATUS"] == "red")
						{
							$res["SHOW"]["UNLOCK"] = (CWorkflow::IsAdmin() ? "Y" : "N");
							$res["SHOW"]["EDIT"] = "N";
							$res["SHOW"]["DELETE"] = "N";
						}
						elseif ($this->check_creator && $res["CREATED_BY"] != $GLOBALS["USER"]->GetId())
						{
							$res["SHOW"]["UNLOCK"] = "N";
							$res["SHOW"]["LOCK"] = "N";
							$res["SHOW"]["DELETE"] = "N";
							$res["SHOW"]["EDIT"] = "N";
						}
					}

					if ($res["STATUS_PERMISSION"] >= 2 || ($this->e_rights ? isset($res['E_RIGHTS']['element_delete']) : ($this->permission >= "W")))
					{
						$res["PERMISSION"] = ($res['LOCK_STATUS'] == "red" && !CWorkflow::IsAdmin() ? "R" : $res["PERMISSION"]);
						if ($res['ORIGINAL']['WF_NEW'] == "Y" || $res["WF_STATUS_ID"] <= 1) // Unpublished || Published and has no changes
						{
							$res["SHOW"]["DELETE"] = ($this->check_creator && $res["CREATED_BY"] != $GLOBALS["USER"]->GetId() ? "N" : "Y");
						}
						elseif ($res["WF_STATUS_ID"] > 1) // Published element has unpublished changes
						{
							$res["SHOW"]["DELETE"] = ($this->check_creator && ($res["CREATED_BY"] != $GLOBALS["USER"]->GetId()) && ($res["PERMISSION"] > "U") ? "N" : "Y");
						}
					}
				} // $this->workflow == "workflow"
				elseif($this->workflow == "bizproc")
				{
					if ($this->e_rights)
						$res["PERMISSION"] = $res["E_RIGHTS"];
					if($res['LOCK_STATUS'] != "red"):
						$res['LOCK_STATUS_BP'] = (call_user_func(array(
								$this->wfParams["DOCUMENT_TYPE"][1],
								"IsDocumentLocked"
							), $res["ID"] | $res["item_id"], "") ? "red" : "green");
						$res['LOCK_STATUS'] = ($res['LOCK_STATUS_BP'] == "red" ? "red" : $res['LOCK_STATUS']);
					endif;
					$res["PERMISSION"] = ($res['LOCK_STATUS'] == "red" ? "R" : $res["PERMISSION"]);
					$res["USER_GROUPS"] = $this->USER["GROUPS"];
					if ($res["CREATED_BY"] == $GLOBALS["USER"]->GetID())
						$res["USER_GROUPS"][] = "Author";
					$res["DOCUMENT_ID"] = $this->wfParams["DOCUMENT_TYPE"];
					$res["DOCUMENT_ID"][2] = $res["ID"] | $res["item_id"];
					$res["~arDocumentStates"] = CBPDocument::GetDocumentStates(
						$this->wfParams["DOCUMENT_TYPE"],
						$res["DOCUMENT_ID"]);
					$res["arDocumentStates"] = array();
					foreach ($res["~arDocumentStates"] as $key => $arDocumentState)
					{
						$res["~arDocumentStates"][$key]["ViewWorkflow"] = CBPDocument::CanUserOperateDocument(
							CBPCanUserOperateOperation::ViewWorkflow,
							$GLOBALS["USER"]->GetID(),
							$res["DOCUMENT_ID"],
							array(
								"DocumentType" => $this->wfParams["DOCUMENT_TYPE"],
								"AllUserGroups" => $res["USER_GROUPS"],
								"DocumentStates" => $res["~arDocumentStates"],
								"WorkflowId" => $key));
						if (strlen($arDocumentState["ID"]) > 0 && $res["~arDocumentStates"][$key]["ViewWorkflow"])
						{
							$res["arDocumentStates"][$key] = $arDocumentState;
							$res["PERMISSION"] = "U";
						}
					}

					if ($res['LOCK_STATUS'] != "red")
					{
						if (CBPDocument::CanUserOperateDocument(
								CBPCanUserOperateOperation::WriteDocument,
								$GLOBALS["USER"]->GetID(),
								$res["DOCUMENT_ID"],
								array(
									"DocumentType" => $this->wfParams["DOCUMENT_TYPE"],
									"IBlockId" => $this->IBLOCK_ID,
									"UserGroups" => $this->USER["GROUPS"],
									"AllUserGroups" => $res["USER_GROUPS"],
									"DocumentStates" => $res["~arDocumentStates"])))
						{
							$res["PERMISSION"] = "W";
						}
						elseif (!CBPDocument::CanUserOperateDocument(
								CBPCanUserOperateOperation::ReadDocument,
								$GLOBALS["USER"]->GetID(),
								$res["DOCUMENT_ID"],
								array(
									"DocumentType" => $this->wfParams["DOCUMENT_TYPE"],
									"IBlockId" => $this->IBLOCK_ID,
									"UserGroups" => $this->USER["GROUPS"],
									"AllUserGroups" => $res["USER_GROUPS"],
									"DocumentStates" => $res["~arDocumentStates"])))
						{
							$res["PERMISSION"] = "D";
						}
					}

					if ($this->CheckRight($res["PERMISSION"], 'element_read') > "D")
					{
						if ($arParams["get_clones"] == "Y" && intVal($res["WF_PARENT_ELEMENT_ID"]) <= 0)
						{
							$arFilter = array(
								"WF_PARENT_ELEMENT_ID" => $res["ID"],
								"SHOW_HISTORY" => "Y",
								);
							$db_rs = CIBlockElement::GetList(
								array("ID" => "ASC"),
								$arFilter);
							if ($db_rs && $rs = $db_rs->Fetch())
							{
								$res["CHILDREN"] = array();
								do
								{
									if ($rs["IBLOCK_SECTION_ID"] == $trashID)
										continue;

									$rs["SHOW"] = array();
									$rs["PERMISSION"] = "R";
									$rs["DOCUMENT_ID"] = $res["DOCUMENT_ID"];
									$rs["DOCUMENT_ID"][2] = $rs["ID"];
									$rs["~arDocumentStates"] = CBPDocument::GetDocumentStates(
										$this->wfParams["DOCUMENT_TYPE"],
										$rs["DOCUMENT_ID"]);

									$USER_GROUPS = $this->USER["GROUPS"];
									if ($rs["CREATED_BY"] == $GLOBALS["USER"]->GetID())
										$USER_GROUPS[] = "Author";

									if (!CBPDocument::CanUserOperateDocument(
											CBPCanUserOperateOperation::ReadDocument,
											$GLOBALS["USER"]->GetID(),
											$rs["DOCUMENT_ID"],
											array(
												"DocumentType" => $this->wfParams["DOCUMENT_TYPE"],
												"IBlockId" => $this->IBLOCK_ID,
												"UserGroups" => $this->USER["GROUPS"],
												"AllUserGroups" => $USER_GROUPS,
												"DocumentStates" => $rs["~arDocumentStates"]
											)
										)
									)
										continue;

									if (CBPDocument::CanUserOperateDocument(
										CBPCanUserOperateOperation::WriteDocument,
										$GLOBALS["USER"]->GetID(),
										$rs["DOCUMENT_ID"],
										array(
											"DocumentType" => $this->wfParams["DOCUMENT_TYPE"],
											"IBlockId" => $this->IBLOCK_ID,
											"UserGroups" => $this->USER["GROUPS"],
											"AllUserGroups" => $USER_GROUPS,
											"DocumentStates" => $rs["~arDocumentStates"])))
									{
										$rs["PERMISSION"] = "W";
									}

									$rs["arDocumentStates"] = array();
									foreach ($rs["~arDocumentStates"] as $key => $arDocumentState)
									{

										if (CBPDocument::CanUserOperateDocument(
											CBPCanUserOperateOperation::ViewWorkflow,
											$GLOBALS["USER"]->GetID(),
											$rs["DOCUMENT_ID"],
											array(
												"DocumentType" => $this->wfParams["DOCUMENT_TYPE"],
												"IBlockId" => $this->IBLOCK_ID,
												"UserGroups" => $this->USER["GROUPS"],
												"AllUserGroups" => $USER_GROUPS,
												"DocumentStates" => $rs["~arDocumentStates"],
												"WorkflowId" => $key)))
										{
											$rs["arDocumentStates"][$key] = $arDocumentState;
											$rs["PERMISSION"] = (empty($rs["PERMISSION"]) ? "U" : $rs["PERMISSION"]);
										}
									}
									if ($rs["PERMISSION"] >= "U")
									{
										if (CBPDocument::CanUserOperateDocument(
											CBPCanUserOperateOperation::StartWorkflow,
											$USER->GetID(),
											$rs["DOCUMENT_ID"],
											array(
												"IBlockId" => $this->IBLOCK_ID,
												"UserGroups" => $this->USER["GROUPS"],
												"AllUserGroups" => $USER_GROUPS,
												"DocumentStates" => $rs["~arDocumentStates"])))
										{
											$rs["SHOW"]["BP_START"] = "Y";
										}

										$rs["SHOW"]["BP_VIEW"] = (!empty($rs["arDocumentStates"]) ? "Y" : "N");
										$rs["SHOW"]["EDIT"] = ($rs["PERMISSION"] >= "W" ? "Y" : "N");
										$rs["SHOW"]["BP"] = ($rs["SHOW"]["BP_VIEW"] == "Y" || $rs["SHOW"]["BP_START"] == "Y" ? "Y" : "N");
										$rs["SHOW"]["EDIT"] = (($rs["PERMISSION"] >= "W" && intVal($rs["WF_PARENT_ELEMENT_ID"]) > 0) || $this->permission >= "W" ? "Y" : "N");
										if ($rs["BP_LOCK_STATUS"] == "red")
										{
											$rs["SHOW"]["UNLOCK"] = (CBPDocument::IsAdmin() ? "Y" : "N");
											$rs["SHOW"]["EDIT"] = "N";
										}
										elseif ($rs["LOCK_STATUS"] == "red" && $this->permission > "U") // TODO!
										{
											$rs["SHOW"]["UNLOCK"] = "Y";
											$rs["SHOW"]["EDIT"] = "N";
										}
										elseif ($this->check_creator && $rs["CREATED_BY"] != $GLOBALS["USER"]->GetId())
										{
											$rs["SHOW"]["UNLOCK"] = "N";
											$rs["SHOW"]["LOCK"] = "N";
											$rs["SHOW"]["DELETE"] = "N";
											$rs["SHOW"]["EDIT"] = "N";
										}
										else
										{
											$rs["SHOW"]["UNLOCK"] = ($rs['LOCK_STATUS'] == "yellow" ? "Y" : "N");
											$rs["SHOW"]["LOCK"] = ($rs['LOCK_STATUS'] == "green" ? "Y" : "N");
											$rs["SHOW"]["DELETE"] = $rs["SHOW"]["HISTORY"] = ($rs["PERMISSION"] > "U" ? "Y" : "N");
										}
									}
									$res["CHILDREN"][$rs["ID"]] = $rs;
								} while ($rs = $db_rs->Fetch());
							}
						}
						if (CBPDocument::CanUserOperateDocument(
							CBPCanUserOperateOperation::StartWorkflow,
							$USER->GetID(),
							$res["DOCUMENT_ID"],
							array(
								"IBlockId" => $this->IBLOCK_ID,
								"UserGroups" => $this->USER["GROUPS"],
								"AllUserGroups" => $res["USER_GROUPS"],
								"DocumentStates" => $res["~arDocumentStates"])))
						{
							$res["SHOW"]["BP_START"] = "Y";
						}
						$res["SHOW"]["BP_VIEW"] = (!empty($res["arDocumentStates"]) ? "Y" : "N");

						if($this->workflow == 'bizproc')
						{
							$res["SHOW"]["BP_VERSIONS"] = (intVal($res["WF_PARENT_ELEMENT_ID"]) <= 0 ? "Y" : "N");
							$res["SHOW"]["BP_CLONE"] = (intVal($res["WF_PARENT_ELEMENT_ID"]) <= 0 ? "Y" : "N");
							$res["SHOW"]["COPY"] = (intVal($res["WF_PARENT_ELEMENT_ID"]) <= 0 ? "Y" : "N");
						}
						else
						{
							$res["SHOW"]["BP_VERSIONS"] = $res["SHOW"]["BP_CLONE"] = $res["SHOW"]["COPY"] = 'N';
						}

						if ($this->CheckRight($res['PERMISSION'], 'element_edit_any_wf_status') < "W" && (intVal($res["WF_PARENT_ELEMENT_ID"]) <= 0) || $res["WF_PARENT_ELEMENT_ID"] == $res["ID"])
						{
							$arDocumentStates = CBPDocument::GetDocumentStates(
								$this->wfParams["DOCUMENT_TYPE"],
								null);

							if (!empty($arDocumentStates))
							{
								CBPDocument::CanUserOperateDocumentType(
									CBPCanUserOperateOperation::WriteDocument,
									$GLOBALS["USER"]->GetID(),
									$this->wfParams["DOCUMENT_TYPE"],
									array(
										"IBlockId" => $this->IBLOCK_ID,
										"IBlockPermission" => $this->permission,
										"UserGroups" => $this->USER["GROUPS"],
										"AllUserGroups" => $res["USER_GROUPS"],
										"DocumentStates" => $arDocumentStates));
							}
							else
							{
								$res["SHOW"]["BP_CLONE"] = "N";
							}
						}
						$res["SHOW"]["BP"] = ($res["SHOW"]["BP_VIEW"] == "Y" || $res["SHOW"]["BP_START"] == "Y" ? "Y" : "N");
						$res["SHOW"]["EDIT"] = ($this->CheckRight($res["PERMISSION"], "element_edit_any_wf_status") >= "W" ? "Y" : "N");

						if ($res["LOCK_STATUS"] == "red")
						{
							$res["SHOW"]["UNLOCK"] = ((CBPDocument::IsAdmin() || $this->CheckRight($res['PERMISSION'], "element_edit") > "W") ? "Y" : "N");
							$res["SHOW"]["LOCK"] = "N";
							$res["SHOW"]["HISTORY"] = ($this->CheckRight($res['PERMISSION'], 'element_edit') > "U" ? "Y" : "N");
							$res["SHOW"]["DELETE"] = "N";
							$res["SHOW"]["EDIT"] = "N";
						}
						elseif ($this->InTrash($res))
						{
							$bRightsEdit_gt_W = ($this->CheckRight($res['PERMISSION'], 'element_rights_edit') > "W");

							$res["SHOW"]["EDIT"] = ($bRightsEdit_gt_W ? "Y" : "N");
							$res["SHOW"]["HISTORY"] = ($bRightsEdit_gt_W ? "Y" : "N");
							$res["SHOW"]["DELETE"] = ($bRightsEdit_gt_W ? "Y" : "N");
							$res["SHOW"]["UNLOCK"] = "N";
							$res["SHOW"]["LOCK"] = "N";

							$res["SHOW"]["BP_START"] = "N";
							$res["SHOW"]["BP_VIEW"] = ($bRightsEdit_gt_W ? "Y" : "N");

							$res["SHOW"]["BP_VERSIONS"] = ($bRightsEdit_gt_W ? "Y" : "N");
							$res["SHOW"]["BP_CLONE"] = ($bRightsEdit_gt_W ? "Y" : "N");
							$res["SHOW"]["COPY"] = (intVal($res["WF_PARENT_ELEMENT_ID"]) <= 0 ? "Y" : "N");
						}
						elseif ($this->check_creator && $res["CREATED_BY"] != $GLOBALS["USER"]->GetId())
						{
							$res["SHOW"]["UNLOCK"] = "N";
							$res["SHOW"]["LOCK"] = "N";
							$res["SHOW"]["DELETE"] = "N";
							$res["SHOW"]["EDIT"] = "N";
						}
						else
						{
							$bElementEdit_gt_U = ($this->CheckRight($res['PERMISSION'], 'element_rights_edit') > 'U');
							$res["SHOW"]["HISTORY"] = ($bElementEdit_gt_U ? "Y" : "N");
							$res["SHOW"]["DELETE"] = ($bElementEdit_gt_U ? "Y" : "N");
							$res["SHOW"]["UNLOCK"] = (($bElementEdit_gt_U && ($res["LOCK_STATUS"] == "yellow")) ? "Y" : "N");
							$res["SHOW"]["LOCK"] = (($bElementEdit_gt_U && ($res["LOCK_STATUS"] == "green")) ? "Y" : "N");
						}

						if ($arBPParamRequired[$this->IBLOCK_ID])
							$res["SHOW"]["COPY"] = "N";
					}
				} // $this->workflow == "bizproc"
				else
				{
					$res["SHOW"]["UNLOCK"] = (($res['LOCK_STATUS'] == "yellow" || (($res['LOCK_STATUS'] == "red" ) && ($res["PERMISSION"] > 'W'))) ? "Y" : "N");
					$res["SHOW"]["LOCK"] = ($res['LOCK_STATUS'] == "green" ? "Y" : "N");
					$res["SHOW"]["COPY"] = "Y";
					$res["SHOW"]["HISTORY"] = $res["SHOW"]["DELETE"] = $res["SHOW"]["EDIT"] =
						($this->check_creator && $res["CREATED_BY"] != $GLOBALS["USER"]->GetId() ? "N" : "Y");
					$res["SHOW"]["HISTORY"] = (($res["SHOW"]["HISTORY"] == "Y" && $this->workflow == "bizproc_limited") ? "Y" : "N");

					if ($this->check_creator && $res["CREATED_BY"] != $GLOBALS["USER"]->GetId())
					{
						$res["SHOW"]["UNLOCK"] = "N";
						$res["SHOW"]["LOCK"] = "N";
						$res["SHOW"]["DELETE"] = "N";
						$res["SHOW"]["EDIT"] = "N";
					}
					elseif ($res["LOCK_STATUS"] == "red" && $res["PERMISSION"] < 'X')
					{
						$res["SHOW"]["DELETE"] = "N";
						$res["SHOW"]["EDIT"] = "N";
					}

					if ($this->e_rights)
					{
						$arShow = array(
							"COPY" => "element_edit",
							"DELETE" => "element_delete",
							"UNDELETE" => "element_no_prems",
							"EDIT" => "element_edit",
							"HISTORY" => "element_edit",
							"LOCK" => "element_edit",
							"UNLOCK" => "element_edit",
							"RIGHTS" => "element_rights_edit"
						);

						$bInTrash = ($this->InTrash($res));

						foreach($arShow as $action => $right)
						{
							if (
								($res['LOCK_STATUS'] == 'red')
								|| $bInTrash
							)
								$right = "element_rights_edit";

							if (!isset($res['E_RIGHTS'][$right]))
								$res['SHOW'][$action] = 'N';
						}
					}
				}
			}
			else
			{
				$res["PERMISSION"] = ($res["WF_NEW"] == "Y" || (intVal($res["WF_PARENT_ELEMENT_ID"]) > 0 && ($res["WF_PARENT_ELEMENT_ID"] != $res['ID'])) ? "D" : $this->permission);
				if ($res["PERMISSION"] > "D" && $this->workflow == "bizproc")
				{
					$res["PERMISSION"] = ($res["BP_PUBLISHED"] == "Y" ? $this->permission : "D");
				}
			}

			$res["SHOW"]["UNDELETE"] = "N";

			$secPath = "/".implode("/", $this->GetNavChain(array("section_id" => $res["IBLOCK_SECTION_ID"]), false));
			$res["SECTION_PATH"] = str_replace("//", "/", $secPath);
			$res["PATH"] = str_replace("//", "/", $secPath . "/" . $res["~NAME"]);
			$arFiles[$res["ID"]] = $res;
		}
		else
		{
			$res = array_merge($res, $arFiles[$res["ID"]]);
		}

		if ($_SERVER['REQUEST_METHOD'] == 'PROPFIND')
		{
			$info["path"] = $res['PATH'];
			$info["path"] = (SITE_CHARSET != "UTF-8" ? $GLOBALS["APPLICATION"]->ConvertCharset($info["path"], SITE_CHARSET, "UTF-8") : $info["path"]);

			$info["props"] = array();

			$info["props"][] = array('ns'=>'DAV:', 'name'=>"creationdate", 'val'=>
				(isset($res["DATE_CREATE_UNIX"]) ? $res["DATE_CREATE_UNIX"] : MakeTimeStamp($res["DATE_CREATE"])));
			$info["props"][] = array('ns'=>'DAV:', 'name'=>"getlastmodified", 'val'=>
				(isset($res["TIMESTAMP_X_UNIX"]) ? $res["TIMESTAMP_X_UNIX"] : MakeTimeStamp($res["TIMESTAMP_X"])));
			$info["props"][] = array('ns'=>'DAV:', 'name'=>"creationdate", 'val'=>MakeTimeStamp($res["DATE_CREATE"]));
			$info["props"][] = array('ns'=>'DAV:', 'name'=>"getlastmodified", 'val'=>MakeTimeStamp($res["TIMESTAMP_X"]));
			$info["props"][] = array('ns'=>'DAV:', 'name'=>"getcontenttype", 'val'=>$res["FILE"]["CONTENT_TYPE"]);
			$info["props"][] = array('ns'=>'DAV:', 'name'=>"getcontentlength", 'val'=>$res["FILE"]["FILE_SIZE"]);
			$info["props"][] = array('ns'=>'DAV:', 'name'=>"isreadonly", 'val'=>($res["PERMISSION"] >= "W" ? "false" : "true"));
			$info["props"][] = array('ns'=>'DAV:', 'name'=>"ishidden", 'val'=>($res["ACTIVE"] == "Y" ? "false" : "true"));
			$info["props"][] = array('ns'=>'DAV:', 'name'=>"iscollection", 'val'=>0);
			$info["props"][] = array('ns'=>'DAV:', 'name'=>"resourcetype", 'val'=>'');	 // required by RFC && LibreOffice
			$info["props"][] = $this->_get_lock_prop();
		}

		if ($this->InTrash($res))
		{
			if ($this->workflow != "bizproc")
			{
				$res["SHOW"]["EDIT"] = "N";
				$res["SHOW"]["LOCK"] = "N";
				$res["SHOW"]["UNLOCK"] = "N";

				$bAdmin = false;

				if ($GLOBALS['USER']->CanDoOperation('webdav_change_settings'))
					$bAdmin = true;

				elseif ($this->e_rights
					&& isset($res['E_RIGHTS']['element_rights_edit']))
						$bAdmin = true;

				elseif (!$this->e_rights
					&& $this->permission > "W")
						$bAdmin = true;

				$res["SHOW"]["DELETE"] = $bAdmin ? "Y" : "N";
				$res["SHOW"]["UNDELETE"] = $bAdmin ? "Y" : "N";
			}
		}


		if ($this->_parse_webdav_info($res))
		{
			if (is_array($res["PROPS"]))
			{
				foreach ($res["PROPS"] as $ns_name => $ns_props)
				{
					foreach ($ns_props as $prop_name => $prop_val)
					{
						if(is_scalar($prop_val))
						{
							if ($_SERVER['REQUEST_METHOD'] == 'PROPFIND')
								$info["props"][] = CWebDavBase::_mkprop($ns_name, $prop_name, $prop_val);
						}
					}
				}
			}
		}
		return $info;
	}

	function _parse_webdav_info(&$res)
	{
		if (isset($res["PROPERTY_WEBDAV_INFO_VALUE"]) && strlen($res["PROPERTY_WEBDAV_INFO_VALUE"]) > 0)
		{
			$arProps = @unserialize((strlen($res["~PROPERTY_WEBDAV_INFO_VALUE"]) > 0 ? $res["~PROPERTY_WEBDAV_INFO_VALUE"] : $res["PROPERTY_WEBDAV_INFO_VALUE"]));
			if (is_array($arProps["PROPS"]))
				$res["PROPS"] = $arProps["PROPS"];
			return true;
		}
		elseif (isset($res["DESCRIPTION"]) && strlen($res["DESCRIPTION"]) > 0)
		{
			$arProps = @unserialize((strlen($res["~DESCRIPTION"]) > 0 ? $res["~DESCRIPTION"] : $res["DESCRIPTION"]));
			if (is_array($arProps["PROPS"]))
				$res["PROPS"] = $arProps["PROPS"];
			return true;
		}
		return false;
	}

	function GetNavChain($options = array(), $for_url = false)
	{
		static $nav_chain = array();
		$bReturn = (strtoupper($for_url) === "ARRAY" ? "ARRAY" :
			($for_url === true ? "URL" : "SITE"));
		if (empty($options) && isset($this->arParams["dir_array"])) // === to deal with 0 at the root of IB
			$options["section_id"] = intval($this->arParams["dir_array"]["ID"]);
		elseif (isset($options['section_id']))
			$options['section_id'] = intval($options['section_id']);

		//mixin iblock id for symlinksection
		$dataCache = $options;
		$dataCache['IBLOCK_ID'] = $this->IBLOCK_ID;
		$dataCache['ROOT_SECTION_ID'] = empty($this->arRootSection['ID'])? 0 : $this->arRootSection['ID'];
		$id = md5(serialize($dataCache));
		if (!array_key_exists($id, $nav_chain))
		{
			if ($this->CACHE_OBJ && $this->CACHE_OBJ->InitCache($this->CACHE_TIME, $id, $this->CACHE_PATH."nav_chain"))
			{
				$nav_chain[$id] = $this->CACHE_OBJ->GetVars();
			}
			else
			{
				$options['check_permissions'] = false;
				$arObject = $this->GetObject($options);
				$nav_chain[$id] = array("URL" => array(), "SITE" => array(), "ARRAY" => array());
				if ($arObject["not_found"] == false && intVal($arObject["item_id"]) > 0)
				{
					$arFile = array();
					$section_id = $arObject["item_id"];
					if ($arObject["is_file"])
					{
						$arFile = $arObject["element_array"];
						$section_id = $arFile["IBLOCK_SECTION_ID"];
						$sectionData = $this->getSectionDataForLinkAnalyze($arFile['IBLOCK_SECTION_ID'], array(
							'ID' => $arFile['IBLOCK_SECTION_ID'],
							'IBLOCK_ID' => $arObject['element_array']['IBLOCK_ID'],
						));
					}
					else
					{
						$sectionData = $this->getSectionDataForLinkAnalyze($arObject["item_id"], array(
							'ID' => $arObject["item_id"],
							'IBLOCK_ID' => empty($arObject['dir_array']['IBLOCK_ID'])? $this->IBLOCK_ID : $arObject['dir_array']['IBLOCK_ID'],
						));
					}

					//simple detect link
					list($contextType, $contextEntityId) = $this->getContextData();
					$isSymlink = !$this->_symlinkMode && CWebDavSymlinkHelper::isLink($contextType, $contextEntityId, $sectionData);
					if($isSymlink)
					{
						$symlinkSectionData = CWebDavSymlinkHelper::getLinkData($contextType, $contextEntityId, $sectionData);
					}

					if($this->_symlinkMode)
					{
						$bRootFounded = (empty($this->_symlinkRealRootSectionData) ? true : false);
						foreach (CWebDavSymlinkHelper::getNavChain($this->_symlinkSectionData['IBLOCK_ID'], $this->_symlinkSectionData['ID']) as $res)
						{
							$this->MetaNames($res);
							if (!$bRootFounded && $res["ID"] == $this->_symlinkRealRootSectionData["ID"])
							{
								$bRootFounded = true;
								continue;
							}
							if (!$bRootFounded)
								continue;

							$url = $this->_uencode($res["NAME"], array("utf8" => "Y", "convert" => "full"));
							$nav_chain[$id]["URL"][] = $url;
							$nav_chain[$id]["SITE"][] = $res["NAME"];
							$nav_chain[$id]["ARRAY"][] = ($res + array("URL" => $url));
						}
					}

					$iblockId = $this->IBLOCK_ID;
					$sectionId = $section_id;
					if($isSymlink)
					{
						$iblockId = $symlinkSectionData['IBLOCK_ID'];
						$sectionId = $symlinkSectionData['ID'];
					}
					$bRootFounded = (empty($this->arRootSection) ? true : false);
					foreach (CWebDavSymlinkHelper::getNavChain($iblockId, $sectionId) as $res)
					{
						$this->MetaNames($res);
						if (!$bRootFounded && $res["ID"] == $this->arRootSection["ID"])
						{
							$bRootFounded = true;
							continue;
						}
						if (!$bRootFounded)
							continue;

						$url = $this->_uencode($res["NAME"], array("utf8" => "Y", "convert" => "full"));
						$nav_chain[$id]["URL"][] = $url;
						$nav_chain[$id]["SITE"][] = $res["NAME"];
						$nav_chain[$id]["ARRAY"][] = ($res + array("URL" => $url));
					}

					if($isSymlink)
					{
						$bRootFounded = (empty($symlinkSectionData[self::UF_LINK_SECTION_ID]) ? true : false);
						foreach (CWebDavSymlinkHelper::getNavChain($symlinkSectionData[self::UF_LINK_IBLOCK_ID], $section_id) as $res)
						{
							$this->MetaNames($res);
							if (!$bRootFounded && $res["ID"] == $symlinkSectionData[self::UF_LINK_SECTION_ID])
							{
								$bRootFounded = true;
								continue;
							}
							if (!$bRootFounded)
								continue;

							$url = $this->_uencode($res["NAME"], array("utf8" => "Y", "convert" => "full"));
							$nav_chain[$id]["URL"][] = $url;
							$nav_chain[$id]["SITE"][] = $res["NAME"];
							$nav_chain[$id]["ARRAY"][] = ($res + array("URL" => $url));
						}
					}

					if (!empty($arFile))
					{
						$url = $this->_uencode($res["NAME"], array("utf8" => "Y", "convert" => "full"));
						$nav_chain[$id]["URL"][] = $url;
						$nav_chain[$id]["SITE"][] = $arFile["NAME"];
						$nav_chain[$id]["ARRAY"][] = ($arFile + array("URL" => $url));
					}
				}
				if ($this->CACHE_OBJ)
				{
					$this->CACHE_OBJ->StartDataCache($this->CACHE_TIME, $id, $this->CACHE_PATH."_nav_chain");
					$this->CACHE_OBJ->EndDataCache($nav_chain[$id]);
				}
			}
		}
		$res = $nav_chain[$id][$bReturn];
		return (is_array($res) ? $res : array());
	}

	function GetPath($options = array())
	{
		$result = '';

		$elm = $this->GetObject($options);

		if (!$elm['not_found'])
		{
			$result = $this->base_url_full;
			$result .= "/".implode("/", $this->GetNavChain($options, true));
		}

		if ($elm['is_file'])
		{
			$result .= $elm['element_name'];
		}

		return $result;
	}

	function GetSectionsTree($options = array())
	{
		static $dataType = 'SectionsTree';
		if(empty($options['prependPath']))
		{
			$options['prependPath'] = '';
		}
		$dataCache = $options;
		$dataCache['IBLOCK_ID'] = $this->IBLOCK_ID;
		$dataCache['ROOT_SECTION_ID'] = empty($this->arRootSection['ID'])? 0 : $this->arRootSection['ID'];
		$id = md5(serialize($dataCache));
		$sections = $this->_dataCache($dataType . $id);
		if ($sections === false)
		{
			$arElement = $this->GetObject($options, false, true);
			if ($arElement["not_found"])
			{
				$sections = array();
			}
			else
			{
				$arFilter = array("IBLOCK_ID" => $this->IBLOCK_ID);
				if (isset($options['not_check_permissions']))
					$arFilter['CHECK_PERMISSIONS'] = 'N';
				$bRootFounded = (empty($this->arRootSection) ? true : false);
				if ($arElement["item_id"] > 0 && !empty($arElement["dir_array"]))
				{
					$arFilter["LEFT_MARGIN"] = intVal($arElement["dir_array"]["LEFT_MARGIN"]) + 1;
					$arFilter["RIGHT_MARGIN"] = intVal($arElement["dir_array"]["RIGHT_MARGIN"]) - 1;
				}
				elseif (!empty($this->arRootSection))
				{
					$arFilter["LEFT_MARGIN"] = intVal($this->arRootSection["LEFT_MARGIN"]) + 1;
					$arFilter["RIGHT_MARGIN"] = intVal($this->arRootSection["RIGHT_MARGIN"]) - 1;
				}

				if(!empty($arElement["is_dir"]) && $arElement["item_id"])
				{
					list($contextType, $contextEntityId) = $this->getContextData();
					$sectionData = $this->getSectionDataForLinkAnalyze($arElement["item_id"], $arElement);
					$iblockId = $sectionData['IBLOCK_ID'];
					if(CWebDavSymlinkHelper::isLink($contextType, $contextEntityId, $sectionData))
					{
						$arFilter['IBLOCK_ID'] = $iblockId;
						$symlinkSectionData = CWebDavSymlinkHelper::getLinkData($contextType, $contextEntityId, $sectionData);
						if(!empty($symlinkSectionData[self::UF_LINK_SECTION_ID]) && $symlinkSectionData['ID'] == $arElement["item_id"])
						{
							$margins = CIBlockSection::GetList(array(), array(
								'ID' => $symlinkSectionData[self::UF_LINK_SECTION_ID],
								'IBLOCK_ID' => $symlinkSectionData[self::UF_LINK_IBLOCK_ID],
								'CHECK_PERMISSIONS' => 'N',
							), false, array('LEFT_MARGIN', 'RIGHT_MARGIN', 'IBLOCK_ID'))->fetch();
							if($margins)
							{
								$arFilter["LEFT_MARGIN"] = intVal($margins["LEFT_MARGIN"]) + 1;
								$arFilter["RIGHT_MARGIN"] = intVal($margins["RIGHT_MARGIN"]) - 1;
							}
						}
					}
				}

				$arResult = array();
				$db_res = CIBlockSection::GetTreeList($arFilter, array(
					'ID',
					'CREATED_BY',
					'MODIFIED_BY',
					'IBLOCK_ID',
					'IBLOCK_SECTION_ID',
					'NAME',
					'LEFT_MARGIN',
					'RIGHT_MARGIN',
					'DEPTH_LEVEL',
					'SOCNET_GROUP_ID',
					'IBLOCK_CODE',
					'TIMESTAMP_X',
				)); // TODO: add e_rights check
				$trashID = $this->GetMetaID('TRASH');
				if ($db_res && $res = $db_res->Fetch())
				{
					$deep = -1;
					$arPath = array();
					$arExclude = array();
					do
					{
						if ($this->MetaNames($res))
						{
							$res["DEPTH_LEVEL"] = intVal($res["DEPTH_LEVEL"]);
							if(isset($arExclude[(int) $res["IBLOCK_SECTION_ID"]]) || $this->MetaSectionHide($res, !empty($options['NON_DROPPED_SECTION'])))
							{
								$arExclude[(int)$res["ID"]] = true;
							}
							else
							{
								if ($res["DEPTH_LEVEL"] > $deep)
								{
									$deep = $res["DEPTH_LEVEL"];
									array_push($arPath, strtolower(htmlspecialcharsbx($res["NAME"])));
								}
								elseif ($res["DEPTH_LEVEL"] == $deep)
								{
									array_pop($arPath);
									array_push($arPath, strtolower(htmlspecialcharsbx($res["NAME"])));
								}
								else
								{
									while ($res["DEPTH_LEVEL"] < $deep)
									{
										array_pop($arPath);
										$deep--;
									}
									array_pop($arPath);
									array_push($arPath, strtolower(htmlspecialcharsbx($res["NAME"])));
								}

								$res["PATH"] = $options['prependPath'] . implode("/", $arPath);
								$arResult[$res["ID"]] = $res;
							}
						}
						else
						{
							$arExclude[(int)$res["ID"]] = true;
						}
					} while ($res = $db_res->Fetch());
				}
				$sections = $arResult;
			}
			$this->_dataCache($dataType . $id, $sections);
		}

		if(!empty($options['setERights']))
		{
			$sectionIds = array();
			foreach ($sections as $section)
			{
				$sectionIds[] = $section['ID'];
			}
			unset($section);

			$sectionRights = $this->GetPermissions('SECTION', $sectionIds);
			foreach ($sections as &$section)
			{
				if (isset($sectionRights[$section['ID']]))
				{
					$section['E_RIGHTS'] = $sectionRights[$section['ID']];
				}
			}
			unset($section);
		}
		global $USER;
		if(!empty($options['SET_IS_SHARED']) && $USER->getId())
		{
			$querySelfSharedSections = \Bitrix\Webdav\FolderInviteTable::getList(array(
				'filter' => array(
					'USER_ID' => $USER->getId(),
					'!=INVITE_USER_ID' => $USER->getId(),
				),
				'select' => array('SECTION_ID', 'IBLOCK_ID'),
			));
			while($folderInvite = $querySelfSharedSections->fetch())
			{
				$selfSharedSections[$folderInvite['SECTION_ID']] = $folderInvite;
			}
			unset($folderInvite);

			foreach ($sections as &$section)
			{
				if(isset($selfSharedSections[$section['ID']]))
				{
					$section['IS_SHARED'] = true;
				}
			}
			unset($section);
		}

		return $sections;
	}

	function ClearCache($path, $mode=false)
	{
		if ($mode == false)
		{
			if ($path == "section")
			{
				BXClearCache(true, $this->CACHE_PATH."root_section");
				BXClearCache(true, $this->CACHE_PATH."section");
				BXClearCache(true, $this->CACHE_PATH."sections_tree");
				BXClearCache(true, $this->CACHE_PATH."nav_chain");
			}
		}
		elseif ($mode == 'local')
		{
			foreach ($this->CACHE as $method=>$arCache)
			{
				if (!is_array($path))
					$path = array($path);

				foreach ($path as $v)
				{
					$cache_id = $this->_get_element_cacheID($v, true);
					if (isset($this->CACHE[$method][$cache_id]))
						unset($this->CACHE[$method][$cache_id]);
					$cache_id = $this->_get_element_cacheID($v, false);
					if (isset($this->CACHE[$method][$cache_id]))
						unset($this->CACHE[$method][$cache_id]);
				}
			}
		}
	}

	function _clear_cache_for_object(&$options)
	{
		$options = (is_array($options) ? $options : array());
		$arClear = array();
		if (array_key_exists("path", $options)) $arClear[] = $options['path'];
		if (array_key_exists('section_id', $options)) $arClear[] = 'section'.$options['section_id'];
		if (array_key_exists('element_id', $options)) $arClear[] = 'element'.$options['element_id'];
		$this->ClearCache($arClear, 'local');
	}

	function CheckRights($method = "", $strong = false, $path = "")
	{
		$result = true;
		if (!parent::CheckRights($method, $strong))
		{
			$result = false;
			$errorCode = (parent::CheckRights($method, $strong, true));
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage('WD_ACCESS_DENIED'), (!!$errorCode ? $errorCode : 'ACCESS_DENIED'), __LINE__);
		}
		elseif (!empty($path))
		{
			$path = $this->_udecode($path);
			$strFileName = basename($path);
			$extention = strtolower(strrchr($strFileName, '.'));
			if (in_array($method, array("COPY", "MOVE", "PUT")))
			{
				if (IsFileUnsafe($strFileName) || $strFileName == "index.php")
				{
					$result = false;
					$GLOBALS['APPLICATION']->ThrowException(GetMessage("WD_FILE_ERROR14"), "FORBIDDEN_NAME", __LINE__);
				}
			}
		}
		return $result;
	}

	function CheckWebRights($method = "", $arParams = array(), $simple = true)
	{
		if($this->withoutAuthorization)
		{
			return true;
		}
		$strong = ($method !== "");

		$path = '';
		if (is_array($arParams['arElement']))
			$path = (isset($arParams['arElement']['item_id']) ? $arParams['arElement']['item_id'] : '');
		elseif (is_string($arParams['arElement']))
			$path = $arParams['arElement'];
		$result = $this->CheckRights($method, $strong, $path);
		if ((! $result) || $simple)
			return $result;

		$arError = array();
		$action = strtolower(is_set($arParams, "action") ? $arParams["action"] : $arParams["ACTION"]);
		$arElement = (is_set($arParams, "arElement") ? $arParams["arElement"] : array());
		static $arErrors = array();
		$static_id = md5(serialize(array($action, $arElement["ID"], $GLOBALS["USER"]->GetID())));
		if (array_key_exists($static_id, $arErrors))
		{
			$arError = $arErrors[$static_id];
		}
		else
		{
			if ( $this->e_rights )
			{
				foreach(array('arElement', 'from', 'to') as $elm)
				{
					if (is_set($arParams, $elm))
					{
						if ((!isset($arParams[$elm]['not_found']) || ($arParams[$elm]['not_found']===true)) && !in_array($action, array('create', 'copy', 'move', 'mkcol')))
							$arError[] = array("id" => "bad_element", "text" => GetMessage("WD_FILE_ERROR105"));
					}
				}

				if (empty($arError))
				{
					if ($action == 'copy')
					{
						//from[]
						//to[]
						$arTo = (isset($arParams['to']) ? $arParams['to'] : array());
						$arFrom = (isset($arParams['from']) ? $arParams['from'] : array());

						$nCount = min(sizeof($arTo), sizeof($arFrom));
						for ($i=0;$i<$nCount;$i++)
						{
							$To = $arTo[$i];
							$From = $arFrom[$i];
							$type = (($To['is_file']) ? 'ELEMENT' : 'SECTION');
							$id = ($To['not_found'] ? $To['parent_id'] : $To['item_id']);
							$op = ($From['is_file'] ?	'section_element_bind' : 'section_section_bind');
							if (!$this->GetPermission($type, $id, $op))
								$arError[] = array("id" => "", "text" => GetMessage("WD_ACCESS_DENIED"));
						}
					}
					elseif (($action == 'create') || ($action == 'mkcol'))
					{
						//arElement
						//null
						if (empty($arElement))
						{
							$arParent = $this->GetObject();
							$bAllowEdit = false;
							if ($arParent['not_found'] === false)
							{
								$bAllowEdit = $this->GetPermission(($arParent['is_file'] ? 'ELEMENT' : 'SECTION'), $arParent['item_id'], 'element_edit');
							}

							return $bAllowEdit;
						}
						else
						{
							$type = 'SECTION';
							if (isset($arElement['parent_id']) && $arElement['parent_id']>0)
							{
								$id = $arElement['parent_id'];
							}
							else
							{
								$id = $this->IBLOCK_ID;
								$type = 'IBLOCK';
							}

							if ($action == 'mkcol')
								return $this->GetPermission($type, $id, 'section_section_bind');

							if ($arElement['is_dir'])
							{
								if(!$this->GetPermission($type, $id, 'section_section_bind'))
									$arError[] = array("id" => "", "text" => GetMessage("WD_ACCESS_DENIED"));
							}
							else
							{
								if (!empty($arParams['create_element_in_section']) || ($this->workflow != "workflow" && $this->workflow != "bizproc"))
								{
									if (! (
										$this->GetPermission($type, $id, 'section_element_bind')
									))
										$arError[] = array("id" => "cannot_create", "text" => GetMessage("WD_ACCESS_DENIED"));
								}
								elseif ($this->workflow == "workflow")
								{
									$db_res = CWorkflowStatus::GetDropDownList("N", "desc");
									if (!($db_res && $res = $db_res->Fetch()))
										$arError[] = array("id" => "bad_wf_statuses", "text" => GetMessage("WD_ACCESS_DENIED"));
								}
								elseif ($this->workflow == 'bizproc')
								{
									$arDocumentStates = CBPDocument::GetDocumentStates($this->wfParams['DOCUMENT_TYPE'], null);
									$arUserGroups = $this->USER["GROUPS"];
									$arUserGroups[] = "Author";

									$canWrite = false;
									if (!CBPDocument::CanUserOperateDocumentType(
										CBPCanUserOperateOperation::WriteDocument,
										$GLOBALS["USER"]->GetID(),
										$this->wfParams['DOCUMENT_TYPE'],
										array(
											"IBlockPermission" => $this->permission,
											"AllUserGroups" => $arUserGroups,
											"DocumentStates" => $arDocumentStates)
										))
										$arError[] = array("id" => "bad_bizproc_permision", "text" => GetMessage("WD_ACCESS_DENIED"));
								}
							}
						}
					}
					elseif ($action == 'delete' || $action == 'undelete') // aka move to trash, move op will be checked later
					{
						//arElement
						$type = (($arElement['is_dir']) ? 'SECTION' : 'ELEMENT');
						if ($type == 'ELEMENT')
						{
							$res = $this->GetPermission($type, $arElement['item_id'], 'element_delete');
							if (!$res)
								$arError[] = array("id" => "", "text" => GetMessage("WD_ACCESS_DENIED"));
						} else
						{
							$res = $this->GetPermission($type, $arElement['item_id'], 'section_delete', false);
							if (!$res)
								$arError[] = array("id" => "", "text" => GetMessage("WD_ACCESS_DENIED"));
						}
					}
					elseif ($action == 'destroy')
					{
						//arElement
						$id = $arElement['item_id'];
						$type = (($arElement['is_dir']) ? 'SECTION' : 'ELEMENT');
						$op = (($arElement['is_dir']) ?	'section_delete' : 'element_delete');
						if (!$this->GetPermission($type, $id, $op, false))
							$arError[] = array("id" => "", "text" => GetMessage("WD_ACCESS_DENIED"));
					}
					elseif ($action == 'edit' || $action == 'lock' || $action == 'proppatch' || $action == 'delete_dropped')
					{
						//arElement
						$id = $arElement['item_id'];
						$type = $arElement['is_dir'] ? 'SECTION' : 'ELEMENT';

						if ($arElement['is_dir'])
						{
							if (!$this->GetPermission($type, $id, 'section_edit'))
								$arError[] = array("id" => "", "text" => GetMessage("WD_ACCESS_DENIED"));
						}
						else
						{
							if ($arElement["LOCK_STATUS_BP"] == "red")
								$arError[] = array("id" => "locked", "text" => GetMessage("WD_FILE_ERROR107"));
							elseif ($this->check_creator && $arElement["CREATED_BY"] != $GLOBALS["USER"]->GetID())
							{
								$arError[] = array("id" => "bad_author", "text" => GetMessage("WD_FILE_ERROR108"));
							}
							elseif ($this->GetPermission($type, $id, 'element_edit_any_wf_status'))
							{
								true;
							}
							elseif ($this->workflow == "workflow" && $this->GetPermission($type, $id, 'element_edit'))
							{
								$arWorkFlow = array("LAST_ID" => CIBlockElement::WF_GetLast($arElement["item_id"]));
								$arWorkFlow["STATUS_ID"] = CIBlockElement::WF_GetCurrentStatus($arWorkFlow["LAST_ID"], $arWorkFlow["STATUS_TITLE"]);
								$arWorkFlow["STATUS_PERMISSION"] = CIBlockElement::WF_GetStatusPermission($arWorkFlow["STATUS_ID"]);
								if ($arWorkFlow["STATUS_ID"] > 1 && $arWorkFlow["STATUS_PERMISSION"] < 2)
								{
									$arError[] = array("id" => "bad_wf_status_permission", "text" => GetMessage("WD_FILE_ERROR109"));
								}
							}
							elseif ($this->workflow == 'bizproc' && $this->GetPermission($type, $id, 'element_edit'))
							{
								$documentId = $this->wfParams['DOCUMENT_TYPE'];
								$documentId[2] = $arElement["item_id"];
								$arDocumentStates = CBPDocument::GetDocumentStates(
									$this->wfParams['DOCUMENT_TYPE'],
									$documentId
								);

								$arUserGroups = $this->USER["GROUPS"];
								if ($arElement["CREATED_BY"] == $GLOBALS["USER"]->GetID())
									$arUserGroups[] = "Author";
								if (!CBPDocument::CanUserOperateDocument(
									CBPCanUserOperateOperation::WriteDocument,
									$GLOBALS["USER"]->GetID(),
									$documentId,
									array(
										"IBlockPermission" => $this->permission,
										"AllUserGroups" => $arUserGroups,
										"DocumentStates" => $arDocumentStates)
									))
								{
									$arError[] = array("id" => "bad_bizproc_permision", "text" => GetMessage("WD_ACCESS_DENIED"));
								}
							}
							else
							{
								$arError[] = array("id" => "bad_permision", "text" => GetMessage("WD_ACCESS_DENIED"));
							}
						}
					}
					elseif ($action == 'read' || $action == 'propfind')
					{
						//arElement, null
						if ($arElement)
						{
							$id = $arElement['item_id'];
							$type = (($arElement['is_dir']) ? 'SECTION' : 'ELEMENT');
							$op = (($arElement['is_dir']) ?	'section_read' : 'element_read');
							if (!$this->GetPermission($type, $id, $op))
								$arError[] = array("id" => "bad_permision", "text" => GetMessage("WD_ACCESS_DENIED"));

							if ($type == 'SECTION' && $id == $this->GetMetaID('TRASH'))
							{
								if (!$this->GetPermission($type, $id, 'section_delete'))
								{
									$arError[] = array("id" => "bad_permision", "text" => GetMessage("WD_ACCESS_DENIED"));
								}
							}
						}
						else
						{
							if (!$this->GetPermission('IBLOCK', $this->IBLOCK_ID, 'section_read'))
								$arError[] = array("id" => "bad_permision", "text" => GetMessage("WD_ACCESS_DENIED"));
						}
					}
					elseif ($action == 'move')
					{
						//from // auto recusive
						//to // auto recusive
						$arTo = (isset($arParams['to']) ? $arParams['to'] : array());
						$arFrom = (isset($arParams['from']) ? $arParams['from'] : array());

						$nCount = min(sizeof($arTo), sizeof($arFrom));
						for ($i=0;$i<$nCount;$i++)
						{
							$To = $arTo[$i];
							$From = $arFrom[$i];

							$type = (($From['is_dir']) ? 'SECTION' : 'ELEMENT');
							$id = $From['item_id'];
							$op = ($From['is_dir'] ? 'section_edit' : 'element_edit');
							if (!$this->GetPermission($type, $id, $op))
								$arError[] = array("id" => "bad_permision", "text" => GetMessage("WD_ACCESS_DENIED"));

							if ($To['not_found'])
							{
								$type = 'SECTION';
								$id = $To['parent_id'];
								$op = ($arFrom['is_dir'] ? 'section_section_bind' : 'section_element_bind'); // TODO: bizproc ?
								if (!$this->GetPermission($type, $id, $op))
								{
									$arError[] = array("id" => "bad_permision", "text" => GetMessage("WD_ACCESS_DENIED"));
								}
							}
							else
							{
								$type = (($To['is_dir']) ? 'SECTION' : 'ELEMENT');
								$id = $To['item_id'];
								$op = ($To['is_dir'] ? ($arFrom['is_dir'] ? 'section_section_bind' : 'section_element_bind') : 'element_edit'); // TODO: bizproc ?
								if (!$this->GetPermission($type, $id, $op))
								{
									$arError[] = array("id" => "bad_permision", "text" => GetMessage("WD_ACCESS_DENIED"));
								}
							}
						}
					}
				}
			}
			else // not e_rights
			{
				// check iblock rights
				if ($this->permission < "R")
				{
					$arError[] = array("id" => "cannot_read", "text" => GetMessage("WD_ACCESS_DENIED"));
				}
				elseif ($this->permission > "U")
				{
					true;
				}
				elseif ($action == "read" || $action == "propfind")
				{
					true;
				}
				elseif ($this->permission < "U")
				{
					$arError[] = array("id" => "cannot_workflow", "text" => GetMessage("WD_ACCESS_DENIED"));
				}
				elseif ($action == "create")
				{
					if ($this->workflow != "workflow" && $this->workflow != "bizproc")
					{
						$arError[] = array("id" => "cannot_write", "text" => GetMessage("WD_ACCESS_DENIED"));
					}
					elseif ($this->workflow == "workflow")
					{
						$db_res = CWorkflowStatus::GetDropDownList("N", "desc");
						if (!($db_res && $res = $db_res->Fetch()))
							$arError[] = array("id" => "bad_wf_statuses", "text" => GetMessage("WD_ACCESS_DENIED"));
					}
					elseif ($this->workflow == 'bizproc')
					{
						$arDocumentStates = CBPDocument::GetDocumentStates($this->wfParams['DOCUMENT_TYPE'], null);
						$arUserGroups = $this->USER["GROUPS"];
						$arUserGroups[] = "Author";

						$canWrite = false;
						if (!CBPDocument::CanUserOperateDocumentType(
								CBPCanUserOperateOperation::WriteDocument,
								$GLOBALS["USER"]->GetID(),
								$this->wfParams['DOCUMENT_TYPE'],
								array(
									"IBlockPermission" => $this->permission,
									"AllUserGroups" => $arUserGroups,
									"DocumentStates" => $arDocumentStates)
							))
							$arError[] = array("id" => "bad_bizproc_permision", "text" => GetMessage("WD_ACCESS_DENIED"));
					}
				}
				elseif (!is_array($arElement) || empty($arElement))
				{
					$arError[] = array("id" => "bad_element", "text" => GetMessage("WD_FILE_ERROR105"));
				}
				elseif ($action == "clone")
				{
					if ($this->workflow != "bizproc")
					{
						$arError[] = array("id" => "bad_workflow", "text" => GetMessage("WD_FILE_ERROR106"));
					}
					else
					{
						// User has to have permissions to read parent document && to create new document
						$arDocumentStates = CBPDocument::GetDocumentStates($this->wfParams['DOCUMENT_TYPE'], null);
						if (!($arElement["PERMISSION"] >= "R" && CBPDocument::CanUserOperateDocumentType(
							CBPCanUserOperateOperation::WriteDocument,
							$GLOBALS["USER"]->GetID(),
							$this->wfParams['DOCUMENT_TYPE'],
							array(
								"IBlockPermission" => $this->permission,
								"AllUserGroups" => array_merge($this->USER["GROUPS"], array("author")),
								"DocumentStates" => $arDocumentStates)))):
							$arError[] = array("id" => "bad_permission", "text" => GetMessage("WD_ACCESS_DENIED"));
						endif;
					}
				}
				elseif (!in_array($action, array("delete", "move", "edit", "unlock", "lock")))
				{
					$arError[] = array("id" => "bad_action", "text" => GetMessage("WD_ERROR_BAD_ACTION"));
				}
				else
				{
					if ($arElement["LOCK_STATUS_BP"] == "red")
						$arError[] = array("id" => "locked", "text" => GetMessage("WD_FILE_ERROR107"));
					elseif ($arElement["LOCK_STATUS"] == "red" && ($action != "unlock" || $arElement["SHOW"]["UNLOCK"] != "Y"))
					{
						$arError[] = array("id" => "locked", "text" => str_replace(
								array("#ID#", "#DATE#"),
								array($arElement["locked_by"], $arElement["date_lock"]),
								GetMessage("WD_ERROR_ELEMENT_LOCKED")));
					}
					elseif ($this->check_creator && $arElement["CREATED_BY"] != $GLOBALS["USER"]->GetID())
					{
						$arError[] = array("id" => "bad_author", "text" => GetMessage("WD_FILE_ERROR108"));
					}
					elseif ($this->workflow == "workflow")
					{
						$arWorkFlow = array("LAST_ID" => CIBlockElement::WF_GetLast($arElement["item_id"]));
						$arWorkFlow["STATUS_ID"] = CIBlockElement::WF_GetCurrentStatus($arWorkFlow["LAST_ID"], $arWorkFlow["STATUS_TITLE"]);
						$arWorkFlow["STATUS_PERMISSION"] = CIBlockElement::WF_GetStatusPermission($arWorkFlow["STATUS_ID"]);
						if ($arWorkFlow["STATUS_ID"] > 1 && $arWorkFlow["STATUS_PERMISSION"] < 2)
						{
							$arError[] = array("id" => "bad_wf_status_permission", "text" => GetMessage("WD_FILE_ERROR109"));
						}
					}
					elseif ($this->workflow == 'bizproc')
					{
						$documentId = $this->wfParams['DOCUMENT_TYPE'];
						$documentId[2] = $arElement["item_id"];
						$arDocumentStates = CBPDocument::GetDocumentStates(
							$this->wfParams['DOCUMENT_TYPE'],
							$documentId
						);

						$arUserGroups = $this->USER["GROUPS"];
						if ($arElement["CREATED_BY"] == $GLOBALS["USER"]->GetID())
							$arUserGroups[] = "Author";
						if (!CBPDocument::CanUserOperateDocument(
								CBPCanUserOperateOperation::WriteDocument,
								$GLOBALS["USER"]->GetID(),
								$documentId,
								array(
									"IBlockPermission" => $this->permission,
									"AllUserGroups" => $arUserGroups,
									"DocumentStates" => $arDocumentStates)
							))
						{
							$arError[] = array("id" => "bad_bizproc_permision", "text" => GetMessage("WD_ACCESS_DENIED"));
						}
					}
				}
			}
			$arErrors[$static_id] = $arError;
		}
		if (empty($arError))
		{
			$e = new CAdminException($arError);
			$this->LAST_ERROR = $e->GetString();
			if ($this->LAST_ERROR == '<br>')
				$this->LAST_ERROR = '';
			return true;
		}
		else
		{
			$e = new CAdminException($arError);
			$this->LAST_ERROR = $e->GetString();
			if ($this->LAST_ERROR == '<br>')
				$this->LAST_ERROR = '';
			return false;
		}
	}

	function GetUfEntity()
	{
		if (empty($this->UF_ENTITY))
		{

			$id = $this->IBLOCK_ID;
			if (isset($this->arRootSection['ID']))
			{
				$id .= '_'.$this->arRootSection['ID'];
			}
			$this->UF_ENTITY = "WEBDAV_".$id."_FILE";
		}
		return $this->UF_ENTITY;
	}

	function GetUfFields($id = 0)
	{
		global $USER_FIELD_MANAGER;

		$id = intval($id);

		if ($id <= 0)
		{
			if ($this->UF_FIELDS === null)
				$this->UF_FIELDS = $USER_FIELD_MANAGER->GetUserFields($this->GetUfEntity(), 0, LANGUAGE_ID);

			return $this->UF_FIELDS;
		}
		else
		{
			$result = $USER_FIELD_MANAGER->GetUserFields($this->GetUfEntity(), $id, LANGUAGE_ID);
			return $result;
		}
	}

	function GetUfFieldsSimpleArray($id = 0)
	{
		$arQ = $this->GetUfFields($id);
		$arRes = array();
		foreach($arQ as $k => $v)
		{
			$vS = $v['VALUE'] != null ? $v['VALUE'] : "";
			$arRes[$k] = $vS;
		}
		return $arRes;
	}

	function IndexUfValues($arFields)
	{
		global $USER_FIELD_MANAGER;

		if (! ($arFields['MODULE_ID'] == 'iblock')
			&& $arFields['PARAM2'] == $this->IBLOCK_ID)
				return false;

		$ufContent = "";
		$ufContent .= $USER_FIELD_MANAGER->OnSearchIndex($this->GetUfEntity(), $arFields['ITEM_ID']);
		$arFields['BODY'] .= "\n" . $ufContent;

		return $arFields;
	}

	function _ib_elm_add($arFields, $bWorkFlow=false, $bUpdateSearch=true, $bResizePictures=false)
	{
		global $USER_FIELD_MANAGER;
		global $APPLICATION;

		$bUF = (isset($arFields['USER_FIELDS']));
		if ($bUF)
		{
			$UF_ENTITY = $this->GetUfEntity();

			if ( ! $USER_FIELD_MANAGER->CheckFields($UF_ENTITY, 0, $arFields['USER_FIELDS']))
			{
				if(is_object($APPLICATION) && $APPLICATION->GetException())
				{
					$e = $APPLICATION->GetException();
					$this->LAST_ERROR .= $e->GetString();
					return false;
				}
			}

			$handlerID = AddEventHandler('search', 'BeforeIndex', array($this, 'IndexUfValues'));
		}

		if ($bUpdateSearch && $bUF)
			$bUpdateSearch = false;

		$el = new CIBlockElement();
		$result = $el->Add($arFields, $bWorkFlow, $bUpdateSearch, $bResizePictures);

		$ID = intval($result);
		if ($ID <= 0)
		{
			$this->LAST_ERROR = $el->LAST_ERROR;
			return false;
		}
		else
		{
			if (
				isset($arFields['PROPERTY_VALUES'])
				&& ! $this->ValidatePropertyValues($ID, $arFields['PROPERTY_VALUES'], $arFields['IBLOCK_ID']) // as SetPropertyValues doesn't handle SaveFile errors
			)
			{
				$this->LAST_ERROR = GetMessage("WD_FILE_ERROR111");
				$el->Delete($ID);
				return false;
			}

			if ($bUF)
			{
				$USER_FIELD_MANAGER->Update($UF_ENTITY, $ID, $arFields['USER_FIELDS']);
				$el->UpdateSearch($ID, true);
			}

			$this->_onEvent('Add', $ID);
		}

		if ($bUF)
			RemoveEventHandler('search', 'BeforeIndex', $handlerID);

		CWebDavDiskDispatcher::sendEventToOwners(null, array(
			'IBLOCK_ID' => (int)$arFields['IBLOCK_ID'],
			'ID' => (int)$arFields['IBLOCK_SECTION_ID'],
		), 'add');

		return (int) $result;
	}

	function ValidatePropertyValues($ID, $arProperties, $iblockId = null)
	{
		if($iblockId === null)
		{
			$iblockId = $this->IBLOCK_ID;
		}
		$result = true;
		$arPropNames = array_keys($arProperties);

		if (empty($arPropNames))
			return $result;

		foreach ($arProperties as $code => $value)
		{
			$dbProps = CIBlockElement::GetProperty($iblockId, $ID, array(), array('CODE' => $code, 'ACTIVE' => 'Y'));

			if (
				$dbProps
				&& ($arProp = $dbProps->Fetch())
			)
			{
				if ($arProp['PROPERTY_TYPE'] == 'F')
				{
					if (((int) $arProp['VALUE']) > 0)
					{
						$arFile = CFile::GetFileArray($arProp['VALUE']);
						if (
							is_array($arFile)
							&& isset($arFile['FILE_SIZE'])
							&& (empty($arProperties[$code]['size']) && !empty($arFile['FILE_SIZE']) || $arFile['FILE_SIZE'] == $arProperties[$code]['size'])
						)
						{
							unset($arProperties[$code]);
						}
					}
				}
				else
				{
					if ($arProp['VALUE'] == $arProperties[$code])
					{
						unset($arProperties[$code]);
					}
				}
			}
		}

		$result = (sizeof($arProperties) <= 0);
		return $result;
	}

	function _ib_elm_delete($ID)
	{
		global $USER_FIELD_MANAGER;

		static $ibe = null;
		if ($ibe === null)
			$ibe = new CIBlockElement();

		$result = $ibe->Delete($ID);
		if ($result)
		{
			$USER_FIELD_MANAGER->Delete($this->GetUfEntity(), $ID);
		}
		else
		{
			$this->LAST_ERROR = $ibe->LAST_ERROR;
		}
		return $result;
	}

	function IsBpParamRequired() // for template
	{
		static $dataType = 'IsBpParamRequired';
		$result = 'N';

		if ($this->workflow == 'bizproc')
		{
			$result = $this->_dataCache($dataType);

			if ($result === false)
			{
				$arTemplateStates =
					CBPWorkflowTemplateLoader::GetDocumentTypeStates(
						$this->wfParams["DOCUMENT_TYPE"],
						CBPDocumentEventType::Create
					)
					+ CBPWorkflowTemplateLoader::GetDocumentTypeStates(
						$this->wfParams["DOCUMENT_TYPE"],
						CBPDocumentEventType::Edit
					);

				foreach ($arTemplateStates as $arTemplateState)
				{
					if (is_array($arTemplateState["TEMPLATE_PARAMETERS"]))
					{
						foreach ($arTemplateState["TEMPLATE_PARAMETERS"] as $res)
						{
							if (
								$res["Required"] == 1
								&& empty($res["Default"])
							)
							{
								$result = "Y";
								break;
							}
						}
						if ($result === 'Y')
							break;
					}
				}

				$this->_dataCache($dataType, $result);
			}
		}
		return ($result === 'Y');
	}

	public static function getRootSectionDataForGroup($groupId)
	{
		$groupLib = CWebDavIblock::LibOptions('group_files', false, SITE_ID);
		if ($groupLib && isset($groupLib['id']) && ($iblockId = intval($groupLib['id'])))
		{
			$groupSectionId = CIBlockWebdavSocnet::getSectionId($iblockId, 'group', $groupId);
			if ($groupSectionId)
			{
				return array(
					'IBLOCK_ID' => $iblockId,
					'SECTION_ID' => $groupSectionId,
				);
			}
		}
		return array();
	}

	public static function getRootSectionDataForUser($userId)
	{
		$userLib = CWebDavIblock::LibOptions('user_files', false, SITE_ID);
		if ($userLib && isset($userLib['id']) && ($iblockId = intval($userLib['id'])))
		{
			$userSectionId = CWebDavIblock::getRootSectionIdForUser($iblockId, $userId);
			if ($userSectionId)
			{
				return array(
					'IBLOCK_ID' => $iblockId,
					'SECTION_ID' => $userSectionId,
				);
			}
		}
		return array();
	}

	public static function getRootSectionIdForUser($iblockId, $userId)
	{
		global $USER_FIELD_MANAGER;
		global $UF_USE_BP;
		$result = CIBlockWebdavSocnet::getSectionId($iblockId, 'user', $userId);
		if (($result = intval($result)) > 0)
		{
			return $result;
		}

		$fields = Array(
			'IBLOCK_ID' => $iblockId,
			'ACTIVE' => 'Y',
			'SOCNET_GROUP_ID' => false,
			'IBLOCK_SECTION_ID' => 0,
			'UF_USE_BP' => 'N',
			'UF_USE_EXT_SERVICES' => CWebDavIblock::resolveDefaultUseExtServices(),
		);

		$user = CUser::getById($userId)->fetch();
		if(empty($user))
		{
			return false;
		}
		$fields['NAME'] = trim($user['LAST_NAME'] . ' ' . $user['FIRST_NAME']);
		$fields['NAME'] = trim(!empty($fields["NAME"]) ? $fields['NAME'] : $user['LOGIN']);
		$fields['CREATED_BY'] = $user['ID'];
		$fields['MODIFIED_BY'] = $user['ID'];

		if (CIBlock::GetArrayByID($iblockId, "RIGHTS_MODE") === "E")
		{
			$tasks = CWebDavIblock::GetTasks();
			$fields['RIGHTS'] = array(
				'n0' => array('GROUP_CODE' => 'U' . $userId, 'TASK_ID' => $tasks['X'])
			);
		}

		$UF_USE_BP = $fields['UF_USE_BP'];
		$USER_FIELD_MANAGER->editFormAddFields('IBLOCK_' . $iblockId . '_SECTION', $fields);
		$section = new CIBlockSection;
		$sectionId = $section->add($fields);
		if (!$sectionId)
		{
			return false;
		}

		WDClearComponentCache(array(
			'webdav.element.edit',
			'webdav.element.hist',
			'webdav.element.upload',
			'webdav.element.view',
			'webdav.menu',
			'webdav.section.edit',
			'webdav.section.list'
		));

		return $sectionId;
	}

	/********** new code **********/

	function _dataCache($type, $value = null)
	{
		if ($type == 'all')
		{
			$type = self::GET_ALL_CACHED_SETTINGS;
		}
		if ($value === null) // GET
		{
			return $this->GetCachedSettings($type);
		}
		else
		{
			$this->SetCachedSettings($type, $value);
		}
	}

	public function getRootSection()
	{
		return $this->arRootSection;
	}

	public function getRootSectionDataByKey($key, $default = null)
	{
		if(empty($this->arRootSection))
		{
			return $default;
		}
		return isset($this->arRootSection[$key])? $this->arRootSection[$key] : $default;
	}

	function SetRootSection($id, $force=false, $enableRedirect = true)
	{

		$id = intVal($id);
		$this->CACHE_PATH = $this->cachePathBase;
		$this->arRootSection = false;

		if ($id <= 0)
		{
			return true;
		}

		if($force && isset(self::$arSectionCache[$id]))
		{
			unset(self::$arSectionCache[$id]);
		}

		if(isset(self::$arSectionCache[$id]))
		{
			$this->arRootSection = self::$arSectionCache[$id];
		}
		elseif(!$force)
		{
			$res = $this->GetCache($id, "root_section", true);
			if($res["result"])
			{
				$this->arRootSection = $res["data"];
			}
		}

		if($this->arRootSection === false)
		{

			$arFilter = array(
				"IBLOCK_ID" => $this->IBLOCK_ID,
				"ID" => $id,
				"CHECK_PERMISSIONS" => "N"
			);
			$db_res = CIBlockSection::GetList(array(), $arFilter, false, array_merge(array('UF_USE_EXT_SERVICES'), self::getUFNamesForSectionLink()));

			if ($db_res && $res = $db_res->Fetch())
			{
				//fixes
				if (CModule::IncludeModule('socialnetwork'))
				{
					// fix an old bug, folder don't contain group name
					if ($res['NAME'] == GetMessage("SONET_GROUP_PREFIX"))
					{
						$arGroup = CSocNetGroup::GetByID(intval($res['SOCNET_GROUP_ID']));
						if($arGroup !== false)
						{
							$res['NAME'] = GetMessage("SONET_GROUP_PREFIX").$arGroup['NAME'];
							$ibs = new CIBlockSection();
							$ibs->Update($res['ID'], array('NAME' => $res['NAME']));
						}
					}
				}

				// fix incorrect iblock settings for socnet
				if (!$this->_symlinkMode &&
					IsModuleInstalled("bizproc") &&
					(CIBlock::GetArrayByID($this->IBLOCK_ID, "BIZPROC") == "N")
				)
				{
					$ib = new CIBlock;
					$res = $ib->Update($this->IBLOCK_ID, array(
						'BIZPROC' => 'Y',
						'WORKFLOW' => 'N'
					));
					if($enableRedirect)
					{
						LocalRedirect($GLOBALS['APPLICATION']->GetCurPageParam());
					}
				}
				//fixes end

				$this->arRootSection = $res;
				self::$arSectionCache[$id] = $res;
				$arTags = array("iblock_id_" . $this->IBLOCK_ID);
				$this->SetCache($id, "root_section", self::$arSectionCache[$id], $arTags, true);
			}
			else
			{
				return false;
			}

		}
		$this->CACHE_PATH = $this->cachePathBase . intval($id).'/';
		return true;
	}

	/********** Cache **********/
	function GetCache($id, $dirName, $useRootSection = true)
	{
		$res = array( "result" => false, "data" => null );
		if(defined("BX_COMP_MANAGED_CACHE") && $this->CACHE_OBJ)
		{
			$cachePath = ($useRootSection ? $this->CACHE_PATH : $this->cachePathBase) . $dirName;
			if($this->CACHE_OBJ->InitCache($this->CACHE_TIME, $id, $cachePath))
			{
				$res["result"] = true;
				$res["data"] = $this->CACHE_OBJ->GetVars();
			}
		}
		return $res;
	}

	function SetCache($id, $dirName, $value, $arTags = array(), $useRootSection = true)
	{
		global $CACHE_MANAGER;
		if(defined("BX_COMP_MANAGED_CACHE") && $this->CACHE_OBJ)
		{
			$cachePath = ($useRootSection ? $this->CACHE_PATH : $this->cachePathBase) . $dirName;
			$this->CACHE_OBJ->StartDataCache($this->CACHE_TIME, $id, $cachePath);

			$CACHE_MANAGER->StartTagCache($cachePath);
			foreach($arTags as $cTagName)
			{
				$CACHE_MANAGER->RegisterTag($cTagName);
			}
			$CACHE_MANAGER->EndTagCache();

			$this->CACHE_OBJ->EndDataCache($value);
		}
	}

	function CleanCache($id, $dirName, $arTags = array(), $useRootSection = true)
	{
		global $CACHE_MANAGER;
		if(defined("BX_COMP_MANAGED_CACHE") && $this->CACHE_OBJ)
		{
			if(count($arTags) > 0)
			{
				foreach($arTags as $cTagName)
				{
					$CACHE_MANAGER->ClearByTag($cTagName);
				}
			}
			else
			{
				$cachePath = ($useRootSection ? $this->CACHE_PATH : $this->cachePathBase) . $dirName;
				$this->CACHE_OBJ->Clean($id, $cachePath);
			}
		}
	}

	function GetCachedSettings($type)
	{
		$key = $this->IBLOCK_ID . "_" . intval($this->arRootSection["ID"]);
		if(!array_key_exists($key, self::$arSettingsCache))
		{
			$cacheID = "wd_ib_params_" . $key;
			$res = $this->GetCache($cacheID, "settings", true);
			if($res["result"])
			{
				self::$arSettingsCache[$key] = $res["data"];
			}
		}
		if($type === self::GET_ALL_CACHED_SETTINGS)
		{
			return self::$arSettingsCache[$key];
		}
		elseif(isset(self::$arSettingsCache[$key][$type]))
		{
			return self::$arSettingsCache[$key][$type];
		}
		return false;
	}

	function SetCachedSettings($type, $value)
	{
		$key = $this->IBLOCK_ID . "_" . intval($this->arRootSection["ID"]);
		$cacheID = 'wd_ib_params_' . $key;
		if(!array_key_exists($key, self::$arSettingsCache))
		{
			self::$arSettingsCache[$key] = $this->GetCachedSettings(self::GET_ALL_CACHED_SETTINGS);
		}
		self::$arSettingsCache[$key][$type] = $value;

		$this->CleanCache($cacheID, "settings", array(), true);

		$arTags = array("iblock_id_" . $this->IBLOCK_ID);
		$this->SetCache($cacheID, "settings", self::$arSettingsCache[$key], $arTags, true);
	}
	/********** Cache End **********/

	/********** IBlock Rights **********/
	/* $this->workflow != "workflow" && $this->workflow != "bizproc" */

	static protected function GetIBlockRightsObject($type, $iBlockID, $id = null)
	{
		if ($type !== self::OBJ_TYPE_IBLOCK && $type !== self::OBJ_TYPE_SECTION && $type !== self::OBJ_TYPE_ELEMENT)
		{
			throw new Exception("_get_ib_rights_object invalid type \"".htmlspecialcharsbx($type)."\"");
		}

		$ibRights = null;
		if ($iBlockID === null)
		{
			throw new Exception("_get_ib_rights_object called, but no iBlockID is set");
		}

		if ($type !== self::OBJ_TYPE_IBLOCK && $id === null)
		{
			throw new Exception("_get_ib_rights_object called, but no ID is set");
		}

		if ($type == self::OBJ_TYPE_SECTION)
		{
			$ibRights = new CIBlockSectionRights($iBlockID, $id);
		}
		elseif ($type == self::OBJ_TYPE_ELEMENT)
		{
			$ibRights = new CIBlockElementRights($iBlockID, $id);
		}
		else
		{
			$ibRights = new CIBlockRights($iBlockID);
		}

		return $ibRights;
	}

	static function CheckUserIBlockPermission($permission, $type, $iBlockID, $id = null)
	{
		$obj = self::GetIBlockRightsObject($type, $iBlockID, $id);
		if(!is_object($obj))
		{
			return false;
		}
		return $obj::UserHasRightTo($iBlockID, $id, $permission);
	}

	/********** IBlock Rights End **********/

	public static function checkUfUseExtServices($iblockId)
	{
		$iblockId = intval($iblockId);
		$query = CUserTypeEntity::GetList(array(), array('ENTITY_ID' => 'IBLOCK_'.$iblockId.'_SECTION', 'FIELD_NAME' => 'UF_USE_EXT_SERVICES'));
		if (!$query || !($row = $query->GetNext()))
		{
			$fields = array(
				'ENTITY_ID' => 'IBLOCK_'.$iblockId.'_SECTION',
				'FIELD_NAME' => 'UF_USE_EXT_SERVICES',
				'USER_TYPE_ID' => 'string',
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SETTINGS' => array('DEFAULT_VALUE' => self::resolveDefaultUseExtServices())
			);
			$fields['EDIT_FORM_LABEL'][LANGUAGE_ID] = GetMessage('WD_OPTIONS_ALLOW_EXT_SERVICES');
			$userField  = new CUserTypeEntity;
			$userField->Add($fields);
			$GLOBALS['USER_FIELD_MANAGER']->arFieldsCache = array();
		}
	}

	/**
	 * Get default value to UF_USE_EXT_SERVICES. This is not DEFAULT_VALUE from DB & USER_FIELD_MANAGER
	 */
	public static function resolveDefaultUseExtServices($value = null)
	{
		return $value == "N" ? "N" : "Y";
	}

	public static function appendRightsOnElements(array $files, array $rights)
	{
		$tasks = CWebDavIblock::GetTasks();
		$reformatRights = array();
		$i = 0;
		foreach ($rights as $letter => $groupCodes)
		{
			if(!is_array($groupCodes))
			{
				$groupCodes = array($groupCodes);
			}
			foreach ($groupCodes as $groupCode)
			{
				$reformatRights['n' . $i] = array(
					'GROUP_CODE' => $groupCode,
					'TASK_ID' => $tasks[$letter],//todo check isset!
				);
				$i++;
			}
			unset($groupCode);
		}
		unset($right);

		foreach ($files as $file)
		{
			if(!is_array($file))
			{
				continue;
			}

			if (CIBlock::GetArrayByID($file['IBLOCK_ID'], "RIGHTS_MODE") === "E")
			{
				$rightObject = CWebDavIblock::_get_ib_rights_object('ELEMENT', $file['ID'], $file['IBLOCK_ID']);
				$rightObject->SetRights(CWebDavTools::appendRights($rightObject, $reformatRights, $tasks));
			}
		}
		unset($file);
	}

	final public static function appendRightsOnSections(array $sections, array $rights)
	{
		$tasks = CWebDavIblock::GetTasks();
		$reformatRights = array();
		$i = 0;
		foreach ($rights as $letter => $groupCodes)
		{
			if(!is_array($groupCodes))
			{
				$groupCodes = array($groupCodes);
			}
			foreach ($groupCodes as $groupCode)
			{
				$reformatRights['n' . $i] = array(
					'GROUP_CODE' => $groupCode,
					'TASK_ID' => $tasks[$letter],//todo check isset!
				);
				$i++;
			}
			unset($groupCode);
		}
		unset($right);

		foreach ($sections as $section)
		{
			$sectionId = (int)$section['ID'];
			if($sectionId <= 0)
			{
				continue;
			}

			if (CIBlock::GetArrayByID($section['IBLOCK_ID'], "RIGHTS_MODE") === "E")
			{
				$rightObject = CWebDavIblock::_get_ib_rights_object('SECTION', $sectionId, $section['IBLOCK_ID']);
				$rightObject->SetRights(CWebDavTools::appendRights($rightObject, $reformatRights, $tasks));
			}
		}
		unset($section);
	}

	final public static function removeRightsOnSections(array $sections, array $rights)
	{
		$tasks = CWebDavIblock::GetTasks();
		$reformatRights = array();
		$i = 0;
		foreach ($rights as $letter => $groupCodes)
		{
			if(!is_array($groupCodes))
			{
				$groupCodes = array($groupCodes);
			}
			foreach ($groupCodes as $groupCode)
			{
				$reformatRights['n' . $i] = array(
					'GROUP_CODE' => $groupCode,
					'TASK_ID' => $tasks[$letter],//todo check isset!
				);
				$i++;
			}
			unset($groupCode);
		}
		unset($right);

		foreach ($sections as $section)
		{
			$sectionId = (int)$section['ID'];
			if($sectionId <= 0)
			{
				continue;
			}

			if (CIBlock::GetArrayByID($section['IBLOCK_ID'], "RIGHTS_MODE") === "E")
			{
				$rightObject = CWebDavIblock::_get_ib_rights_object('SECTION', $sectionId, $section['IBLOCK_ID']);
				$rightObject->SetRights(CWebDavTools::removeRights($rightObject, $reformatRights, $tasks));
			}
		}
		unset($section);
	}

	/**
	 * @return array
	 */
	private function getContextData()
	{
		if (!empty($this->attributes['user_id']))
		{
			$contextType     = CWebDavSymlinkHelper::ENTITY_TYPE_USER;
			$contextEntityId = $this->attributes['user_id'];

			return array($contextType, $contextEntityId);
		}
		elseif (!empty($this->attributes['group_id']))
		{
			$contextType     = CWebDavSymlinkHelper::ENTITY_TYPE_GROUP;
			$contextEntityId = $this->attributes['group_id'];

			return array($contextType, $contextEntityId);
		}
		elseif(isset($this->arRootSection['ID']))
		{
			$contextType = CWebDavSymlinkHelper::ENTITY_TYPE_SECTION;
			$contextEntityId = $this->arRootSection['ID'];

			return array($contextType, $contextEntityId);
		}
		else
		{
			$contextType = CWebDavSymlinkHelper::ENTITY_TYPE_SHARED;
			//todo $contextEntityId !!!
			$contextEntityId = $this->IBLOCK_ID;

			return array($contextType, $contextEntityId);
		}
	}

	private function getSectionDataForLinkAnalyze($sectionId, array $miscData = array(), $forwardFromSymlinkSectionToReal = true)
	{
		if(!empty($miscData['IBLOCK_ID']))
		{
			$iblockId = $miscData['IBLOCK_ID'];
			CWebDavSymlinkHelper::setIblockIdForSectionId($sectionId, $iblockId);
		}
		else
		{
			$iblockId = CWebDavSymlinkHelper::getIblockIdForSectionId($sectionId);
		}

		if($forwardFromSymlinkSectionToReal)
		{
			if(!empty($this->arParams['dir_array']['ID']) && $this->arParams['dir_array']['ID'] == $sectionId)
			{
				//this sectionId is symlink. Forward data to symlink data
				if(!empty($this->arParams['dir_array'][self::UF_LINK_IBLOCK_ID]))
				{
					$sectionId = $this->arParams['dir_array'][self::UF_LINK_SECTION_ID];
					$iblockId = $this->arParams['dir_array'][self::UF_LINK_IBLOCK_ID];

					CWebDavSymlinkHelper::setIblockIdForSectionId($sectionId, $iblockId);
				}
				else
				{
					$iblockId = $this->arParams['dir_array']['IBLOCK_ID'];
					CWebDavSymlinkHelper::setIblockIdForSectionId($sectionId, $iblockId);
				}
			}
			elseif(!empty($miscData['dir_array']['ID']) && $miscData['dir_array']['ID'] == $sectionId)
			{
				//this sectionId is symlink. Forward data to symlink data
				if(!empty($miscData['dir_array'][self::UF_LINK_IBLOCK_ID]))
				{
					$sectionId = $miscData['dir_array'][self::UF_LINK_SECTION_ID];
					$iblockId = $miscData['dir_array'][self::UF_LINK_IBLOCK_ID];

					CWebDavSymlinkHelper::setIblockIdForSectionId($sectionId, $iblockId);
				}
				else
				{
					$iblockId = $miscData['dir_array']['IBLOCK_ID'];
					CWebDavSymlinkHelper::setIblockIdForSectionId($sectionId, $iblockId);
				}
			}
			elseif(!empty($miscData['ID']) && $miscData['ID'] == $sectionId)
			{
				//this sectionId is symlink. Forward data to symlink data
				if(!empty($miscData[self::UF_LINK_IBLOCK_ID]))
				{
					$sectionId = $miscData[self::UF_LINK_SECTION_ID];
					$iblockId = $miscData[self::UF_LINK_IBLOCK_ID];

					CWebDavSymlinkHelper::setIblockIdForSectionId($sectionId, $iblockId);
				}
				else
				{
					$iblockId = $miscData['IBLOCK_ID'];
					CWebDavSymlinkHelper::setIblockIdForSectionId($sectionId, $iblockId);
				}
			}
		}

		return array(
			'ID' => $sectionId,
			'IBLOCK_ID' => $iblockId,
		);
	}

	final public static function getUFNamesForSectionLink()
	{
		if(!self::isEnabledUFSymlinkMode())
		{
			return array();
		}
		return array(
			self::UF_LINK_IBLOCK_ID,
			self::UF_LINK_SECTION_ID,
			self::UF_LINK_CAN_FORWARD,
			self::UF_LINK_ROOT_SECTION_ID,
		);
	}

	final public static function isEnabledUFSymlinkMode()
	{
		return (bool)self::$_fetchUFSymlinkMode;
	}

	final public static function enableUFSymlinkMode()
	{
		self::$_fetchUFSymlinkMode = true;
	}

	final public static function disableUFSymlinkMode()
	{
		self::$_fetchUFSymlinkMode = false;
	}

	final public static function needBlockByDisk()
	{
		return
			(
				Option::get('disk', 'process_converted', false) === 'Y' ||
				Option::get('webdav', 'process_converted', false) === 'Y'
			) ||
			(
				isModuleInstalled('webdav') &&
					(
						Option::get('disk', 'successfully_converted', false) === 'Y' ||
						Option::get('webdav', 'successfully_converted', false) === 'Y'
					)
			)
		;
	}

	final public static function OnBeforeIBlockElementAdd($fields)
	{
		if(!static::needBlockByDisk())
		{
			return true;
		}
		if(empty($fields['IBLOCK_ID']))
		{
			return true;
		}
		if(CIBlock::GetArrayByID($fields['IBLOCK_ID'], 'IBLOCK_TYPE_ID') === 'library')
		{
			global $APPLICATION;
			$APPLICATION->throwException(GetMessage('WD_BLOCKED_BY_DISK'));
			return false;
		}

	}

	final public static function OnBeforeIBlockElementUpdate($fields)
	{
		if(!static::needBlockByDisk())
		{
			return true;
		}
		if(empty($fields['IBLOCK_ID']))
		{
			return true;
		}
		if(CIBlock::GetArrayByID($fields['IBLOCK_ID'], 'IBLOCK_TYPE_ID') === 'library')
		{
			global $APPLICATION;
			$APPLICATION->throwException(GetMessage('WD_BLOCKED_BY_DISK'));
			return false;
		}

	}

	final public static function OnBeforeIBlockElementDelete($id)
	{
		if(!static::needBlockByDisk())
		{
			return true;
		}
		$fields = CIBlockElement::GetList(array(), array('ID' => $id), false, false, array('IBLOCK_ID'))->fetch();
		if(empty($fields['IBLOCK_ID']))
		{
			return true;
		}
		if(CIBlock::GetArrayByID($fields['IBLOCK_ID'], 'IBLOCK_TYPE_ID') === 'library')
		{
			global $APPLICATION;
			$APPLICATION->throwException(GetMessage('WD_BLOCKED_BY_DISK'));
			return false;
		}

	}

	final public static function OnBeforeIBlockSectionAdd($fields)
	{
		if(!static::needBlockByDisk())
		{
			return true;
		}
		if(empty($fields['IBLOCK_ID']))
		{
			return true;
		}
		if(CIBlock::GetArrayByID($fields['IBLOCK_ID'], 'IBLOCK_TYPE_ID') === 'library')
		{
			global $APPLICATION;
			$APPLICATION->throwException(GetMessage('WD_BLOCKED_BY_DISK'));
			return false;
		}

	}

	final public static function OnBeforeIBlockSectionUpdate($fields)
	{
		if(!static::needBlockByDisk())
		{
			return true;
		}
		if(empty($fields['IBLOCK_ID']))
		{
			return true;
		}
		if(CIBlock::GetArrayByID($fields['IBLOCK_ID'], 'IBLOCK_TYPE_ID') === 'library')
		{
			global $APPLICATION;
			$APPLICATION->throwException(GetMessage('WD_BLOCKED_BY_DISK'));
			return false;
		}

	}

	final public static function OnBeforeIBlockSectionDelete($id)
	{
		if(!static::needBlockByDisk())
		{
			return true;
		}
		$fields = CIBlockSection::GetList(array(), array('ID' => $id), false, array('IBLOCK_ID'))->fetch();
		if(empty($fields['IBLOCK_ID']))
		{
			return true;
		}
		if(CIBlock::GetArrayByID($fields['IBLOCK_ID'], 'IBLOCK_TYPE_ID') === 'library')
		{
			global $APPLICATION;
			$APPLICATION->throwException(GetMessage('WD_BLOCKED_BY_DISK'));
			return false;
		}

	}
}