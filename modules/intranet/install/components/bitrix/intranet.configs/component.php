<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (
	!(
		\Bitrix\Main\Loader::includeModule("intranet") && $GLOBALS['USER']->IsAdmin()
		|| \Bitrix\Main\Loader::includeModule("bitrix24") && $GLOBALS['USER']->CanDoOperation('bitrix24_config')
	)
)
{
	$APPLICATION->AuthForm(GetMessage("CONFIG_ACCESS_DENIED"));
}

use Bitrix\Main\Localization\CultureTable;

$arParams["CONFIG_PAGE"] = $APPLICATION->GetCurPageParam("", array("success", "otp"));
$arParams["CONFIG_PATH_TO_POST"] = SITE_DIR."company/personal/user/".$USER->getId()."/blog/edit/new/";

$arResult["ERROR"] = "";
$arResult["IS_BITRIX24"] = IsModuleInstalled("bitrix24");

if(!function_exists("Bitrix24SaveLogo"))
{
	function Bitrix24SaveLogo($arFile, $arRestriction = Array(), $mode = "")
	{
		$mode == "retina" ? "_retina" : "";
		$oldFileID = COption::GetOptionInt("bitrix24", "client_logo".$mode, "");

		$arFile = $arFile + Array(
				"del" => ($oldFileID ? "Y" : ""),
				"old_file" => (intval($oldFileID) > 0 ? intval($oldFileID): 0 ),
				"MODULE_ID" => "bitrix24"
			);

		$max_file_size = (array_key_exists("max_file_size", $arRestriction) ? intval($arRestriction["max_file_size"]) : 0);
		$max_width = (array_key_exists("max_width", $arRestriction) ? intval($arRestriction["max_width"]) : 0);
		$max_height = (array_key_exists("max_height", $arRestriction) ? intval($arRestriction["max_height"]) : 0);
		$extensions = (array_key_exists("extensions", $arRestriction) && strlen($arRestriction["extensions"]) > 0 ? trim($arRestriction["extensions"]) : false);

		$error = CFile::CheckFile($arFile, /*$max_file_size*/0, false, $extensions);
		if (strlen($error)>0)
		{
			return $error;
		}

		if ($max_width > 0 || $max_height > 0)
		{
			$error = CFile::CheckImageFile($arFile, /*$max_file_size*/0, $max_width, $max_height);
			if (strlen($error)>0)
			{
				return $error;
			}
		}

		$arFile["name"] = "logo_".randString(8).".png";
		$fileID = (int)CFile::SaveFile($arFile, "bitrix24");

		return $fileID;
	}
}

if (\Bitrix\Main\Loader::includeModule("bitrix24"))
{
	$arResult["LICENSE_TYPE"] = CBitrix24::getLicenseType();
	$arResult["LICENSE_PREFIX"] = CBitrix24::getLicensePrefix();
	$arResult["IS_LICENSE_PAID"] = CBitrix24::IsLicensePaid();
	$arResult['SHOW_GOOGLE_API_KEY_FIELD'] = \CBitrix24::isCustomDomain();
}
elseif(\Bitrix\Main\Loader::includeModule('fileman') && class_exists('Bitrix\Fileman\UserField\Address'))
{
	$arResult['SHOW_GOOGLE_API_KEY_FIELD'] = true;
}

$arResult['SHOW_ADDRESS_FORMAT'] = \Bitrix\Main\Loader::includeModule('location');

$arResult["DATE_FORMATS"] = array("DD.MM.YYYY", "DD/MM/YYYY", "MM.DD.YYYY", "MM/DD/YYYY", "YYYY/MM/DD", "YYYY-MM-DD");
$arResult["TIME_FORMATS"] = array("HH:MI:SS", "H:MI:SS T");
$arResult["NAME_FORMATS"] = CSite::GetNameTemplates();
$arResult["ORGANIZATION_TYPE"] = COption::GetOptionString("intranet", "organization_type", "");

$rsSite = CSite::GetByID(SITE_ID);
if ($arSite = $rsSite->Fetch())
{
	$arResult["CUR_DATE_FORMAT"] = $arSite["FORMAT_DATE"];
	$arResult["CUR_TIME_FORMAT"] = str_replace($arResult["CUR_DATE_FORMAT"]." ", "", $arSite["FORMAT_DATETIME"]);
	$arResult["WEEK_START"] = $arSite["WEEK_START"];
	$arResult["CULTURE_ID"] = $arSite["CULTURE_ID"];
	$arResult["CUR_NAME_FORMAT"] = $arSite["FORMAT_NAME"];
}

if (\Bitrix\Main\Loader::includeModule("calendar"))
{
	$arResult["WORKTIME_LIST"] = array();
	for ($i = 0; $i < 24; $i++)
	{
		$arResult["WORKTIME_LIST"][strval($i)] = CCalendar::FormatTime($i, 0);
		$arResult["WORKTIME_LIST"][strval($i).'.30'] = CCalendar::FormatTime($i, 30);
	}
	$arResult["CALENDAT_SET"] = CCalendar::GetSettings(array('getDefaultForEmpty' => false));
	$arResult["WEEK_DAYS"] = Array('MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU');
}

// phone number default country
$countriesReference = GetCountryArray();
$arResult['COUNTRIES'] = array();
foreach ($countriesReference['reference_id'] as $k => $v)
{
	$arResult['COUNTRIES'][$v] = $countriesReference['reference'][$k];
}
$phoneNumberDefaultCountry = Bitrix\Main\PhoneNumber\Parser::getDefaultCountry();
$arResult['PHONE_NUMBER_DEFAULT_COUNTRY'] = GetCountryIdByCode($phoneNumberDefaultCountry);

$arResult["IS_DISK_CONVERTED"] = COption::GetOptionString('disk', 'successfully_converted', false) == 'Y';

$arResult["DISK_VIEWER_SERVICE"] = array();
if (Bitrix\Main\Loader::includeModule("disk"))
{
	$documentHandlersManager = \Bitrix\Disk\Driver::getInstance()->getDocumentHandlersManager();

	$optionList = array();
	foreach($documentHandlersManager->getHandlersForView() as $handler)
	{
		$optionList[$handler->getCode()] = $handler->getName();
	}
	unset($handler);
	$arResult["DISK_VIEWER_SERVICE"] = $optionList;

	$arResult["DISK_VIEWER_SERVICE_DEFAULT"] = \Bitrix\Disk\Configuration::getDefaultViewerServiceCode();

	$arResult["DISK_LIMIT_PER_FILE_SELECTED"] = COption::GetOptionInt("disk", 'disk_version_limit_per_file', 0);
	$arResult["DISK_LIMIT_PER_FILE"] = array(0 => GetMessage('DISK_VERSION_LIMIT_PER_FILE_UNLIMITED'), 3=> 3, 10 => 10, 25 => 25, 50 => 50, 100 => 100, 500 => 500);
}

if(\Bitrix\Main\ModuleManager::isModuleInstalled('rest'))
{
	$arResult['MP_ALLOW_USER_INSTALL_EXTENDED'] = !$arResult['IS_BITRIX24'] || \Bitrix\Bitrix24\Feature::isFeatureEnabled('rest_userinstall_extended');
}
$arResult['SHOW_RENAME_POPUP'] = ($_GET['change_address'] == 'yes');

//logo for bitrix24
if (
	$_SERVER["REQUEST_METHOD"] == "POST" && check_bitrix_sessid()
	&& ($arResult["IS_BITRIX24"] && \Bitrix\Bitrix24\Feature::isFeatureEnabled("set_logo") || !$arResult["IS_BITRIX24"])
)
{
	\Bitrix\Intranet\Composite\CacheProvider::deleteUserCache();

	if (isset($_FILES["client_logo"]))
	{
		$error = "";
		$APPLICATION->RestartBuffer();
		$arFile = $_FILES["client_logo"];

		$APPLICATION->RestartBuffer();
		if ($arFile["name"])
		{
			$fileID = Bitrix24SaveLogo($arFile, array("extensions" => "png", "max_height" => 55, "max_width" => 222));
			if (intval($fileID))
			{
				if ($arResult["IS_BITRIX24"])
				{
					COption::SetOptionInt("bitrix24", "client_logo", $fileID);
				}
				else
				{
					COption::SetOptionInt("bitrix24", "client_logo", $fileID, false, SITE_ID);
				}

				$res["path"] =  CFile::GetPath($fileID);
			}
			else
			{
				$error = str_replace("<br>", "", $fileID);
				$res["error"] =  $error;
			}

			echo \Bitrix\Main\Web\Json::encode($res);
		}
		die();
	}

	if (isset($_FILES["client_logo_retina"]))
	{
		$error = "";
		$APPLICATION->RestartBuffer();
		$arFile = $_FILES["client_logo_retina"];

		$APPLICATION->RestartBuffer();
		if ($arFile["name"])
		{
			$fileID = Bitrix24SaveLogo($arFile, array("extensions" => "png", "max_height" => 110, "max_width" => 444), "retina");
			if (intval($fileID))
			{
				if ($arResult["IS_BITRIX24"])
				{
					COption::SetOptionInt("bitrix24", "client_logo_retina", $fileID);
				}
				else
				{
					COption::SetOptionInt("bitrix24", "client_logo_retina", $fileID, false, SITE_ID);
				}

				$res["path"] =  CFile::GetPath($fileID);
			}
			else
			{
				$error = str_replace("<br>", "", $fileID);
				$res["error"] =  $error;
			}

			echo \Bitrix\Main\Web\Json::encode($res);
		}
		die();
	}

	if (isset($_POST["client_delete_logo"]) && $_POST["client_delete_logo"] == "Y")
	{
		$mode = $_POST["mode"] == "retina" ? "_retina" : "";
		$fileId = COption::GetOptionInt("bitrix24", "client_logo".$mode, "");
		CFile::Delete($fileId);
		if ($arResult["IS_BITRIX24"])
		{
			COption::RemoveOption("bitrix24", "client_logo".$mode);
		}
		else
		{
			COption::RemoveOption("bitrix24", "client_logo".$mode, SITE_ID);
		}

		$APPLICATION->RestartBuffer();
		die();
	}
}

if ($arResult["IS_BITRIX24"])
{
	$arResult["IP_RIGHTS"] = array();
	$dbIpRights = Bitrix\Bitrix24\OptionTable::getList(array(
		"filter" => array("=NAME" => "ip_access_rights")
	));
	if ($arIpRights = $dbIpRights->Fetch())
	{
		$arResult["IP_RIGHTS"] = unserialize($arIpRights["VALUE"]);
	}
}

if ($_SERVER["REQUEST_METHOD"] == "POST"  && (isset($_POST["save_settings"]) )&& check_bitrix_sessid())
{
	if (defined("BX_COMP_MANAGED_CACHE"))
	{
		global $CACHE_MANAGER;
		$CACHE_MANAGER->ClearByTag("bitrix24_left_menu");
	}

	if (isset($_REQUEST["site_title"]))
	{
		if ($arResult["IS_BITRIX24"])
		{
			COption::SetOptionString("bitrix24", "site_title", $_REQUEST["site_title"]);
		}
		else
		{
			COption::SetOptionString("bitrix24", "site_title", $_REQUEST["site_title"], false, SITE_ID);
		}
	}

	if ($arResult["IS_BITRIX24"])
	{
		\Bitrix\Intranet\Composite\CacheProvider::deleteUserCache();

		if (isset($_REQUEST["logo_name"]) && strlen($_REQUEST["logo_name"])>0)
		{
			COption::SetOptionString("main", "site_name", $_REQUEST["logo_name"]);
			$iblockID = COption::GetOptionInt("intranet", "iblock_structure");
			$db_up_department = CIBlockSection::GetList(Array(), Array("SECTION_ID"=>0, "IBLOCK_ID"=>$iblockID));
			if ($ar_up_department = $db_up_department->Fetch())
			{
				$up_dep_id = $ar_up_department['ID'];
				if (CIBlockRights::UserHasRightTo($iblockID, $up_dep_id, 'section_edit'))
				{
					$section = new CIBlockSection;
					$res = $section->Update($up_dep_id, array("NAME" => $_REQUEST["logo_name"]));
				}
			}
		}

		if (strlen($_POST["email_from"])>0 && check_email($_POST["email_from"]))
		{
			COption::SetOptionString("main", "email_from", $_POST["email_from"]);
			COption::SetOptionString("forum", "FORUM_FROM_EMAIL", $_POST["email_from"]);
		}
		else
			$activateError = GetMessage("CONFIG_EMAIL_ERROR");

		if (\CBitrix24::IsNetworkAllowed() && in_array($arResult["LICENSE_PREFIX"], array("ru", "ua", "kz", "by")))
		{
			if (CModule::IncludeModule('socialservices'))
			{
				$socnetObj = new \Bitrix\Socialservices\Network();
				if (strlen($_POST["network_avaiable"])>0)
				{
					$socnetObj->setEnable(true);
				}
				else
				{
					$socnetObj->setEnable(false);
				}
			}
		}

		//self register
		if (\Bitrix\Main\Loader::includeModule("socialservices"))
		{
			\Bitrix\Socialservices\Network::setRegisterSettings(array(
				"REGISTER" => isset($_POST["allow_register"]) ? "Y" : "N",
			));
		}

		//allow invite users
		if (strlen($_POST["allow_invite_users"])>0)
			COption::SetOptionString("bitrix24", "allow_invite_users", "Y");
		else
			COption::SetOptionString("bitrix24", "allow_invite_users", "N");
	}

	if (
		$arResult["IS_BITRIX24"] && \Bitrix\Bitrix24\Feature::isFeatureEnabled("remove_logo24")
		|| !$arResult["IS_BITRIX24"]
	)
	{
		if (strlen($_POST["logo24"])>0)
			COption::SetOptionString("bitrix24", "logo24show", "Y");
		else
			COption::SetOptionString("bitrix24", "logo24show", "N");
	}

	if (strlen($_POST["rating_text_like_y"])>0)
		COption::SetOptionString("main", "rating_text_like_y", htmlspecialcharsbx($_POST["rating_text_like_y"]));
	/*if (strlen($_POST["rating_text_like_n"])>0)
		COption::SetOptionString("main", "rating_text_like_n", htmlspecialcharsbx($_POST["rating_text_like_n"]));*/

//date/time format, week start
	if (
		strlen($_POST["date_format"])>0
		|| strlen($_POST["time_format"]) > 0
		|| strlen($_POST["WEEK_START"]) > 0
	)
	{
		$arFields = array();
		if (in_array($_POST["date_format"], $arResult["DATE_FORMATS"]))
		{
			$arFields["FORMAT_DATE"] = $_POST["date_format"];

			if (in_array($_POST["time_format"], $arResult["TIME_FORMATS"]))
			{
				$arFields["FORMAT_DATETIME"] = $_POST["date_format"]." ".$_POST["time_format"];
			}
		}

		if (isset($_POST["WEEK_START"]))
		{
			$arFields["WEEK_START"] = $_POST["WEEK_START"];
		}
		if (isset($_POST["FORMAT_NAME"]))
		{
			if (!preg_match('/^(?:#TITLE#|#NAME#|#LAST_NAME#|#SECOND_NAME#|#NAME_SHORT#|#LAST_NAME_SHORT#|#SECOND_NAME_SHORT#|#EMAIL#|#ID#|\s|,)+$/D', $_POST["FORMAT_NAME"]))
			{
				$arResult["ERROR"] = GetMessage("CONFIG_FORMAT_NAME_ERROR");
			}
			else
			{
				$arFields["FORMAT_NAME"] = $_POST["FORMAT_NAME"];
			}
		}

		if(!empty($arFields))
		{
			$result = CultureTable::update($arResult["CULTURE_ID"] , $arFields);

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->ClearByTag('sonet_group');
			}
		}
	}

	$SET = array();
	if (isset($_POST['work_time_start']))
		$SET["work_time_start"] = $_POST["work_time_start"];

	if (isset($_POST["work_time_end"]) && !empty($_POST["work_time_end"]))
		$SET["work_time_end"] = $_POST["work_time_end"];

	if (isset($_POST["week_holidays"]))
		$SET["week_holidays"] = implode('|',$_POST['week_holidays']);
	else
		$SET["week_holidays"] = "";

	if (isset($_POST["year_holidays"]))
		$SET["year_holidays"] = $_POST["year_holidays"];
	else
		$SET["year_holidays"] = "";

	if (!empty($SET) && CModule::IncludeModule("calendar"))
	{
		CCalendar::SetSettings($SET);
	}

	if($_POST['phone_number_default_country'] > 0)
	{
		COption::SetOptionInt('main', 'phone_number_default_country', $_POST['phone_number_default_country']);
	}

	if (isset($_POST["organization"]) && in_array($_POST["organization"], array("", "public_organization", "gov_organization")))
	{
		COption::SetOptionString("intranet", "organization_type", $_POST["organization"]);
		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag('intranet_ustat');
		}
	}

	if (strlen($_POST["default_viewer_service"])>0 && in_array($_POST["default_viewer_service"], array_keys($arResult["DISK_VIEWER_SERVICE"])))
		COption::SetOptionString("disk", "default_viewer_service", $_POST["default_viewer_service"]);

	if ($arResult["IS_DISK_CONVERTED"])
	{
		if (strlen($_POST["disk_allow_edit_object_in_uf"]) > 0)
			COption::SetOptionString("disk", "disk_allow_edit_object_in_uf", "Y");
		else
			COption::SetOptionString("disk", "disk_allow_edit_object_in_uf", "N");

		if (strlen($_POST["disk_allow_autoconnect_shared_objects"]) > 0)
			COption::SetOptionString("disk", "disk_allow_autoconnect_shared_objects", "Y");
		else
			COption::SetOptionString("disk", "disk_allow_autoconnect_shared_objects", "N");
	}
	else
	{
		if (strlen($_POST["webdav_global"])>0)
			COption::SetOptionString("webdav", "webdav_allow_ext_doc_services_global", "Y");
		else
			COption::SetOptionString("webdav", "webdav_allow_ext_doc_services_global", "N");

		if (strlen($_POST["webdav_local"])>0)
			COption::SetOptionString("webdav", "webdav_allow_ext_doc_services_local", "Y");
		else
			COption::SetOptionString("webdav", "webdav_allow_ext_doc_services_local", "N");

		if (strlen($_POST["webdav_autoconnect_share_group_folder"]) > 0)
			COption::SetOptionString("webdav", "webdav_allow_autoconnect_share_group_folder", "Y");
		else
			COption::SetOptionString("webdav", "webdav_allow_autoconnect_share_group_folder", "N");
	}

	if (!$arResult["IS_BITRIX24"] || $arResult["IS_BITRIX24"] && \Bitrix\Bitrix24\Feature::isFeatureEnabled("disk_version_limit_per_file"))
	{
		if (
			strlen($_POST["disk_version_limit_per_file"]) > 0
			&& in_array($_POST["disk_version_limit_per_file"], array_keys($arResult["DISK_LIMIT_PER_FILE"]))
		)
		{
			COption::SetOptionString("disk", "disk_version_limit_per_file", $_POST["disk_version_limit_per_file"]);
		}
	}

	if (!$arResult["IS_BITRIX24"] || $arResult["IS_BITRIX24"] && \Bitrix\Bitrix24\Feature::isFeatureEnabled("disk_switch_external_link"))
	{
		if (strlen($_POST["disk_allow_use_external_link"]) > 0)
			COption::SetOptionString("disk", "disk_allow_use_external_link", "Y");
		else
			COption::SetOptionString("disk", "disk_allow_use_external_link", "N");
	}

	if (!$arResult["IS_BITRIX24"] || $arResult["IS_BITRIX24"] && \Bitrix\Bitrix24\Feature::isFeatureEnabled("disk_object_lock_enabled"))
	{
		if (strlen($_POST["disk_object_lock_enabled"]) > 0)
			COption::SetOptionString("disk", "disk_object_lock_enabled", "Y");
		else
			COption::SetOptionString("disk", "disk_object_lock_enabled", "N");
	}

	if (
		!$arResult["IS_BITRIX24"]
		|| $arResult["IS_BITRIX24"] && strlen($_POST["disk_allow_use_extended_fulltext"]) <= 0
	)
	{
		if (strlen($_POST["disk_allow_use_extended_fulltext"]) > 0)
			COption::SetOptionString("disk", "disk_allow_use_extended_fulltext", "Y");
		else
			COption::SetOptionString("disk", "disk_allow_use_extended_fulltext", "N");
	}

	if (strlen($_POST["allow_livefeed_toall"]) > 0)
		COption::SetOptionString("socialnetwork", "allow_livefeed_toall", "Y");
	else
		COption::SetOptionString("socialnetwork", "allow_livefeed_toall", "N");

	if (strlen($_POST["default_livefeed_toall"]) > 0)
		COption::SetOptionString("socialnetwork", "default_livefeed_toall", "Y");
	else
		COption::SetOptionString("socialnetwork", "default_livefeed_toall", "N");

	if (
		is_array($_POST["livefeed_toall_rights"])
		&& count($_POST["livefeed_toall_rights"]) > 0
	)
	{
		$valuesToSave = [];
		foreach($_POST["livefeed_toall_rights"] as $key => $value)
		{
			$valuesToSave[] = ($value == 'UA' ? 'AU' : $value);
		}

		$val = serialize($valuesToSave);
	}
	else
	{
		$val = serialize(array("AU"));
	}

	COption::SetOptionString("socialnetwork", "livefeed_toall_rights", $val);

	if ($arResult["IS_BITRIX24"])
	{
		if (strlen($_POST["allow_new_user_lf"])>0)
			COption::SetOptionString("intranet", "BLOCK_NEW_USER_LF_SITE", "N", false, SITE_ID);
		else
			COption::SetOptionString("intranet", "BLOCK_NEW_USER_LF_SITE", "Y", false, SITE_ID);

		if (strlen($_POST["show_year_for_female"])>0)
			COption::SetOptionString("intranet", "show_year_for_female", "Y", false);
		else
			COption::SetOptionString("intranet", "show_year_for_female", "N", false);

		if (strlen($_POST["buy_tariff_by_all"])>0)
			COption::SetOptionString("bitrix24", "buy_tariff_by_all", "Y", false);
		else
			COption::SetOptionString("bitrix24", "buy_tariff_by_all", "N", false);
	}

	if (
		!$arResult["IS_BITRIX24"]
		|| \Bitrix\Bitrix24\Release::isAvailable('stresslevel')
	)
	{
		if (strlen($_POST["stresslevel_available"])>0)
			COption::SetOptionString("intranet", "stresslevel_available", "Y", false);
		else
			COption::SetOptionString("intranet", "stresslevel_available", "N", false);
	}

	// tasks
	if (strlen($_POST["create_overdue_chats"]) > 0)
		COption::SetOptionString("tasks", "create_overdue_chats", "Y", false);
	else
		COption::SetOptionString("tasks", "create_overdue_chats", "N", false);

	\Bitrix\Main\Config\Option::set(
		'main',
		'collect_geo_data',
		(empty($_POST['collect_geo_data']) ? 'N' : 'Y')
	);

	\Bitrix\Main\Config\Option::set(
		'main',
		'track_outgoing_emails_read',
		(empty($_POST['track_outgoing_emails_read']) ? 'N' : 'Y')
	);

	\Bitrix\Main\Config\Option::set(
		'main',
		'track_outgoing_emails_click',
		(empty($_POST['track_outgoing_emails_click']) ? 'N' : 'Y')
	);

//im chat
	if (CModule::IncludeModule('im'))
	{
		if (isset($_POST["allow_general_chat_toall"]))
		{
			$valuesToSave = [];
			if (is_array($_POST["imchat_toall_rights"]))
			{
				foreach($_POST["imchat_toall_rights"] as $key => $value)
				{
					$valuesToSave[] = ($value == 'UA' ? 'AU' : $value);
				}
			}

			if (in_array('AU', $valuesToSave) || empty($valuesToSave))
			{
				CIMChat::SetAccessToGeneralChat(true);
			}
			else
			{
				CIMChat::SetAccessToGeneralChat(false, $valuesToSave);
			}
		}
		else
		{
			CIMChat::SetAccessToGeneralChat(false);
		}
	}

	if (strlen($_POST["general_chat_message_join"])>0)
		COption::SetOptionString("im", "general_chat_message_join", true);
	else
		COption::SetOptionString("im", "general_chat_message_join", false);

	if (strlen($_POST["general_chat_message_leave"])>0)
		COption::SetOptionString("im", "general_chat_message_leave", true);
	else
		COption::SetOptionString("im", "general_chat_message_leave", false);

	if (strlen($_POST["url_preview_enable"])>0)
		COption::SetOptionString("main", "url_preview_enable", "Y");
	else
		COption::SetOptionString("main", "url_preview_enable", "N");

//security
	if (Bitrix\Main\Loader::includeModule("security"))
	{
		$otpGetParam = false;
		if (isset($_POST["security_otp_days"]))
		{
			$numDays = intval($_POST["security_otp_days"]);
			if ($numDays)
				Bitrix\Security\Mfa\Otp::setSkipMandatoryDays($numDays);
		}
		if (isset($_POST["security_otp"]) && CModule::IncludeModule("security"))
		{
			$currentMandatory = CSecurityUser::IsOtpMandatory();
			if (!$currentMandatory)
			{
				$otpGetParam = true;
			}
		}
		Bitrix\Security\Mfa\Otp::setMandatoryUsing(isset($_POST["security_otp"]) ? true : false);

		if ($_POST["send_otp_push"] <> '')
			COption::SetOptionString("intranet", "send_otp_push", "Y");
		else
			COption::SetOptionString("intranet", "send_otp_push", "N");
	}

	if ($arResult["IS_BITRIX24"])
	{
		if (strlen($_POST["general_chat_message_admin_rights"])>0)
			COption::SetOptionString("im", "general_chat_message_admin_rights", true);
		else
			COption::SetOptionString("im", "general_chat_message_admin_rights", false);

		$manualModulesChangedList = [];
		//features
		if (isset($_POST["feature_crm"]) && !IsModuleInstalled("crm"))
		{
			COption::SetOptionString("bitrix24", "feature_crm", "Y");
			\Bitrix\Main\ModuleManager::add("crm");
			$manualModulesChangedList['crm'] = 'Y';
		}
		elseif (!isset($_POST["feature_crm"]) && IsModuleInstalled("crm"))
		{
			COption::SetOptionString("bitrix24", "feature_crm", "N");
			\Bitrix\Main\ModuleManager::delete("crm");
			$manualModulesChangedList['crm'] = 'N';
		}

		if (isset($_POST["feature_extranet"]) && !IsModuleInstalled("extranet"))
		{
			COption::SetOptionString("bitrix24", "feature_extranet", "Y");
			CBitrix24::updateExtranetUsersActivity(true);
			\Bitrix\Main\ModuleManager::add("extranet");
			$manualModulesChangedList['extranet'] = 'Y';
		}
		elseif (!isset($_POST["feature_extranet"]) && IsModuleInstalled("extranet"))
		{
			COption::SetOptionString("bitrix24", "feature_extranet", "N");
			CBitrix24::updateExtranetUsersActivity(false);
			\Bitrix\Main\ModuleManager::delete("extranet");
			$manualModulesChangedList['extranet'] = 'N';
		}

		if (\Bitrix\Bitrix24\Feature::isFeatureEnabled("timeman"))
		{
			if (isset($_POST["feature_timeman"]) && !IsModuleInstalled("timeman"))
			{
				COption::SetOptionString("bitrix24", "feature_timeman", "Y");
				\Bitrix\Main\ModuleManager::add("timeman");
				$manualModulesChangedList["timeman"] = 'Y';
			}
			elseif (!isset($_POST["feature_timeman"]) && IsModuleInstalled("timeman"))
			{
				COption::SetOptionString("bitrix24", "feature_timeman", "N");
				\Bitrix\Main\ModuleManager::delete("timeman");
				$manualModulesChangedList["timeman"] = 'N';
			}
		}

		if (\Bitrix\Bitrix24\Feature::isFeatureEnabled("meeting"))
		{
			if (isset($_POST["feature_meeting"]) && !IsModuleInstalled("meeting"))
			{
				COption::SetOptionString("bitrix24", "feature_meeting", "Y");
				\Bitrix\Main\ModuleManager::add("meeting");
				$manualModulesChangedList["meeting"] = 'Y';
			}
			elseif (!isset($_POST["feature_meeting"]) && IsModuleInstalled("meeting"))
			{
				COption::SetOptionString("bitrix24", "feature_meeting", "N");
				\Bitrix\Main\ModuleManager::delete("meeting");
				$manualModulesChangedList["meeting"] = 'N';
			}
		}

		if (\Bitrix\Bitrix24\Feature::isFeatureEnabled("lists"))
		{
			if (isset($_POST["feature_lists"]) && !IsModuleInstalled("lists"))
			{
				COption::SetOptionString("bitrix24", "feature_lists", "Y");
				\Bitrix\Main\ModuleManager::add("lists");
				$manualModulesChangedList["lists"] = 'Y';
			}
			elseif (!isset($_POST["feature_lists"]) && IsModuleInstalled("lists"))
			{
				COption::SetOptionString("bitrix24", "feature_lists", "N");
				\Bitrix\Main\ModuleManager::delete("lists");
				$manualModulesChangedList["lists"] = 'N';
			}
		}

		if (!empty($manualModulesChangedList))
		{
			$event = new Bitrix\Main\Event("bitrix24", "OnManualModuleAddDelete", array(
				'modulesList' => $manualModulesChangedList
			));
			$event->send();
		}

		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag('sonet_group');
		}

		//ip
		if (\Bitrix\Bitrix24\Feature::isFeatureEnabled("ip_access_rights"))
		{
			$arIpSettings = array();
			foreach ($_POST as $key => $arItem)
			{
				if (strpos($key, "ip_access_rights_") !== false)
				{
					$right = str_replace("ip_access_rights_", "", $key);
					if (is_array($arItem))
					{
						foreach ($arItem as $ip)
						{
							$ip = trim($ip);
							if ($ip)
							{
								if (strpos($ip, "-") !== false)
								{
									$ipRange = explode("-", $ip);
									preg_match('#^(?:\d{1,3}\.){3}\d{1,3}$#', $ipRange[0], $matches1);
									preg_match('#^(?:\d{1,3}\.){3}\d{1,3}$#', $ipRange[1], $matches2);
									if ($matches1[0] && $matches2[0])
										$arIpSettings[$right][] = $ip;
									else
										$arResult["ERROR"] = GetMessage("CONFIG_IP_ERROR");
								}
								else
								{
									preg_match('#^(?:\d{1,3}\.){3}\d{1,3}$#', $ip, $matches);
									if ($matches[0])
										$arIpSettings[$right][] = $ip;
									else
										$arResult["ERROR"] = GetMessage("CONFIG_IP_ERROR");
								}
							}
						}
					}
				}
			}
		}
	}

	if (isset($_POST["show_fired_employees"]))
	{
		COption::SetOptionString("bitrix24", "show_fired_employees", "Y");
	}
	else
	{
		COption::SetOptionString("bitrix24", "show_fired_employees", "N");
	}

	if(\Bitrix\Main\Loader::includeModule('rest'))
	{
		if($arResult['MP_ALLOW_USER_INSTALL_EXTENDED'])
		{
			if($_REQUEST['mp_allow_user_install'] === 'Y')
			{
				$valuesToSave = [];
				if (is_array($_REQUEST['mp_user_install_rights']))
				{
					foreach($_REQUEST['mp_user_install_rights'] as $key => $value)
					{
						$valuesToSave[] = ($value == 'UA' ? 'AU' : $value);
					}
				}

				\CRestUtil::setInstallAccessList($valuesToSave);
			}
			else
			{
				\CRestUtil::setInstallAccessList(array());
			}
		}
		elseif($_REQUEST['mp_allow_user_install_changed'] === 'Y')
		{
			\CRestUtil::setInstallAccessList($_REQUEST['mp_allow_user_install'] === 'Y' ? array('AU') : array());
		}
	}


	if($arResult['SHOW_GOOGLE_API_KEY_FIELD'])
	{
		if($arResult['IS_BITRIX24'])
		{
			\Bitrix\Main\Config\Option::set('bitrix24', 'google_map_api_key', $_POST['google_api_key']);
			\Bitrix\Main\Config\Option::set('bitrix24', 'google_map_api_key_host', BX24_HOST_NAME);
		}
		else
		{
			\Bitrix\Main\Config\Option::set('fileman', 'google_map_api_key', $_POST['google_api_key']);
		}
	}

	if(isset($_REQUEST['address_format_code']) && $arResult['SHOW_ADDRESS_FORMAT'])
	{
		\Bitrix\Location\Infrastructure\FormatCode::setCurrent($_REQUEST['address_format_code']);
	}

	//gdpr
	if ($arResult["IS_BITRIX24"])
	{
		COption::SetOptionString("bitrix24", "gdpr_email_info", isset($_POST["gdpr_email_info"]) ? "Y" : "N");
		COption::SetOptionString("bitrix24", "gdpr_email_training", isset($_POST["gdpr_email_training"]) ? "Y" : "N");

		if (!in_array($arResult["LICENSE_PREFIX"], array("ru", "ua", "kz", "by")))
		{
			$gdprLegalName = trim($_POST["gdpr_legal_name"]);
			$gdprContactName = trim($_POST["gdpr_contact_name"]);
			$gdprTitle = trim($_POST["gdpr_title"]);
			$gdprDate = trim($_POST["gdpr_date"]);
			$gdprNotificationEmail = trim($_POST["gdpr_notification_email"]);

			if (isset($_POST["gdpr_data_processing"]))
			{
				if (
					empty($gdprLegalName)
					|| empty($gdprContactName)
					|| empty($gdprTitle)
					|| empty($gdprDate)
					|| empty($gdprNotificationEmail)
				)
				{
					$arResult["ERROR"] = GetMessage("CONFIG_GDRP_EMPTY_ERROR");
				}
				else
				{
					if (!check_email($gdprNotificationEmail))
					{
						$arResult["ERROR"] = GetMessage("CONFIG_GDRP_EMAIL_ERROR");
					}
					else
					{
						COption::SetOptionString("bitrix24", "gdpr_data_processing", "Y");
						COption::SetOptionString("bitrix24", "gdpr_legal_name", $gdprLegalName);
						COption::SetOptionString("bitrix24", "gdpr_contact_name", $gdprContactName);
						COption::SetOptionString("bitrix24", "gdpr_title", $gdprTitle);
						COption::SetOptionString("bitrix24", "gdpr_date", $gdprDate);
						COption::SetOptionString("bitrix24", "gdpr_notification_email", $gdprNotificationEmail);
					}
				}
			}
			else
			{
				COption::SetOptionString("bitrix24", "gdpr_data_processing", "N");
				COption::SetOptionString("bitrix24", "gdpr_legal_name", $gdprLegalName);
				COption::SetOptionString("bitrix24", "gdpr_contact_name", $gdprContactName);
				COption::SetOptionString("bitrix24", "gdpr_title", $gdprTitle);
				COption::SetOptionString("bitrix24", "gdpr_date", $gdprDate);
				COption::SetOptionString("bitrix24", "gdpr_notification_email", $gdprNotificationEmail);
			}
		}
	}

	if (!$arResult["ERROR"])
	{
		if ($arResult["IS_BITRIX24"] && \Bitrix\Bitrix24\Feature::isFeatureEnabled("ip_access_rights"))
		{
			if (empty($arIpSettings))
			{
				Bitrix\Bitrix24\OptionTable::delete("ip_access_rights");
			}
			else
			{
				$arIpSettingsSer = serialize($arIpSettings);
				if (!empty($arResult["IP_RIGHTS"]))
				{
					Bitrix\Bitrix24\OptionTable::update("ip_access_rights", array("VALUE" => $arIpSettingsSer));
				}
				else
				{
					Bitrix\Bitrix24\OptionTable::add(
						array(
							"NAME" => "ip_access_rights",
							"VALUE" => $arIpSettingsSer
						)
					);
				}
			}
			$arResult["IP_RIGHTS"] = $arIpSettings;
		}

		if ($otpGetParam)
			$url = $APPLICATION->GetCurPageParam("success=Y&otp=Y");
		else
			$url = $APPLICATION->GetCurPageParam("success=Y");
		LocalRedirect($url);
	}
}

$arResult["SECURITY_MODULE"] = false;
if (Bitrix\Main\Loader::includeModule("security"))
{
	$arResult["SECURITY_MODULE"] = true;
	$arResult["SECURITY_IS_USER_OTP_ACTIVE"] = CSecurityUser::IsUserOtpActive($USER->GetID());
	$arResult["SECURITY_OTP"] = Bitrix\Security\Mfa\Otp::isMandatoryUsing();
	$arResult["SECURITY_OTP_DAYS"] = Bitrix\Security\Mfa\Otp::getSkipMandatoryDays();
}

$arResult["IM_MODULE"] = Bitrix\Main\ModuleManager::isModuleInstalled("im");

if ($arResult["IS_BITRIX24"])
{
	$arResult['CREATOR_CONFIRMED'] = \CBitrix24::isEmailConfirmed();
	$arResult['ALLOW_DOMAIN_CHANGE'] = !\CBitrix24::isDomainChanged();

	if($arResult['ALLOW_DOMAIN_CHANGE'])
	{
		\CJSCore::Init(array('b24_rename'));
	}

	$arResult["ALLOW_SELF_REGISTER"] = "N";
	if(\Bitrix\Main\Loader::includeModule("socialservices"))
	{
		$registerSettings = \Bitrix\Socialservices\Network::getRegisterSettings();
		$arResult["ALLOW_SELF_REGISTER"] = $registerSettings["REGISTER"] == "Y" ? "Y" : "N";
	}

	$arResult["ALLOW_INVITE_USERS"] = COption::GetOptionString("bitrix24", "allow_invite_users", "N");
	$arResult["ALLOW_NEW_USER_LF"] = (
	COption::GetOptionString("intranet", "BLOCK_NEW_USER_LF_SITE", "N", SITE_ID) == 'Y'
		? 'N'
		: 'Y'
	);

	$arResult['ALLOW_NETWORK_CHANGE'] = \CBitrix24::IsNetworkAllowed() ? 'Y' : 'N';
	$arResult['SHOW_YEAR_FOR_FEMALE'] = COption::GetOptionString("intranet", "show_year_for_female", "N");

	$arResult["NETWORK_AVAILABLE"] = 'N';
	if ($arResult['CREATOR_CONFIRMED'] && CModule::IncludeModule('socialservices'))
	{
		$socnetObj = new \Bitrix\Socialservices\Network();
		$arResult["NETWORK_AVAILABLE"] = $socnetObj->isOptionEnabled() ? "Y" : "N";
	}

	$billingCurrency = CBitrix24::BillingCurrency();
	$arProductPrices = CBitrix24::getPrices($billingCurrency);
	$arResult["PROJECT_PRICE"] = CBitrix24::ConvertCurrency($arProductPrices["TF1"]["PRICE"], $billingCurrency);
}

$arResult['STRESSLEVEL_AVAILABLE'] = COption::GetOptionString("intranet", "stresslevel_available", "Y");

if($arResult['SHOW_GOOGLE_API_KEY_FIELD'])
{
	$arResult['GOOGLE_API_KEY'] = \Bitrix\Fileman\UserField\Address::getApiKey();

	if($arResult['IS_BITRIX24'])
	{
		$arResult['GOOGLE_API_KEY_HOST'] = \Bitrix\Main\Config\Option::get('bitrix24', 'google_map_api_key_host');
	}
}

if($arResult['SHOW_ADDRESS_FORMAT'])
{
	$arResult['LOCATION_ADDRESS_FORMAT_CODE'] = \Bitrix\Location\Infrastructure\FormatCode::getCurrent();
	$arResult['LOCATION_ADDRESS_FORMAT_LIST'] = [];
	$arResult['LOCATION_ADDRESS_FORMAT_DESCRIPTION_LIST'] = [];
	$arResult['LOCATION_ADDRESS_FORMAT_DESCRIPTION'] = [];
	$sanitizer = new CBXSanitizer();

	foreach(\Bitrix\Location\Service\FormatService::getInstance()->findAll(LANGUAGE_ID) as $format)
	{
		$arResult['LOCATION_ADDRESS_FORMAT_LIST'][$format->getCode()] = $format->getName();
		$arResult['LOCATION_ADDRESS_FORMAT_DESCRIPTION_LIST'][$format->getCode()] = $format->getDescription();

		if($format->getCode() === $arResult['LOCATION_ADDRESS_FORMAT_CODE'])
		{
			$arResult['LOCATION_ADDRESS_FORMAT_DESCRIPTION'] = $sanitizer->SanitizeHtml($format->getDescription());
		}
	}
}

$defaultRightsSerialized = 'a:1:{i:0;s:2:"AU";}';
$val = COption::GetOptionString("socialnetwork", "livefeed_toall_rights", $defaultRightsSerialized);
$arToAllRights = unserialize($val);
if (!$arToAllRights)
{
	$arToAllRights = unserialize($defaultRightsSerialized);
}
$arResult['arToAllRights'] = \IntranetConfigsComponent::processOldAccessCodes($arToAllRights);

$arChatToAllRights = [];
$imAllowRights = COption::GetOptionString("im", "allow_send_to_general_chat_rights");
if (!empty($imAllowRights))
{
	$arChatToAllRights = explode(",", $imAllowRights);
}
$arResult['arChatToAllRights'] = \IntranetConfigsComponent::processOldAccessCodes($arChatToAllRights);


if(\Bitrix\Main\Loader::includeModule('rest'))
{
	$arResult['MP_ALLOW_USER_INSTALL'] = \CRestUtil::getInstallAccessList();
	$arResult['MP_ALLOW_USER_INSTALL'] = \IntranetConfigsComponent::processOldAccessCodes($arResult['MP_ALLOW_USER_INSTALL']);
}

$this->IncludeComponentTemplate();
?>
