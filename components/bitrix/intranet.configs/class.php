<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\CultureTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Bitrix24\Feature;
use Bitrix\Main\ModuleManager;
use Bitrix\Location\Repository\SourceRepository;
use Bitrix\Location;
use Bitrix\Main\Config\Option;
use Bitrix\Intranet\Integration\Main\Culture;
use Bitrix\Intranet;

final class IntranetConfigsComponent extends CBitrixComponent
{
	public function __construct($component = null)
	{
		parent::__construct($component);
	}

	public static function processOldAccessCodes($rightsList)
	{
		static $rootDepartmentId = null;

		if (!is_array($rightsList))
		{
			return [];
		}

		if ($rootDepartmentId === null)
		{
			$rootDepartmentId = COption::GetOptionString("main", "wizard_departament", false, SITE_DIR, true);
			if (empty($rootDepartmentId))
			{
				$depapartmentRepository = Intranet\Service\ServiceContainer::getInstance()
					->departmentRepository();
				$rootDepartment = $depapartmentRepository->getRootDepartment();
				if ($rootDepartment)
				{
					$rootDepartmentId = $rootDepartment->getId();
				}
			}
		}

		foreach($rightsList as $key => $value)
		{
			if ($value == 'AU')
			{
				unset($rightsList[$key]);
				$rightsList[] = 'UA';
			}
			elseif (preg_match('/^IU(\d+)$/i', $value, $matches))
			{
				unset($rightsList[$key]);
				$rightsList[] = 'U'.$matches[1];
			}
			elseif (
				!empty($rootDepartmentId)
				&& ($value == 'DR'.$rootDepartmentId)
			)
			{
				unset($rightsList[$key]);
				$rightsList[] = 'UA';
			}
		}

		return array_unique($rightsList);
	}

	private function saveFile($arFile, $arRestriction = Array(), $mode = "")
	{
		$mode == "retina" ? "_retina" : "";
		$oldFileID = COption::GetOptionInt("bitrix24", "client_logo".$mode, "");

		$arFile = $arFile + Array(
				"del" => ($oldFileID ? "Y" : ""),
				"old_file" => (intval($oldFileID) > 0 ? intval($oldFileID): 0 ),
				"MODULE_ID" => "bitrix24",
			);

		$max_file_size = (array_key_exists("max_file_size", $arRestriction) ? intval($arRestriction["max_file_size"]) : 0);
		$max_width = (array_key_exists("max_width", $arRestriction) ? intval($arRestriction["max_width"]) : 0);
		$max_height = (array_key_exists("max_height", $arRestriction) ? intval($arRestriction["max_height"]) : 0);
		$extensions = (array_key_exists("extensions", $arRestriction) && $arRestriction["extensions"] <> '' ? trim($arRestriction["extensions"]) : false);

		$error = CFile::CheckFile($arFile, /*$max_file_size*/0, false, $extensions);
		if ($error <> '')
		{
			return $error;
		}

		if ($max_width > 0 || $max_height > 0)
		{
			$error = CFile::CheckImageFile($arFile, /*$max_file_size*/0, $max_width, $max_height);
			if ($error <> '')
			{
				return $error;
			}
		}

		$arFile["name"] = "logo_".randString(8).".png";
		$fileID = (int)CFile::SaveFile($arFile, "bitrix24");

		return $fileID;
	}

	private function checkLogoData()
	{
		global $APPLICATION;

		if (isset($_FILES["client_logo"]))
		{
			$error = "";
			$APPLICATION->RestartBuffer();
			$arFile = $_FILES["client_logo"];

			$APPLICATION->RestartBuffer();
			if ($arFile["name"])
			{
				$fileID = $this->saveFile($arFile, array("extensions" => "png", "max_height" => 55, "max_width" => 222));
				if (intval($fileID))
				{
					if ($this->arResult["IS_BITRIX24"])
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
				$fileID = $this->saveFile($arFile, array("extensions" => "png", "max_height" => 110, "max_width" => 444), "retina");
				if (intval($fileID))
				{
					if ($this->arResult["IS_BITRIX24"])
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
			if ($this->arResult["IS_BITRIX24"])
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

	private function saveOtpSettings()
	{
		if (!Loader::includeModule('security'))
		{
			return;
		}

		if (Loader::includeModule('bitrix24'))
		{
			//otp is always mandatory for integrator group in cloud

			$otpRights = \Bitrix\Security\Mfa\Otp::getMandatoryRights();
			$employeeGroup = 'G' . \CBitrix24::getEmployeeGroupId();
			$adminGroup = 'G1';

			if (isset($_POST['security_otp']))
			{
				if (!in_array($adminGroup, $otpRights))
				{
					$otpRights[] = $adminGroup;
					$this->arResult['SHOW_OTP_INFO_POPUP'] = true;
				}

				if (!in_array($employeeGroup, $otpRights))
				{
					$otpRights[] = $employeeGroup;
				}
			}
			else
			{
				foreach ($otpRights as $key => $group)
				{
					if ($group === $adminGroup || $group === $employeeGroup)
					{
						unset($otpRights[$key]);
					}
				}
			}

			\Bitrix\Security\Mfa\Otp::setMandatoryRights($otpRights);
		}
		else
		{
			if (isset($_POST['security_otp']) && !\CSecurityUser::IsOtpMandatory())
			{
				$this->arResult['SHOW_OTP_INFO_POPUP'] = true;
			}

			\Bitrix\Security\Mfa\Otp::setMandatoryUsing(isset($_POST['security_otp']) ? true : false);
		}

		if (isset($_POST['security_otp_days']))
		{
			$numDays = intval($_POST['security_otp_days']);
			if ($numDays > 0)
			{
				\Bitrix\Security\Mfa\Otp::setSkipMandatoryDays($numDays);
			}
		}

		if ($_POST['send_otp_push'] <> '')
		{
			Option::set('intranet', 'send_otp_push', 'Y');
		}
		else
		{
			Option::set('intranet', 'send_otp_push', 'N');
		}
	}

	private function saveSettings()
	{
		global $APPLICATION;

		if (defined("BX_COMP_MANAGED_CACHE"))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag("bitrix24_left_menu");
		}

		if (isset($_REQUEST["site_title"]))
		{
			Intranet\Portal::getInstance()->getSettings()->setTitle($_REQUEST["site_title"]);
		}

		if ($this->arResult["IS_BITRIX24"])
		{
			\Bitrix\Intranet\Composite\CacheProvider::deleteUserCache();

			if (isset($_REQUEST["logo_name"]) && $_REQUEST["logo_name"] <> '')
			{
				Intranet\Portal::getInstance()->getSettings()->setName($_REQUEST["logo_name"]);
				$iblockID = COption::GetOptionInt("intranet", "iblock_structure");
				$departmentRepository = Intranet\Service\ServiceContainer::getInstance()->departmentRepository();
				$rootDepartment = $departmentRepository->getRootDepartment();
				if ($rootDepartment)
				{
					//TODO: need check form the "humanresource" module
					if (CIBlockRights::UserHasRightTo($iblockID, $rootDepartment->getId(), 'section_edit'))
					{
						$rootDepartment->setName($_REQUEST["logo_name"]);
						$departmentRepository->save($rootDepartment);
					}
				}
			}

			if ($_POST["email_from"] <> '' && check_email($_POST["email_from"]))
			{
				COption::SetOptionString("main", "email_from", $_POST["email_from"]);
				COption::SetOptionString("forum", "FORUM_FROM_EMAIL", $_POST["email_from"]);
			}
			else
				$activateError = GetMessage("CONFIG_EMAIL_ERROR");

			//self register
			if (Loader::includeModule("socialservices"))
			{
				\Bitrix\Socialservices\Network::setRegisterSettings(array(
					"REGISTER" => isset($_POST["allow_register"]) ? "Y" : "N",
				));
			}

			//allow invite users
			if (isset($_POST["allow_invite_users"]) && $_POST["allow_invite_users"] <> '')
				COption::SetOptionString("bitrix24", "allow_invite_users", "Y");
			else
				COption::SetOptionString("bitrix24", "allow_invite_users", "N");
		}

		if (
			$this->arResult["IS_BITRIX24"] && Feature::isFeatureEnabled("remove_logo24")
			|| !$this->arResult["IS_BITRIX24"]
		)
		{
			if ($_POST["logo24"] <> '')
				COption::SetOptionString("bitrix24", "logo24show", "Y");
			else
				COption::SetOptionString("bitrix24", "logo24show", "N");
		}

		if ($_POST["rating_text_like_y"] <> '')
			COption::SetOptionString("main", "rating_text_like_y", htmlspecialcharsbx($_POST["rating_text_like_y"]));
		/*if ($_POST["rating_text_like_n"] <> '')
			COption::SetOptionString("main", "rating_text_like_n", htmlspecialcharsbx($_POST["rating_text_like_n"]));*/

		//date/time format, week start
		if (isset($_POST["cultureId"]) && !empty($_POST["cultureId"]))
		{
			Culture::updateCurrentSiteCulture($_POST["cultureId"]);
		}

		$cultureFields = [];

		if (isset($_POST["time_format"]) && in_array($_POST["time_format"], [12, 24]))
		{
			$cultureFields['TIME_FORMAT_TYPE'] = $_POST["time_format"];
		}

		if (isset($_POST["WEEK_START"]))
		{
			$cultureFields["WEEK_START"] = $_POST["WEEK_START"];
		}

		if (isset($_POST["FORMAT_NAME"]))
		{
			if (!preg_match('/^(?:#TITLE#|#NAME#|#LAST_NAME#|#SECOND_NAME#|#NAME_SHORT#|#LAST_NAME_SHORT#|#SECOND_NAME_SHORT#|#EMAIL#|#ID#|\s|,)+$/D', $_POST["FORMAT_NAME"]))
			{
				$this->arResult["ERROR"] = GetMessage("CONFIG_FORMAT_NAME_ERROR");
			}
			else
			{
				$cultureFields["FORMAT_NAME"] = $_POST["FORMAT_NAME"];
			}
		}

		if(!empty($cultureFields))
		{
			Culture::updateCulture($cultureFields);

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->ClearByTag('sonet_group');
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

		if ($_POST["default_viewer_service"] <> '' && in_array($_POST["default_viewer_service"], array_keys($this->arResult["DISK_VIEWER_SERVICE"])))
			COption::SetOptionString("disk", "default_viewer_service", $_POST["default_viewer_service"]);

		if ($this->arResult["IS_DISK_CONVERTED"])
		{
			if (isset($_POST["disk_allow_edit_object_in_uf"]) && $_POST["disk_allow_edit_object_in_uf"] <> '')
				COption::SetOptionString("disk", "disk_allow_edit_object_in_uf", "Y");
			else
				COption::SetOptionString("disk", "disk_allow_edit_object_in_uf", "N");

			if (isset($_POST["disk_allow_autoconnect_shared_objects"]) && $_POST["disk_allow_autoconnect_shared_objects"] <> '')
				COption::SetOptionString("disk", "disk_allow_autoconnect_shared_objects", "Y");
			else
				COption::SetOptionString("disk", "disk_allow_autoconnect_shared_objects", "N");
		}
		else
		{
			if ($_POST["webdav_global"] <> '')
				COption::SetOptionString("webdav", "webdav_allow_ext_doc_services_global", "Y");
			else
				COption::SetOptionString("webdav", "webdav_allow_ext_doc_services_global", "N");

			if ($_POST["webdav_local"] <> '')
				COption::SetOptionString("webdav", "webdav_allow_ext_doc_services_local", "Y");
			else
				COption::SetOptionString("webdav", "webdav_allow_ext_doc_services_local", "N");

			if ($_POST["webdav_autoconnect_share_group_folder"] <> '')
				COption::SetOptionString("webdav", "webdav_allow_autoconnect_share_group_folder", "Y");
			else
				COption::SetOptionString("webdav", "webdav_allow_autoconnect_share_group_folder", "N");
		}

		if (!$this->arResult["IS_BITRIX24"] || $this->arResult["IS_BITRIX24"] && Feature::isFeatureEnabled("disk_version_limit_per_file"))
		{
			if (
				$_POST["disk_version_limit_per_file"] <> ''
				&& in_array($_POST["disk_version_limit_per_file"], array_keys($this->arResult["DISK_LIMIT_PER_FILE"]))
			)
			{
				COption::SetOptionString("disk", "disk_version_limit_per_file", $_POST["disk_version_limit_per_file"]);
			}
		}

		if (!$this->arResult["IS_BITRIX24"] || $this->arResult["IS_BITRIX24"] && Feature::isFeatureEnabled("disk_switch_external_link"))
		{
			if ($_POST["disk_allow_use_external_link"] <> '')
				COption::SetOptionString("disk", "disk_allow_use_external_link", "Y");
			else
				COption::SetOptionString("disk", "disk_allow_use_external_link", "N");
		}

		if (!$this->arResult["IS_BITRIX24"] || $this->arResult["IS_BITRIX24"] && Feature::isFeatureEnabled("disk_object_lock_enabled"))
		{
			if (isset($_POST["disk_object_lock_enabled"]) && $_POST["disk_object_lock_enabled"] <> '')
				COption::SetOptionString("disk", "disk_object_lock_enabled", "Y");
			else
				COption::SetOptionString("disk", "disk_object_lock_enabled", "N");
		}

		if (
			!$this->arResult["IS_BITRIX24"]
			|| $this->arResult["IS_BITRIX24"] && Feature::isFeatureEnabled("disk_allow_use_extended_fulltext")
		)
		{
			if (isset($_POST["disk_allow_use_extended_fulltext"]) && $_POST["disk_allow_use_extended_fulltext"] <> '')
				COption::SetOptionString("disk", "disk_allow_use_extended_fulltext", "Y");
			else
				COption::SetOptionString("disk", "disk_allow_use_extended_fulltext", "N");
		}

		if (
			Loader::includeModule('imconnector')
			&& method_exists('\Bitrix\ImConnector\Connectors\Network', 'setIsSearchEnabled')
		)
		{
			if ($_POST["allow_search_network"] <> '')
			{
				\Bitrix\ImConnector\Connectors\Network::setIsSearchEnabled(true);
			}
			else
			{
				\Bitrix\ImConnector\Connectors\Network::setIsSearchEnabled(false);
			}
		}

		if (isset($_POST["allow_livefeed_toall"]) && $_POST["allow_livefeed_toall"] <> '')
			COption::SetOptionString("socialnetwork", "allow_livefeed_toall", "Y");
		else
			COption::SetOptionString("socialnetwork", "allow_livefeed_toall", "N");

		if (isset($_POST["default_livefeed_toall"]) && $_POST["default_livefeed_toall"] <> '')
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

		if ($this->arResult["IS_BITRIX24"])
		{
			if (isset($_POST["allow_new_user_lf"]) && $_POST["allow_new_user_lf"] <> '')
				COption::SetOptionString("intranet", "BLOCK_NEW_USER_LF_SITE", "N", false, SITE_ID);
			else
				COption::SetOptionString("intranet", "BLOCK_NEW_USER_LF_SITE", "Y", false, SITE_ID);

			if (isset($_POST["show_year_for_female"]) && $_POST["show_year_for_female"] <> '')
				COption::SetOptionString("intranet", "show_year_for_female", "Y", false);
			else
				COption::SetOptionString("intranet", "show_year_for_female", "N", false);

			if (isset($_POST["buy_tariff_by_all"]) && $_POST["buy_tariff_by_all"] <> '')
				COption::SetOptionString("bitrix24", "buy_tariff_by_all", "Y", false);
			else
				COption::SetOptionString("bitrix24", "buy_tariff_by_all", "N", false);
		}

		if (
			!$this->arResult["IS_BITRIX24"]
			|| \Bitrix\Bitrix24\Release::isAvailable('stresslevel')
		)
		{
			if ($_POST["stresslevel_available"] <> '')
				COption::SetOptionString("intranet", "stresslevel_available", "Y", false);
			else
				COption::SetOptionString("intranet", "stresslevel_available", "N", false);
		}

		// tasks
		if (isset($_POST["create_overdue_chats"]) && $_POST["create_overdue_chats"] <> '')
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
			if (isset($_POST["general_chat_can_post"]))
			{
				$generalChat = \Bitrix\Im\V2\Chat\ChatFactory::getInstance()->getGeneralChat();

				if ($generalChat)
				{
					$generalChat->setCanPost($_POST["general_chat_can_post"]);
					if ($_POST["general_chat_can_post"] === \Bitrix\Im\V2\Chat::MANAGE_RIGHTS_MANAGERS)
					{
						if (isset($_POST["imchat_toall_rights"]))
						{
							$managerIds = array_map(function ($userCode) {
								$matches = [];
								if (preg_match('/^U(\d+)$/', $userCode, $matches))
								{
									return $matches[1];
								}
							}, $_POST["imchat_toall_rights"]);
							$generalChat->setManagers($managerIds);
						}
					}
					$generalChat->save();
				}
			}
		}

		if ($_POST["general_chat_message_join"] <> '')
			COption::SetOptionString("im", "general_chat_message_join", true);
		else
			COption::SetOptionString("im", "general_chat_message_join", false);

		if (isset($_POST["general_chat_message_leave"]) && $_POST["general_chat_message_leave"] <> '')
			COption::SetOptionString("im", "general_chat_message_leave", true);
		else
			COption::SetOptionString("im", "general_chat_message_leave", false);

		if (isset($_POST["url_preview_enable"]) && $_POST["url_preview_enable"] <> '')
			COption::SetOptionString("main", "url_preview_enable", "Y");
		else
			COption::SetOptionString("main", "url_preview_enable", "N");

		//security
		$this->arResult['SHOW_OTP_INFO_POPUP'] = false;
		$this->saveOtpSettings();

		if ($this->arResult["IS_BITRIX24"])
		{
			if ($_POST["general_chat_message_admin_rights"] <> '')
				COption::SetOptionString("im", "general_chat_message_admin_rights", true);
			else
				COption::SetOptionString("im", "general_chat_message_admin_rights", false);

			$manualModulesChangedList = [];
			//features
			if (isset($_POST["feature_crm"]) && !IsModuleInstalled("crm"))
			{
				COption::SetOptionString("bitrix24", "feature_crm", "Y");
				ModuleManager::add("crm");

				if (!IsModuleInstalled("crmmobile"))
				{
					ModuleManager::add("crmmobile");
				}

				$manualModulesChangedList['crm'] = 'Y';
			}
			elseif (!isset($_POST["feature_crm"]) && IsModuleInstalled("crm"))
			{
				COption::SetOptionString("bitrix24", "feature_crm", "N");

				if (IsModuleInstalled("crmmobile"))
				{
					ModuleManager::delete("crmmobile");
				}

				ModuleManager::delete("crm");
				$manualModulesChangedList['crm'] = 'N';
			}

			if (isset($_POST["feature_extranet"]) && !IsModuleInstalled("extranet"))
			{
				COption::SetOptionString("bitrix24", "feature_extranet", "Y");
				CBitrix24::updateExtranetUsersActivity(true);
				ModuleManager::add("extranet");
				$manualModulesChangedList['extranet'] = 'Y';
			}
			elseif (!isset($_POST["feature_extranet"]) && IsModuleInstalled("extranet"))
			{
				COption::SetOptionString("bitrix24", "feature_extranet", "N");
				CBitrix24::updateExtranetUsersActivity(false);
				ModuleManager::delete("extranet");
				$manualModulesChangedList['extranet'] = 'N';
			}

			if (Feature::isFeatureEnabled("timeman"))
			{
				if (isset($_POST["feature_timeman"]) && !IsModuleInstalled("timeman"))
				{
					COption::SetOptionString("bitrix24", "feature_timeman", "Y");
					ModuleManager::add("timeman");
					$manualModulesChangedList["timeman"] = 'Y';
				}
				elseif (!isset($_POST["feature_timeman"]) && IsModuleInstalled("timeman"))
				{
					COption::SetOptionString("bitrix24", "feature_timeman", "N");
					ModuleManager::delete("timeman");
					$manualModulesChangedList["timeman"] = 'N';
				}
			}

			if (Feature::isFeatureEnabled("meeting"))
			{
				if (isset($_POST["feature_meeting"]) && !IsModuleInstalled("meeting"))
				{
					COption::SetOptionString("bitrix24", "feature_meeting", "Y");
					ModuleManager::add("meeting");
					$manualModulesChangedList["meeting"] = 'Y';
				}
				elseif (!isset($_POST["feature_meeting"]) && IsModuleInstalled("meeting"))
				{
					COption::SetOptionString("bitrix24", "feature_meeting", "N");
					ModuleManager::delete("meeting");
					$manualModulesChangedList["meeting"] = 'N';
				}
			}

			if (Feature::isFeatureEnabled("lists"))
			{
				if (isset($_POST["feature_lists"]) && !IsModuleInstalled("lists"))
				{
					COption::SetOptionString("bitrix24", "feature_lists", "Y");
					ModuleManager::add("lists");
					$manualModulesChangedList["lists"] = 'Y';
				}
				elseif (!isset($_POST["feature_lists"]) && IsModuleInstalled("lists"))
				{
					COption::SetOptionString("bitrix24", "feature_lists", "N");
					ModuleManager::delete("lists");
					$manualModulesChangedList["lists"] = 'N';
				}
			}

			if (!empty($manualModulesChangedList))
			{
				$event = new Bitrix\Main\Event("bitrix24", "OnManualModuleAddDelete", array(
					'modulesList' => $manualModulesChangedList,
				));
				$event->send();
			}

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->ClearByTag('sonet_group');
			}

			//ip
			if (Feature::isFeatureEnabled("ip_access_rights"))
			{
				$arIpSettings = array();
				foreach ($_POST as $key => $arItem)
				{
					if (mb_strpos($key, "ip_access_rights_") !== false)
					{
						$right = str_replace("ip_access_rights_", "", $key);
						if (is_array($arItem))
						{
							foreach ($arItem as $ip)
							{
								$ip = trim($ip);
								if ($ip)
								{
									if (mb_strpos($ip, "-") !== false)
									{
										$ipRange = explode("-", $ip);
										preg_match('#^(?:\d{1,3}\.){3}\d{1,3}$#', $ipRange[0], $matches1);
										preg_match('#^(?:\d{1,3}\.){3}\d{1,3}$#', $ipRange[1], $matches2);
										if ($matches1[0] && $matches2[0])
											$arIpSettings[$right][] = $ip;
										else
											$this->arResult["ERROR"] = GetMessage("CONFIG_IP_ERROR");
									}
									else
									{
										preg_match('#^(?:\d{1,3}\.){3}\d{1,3}$#', $ip, $matches);
										if ($matches[0])
											$arIpSettings[$right][] = $ip;
										else
											$this->arResult["ERROR"] = GetMessage("CONFIG_IP_ERROR");
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

		if(Loader::includeModule('rest'))
		{
			if($this->arResult['MP_ALLOW_USER_INSTALL_EXTENDED'])
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

		if(isset($_REQUEST['address_format_code']) && $this->arResult['SHOW_ADDRESS_FORMAT'])
		{
			Location\Infrastructure\FormatCode::setCurrent($_REQUEST['address_format_code']);
		}

		if ($this->arResult['IS_LOCATION_MODULE_INCLUDED'])
		{
			if (isset($_REQUEST['LOCATION_SOURCE_CODE']))
			{
				Location\Infrastructure\SourceCodePicker::setSourceCode($_REQUEST['LOCATION_SOURCE_CODE']);
			}

			/** @var Bitrix\Location\Entity\Source $source */
			foreach ($this->arResult['LOCATION_SOURCES'] as $source)
			{
				$sourceCode = $source->getCode();
				$sourceConfig = $source->getConfig() ?? new Location\Entity\Source\Config();

				if (!isset($_REQUEST['LOCATION_SOURCE'][$sourceCode]))
				{
					continue;
				}
				$sourceRequest = $_REQUEST['LOCATION_SOURCE'][$sourceCode];

				/**
				 * Update source config
				 */
				$sourceConfigRequest = $_REQUEST['LOCATION_SOURCE'][$sourceCode]['CONFIG'] ?? [];
				/** @var Location\Entity\Source\ConfigItem $configItem */
				foreach ($sourceConfig as $configItem)
				{
					if (!$configItem->isVisible())
					{
						continue;
					}
					if (!isset($sourceConfigRequest[$configItem->getCode()]))
					{
						continue;
					}

					$value = null;
					if ($configItem->getType() === Location\Entity\Source\ConfigItem::STRING_TYPE)
					{
						$value = $sourceConfigRequest[$configItem->getCode()];
					}
					elseif ($configItem->getType() === Location\Entity\Source\ConfigItem::BOOL_TYPE)
					{
						$value = $sourceConfigRequest[$configItem->getCode()] === 'Y';
					}

					$configItem->setValue($value);
				}
				$source->setConfig($sourceConfig);

				/**
				 * Save updated source to database
				 */
				$this->arResult['LOCATION_SOURCE_REPOSITORY']->save($source);
			}
		}

		if(isset($_POST['yandex_map_api_key']))
		{
			\Bitrix\Main\Config\Option::set('fileman', 'yandex_map_api_key', $_POST['yandex_map_api_key']);
		}

		if($this->arResult['SHOW_GOOGLE_API_KEY_FIELD'])
		{
			if($this->arResult['IS_BITRIX24'])
			{
				\Bitrix\Main\Config\Option::set('bitrix24', 'google_map_api_key', $_POST['google_api_key']);
				\Bitrix\Main\Config\Option::set(
					'bitrix24',
					'google_map_api_key_host',
					defined('BX24_HOST_NAME')? BX24_HOST_NAME: SITE_SERVER_NAME
				);
			}
			else
			{
				\Bitrix\Main\Config\Option::set('fileman', 'google_map_api_key', $_POST['google_api_key']);
			}
		}



		//gdpr
		if ($this->arResult["IS_BITRIX24"])
		{
			if (!in_array($this->arResult["LICENSE_PREFIX"], array("ru", "ua", "kz", "by")))
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
						$this->arResult["ERROR"] = GetMessage("CONFIG_GDRP_EMPTY_ERROR");
					}
					else
					{
						if (!check_email($gdprNotificationEmail))
						{
							$this->arResult["ERROR"] = GetMessage("CONFIG_GDRP_EMAIL_ERROR");
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

		if (!$this->arResult["ERROR"])
		{
			if ($this->arResult["IS_BITRIX24"] && Feature::isFeatureEnabled("ip_access_rights"))
			{
				if (empty($arIpSettings))
				{
					Bitrix\Bitrix24\OptionTable::delete("ip_access_rights");
				}
				else
				{
					$arIpSettingsSer = serialize($arIpSettings);
					if (!empty($this->arResult["IP_RIGHTS"]))
					{
						Bitrix\Bitrix24\OptionTable::update("ip_access_rights", array("VALUE" => $arIpSettingsSer));
					}
					else
					{
						Bitrix\Bitrix24\OptionTable::add(
							array(
								"NAME" => "ip_access_rights",
								"VALUE" => $arIpSettingsSer,
							)
						);
					}
				}
				$this->arResult["IP_RIGHTS"] = $arIpSettings;
			}

			if ($this->arResult['SHOW_OTP_INFO_POPUP'])
				$url = $APPLICATION->GetCurPageParam("success=Y&otp=Y");
			else
				$url = $APPLICATION->GetCurPageParam("success=Y");
			LocalRedirect($url);
		}
	}

	private function prepareData()
	{
		global $APPLICATION, $USER;

		$this->arParams["CONFIG_PAGE"] = $APPLICATION->GetCurPageParam("", array("success", "otp"));
		$this->arParams["CONFIG_PATH_TO_POST"] = SITE_DIR."company/personal/user/".$USER->getId()."/blog/edit/new/";

		$this->arResult["ERROR"] = "";
		$this->arResult["IS_BITRIX24"] = IsModuleInstalled("bitrix24");
		$this->arResult['IS_LOCATION_MODULE_INCLUDED'] = Loader::includeModule('location');
		$this->arResult['SHOW_ADDRESS_FORMAT'] = $this->arResult['IS_LOCATION_MODULE_INCLUDED'];

		if (Loader::includeModule("bitrix24"))
		{
			$this->arResult["LICENSE_TYPE"] = CBitrix24::getLicenseType();
			$this->arResult["LICENSE_PREFIX"] = CBitrix24::getLicensePrefix();
			$this->arResult["IS_LICENSE_PAID"] = CBitrix24::IsLicensePaid();
		}

		$this->arResult['LOCATION_SOURCES'] = [];
		if ($this->arResult['IS_LOCATION_MODULE_INCLUDED'])
		{
			$this->arResult['LOCATION_SOURCE_REPOSITORY'] = new SourceRepository(new Location\Entity\Source\OrmConverter());
			$this->arResult['LOCATION_SOURCES'] = $this->arResult['LOCATION_SOURCE_REPOSITORY']->findAll();
		}

		if (Loader::includeModule("bitrix24"))
		{
			$this->arResult['SHOW_GOOGLE_API_KEY_FIELD'] = \CBitrix24::isCustomDomain();
		}
		elseif(Loader::includeModule('fileman') && class_exists('Bitrix\Fileman\UserField\Address'))
		{
			$this->arResult['SHOW_GOOGLE_API_KEY_FIELD'] = true;
		}

		$portalPrefix = '';

		if (Loader::includeModule('bitrix24'))
		{
			$portalPrefix = $this->arResult['LICENSE_PREFIX'];
		}
		elseif (Loader::includeModule('intranet'))
		{
			$portalPrefix = CIntranetUtils::getPortalZone();
		}

		$this->arResult['SHOW_YANDEX_MAP_KEY_FIELD'] = in_array($portalPrefix, ['ru', 'by', 'kz']);

		$this->arResult['CULTURES'] = Culture::getCultures();

		$currentSite = Culture::getCurrentSite();
		$this->arResult['CURRENT_CULTURE_ID'] = $currentSite['CULTURE_ID'];
		$this->arResult["CUR_DATE_FORMAT"] = $currentSite["FORMAT_DATE"];
		$this->arResult["CUR_TIME_FORMAT"] = str_replace($this->arResult["CUR_DATE_FORMAT"]." ", "", $currentSite["FORMAT_DATETIME"]);
		$this->arResult["TIME_FORMAT_TYPE"] = ($this->arResult["CUR_TIME_FORMAT"] === 'HH:MI:SS' ? 24 : 12);
		$this->arResult["WEEK_START"] = $currentSite["WEEK_START"];
		$this->arResult["CUR_NAME_FORMAT"] = $currentSite["FORMAT_NAME"];

		$this->arResult["NAME_FORMATS"] = CSite::GetNameTemplates();
		$this->arResult["ORGANIZATION_TYPE"] = COption::GetOptionString("intranet", "organization_type", "");

		if (Loader::includeModule("calendar"))
		{
			$this->arResult["WORKTIME_LIST"] = array();
			for ($i = 0; $i < 24; $i++)
			{
				$this->arResult["WORKTIME_LIST"][strval($i)] = CCalendar::FormatTime($i, 0);
				$this->arResult["WORKTIME_LIST"][strval($i).'.30'] = CCalendar::FormatTime($i, 30);
			}
			$this->arResult["CALENDAT_SET"] = CCalendar::GetSettings(array('getDefaultForEmpty' => false));
			$this->arResult["WEEK_DAYS"] = Array('MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU');
		}

		// phone number default country
		$countriesReference = GetCountryArray();
		$this->arResult['COUNTRIES'] = array();
		foreach ($countriesReference['reference_id'] as $k => $v)
		{
			$this->arResult['COUNTRIES'][$v] = $countriesReference['reference'][$k];
		}
		$phoneNumberDefaultCountry = Bitrix\Main\PhoneNumber\Parser::getDefaultCountry();
		$this->arResult['PHONE_NUMBER_DEFAULT_COUNTRY'] = GetCountryIdByCode($phoneNumberDefaultCountry);

		$this->arResult["IS_DISK_CONVERTED"] = COption::GetOptionString('disk', 'successfully_converted', false) == 'Y';

		$this->arResult["DISK_VIEWER_SERVICE"] = array();
		if (Bitrix\Main\Loader::includeModule("disk"))
		{
			$documentHandlersManager = \Bitrix\Disk\Driver::getInstance()->getDocumentHandlersManager();

			$optionList = array();
			foreach($documentHandlersManager->getHandlersForView() as $handler)
			{
				$optionList[$handler::getCode()] = $handler::getName();
			}
			unset($handler);
			$this->arResult["DISK_VIEWER_SERVICE"] = $optionList;

			$this->arResult["DISK_VIEWER_SERVICE_DEFAULT"] = \Bitrix\Disk\Configuration::getDefaultViewerServiceCode();

			$this->arResult["DISK_LIMIT_PER_FILE_SELECTED"] = COption::GetOptionInt("disk", 'disk_version_limit_per_file', 0);
			$this->arResult["DISK_LIMIT_PER_FILE"] = array(0 => GetMessage('DISK_VERSION_LIMIT_PER_FILE_UNLIMITED'), 3=> 3, 10 => 10, 25 => 25, 50 => 50, 100 => 100, 500 => 500);
		}

		if(ModuleManager::isModuleInstalled('rest'))
		{
			$this->arResult['MP_ALLOW_USER_INSTALL_EXTENDED'] = !$this->arResult['IS_BITRIX24'] || Feature::isFeatureEnabled('rest_userinstall_extended');
		}
		$this->arResult['SHOW_RENAME_POPUP'] = (isset($_GET['change_address']) && $_GET['change_address'] == 'yes');

		if ($this->arResult["IS_BITRIX24"])
		{
			$this->arResult["IP_RIGHTS"] = array();
			$dbIpRights = Bitrix\Bitrix24\OptionTable::getList(array(
				"filter" => array("=NAME" => "ip_access_rights"),
			));
			if ($arIpRights = $dbIpRights->Fetch())
			{
				$this->arResult["IP_RIGHTS"] = unserialize($arIpRights["VALUE"], ["allowed_classes" => false]);
			}
		}
	}

	private function getOtpSettings()
	{
		global $USER;

		$this->arResult['SECURITY_MODULE'] = false;

		if (!Loader::includeModule('security'))
		{
			return;
		}

		$this->arResult['SECURITY_MODULE'] = true;
		$this->arResult['SECURITY_IS_USER_OTP_ACTIVE'] = \CSecurityUser::IsUserOtpActive($USER->GetID());
		$this->arResult['SECURITY_OTP_DAYS'] = \Bitrix\Security\Mfa\Otp::getSkipMandatoryDays();
		$this->arResult['SECURITY_OTP'] = \Bitrix\Security\Mfa\Otp::isMandatoryUsing();

		if (Loader::includeModule('bitrix24') && $this->arResult['SECURITY_OTP'])
		{
			$otpRights = \Bitrix\Security\Mfa\Otp::getMandatoryRights();
			$adminGroup = 'G1';
			$employeeGroup = 'G' . \CBitrix24::getEmployeeGroupId();

			if (!in_array($adminGroup, $otpRights) || !in_array($employeeGroup, $otpRights))
			{
				$this->arResult['SECURITY_OTP'] = false;
			}
		}
	}

	private function prepareAdditionalData()
	{
		$this->getOtpSettings();

		$this->arResult["IM_MODULE"] = ModuleManager::isModuleInstalled("im");

		if ($this->arResult["IS_BITRIX24"])
		{
			$this->arResult['CREATOR_CONFIRMED'] = \CBitrix24::isEmailConfirmed();
			$this->arResult['ALLOW_DOMAIN_CHANGE'] = !\CBitrix24::isDomainChanged();

			if($this->arResult['ALLOW_DOMAIN_CHANGE'])
			{
				\CJSCore::Init(array('b24_rename'));
			}

			$this->arResult["ALLOW_SELF_REGISTER"] = "N";
			if(Loader::includeModule("socialservices"))
			{
				$registerSettings = \Bitrix\Socialservices\Network::getRegisterSettings();
				$this->arResult["ALLOW_SELF_REGISTER"] = $registerSettings["REGISTER"] == "Y" ? "Y" : "N";
			}

			$this->arResult["ALLOW_INVITE_USERS"] = COption::GetOptionString("bitrix24", "allow_invite_users", "N");
			$this->arResult["ALLOW_NEW_USER_LF"] = (
			COption::GetOptionString("intranet", "BLOCK_NEW_USER_LF_SITE", "N", SITE_ID) == 'Y'
				? 'N'
				: 'Y'
			);

			$this->arResult['ALLOW_NETWORK_CHANGE'] = \CBitrix24::IsNetworkAllowed() ? 'Y' : 'N';
			$this->arResult['SHOW_YEAR_FOR_FEMALE'] = COption::GetOptionString("intranet", "show_year_for_female", "N");

			$this->arResult["NETWORK_AVAILABLE"] = 'N';

			$billingCurrency = CBitrix24::BillingCurrency();
			$this->arResult["PROJECT_PRICE"] = '';
			if (($arProductPrices = CBitrix24::getPrices($billingCurrency)) && !empty($arProductPrices["TF1"]["PRICE"]))
			{
				$this->arResult["PROJECT_PRICE"] = CBitrix24::ConvertCurrency($arProductPrices["TF1"]["PRICE"], $billingCurrency);
			}
		}

		$this->arResult['STRESSLEVEL_AVAILABLE'] = COption::GetOptionString("intranet", "stresslevel_available", "Y");

		if($this->arResult['SHOW_YANDEX_MAP_KEY_FIELD'])
		{
			$this->arResult['YANDEX_MAP_API_KEY'] = \Bitrix\Main\Config\Option::get('fileman', 'yandex_map_api_key');
		}

		if($this->arResult['SHOW_GOOGLE_API_KEY_FIELD'])
		{
			$this->arResult['GOOGLE_API_KEY'] = \Bitrix\Fileman\UserField\Address::getApiKey();

			if($this->arResult['IS_BITRIX24'])
			{
				$this->arResult['GOOGLE_API_KEY_HOST'] = \Bitrix\Main\Config\Option::get('bitrix24', 'google_map_api_key_host');
			}
		}

		if($this->arResult['SHOW_ADDRESS_FORMAT'])
		{
			$this->arResult['LOCATION_ADDRESS_FORMAT_CODE'] = Location\Infrastructure\FormatCode::getCurrent();
			$this->arResult['LOCATION_ADDRESS_FORMAT_LIST'] = [];
			$this->arResult['LOCATION_ADDRESS_FORMAT_DESCRIPTION_LIST'] = [];
			$this->arResult['LOCATION_ADDRESS_FORMAT_DESCRIPTION'] = [];
			$sanitizer = new CBXSanitizer();

			foreach(Location\Service\FormatService::getInstance()->findAll(LANGUAGE_ID) as $format)
			{
				$this->arResult['LOCATION_ADDRESS_FORMAT_LIST'][$format->getCode()] = $format->getName();
				$this->arResult['LOCATION_ADDRESS_FORMAT_DESCRIPTION_LIST'][$format->getCode()] = $format->getDescription();

				if($format->getCode() === $this->arResult['LOCATION_ADDRESS_FORMAT_CODE'])
				{
					$this->arResult['LOCATION_ADDRESS_FORMAT_DESCRIPTION'] = $sanitizer->SanitizeHtml($format->getDescription());
				}
			}
		}

		$defaultRightsSerialized = 'a:1:{i:0;s:2:"AU";}';
		$val = COption::GetOptionString("socialnetwork", "livefeed_toall_rights", $defaultRightsSerialized);
		$arToAllRights = unserialize($val, ["allowed_classes" => false]);
		if (!$arToAllRights)
		{
			$arToAllRights = unserialize($defaultRightsSerialized, ["allowed_classes" => false]);
		}
		$this->arResult['arToAllRights'] = \IntranetConfigsComponent::processOldAccessCodes($arToAllRights);

		// im
		if (!method_exists('\Bitrix\Im\V2\Chat\GeneralChat', 'getRightsForIntranetConfig'))
		{
			$arChatToAllRights = [];
			$imAllowRights = COption::GetOptionString("im", "allow_send_to_general_chat_rights");
			if (!empty($imAllowRights))
			{
				$arChatToAllRights = explode(",", $imAllowRights);
			}
			$this->arResult['arChatToAllRights'] = \IntranetConfigsComponent::processOldAccessCodes($arChatToAllRights);
		}
		else
		{
			$generalChat = \Bitrix\Im\V2\Chat\ChatFactory::getInstance()->getGeneralChat();
			if ($generalChat)
			{
				$generalChatRights = $generalChat->getRightsForIntranetConfig();
				$this->arResult = array_merge($this->arResult, $generalChatRights);
			}
		}
		//end im

		if(Loader::includeModule('rest'))
		{
			$this->arResult['MP_ALLOW_USER_INSTALL'] = \CRestUtil::getInstallAccessList();
			$this->arResult['MP_ALLOW_USER_INSTALL'] = \IntranetConfigsComponent::processOldAccessCodes($this->arResult['MP_ALLOW_USER_INSTALL']);
		}
	}

	public function executeComponent()
	{
		if (!$this->checkRights())
		{
			$this->showErrors();
			return;
		}

		$this->prepareData();

		//logo for bitrix24
		if (
			$_SERVER["REQUEST_METHOD"] == "POST" && check_bitrix_sessid()
			&& ($this->arResult["IS_BITRIX24"] && Feature::isFeatureEnabled("set_logo") || !$this->arResult["IS_BITRIX24"])
		)
		{
			\Bitrix\Intranet\Composite\CacheProvider::deleteUserCache();

			$this->checkLogoData();
		}

		if ($_SERVER["REQUEST_METHOD"] == "POST"  && (isset($_POST["save_settings"]) )&& check_bitrix_sessid())
		{
			$this->saveSettings();
		}

		$this->prepareAdditionalData();

		$this->includeComponentTemplate();
	}

	private function checkRights()
	{
		if (
			!(
				Loader::includeModule("intranet") && $GLOBALS['USER']->IsAdmin()
				|| Loader::includeModule("bitrix24") && $GLOBALS['USER']->CanDoOperation('bitrix24_config')
			)
		)
		{
			$this->errors[] = Loc::getMessage('CONFIG_ACCESS_DENIED');
			return false;
		}

		return true;
	}

	protected function showErrors(): void
	{
		if (count($this->errors) <= 0)
		{
			return;
		}

		foreach ($this->errors as $error)
		{
			ShowError($error);
		}

		return;
	}
}