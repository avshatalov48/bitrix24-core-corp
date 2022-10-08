<?php

define("SOCSERV_AUTHORISATION_ERROR", 1);
define("SOCSERV_REGISTRATION_DENY", 2);
define("SOCSERV_DEFAULT_HTTP_TIMEOUT", 10);

require_once __DIR__.'/autoload.php';

$arJSDescription = array(
	'js' => '/bitrix/js/socialservices/ss_timeman.js',
	'css' => '/bitrix/js/socialservices/css/ss.css',
	'rel' => ['ui.design-tokens', 'popup', 'ajax', 'fx', 'ls', 'date', 'json'],
	'lang' => '/bitrix/modules/socialservices/lang/'.LANGUAGE_ID.'/js_socialservices.php'
	);

if(IsModuleInstalled("timeman"))
{
	$userSocServEnable = CSocServAuthManager::GetCachedUserOption("user_socserv_enable");
	if($userSocServEnable != '')
		$arJSDescription['lang_additional'] = array('IS_ENABLED' => $userSocServEnable);
}

CJSCore::RegisterExt('socserv_timeman', $arJSDescription);

class CSocServEventHandlers
{
	public static function OnFillSocNetLogEvents(&$arSocNetLogEvents)
	{
		$arSocNetLogEvents["twitter"] = array(
			"ENTITIES" =>	array(
				SONET_SUBSCRIBE_ENTITY_USER => array(
					"OPERATION" => "viewprofile"
				),
				SONET_SUBSCRIBE_ENTITY_GROUP => array(
					"OPERATION" => "viewsystemevents"
				),
			),
			"CLASS_FORMAT" => "CSocServEventHandlers",
			"METHOD_FORMAT" => "FormatEvent_Data",
			"FULL_SET" => array("data", "data_comment"),
			"COMMENT_EVENT" => array(
				"EVENT_ID" => "data_comment",
				"CLASS_FORMAT" => "CSocServEventHandlers",
				"METHOD_FORMAT"	=> "FormatComment_Data",
				"RATING_TYPE_ID" => "LOG_COMMENT"
			)
		);
	}

	public static function FormatEvent_Data($arFields, $arParams, $bMail = false)
	{
		$arResult = array(
			"EVENT" => $arFields,
			"URL" => ""
		);

		if (!CModule::IncludeModule("socialnetwork"))
			return $arResult;

		if (in_array($arFields["ENTITY_TYPE"], array(SONET_SUBSCRIBE_ENTITY_GROUP, SONET_SUBSCRIBE_ENTITY_USER)))
		{
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, $bMail);

			$rsRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arFields["ID"]));
			$arRights = array();
			while ($arRight = $rsRight->Fetch())
				$arRights[] = $arRight["GROUP_CODE"];

			$arDestination = CSocNetLogTools::FormatDestinationFromRights($arRights, $arParams, $iMoreCount);
		}

		$title = "";

		$arEventParams = unserialize($arFields["~PARAMS"] <> '' ? $arFields["~PARAMS"] : $arFields["PARAMS"], ['allowed_classes' => [
			\Bitrix\Main\Type\DateTime::class,
			\Bitrix\Main\Type\Date::class,
			\Bitrix\Main\Web\Uri::class,
			\DateTime::class,
			\DateTimeZone::class,
		]]);

		if (
			in_array($arFields["ENTITY_TYPE"], array(SONET_SUBSCRIBE_ENTITY_GROUP, SONET_SUBSCRIBE_ENTITY_USER))
			&& is_array($arEventParams)
			&& count($arEventParams) > 0
			&& array_key_exists("ENTITY_NAME", $arEventParams)
			&& $arEventParams["ENTITY_NAME"] <> ''
		)
		{
			if (!$bMail && $arFields["URL"] <> '')
				$title_tmp = '<a href="'.$arFields["URL"].'">'.$arEventParams["ENTITY_NAME"].'</a>';
			else
				$title_tmp = $arEventParams["ENTITY_NAME"];
		}
		else
		{
			if (!$bMail && $arFields["URL"] <> '')
				$title_tmp = '<a href="'.$arFields["URL"].'">'.$arFields["TITLE"].'</a>';
			else
				$title_tmp = $arFields["TITLE"];
		}

		$title = str_replace(
			array("#TITLE#", "#ENTITY#"),
			array($title_tmp, ($bMail ? $arResult["ENTITY"]["FORMATTED"] : $arResult["ENTITY"]["FORMATTED"]["NAME"])),
			($bMail ? GetMessage("LFP_SOCNET_LOG_DATA_".$arFields["ENTITY_TYPE"]."_TITLE_MAIL") : GetMessage("LFP_SOCNET_LOG_DATA_TITLE"))
		);

		$url = false;

		if ($arFields["URL"] <> '')
			$url = $arFields["URL"];

		if (in_array($arFields["ENTITY_TYPE"], array(SONET_SUBSCRIBE_ENTITY_GROUP, SONET_SUBSCRIBE_ENTITY_USER)))
		{
			if (!$bMail)
				$message = $arFields["MESSAGE"];
			else
				$message = $arFields["TITLE"]."#BR##BR#".$arFields["MESSAGE"];
		}
		else
			$message = $arFields["MESSAGE"];

		$arFieldsTooltip = array(
			'ID' => $arFields['USER_ID'],
			'NAME' => $arFields['~CREATED_BY_NAME'],
			'LAST_NAME' => $arFields['~CREATED_BY_LAST_NAME'],
			'SECOND_NAME' => $arFields['~CREATED_BY_SECOND_NAME'],
			'LOGIN' => $arFields['~CREATED_BY_LOGIN'],
		);
		$arResult['CREATED_BY']['TOOLTIP_FIELDS'] = CSocNetLog::FormatEvent_FillTooltip($arFieldsTooltip, $arParams);
		$twitInfo = unserialize($arFields['~PARAMS'], ['allowed_classes' => [
			\Bitrix\Main\Type\DateTime::class,
			\Bitrix\Main\Type\Date::class,
			\Bitrix\Main\Web\Uri::class,
			\DateTime::class,
			\DateTimeZone::class,
		]]);

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => $arFields["TITLE"],
			"TITLE_24" => "",
			"MESSAGE" => "<a  target=\"_blank\" style=\"text-decoration: none; color: #5C6470; font-weight: bold; font-size: 12px\" href=\"https://twitter.com/".$twitInfo['SCREEN_NAME']."/status/".$twitInfo['TWIT_ID']."\">".$arFields["TITLE"]."</a><p>".($bMail ? CSocNetTextParser::killAllTags($message) : $message),
			"IS_IMPORTANT" => false,
			"STYLE" => "",
			"DESTINATION" => $arDestination
		);

		if (intval($iMoreCount) > 0)
			$arResult["EVENT_FORMATTED"]["DESTINATION_MORE"] = $iMoreCount;

		if (
			is_array($arEventParams)
			&& array_key_exists("SOURCE_TIMESTAMP", $arEventParams)
		)
			$arResult["EVENT_FORMATTED"]["LOG_DATE_FORMAT"] = ConvertTimeStamp($arEventParams["SOURCE_TIMESTAMP"], "FULL");

		if ($url <> '')
			$arResult["EVENT_FORMATTED"]["URL"] = $url;

		if (!$bMail)
		{
			if ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP)
			{
				$arGroup = array(
					"IMAGE_ID" => $arFields["GROUP_IMAGE_ID"]
				);
				$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatarGroup($arGroup, $arParams);
			}
			elseif ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_USER)
				$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams, "USER_");
			elseif ($arFields["ENTITY_TYPE"] == "N")
				$arResult["EVENT_FORMATTED"]["AVATAR_STYLE"] = "avatar-info";

			$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
			$arAllow = array(
				"HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y",
				"IMG" => "Y", "LOG_IMG" => "N",
				"QUOTE" => "Y", "LOG_QUOTE" => "N",
				"CODE" => "Y", "LOG_CODE" => "N",
				"FONT" => "Y", "LOG_FONT" => "N",
				"LIST" => "Y",
				"SMILES" => "Y",
				"NL2BR" => "N",
				"MULTIPLE_BR" => "Y",
				"VIDEO" => "Y", "LOG_VIDEO" => "N"
			);

			$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
				$parserLog->convert(htmlspecialcharsback(str_replace("#CUT#",	"", $arResult["EVENT_FORMATTED"]["MESSAGE"])), array(), $arAllow),
				500
			);

			$arAllow = array("HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "MULTIPLE_BR" => "Y", "VIDEO" => "Y", "LOG_VIDEO" => "N");
			$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));

			if (
				$arParams["MOBILE"] != "Y"
				&& $arParams["NEW_TEMPLATE"] != "Y"
			)
				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
		}

		return $arResult;
	}

	public static function GetEntity_Data($arFields, $bMail)
	{
		$arEntity = array();

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
						$arEntity["FORMATTED"]["NAME"] = $arProviders[$arFields["ENTITY_ID"]]["NAME"] = $arScheme["NAME"];
				}
			}
		}

		return $arEntity;
	}

	public static function FormatComment_Data($arFields, $arParams, $bMail = false, $arLog = array())
	{
		$arResult = array(
			"EVENT_FORMATTED"	=> array(),
		);

		if (!CModule::IncludeModule("socialnetwork"))
			return $arResult;

		if (in_array($arLog["ENTITY_TYPE"], array(SONET_SUBSCRIBE_ENTITY_GROUP, SONET_SUBSCRIBE_ENTITY_USER)))
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arLog, $arParams, $bMail);

		if(!$bMail && $arParams["USE_COMMENT"] != "Y")
		{
			$arLog["ENTITY_ID"] = $arFields["ENTITY_ID"];
			$arLog["ENTITY_TYPE"] = $arFields["ENTITY_TYPE"];
		}

		if (
			!$bMail
			&& array_key_exists("URL", $arLog)
			&& $arLog["URL"] <> ''
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
			"TITLE" => ($bMail || $arParams["USE_COMMENT"] != "Y" ? "<a href=\"asdfasdf\">".$title."</a>" : ""),
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["MESSAGE"])
		);

		if ($bMail)
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arLog, true);
			if ($url <> '')
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}
		else
		{
			$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
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
				"VIDEO" => "Y", "LOG_VIDEO" => "N"
			);

			$arAllow = array("HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "LOG_VIDEO" => "N");
			$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));

			if (
				$arParams["MOBILE"] != "Y"
				&& $arParams["NEW_TEMPLATE"] != "Y"
			)
			{
				$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
					$parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow),
					500
				);
				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
			}
		}

		return $arResult;
	}

	public static function OnTimeManShow()
	{
		if(COption::GetOptionString("socialservices", "allow_send_user_activity", "Y") == 'Y')
			CJSCore::Init(array('socserv_timeman'));
	}

	public static function OnUserLogout(&$arParams)
	{
		CSocServAuthManager::UnsetAuthorizedServiceId();
	}
}
