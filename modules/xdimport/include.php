<?
global $DBType;

IncludeModuleLangFile(__FILE__);

if (!IsModuleInstalled("socialnetwork"))
{
	return false;
}

define("XDI_DEBUG", false);
define("XDI_XML_ERROR_DEBUG", false);
define("XDI_XML_DEBUG", false);

$db_type = strtolower($DB->type);
CModule::AddAutoloadClasses(
	"xdimport",
	array(
		"CXDImport" => "classes/general/xdimport.php",
		"CXDIUser" => "classes/general/user.php",
		"CXDILiveFeed" => "classes/general/livefeed.php",
		"CXDILFScheme" => "classes/".$DBType."/lf_scheme.php",
		"CXDILFSchemeRights" => "classes/general/lf_scheme_rights.php",
		"CXDILFSchemeXML" => "classes/general/lf_scheme_xml.php",
		"CXDILFSchemeRSS" => "classes/general/lf_scheme_rss.php",
		"CXDILFSchemeRSSAtom" => "classes/general/lf_scheme_rss_atom.php",
	)
);

class CXDILFEventHandlers
{
	function OnFillSocNetAllowedSubscribeEntityTypes(&$arSocNetAllowedSubscribeEntityTypes)
	{
		define("SONET_SUBSCRIBE_ENTITY_PROVIDER", "P");
		$arSocNetAllowedSubscribeEntityTypes[] = SONET_SUBSCRIBE_ENTITY_PROVIDER;

		global $arSocNetAllowedSubscribeEntityTypesDesc;
		$arSocNetAllowedSubscribeEntityTypesDesc[SONET_SUBSCRIBE_ENTITY_PROVIDER] = array(
			"TITLE_LIST" => GetMessage("LFP_SOCNET_LOG_LIST_P_ALL"),
			"TITLE_ENTITY" => GetMessage("LFP_SOCNET_LOG_P"),
			"TITLE_ENTITY_XDI" => GetMessage("LFP_SOCNET_LOG_XDI_P"),
			"CLASS_DESC_GET" => "CXDILFScheme",
			"METHOD_DESC_GET" => "GetProviderByID",
			"XDIMPORT_ALLOWED" => "Y"
		);
	}

	function OnFillSocNetLogEvents(&$arSocNetLogEvents)
	{
		$arSocNetLogEvents["data"] = array(
			"ENTITIES" =>	array(
				SONET_SUBSCRIBE_ENTITY_PROVIDER => array(
					"TITLE" => GetMessage("LFP_SOCNET_LOG_DATA"),
					"TITLE_SETTINGS" => GetMessage("LFP_SOCNET_LOG_DATA_SETTINGS"),
					"TITLE_SETTINGS_1" => GetMessage("LFP_SOCNET_LOG_DATA_SETTINGS_P_1"),
					"TITLE_SETTINGS_2" => GetMessage("LFP_SOCNET_LOG_DATA_SETTINGS_P_2"),
				),
				SONET_SUBSCRIBE_ENTITY_USER => array(
					"TITLE" => GetMessage("LFP_SOCNET_LOG_DATA"),
					"TITLE_SETTINGS" => GetMessage("LFP_SOCNET_LOG_DATA_SETTINGS"),
					"TITLE_SETTINGS_1" => GetMessage("LFP_SOCNET_LOG_DATA_SETTINGS_U_1"),
					"TITLE_SETTINGS_2" => GetMessage("LFP_SOCNET_LOG_DATA_SETTINGS_U_2"),
					"OPERATION" => "viewprofile"
				),
				SONET_SUBSCRIBE_ENTITY_GROUP => array(
					"TITLE" => GetMessage("LFP_SOCNET_LOG_DATA"),
					"TITLE_SETTINGS" => GetMessage("LFP_SOCNET_LOG_DATA_SETTINGS"),
					"TITLE_SETTINGS_1" => GetMessage("LFP_SOCNET_LOG_DATA_SETTINGS_G_1"),
					"TITLE_SETTINGS_2" => GetMessage("LFP_SOCNET_LOG_DATA_SETTINGS_G_2"),
					"OPERATION" => "viewsystemevents"
				),
			),
			"CLASS_FORMAT" => "CXDILFEventHandlers",
			"METHOD_FORMAT" => "FormatEvent_Data",
			"FULL_SET" => array("data", "data_comment"),
			"COMMENT_EVENT" => array(
				"EVENT_ID" => "data_comment",
				"UPDATE_CALLBACK" => "NO_SOURCE",
				"DELETE_CALLBACK" => "NO_SOURCE",
				"CLASS_FORMAT" => "CXDILFEventHandlers",
				"METHOD_FORMAT"	=> "FormatComment_Data",
				"RATING_TYPE_ID" => "LOG_COMMENT"
			),
			"XDIMPORT_ALLOWED" => "Y"
		);
	}

	function FormatEvent_Data($arFields, $arParams, $bMail = false)
	{
		$arResult = array(
			"EVENT" => $arFields,
			"URL" => ""
		);

		if (!CModule::IncludeModule("socialnetwork"))
		{
			return $arResult;
		}

		if ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_PROVIDER)
		{
			$arResult["ENTITY"] = CXDILFEventHandlers::GetEntity_Data($arFields, $bMail);
			$rsRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arFields["ID"]));
			$arRights = array();
			while ($arRight = $rsRight->Fetch())
			{
				$arRights[] = $arRight["GROUP_CODE"];
			}

			$arDestination = CSocNetLogTools::FormatDestinationFromRights($arRights, $arParams, $iMoreCount);
		}
		elseif (in_array($arFields["ENTITY_TYPE"], array(SONET_SUBSCRIBE_ENTITY_GROUP, SONET_SUBSCRIBE_ENTITY_USER)))
		{
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, $bMail);

			if ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP) // group
			{
				$arDestination = array(
					array(
						"STYLE" => "sonetgroups",
						"TITLE" => $arResult["ENTITY"]["FORMATTED"]["NAME"],
						"URL" => $arResult["ENTITY"]["FORMATTED"]["URL"],
						"IS_EXTRANET" => (is_array($GLOBALS["arExtranetGroupID"]) && in_array($arFields["ENTITY_ID"], $GLOBALS["arExtranetGroupID"]))
					)
				);
			}
		}

		$arEventParams = \Bitrix\XDImport\Internals\Utils::getParamsFromString(strlen($arFields["~PARAMS"]) > 0 ? $arFields["~PARAMS"] : $arFields["PARAMS"]);

		if (
			is_array($arEventParams)
			&& array_key_exists("SCHEME_ID", $arEventParams)
		)
		{
			$rs = CXDILFScheme::GetByID($arEventParams["SCHEME_ID"]);
			if ($arScheme = $rs->Fetch())
			{
				$arParams["IS_HTML"] = $arScheme["IS_HTML"];
			}
		}

		if (
			in_array($arFields["ENTITY_TYPE"], array(SONET_SUBSCRIBE_ENTITY_GROUP, SONET_SUBSCRIBE_ENTITY_USER))
			&& is_array($arEventParams)
			&& count($arEventParams) > 0
			&& array_key_exists("ENTITY_NAME", $arEventParams)
			&& strlen($arEventParams["ENTITY_NAME"]) > 0
		)
		{
			$title_tmp = (
				!$bMail
				&& strlen($arFields["URL"]) > 0
					? '<a href="'.$arFields["URL"].'">'.$arEventParams["ENTITY_NAME"].'</a>'
					: $arEventParams["ENTITY_NAME"]
			);
		}
		else
		{
			$title_tmp = (
				!$bMail
				&& strlen($arFields["URL"]) > 0
					? '<a href="'.$arFields["URL"].'">'.$arFields["TITLE"].'</a>'
					: $arFields["TITLE"]
			);
		}

		$title = str_replace(
			array("#TITLE#", "#ENTITY#"),
			array($title_tmp, ($bMail ? $arResult["ENTITY"]["FORMATTED"] : $arResult["ENTITY"]["FORMATTED"]["NAME"])),
			($bMail ? GetMessage("LFP_SOCNET_LOG_DATA_".$arFields["ENTITY_TYPE"]."_TITLE_MAIL") : GetMessage("LFP_SOCNET_LOG_DATA_TITLE"))
		);

		$url = false;

		if (strlen($arFields["URL"]) > 0)
		{
			$url = $arFields["URL"];
		}

		if ($arParams["IS_HTML"] == "Y")
		{
			$sanitizer = new CBXSanitizer();
			$sanitizer->ApplyHtmlSpecChars(false);
			$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_LOW);
			$message = htmlspecialcharsEx($sanitizer->SanitizeHtml(htmlspecialcharsback($arFields["MESSAGE"])));
		}
		else
		{
			$message = htmlspecialcharsEx($arFields["MESSAGE"]);
		}

		if (in_array($arFields["ENTITY_TYPE"], array(SONET_SUBSCRIBE_ENTITY_GROUP, SONET_SUBSCRIBE_ENTITY_USER)))
		{
			$message = (
				!$bMail
					? "<b><a href='".$arFields["URL"]."'>".$arFields["TITLE"]."</a></b><br />".$message
					: $arFields["TITLE"]."#BR##BR#".$message
			);
		}

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => $title,
			"TITLE_24" => ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_PROVIDER ? (($arParams["MOBILE"] == "Y") ? GetMessage("LFP_SOCNET_LOG_DATA_TITLE_24") : GetMessage("LFP_SOCNET_LOG_DATA_TITLE_IMPORTANT_24")) : GetMessage("LFP_SOCNET_LOG_DATA_TITLE_24")),
			"MESSAGE" => ($bMail ? CSocNetTextParser::killAllTags($message) : $message),
			"IS_IMPORTANT" => ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_PROVIDER ? true : false),
			"STYLE" => ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_PROVIDER ? "imp-post feed-external-massage" : ""),
			"DESTINATION" => $arDestination
		);

		if ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_PROVIDER)
		{
			$arResult["EVENT_FORMATTED"]["TITLE_24_2"] = $arFields["TITLE"];
		}

		if (intval($iMoreCount) > 0)
		{
			$arResult["EVENT_FORMATTED"]["DESTINATION_MORE"] = $iMoreCount;
		}

		if (
			is_array($arEventParams)
			&& array_key_exists("SOURCE_TIMESTAMP", $arEventParams)
		)
		{
			$arResult["EVENT_FORMATTED"]["LOG_DATE_FORMAT"] = ConvertTimeStamp($arEventParams["SOURCE_TIMESTAMP"], "FULL");
		}

		if (strlen($url) > 0)
		{
			$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}

		if (!$bMail)
		{
			if ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_PROVIDER)
			{
				$arResult["EVENT_FORMATTED"]["AVATAR_STYLE"] = "avatar-rss";
			}
			elseif ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP)
			{
				$arGroup = array(
					"IMAGE_ID" => $arFields["GROUP_IMAGE_ID"]
				);
				$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatarGroup($arGroup, $arParams);
			}
			elseif ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_USER)
			{
				$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams, "USER_");
			}
			elseif ($arFields["ENTITY_TYPE"] == "N")
			{
				$arResult["EVENT_FORMATTED"]["AVATAR_STYLE"] = "avatar-info";
			}

			$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
			$parserLog->pathToUser = (!empty($arParams["PATH_TO_USER"]) ? $arParams["PATH_TO_USER"] : '');

			$arAllow = array(
				"HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y", 
				"IMG" => "Y", 
				"QUOTE" => "Y", 
				"CODE" => "Y", 
				"FONT" => "Y", 
				"LIST" => "Y", 
				"SMILES" => "Y", 
				"NL2BR" => "N", 
				"LOG_NL2BR" => ($arParams["IS_HTML"] == "Y" ? "N" : "Y"),
				"MULTIPLE_BR" => "N", 
				"VIDEO" => "Y", 
				"LOG_VIDEO" => "N"
			);

			$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));

			if (
				$arParams["MOBILE"] != "Y" 
				&& $arParams["NEW_TEMPLATE"] != "Y"
			)
			{
				$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
					$parserLog->convert(htmlspecialcharsback(str_replace("#CUT#", "", $arResult["EVENT_FORMATTED"]["MESSAGE"])), array(), $arAllow),
					500
				);
				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);			
			}
		}
		return $arResult;
	}

	function FormatComment_Data($arFields, $arParams, $bMail = false, $arLog = array())
	{
		$arResult = array(
			"EVENT_FORMATTED" => array(),
		);

		if (!CModule::IncludeModule("socialnetwork"))
		{
			return $arResult;
		}

		if ($arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_PROVIDER)
		{
			$arResult["ENTITY"] = CXDILFEventHandlers::GetEntity_Data($arLog, $bMail);
		}
		elseif (in_array($arLog["ENTITY_TYPE"], array(SONET_SUBSCRIBE_ENTITY_GROUP, SONET_SUBSCRIBE_ENTITY_USER)))
		{
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arLog, $arParams, $bMail);
		}

		if(!$bMail && $arParams["USE_COMMENT"] != "Y")
		{
			$arLog["ENTITY_ID"] = $arFields["ENTITY_ID"];
			$arLog["ENTITY_TYPE"] = $arFields["ENTITY_TYPE"];
		}

		if (
			!$bMail
			&& array_key_exists("URL", $arLog)
			&& strlen($arLog["URL"]) > 0
		)
			$news_tmp = '<a href="'.$arLog["URL"].'">'.$arLog["TITLE"].'</a>';
		else
			$news_tmp = $arLog["TITLE"];

		$title_tmp = ($bMail ? GetMessage("LFP_SOCNET_LOG_DATA_COMMENT_".$arLog["ENTITY_TYPE"]."_TITLE_MAIL") : GetMessage("LFP_SOCNET_LOG_DATA_COMMENT_TITLE"));

		$title = str_replace(
			array("#TITLE#", "#ENTITY#"),
			array($news_tmp, ($bMail ? $arResult["ENTITY"]["FORMATTED"] : $arResult["ENTITY"]["FORMATTED"]["NAME"])),
			$title_tmp
		);

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => ($bMail || $arParams["USE_COMMENT"] != "Y" ? $title : ""),
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["MESSAGE"])
		);

		if ($bMail)
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arLog, true);
			if (strlen($url) > 0)
			{
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
			}
		}
		else
		{
			static $parserLog = false;
			if (CModule::IncludeModule("forum"))
			{
				$arAllow = array(
					"HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "LOG_IMG" => "N",
					"QUOTE" => "Y", "LOG_QUOTE" => "N",
					"CODE" => "Y", "LOG_CODE" => "N",
					"FONT" => "Y", "LOG_FONT" => "N",
					"LIST" => "Y",
					"SMILES" => "Y",
					"NL2BR" => "Y",
					"MULTIPLE_BR" => "N",
					"VIDEO" => "Y", "LOG_VIDEO" => "N",
					"USERFIELDS" => $arFields["UF"],
					"USER" => ($arParams["IM"] == "Y" ? "N" : "Y")
				);

				if (!$parserLog)
				{
					$parserLog = new forumTextParser(LANGUAGE_ID);
				}

				$parserLog->pathToUser = $parserLog->userPath = $arParams["PATH_TO_USER"];
				$parserLog->arUserfields = $arFields["UF"];
				$parserLog->bMobile = ($arParams["MOBILE"] == "Y");

				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), $arAllow));
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = preg_replace("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/is".BX_UTF_PCRE_MODIFIER, "\\2", $arResult["EVENT_FORMATTED"]["MESSAGE"]);
			}
			else
			{
				$arAllow = array(
					"HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "LOG_IMG" => "N",
					"QUOTE" => "Y", "LOG_QUOTE" => "N",
					"CODE" => "Y", "LOG_CODE" => "N",
					"FONT" => "Y", "LOG_FONT" => "N",
					"LIST" => "Y",
					"SMILES" => "Y",
					"NL2BR" => "Y",
					"MULTIPLE_BR" => "N",
					"VIDEO" => "Y", "LOG_VIDEO" => "N",
					"USERFIELDS" => $arFields["UF"],
					"USER" => "Y"
				);

				if (!$parserLog)
					$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);

				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));
			}

			if (
				$arParams["MOBILE"] != "Y" 
				&& $arParams["NEW_TEMPLATE"] != "Y"
			)
			{
				if (CModule::IncludeModule("forum"))			
					$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
						$parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), $arAllow),
						500
					);
				else
					$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
						$parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow),
						500
					);

				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
			}
		}

		return $arResult;
	}

	function GetEntity_Data($arFields, $bMail)
	{
		$arEntity = array();
		$arEventParams = unserialize(strlen($arFields["~PARAMS"]) > 0 ? $arFields["~PARAMS"] : $arFields["PARAMS"]);

		global $arProviders;

		if (!$arProviders)
			$arProviders = array();

		if (intval($arFields["ENTITY_ID"]) > 0)
		{
			if (array_key_exists($arFields["ENTITY_ID"], $arProviders))
			{
				if ($bMail)
					$arEntity["FORMATTED"] = $arProviders[$arFields["ENTITY_ID"]]["NAME"];
				else
					$arEntity["FORMATTED"]["NAME"] = $arProviders[$arFields["ENTITY_ID"]]["NAME"];
			}
			else
			{
				$rsScheme = CXDILFScheme::GetByID($arFields["ENTITY_ID"]);
				if ($arScheme = $rsScheme->GetNext())
				{
					if ($bMail)
						$arEntity["FORMATTED"] = $arProviders[$arFields["ENTITY_ID"]]["NAME"] = $arScheme["NAME"];
					else
					{
						if(defined("BX_COMP_MANAGED_CACHE"))
							$GLOBALS["CACHE_MANAGER"]->RegisterTag("XDI_SCHEME_".$arScheme["ID"]);

						$arEntity["FORMATTED"]["NAME"] = $arProviders[$arFields["ENTITY_ID"]]["NAME"] = $arScheme["NAME"];
					}
				}
			}
		}

		return $arEntity;
	}

}
?>