<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

IncludeModuleLangFile(__FILE__);

class CIntranetNotify
{
	public static function NewUserMessage($USER_ID)
	{
		static $uniqueIdCache = [];

		global $DB;

		if (!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}

		$USER_ID = intval($USER_ID);
		if ($USER_ID <= 0)
		{
			return false;
		}

		$arRights = self::GetRights($USER_ID);
		if (!$arRights)
		{
			return false;
		}

		$blockNewUserLF = COption::GetOptionString("intranet", "BLOCK_NEW_USER_LF_SITE", false);
		if (!$blockNewUserLF)
		{
			$blockNewUserLF = COption::GetOptionString("intranet", "BLOCK_NEW_USER_LF", "N");
		}

		if ($blockNewUserLF != "Y")
		{
			$dbRes = CUser::GetList($by="ID", $order="asc", array("ID_EQUAL_EXACT" => $USER_ID), array("FIELDS" => array("EXTERNAL_AUTH_ID"), "SELECT" => array("UF_DEPARTMENT")));
			if (
				($arUser = $dbRes->Fetch())
				&& (!in_array($arUser["EXTERNAL_AUTH_ID"], Array('bot', 'imconnector')))
			)
			{
				$bExtranetUser = (
					IsModuleInstalled("extranet")
					&& (
						!isset($arUser['UF_DEPARTMENT'])
						|| !is_array($arUser['UF_DEPARTMENT'])
						|| empty($arUser['UF_DEPARTMENT'])
					)
				);

				$uniqueId = round((microtime(true) - mktime(0,0,0,1,1,2017))*10);
				while (in_array($uniqueId, $uniqueIdCache))
				{
					$uniqueId += 10000000;
				}
				$uniqueIdCache[] = $uniqueId;

				$arSoFields = array(
					"ENTITY_TYPE" => SONET_INTRANET_NEW_USER_ENTITY,
					"EVENT_ID" => SONET_INTRANET_NEW_USER_EVENT_ID,
					"ENTITY_ID" => $USER_ID,
					"SOURCE_ID" => $USER_ID,
					"USER_ID" => $USER_ID,
					"=LOG_DATE" => $DB->CurrentTimeFunction(),
					"MODULE_ID" => "intranet",
					"TITLE_TEMPLATE" => "#TITLE#",
					"TITLE" => Loc::getMessage($bExtranetUser ? 'I_NEW_USER_EXTERNAL_TITLE' : 'I_NEW_USER_TITLE'),
					"MESSAGE" => '',
					"TEXT_MESSAGE" => '',
					"CALLBACK_FUNC" => false,
					"SITE_ID" => SITE_ID,
					"ENABLE_COMMENTS" => "Y", //!!!
					"RATING_TYPE_ID" => "INTRANET_NEW_USER",
					"RATING_ENTITY_ID" => $uniqueId,
				);

				// check earlier messages for this user
				$res = CSocNetLog::getList(
					array(),
					array(
						'ENTITY_TYPE' => $arSoFields['ENTITY_TYPE'],
						'ENTITY_ID' => $arSoFields['ENTITY_ID'],
						'EVENT_ID' => $arSoFields['EVENT_ID'],
						'SOURCE_ID' => $arSoFields['SOURCE_ID'],
					),
					false,
					false,
					array('ID')
				);
				while($logEntry = $res->fetch())
				{
					CSocNetLog::delete($logEntry['ID']);
				}

				$logID = CSocNetLog::add($arSoFields, false);

				if (intval($logID) > 0)
				{
					$arFields = array(
						"TMP_ID" => $logID
					);

					if (
						$bExtranetUser
						&& Loader::includeModule("extranet")
					)
					{
						$arFields["SITE_ID"] = CExtranet::getSitesByLogDestinations($arRights);
					}
					elseif (defined("ADMIN_SECTION") && ADMIN_SECTION === true)
					{
						$site = CSocNetLogComponent::getSiteByDepartmentId($arUser["UF_DEPARTMENT"]);
						if ($site)
						{
							$arFields["SITE_ID"] = array($site['LID']);
						}
					}

					CSocNetLog::Update($logID, $arFields);
					CSocNetLogRights::Add($logID, $arRights);
					CSocNetLog::SendEvent($logID, "SONET_NEW_EVENT", $logID);
				}
			}
		}

		return false;
	}

	public static function OnAfterSocNetLogCommentAdd($ID, $arFields)
	{
		if (
			$arFields['ENTITY_TYPE'] == SONET_INTRANET_NEW_USER_ENTITY
			&& $arFields['EVENT_ID'] == SONET_INTRANET_NEW_USER_COMMENT_EVENT_ID
		)
		{
			$arUpdateFields = array(
				'RATING_TYPE_ID' => 'INTRANET_NEW_USER_COMMENT',
				'RATING_ENTITY_ID' => $ID,
			);

			CSocNetLogComments::Update($ID, $arUpdateFields);
		}
	}

	public static function OnFillSocNetLogEvents(&$arSocNetLogEvents)
	{
		$arSocNetLogEvents[SONET_INTRANET_NEW_USER_EVENT_ID] = array(
			"ENTITIES" => array(
				SONET_INTRANET_NEW_USER_ENTITY => array(
					'TITLE' => GetMessage('I_NEW_USER_TITLE_SETTINGS'),
					"TITLE_SETTINGS_1" => "#TITLE#",
					"TITLE_SETTINGS_2" => "#TITLE#",
					"TITLE_SETTINGS_ALL" => GetMessage('I_NEW_USER_TITLE_SETTINGS'),
					"TITLE_SETTINGS_ALL_1" => GetMessage('I_NEW_USER_TITLE_SETTINGS'),
					"TITLE_SETTINGS_ALL_2" => GetMessage('I_NEW_USER_TITLE_SETTINGS'),
				),
			),
			"CLASS_FORMAT" => "CIntranetNotify",
			"METHOD_FORMAT" => "FormatEvent",
			"HAS_CB" => 'Y',
			"FULL_SET" => array(SONET_INTRANET_NEW_USER_EVENT_ID, SONET_INTRANET_NEW_USER_COMMENT_EVENT_ID),
			"COMMENT_EVENT" => array(
				"EVENT_ID" => SONET_INTRANET_NEW_USER_COMMENT_EVENT_ID,
				"UPDATE_CALLBACK" => "NO_SOURCE",
				"DELETE_CALLBACK" => "NO_SOURCE",
				"CLASS_FORMAT" => "CIntranetNotify",
				"METHOD_FORMAT" => "FormatComment",
				"RATING_TYPE_ID" => "LOG_COMMENT"
			)
		);
	}

	public static function OnFillSocNetAllowedSubscribeEntityTypes(&$arSocNetEntityTypes)
	{
		$arSocNetEntityTypes[] = SONET_INTRANET_NEW_USER_ENTITY;

		global $arSocNetAllowedSubscribeEntityTypesDesc;
		$arSocNetAllowedSubscribeEntityTypesDesc[SONET_INTRANET_NEW_USER_ENTITY] = array(
			"TITLE_LIST" => GetMessage('I_NEW_USER_TITLE_LIST'),
			"TITLE_ENTITY" => GetMessage('I_NEW_USER_TITLE_LIST'),
			"CLASS_DESC_GET" => "CIntranetNotify",
			"METHOD_DESC_GET" => "GetByID",
			"CLASS_DESC_SHOW" => "CIntranetNotify",
			"METHOD_DESC_SHOW" => "GetForShow",
		);
	}

	public static function GetByID($ID)
	{
		$ID = IntVal($ID);
		$dbUser = CUser::GetByID($ID);
		if ($arUser = $dbUser->GetNext())
		{
			$arUser["NAME_FORMATTED"] = CUser::FormatName(CSite::GetNameFormat(false), $arUser);
			$arUser["~NAME_FORMATTED"] = htmlspecialcharsback($arUser["NAME_FORMATTED"]);
			return $arUser;
		}
		else
			return false;
	}

	public static function GetForShow($arDesc)
	{
		return htmlspecialcharsback($arDesc["NAME_FORMATTED"]);
	}


	public static function FormatEvent($arFields, $arParams, $bMail = false)
	{
		global $CACHE_MANAGER, $APPLICATION;

		$arResult = array(
			"EVENT" => $arFields
		);

		$user_url = str_replace('#user_id#', $arFields['ENTITY_ID'], $arParams['PATH_TO_USER']);

		$dbRes = CUser::GetByID($arFields['ENTITY_ID']);
		$arUser = $dbRes->Fetch();

		if (
			$arUser
			&& (
				IsModuleInstalled("extranet")
				|| (
					!empty($arUser['UF_DEPARTMENT'])
					&& is_array($arUser['UF_DEPARTMENT'])
					&& intval($arUser['UF_DEPARTMENT'][0]) > 0
				) // for uninstalled extranet module / b24
			)
		)
		{
			if(!$bMail)
			{
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->RegisterTag("USER_NAME_".intval($arUser["ID"]));
				}

				$bExtranetUser = (
					IsModuleInstalled("extranet")
					&& (
						!isset($arUser['UF_DEPARTMENT'])
						|| !is_array($arUser['UF_DEPARTMENT'])
						|| empty($arUser['UF_DEPARTMENT'])
					)
				);

				ob_start();
				$APPLICATION->IncludeComponent('bitrix:intranet.livefeed.newuser', '', array(
					'USER' => $arUser,
					'PARAMS' => $arParams,
					'AVATAR_SRC' => CSocNetLog::FormatEvent_CreateAvatar($arFields, $arParams, 'CREATED_BY'),
					'USER_URL' => $user_url,
				), null, array('HIDE_ICONS' => 'Y'));
				$html_message = ob_get_contents();
				ob_end_clean();

				$arResult = array(
					'EVENT' => $arFields,
					'EVENT_FORMATTED' => array(
						'TITLE' => ($bExtranetUser ? GetMessage('I_NEW_USER_EXTERNAL_TITLE') : GetMessage('I_NEW_USER_TITLE')),
						'TITLE_24' => ($bExtranetUser ? GetMessage('I_NEW_USER_EXTERNAL_TITLE') : GetMessage('I_NEW_USER_TITLE')),
						"MESSAGE" => $html_message,
						"SHORT_MESSAGE" => $html_message,
						'IS_IMPORTANT' => true,
						'STYLE' => 'new-employee',
						'AVATAR_STYLE' => 'avatar-info'
					),
				);

				if (Loader::includeModule('bitrix24'))
				{
					$arResult['CREATED_BY']['FORMATTED'] = (
						$arParams["MOBILE"] == "Y"
							? htmlspecialcharsEx(self::GetSiteName())
							: '<a href="'.BITRIX24_PATH_COMPANY_STRUCTURE_VISUAL.'">'.htmlspecialcharsEx(self::GetSiteName()).'</a>'
					);
				}
				else
				{
					$arResult['CREATED_BY']['FORMATTED'] = '';
					if (is_array($arUser['UF_DEPARTMENT']) && count($arUser['UF_DEPARTMENT']) > 0)
					{
						if ($arParams["MOBILE"] == "Y")
						{
							$url = "";
						}
						else
						{
							$url = $arParams['PATH_TO_CONPANY_DEPARTMENT'];
							if (strlen($url) <= 0)
							{
								$url = $arParams['PATH_TO_COMPANY_DEPARTMENT'];
							}
						}

						$dbRes = CIBlockSection::GetList(array('ID' => 'ASC'), array('ID' => $arUser['UF_DEPARTMENT']));
						if ($arSection = $dbRes->fetch())
						{
							$arResult['CREATED_BY']['FORMATTED'] = (
								strlen($url) > 0
									? '<a href="'.str_replace('#ID#', $arSection['ID'], $url).'">'.htmlspecialcharsEx($arSection['NAME']).'</a>'
									: htmlspecialcharsEx($arSection['NAME'])
							);
						}
					}

					if (strlen($arResult['CREATED_BY']['FORMATTED']) <= 0)
					{
						$arResult['CREATED_BY']['FORMATTED'] = htmlspecialcharsEx(self::GetSiteName());
					}
				}

				$arResult['ENTITY']['FORMATTED']["NAME"] = ($bExtranetUser ? GetMessage('I_NEW_USER_EXTERNAL_TITLE') : GetMessage('I_NEW_USER_TITLE'));
				$arResult['ENTITY']['FORMATTED']["URL"] = $user_url;

				if (
					$arParams["MOBILE"] != "Y"
					&& $arParams["NEW_TEMPLATE"] != "Y"
				)
				{
					$arResult['EVENT_FORMATTED']['IS_MESSAGE_SHORT'] = CSocNetLogTools::FormatEvent_IsMessageShort($arFields['MESSAGE']);
				}
			}
		}
		else
		{
			$arResult = false;
		}

		return $arResult;
	}

	public static function FormatComment($arFields, $arParams, $bMail = false, $arLog = array())
	{
		$arResult = array(
			"EVENT_FORMATTED" => array(),
		);

		if (!CModule::IncludeModule("socialnetwork"))
			return $arResult;

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => GetMessage('I_NEW_USER_TITLE'),
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["MESSAGE"])
		);

		$arResult["ENTITY"]["TYPE_MAIL"] = GetMessage('I_NEW_USER_TITLE');
		if ($bMail)
		{

		}
		else
		{
			static $parserLog = false;
			if (CModule::IncludeModule("forum"))
			{
				if (!$parserLog)
				{
					$parserLog = new forumTextParser(LANGUAGE_ID);
				}

				$arAllow = array(
					"HTML" => "N",
					"ALIGN" => "Y",
					"ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "QUOTE" => "Y",
					"CODE" => "Y", "FONT" => "Y",
					"LIST" => "Y", "SMILES" => "Y",
					"NL2BR" => "Y", "VIDEO" => "Y",
					"LOG_VIDEO" => "N", "SHORT_ANCHOR" => "Y",
					"USERFIELDS" => $arFields["UF"],
					"USER" => ($arParams["IM"] == "Y" ? "N" : "Y")
				);

				$parserLog->pathToUser = $parserLog->userPath = $arParams["PATH_TO_USER"];
				$parserLog->arUserfields = $arFields["UF"];
				$parserLog->bMobile = ($arParams["MOBILE"] == "Y");
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), $arAllow));
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = preg_replace("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/is".BX_UTF_PCRE_MODIFIER, "\\2", $arResult["EVENT_FORMATTED"]["MESSAGE"]);
			}
			else
			{
				if (!$parserLog)
				{
					$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
				}

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

	protected static function GetRights($USER_ID)
	{
		$bExtranetUser = false;
		if (IsModuleInstalled("extranet"))
		{
			$rsUser = CUser::GetByID($USER_ID);
			if ($arUser = $rsUser->Fetch())
			{
				if (intval($arUser["UF_DEPARTMENT"][0]) <= 0)
				{
					$bExtranetUser = true;
				}
			}
		}

		if ($bExtranetUser && CModule::IncludeModule("socialnetwork"))
		{
			$rsSocNetUserToGroup = CSocNetUserToGroup::GetList(
				array(),
				array("USER_ID" => $USER_ID),
				false,
				false,
				array("GROUP_ID")
			);
			
			$arResult = array();
			while ($arSocNetUserToGroup = $rsSocNetUserToGroup->Fetch())
			{
				$arResult[] = "SG".$arSocNetUserToGroup["GROUP_ID"]."_".SONET_ROLES_USER;
				$arResult[] = "SG".$arSocNetUserToGroup["GROUP_ID"]."_".SONET_ROLES_MODERATOR;
				$arResult[] = "SG".$arSocNetUserToGroup["GROUP_ID"]."_".SONET_ROLES_OWNER;
			}
			return $arResult;
		}
		else
		{
			return array("G2");
		}
	}

	protected static function GetSiteName()
	{
		return COption::GetOptionString("main", "site_name", "");
	}

	public static function OnSendMentionGetEntityFields($arCommentFields)
	{
		if ($arCommentFields["EVENT_ID"] != SONET_INTRANET_NEW_USER_COMMENT_EVENT_ID)
		{
			return false;
		}

		if (!CModule::IncludeModule("socialnetwork"))
		{
			return true;
		}

		$dbLog = CSocNetLog::GetList(
			array(),
			array(
				"ID" => $arCommentFields["LOG_ID"],
				"EVENT_ID" => SONET_INTRANET_NEW_USER_EVENT_ID
			),
			false,
			false,
			array("ID", "USER_ID")
		);

		if (
			($arLog = $dbLog->Fetch())
			&& (intval($arLog["USER_ID"]) > 0)
		)
		{
			$genderSuffix = "";
			$dbUsers = CUser::GetList(($by="ID"), ($order="desc"), array("ID" => $arCommentFields["USER_ID"].' | '.$arLog["USER_ID"]), array("PERSONAL_GENDER", "LOGIN", "NAME", "LAST_NAME", "SECOND_NAME"));
			while ($arUser = $dbUsers->Fetch())
			{
				if ($arUser["ID"] == $arCommentFields["USER_ID"])
				{
					$genderSuffix = $arUser["PERSONAL_GENDER"];
				}
				if ($arUser["ID"] == $arLog["USER_ID"])
				{
					$nameFormatted = CUser::FormatName(CSite::GetNameFormat(), $arUser);
				}
			}

			$strPathToLogEntry = str_replace("#log_id#", $arLog["ID"], COption::GetOptionString("socialnetwork", "log_entry_page", "/company/personal/log/#log_id#/", SITE_ID));
			$strPathToLogEntryComment = $strPathToLogEntry.(strpos($strPathToLogEntry, "?") !== false ? "&" : "?")."commentID=".$arCommentFields["ID"];

			$arReturn = array(
				"URL" => $strPathToLogEntryComment,
				"NOTIFY_MODULE" => "intranet",
				"NOTIFY_TAG" => "INTRANET_NEW_USER|COMMENT_MENTION|".$arCommentFields["ID"],
				"NOTIFY_MESSAGE" => GetMessage("I_NEW_USER_MENTION".(strlen($genderSuffix) > 0 ? "_".$genderSuffix : ""), Array("#title#" => "<a href=\"#url#\" class=\"bx-notifier-item-action\">".$nameFormatted."</a>")),
				"NOTIFY_MESSAGE_OUT" => GetMessage("I_NEW_USER_MENTION".(strlen($genderSuffix) > 0 ? "_".$genderSuffix : ""), Array("#title#" => $nameFormatted))." ("."#server_name##url#)"
			);

			return $arReturn;
		}
		else
		{
			return false;
		}
	}
}
?>