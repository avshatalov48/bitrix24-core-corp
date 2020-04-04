<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");
$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/functions.php")));
__IncludeLang($file);
if (!function_exists("WrapLongWords"))
{
	function WrapLongWords($text = "")
	{
		if (strLen($text) <= 40)
			return $text;
		$word_separator = "\s.,;:!?\#\*\|\[\]\(\)";
		$text = preg_replace_callback("/(?<=[".$word_separator."])(([^".$word_separator."]+))(?=[".$word_separator."])/is".BX_UTF_PCRE_MODIFIER,
			'__wrapLongWords', " ".$text." ");
		return trim($text);
	}

	function __wrapLongWords($m)
	{
		return Wrap($m[2]);
	}
}
if (!function_exists("Wrap"))
{
	function Wrap($str)
	{
		$str = preg_replace("/([^ \n\r\t\x01]{40})(.?+)/is".BX_UTF_PCRE_MODIFIER,"\\1<WBR />&shy;\\2", $str);
		return $str;
	}
}

if (!function_exists("WrapLongText"))
{
	function WrapLongText($text, $linelength=80, $lines=10)
	{
		$arLines = explode("\n", wordwrap(str_replace(array("\n", "\n\r", "\r\n", "\r"), " ", $text), $linelength, "\n", true));
		$ending = (sizeof($arLines) > $lines) ? "..." : "";
		return implode(" ", array_slice($arLines, 0, $lines)).$ending;
	}
}

if (!function_exists("__get_file_array"))
{
	function __get_file_array($id, &$res)
	{
		static $arFilesCache = array();
		if (!array_key_exists($id, $arFilesCache))
		{
			$db_res = CFile::GetByID($id);
			$arFilesCache[$id] = $db_res->GetNext();
			$arFilesCache[$id]["FILE_ARRAY"] = array();
			__parse_file_size($arFilesCache[$id]["FILE_ARRAY"], $arFilesCache[$id]);
		}
		if (!array_key_exists($id, $arFilesCache))
		{
			$res = $arFilesCache[$id];
			return true;
		}

		return false;
	}
}
if (!function_exists("__parse_file_size"))
{
	function __parse_file_size(&$res_file, &$res)
	{
		if (isset($res_file['FILE_SIZE']))
			$res['FILE_SIZE'] = CFile::FormatSize(intval($res_file['FILE_SIZE']), 0);
	}
}
if (!function_exists("__format_user4search"))
{
	function __format_user4search($userID=null, $nameTemplate="")
	{
		global $USER;

		if ($userID == null)
			$userID = $USER->GetID();

		if (empty($nameTemplate))
			$nameTemplate = CSite::GetNameFormat(false);

		$rUser = CUser::GetByID($userID);
		if ($rUser && $arUser =$rUser->Fetch())
		{
			$userName = CUser::FormatName($nameTemplate.' [#ID#]', $arUser);
			if (!(strlen($arUser['NAME'])>0 || strlen($arUser['LAST_NAME'])>0))
			{
				$userName .= " [".$arUser['ID']."]";
			}
		}
		else
		{
			$userName = "";
		}
		return $userName;
	}
}

if (!function_exists("__parse_user"))
{
	function __parse_user($user_id, $user_url, $nameTemplate="")
	{
		static $arUsersCache = array();
		if (empty($nameTemplate))
		{
			$nameTemplate = "#NOBR##LAST_NAME# #NAME##/NOBR#";
			static $arNameFormats = array();
			if (! isset($arNameFormats[SITE_ID]))
			{
				$arNameFormats[SITE_ID] = CSite::GetNameFormat(false);
			}
			if (isset($arNameFormats[SITE_ID]))
			{
				$nameTemplate = $arNameFormats[SITE_ID];
			}
		}

		if (intVal($user_id) > 0 && !array_key_exists($user_id, $arUsersCache))
		{
			$rsUser = CUser::GetByID($user_id);
			$arUsersCache[$user_id] = $rsUser->Fetch();
			if ($arUsersCache[$user_id] !== false)
			{
				$arUsersCache[$user_id]["ID"] = $user_id;
				$arUsersCache[$user_id]["URL"] = CComponentEngine::MakePathFromTemplate($user_url, array("USER_ID" => $user_id));
				$arUsersCache[$user_id]["FULL_NAME"] = CUser::FormatName($nameTemplate, $arUsersCache[$user_id]);
				if (empty($arUsersCache[$user_id]["FULL_NAME"]))
					$arUsersCache[$user_id]["FULL_NAME"] = $arUsersCache[$user_id]["LOGIN"];
				$arUsersCache[$user_id]["PHOTO"]=intval($arUsersCache[$user_id]["PERSONAL_PHOTO"]);
				$arUsersCache[$user_id]["LINK"] = '<a href="'.$arUsersCache[$user_id]["URL"].'">'.$arUsersCache[$user_id]["FULL_NAME"].'</a>';
				ob_start();
				$GLOBALS['APPLICATION']->IncludeComponent("bitrix:main.user.link",
					'',
					array(
						"ID" => $user_id,
						"HTML_ID" => "auth_".$user_id,
						"INLINE" => "Y",
						"NAME_TEMPLATE" => $nameTemplate,
						"USE_THUMBNAIL_LIST" => "N",
						"CACHE_TYPE" => "A",
						"CACHE_TIME" => 7200,
					),
					false,
					array("HIDE_ICONS" => "Y")
				);
				$arUsersCache[$user_id]['main_user_link'] = ob_get_clean();
				$arUsersCache[$user_id]['main_user_link'] = str_replace(array("\n","\t"), "", $arUsersCache[$user_id]['main_user_link']);
			}
		}
		if (!empty($arUsersCache[$user_id]))
		{
			$arUser = $arUsersCache[$user_id];
			if (isset($arUser['main_user_link']))  // fix duplicate ids in (static) main.user.link
			{
				$newid = RandString(8);
				$pos = strpos($arUser['main_user_link'], 'anchor_');
				while ($pos !== false)
				{
					$arUser['main_user_link'] = substr($arUser['main_user_link'], 0, $pos+7) . $newid . substr($arUser['main_user_link'], $pos+15);
					$pos = strpos($arUser['main_user_link'], 'anchor_', $pos+14);
				}
			}
			return $arUser;
		}
		return array("ID" => 0, "NAME" => "Guest", "LINK" => "Guest", "main_user_link" => "");
	}
}
if (!function_exists("__prepare_item_info"))
{
	function __prepare_item_info(&$res, $arParams = array())
	{
		if ($res["TYPE"] == "E")
		{
			if (!is_set($res, "FILE_ARRAY") && is_set($res, "FILE"))
				$res["FILE_ARRAY"] = $res["FILE"];
			__parse_file_size($res["FILE_ARRAY"], $res);
			$res["PROPERTIES"] = array();
			$tmp = array();
			foreach ($res as $key => $val)
			{
				if (substr($key, -9, 9) != "_VALUE_ID" ||
					!(substr($key, 0, 9) == "PROPERTY_" || substr($key, 0, 10) == "~PROPERTY_"))
					continue;
				$key = substr($key, 0, strlen($key) - 9);
				$tmp[$key] = $res[$key."_VALUE"];
				if (substr($key, 0, 9) == "PROPERTY_")
					$res["PROPERTIES"][substr($key, 9)] = array("VALUE" => $res[$key."_VALUE"]);
			}
			$res["FILE_EXTENTION"] = htmlspecialcharsbx(strtolower(strrchr($res['~NAME'] , '.')));
		}

		foreach (array("MODIFIED_BY", "CREATED_BY", "WF_LOCKED_BY") as $user_key)
		{
			if (is_array($res) && !array_key_exists("~".$user_key, $res))
				$res["~".$user_key] = $res[$user_key];
			$res[$user_key] = __parse_user($res[$user_key], $arParams["USER_VIEW_URL"], (isset($arParams["NAME_TEMPLATE"])?$arParams["NAME_TEMPLATE"]:null));
		}

		if ($res["TYPE"] == "S")
		{
			$res["URL"] = array(
			"SECTIONS_DIALOG" => WDAddPageParams(CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"],
				array("PATH" => $res["PATH"], "SECTION_ID" => $res["IBLOCK_SECTION_ID"], "ELEMENT_ID" => "files", "ELEMENT_NAME" => "files")), array("dialog" => "Y", "ajax_call" => "Y")),
			"~THIS" => CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"],
				array("PATH" => $res["~PATH"], "SECTION_ID" => $res["ID"], "ELEMENT_ID" => "files", "ELEMENT_NAME" => "files")),
			"THIS" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"],
				array("PATH" => $res["PATH"], "SECTION_ID" => $res["ID"], "ELEMENT_ID" => "files", "ELEMENT_NAME" => "files")));

			$res["URL"]["EDIT"] = CComponentEngine::MakePathFromTemplate($arParams["~SECTION_EDIT_URL"],
				array("PATH" => $res["PATH"], "SECTION_ID" => $res["ID"], "ACTION" => "EDIT"));
			$res["URL"]["UNDELETE"] = CComponentEngine::MakePathFromTemplate($arParams["~SECTION_EDIT_URL"],
				array("PATH" => $res["PATH"], "SECTION_ID" => $res["ID"], "ACTION" => "UNDELETE"));
			$res["URL"]["DELETE"] = CComponentEngine::MakePathFromTemplate($arParams["~SECTION_EDIT_URL"],
				array("PATH" => $res["PATH"], "SECTION_ID" => $res["ID"], "ACTION" => "DROP"));
			$res["URL"]["DELETE"] = WDAddPageParams($res["URL"]["DELETE"], array("edit_section" => "y", "sessid" => bitrix_sessid()), false);
		}
		else
		{
			$res["URL"] = array(
			"~THIS" => CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"],
				array("PATH" => $res["PATH"], "SECTION_ID" => intVal($res["IBLOCK_SECTION_ID"]), "ELEMENT_ID" => $res["ID"], "ELEMENT_NAME" => $res["~NAME"])),
			"THIS" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"],
				array("PATH" => $res["PATH"], "SECTION_ID" => intVal($res["IBLOCK_SECTION_ID"]), "ELEMENT_ID" => $res["ID"], "ELEMENT_NAME" => $res["~NAME"])),
			"~SECTION" => CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"],
				array("PATH" => $res["SECTION_PATH"], "SECTION_ID" => $res["IBLOCK_SECTION_ID"], "ELEMENT_ID" => "files", "ELEMENT_NAME" => "files")),
			"SECTION" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"],
				array("PATH" => $res["SECTION_PATH"], "SECTION_ID" => $res["IBLOCK_SECTION_ID"], "ELEMENT_ID" => "files", "ELEMENT_NAME" => "files")),
			"SECTIONS_DIALOG" => WDAddPageParams(CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"],
				array("PATH" => $res["SECTION_PATH"], "SECTION_ID" => $res["IBLOCK_SECTION_ID"], "ELEMENT_ID" => "files", "ELEMENT_NAME" => "files")), array("dialog"=>"Y"), false),
			"VIEW" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_URL"],
				array("PATH" => $res["PATH"], "SECTION_ID" => intVal($res["IBLOCK_SECTION_ID"]), "ELEMENT_ID" => $res["ID"], "ELEMENT_NAME" => $res["~NAME"])),
			"HIST" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_HISTORY_URL"],
				array("PATH" => $res["PATH"], "SECTION_ID" => intVal($res["IBLOCK_SECTION_ID"]), "ELEMENT_ID" => $res["ID"])),
			"DOWNLOAD" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_HISTORY_GET_URL"],
				array("PATH" => $res["PATH"], "ELEMENT_ID" => $res["ID"], "ID" => $res["ID"], "ELEMENT_NAME" => $res["~NAME"])),
			"VERSIONS" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_VERSIONS_URL"],
				array("ELEMENT_ID" => ($res["WF_PARENT_ELEMENT_ID"] ? $res["WF_PARENT_ELEMENT_ID"] : $res["ID"]))),
			"EDIT" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"],
				array("PATH" => $res["PATH"], "ELEMENT_ID" => $res["ID"], "ACTION" => "EDIT")),
			"UNDELETE" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"],
				array("PATH" => $res["PATH"], "ELEMENT_ID" => $res["ID"], "ACTION" => "UNDELETE")),
			"CLONE" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_VERSION_URL"],
				array("PATH" => $res["PATH"], "ELEMENT_ID" => $res["ID"], "ACTION" => "CLONE")),
			"COPY" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"],
				array("PATH" => $res["PATH"], "ELEMENT_ID" => $res["ID"], "ACTION" => "COPY")),
			"DELETE" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"],
				array("PATH" => $res["PATH"], "ELEMENT_ID" => $res["ID"], "ACTION" => "DELETE")),
			"UNLOCK" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"],
				array("PATH" => $res["PATH"], "ELEMENT_ID" => $res["ID"], "ACTION" => "UNLOCK")),
			"LOCK" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"],
				array("PATH" => $res["PATH"], "ELEMENT_ID" => $res["ID"], "ACTION" => "LOCK")),
			"BP" => WDAddPageParams(CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_URL"],
				array("PATH" => $res["PATH"], "SECTION_ID" => intVal($res["IBLOCK_SECTION_ID"]), "ELEMENT_ID" => $res["ID"], "ELEMENT_NAME" => $res["~NAME"])), array("webdavForm".$arParams["IBLOCK_ID"]."_active_tab"=>"tab_bizproc_view")),
			"BP_START" => CComponentEngine::MakePathFromTemplate($arParams["~WEBDAV_START_BIZPROC_URL"],
				array("ELEMENT_ID" => $res["ID"])),
			"BP_TASK" => CComponentEngine::MakePathFromTemplate($arParams["~WEBDAV_TASK_LIST_URL"],
				array("PATH" => $res["PATH"],"ELEMENT_ID" => $res["ID"])));
			$res["URL"]["DELETE"] = WDAddPageParams($res["URL"]["DELETE"], array("edit" => "y", "sessid" => bitrix_sessid()), false);
			$res["URL"]["UNLOCK"] = WDAddPageParams($res["URL"]["UNLOCK"], array("edit" => "y", "sessid" => bitrix_sessid()), false);
			$res["URL"]["LOCK"] = WDAddPageParams($res["URL"]["LOCK"], array("edit" => "y", "sessid" => bitrix_sessid()), false);
			$res["URL"]["BP_START"] = WDAddPageParams($res["URL"]["BP_START"],
				array("back_url" => urlencode($GLOBALS['APPLICATION']->GetCurPageParam())), false);
			if ($res["WF_PARENT_ELEMENT_ID"] > 0)
			{
				$res["URL"]["~PARENT"] = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"],
					array(
						"PATH" => $res["PATH"],
						"SECTION_ID" => intVal($res["IBLOCK_SECTION_ID"]),
						"ELEMENT_ID" => $res["WF_PARENT_ELEMENT_ID"],
						"ELEMENT_NAME" => $res["~NAME"]));
				$res["URL"]["PARENT"] = CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"],
					array(
						"PATH" => $res["PATH"],
						"SECTION_ID" => intVal($res["IBLOCK_SECTION_ID"]),
						"ELEMENT_ID" => $res["WF_PARENT_ELEMENT_ID"],
						"ELEMENT_NAME" => $res["~NAME"]));
			}
		}
		foreach ($res["URL"] as $key => $val)
		{
			$res["URL"][$key] = $val = str_replace(array("\\", "///", "//"), "/", $val);
			if (substr($key, 0, 1) == "~" || array_key_exists("~".$key, $res["URL"]))
				continue;
			$res["URL"]["~".$key] = $val;
			$res["URL"][$key] = htmlspecialcharsbx($val);
		}
	}
}

if (!function_exists("__make_hint"))
{
	function __make_hint(&$res, $prefix='')
	{
		$hint = '';
		$text = '';
		if (strlen($res["~PREVIEW_TEXT"]) > 0)
			$text = trim($res["~PREVIEW_TEXT"]);
		elseif (strlen($res["PREVIEW_TEXT"]) > 0)
			$text = trim($res["PREVIEW_TEXT"]);
		$text = HTMLToTxt($text);
		if (strlen($text) < 1) return "";
		$hint = "<b>".GetMessage("WD_DESCRIPTION").":</b><br />".WrapLongText($text);
		if (strlen($res["TAGS"]) > 0)
		{
			if (strlen($hint)>0) $hint .= '<br />';
			$hint .= "<b>".GetMessage("WD_TAGS").":</b><br />";
			$tags = explode(',', $res['TAGS']);
			foreach ($tags as $i => $tag)
				$tags[$i] = "<a href=\"javascript:void(0);\" onclick=\"WDSearchTag('".CUtil::JSEscape(urlencode(trim($tag)))."');\" class=\"tag_link\">".trim($tag)."</a>";
			$hint .= implode(', ', $tags);
		}
		if (strlen($hint)>0) $hintLink = 'onmouseover="BX.hint(this, hint'.$prefix.$res['ID'].')"';
		else $hintLink = '';
		$res['HINT'] = (strlen($hint) > 0 ? "<script>var hint".$prefix.$res['ID']."='".CUtil::JSEscape($hint)."';</script>" : "");
		return $hintLink;
	}
}

if (!function_exists("__wd_get_office_extensions"))
{
	function __wd_get_office_extensions()
	{
		return explode(' ', COption::GetOptionString("webdav", "office_extensions",
			".accda .accdb .accde .accdt .accdu .doc .docm .docx .dot .dotm ".
			".dotx .gsa .gta .mda .mdb .mny .mpc .mpp .mpv .mso .msproducer .pcs ".
			".pot .potm .potx .ppa .ppam .pps .ppsm .ppsx .ppt .pptm .pptx .pst .pub ".
			".rtf .sldx .xla .xlam .xlb .xlc .xld .xlk .xll .xlm .xls .xlsb .xlsm .xlsx ".
			".xlt .xltm .xltx .xlv .xlw .xps .xsf .odt .ods .odp .odb .odg .odf"
		));
	}
}

if (!function_exists("__build_item_info"))
{
	function __build_item_info(&$res, $arParams, $WrapLongWords = false)
	{
		global $DB, $USER;
		static $bTheFirstTimeonPage = true;
		static $bShowWebdav = true;
		static $arBPTemplates = array();
		static $arOfficeExtensions = false;
		static $checkParentSectionIsLink = array();

		$nameTemplate = "#NOBR##LAST_NAME# #NAME##/NOBR#";
		static $arNameFormats = array();
		if (! isset($arNameFormats[SITE_ID]))
		{
			$arNameFormats[SITE_ID] = CSite::GetNameFormat(false);
		}
		if (isset($arNameFormats[SITE_ID]))
		{
			$nameTemplate = $arNameFormats[SITE_ID];
		}

		if (!$arOfficeExtensions)
			$arOfficeExtensions = __wd_get_office_extensions();

		if (!isset($arParams["OBJECT"]))
			return;

		$ob = $arParams["OBJECT"];
		static $allowExtDocServices = null;
		if($allowExtDocServices === null)
		{
			$allowExtDocServicesGlobal = CWebDavTools::allowUseExtServiceGlobal();
			$allowExtDocServicesLocal = CWebDavTools::allowUseExtServiceLocal();

			$allowExtDocServices = $allowExtDocServicesGlobal;
			if($ob->arRootSection['UF_USE_EXT_SERVICES'] && $allowExtDocServicesLocal)
			{
				$allowExtDocServices = 'Y' == CWebDavIblock::resolveDefaultUseExtServices($ob->arRootSection['UF_USE_EXT_SERVICES']);
			}
		}
		static $rootDataForCurrentUser = null;
		static $isUserLib = null;
		if($rootDataForCurrentUser === null && $USER->getId())
		{
			$rootDataForCurrentUser = CWebDavIblock::getRootSectionDataForUser($USER->getId());
			$isUserLib = $ob->attributes['user_id'] == $USER->getId() && !($ob->meta_state == CWebDavIblock::DROPPED);
		}

		static $isExtranetUser = null;
		if($isExtranetUser === null)
		{
			$isExtranetUser = !$USER->getId() || !CWebDavTools::isIntranetUser($USER->getId());
		}

		$bInTrash = ("/" . $ob->meta_names["TRASH"]["alias"] == $ob->_udecode($ob->_path));

		if ($res["TYPE"] != "S" && $arBPTemplates != $arParams["TEMPLATES"])
		{
			$bShowWebdav = true;
			$arBPTemplates = $arParams["TEMPLATES"];
			if (is_array($arParams["TEMPLATES"]) && !empty($arParams["TEMPLATES"]))
			{
				foreach ($arParams["TEMPLATES"] as $key => $arTemplateState)
				{
					if (in_array($arTemplateState["AUTO_EXECUTE"], array(2, 3, 6, 7)) &&
						(is_array($arTemplateState["PARAMETERS"]) || is_array($arTemplateState["TEMPLATE_PARAMETERS"])))
					{
						$arTemplateState["TEMPLATE_PARAMETERS"] = (is_array($arTemplateState["PARAMETERS"]) ?
							$arTemplateState["PARAMETERS"] : $arTemplateState["TEMPLATE_PARAMETERS"]);
						foreach ($arTemplateState["TEMPLATE_PARAMETERS"] as $val)
						{
							if ($val["Required"] == 1 && empty($val["Default"]))
							{
								$bShowWebdav = false;
								break;
							}
						}
					}
				}
			}
		}
		$res["bShowWebDav"] = $bShowWebdav;

/************** Grid Data ******************************************/
		$arActions = array();
		if ($res["TYPE"] == "S")
		{
			$arActions["section_open"] = array(
				"ICONCLASS" => "section_open",
				"TITLE" => GetMessage("WD_OPEN_SECTION"),
				"TEXT" => GetMessage("WD_OPEN"),
				"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~THIS"])."');",
				"DEFAULT" => true);
			if ($res["SHOW"]["UNDELETE"] == "Y")
			{
				$arActions["section_undelete"] = array(
					"ICONCLASS" => "section_download",
					"TITLE" => GetMessage("WD_UNDELETE_SECTION"),
					"TEXT" => GetMessage("WD_UNDELETE"),
					"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape(WDAddPageParams($res["URL"]["~UNDELETE"], array("edit_section"=>"Y", "sessid" => bitrix_sessid()), false))."');",
					"DEFAULT" => false);
			}

			if ($res["SHOW"]["EDIT"] == "Y")
			{
				if (
					$ob->Type == "iblock"
					&& $arParams["OBJECT"]->CheckWebRights("", false, array("action" => "create"))
				) // TODO: move it to module!
				{
					//sharing with antoher user. Only user_lib files.
					global $USER;
					if(!empty($arParams["OBJECT"]->attributes['user_id']) && $arParams["OBJECT"]->attributes['user_id'] == $USER->getID() && !$isExtranetUser)
					{
						if(empty($res['LINK'])) //this section is link.
						{
							if(!empty($res['SHARED_SECTION']))
							{
								//usage. Show list user
								$uriToShareSection = $GLOBALS['APPLICATION']->GetCurUri(http_build_query(array(
									'toWDController' => 1,
									'wdaction' => 'detail_user_share',
									'shareSectionId' => $res["ID"],
								)));
								$arActions["section_share"] = array(
									"ICONCLASS" => "section_share",
									"TITLE" => GetMessage("WD_SHARE_TITLE_2"),
									"TEXT" => GetMessage("WD_SHARE_NAME_2"),
									"ONCLICK" => "WDShareFolder('{$uriToShareSection}', {$res["ID"]}, null, '" . CUtil::JSEscape($res["NAME"]) . "')",
								);
							}
							else
							{
								if(!isset($checkParentSectionIsLink[$res['IBLOCK_SECTION_ID']]))
								{
									$checkParentSectionIsLink[$res['IBLOCK_SECTION_ID']] = CWebDavSymlinkHelper::isLink(CWebDavSymlinkHelper::ENTITY_TYPE_USER, $arParams["OBJECT"]->attributes['user_id'], array(
										'ID' => $res['IBLOCK_SECTION_ID'],
										'IBLOCK_ID' => $res['IBLOCK_ID'],
									));
								}
								//if element in link - don't share
								if(!$checkParentSectionIsLink[$res['IBLOCK_SECTION_ID']])
								{
									$uriToShareSection = $GLOBALS['APPLICATION']->GetCurUri(http_build_query(array(
										'toWDController' => 1,
										'wdaction' => 'detail_user_share',
										'shareSectionId' => $res["ID"],
									)));
									$arActions["section_share"] = array(
										"ICONCLASS" => "section_share",
										"TITLE" => GetMessage("WD_SHARE_TITLE_2"),
										"TEXT" => GetMessage("WD_SHARE_NAME_2"),
										"ONCLICK" => "WDShareFolder('{$uriToShareSection}', {$res["ID"]}, null, '" . CUtil::JSEscape($res["NAME"]) . "')",
									);
								}
							}
						}
						else
						{
							//usage. Show list user
							$uriToShareSection = $GLOBALS['APPLICATION']->GetCurUri(http_build_query(array(
								'toWDController' => 1,
								'wdaction' => 'info_user_share',
								'shareSectionId' => $res['LINK']['SECTION_ID'],
							)));
							$arActions["section_share"] = array(
								"ICONCLASS" => "section_share",
								"TITLE" => GetMessage("WD_MANAGE_SHARE_TITLE"),
								"TEXT" => GetMessage("WD_MANAGE_SHARE_NAME"),
								"ONCLICK" => "WDShareFolder('{$uriToShareSection}', {$res['LINK']['SECTION_ID']}, '" . CUtil::JSEscape($res["URL"]["~DELETE"]) . "', '" . CUtil::JSEscape($res["NAME"]) . "')",
							);
						}
					}
					elseif(CWebDavIblock::$possibleUseSymlinkByInternalSections && !$isExtranetUser)
					{
						if(empty($res['LINK'])) //this section is link.
						{
							if(!empty($res['SHARED_SECTION']))
							{
								//usage. Show list user
								$uriToShareSection = $GLOBALS['APPLICATION']->GetCurUri(http_build_query(array(
									'toWDController' => 1,
									'wdaction' => 'info_user_share',
									'shareSectionId' => $res["ID"],
								)));
								$uriToDisconnectSection = $GLOBALS['APPLICATION']->GetCurUri(http_build_query(array(
									'toWDController' => 1,
									'wdaction' => 'disconnect',
									'shareSectionId' => $res["ID"],
								)));
								$arActions["section_share"] = array(
									"ICONCLASS" => "section_share",
									"TITLE" => GetMessage("WD_MANAGE_SHARE_TITLE"),
									"TEXT" => GetMessage("WD_MANAGE_SHARE_TITLE"),
									"ONCLICK" => "WDShareFolderInSharedDocs('{$uriToShareSection}', {$res["ID"]}, '{$uriToDisconnectSection}', '" . CUtil::JSEscape($res["NAME"]) . "')",
								);
							}
							else
							{
								$uriToShareSection = $GLOBALS['APPLICATION']->GetCurUri(http_build_query(array(
									'toWDController' => 1,
									'wdaction' => 'connect',
									'shareSectionId' => $res["ID"],
								)));
								$arActions["section_share"] = array(
									"ICONCLASS" => "section_share",
									"TITLE" => GetMessage("WD_SHARE_SECTION_CONNECT_TITLE"),
									"TEXT" => GetMessage("WD_SHARE_SECTION_CONNECT_NAME"),
									"ONCLICK" => "showWebdavSharedSectionDiskPopup('{$uriToShareSection}', {$res["ID"]}, null, '" . CUtil::JSEscape($res["NAME"]) . "')",
								);
							}
						}
					}
				}

				$arActions["section_rename"] = array(
					"ICONCLASS" => "section_rename",
					"TITLE" => GetMessage("WD_RENAME_SECTION_TITLE"),
					"TEXT" => GetMessage("WD_RENAME_NAME"),
					"ONCLICK" => "WDRename(BX('ID_".$res["TYPE"].$res["ID"]."'), bxGrid_".$arParams["GRID_ID"].", '".$arParams["GRID_ID"]."')");

				if (
					$ob->Type == "iblock"
					&& $arParams["OBJECT"]->CheckWebRights("", false, array("action" => "create"))
				) // TODO: move it to module!
				{
					$url = WDAddPageParams($res["URL"]["SECTIONS_DIALOG"],
						array(
							"ACTION" => "COPY",
							"NAME" => urlencode($res["NAME"]),
							"ID" => "S".$res["ID"]
						), false
					);
					$arActions["section_copy"] = array(
						"ICONCLASS" => "section_copy",
						"TITLE" => GetMessage("WD_COPY_SECTION_TITLE"),
						"TEXT" => GetMessage("WD_COPY_NAME"),
						"ONCLICK" => "(new BX.CDialog({'width': 450, 'heght':400, 'content_url':'".CUtil::JSEscape($url)."'})).Show()"
					);

					$url = WDAddPageParams($res["URL"]["SECTIONS_DIALOG"],
						array(
							"ACTION" => "MOVE",
							"NAME" => urlencode($res["NAME"]),
							"ID" => "S".$res["ID"]
						), false
					);
					$arActions["section_move"] = array(
						"ICONCLASS" => "section_move",
						"TITLE" => GetMessage("WD_MOVE_SECTION_TITLE"),
						"TEXT" => GetMessage("WD_MOVE_NAME"),
						"ONCLICK" => "(new BX.CDialog({'width': 450, 'heght':400, 'content_url':'".CUtil::JSEscape($url)."'})).Show()"
					);
				}
			}
			if ($res["SHOW"]["RIGHTS"] == "Y")
			{
				$urlParams = array(
					"IBLOCK_ID" => $arParams["IBLOCK_ID"],
					"ENTITY_TYPE" => "SECTION",
					"ENTITY_ID" => $res['ID'],
					"back_url" => urlencode($GLOBALS['APPLICATION']->GetCurPage())
				);
				if (isset($ob->attributes['user_id']))
				{
					$urlParams['SOCNET_TYPE'] = 'user';
					$urlParams['SOCNET_ID'] = $ob->attributes['user_id'];
				}
				elseif (isset($ob->attributes['group_id']))
				{
					$urlParams['SOCNET_TYPE'] = 'group';
					$urlParams['SOCNET_ID'] = $ob->attributes['group_id'];
				}
				$url = WDAddPageParams(
					"/bitrix/components/bitrix/webdav.section.list/templates/.default/iblock_e_rights.php",
					$urlParams,
					false
				);
				$arActions["section_permissions"] = array(
					"ICONCLASS" => "section_permissions",
					"TITLE" => GetMessage("WD_SECTION_PERMISSIONS"),
					"TEXT" => GetMessage("WD_PERMISSIONS"),
					//"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~EDIT"]."?webdavForm".$arParams["IBLOCK_ID"]."_active_tab=tab_permissions")."');");
					"ONCLICK" => "(new BX.CDialog({'width': 750, 'heght':400, 'content_url':'".CUtil::JSEscape($url)."'})).Show()"
				);
			}
			if ($res["SHOW"]["DELETE"] == "Y" && ((!$bInTrash) || ($bInTrash && $arParams["PERMISSION"] > "W")))
			{
				if(!empty($res['LINK']))
				{
					$arActions["section_unshare"] = array(
						"ICONCLASS" => "section_drop",
						"TITLE" => GetMessage("WD_UNSHARE_SECTION"),
						"TEXT" => GetMessage("WD_UNSHARE"),
						"ONCLICK" => "WDConfirm('".CUtil::JSEscape(GetMessage("WD_UNSHARE_TITLE")).
						"', '".CUtil::JSEscape(GetMessage("WD_UNSHARE_SECTION_CONFIRM", array('#NAME#' => $res['NAME']))).
						"', function() {jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~DELETE"])."')})");
				}
				elseif(!empty($res['SHARED_SECTION']) && isset($arActions["section_share"]))
				{
					$arActions["section_drop"] = array(
						"ICONCLASS" => "section_drop",
						"TITLE" => GetMessage("WD_DELETE_SECTION"),
						"TEXT" => GetMessage("WD_DELETE"),
						"ONCLICK" => "WDConfirm('".CUtil::JSEscape(GetMessage("WD_DELETE_OWN_SHARE_SECTION_TITLE")).
						"', '".CUtil::JSEscape(GetMessage("WD_DELETE_OWN_SHARE_SECTION_CONFIRM", array('#NAME#' => $res['NAME']))).
						"', function() {jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~DELETE"])."')})");

				}
				else
				{
					$forceDeleteUrl = CHTTP::urlAddParams($res["URL"]["~DELETE"], array(
						'delete_without_trash' => 1,
					));
					$arActions["section_drop"] = array(
						"ICONCLASS" => "section_drop",
						"TITLE" => GetMessage("WD_DELETE_SECTION"),
						"TEXT" => GetMessage("WD_DELETE"),
					);
					if($res["SHOW"]["UNDELETE"] == "Y")
					{
						$arActions["section_drop"]['ONCLICK'] =
							"WDConfirm('".CUtil::JSEscape(GetMessage("WD_DELETE_TITLE")).
							"', '".CUtil::JSEscape(GetMessage(($res["SHOW"]["UNDELETE"] == "Y")?"WD_DESTROY_SECTION_CONFIRM":"WD_DELETE_SECTION_CONFIRM", array('#NAME#' => $res['NAME']))).
							"', function() {jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~DELETE"])."')})";
					}
					elseif($arParams['OBJECT']->CheckRight($res["E_RIGHTS"], "iblock_edit") >= "X")
					{
						$arActions["section_drop"]['ONCLICK'] =
							"WDConfirmDelete('".CUtil::JSEscape(GetMessage("WD_DELETE_TITLE"))."', '".
							CUtil::JSEscape(GetMessage("WD_TRASH_DELETE_DESTROY_SECTION_CONFIRM", array("#NAME#" => $res['NAME']))) . "'" .
							", '".CUtil::JSEscape(GetMessage("WD_TRASH_DELETE_BUTTON"))."'" .
							", '".CUtil::JSEscape(GetMessage("WD_TRASH_DESTROY_BUTTON"))."'" .
							", '".CUtil::JSEscape(GetMessage("WD_TRASH_CANCEL_DELETE_BUTTON"))."'" .
							", function() { var urlDelete = '".CUtil::JSEscape($res["URL"]["~DELETE"])."';  jsUtils.Redirect([], urlDelete)}" .
							", function() { var urlDelete = '" . CUtil::JSEscape($forceDeleteUrl) . "'; jsUtils.Redirect([], urlDelete)})";
					}
					else
					{
						$arActions["section_drop"]['ONCLICK'] =
							"WDConfirm('".CUtil::JSEscape(GetMessage("WD_DELETE_TITLE"))."', '".
							CUtil::JSEscape(GetMessage("WD_DELETE_SECTION_CONFIRM", array("#NAME#" => $res['NAME']))) .
							"', function() { var urlDelete = '".CUtil::JSEscape($res["URL"]["~DELETE"])."';  jsUtils.Redirect([], urlDelete)})";
					}
				}
			}
			$arActions['preview_launch'] = array(
				'type' => 'folder',
				'src' => $res["URL"]["~THIS"],
				'title' => $res['NAME'],
				'owner' => CUser::FormatName(CSite::GetNameFormat(false),
					array(
						'LOGIN' => $res['CREATED_BY']['LOGIN'],
						'NAME' => $res['CREATED_BY']['NAME'],
						'SECOND_NAME' => $res['CREATED_BY']['SECOND_NAME'],
						'LAST_NAME' => $res['CREATED_BY']['LAST_NAME'],
					),
					true,
					false
				),
				'size' => CFile::FormatSize($res['PROPERTY_WEBDAV_SIZE_VALUE']),
				'dateModify' => FormatDate('FULL', MakeTimeStamp($res["TIMESTAMP_X"])),
			);
		}
		else
		{
			$arActions["element_open"] = array(
				"ICONCLASS" => "element_open",
				"TITLE" => GetMessage("WD_OPEN_DOCUMENT"),
				"TEXT" => GetMessage("WD_OPEN"),
				"ONCLICK" => "OpenDoc('".CUtil::JSEscape(htmlspecialcharsbx($res["URL"]["~THIS"]))."', ".
				(
						in_array($res["FILE_EXTENTION"], $arOfficeExtensions)
						&& ($arParams['DEFAULT_EDIT'] === 'Y')
					? "true" : "false"
				).");",
				"DEFAULT" => true);

			if($allowExtDocServices && CWebDavTools::allowPreviewFile($res["FILE_EXTENTION"], $res['PROPERTY_WEBDAV_SIZE_VALUE']))
			{
				//showInViewer
				$downloadUrl = CUtil::JSEscape($res["URL"]["~DOWNLOAD"]);
				$editInUrl = $editrUrl  = CUtil::JSEscape(($GLOBALS['APPLICATION']->IsHTTPS() ? 'https' : 'http').'://'.str_replace("//", "/", $_SERVER['HTTP_HOST']."/".$res["URL"]["THIS"])) . '?' . bitrix_sessid_get() . '&editIn=' . CWebDavLogOnlineEditBase::DEFAULT_SERVICE_NAME . '&start=1';
				$downloadUrl .= ((strpos($downloadUrl, "?") === false) ? "?" : "&") . "ncc=1&force_download=1";
				$viewerUrl  = CUtil::JSEscape(($GLOBALS['APPLICATION']->IsHTTPS() ? 'https' : 'http').'://'.str_replace("//", "/", $_SERVER['HTTP_HOST']."/".$res["URL"]["THIS"])) . '?showInViewer=1';
				$arActions['preview_launch'] = array(
					'type' => 'iframe',
					'src' => $viewerUrl,
					'download' => $downloadUrl,
					'history' => CHTTP::urlAddParams($res['URL']['VIEW'], array('webdavForm' . $res['IBLOCK_ID'] . '_active_tab' => 'tab_history')),
					'edit' => $res['LOCK_STATUS'] == 'green' && CWebDavEditDocGoogle::isEditable($res["FILE_EXTENTION"]) && $res['E_RIGHTS']['element_edit']? $editInUrl : '',
					'askConvert' => CWebDavEditDocGoogle::isNeedConvertExtension($res["FILE_EXTENTION"]),
					'title' => $res['NAME'],
					'inPersonalLib' => $isUserLib && $res['LOCK_STATUS'] == 'green' && $res['E_RIGHTS']['element_edit']? '1' : '',
					'externalId' => $isUserLib? "st{$rootDataForCurrentUser['IBLOCK_ID']}|{$rootDataForCurrentUser['SECTION_ID']}|f{$res['ID']}" : '',
					'relativePath' => $res['PATH'],
				);
			}
			elseif($allowExtDocServices && CWebDavEditDocGoogle::isEditable($res["FILE_EXTENTION"]))
			{
				//showInViewer
				$downloadUrl = CUtil::JSEscape($res["URL"]["~DOWNLOAD"]);
				$editInUrl = $editrUrl  = CUtil::JSEscape(($GLOBALS['APPLICATION']->IsHTTPS() ? 'https' : 'http').'://'.str_replace("//", "/", $_SERVER['HTTP_HOST']."/".$res["URL"]["THIS"])) . '?' . bitrix_sessid_get() . '&editIn=' . CWebDavLogOnlineEditBase::DEFAULT_SERVICE_NAME . '&start=1';
				$downloadUrl .= ((strpos($downloadUrl, "?") === false) ? "?" : "&") . "ncc=1&force_download=1";
				$viewerUrl  = CUtil::JSEscape(($GLOBALS['APPLICATION']->IsHTTPS() ? 'https' : 'http').'://'.str_replace("//", "/", $_SERVER['HTTP_HOST']."/".$res["URL"]["THIS"])) . '?showInViewer=1';
				$arActions['preview_launch'] = array(
					'type' => 'onlyedit',
					'src' => $viewerUrl,
					'download' => $downloadUrl,
					'history' => CHTTP::urlAddParams($res['URL']['VIEW'], array('webdavForm' . $res['IBLOCK_ID'] . '_active_tab' => 'tab_history')),
					'edit' => $res['LOCK_STATUS'] == 'green' && CWebDavEditDocGoogle::isEditable($res["FILE_EXTENTION"]) && $res['E_RIGHTS']['element_edit']? $editInUrl : '',
					'askConvert' => CWebDavEditDocGoogle::isNeedConvertExtension($res["FILE_EXTENTION"]),
					'title' => $res['NAME'],
					'owner' => CUser::FormatName(CSite::GetNameFormat(false),
						array(
							'LOGIN' => $res['CREATED_BY']['LOGIN'],
							'NAME' => $res['CREATED_BY']['NAME'],
							'SECOND_NAME' => $res['CREATED_BY']['SECOND_NAME'],
							'LAST_NAME' => $res['CREATED_BY']['LAST_NAME'],
						),
						true,
						false
					),
					'size' => CFile::FormatSize($res['PROPERTY_WEBDAV_SIZE_VALUE']),
					'dateModify' => FormatDate('FULL', MakeTimeStamp($res["TIMESTAMP_X"])),
					'tooBigSizeMsg' => true,
					'inPersonalLib' => $isUserLib && $res['LOCK_STATUS'] == 'green' && $res['E_RIGHTS']['element_edit']? '1' : '',
					'externalId' => $isUserLib? "st{$rootDataForCurrentUser['IBLOCK_ID']}|{$rootDataForCurrentUser['SECTION_ID']}|f{$res['ID']}" : '',
					'relativePath' => $res['PATH'],
				);
			}
			elseif(CFile::IsImage($res['NAME']))
			{
				$downloadUrl = CUtil::JSEscape($res["URL"]["~DOWNLOAD"]);
				$downloadUrl .= ((strpos($downloadUrl, "?") === false) ? "?" : "&") . "ncc=1&force_download=1";
				$arActions['preview_launch'] = array(
					'type' => 'image',
					'src' => $downloadUrl,
					'download' => $downloadUrl,
					'title' => $res['NAME'],
				);
			}
			else
			{
				$downloadUrl = CUtil::JSEscape($res["URL"]["~DOWNLOAD"]);
				$downloadUrl .= ((strpos($downloadUrl, "?") === false) ? "?" : "&") . "ncc=1&force_download=1";
				$arActions['preview_launch'] = array(
					'type' => 'unknown',
					'src' => $downloadUrl,
					'download' => $downloadUrl,
					'title' => $res['NAME'],
					'owner' => CUser::FormatName(CSite::GetNameFormat(false),
						array(
							'LOGIN' => $res['CREATED_BY']['LOGIN'],
							'NAME' => $res['CREATED_BY']['NAME'],
							'SECOND_NAME' => $res['CREATED_BY']['SECOND_NAME'],
							'LAST_NAME' => $res['CREATED_BY']['LAST_NAME'],
						),
						true,
						false
					),
					'size' => CFile::FormatSize($res['PROPERTY_WEBDAV_SIZE_VALUE']),
					'dateModify' => FormatDate('FULL', MakeTimeStamp($res["TIMESTAMP_X"])),
					'tooBigSizeMsg' => $allowExtDocServices && CWebDavTools::allowPreviewFile($res["FILE_EXTENTION"], $res['PROPERTY_WEBDAV_SIZE_VALUE'], false),
					'inPersonalLib' => $isUserLib && $res['LOCK_STATUS'] == 'green' && $res['E_RIGHTS']['element_edit']? '1' : '',
					'externalId' => $isUserLib? "st{$rootDataForCurrentUser['IBLOCK_ID']}|{$rootDataForCurrentUser['SECTION_ID']}|f{$res['ID']}" : '',
					'relativePath' => $res['PATH'],
				);
			}
			$downloadUrl = CUtil::JSEscape($res["URL"]["~DOWNLOAD"]);
			$downloadUrl .= ((strpos($downloadUrl, "?") === false) ? "?" : "&") . "ncc=1&force_download=1";

			$arActions["element_download"] = array(
				"ICONCLASS" => "element_download",
				"TITLE" => GetMessage("WD_DOWNLOAD_ELEMENT"),
				"TEXT" => GetMessage("WD_DOWNLOAD"),
				"ONCLICK" => "window.location.href = '".$downloadUrl."';",
				"DEFAULT" => false);

			if ($arParams["PERMISSION"] >= "U")
			{
				$urlT = CUtil::JSEscape(($GLOBALS['APPLICATION']->IsHTTPS() ? 'https' : 'http').'://'.str_replace("//", "/", $_SERVER['HTTP_HOST']."/".$res["URL"]["THIS"]));
				$arActions["copy_ext_link"] = array(
					"ICONCLASS" => "element_ext_link", //"element_ext_link",
					"TITLE" => GetMessage("WD_COPY_EXT_LINK_TITLE"),
					"TEXT" => GetMessage("WD_COPY_EXT_LINK"),
					"ONCLICK" => CWebDavExtLinks::InsertDialogCallText($urlT)
				);
			}

			if ($res["SHOW"]["UNDELETE"] == "Y")
			{
				$arActions["element_undelete"] = array(
					"ICONCLASS" => "element_download",
					"TITLE" => GetMessage("WD_UNDELETE_ELEMENT"),
					"TEXT" => GetMessage("WD_UNDELETE"),
					"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape(WDAddPageParams($res["URL"]["~UNDELETE"], array("edit"=>"Y", "sessid" => bitrix_sessid()), false))."');",
					"DEFAULT" => false);
			}

			if ($arParams["PERMISSION"] >= "U")
			{
				if (($res["SHOW"]["LOCK"] == "Y") || ($res["SHOW"]["UNLOCK"] == "Y"))
				{
					$arActions["element_upload"] = array(
						"ICONCLASS" => "element_edit",
						"TITLE" => GetMessage("WD_UPLOAD_ELEMENT"),
						"TEXT" => GetMessage("WD_UPLOAD"),
						"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"][(($arParams["OBJECT"]->Type == "folder")?"EDIT":"~VIEW")].'#upload')."');");
				}


				if (
					$ob->Type == "iblock"
					&& $res["SHOW"]["UNLOCK"] == "Y"
				)
				{
					$arActions["element_unlock"] = array(
						"ICONCLASS" => "element_unlock",
						"TITLE" => GetMessage("WD_UNLOCK_ELEMENT"),
						"TEXT" => GetMessage("WD_UNLOCK"),
						"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~UNLOCK"])."');");
				}

				if (
					$ob->Type == "iblock"
					&& $res["SHOW"]["LOCK"] == "Y"
				)
				{
					$arActions["element_lock"] = array(
						"ICONCLASS" => "element_unlock",
						"TITLE" => GetMessage("WD_LOCK_ELEMENT"),
						"TEXT" => GetMessage("WD_LOCK"),
						"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~LOCK"])."');");
				}

				if (
					(
						($res["SHOW"]["LOCK"] == "Y")
						|| ($res["SHOW"]["UNLOCK"] == "Y")
					)
					&& in_array($res["FILE_EXTENTION"], $arOfficeExtensions)
				)
				{
					$arActions["element_edit_office"] = array(
						"ICONCLASS" => "element_edit",
						"TITLE" => GetMessage("WD_EDIT_MSOFFICE"),
						"TEXT" => GetMessage("WD_EDIT_MSOFFICE_MENU"),
						"OFFICECHECK" => true,
						"DISABLED" => !($bShowWebdav && $res["SHOW"]["EDIT"] == "Y" ),
						"ONCLICK" => 'return EditDocWithProgID(\''.CUtil::addslashes($res["URL"]["~THIS"]).'\');');
				}
			}

			$arActions["element_view"] = array(
				"ICONCLASS" => "element_view",
				"TITLE" => GetMessage("WD_VIEW_ELEMENT"),
				"TEXT" => GetMessage(($res["~TYPE"]=="FILE"?"WD_PROPERTIES":"WD_VIEW")),
				"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"][($res["~TYPE"]=="FILE"?"EDIT":"~VIEW")])."');");

			if ($arParams["USE_COMMENTS"]=="Y" && IsModuleInstalled("forum"))
			{
				$arActions["element_comment"] = array(
					"ICONCLASS" => "element_comment",
					"TITLE" => GetMessage("WD_ELEMENT_COMMENT_NAME"),
					"TEXT" => GetMessage("WD_ELEMENT_COMMENT_TITLE"),
					"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~VIEW"]."?webdavForm".$arParams["IBLOCK_ID"]."_active_tab=tab_comments")."');");
			}


			if ($arParams["PERMISSION"] >= "U")
			{

				$arActions["copy_link"] = array(
					"ICONCLASS" => "element_download",
					"TITLE" => GetMessage("WD_COPY_LINK_TITLE"),
					"TEXT" => GetMessage("WD_COPY_LINK"),
					"ONCLICK" => "WDCopyLinkDialog('".CUtil::JSEscape(($GLOBALS['APPLICATION']->IsHTTPS() ? 'https' : 'http').'://'.str_replace("//", "/", $_SERVER['HTTP_HOST']."/".$res["URL"]["THIS"]))."')"
				);

				/*
				$urlT = CUtil::JSEscape(($GLOBALS['APPLICATION']->IsHTTPS() ? 'https' : 'http').'://'.str_replace("//", "/", $_SERVER['HTTP_HOST']."/".$res["URL"]["THIS"]));
				$arActions["copy_ext_link"] = array(
					"ICONCLASS" => "element_download",
					"TITLE" => GetMessage("WD_COPY_EXT_LINK_TITLE"),
					"TEXT" => GetMessage("WD_COPY_EXT_LINK"),
					"ONCLICK" => CWebDavExtLinks::InsertDialogCallText($urlT)
				);
				*/

				if ($res["SHOW"]["HISTORY"] == "Y")
				{
					$arActions["element_history"] = array(
						"ICONCLASS" => "element_history".($res["SHOW"]["BP"] == "Y" ? " bizproc_history" : ""),
						"TITLE" => GetMessage("WD_HIST_ELEMENT_ALT"),
						"TEXT" => GetMessage("WD_HIST_ELEMENT"),
						"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~VIEW"]."?webdavForm".$arParams["IBLOCK_ID"]."_active_tab=tab_history")."');");
				}

				if (($res["SHOW"]["LOCK"] == "Y") || ($res["SHOW"]["UNLOCK"] == "Y"))
				{
					if ($res["SHOW"]["BP_VIEW"] == "Y")
					{
						$arActionsBpTmp[] = array(
							"ICONCLASS" => "bizproc_document",
							"TITLE" => GetMessage("IBLIST_A_BP_H"),
							"TEXT" => GetMessage("IBLIST_A_BP_H"),
							"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~BP"])."');");
					}
					if ($res["SHOW"]["BP_START"] == "Y" && is_array($arParams["TEMPLATES"]))
					{
						$arr = array();
						foreach ($arParams["TEMPLATES"] as $key => $arWorkflowTemplate)
						{
							if (!CBPDocument::CanUserOperateDocument(
								CBPCanUserOperateOperation::StartWorkflow,
								$GLOBALS["USER"]->GetID(),
								$res["DOCUMENT_ID"],
								array(
									"UserGroups" => $res["USER_GROUPS"],
									"DocumentStates" => $res["~arDocumentStates"],
									"WorkflowTemplateList" => $arTemplates,
									"WorkflowTemplateId" => $arWorkflowTemplate["ID"]))) {
										continue;
									}
							$url = $res["URL"]["~BP_START"];
							$url .= (strpos($url, "?") === false ? "?" : "&")."workflow_template_id=".$arWorkflowTemplate["ID"].'&'.bitrix_sessid_get();
							$arr[] = array(
								"ICONCLASS" => "",
								"TITLE" => $arWorkflowTemplate["DESCRIPTION"],
								"TEXT" => $arWorkflowTemplate["NAME"],
								"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($url)."');");
						}
						if (!empty($arr))
						{
							$arActionsBpTmp[] = array(
								"ICONCLASS" => "bizproc_start",
								"TITLE" => GetMessage("WD_START_BP_TITLE"),
								"TEXT" => GetMessage("WD_START_BP"),
								"MENU" => $arr);
						}
					}

					//if ($res["SHOW"]["BP_CLONE"] == "Y")
					//{
					//$arActionsBpTmp[] = array(
					//"ICONCLASS" => "bizproc_document",
					//"TITLE" => GetMessage("WD_CREATE_VERSION_ALT"),
					//"TEXT" => GetMessage("WD_CREATE_VERSION"),
					//"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~CLONE"])."');");
					//$arActionsBpTmp[] = array(
					//"ICONCLASS" => "bizproc_document",
					//"TITLE" => GetMessage("WD_VERSIONS_ALT"),
					//"TEXT" => GetMessage("WD_VERSIONS"),
					//"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~VERSIONS"])."');");
					//}

					if (!empty($arActionsBpTmp)) $arActions += $arActionsBpTmp;

					$arActions["separator_del"] = array("SEPARATOR" => true);
					if ($ob->Type == "folder" || ($ob->Type == "iblock" && $res["WF_PARENT_ELEMENT_ID"] === null))
					{
						$arActions["element_rename"] = array(
							"ICONCLASS" => "element_rename",
							"TITLE" => GetMessage("WD_RENAME_TITLE"),
							"TEXT" => GetMessage("WD_RENAME_NAME"),
							"ONCLICK" => "WDRename(BX('ID_".$res["TYPE"].$res["ID"]."'), bxGrid_".$arParams["GRID_ID"].", '".$arParams["GRID_ID"]."')");
					}
				}

				if ($res["SHOW"]["COPY"] == 'Y')
				{
					$url = WDAddPageParams(
						$res["URL"]["SECTIONS_DIALOG"],
						array(
							"ACTION" => "COPY",
							"NAME" => urlencode($res["NAME"]),
							"ID" => "E".$res["ID"]
						),
						false);
					$arActions["element_copy"] = array(
						"ICONCLASS" => "element_copy",
						"TITLE" => GetMessage("WD_COPY_TITLE"),
						"TEXT" => GetMessage("WD_COPY_NAME"),
						"ONCLICK" => "(new BX.CDialog({'width': 450, 'heght':400, 'content_url':'".CUtil::JSEscape($url)."'})).Show()"
					);
				}

				if ($ob->Type == "iblock" && $res["WF_PARENT_ELEMENT_ID"] === null && ($res["SHOW"]["EDIT"] == "Y"))
				{
					$url = WDAddPageParams(
						$res["URL"]["SECTIONS_DIALOG"],
						array(
							"ACTION" => "MOVE",
							"NAME" => urlencode($res["NAME"]),
							"ID" => "E".$res["ID"]
						),
						false
					);
					$arActions["element_move"] = array(
						"ICONCLASS" => "element_move",
						"TITLE" => GetMessage("WD_MOVE_TITLE"),
						"TEXT" => GetMessage("WD_MOVE_NAME"),
						"ONCLICK" => "(new BX.CDialog({'width': 450, 'heght':400, 'content_url':'".CUtil::JSEscape($url)."'})).Show()"
					);
				}

				if ($res["SHOW"]["RIGHTS"] == "Y")
				{
					$urlParams = array(
						"IBLOCK_ID" => $arParams["IBLOCK_ID"],
						"ENTITY_TYPE" => "ELEMENT",
						"ENTITY_ID" => $res['ID'],
						"back_url" => urlencode($GLOBALS['APPLICATION']->GetCurPageParam())
					);
					if (isset($ob->attributes['user_id']))
					{
						$urlParams['SOCNET_TYPE'] = 'user';
						$urlParams['SOCNET_ID'] = $ob->attributes['user_id'];
					}
					elseif (isset($ob->attributes['group_id']))
					{
						$urlParams['SOCNET_TYPE'] = 'group';
						$urlParams['SOCNET_ID'] = $ob->attributes['group_id'];
					}

					$url = WDAddPageParams(
						"/bitrix/components/bitrix/webdav.section.list/templates/.default/iblock_e_rights.php",
						$urlParams,
						false
					);

					$arActions["element_permissions"] = array(
						"ICONCLASS" => "element_permissions",
						"TITLE" => GetMessage("WD_ELEMENT_PERMISSIONS"),
						"TEXT" => GetMessage("WD_PERMISSIONS"),
						//"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~VIEW"]."?webdavForm".$arParams["IBLOCK_ID"]."_active_tab=tab_permissions")."');"
						"ONCLICK" => "(new BX.CDialog({'width': 750, 'heght':400, 'content_url':'".CUtil::JSEscape($url)."'})).Show()"
					);
				}

				if ($res["SHOW"]["DELETE"] == "Y" && ((!$bInTrash) || ($bInTrash && $arParams["PERMISSION"] >= "X")))
				{
					$forceDeleteUrl = CHTTP::urlAddParams($res["URL"]["~DELETE"], array(
						'delete_without_trash' => 1,
					));

					$arActions["element_delete"] = array(
						"ICONCLASS" => "element_delete",
						"TITLE" => GetMessage("WD_DELETE_ELEMENT"),
						"TEXT" => GetMessage("WD_DELETE"),
					);
					if($res["SHOW"]["UNDELETE"] == "Y")
					{
						$arActions["element_delete"]["ONCLICK"] =
							"WDConfirm('".CUtil::JSEscape(GetMessage("WD_DELETE_TITLE"))."', '".
							CUtil::JSEscape(GetMessage(($res["SHOW"]["UNDELETE"] == "Y") ? "WD_DESTROY_CONFIRM" : "WD_DELETE_CONFIRM", array("#NAME#" => $res['NAME']))).
							"', function() {jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~DELETE"])."')})";
					}
					elseif($arParams['OBJECT']->CheckRight($res["E_RIGHTS"], "iblock_edit") >= "X")
					{
						$arActions["element_delete"]['ONCLICK'] =
							"WDConfirmDelete('".CUtil::JSEscape(GetMessage("WD_DELETE_TITLE"))."', '".
							CUtil::JSEscape(GetMessage("WD_TRASH_DELETE_DESTROY_ELEMENT_CONFIRM", array("#NAME#" => $res['NAME']))) . "'" .
							", '".CUtil::JSEscape(GetMessage("WD_TRASH_DELETE_BUTTON"))."'" .
							", '".CUtil::JSEscape(GetMessage("WD_TRASH_DESTROY_BUTTON"))."'" .
							", '".CUtil::JSEscape(GetMessage("WD_TRASH_CANCEL_DELETE_BUTTON"))."'" .
							", function() { var urlDelete = '".CUtil::JSEscape($res["URL"]["~DELETE"])."';  jsUtils.Redirect([], urlDelete)}" .
							", function() { var urlDelete = '" . CUtil::JSEscape($forceDeleteUrl) . "'; jsUtils.Redirect([], urlDelete)})";
					}
					else
					{
						$arActions["element_delete"]['ONCLICK'] =
							"WDConfirm('".CUtil::JSEscape(GetMessage("WD_DELETE_TITLE"))."', '".
							CUtil::JSEscape(GetMessage("WD_DELETE_CONFIRM", array("#NAME#" => $res['NAME']))) .
							"', function() { var urlDelete = '".CUtil::JSEscape($res["URL"]["~DELETE"])."';  jsUtils.Redirect([], urlDelete)})";
					}
				}
			}
		}

		foreach (array("MODIFIED_BY", "CREATED_BY", "WF_LOCKED_BY") as $user_key)
		{
			$aCols[$user_key] = (is_array($res[$user_key]) ? $res[$user_key] : __parse_user($res[$user_key], $arParams["USER_VIEW_URL"], (isset($arParams["NAME_TEMPLATE"])?$arParams["NAME_TEMPLATE"]:null)));
			$aCols[$user_key] = "<div class=\"wd-user-link\">". $aCols[$user_key]['main_user_link'] ./*.ob_get_clean().*/"</div>";
		}

		if ($res["TYPE"] == "S")
		{
			$classNameForIcon = (!empty($res['LINK']) || !empty($res['SHARED_SECTION']))? 'shared-section-icon' : 'section-icon';
			$res["FTYPE"] = "folder";
			$aCols["PLAIN_NAME"] = $res["NAME"];
			$aCols["NAME"]['shared'] = ($res["SHOW"]["SHARED"])?'<div class="element-shared"></div>':'';
			$aCols["NAME"] =
				'<div class="section-name">
					<div class="' . $classNameForIcon . '"></div>'.$aCols["NAME"]['shared'].'<a class="section-title" id="sec'.$res['ID'].'" href="'.$res["URL"]["THIS"].'"'.
						'data-bx-viewer="' . $arActions['preview_launch']['type'] . '" ' .
						'data-bx-title="' . htmlspecialcharsbx($arActions['preview_launch']['title']) . '" ' .
						'data-bx-src="' . $arActions['preview_launch']['src'] . '" ' .
						'data-bx-size="' . $arActions['preview_launch']['size'] . '" ' .
						'data-bx-owner="' . htmlspecialcharsbx($arActions['preview_launch']['owner']) . '" ' .
						'data-bx-dateModify="' . htmlspecialcharsbx($arActions['preview_launch']['dateModify']) . '" ' .

					'>'.htmlspecialcharsbx($res["NAME"]).'</a>
				</div>';

			if((!empty($res['LINK']) || !empty($res['SHARED_SECTION'])) && isset($arActions["section_share"]))
			{
				$aCols['FILE_SIZE'] = '<div id="sec' . $res['ID'] . '-share" class="wd-share-hotkey-share section-name" onclick="'. $arActions["section_share"]['ONCLICK'] . '">' . GetMessage('WD_ALREADY_SHARE_SECTION') . '</div></div> ';
			}
			//only owner can share section
			elseif(!empty($arParams["OBJECT"]->attributes['user_id']) && $arParams["OBJECT"]->attributes['user_id'] == $USER->getId() && !empty($arActions["section_share"]) && !$isExtranetUser)
			{
				$aCols['FILE_SIZE'] = '<div id="sec' . $res['ID'] . '-share" class="wd-share-hotkey-potential-share section-name" onclick="'. $arActions["section_share"]['ONCLICK'] . '"><div class="shared-section-icon"></div> ' . GetMessage('WD_MAKE_SHARE_SECTION') . '</div> ';
			}
			//potential sharing non user files.
			elseif(empty($arParams["OBJECT"]->attributes['user_id']) && CWebDavIblock::$possibleUseSymlinkByInternalSections && !empty($arActions["section_share"]) && !$isExtranetUser)
			{
				$aCols['FILE_SIZE'] = '<div id="sec' . $res['ID'] . '-share" class="wd-share-hotkey-potential-share section-name" onclick="'. $arActions["section_share"]['ONCLICK'] . '"><div class="shared-section-icon"></div> ' . GetMessage('WD_SHARE_SECTION_CONNECT_IN_GRID') . '</div> ';
			}
		}
		else
		{
			$aCols["NAME"] = array();
			$hintLink = __make_hint($res);
			if (! isset($arParams['MERGE_VIEW']))
			{
				$aCols["NAME"]['hint'] = $res['HINT'];
			}
			else
			{
				$aCols["NAME"]['hint'] = '';
				$hintLink = '';
			}

			$aCols["NAME"]['icon'] = '<div class="element-icon icons icon-'.substr($res["FILE_EXTENTION"], 1).'"></div>';
			$aCols["NAME"]['shared'] = ($res["SHOW"]["SHARED"])?'<div class="element-shared"></div>':'';

			if (strlen($res["NAME"]) == 0)
			{
				$aCols["NAME"]["name"] = "<span>&nbsp;</span>";
			}
			else
			{
				$resName = ($WrapLongWords) ? WrapLongWords(htmlspecialcharsbx($res["NAME"])) : htmlspecialcharsbx($res["NAME"]);
				if($ob->Type != "iblock")
				{
					$aCols["NAME"]['name'] = '<a class="element-title '.((strlen($hintLink) > 0) ? 'element-hint ' : ' ').
						'" id="doc'.$res['ID'].'" '.
						$hintLink . 'href="'.htmlspecialcharsbx($res["URL"]["THIS"]).
						'" onclick="return OpenDoc(this, '.
						(
						in_array($res["FILE_EXTENTION"], $arOfficeExtensions)
						&& ($arParams['DEFAULT_EDIT'] == 'Y')
							? "true" : "false").')"'.
						' target="_blank"'.(strlen($hintLink)>0 ? '' : ' title="'.GetMessage("WD_DOWNLOAD_ELEMENT").'"' ).
						'>' . $resName . '</a>';
				}
				else
				{
					$aCols["NAME"]['name'] =
						'<a class="element-title '.((strlen($hintLink) > 0) ? 'element-hint ' : ' ') .
						'" id="doc' . $res['ID'] . '" ' .
						$hintLink . ' ' .
						'data-bx-viewer="' . $arActions['preview_launch']['type'] . '" ' .
						'data-bx-title="' . htmlspecialcharsbx($arActions['preview_launch']['title']) . '" ' .
						'data-bx-src="' . $arActions['preview_launch']['src'] . '" ' .
						'data-bx-historyPage="' . $arActions['preview_launch']['history'] . '" ' .
						'data-bx-edit="' . $arActions['preview_launch']['edit'] . '" ' .
						'data-bx-isFromUserLib="' . $arActions['preview_launch']['inPersonalLib'] . '" ' .
						'data-bx-externalId="' . $arActions['preview_launch']['externalId'] . '" ' .
						'data-bx-relativePath="' . $arActions['preview_launch']['relativePath'] . '" ' .
						'data-bx-askConvert="' . $arActions['preview_launch']['askConvert'] . '" ' .
						'data-bx-download="' . $arActions['preview_launch']['download'] . '" ' .
						'data-bx-size="' . $arActions['preview_launch']['size'] . '" ' .
						'data-bx-owner="' . htmlspecialcharsbx($arActions['preview_launch']['owner']) . '" ' .
						'data-bx-dateModify="' . htmlspecialcharsbx($arActions['preview_launch']['dateModify']) . '" ' .
						'data-bx-tooBigSizeMsg="' . htmlspecialcharsbx($arActions['preview_launch']['tooBigSizeMsg']) . '" ' .

						'>' . $resName . '</a>';
				}
			}

			$aCols["NAME"]['status'] = '';
			if ($arParams["PERMISSION"] >= "U" && in_array($res['LOCK_STATUS'], array("red", "yellow")))
			{
				$aCols["NAME"]['status'] .= '<div class="element-status-'.$res['LOCK_STATUS'].'">';
					if ($res['LOCK_STATUS'] == "yellow")
					{
						$aCols["NAME"]['status'] .= '['.GetMessage("IBLOCK_YELLOW_MSG").']';
					}
					else
					{
						if (!is_array($res['WF_LOCKED_BY'])
							&& intval($res['WF_LOCKED_BY']) > 0
						)
						{
							$rUserLockedBy = CUser::GetByID($res['WF_LOCKED_BY']);
							$res['WF_LOCKED_BY'] = $rUserLockedBy->Fetch();
						}

						if (
							(isset($res['WF_LOCKED_BY']['ID']))
							&& ($res['WF_LOCKED_BY']['ID'] > 0)
						)
						{
							$res['LOCKED_USER_NAME'] = CUser::FormatName($nameTemplate, $res['WF_LOCKED_BY']);
							$aCols["NAME"]['status'] .= '['.trim(GetMessage("IBLOCK_RED_MSG",array('#NAME#' => $res['LOCKED_USER_NAME']))).']';
						}
						else
						{
							$aCols["NAME"]['status'] .= '['.GetMessage("IBLOCK_RED_MSG_OTHER").']';
						}

					}
				$aCols["NAME"]['status'] .= '</div>';
			}

			if ($arParams["USE_COMMENTS"] == "Y" && intVal($res["PROPERTY_FORUM_MESSAGE_CNT_VALUE"]) > 0):
				$aCols["NAME"]['comments'] = '<a href="'.$res["URL"]["VIEW"].'?webdavForm'.$arParams["IBLOCK_ID"].'_active_tab=tab_comments" class="element-properties element-comments" title="'.
					GetMessage("WD_COMMENTS_FOR_DOCUMENT")." ".intVal($res["PROPERTY_FORUM_MESSAGE_CNT_VALUE"]).'">'.intVal($res["PROPERTY_FORUM_MESSAGE_CNT_VALUE"]).'</a>';

			endif;

			$aCols["PROPERTY_FORUM_MESSAGE_CNT"] = '<a href="'.$res["URL"]["VIEW"].'">'.intVal($res["PROPERTY_FORUM_MESSAGE_CNT_VALUE"]).'</a>';

			$aCols["BP_PUBLISHED"] = "<div class=\"wd-bp-published wd-bp-published-".($res["BP_PUBLISHED"] != "Y" ? "n" : "y")."\"></div>";

			$aCols["NAME"]["version"] = "";
			if ($arParams["WORKFLOW"] == "bizproc" && $res["WF_PARENT_ELEMENT_ID"] > 0)
			{
				$aCols["NAME"]["version"] = "<span class=\"wd-element-version\">" . GetMessage("WD_NAME_VERSION") . "</span>";
			}

			$aCols["BIZPROC"] = "";
			if ($arParams["WORKFLOW"] == "bizproc" && !empty($res["arDocumentStates"]))
			{
				$arDocumentStates = $res["arDocumentStates"];
				if (count($arDocumentStates) == 1)
				{
					$arDocumentState = reset($arDocumentStates);
					$arTasksWorkflow = CBPDocument::GetUserTasksForWorkflow($GLOBALS["USER"]->GetID(), $arDocumentState["ID"]);
					$aColss["BIZPROC"] =
					'<div class="bizproc-item-title">'.
						(!empty($arDocumentState["TEMPLATE_NAME"]) ? htmlspecialcharsbx(htmlspecialcharsbx($arDocumentState["TEMPLATE_NAME"])) : GetMessage("IBLIST_BP")).': '.
						'<span class="bizproc-item-title bizproc-state-title" style="">'.
							'<a href="'.$res["URL"]["BP"].'">'.
								(strlen($arDocumentState["STATE_TITLE"]) > 0 ? htmlspecialcharsbx(htmlspecialcharsbx($arDocumentState["STATE_TITLE"])) : htmlspecialcharsbx(htmlspecialcharsbx($arDocumentState["STATE_NAME"]))).
							'</a>'.
						'</span>'.
					'</div>';
					$aColss["BIZPROC"] = str_replace("'","\"",$aColss["BIZPROC"]);
					$aCols["NAME"]['bizproc'] = "<div class=\"element-bizproc-status bizproc-statuses " . (!(strlen($arDocumentState["ID"]) <= 0 || strlen($arDocumentState["WORKFLOW_STATUS"]) <= 0) ?
								'bizproc-status-'.(empty($arTasksWorkflow) ? "inprogress" : "attention") : '') . "\" onmouseover='BX.hint(this, \"".addslashes($aColss["BIZPROC"])."\")'></div>";

					if (!empty($arTasksWorkflow))
					{
						$tmp = array();
						foreach ($arTasksWorkflow as $key => $val)
						{
							$url = CComponentEngine::MakePathFromTemplate($arParams["WEBDAV_TASK_URL"],
								array("ELEMENT_ID" => $res["ID"], "ID" => $val["ID"]));
							$url = WDAddPageParams($url, array("back_url" =>  urlencode($GLOBALS['APPLICATION']->GetCurPageParam())), false);
							$tmp[] = '<a href="'.$url.'">'.$val["NAME"].'</a>';
						}
						$aColss["BIZPROC"] .= '<div class="bizproc-tasks">'.implode(", ", $tmp).'</div>';
					}
				}
				else
				{
					$arTasks = array(); $bInprogress = false; $tmp = array();

					foreach ($arDocumentStates as $key => $arDocumentState)
					{
						$arTasksWorkflow = CBPDocument::GetUserTasksForWorkflow($GLOBALS["USER"]->GetID(), $arDocumentState["ID"]);
						if (!$bInprogress)
							$bInprogress = (strlen($arDocumentState["ID"]) > 0 && strlen($arDocumentState["WORKFLOW_STATUS"]) > 0);
						$tmp[$key] =
							'<li class="bizproc-item">'.
								'<div class="bizproc-item-title">'.
									'<div class="bizproc-statuses '.
										(strlen($arDocumentState["ID"]) > 0 && strlen($arDocumentState["WORKFLOW_STATUS"]) > 0 ?
											'bizproc-status-'.(empty($arTasksWorkflow) ? "inprogress" : "attention") : '').'"></div>'.
									(!empty($arDocumentState["TEMPLATE_NAME"]) ? $arDocumentState["TEMPLATE_NAME"] : GetMessage("IBLIST_BP")).

								'</div>'.
								'<div class="bizproc-item-title bizproc-state-title">'.
									(strlen($arDocumentState["STATE_TITLE"]) > 0 ? $arDocumentState["STATE_TITLE"] : $arDocumentState["STATE_NAME"]).
								'</div>';

						if (!empty($arTasksWorkflow))
						{
							$tmp_tasks = array();
							foreach ($arTasksWorkflow as $val)
							{
								$url = CComponentEngine::MakePathFromTemplate($arParams["WEBDAV_TASK_URL"],
										array("ELEMENT_ID" => $res["ID"], "ID" => $val["ID"]));
								$url = WDAddPageParams($url, array("back_url" =>  urlencode($GLOBALS['APPLICATION']->GetCurPageParam())), false);
								$tmp_tasks[] = '<a href="'.$url.'">'.$val["NAME"].'</a>';
								$arTasks[] = $val;
							}


							$tmp[$key] .= '<div class="bizproc-tasks">'.implode(", ", $tmp_tasks).'</div>';
						}
						$tmp[$key] .=
							'</li>';
					}
					$aColss["BIZPROC"] =
						'<span class="bizproc-item-title">'.
							GetMessage("WD_BP_R_P").': <a href="'.$res["URL"]["BP"].'" title="'.GetMessage("WD_BP_R_P_TITLE").'">'.count($arDocumentStates).'</a>'.
						'</span>'.
						(!empty($arTasks) ?
						'<br /><span class="bizproc-item-title">'.
							GetMessage("WD_TASKS").': <a href="'.$res["URL"]["BP_TASK"].'" title="'.GetMessage("WD_TASKS_TITLE").'">'.count($arTasks).'</a></span>' : '');

					$aCols["NAME"]['bizproc'] = "<div class=\"element-bizproc-status bizproc-statuses " .
						($bInprogress ? ' bizproc-status-'.(empty($arTasks) ? "inprogress" : "attention") : '' ) .
						"\" onmouseover='BX.hint(this, \"".addslashes($aColss['BIZPROC'])."\")'></div>";
				}
				$aCols['BIZPROC'] = $aColss['BIZPROC'];
			}
		}
		$aCols["ACTIVE"] = ($res["ACTIVE"] == "Y" ? GetMessage("WD_Y") : GetMessage("WD_N"));
		$aCols["TIMESTAMP_X"] = "<div class='wd_column_date'>".FormatDate('X', MakeTimeStamp($res["TIMESTAMP_X"]))."</div>";
		$aCols["DATE_CREATE"] = "<div class='wd_column_date'>".FormatDate('X', MakeTimeStamp($res["DATE_CREATE"]))."</div>";
		$sName = '';
		$sRating = '';
		if ($res['TYPE'] != 'S')
		{
				if ($arParams["SHOW_RATING"] == 'Y' && $arParams["RATING_TAG"] == 'Y')
					$sRating = "#RATING#";
			$aCols["NAME"] = $aCols["NAME"]["hint"] .
				"<div class=\"element-name\">" . $aCols["NAME"]["icon"] . $aCols["NAME"]["shared"] .
					"<div class=\"element-name-wrapper\">" . $aCols["NAME"]["name"] . $aCols["NAME"]["version"] . CWebDavExtLinks::$icoRepStr . $aCols["NAME"]["comments"] . $sRating . $aCols["NAME"]["status"]  . "</div>" .
					$aCols["NAME"]["bizproc"] .
				"</div>" ;
		}

		if ($bTheFirstTimeonPage == true && $res["PERMISSION"] >= "U")
		{
			$bTheFirstTimeonPage = false;
?>
<script>
try {
if (/*@cc_on ! @*/ false && new ActiveXObject("SharePoint.OpenDocuments.2"))
{
	BX.ready(
		function()
		{
			setTimeout(
				function ()
				{
					try
					{
						var res = document.getElementsByTagName("A");
						for (var ii = 0; ii < res.length; ii++)
						{
							if (res[ii].className.indexOf("element-edit-office") >= 0) { res[ii].style.display = 'block'; }
						}
					}
					catch(e) {}
				}
				, 10
			)
		}
	);
}
} catch(e) {}

BX.message({
	'wd_desktop_disk_is_installed': '<?= (bool)CWebDavTools::isDesktopDiskInstall() ?>'
});

</script>
<?
		}
		return array("actions" => $arActions, "columns" => $aCols);
	}
}
?>