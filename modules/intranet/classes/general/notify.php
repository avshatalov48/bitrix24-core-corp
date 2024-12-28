<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\RandomSequence;

IncludeModuleLangFile(__FILE__);

class CIntranetNotify
{
	public static function NewUserMessage($USER_ID): void
	{
		if (!CModule::IncludeModule('socialnetwork'))
		{
			return;
		}

		$USER_ID = (int)$USER_ID;
		if ($USER_ID <= 0)
		{
			return;
		}

		$arRights = self::GetRights($USER_ID);
		if (!$arRights)
		{
			return;
		}

		$blockNewUserLF = COption::GetOptionString("intranet", "BLOCK_NEW_USER_LF_SITE", false);
		if (!$blockNewUserLF)
		{
			$blockNewUserLF = COption::GetOptionString("intranet", "BLOCK_NEW_USER_LF", "N");
		}

		if ($blockNewUserLF === 'Y')
		{
			return;
		}

		$dbRes = CUser::GetList(
			"ID",
			"asc",
			[ 'ID_EQUAL_EXACT' => $USER_ID ],
			[
				'FIELDS' => [ 'EXTERNAL_AUTH_ID' ],
				'SELECT' => [ 'UF_DEPARTMENT' ],
			]
		);
		if (
			!($arUser = $dbRes->fetch())
			|| (in_array($arUser["EXTERNAL_AUTH_ID"], [ 'bot', 'imconnector' ], true))
		)
		{
			return;
		}

		$bExtranetUser = false;

		if (
			!isset($arUser['UF_DEPARTMENT'])
			|| !is_array($arUser['UF_DEPARTMENT'])
			|| empty($arUser['UF_DEPARTMENT'])
		)
		{
			if (!Loader::includeModule('extranet'))
			{
				return;
			}

			$extranetGroupId = CExtranet::getExtranetUserGroupId();
			if (
				!$extranetGroupId
				|| !in_array($extranetGroupId, CUser::GetUserGroup($USER_ID))
			)
			{
				return;
			}

			$bExtranetUser = true;
		}

		$randomGenerator = new RandomSequence('INTRANET_NEW_USER' . $USER_ID);

		$arSoFields = array(
			"ENTITY_TYPE" => SONET_INTRANET_NEW_USER_ENTITY,
			"EVENT_ID" => SONET_INTRANET_NEW_USER_EVENT_ID,
			"ENTITY_ID" => $USER_ID,
			"SOURCE_ID" => $USER_ID,
			"USER_ID" => $USER_ID,
			"=LOG_DATE" => CDatabase::CurrentTimeFunction(),
			"MODULE_ID" => "intranet",
			"TITLE_TEMPLATE" => "#TITLE#",
			"TITLE" => static::getNewUserPostTitle($USER_ID, $bExtranetUser),
			"MESSAGE" => '',
			"TEXT_MESSAGE" => '',
			"CALLBACK_FUNC" => false,
			"SITE_ID" => SITE_ID,
			"ENABLE_COMMENTS" => "Y", //!!!
			"RATING_TYPE_ID" => "INTRANET_NEW_USER",
			"RATING_ENTITY_ID" => $randomGenerator->rand(1, 2147483647),
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

		if ((int)$logID <= 0)
		{
			return;
		}

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

	public static function OnAfterSocNetLogCommentAdd($ID, $arFields)
	{
		if (
			$arFields['ENTITY_TYPE'] === SONET_INTRANET_NEW_USER_ENTITY
			&& $arFields['EVENT_ID'] === SONET_INTRANET_NEW_USER_COMMENT_EVENT_ID
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
		$ID = (int)$ID;
		$dbUser = CUser::GetByID($ID);
		if ($arUser = $dbUser->GetNext())
		{
			$arUser["NAME_FORMATTED"] = CUser::FormatName(CSite::GetNameFormat(false), $arUser);
			$arUser["~NAME_FORMATTED"] = htmlspecialcharsback($arUser["NAME_FORMATTED"]);
			return $arUser;
		}

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

		$arParams = is_array($arParams) ? $arParams : [];
		$arParams['MOBILE'] = $arParams['MOBILE'] ?? 'N';
		$arParams['PATH_TO_USER'] = $arParams['PATH_TO_USER'] ?? '';

		$user_url = str_replace('#user_id#', $arFields['ENTITY_ID'], $arParams['PATH_TO_USER']);

		$dbRes = CUser::GetByID($arFields['ENTITY_ID']);
		$arUser = $dbRes->Fetch();

		if (
			$arUser
			&& (
				ModuleManager::isModuleInstalled('extranet')
				|| (
					!empty($arUser['UF_DEPARTMENT'])
					&& is_array($arUser['UF_DEPARTMENT'])
					&& (int)$arUser['UF_DEPARTMENT'][0] > 0
				) // for uninstalled extranet module / b24
			)
		)
		{
			if(!$bMail)
			{
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->RegisterTag("USER_NAME_" . (int)$arUser["ID"]);
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
				$html_message = ob_get_clean();

				$arResult = array(
					'EVENT' => $arFields,
					'EVENT_FORMATTED' => array(
						'TITLE' => static::getNewUserPostTitle((int)$arUser['ID'], $bExtranetUser),
						'TITLE_24' => static::getNewUserPostTitle((int)$arUser['ID'], $bExtranetUser),
						"MESSAGE" => $html_message,
						"SHORT_MESSAGE" => $html_message,
						'IS_IMPORTANT' => true,
						'STYLE' => 'new-employee',
						'AVATAR_STYLE' => 'avatar-info'
					),
				);

				if ($bExtranetUser)
				{
					$workgroupCodesList = [];

					$res = \Bitrix\Socialnetwork\LogRightTable::getList([
						'filter' => [
							'=LOG_ID' => (int)$arFields['ID'],
						],
						'select' => [ 'GROUP_CODE' ],
					]);
					while ($logRightsFields = $res->fetch())
					{
						if (
							preg_match('/^SG(\d+)$/i', $logRightsFields['GROUP_CODE'], $matches)
							&& (int)$matches[1] > 0
						)
						{
							$workgroupCodesList[] = $matches[0];
						}
					}

					if (!empty($workgroupCodesList))
					{
						$arResult['EVENT_FORMATTED']['DESTINATION'] = CSocNetLogTools::formatDestinationFromRights($workgroupCodesList, $arParams);
					}
				}

				if (Loader::includeModule('bitrix24'))
				{
					$arResult['CREATED_BY']['FORMATTED'] = (
						$arParams["MOBILE"] === "Y"
							? htmlspecialcharsEx(self::GetSiteName())
							: '<a href="'.BITRIX24_PATH_COMPANY_STRUCTURE_VISUAL.'">'.htmlspecialcharsEx(self::GetSiteName()).'</a>'
					);
				}
				else
				{
					$arResult['CREATED_BY']['FORMATTED'] = '';
					if (is_array($arUser['UF_DEPARTMENT']) && count($arUser['UF_DEPARTMENT']) > 0)
					{
						if ($arParams["MOBILE"] === "Y")
						{
							$url = "";
						}
						else
						{
							$url = $arParams['PATH_TO_CONPANY_DEPARTMENT'];
							if ($url == '')
							{
								$url = $arParams['PATH_TO_COMPANY_DEPARTMENT'];
							}
						}

						$departmentRepository = \Bitrix\Intranet\Service\ServiceContainer::getInstance()
							->departmentRepository();
						$departmentId = is_array($arUser['UF_DEPARTMENT'])
							? (int)$arUser['UF_DEPARTMENT'][0]
							: (int)$arUser['UF_DEPARTMENT'];
						$department = $departmentRepository->getById($departmentId);
						if ($department)
						{
							$arResult['CREATED_BY']['FORMATTED'] = (
								$url <> ''
									? '<a href="'.str_replace('#ID#', $department->getId(), $url).'">'.htmlspecialcharsEx($department->getName()).'</a>'
									: htmlspecialcharsEx($department->getName())
							);
						}
					}

					if ($arResult['CREATED_BY']['FORMATTED'] == '')
					{
						$arResult['CREATED_BY']['FORMATTED'] = htmlspecialcharsEx(self::GetSiteName());
					}
				}

				$arResult['ENTITY']['FORMATTED']["NAME"] = static::getNewUserPostTitle((int)$arUser["ID"], $bExtranetUser);
				$arResult['ENTITY']['FORMATTED']["URL"] = $user_url;

				if (
					$arParams["MOBILE"] !== "Y"
					&& $arParams["NEW_TEMPLATE"] !== "Y"
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
		if (!$bMail)
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
					"USER" => (isset($arParams["IM"]) && $arParams["IM"] === "Y" ? "N" : "Y")
				);

				$parserLog->pathToUser = $parserLog->userPath = $arParams["PATH_TO_USER"];
				$parserLog->arUserfields = $arFields["UF"];
				$parserLog->bMobile = (isset($arParams["MOBILE"]) && $arParams["MOBILE"] === "Y");
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), $arAllow));
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = preg_replace("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/isu", "\\2", $arResult["EVENT_FORMATTED"]["MESSAGE"]);
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
				(!isset($arParams["MOBILE"]) || $arParams["MOBILE"] !== "Y")
				&& (!isset($arParams["NEW_TEMPLATE"]) || $arParams["NEW_TEMPLATE"] !== "Y")
			)
			{
				if (CModule::IncludeModule("forum"))
				{
					$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
						$parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), $arAllow),
						500
					);
				}
				else
				{
					$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
						$parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow),
						500
					);
				}

				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
			}
		}

		return $arResult;
	}

	protected static function GetRights($USER_ID): array
	{
		$bExtranetUser = false;
		if (ModuleManager::isModuleInstalled('extranet'))
		{
			$rsUser = CUser::GetByID($USER_ID);
			if (
				($arUser = $rsUser->fetch())
				&& (int)$arUser["UF_DEPARTMENT"][0] <= 0
			)
			{
				$bExtranetUser = true;
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
				$arResult[] = "SG".$arSocNetUserToGroup["GROUP_ID"];
				$arResult[] = "SG".$arSocNetUserToGroup["GROUP_ID"]."_".SONET_ROLES_USER;
				$arResult[] = "SG".$arSocNetUserToGroup["GROUP_ID"]."_".SONET_ROLES_MODERATOR;
				$arResult[] = "SG".$arSocNetUserToGroup["GROUP_ID"]."_".SONET_ROLES_OWNER;
			}
			return $arResult;
		}

		return array("G2");
	}

	protected static function GetSiteName(): string
	{
		$result = Option::get('main', 'site_name');

		if ($result === '')
		{
			$result = Loc::getMessage('I_NEW_USER_SITE_NAME_DEFAULT');
		}
		return $result;
	}

	public static function OnSendMentionGetEntityFields($arCommentFields)
	{
		if ($arCommentFields["EVENT_ID"] !== SONET_INTRANET_NEW_USER_COMMENT_EVENT_ID)
		{
			return false;
		}

		if (!Loader::includeModule('socialnetwork'))
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
			&& ((int)$arLog["USER_ID"] > 0)
		)
		{
			$genderSuffix = "";
			$dbUsers = CUser::GetList("ID", "desc",
				array("ID" => $arCommentFields["USER_ID"].' | '.$arLog["USER_ID"]),
				array("PERSONAL_GENDER", "LOGIN", "NAME", "LAST_NAME", "SECOND_NAME")
			);
			while ($arUser = $dbUsers->Fetch())
			{
				if ((int)$arUser["ID"] === (int)$arCommentFields["USER_ID"])
				{
					$genderSuffix = $arUser["PERSONAL_GENDER"];
				}
				if ((int)$arUser["ID"] === (int)$arLog["USER_ID"])
				{
					$nameFormatted = CUser::FormatName(CSite::GetNameFormat(), $arUser);
				}
			}

			$strPathToLogEntry = str_replace("#log_id#", $arLog["ID"], COption::GetOptionString("socialnetwork", "log_entry_page", "/company/personal/log/#log_id#/", SITE_ID));
			$strPathToLogEntryComment = $strPathToLogEntry.(mb_strpos($strPathToLogEntry, "?") !== false ? "&" : "?")."commentID=".$arCommentFields["ID"];

			$arReturn = array(
				"URL" => $strPathToLogEntryComment,
				"NOTIFY_MODULE" => "intranet",
				"NOTIFY_TAG" => "INTRANET_NEW_USER|COMMENT_MENTION|".$arCommentFields["ID"],
				"NOTIFY_MESSAGE" => Loc::getMessage("I_NEW_USER_MENTION".($genderSuffix !== '' ? "_" . $genderSuffix : ""), Array("#title#" => "<a href=\"#url#\" class=\"bx-notifier-item-action\">".$nameFormatted."</a>")),
				"NOTIFY_MESSAGE_OUT" => Loc::getMessage("I_NEW_USER_MENTION".($genderSuffix !== '' ? "_" . $genderSuffix : ""), Array("#title#" => $nameFormatted))." ("."#server_name##url#)"
			);

			return $arReturn;
		}

		return false;
	}

	protected static function getNewUserPostTitle(int $userId, bool $isExtranet = false): string
	{
		if (!$isExtranet)
		{
			return Loc::getMessage('I_NEW_USER_TITLE');
		}

		$collaberService = \Bitrix\Extranet\Service\ServiceContainer::getInstance()->getCollaberService();

		if ($collaberService->isCollaberById($userId))
		{
			return Loc::getMessage('I_NEW_USER_GUEST_TITLE');
		}

		return Loc::getMessage('I_NEW_USER_EXTERNAL_TITLE');
	}
}
