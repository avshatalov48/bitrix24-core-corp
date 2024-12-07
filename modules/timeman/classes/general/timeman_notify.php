<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable;

IncludeModuleLangFile(__FILE__);

class CTimeManNotify
{
	// $SEND_TYPE = 'U'pdate (only up if exists) OR 'A'dd (only add if not exists)
	public static function SendMessage($ENTRY_ID, $SEND_TYPE = false, $options = [])
	{
		global $DB, $APPLICATION, $USER;
		$sendNotifications = true;
		if (array_key_exists('SEND_NOTIFICATIONS', $options))
		{
			$sendNotifications = (bool)$options['SEND_NOTIFICATIONS'];
		}
		$ENTRY_ID = intval($ENTRY_ID);
		if ($ENTRY_ID <= 0)
		{
			return false;
		}

		if(!CModule::IncludeModule("socialnetwork"))
		{
			return false;
		}

		$dbEntry = CTimeManEntry::GetList(
			array(),
			array(
				"ID" => $ENTRY_ID
			),
			false,
			false,
			array("ID", "USER_ID", "DATE_START", "USER_GENDER", "INACTIVE_OR_ACTIVATED")
		);

		$arEntry = $dbEntry->Fetch();
		if ($arEntry)
		{
			$arRights = self::GetRights($arEntry["USER_ID"]);
			if (!$arRights)
			{
				return false;
			}

			$date = $DB->CurrentTimeFunction();

			$arSoFields = Array(
				"EVENT_ID" => "timeman_entry",
				"=LOG_DATE" => $date,
				"MODULE_ID" => "timeman",
				"TITLE_TEMPLATE" => "#TITLE#",
				"TITLE" => GetMessage("TIMEMAN_NOTIFY_TITLE"),
				"MESSAGE" => '',
				"TEXT_MESSAGE" => '',
				"CALLBACK_FUNC" => false,
				"SOURCE_ID" => $ENTRY_ID,
				"SITE_ID" => SITE_ID,
				"ENABLE_COMMENTS" => "Y", //!!!
				"PARAMS" => serialize(array(
					"FORUM_ID" => COption::GetOptionInt("timeman", "report_forum_id", "")
				))
			);
			$sendFromUserId = null;
			if (array_key_exists('SEND_FROM_USER_ID', $options) && $options['SEND_FROM_USER_ID'] > 0)
			{
				$sendFromUserId = (int)$options['SEND_FROM_USER_ID'];
			}
			$arSoFields["ENTITY_TYPE"] = SONET_TIMEMAN_ENTRY_ENTITY;
			$arSoFields["ENTITY_ID"] = $arEntry["USER_ID"];
			$arSoFields["USER_ID"] = $sendFromUserId === null ? $USER->GetID() : $sendFromUserId;

			$dbRes = CSocNetLog::GetList(array(), array(
				'ENTITY_TYPE' => $arSoFields['ENTITY_TYPE'],
				'ENTITY_ID' => $arSoFields['ENTITY_ID'],
				'EVENT_ID' => $arSoFields['EVENT_ID'],
				'SOURCE_ID' => $arSoFields['SOURCE_ID'],
			));

			$arRes = $dbRes->Fetch();

			$bSend = false;
			if ($arRes)
			{
				$logID = $arRes['ID'];

				if ($SEND_TYPE !== 'A')
				{
					$arSoFields["=LOG_UPDATE"] = $date;

					CSocNetLog::Update($logID, $arSoFields);
					CSocNetLogFollow::DeleteByLogID($logID, "Y", true); // not only delete but update to NULL for existing records

					$bSend = true;

					if (IsModuleInstalled("im") && $sendNotifications)
					{
						$arEntry["LOG_ID"] = $logID;

						if ($SEND_TYPE === "U")
						{
							self::NotifyImApprove($arEntry);
						}
						else
						{
							self::NotifyImNew($arEntry);
						}
					}

				}
			}
			else if ($SEND_TYPE !== 'U')
			{
				$logID = CSocNetLog::Add($arSoFields, false);

				if (intval($logID) > 0)
				{
					CSocNetLog::Update($logID, array("TMP_ID" => $logID));
					CSocNetLogRights::Add($logID, $arRights);

					if (
						$sendNotifications
						&& $arEntry["INACTIVE_OR_ACTIVATED"] === "Y"
						&& IsModuleInstalled("im")
					)
					{
						$arEntry["LOG_ID"] = $logID;
						self::NotifyImNew($arEntry);
					}
					$bSend = true;
				}
			}

			if ($bSend && (int)$logID > 0)
			{
				CSocNetLog::SendEvent($logID, "SONET_NEW_EVENT", $logID);
			}

			return $logID;
		}
	}

	protected static function getFormattedDate($date, ?string $languageId = null): string
	{
		$culture = \Bitrix\Main\Application::getInstance()->getContext()->getCulture();
		$dayMonthFormat = $culture->getDayMonthFormat();

		return FormatDate($dayMonthFormat, MakeTimeStamp($date, FORMAT_DATETIME), false, $languageId);
	}

	protected static function NotifyImNew($arEntry)
	{
		if(!CModule::IncludeModule("im"))
		{
			return false;
		}

		$arMessageFields = array(
			"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
			"FROM_USER_ID" => $arEntry["USER_ID"],
			"NOTIFY_TYPE" => IM_NOTIFY_FROM,
			"NOTIFY_MODULE" => "timeman",
			"NOTIFY_EVENT" => "entry",
			"LOG_ID" => $arEntry["LOG_ID"],
			"NOTIFY_TAG" => "TIMEMAN|ENTRY|".$arEntry["ID"],
		);

		$reports_page = COption::GetOptionString("timeman", "TIMEMAN_REPORT_PATH", "/timeman/timeman.php");

		$gender_suffix = match ($arEntry["USER_GENDER"])
		{
			"M" => "_M",
			"F" => "_F",
			default => "",
		};

		$arManagers = CTimeMan::GetUserManagers($arEntry["USER_ID"]);
		if (is_array($arManagers) && count($arManagers) > 0)
		{
			foreach($arManagers as $managerID)
			{
				$arMessageFields["TO_USER_ID"] = $managerID;
				$arTmp = CSocNetLogTools::ProcessPath(array("REPORTS_PAGE" => $reports_page), $managerID);


				$arMessageFields["NOTIFY_MESSAGE"] = fn (?string $languageId = null) => Loc::getMessage(
					"TIMEMAN_ENTRY_IM_ADD".$gender_suffix,
					[
						"#period#" => "<a href=\"".$arTmp["URLS"]["REPORTS_PAGE"]."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx(self::getFormattedDate($arEntry["DATE_START"], $languageId))."</a>",
					],
					$languageId
				);

				$arMessageFields["NOTIFY_MESSAGE_OUT"] = fn (?string $languageId = null) =>
					Loc::getMessage(
						"TIMEMAN_ENTRY_IM_ADD".$gender_suffix,
						[
							"#period#" => htmlspecialcharsbx(self::getFormattedDate($arEntry["DATE_START"], $languageId)),
						],
						$languageId
					)
					. " (".$arTmp["SERVER_NAME"].$arTmp["URLS"]["REPORTS_PAGE"].")"
				;

				CIMNotify::Add($arMessageFields);
			}
		}

		return true;
	}

	protected static function NotifyImApprove($arEntry)
	{
		if(!CModule::IncludeModule("im"))
		{
			return false;
		}

		$arMessageFields = array(
			"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
			"FROM_USER_ID" => $GLOBALS["USER"]->GetID(),
			"TO_USER_ID" => $arEntry["USER_ID"],
			"NOTIFY_TYPE" => IM_NOTIFY_FROM,
			"NOTIFY_MODULE" => "timeman",
			"NOTIFY_EVENT" => "entry_approve",
			"LOG_ID" => $arEntry["LOG_ID"],
			"NOTIFY_TAG" => "TIMEMAN|ENTRY|".$arEntry["ID"],
		);

		$reports_page = COption::GetOptionString("timeman", "TIMEMAN_REPORT_PATH", "/timeman/timeman.php");
		$gender_suffix = "";

		$dbUser = CUser::GetByID($GLOBALS["USER"]->GetID());
		if ($arUser = $dbUser->Fetch())
		{
			$gender_suffix = match ($arUser["PERSONAL_GENDER"])
			{
				"M" => "_M",
				"F" => "_F",
				default => "",
			};
		}

		$arTmp = CSocNetLogTools::ProcessPath(array("REPORTS_PAGE" => $reports_page), $arEntry["USER_ID"]);

		$arMessageFields["NOTIFY_MESSAGE"] = fn (?string $languageId = null) => Loc::getMessage(
			"TIMEMAN_ENTRY_IM_APPROVE" . $gender_suffix,
			[
				"#period#" => "<a href=\"".$arTmp["URLS"]["REPORTS_PAGE"]."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx(self::getFormattedDate($arEntry["DATE_START"], $languageId))."</a>",
			],
			$languageId
		);

		$arMessageFields["NOTIFY_MESSAGE_OUT"] = fn (?string $languageId = null) =>
			Loc::getMessage(
				"TIMEMAN_ENTRY_IM_APPROVE" . $gender_suffix,
				[
					"#period#" => htmlspecialcharsbx(self::getFormattedDate($arEntry["DATE_START"], $languageId)),
				],
				$languageId
			)
			. " (".$arTmp["SERVER_NAME"].$arTmp["URLS"]["REPORTS_PAGE"].")";

		CIMNotify::Add($arMessageFields);

		return true;
	}

	public static function OnFillSocNetAllowedSubscribeEntityTypes(&$arSocNetEntityTypes)
	{
		$arSocNetEntityTypes[] = SONET_TIMEMAN_ENTRY_ENTITY;

		global $arSocNetAllowedSubscribeEntityTypesDesc;
		$arSocNetAllowedSubscribeEntityTypesDesc[SONET_TIMEMAN_ENTRY_ENTITY] = array(
			"TITLE_LIST" => GetMessage("TIMEMAN_ENTRY_TITLE"),
			"TITLE_ENTITY" =>GetMessage("TIMEMAN_ENTRY_TITLE"),
			"CLASS_DESC_GET" => "CTimeManNotify",
			"METHOD_DESC_GET" => "GetByID",
			"CLASS_DESC_SHOW" => "CTimeManNotify",
			"METHOD_DESC_SHOW" => "GetForShow",
			"USE_CB_FILTER" => "Y",
			"HAS_CB" => "Y",
		);
	}

	public static function OnFillSocNetLogEvents(&$arSocNetLogEvents)
	{
		$arSocNetLogEvents["timeman_entry"] = array(
			"ENTITIES" => array(
				SONET_TIMEMAN_ENTRY_ENTITY => array(
					'TITLE' =>GetMessage("TIMEMAN_ENTRY_TITLE"),
					"TITLE_SETTINGS_1" => "#TITLE#",
					"TITLE_SETTINGS_2" => "#TITLE#",
					"TITLE_SETTINGS_ALL" => GetMessage("TIMEMAN_ENTRY_TITLE"),
					"TITLE_SETTINGS_ALL_1" => GetMessage("TIMEMAN_ENTRY_TITLE"),
					"TITLE_SETTINGS_ALL_2" => GetMessage("TIMEMAN_ENTRY_TITLE")
				),
			),
			"CLASS_FORMAT" => "CTimeManNotify",
			"METHOD_FORMAT" => "FormatEvent",
			"HAS_CB" => 'Y',
			"FULL_SET" => array("timeman_entry", "timeman_entry_comment"),
			"COMMENT_EVENT" => array(
				"EVENT_ID" => "timeman_entry_comment",
				"CLASS_FORMAT" => "CTimeManNotify",
				"METHOD_FORMAT" => "FormatComment",
				"ADD_CALLBACK" => array("CTimeManNotify", "AddComment"),
				"UPDATE_CALLBACK" => array("CSocNetLogTools", "UpdateComment_Forum"),
				"DELETE_CALLBACK" => array("CSocNetLogTools", "DeleteComment_Forum"),
				"RATING_TYPE_ID" => "FORUM_POST"
			)
		);
	}

	public static function GetRights($USER_ID)
	{
		$arRights = array("U".$USER_ID);
		$arManagers = CTimeMan::GetUserManagers($USER_ID);
		if (is_array($arManagers) && count($arManagers) > 0)
		foreach($arManagers as $mID)
		{
			// if ($mID == $USER_ID)
				// return false;

			$arRights[] = "U".$mID;
		}

		return array_unique($arRights);
		//return array("G2");
	}

	public static function GetByID($ID)
	{
		$ID = intval($ID);
		$dbUser = CUser::GetByID($ID);
		if ($arUser = $dbUser->GetNext())
		{
			$arUser["NAME_FORMATTED"] = CUser::FormatName(CSite::GetNameFormat(false), $arUser, true);
			$arUser["~NAME_FORMATTED"] = GetMessage("TIMEMAN_ENTRY_TITLE2").htmlspecialcharsback($arUser["NAME_FORMATTED"]);
			return $arUser;
		}
		else
			return false;
	}

	public static function GetForShow($arDesc)
	{
		return GetMessage("TIMEMAN_ENTRY_TITLE2").htmlspecialcharsback($arDesc["NAME_FORMATTED"]);
	}

	public static function FormatEvent($arFields, $arParams, $bMail = false)
	{
		global $APPLICATION, $CACHE_MANAGER;

		$arResult = array();

		$user_url = (($arParams["PATH_TO_USER"] ?? '') <> ''
			? $arParams["PATH_TO_USER"]
			: COption::GetOptionString(
				'intranet',
				'path_user',
				'/company/personal/user/#USER_ID#/',
				$arFields["SITE_ID"]
			)
		);

		$arManagers = CTimeMan::GetUserManagers($arFields["ENTITY_ID"]);
		$arManagers[] = $arFields["ENTITY_ID"];
		$arManagers[] = $arFields["USER_ID"];
		$arManagers = array_unique($arManagers);

		$dbEntry = CTimeManEntry::GetList(array(), array('ID' => $arFields["SOURCE_ID"]), false, false, array('DATE_START', 'INACTIVE_OR_ACTIVATED', 'ACTIVE'));
		$arEntry = $dbEntry->Fetch();

		$dbManagers = CUser::GetList(
			'ID', 'ASC',
			['ID' => implode('|', $arManagers)],
			[
				'FIELDS' => [
					'ID',
					'LOGIN',
					'NAME',
					'LAST_NAME',
					'SECOND_NAME',
					'TITLE',
					'SECOND_NAME',
					'EMAIL',
					'WORK_POSITION',
					'PERSONAL_PHOTO',
					'PERSONAL_GENDER',
				]
			]
		);

		$arCurrentUserManagers = array();
		$arUser = array();
		$arChanger = array();
		while ($manager = $dbManagers->GetNext())
		{
			$info = array(
				'ID' => $manager['ID'],
				'LOGIN' => $manager['LOGIN'],
				'NAME' => CUser::FormatName(CSite::GetNameFormat(false), $manager, true, false),
				'URL' => str_replace(array('#ID#', '#USER_ID#', '#id#', '#user_id#'), $manager['ID'], $user_url),
				'WORK_POSITION' => $manager['WORK_POSITION'],
				'PERSONAL_PHOTO' => $manager['PERSONAL_PHOTO'],
				'PERSONAL_GENDER' => $manager['PERSONAL_GENDER']
			);

			if (intval($info["PERSONAL_PHOTO"]) <= 0)
			{
				switch($info["PERSONAL_GENDER"])
				{
					case "M":
						$suffix = "male";
						break;
					case "F":
						$suffix = "female";
						break;
					default:
						$suffix = "unknown";
				}
				$info["PERSONAL_PHOTO"] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, SITE_ID);
			}

			if ($manager['ID'] == $arFields["ENTITY_ID"])
				$arUser = $info;

			if ($manager['ID'] == $arFields["USER_ID"])
				$arChanger = $info;

			if (($manager['ID'] != $arFields["ENTITY_ID"]) || count($arManagers) == 1)
				$arCurrentUserManagers[] = $info;
		}

		$arResult["EVENT"] = $arFields;

		$gender = trim($arChanger['PERSONAL_GENDER']);
		if ($gender == '')
			$gender = 'N';

		$culture = \Bitrix\Main\Application::getInstance()->getContext()->getCulture();
		$dayMonthFormat = $culture->getDayMonthFormat();
		if(!$bMail)
		{
			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->RegisterTag("USER_NAME_".intval($arUser["ID"]));
				$CACHE_MANAGER->RegisterTag("USER_NAME_".intval($arCurrentUserManagers[0]["ID"]));
			}

			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:timeman.livefeed.workday',
				(($arParams["MOBILE"] ?? '') === 'Y' ? 'mobile' : ''),
				[
					'USER' => $arUser,
					'MANAGER' => $arCurrentUserManagers[0],
					'ENTRY' => $arEntry,
					'PARAMS' => $arParams
				],
				null,
				['HIDE_ICONS' => 'Y']
			);
			$html_message = ob_get_contents();
			ob_end_clean();

			if (($arParams["MOBILE"] ?? '') == "Y")
				$arResult = array(
					'EVENT' => $arFields,
					'EVENT_FORMATTED' => array(
						'TITLE_24' => GetMessage('TIMEMAN_ENTRY_LF_TITLE'.($arEntry['INACTIVE_OR_ACTIVATED'] == 'N' ? '_COMMENT' : ($arFields['ENTITY_ID'] == $arFields['USER_ID'] ? '' : '2')).$gender."_24_MOBILE", array(
							'#DATE#' => FormatDate($dayMonthFormat, MakeTimeStamp($arEntry['DATE_START'])),
						)),
						"MESSAGE" => htmlspecialcharsbx($html_message),
						"IS_IMPORTANT" => false,
						"DESCRIPTION" => ($arEntry['INACTIVE_OR_ACTIVATED'] == 'N' ? '' : ($arFields['ENTITY_ID'] == $arFields['USER_ID'] ? GetMessage("TIMEMAN_ENTRY_LF_DESCRIPTION_24_MOBILE") : array(GetMessage("TIMEMAN_ENTRY_LF_DESCRIPTION2_24_MOBILE"), GetMessage("TIMEMAN_ENTRY_LF_DESCRIPTION2_24_MOBILE_VALUE")))),
						"DESCRIPTION_STYLE" => ($arEntry["INACTIVE_OR_ACTIVATED"] == "Y" && $arEntry["ACTIVE"] == "N" ? false : "green")
					)
				);
			else
			{
				$href = \Bitrix\Timeman\Service\DependencyManager::getInstance()
					->getUrlManager()
					->getUriTo(\Bitrix\Timeman\TimemanUrlManager::URI_RECORD_REPORT, [
						'RECORD_ID' => $arFields['SOURCE_ID']
					]);
				$arResult = array(
					'EVENT' => $arFields,
					'EVENT_FORMATTED' => array(
						'TITLE' => GetMessage('TIMEMAN_ENTRY_LF_TITLE'.($arEntry['INACTIVE_OR_ACTIVATED'] == 'N' ? '_COMMENT' : ($arFields['ENTITY_ID'] == $arFields['USER_ID'] ? '' : '2')).$gender, array(
							'#URL#' => $href,
							'#DATE#' => FormatDate($dayMonthFormat, MakeTimeStamp($arEntry['DATE_START'])),
						)),
						'TITLE_24' => GetMessage('TIMEMAN_ENTRY_LF_TITLE'.($arEntry['INACTIVE_OR_ACTIVATED'] == 'N' ? '_COMMENT' : ($arFields['ENTITY_ID'] == $arFields['USER_ID'] ? '' : '2')).$gender."_24", array(
							'#URL#' => $href,
							'#DATE#' => FormatDate($dayMonthFormat, MakeTimeStamp($arEntry['DATE_START'])),
						)),
						'URL' => $href,
						"MESSAGE" => $html_message,
						"SHORT_MESSAGE" => $html_message,
						"IS_IMPORTANT" => false,
						"STYLE" => ($arEntry["INACTIVE_OR_ACTIVATED"] == "Y" && $arEntry["ACTIVE"] == "N" ? "workday-edit" : "workday-confirm")
					)
				);
				$arResult["ENTITY"]["FORMATTED"]["NAME"] = GetMessage("TIMEMAN_NOTIFY_TITLE");
				$arResult["ENTITY"]["FORMATTED"]["URL"] = COption::GetOptionString("timeman","TIMEMAN_REPORT_PATH","/timeman/timeman.php");
			}
			$arResult['AVATAR_SRC'] = CSocNetLog::FormatEvent_CreateAvatar($arFields, $arParams, 'CREATED_BY');

			$arFieldsTooltip = array(
				'ID' => $arFields['USER_ID'],
				'NAME' => $arFields['~CREATED_BY_NAME'],
				'LAST_NAME' => $arFields['~CREATED_BY_LAST_NAME'],
				'SECOND_NAME' => $arFields['~CREATED_BY_SECOND_NAME'],
				'LOGIN' => $arFields['~CREATED_BY_LOGIN'],
			);
			$arResult['CREATED_BY']['TOOLTIP_FIELDS'] = CSocNetLog::FormatEvent_FillTooltip($arFieldsTooltip, $arParams);

			if (
				($arParams["MOBILE"] ?? '') != "Y"
				&& ($arParams["NEW_TEMPLATE"] ?? '') != "Y"
			)
				$arResult['EVENT_FORMATTED']['IS_MESSAGE_SHORT'] = CSocNetLog::FormatEvent_IsMessageShort($arFields['MESSAGE']);
		}
		else
		{
			$URL = COption::GetOptionString("timeman", "TIMEMAN_REPORT_PATH", "/timeman/timeman.php");
			$URL = CSocNetLogTools::FormatEvent_GetURL(array("URL"=>$URL, "SITE_ID"=>$arFields["SITE_ID"]));
			$arResult["ENTITY"]["TYPE_MAIL"] = GetMessage("TIMEMAN_TITLE_FOR_MAIL");
			$arResult['EVENT_FORMATTED'] = array(
				"TITLE" => $arChanger["NAME"]." ".GetMessage('TIMEMAN_ENTRY_LF_TITLE'.($arEntry['INACTIVE_OR_ACTIVATED'] == 'N' ? '_COMMENT' : ($arFields['ENTITY_ID'] == $arFields['USER_ID'] ? '' : '2')).'_MAIL'.$gender, array(
					'#DATE#' => FormatDate($dayMonthFormat, MakeTimeStamp($arEntry['DATE_START'])),
				)),
				"URL" => $URL,
				"MESSAGE" => $arFields["TITLE"],
				"IS_IMPORTANT" => false
			);
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

		$dbEntry = CTimeManEntry::GetList(array(), array('ID' => $arFields["LOG_SOURCE_ID"]));
		if (!$arEntry = $dbEntry->Fetch())
			return $arResult;

		if(!$bMail && (!isset($arParams["USE_COMMENT"]) || $arParams["USE_COMMENT"] != "Y"))
		{
			$arLog["ENTITY_ID"] = $arFields["ENTITY_ID"];
			$arLog["ENTITY_TYPE"] = $arFields["ENTITY_TYPE"];
		}

		$culture = \Bitrix\Main\Application::getInstance()->getContext()->getCulture();
		$dayMonthFormat = $culture->getDayMonthFormat();
		$news_tmp = $arLog["TITLE"] ?? '';
		$title_tmp = GetMessage("TIMEMAN_NEW_COMMENT", array(
			'#DATE#' => FormatDate($dayMonthFormat, MakeTimeStamp($arEntry['DATE_START'])),
		))."\n";

		$title_tmp.= GetMessage("COMMENT_AUTHOR").CUser::FormatName(CSite::GetNameFormat(false),
			array("NAME" => $arFields["CREATED_BY_NAME"], "LAST_NAME" => $arFields["CREATED_BY_LAST_NAME"], "SECOND_NAME" => $arFields["CREATED_BY_SECOND_NAME"], "LOGIN" => $arFields["CREATED_BY_LOGIN"]), true)."\n";
		$title_tmp.= GetMessage("COMMENT_TEXT");

		$title = str_replace(
			array("#TITLE#", "#ENTITY#"),
			array($news_tmp, ($bMail ? ($arResult["ENTITY"]["FORMATTED"] ?? '') : ($arResult["ENTITY"]["FORMATTED"]["NAME"] ?? ''))),
			$title_tmp
		);

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => ($bMail || ($arParams["USE_COMMENT"] ?? null) != "Y" ? $title : ""),
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["MESSAGE"])
		);

		$arResult["ENTITY"]["TYPE_MAIL"] = GetMessage("TIMEMAN_TITLE_FOR_MAIL_COMMENT");
		if ($bMail)
		{
			$reportURL = COption::GetOptionString("timeman","TIMEMAN_REPORT_PATH","/timeman/timeman.php");
			if ($reportURL == '')
				$reportURL = "/timeman/timeman.php";

			$reportURL = CSocNetLogTools::FormatEvent_GetURL(
				array("URL" => $reportURL, "SITE_ID" => $arFields["LOG_SITE_ID"])
			);
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
					"USER" => (($arParams["IM"] ?? null) == "Y" ? "N" : "Y")
				);

				if (!$parserLog)
					$parserLog = new forumTextParser(LANGUAGE_ID);

				$parserLog->arUserfields = $arFields["UF"];
				$parserLog->pathToUser = $parserLog->userPath = $arParams["PATH_TO_USER"];
				$parserLog->bMobile = (isset($arParams["MOBILE"]) && $arParams["MOBILE"] == "Y");
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), $arAllow));
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = preg_replace("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/isu", "\\2", $arResult["EVENT_FORMATTED"]["MESSAGE"]);
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
					"USERFIELDS" => $arFields["UF"]
				);

				if (!$parserLog)
				{
					$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
				}

				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));
			}

			if (
				($arParams["MOBILE"] ?? null) != "Y"
				&& ($arParams["NEW_TEMPLATE"] ?? null) != "Y"
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

	public static function AddComment($arFields)
	{
		$dbResult = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array("TMP_ID" => $arFields["LOG_ID"]),
				false,
				false,
				array("ID", "SOURCE_ID", "PARAMS","SITE_ID")
			);

		$FORUM_ID = 0;
		if ($arLog = $dbResult->Fetch())
		{
			if ($arLog["SOURCE_ID"]>0)
			{
				$FORUM_ID = self::GetForum($arLog);
			}
		}

		if ($FORUM_ID > 0)
			$arReturn = self::AddCommentMessage($arFields, $FORUM_ID, $arLog);
		else
			$arReturn =  array(
				"SOURCE_ID" => false,
				"ERROR" => GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR"),
				"NOTES" => ""
			);

		return $arReturn;
	}

	public static function AddCommentMessage($arFields, $FORUM_ID, $arLog)
	{
		global $USER, $DB, $USER_FIELD_MANAGER;

		$dbRes = CTimeManEntry::GetList([], ['ID' => $arLog['SOURCE_ID']]);
		$arEntry = $dbRes->Fetch();

		if ($arEntry && CModule::IncludeModule("forum"))
		{
			$ufFileID = [];
			$ufDocID = [];
			if (isset($arFields['FORUM_COMMENT_ADDED']) && $arFields['FORUM_COMMENT_ADDED'] === true && $arFields['MESSAGE_ID'])
			{
				$mess_id = $arFields['MESSAGE_ID'];
			}
			else
			{
				if (!$userName = trim($USER->GetFormattedName(false)))
				{
					$userName = $USER->GetLogin();
				}

				if (intval($arEntry["FORUM_TOPIC_ID"]) > 0)
				{
					if (!CForumTopic::GetByID($arEntry["FORUM_TOPIC_ID"]))
					{
						$arEntry["FORUM_TOPIC_ID"] = false;
					}
				}

				if (intval($arEntry["FORUM_TOPIC_ID"]) <= 0)
				{
					$t = ConvertTimeStamp(time(), "FULL");
					$arTopicFields = [
						"TITLE" => $arEntry["DATE_START"],
						"USER_START_ID" => $arFields["USER_ID"],
						"STATE" => "Y",
						"FORUM_ID" => $FORUM_ID,
						"USER_START_NAME" => $userName,
						"START_DATE" => $t,
						"POSTS" => 0,
						"VIEWS" => 0,
						"APPROVED" => "Y",
						"LAST_POSTER_NAME" => $userName,
						"LAST_POST_DATE" => $t,
						"LAST_MESSAGE_ID" => 0,
						"XML_ID" => "TIMEMAN_ENTRY_" . $arLog["SOURCE_ID"],
					];
					$TOPIC_ID = CForumTopic::Add($arTopicFields);
					if ($TOPIC_ID > 0)
					{
						CTimeManEntry::Update($arLog['SOURCE_ID'], ["FORUM_TOPIC_ID" => $TOPIC_ID]);
					}
				}
				else
				{
					$TOPIC_ID = $arEntry["FORUM_TOPIC_ID"];
				}

				if ($TOPIC_ID)
				{
					$arFieldsP = [
						"AUTHOR_ID" => $arFields["USER_ID"],
						"AUTHOR_NAME" => $userName,
						"POST_MESSAGE" => $arFields["TEXT_MESSAGE"],
						"POST_DATE" => date($DB->DateFormatToPHP(FORMAT_DATETIME), time() - 1),
						"FORUM_ID" => $FORUM_ID,
						"TOPIC_ID" => $TOPIC_ID,
						"APPROVED" => "Y",
						"PARAM2" => $arLog["SOURCE_ID"],
					];

					$USER_FIELD_MANAGER->EditFormAddFields("SONET_COMMENT", $arTmp);
					if (is_array($arTmp))
					{
						if (array_key_exists("UF_SONET_COM_DOC", $arTmp))
						{
							$GLOBALS["UF_FORUM_MESSAGE_DOC"] = $arTmp["UF_SONET_COM_DOC"];
						}
						elseif (array_key_exists("UF_SONET_COM_FILE", $arTmp))
						{
							$arFieldsP["FILES"] = [];
							foreach ($arTmp["UF_SONET_COM_FILE"] as $file_id)
							{
								$arFieldsP["FILES"][] = ["FILE_ID" => $file_id];
							}
						}
					}

					$USER_FIELD_MANAGER->EditFormAddFields("FORUM_MESSAGE", $arFieldsP);

					$mess_id = CForumMessage::Add($arFieldsP);

				}
			}
			// get UF DOC value and FILE_ID there
			if ($mess_id > 0)
			{
				$dbAddedMessageFiles = CForumFiles::GetList(["ID" => "ASC"], ["MESSAGE_ID" => $mess_id]);
				while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
				{
					$ufFileID[] = $arAddedMessageFiles["FILE_ID"];
				}

				$ufDocID = $USER_FIELD_MANAGER->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $mess_id, LANGUAGE_ID);
			}

			if (IsModuleInstalled("im"))
			{
				CTimeManNotify::AddCommentToIM([
					"USER_ID" => $arFields["USER_ID"],
					"ENTRY_ID" => $arEntry["ID"],
					"LOG_ID" => $arLog["ID"],
					"MESSAGE" => $arFields["TEXT_MESSAGE"],
				]);
			}

			return [
				"RATING_TYPE_ID" => "FORUM_POST",
				"RATING_ENTITY_ID" => $mess_id,
				"SOURCE_ID" => $mess_id,
				"UF" => [
					"FILE" => $ufFileID,
					"DOC" => $ufDocID,
				],
			];
		}

		return false;
	}

	public static function onAfterForumMessageAdd($id, $arMessage, $topicInfo, $forumInfo, $arFields)
	{
		$topicXmlId = null;
		if ($topicInfo instanceof \Bitrix\Forum\Topic && !empty($topicInfo->getData())
			&& is_array($topicInfo->getData()) && array_key_exists('XML_ID', $topicInfo->getData()))
		{
			$topicXmlId = $topicInfo->getData()['XML_ID'];
		}
		elseif (is_array($topicInfo) && array_key_exists('XML_ID', $topicInfo))
		{
			$topicXmlId = $topicInfo['XML_ID'];
		}
		if ($topicXmlId !== null && mb_strpos($topicXmlId, 'TIMEMAN_ENTRY_') === 0)
		{
			if (isset($arFields['PARAM1']) && ($arFields['PARAM1'] === SONET_TIMEMAN_ENTRY_ENTITY || $arFields['PARAM1'] === 'TM'))
			{
				return;
			}
			$recordId = mb_substr($topicXmlId, mb_strlen('TIMEMAN_ENTRY_'));
			if (!is_numeric($recordId) || (int)$recordId <= 0)
			{
				return;
			}
			$recordId = (int)$recordId;
			$record = WorktimeRecordTable::query()
				->addSelect('APPROVED_BY')
				->addSelect('ID')
				->where('ID', $recordId)
				->exec()
				->fetchObject();
			if (!$record)
			{
				return;
			}
			\Bitrix\Timeman\Service\DependencyManager::getInstance()
				->getLiveFeedManager()
				->continueWorkdayPostTrackingForApprover($record->getId(), $record->getApprovedBy());
		}
	}

	public static function onAfterForumCommentAdd($entityType, $entityId, $arFields)
	{
		if ($entityType !== 'TM')
		{
			return;
		}
		$record = WorktimeRecordTable::query()
			->addSelect('FORUM_TOPIC_ID')
			->where('ID', $entityId)
			->fetch();
		if ($record !== false && $record['FORUM_TOPIC_ID'] <= 0 && $arFields['MESSAGE']['TOPIC_ID'] > 0)
		{
			WorktimeRecordTable::update($entityId, ['FORUM_TOPIC_ID' => $arFields['MESSAGE']['TOPIC_ID']]);
		}
		$data = [
			'ENTRY_ID' => $entityId,
			'COMMENT_TEXT' => $arFields['MESSAGE']['POST_MESSAGE'],
			'USER_ID' => $arFields['PARAMS']['AUTHOR_ID'],
			'FORUM_COMMENT_ADDED' => true,
			'MESSAGE_ID' => $arFields['MESSAGE']['ID'],
		];
		CTimeManNotify::AddCommentToLog($data);
	}

	public static function AddCommentToLog($arFields)
	{
		global $DB, $USER;
		CModule::IncludeModule("socialnetwork");

		$result = false;
		$LOG_ID = CTimeManNotify::SendMessage($arFields["ENTRY_ID"], 'A');

		$arMessFields = Array(
			"EVENT_ID" => "timeman_entry_comment",
			"ENTITY_ID" => $arFields["ENTRY_ID"],
			"TEXT_MESSAGE" => $arFields["COMMENT_TEXT"],
			"MESSAGE" => $arFields["COMMENT_TEXT"],
			"USER_ID" => $arFields["USER_ID"],
			"FORUM_COMMENT_ADDED" => isset($arFields["FORUM_COMMENT_ADDED"]) ? $arFields["FORUM_COMMENT_ADDED"] : false,
			"MESSAGE_ID" => isset($arFields["MESSAGE_ID"]) ? $arFields["MESSAGE_ID"] : null,
			"ENTITY_TYPE" => SONET_TIMEMAN_ENTRY_ENTITY,
			"LOG_ID" => $LOG_ID,
			"=LOG_DATE" => $DB->CurrentTimeFunction()
		);

		$result = CSocNetLogComments::Add($arMessFields, true, false);
		CSocNetLog::CounterIncrement($result, false, false, "LC");

		$curUser = $USER->GetID();

		$dbLogRights = CSocNetLogRights::GetList(array(), array("LOG_ID" => $LOG_ID));
		while($arRight = $dbLogRights->Fetch())
			$arRights[] = $arRight["GROUP_CODE"];

		if (!in_array("U".$curUser, $arRights))
			CSocNetLogRights::Add($LOG_ID, "U".$curUser);

		return $result;
	}

	public static function AddCommentToIM($arFields)
	{
		if (
			CModule::IncludeModule("im")
			&& intval($arFields["USER_ID"]) > 0
			&& intval($arFields["ENTRY_ID"]) > 0
		)
		{
			$dbEntry = CTimeManEntry::GetList(array(), array("ID" => $arFields["ENTRY_ID"]));
			if ($arEntry = $dbEntry->Fetch())
			{
				$culture = \Bitrix\Main\Application::getInstance()->getContext()->getCulture();
				$dayMonthFormat = $culture->getDayMonthFormat();

				$arMessageFields = array(
					"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
					"FROM_USER_ID" => $arFields["USER_ID"],
					"NOTIFY_TYPE" => IM_NOTIFY_FROM,
					"NOTIFY_MODULE" => "timeman",
					"NOTIFY_EVENT" => "entry_comment",
					"NOTIFY_TAG" => "TIMEMAN|ENTRY|".$arFields["ENTRY_ID"],
				);

				$arUserIDToSend = array(
					$arEntry["USER_ID"]
				);

				$gender_suffix = "";
				$dbUser = CUser::GetByID($arFields["USER_ID"]);
				if ($arUser = $dbUser->Fetch())
				{
					$gender_suffix = match ($arUser["PERSONAL_GENDER"])
					{
						"M" => "_M",
						"F" => "_F",
						default => "",
					};
				}

				$arManagers = CTimeMan::GetUserManagers($arEntry["USER_ID"]);
				if (is_array($arManagers))
					$arUserIDToSend = array_merge($arUserIDToSend, $arManagers);

				$reports_page = COption::GetOptionString("timeman", "TIMEMAN_REPORT_PATH", "/timeman/timeman.php");

				$arUnFollowers = array();

				$rsUnFollower = CSocNetLogFollow::GetList(
					array(
						"USER_ID" => $arUserIDToSend,
						"CODE" => "L".$arFields["LOG_ID"],
						"TYPE" => "N"
					),
					array("USER_ID")
				);
				while ($arUnFollower = $rsUnFollower->Fetch())
				{
					$arUnFollowers[] = $arUnFollower["USER_ID"];
				}

				$arUserIDToSend = array_diff($arUserIDToSend, $arUnFollowers);

				foreach($arUserIDToSend as $user_id)
				{
					if ($arFields["USER_ID"] == $user_id)
					{
						continue;
					}

					$arMessageFields["TO_USER_ID"] = $user_id;
					$arTmp = CSocNetLogTools::ProcessPath(array("REPORTS_PAGE" => $reports_page), $user_id);

					$sender_type = ($arEntry["USER_ID"] == $user_id ? "1" : ($arEntry["USER_ID"] == $arFields["USER_ID"] ? "2" : "3"));

					$arMessageFields["NOTIFY_MESSAGE"] = fn (?string $languageId = null) => Loc::getMessage(
						"TIMEMAN_ENTRY_IM_COMMENT_".$sender_type.$gender_suffix,
						[
							"#period#" => "<a href=\"".$arTmp["URLS"]["REPORTS_PAGE"]."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx(FormatDate($dayMonthFormat, MakeTimeStamp($arEntry["DATE_START"], FORMAT_DATETIME), false, $languageId))."</a>",
						],
						$languageId
					);

					$arMessageFields["NOTIFY_MESSAGE_OUT"] = fn (?string $languageId = null) =>
						Loc::getMessage(
							"TIMEMAN_ENTRY_IM_COMMENT_".$sender_type.$gender_suffix,
							[
								"#period#" => htmlspecialcharsbx(FormatDate($dayMonthFormat, MakeTimeStamp($arEntry["DATE_START"], FORMAT_DATETIME), false, $languageId)),
							],
							$languageId
						)
						. " (".$arTmp["SERVER_NAME"].$arTmp["URLS"]["REPORTS_PAGE"].")#BR##BR#".$arFields["MESSAGE"];

					CIMNotify::Add($arMessageFields);
				}
			}
		}
	}

	public static function GetForum($arLog = null)
	{
		$forumId = COption::GetOptionInt("timeman", "report_forum_id", 0);
		if ($forumId <= 0 && CModule::IncludeModule("forum"))
		{
			$siteId = SITE_ID;
			if ($arLog)
			{
				$siteId = $arLog["SITE_ID"];
			}
			$arForumFields = [
				"NAME" => GetMessage("TIMEMAN_FORUM_TITLE"),
				"DESCRIPTION" => "",
				"FORUM_GROUP_ID" => 0,
				"GROUP_ID" => [1 => "Y", 2 => "M"],
				"SITES" => [$siteId => "/"],
				"ACTIVE" => "Y",
				"MODERATION" => "N",
				"INDEXATION" => "N",
				"SORT" => 150,
				"ASK_GUEST_EMAIL" => "N",
				"USE_CAPTCHA" => "N",
				"ALLOW_HTML" => "N",
				"ALLOW_ANCHOR" => "Y",
				"ALLOW_BIU" => "Y",
				"ALLOW_IMG" => "Y",
				"ALLOW_VIDEO" => "Y",
				"ALLOW_LIST" => "Y",
				"ALLOW_QUOTE" => "Y",
				"ALLOW_CODE" => "Y",
				"ALLOW_FONT" => "Y",
				"ALLOW_SMILES" => "Y",
				"ALLOW_UPLOAD" => "Y",
				"ALLOW_UPLOAD_EXT" => "",
				"ALLOW_TOPIC_TITLED" => "Y",
			];

			$forumId = CForumNew::Add($arForumFields);
			if ($forumId > 0)
			{
				COption::SetOptionInt("timeman", "report_forum_id", $forumId);
			}
		}

		return $forumId;
	}

	public static function OnAfterUserUpdate($arFields)
	{
		if (array_key_exists("UF_DEPARTMENT", $arFields))
		{
			$arDept = $arFields["UF_DEPARTMENT"];
			if (!is_array($arDept))
			{
				$arDept = array($arDept);
			}

			foreach ($arDept as $key => $val)
			{
				if (intval($val) <= 0)
				{
					unset($arDept[$key]);
				}
			}

			if (
				!empty($arDept)
				&& CModule::IncludeModule("socialnetwork")
			)
			{
				$arNewRights = self::GetRights($arFields["ID"]);

				$rsLog = CSocNetLog::GetList(
					array(),
					array(
						'ENTITY_TYPE' => SONET_TIMEMAN_ENTRY_ENTITY,
						'ENTITY_ID' => $arFields["ID"],
						'EVENT_ID' => "timeman_entry",
					),
					false,
					false,
					array("ID")
				);

				while ($arLog = $rsLog->Fetch())
				{
					$arOldRights = array();

					$rsLogRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arLog["ID"]));
					while ($arLogRight = $rsLogRight->Fetch())
					{
						$arOldRights[] = $arLogRight["GROUP_CODE"];
					}

					$diff1 = array_diff($arNewRights, $arOldRights);
					$diff2 = array_diff($arOldRights, $arNewRights);

					if (
						!empty($diff1)
						|| !empty($diff2)
					)
					{
						CSocNetLogRights::DeleteByLogID($arLog["ID"]);
						CSocNetLogRights::Add($arLog["ID"], $arNewRights);
					}
				}
			}

			if ($arDept)
			{
				$settingKeys = [
					'UF_TIMEMAN',
					'UF_TM_REPORT_REQ',
				];

				$timemanUser = new \CTimeManUser($arFields['ID']);

				$currentSettings = $timemanUser->getPersonalSettings($settingKeys);

				$isInheritDepartmentSettings = (
					$currentSettings['UF_TIMEMAN'] === null
					&& $currentSettings['UF_TM_REPORT_REQ'] === null
				);
				if ($isInheritDepartmentSettings)
				{
					$user = new \CUser();
					$user->update(
						$arFields['ID'],
						[
							'UF_TIMEMAN' => null,
							'UF_TM_REPORT_REQ' => null,
						]
					);

					if (defined('BX_COMP_MANAGED_CACHE'))
					{
						global $CACHE_MANAGER;

						$CACHE_MANAGER->CleanDir('timeman_structure_'
							. \COption::getOptionInt('intranet', 'iblock_structure')
						);
					}
				}
			}
		}
	}
}
?>