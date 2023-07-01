<?php

IncludeModuleLangFile(__FILE__);

use Bitrix\Crm\Activity\Provider\Tasks\Task;
use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Settings;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Uri;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Objectify\Values;

class CCrmLiveFeedEntity
{
	const Lead = SONET_CRM_LEAD_ENTITY;
	const Contact = SONET_CRM_CONTACT_ENTITY;
	const Company = SONET_CRM_COMPANY_ENTITY;
	const Deal = SONET_CRM_DEAL_ENTITY;
	const Activity = SONET_CRM_ACTIVITY_ENTITY;
	const Invoice = SONET_CRM_INVOICE_ENTITY;
	const Order = SONET_CRM_ORDER_ENTITY;

	const SuspendedLead = SONET_CRM_SUSPENDED_LEAD_ENTITY;
	const SuspendedContact = SONET_SUSPENDED_CRM_CONTACT_ENTITY;
	const SuspendedCompany = SONET_SUSPENDED_CRM_COMPANY_ENTITY;
	const SuspendedDeal = SONET_CRM_SUSPENDED_DEAL_ENTITY;
	const SuspendedActivity = SONET_CRM_SUSPENDED_ACTIVITY_ENTITY;

	public const DynamicPattern = 'CRMDYNAMIC#ID#ENTITY';
	public const SuspendedDynamicPattern = 'CRMSUSDYNAMIC#ID#ENTITY';

	const Undefined = '';

	private static $ALL_FOR_SQL = null;

	public static function IsDefined($entityType)
	{
		return $entityType === self::Lead
			|| $entityType === self::Contact
			|| $entityType === self::Company
			|| $entityType === self::Deal
			|| $entityType === self::Activity
			|| $entityType === self::Invoice;
	}
	public static function GetAll()
	{
		return array(
			self::Lead,
			self::Contact,
			self::Company,
			self::Deal,
			self::Activity,
			self::Invoice,
			self::SuspendedLead,
			self::SuspendedContact,
			self::SuspendedCompany,
			self::SuspendedDeal,
			self::SuspendedActivity
		);
	}

	/*
	 * Get types that do not have dependencies
	 * */
	public static function GetLeafs()
	{
		return array(self::Activity, self::Invoice);
	}

	public static function GetAllForSql()
	{
		if(self::$ALL_FOR_SQL === null)
		{
			global $DB;
			self::$ALL_FOR_SQL = array(
				"'".($DB->ForSql(self::Lead))."'",
				"'".($DB->ForSql(self::Contact))."'",
				"'".($DB->ForSql(self::Company))."'",
				"'".($DB->ForSql(self::Deal))."'",
				"'".($DB->ForSql(self::Activity))."'",
				"'".($DB->ForSql(self::Invoice))."'"
			);
		}
		return self::$ALL_FOR_SQL;
	}

	public static function GetForSql($types)
	{
		if(!is_array($types) || empty($types))
		{
			return self::GetAllForSql();
		}

		global $DB;
		$result = array();
		foreach($types as $type)
		{
			if(self::IsDefined($type))
			{
				$result[] = "'".($DB->ForSql($type))."'";
			}
		}
		return $result;
	}

	public static function GetForSqlString($types)
	{
		return implode(',', self::GetForSql($types));
	}

	public static function ResolveEntityTypeID($entityType)
	{
		switch($entityType)
		{
			case self::Lead:
			{
				return CCrmOwnerType::Lead;
			}
			case self::Contact:
			{
				return CCrmOwnerType::Contact;
			}
			case self::Company:
			{
				return CCrmOwnerType::Company;
			}
			case self::Deal:
			{
				return CCrmOwnerType::Deal;
			}
			case self::Activity:
			{
				return CCrmOwnerType::Activity;
			}
			case self::Invoice:
			{
				return CCrmOwnerType::Invoice;
			}
			case self::Order:
			{
				return CCrmOwnerType::Order;
			}
			default:
			{
				return CCrmOwnerType::Undefined;
			}
		}
	}
	public static function GetByEntityTypeID($entityTypeID)
	{
		switch($entityTypeID)
		{
			case CCrmOwnerType::Lead:
			{
				return self::Lead;
			}
			case CCrmOwnerType::Contact:
			{
				return self::Contact;
			}
			case CCrmOwnerType::Company:
			{
				return self::Company;
			}
			case CCrmOwnerType::Deal:
			{
				return self::Deal;
			}
			case CCrmOwnerType::Activity:
			{
				return self::Activity;
			}
			case CCrmOwnerType::Invoice:
			{
				return self::Invoice;
			}
			case CCrmOwnerType::Order:
			{
				return self::Order;
			}
			case CCrmOwnerType::SuspendedLead:
			{
				return self::SuspendedLead;
			}
			case CCrmOwnerType::SuspendedContact:
			{
				return self::SuspendedContact;
			}
			case CCrmOwnerType::SuspendedCompany:
			{
				return self::SuspendedCompany;
			}
			case CCrmOwnerType::SuspendedDeal:
			{
				return self::SuspendedDeal;
			}
			case CCrmOwnerType::SuspendedActivity:
			{
				return self::SuspendedActivity;
			}
			default:
			{
				if (
					$entityTypeID >= CCrmOwnerType::DynamicTypeStart
					&& $entityTypeID < CCrmOwnerType::DynamicTypeEnd
				)
				{
					return str_replace('#ID#', $entityTypeID, self::DynamicPattern);
				}

				if (
					$entityTypeID >= CCrmOwnerType::SuspendedDynamicTypeStart
					&& $entityTypeID < CCrmOwnerType::SuspendedDynamicTypeEnd
				)
				{
					return str_replace('#ID#', $entityTypeID, self::SuspendedDynamicPattern);
				}

				return self::Undefined;
			}
		}
	}
}

class CCrmLiveFeedEvent
{
	// Entity prefixes -->
	const LeadPrefix = 'crm_lead_';
	const ContactPrefix = 'crm_contact_';
	const CompanyPrefix = 'crm_company_';
	const DealPrefix = 'crm_deal_';
	const ActivityPrefix = 'crm_activity_';
	const InvoicePrefix = 'crm_invoice_';
	//<-- Entity prefixes

	// Event -->
	const Add = 'add';
	const Progress = 'progress';
	const Denomination = 'denomination';
	const Responsible = 'responsible';
	const Client = 'client';
	const Owner = 'owner';
	const Message = 'message';
	const Custom = 'custom';
	//<-- Event

	const CommentSuffix = '_comment';
	public static function GetEventID($entityTypeID, $eventType)
	{
		switch($entityTypeID)
		{
			//Event IDs like crm_lead_add, crm_lead_add_comment
			case CCrmLiveFeedEntity::Lead:
			{
				return self::LeadPrefix.$eventType;
			}
			case CCrmLiveFeedEntity::Contact:
			{
				return self::ContactPrefix.$eventType;
			}
			case CCrmLiveFeedEntity::Company:
			{
				return self::CompanyPrefix.$eventType;
			}
			case CCrmLiveFeedEntity::Deal:
			{
				return self::DealPrefix.$eventType;
			}
			case CCrmLiveFeedEntity::Activity:
			{
				return self::ActivityPrefix.$eventType;
			}
			case CCrmLiveFeedEntity::Invoice:
			{
				return self::InvoicePrefix.$eventType;
			}
		}

		return '';
	}
	public static function PrepareEntityEventInfos($entityTypeID)
	{
		$result = array();

		$prefix = '';
		$events = null;
		switch($entityTypeID)
		{
			case CCrmLiveFeedEntity::Lead:
			{
				$prefix = self::LeadPrefix;
				$events = array(
					self::Add,
					self::Progress,
					self::Responsible,
					self::Denomination,
					self::Message
				);
			}
			break;
			case CCrmLiveFeedEntity::Deal:
			{
				$prefix = self::DealPrefix;
				$events = array(
					self::Add,
					self::Client,
					self::Progress,
					self::Responsible,
					self::Denomination,
					self::Message
				);
			}
			break;
			case CCrmLiveFeedEntity::Company:
			{
				$prefix = self::CompanyPrefix;
				$events = array(
					self::Add,
					self::Responsible,
					self::Denomination,
					self::Message
				);
			}
			break;
			case CCrmLiveFeedEntity::Contact:
			{
				$prefix = self::ContactPrefix;
				$events = array(
					self::Add,
					self::Owner,
					self::Responsible,
					self::Denomination,
					self::Message
				);
			}
			break;
			case CCrmLiveFeedEntity::Activity:
			{
				$prefix = self::ActivityPrefix;
				$events = array(self::Add);
			}
			break;
			case CCrmLiveFeedEntity::Invoice:
			{
				$prefix = self::InvoicePrefix;
				$events = array(self::Add);
			}
			break;
		}

		if(is_array($events))
		{
			foreach($events as &$event)
			{
				$eventID = "{$prefix}{$event}";
				$result[] = array(
					'EVENT_ID' => $eventID,
					'COMMENT_EVENT_ID' => $eventID.self::CommentSuffix,
					'COMMENT_ADD_CALLBACK' => (
						($prefix == self::ActivityPrefix && $event == self::Add)
							? array("CCrmLiveFeed", "AddCrmActivityComment")
							: false
					),
					'COMMENT_UPDATE_CALLBACK' => (
						($prefix == self::ActivityPrefix && $event == self::Add)
							? array("CCrmLiveFeed", "UpdateCrmActivityComment")
							: "NO_SOURCE"
					),
					'COMMENT_DELETE_CALLBACK' => (
						($prefix == self::ActivityPrefix && $event == self::Add)
							? array("CCrmLiveFeed", "DeleteCrmActivityComment")
							: "NO_SOURCE"
					)
				);
			}
			unset($event);
		}

		return $result;
	}
}

class CCrmLiveFeed
{
	const UntitledMessageStub = '__EMPTY__';

	public static function OnFillSocNetAllowedSubscribeEntityTypes(&$entityTypes)
	{
		$typeNames =  CCrmLiveFeedEntity::GetAll();
		foreach($typeNames as $typeName)
		{
			$entityTypes[] = $typeName;
		}

		global $arSocNetAllowedSubscribeEntityTypesDesc;
		$arSocNetAllowedSubscribeEntityTypesDesc[CCrmLiveFeedEntity::Lead] = array(
			'USE_CB_FILTER' => 'Y',
			'HAS_CB' => 'N'
		);
		$arSocNetAllowedSubscribeEntityTypesDesc[CCrmLiveFeedEntity::Contact] = array(
			'USE_CB_FILTER' => 'Y',
			'HAS_CB' => 'N'
		);
		$arSocNetAllowedSubscribeEntityTypesDesc[CCrmLiveFeedEntity::Company] = array(
			'USE_CB_FILTER' => 'Y',
			'HAS_CB' => 'N'
		);
		$arSocNetAllowedSubscribeEntityTypesDesc[CCrmLiveFeedEntity::Deal] = array(
			'USE_CB_FILTER' => 'Y',
			'HAS_CB' => 'N'
		);
		$arSocNetAllowedSubscribeEntityTypesDesc[CCrmLiveFeedEntity::Activity] = array(
			'USE_CB_FILTER' => 'Y',
			'HAS_CB' => 'N'
		);
		$arSocNetAllowedSubscribeEntityTypesDesc[CCrmLiveFeedEntity::SuspendedLead] = array(
			'USE_CB_FILTER' => 'Y',
			'HAS_CB' => 'N'
		);
		$arSocNetAllowedSubscribeEntityTypesDesc[CCrmLiveFeedEntity::SuspendedContact] = array(
			'USE_CB_FILTER' => 'Y',
			'HAS_CB' => 'N'
		);
		$arSocNetAllowedSubscribeEntityTypesDesc[CCrmLiveFeedEntity::SuspendedCompany] = array(
			'USE_CB_FILTER' => 'Y',
			'HAS_CB' => 'N'
		);
		$arSocNetAllowedSubscribeEntityTypesDesc[CCrmLiveFeedEntity::SuspendedDeal] = array(
			'USE_CB_FILTER' => 'Y',
			'HAS_CB' => 'N'
		);
		$arSocNetAllowedSubscribeEntityTypesDesc[CCrmLiveFeedEntity::SuspendedActivity] = array(
			'USE_CB_FILTER' => 'Y',
			'HAS_CB' => 'N'
		);
	}
	public static function OnFillSocNetLogEvents(&$events)
	{
		$lf_entities = CCrmLiveFeedEntity::GetAll();

		foreach($lf_entities as $lf_entity)
		{
			$infos = CCrmLiveFeedEvent::PrepareEntityEventInfos($lf_entity);
			if(!empty($infos))
			{
				foreach($infos as &$info)
				{
					$eventID = $info['EVENT_ID'];
					$commentEventID = $info['COMMENT_EVENT_ID'];

					$events[$eventID] = array(
						'ENTITIES' => array(
							$lf_entity => array(),
						),
						'CLASS_FORMAT' => 'CCrmLiveFeed',
						'METHOD_FORMAT' => 'FormatEvent',
						'HAS_CB' => 'N',
						'COMMENT_EVENT' => array(
							'EVENT_ID' => $commentEventID,
							'CLASS_FORMAT' => 'CCrmLiveFeed',
							'METHOD_FORMAT' => 'FormatComment'
						)
					);

					if (!empty($info['COMMENT_ADD_CALLBACK']))
					{
						$events[$eventID]['COMMENT_EVENT']['ADD_CALLBACK'] = $info['COMMENT_ADD_CALLBACK'];
					}

					if (!empty($info['COMMENT_UPDATE_CALLBACK']))
					{
						$events[$eventID]['COMMENT_EVENT']['UPDATE_CALLBACK'] = $info['COMMENT_UPDATE_CALLBACK'];
					}

					if (!empty($info['COMMENT_DELETE_CALLBACK']))
					{
						$events[$eventID]['COMMENT_EVENT']['DELETE_CALLBACK'] = $info['COMMENT_DELETE_CALLBACK'];
					}
				}
				unset($info);
			}
		}
	}
	public static function FormatEvent($arFields, $arParams, $bMail = false)
	{
		global $APPLICATION, $CACHE_MANAGER;
		ob_start();
		if ($arFields['ENTITY_TYPE'] == CCrmLiveFeedEntity::Activity)
		{
			if ($arActivity = CCrmActivity::GetByID($arFields["ENTITY_ID"], false))
			{
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->RegisterTag("CRM_ACTIVITY_".$arFields["ENTITY_ID"]);
					if ($arActivity["TYPE_ID"] == CCrmActivityType::Call)
					{
						$CACHE_MANAGER->RegisterTag("CRM_CALLTO_SETTINGS");
					}
				}

				$arActivity["COMMUNICATIONS"] = CCrmActivity::GetCommunications($arActivity["ID"]);
				$arComponentReturn = $APPLICATION->IncludeComponent('bitrix:crm.livefeed.activity', '', array(
					'FIELDS' => $arFields,
					'ACTIVITY' => $arActivity,
					'PARAMS' => $arParams
				), null, array('HIDE_ICONS' => 'Y'));
			}
		}
		elseif ($arFields['ENTITY_TYPE'] == CCrmLiveFeedEntity::Invoice)
		{
			if ($arInvoice = CCrmInvoice::GetByID($arFields["ENTITY_ID"]))
			{
				if (!array_key_exists("URL", $arInvoice))
				{
					$arInvoice["URL"] = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Invoice, $arFields["ENTITY_ID"]);
				}

				$arComponentReturn = $APPLICATION->IncludeComponent('bitrix:crm.livefeed.invoice', '', array(
					'FIELDS' => $arFields,
					'INVOICE' => $arInvoice,
					'PARAMS' => $arParams
				), null, array('HIDE_ICONS' => 'Y'));
			}
		}
		else
		{
			$arComponentReturn = $APPLICATION->IncludeComponent('bitrix:crm.livefeed', '', array(
				'FIELDS' => $arFields,
				'PARAMS' => $arParams
			), null, array('HIDE_ICONS' => 'Y'));
		}

		$html_message = ob_get_clean();

		$arRights = array();
		$arEventFields = array(
			"LOG_ID" => $arFields["ID"],
			"EVENT_ID" => $arFields["EVENT_ID"]
		);

		if ($arFields["ENTITY_TYPE"] == CCrmLiveFeedEntity::Activity)
		{
			$dbRight = CSocNetLogRights::GetList(
				array(),
				array("LOG_ID" => $arFields["ID"])
			);
			while ($arRight = $dbRight->Fetch())
			{
				if (preg_match('/^SG(\d+)$/', $arRight["GROUP_CODE"], $matches))
				{
					$arRights[] = $arRight["GROUP_CODE"];
				}
			}
		}

		if (($arParams["MOBILE"] ?? null) === "Y")
		{
			self::OnBeforeSocNetLogEntryGetRights($arEventFields, $arRights);
			$arDestination = CSocNetLogTools::FormatDestinationFromRights($arRights, array_merge($arParams, array("CREATED_BY" => $arFields["USER_ID"], "USE_ALL_DESTINATION" => true)), $iMoreCount);

			if (
				$arFields['ENTITY_TYPE'] == CCrmLiveFeedEntity::Activity
				&& $arActivity
				&& $arActivity["TYPE_ID"] == CCrmActivityType::Task
			)
			{
				$title_24 = '';
				$description = htmlspecialcharsbx($arActivity["SUBJECT"]);
				$description_style = 'task';
			}
			else
			{
				$title_24 = GetMessage('CRM_LF_MESSAGE_TITLE_24');
				$description = '';
				$description_style = '';
			}

			$arResult = array(
				'EVENT' => $arFields,
				'EVENT_FORMATTED' => array(
					'TITLE_24' => $title_24,
					"MESSAGE" => htmlspecialcharsbx($html_message),
					"IS_IMPORTANT" => false,
					"DESTINATION" => $arDestination,
					"DESCRIPTION" => $description,
					"DESCRIPTION_STYLE" => $description_style
				),
				"AVATAR_SRC" => CSocNetLog::FormatEvent_CreateAvatar($arFields, $arParams, 'CREATED_BY')
			);
		}
		else
		{
			if ($arFields["ENTITY_TYPE"] == CCrmLiveFeedEntity::Activity)
			{
				$arEventFields["ACTIVITY"] = $arActivity;
			}
			elseif ($arFields["ENTITY_TYPE"] == CCrmLiveFeedEntity::Invoice)
			{
				$arEventFields["ACTIVITY"] = $arInvoice;
			}

			self::OnBeforeSocNetLogEntryGetRights($arEventFields, $arRights);
			if (
				$arFields["ENTITY_TYPE"] == CCrmLiveFeedEntity::Activity
				&& $arActivity
			)
			{
				if ($arActivity["TYPE_ID"] == CCrmActivityType::Call)
				{
					if($arActivity["DIRECTION"] == CCrmActivityDirection::Incoming)
					{
						$title24_2 = GetMessage("CRM_LF_ACTIVITY_CALL_INCOMING_TITLE");
					}
					elseif($arActivity["DIRECTION"] == CCrmActivityDirection::Outgoing)
					{
						$title24_2 = GetMessage("CRM_LF_ACTIVITY_CALL_OUTGOING_TITLE");
					}
					$title24_2 = str_replace(
						"#COMPLETED#",
						"<i>".GetMessage($arActivity["COMPLETED"] === "Y" ? "CRM_LF_ACTIVITY_CALL_COMPLETED" : "")."</i>",
						$title24_2
					);
				}
				elseif ($arActivity["TYPE_ID"] == CCrmActivityType::Email)
				{
					if($arActivity["DIRECTION"] == CCrmActivityDirection::Incoming)
					{
						$title24_2 = GetMessage("CRM_LF_ACTIVITY_EMAIL_INCOMING_TITLE");
					}
					elseif($arActivity["DIRECTION"] == CCrmActivityDirection::Outgoing)
					{
						$title24_2 = GetMessage("CRM_LF_ACTIVITY_EMAIL_OUTGOING_TITLE");
					}
				}
				elseif ($arActivity["TYPE_ID"] == CCrmActivityType::Meeting)
				{
					$title24_2 = GetMessage("CRM_LF_ACTIVITY_MEETING_TITLE");

					$title24_2 = str_replace(
						"#COMPLETED#",
						"<i>".GetMessage($arActivity["COMPLETED"] === "Y" ? "CRM_LF_ACTIVITY_MEETING_COMPLETED" : "CRM_LF_ACTIVITY_MEETING_NOT_COMPLETED")."</i>",
						$title24_2
					);
				}
				$title24_2_style = "crm-feed-activity-status";
			}

			$arDestination = CSocNetLogTools::FormatDestinationFromRights($arRights, array_merge($arParams, array("CREATED_BY" => $arFields["USER_ID"], "USE_ALL_DESTINATION" => true)), $iMoreCount);

			$arResult = array(
				'EVENT' => $arFields,
				'EVENT_FORMATTED' => array(
					'URL' => "",
					"MESSAGE" => htmlspecialcharsbx($html_message),
					"IS_IMPORTANT" => false,
					"DESTINATION" => $arDestination
				)
			);

			if (
				$arFields["ENTITY_TYPE"] == CCrmLiveFeedEntity::Activity
				&& $arActivity["TYPE_ID"] == CCrmActivityType::Email
				&& $arActivity["DIRECTION"] == CCrmActivityDirection::Incoming
			)
			{
				switch ($arActivity['OWNER_TYPE_ID'])
				{
					case CCrmOwnerType::Company:
						$rsCrmCompany = CCrmCompany::GetListEx(array(), array('ID' => $arActivity['OWNER_ID'], 'CHECK_PERMISSIONS' => 'N', '@CATEGORY_ID' => 0,), false, false, array('LOGO'));
						if ($arCrmCompany = $rsCrmCompany->Fetch())
						{
							$fileID = $arCrmCompany['LOGO'];
						}
						break;
					case CCrmOwnerType::Contact:
						$rsCrmContact = CCrmContact::GetListEx(array(), array('ID' => $arActivity['OWNER_ID'], 'CHECK_PERMISSIONS' => 'N', '@CATEGORY_ID' => 0,), false, false, array('PHOTO'));
						if ($arCrmContact = $rsCrmContact->Fetch())
						{
							$fileID = $arCrmContact['PHOTO'];
						}
						break;
					default:
						$fileID = false;
				}

				$arResult["AVATAR_SRC"] = CSocNetLog::FormatEvent_CreateAvatar(
					array(
						'PERSONAL_PHOTO' => $fileID
					),
					$arParams,
					''
				);
			}
			else
			{
				$arResult["AVATAR_SRC"] = CSocNetLog::FormatEvent_CreateAvatar($arFields, $arParams, 'CREATED_BY');
			}

			if (isset($title24_2))
			{
				$arResult["EVENT_FORMATTED"]["TITLE_24_2"] = $title24_2;
				if (isset($title24_2_style))
				{
					$arResult["EVENT_FORMATTED"]["TITLE_24_2_STYLE"] = $title24_2_style;
				}
			}

			$arResult["CACHED_CSS_PATH"] = array(
				"/bitrix/themes/.default/crm-entity-show.css"
			);

			$arResult["CACHED_JS_PATH"] = array(
				"/bitrix/js/crm/progress_control.js",
				"/bitrix/js/crm/activity.js",
				"/bitrix/js/crm/common.js"
			);

			if (IsModuleInstalled("tasks"))
			{
				$arResult["CACHED_CSS_PATH"][] = "/bitrix/js/tasks/css/tasks.css";
			}

			if (is_array($arComponentReturn) && !empty($arComponentReturn["CACHED_CSS_PATH"]))
			{
				$arResult["CACHED_CSS_PATH"][] = $arComponentReturn["CACHED_CSS_PATH"];
			}

			if (is_array($arComponentReturn) && !empty($arComponentReturn["CACHED_JS_PATH"]))
			{
				$arResult["CACHED_JS_PATH"][] = $arComponentReturn["CACHED_JS_PATH"];
			}
		}

		if (
			$arFields["ENTITY_TYPE"] == CCrmLiveFeedEntity::Activity
			&& $arActivity["TYPE_ID"] == CCrmActivityType::Email
			&& $arActivity["DIRECTION"] == CCrmActivityDirection::Incoming
		)
		{
			$arResult['CREATED_BY']['FORMATTED'] = htmlspecialcharsbx(CCrmOwnerType::GetCaption($arActivity['OWNER_TYPE_ID'], $arActivity['OWNER_ID'], false));
		}
		else
		{
			$arFieldsTooltip = array(
				'ID' => $arFields['USER_ID'],
				'NAME' => $arFields['~CREATED_BY_NAME'] ?? null,
				'LAST_NAME' => $arFields['~CREATED_BY_LAST_NAME'] ?? null,
				'SECOND_NAME' => $arFields['~CREATED_BY_SECOND_NAME'] ?? null,
				'LOGIN' => $arFields['~CREATED_BY_LOGIN'] ?? null,
			);
			$arResult['CREATED_BY']['TOOLTIP_FIELDS'] = CSocNetLogTools::FormatEvent_FillTooltip($arFieldsTooltip, $arParams);
		}

		if (
			$arFields['ENTITY_TYPE'] == CCrmLiveFeedEntity::Activity
			&& $arActivity
			&& $arActivity["TYPE_ID"] == CCrmActivityType::Task
		)
		{
			$arResult["COMMENTS_PARAMS"] = array(
				"ENTITY_TYPE" => "TK",
				"ENTITY_XML_ID" => "TASK_".$arActivity["ASSOCIATED_ENTITY_ID"],
				"NOTIFY_TAGS" => "FORUM|COMMENT"
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
		{
			return $arResult;
		}

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => "",
			"MESSAGE" => $arFields["MESSAGE"]
		);

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
				"USER" => (isset($arParams["IM"]) && $arParams["IM"] === "Y" ? "N" : "Y"),
				"ALIGN" => "Y"
			);

			if (!$parserLog)
			{
				$parserLog = new forumTextParser(LANGUAGE_ID);
			}

			$parserLog->arUserfields = $arFields["UF"];
			$parserLog->pathToUser = $arParams["PATH_TO_USER"];

			if ($arParams['MOBILE'] !== 'Y')
			{
				if (!empty($arParams['IMAGE_MAX_WIDTH']))
				{
					$parserLog->imageWidth = (int)$arParams['IMAGE_MAX_WIDTH'];
				}
				if (!empty($arParams['IMAGE_MAX_HEIGHT']))
				{
					$parserLog->imageHeight = (int)$arParams['IMAGE_MAX_HEIGHT'];
				}
			}

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
				"USERFIELDS" => $arFields["UF"]
			);

			if (!$parserLog)
			{
				$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
			}

			$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));
		}
		return $arResult;
	}
	static private function BuildRelationSelectSql(&$params)
	{
		$slRelTableName =  CCrmSonetRelation::TABLE_NAME;
		$parent = isset($params['PARENT']) ? $params['PARENT'] : array();
		$level = 1;
		$parents = array($parent);

		$nextParent = isset($parent['PARENT']) ? $parent['PARENT'] : null;
		while(is_array($nextParent))
		{
			$level++;
			$parents[] = $nextParent;
			$nextParent = isset($nextParent['PARENT']) ? $nextParent['PARENT'] : null;
		}

		$curParent = $parents[$level - 1];
		$parentEntityType = isset($curParent['ENTITY_TYPE']) ? $curParent['ENTITY_TYPE'] : '';
		$parentEntityID = isset($curParent['ENTITY_ID']) ? intval($curParent['ENTITY_ID']) : 0;

		$alias = 'R';
		$sqlFrom = "{$slRelTableName} R";
		$sqlWhere = "R.SL_PARENT_ENTITY_TYPE = '{$parentEntityType}' AND R.PARENT_ENTITY_ID = {$parentEntityID} AND R.LVL = {$level}";

		$subFilters = isset($params['SUB_FILTERS']) ? $params['SUB_FILTERS'] : null;
		if(!is_array($subFilters) || empty($subFilters))
		{
			return false;
		}

		$subFilterResults = array();
		foreach($subFilters as &$subFilter)
		{
			$entityType = isset($subFilter['ENTITY_TYPE']) ? $subFilter['ENTITY_TYPE'] : '';
			if($entityType === '')
			{
				continue;
			}

			$eventID = isset($subFilter['EVENT_ID']) ? $subFilter['EVENT_ID'] : '';
			$eventIDs = '';
			if(is_string($eventID) && $eventID !== '')
			{
				$eventIDs = "'{$eventID}'";
			}
			elseif(is_array($eventID))
			{
				foreach($eventID as $v)
				{
					if($v === '')
					{
						continue;
					}

					if($eventIDs !== '')
					{
						$eventIDs .= ', ';
					}

					$eventIDs .= "'{$v}'";
				}
			}

			$subFilterResults[] = $eventIDs !== ''
				? "({$alias}.SL_ENTITY_TYPE = '{$entityType}' AND {$alias}.SL_EVENT_ID IN ({$eventIDs}))"
				: "{$alias}.SL_ENTITY_TYPE = '{$entityType}'";
		}
		unset($subFilter);

		if(empty($subFilterResults))
		{
			return false;
		}

		if(count($subFilterResults) > 1)
		{
			$logic = isset($params['LOGIC']) ? $params['LOGIC'] : 'AND';
			$subFilterSql = '('.implode(" {$logic} ", $subFilterResults).')';
		}
		else
		{
			$subFilterSql = $subFilterResults[0] ;
		}

		return $sqlWhere !== ''
			? "SELECT {$alias}.SL_ID AS ID FROM {$sqlFrom} WHERE {$sqlWhere} AND {$subFilterSql}"
			: "SELECT {$alias}.SL_ID AS ID FROM {$sqlFrom} AND {$subFilterSql}";
	}
	static public function BuildRelationFilterSql($vals, $key, $operation, $isNegative, $field, $fields, $filter)
	{
		if($key !== 'CRM_RELATION')
		{
			return false;
		}
		$selctSql = self::BuildRelationSelectSql($vals);
		return "{$field} IN ({$selctSql})";
	}
	static private function BuildSubscriptionSelectSql(&$params, $options = array())
	{
		global $DB;
		$userID = isset($params['USER_ID']) ? intval($params['USER_ID']) : 0;
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$startTime = isset($options['START_TIME']) ? $options['START_TIME'] : '';
		if($startTime !== '')
		{
			$startTime = $DB->CharToDateFunction($DB->ForSql($startTime), 'FULL');
		}
		$top = isset($options['TOP']) ? intval($options['TOP']) : 0;

		$allEntities = isset($params['ENTITY_TYPES']) ? $params['ENTITY_TYPES'] : null;
		if(!is_array($allEntities) || empty($allEntities))
		{
			$allEntities = CCrmLiveFeedEntity::GetAll();
		}
		$allLeafTypes = CCrmLiveFeedEntity::GetLeafs();

		$subscrTableName = CCrmSonetSubscription::TABLE_NAME;
		$relTableName = CCrmSonetRelation::TABLE_NAME;

		$rootTypes = array_diff($allEntities, $allLeafTypes);
		$rootTypeSql = !empty($rootTypes) ? CCrmLiveFeedEntity::GetForSqlString($rootTypes) : '';
		if($rootTypeSql === '')
		{
			$rootSql = '';
		}
		else
		{
			$rootSql =
				"SELECT L1.ID FROM b_sonet_log L1
					INNER JOIN {$subscrTableName} S1
						ON S1.USER_ID = {$userID}
						AND L1.ENTITY_TYPE = S1.SL_ENTITY_TYPE
						AND L1.ENTITY_ID = S1.ENTITY_ID
						AND L1.ENTITY_TYPE IN ({$rootTypeSql})";

			if($startTime !== '')
			{
				$rootSql .= " AND L1.LOG_UPDATE >= {$startTime}";
			}

			if($top > 0)
			{
				$rootSql .= ' ORDER BY L1.LOG_UPDATE DESC';
				CSqlUtil::PrepareSelectTop($rootSql, $top, 'mysql');
			}
		}

		$leafTypes = array_intersect($allEntities, $allLeafTypes);
		$leafTypeSql = !empty($leafTypes) ? CCrmLiveFeedEntity::GetForSqlString($leafTypes) : '';

		if($leafTypeSql === '')
		{
			$leafSql = '';
		}
		else
		{
			$leafSql =
				"SELECT R1.SL_ID AS ID FROM {$relTableName} R1
					INNER JOIN {$subscrTableName} S1
						ON S1.USER_ID = {$userID}
						AND R1.SL_ENTITY_TYPE IN($leafTypeSql)
						AND R1.LVL = 1
						AND R1.SL_PARENT_ENTITY_TYPE = S1.SL_ENTITY_TYPE
						AND R1.PARENT_ENTITY_ID = S1.ENTITY_ID";

			if($startTime !== '')
			{
				$leafSql .= " AND R1.SL_LAST_UPDATED >= {$startTime}";
			}

			if($top > 0)
			{
				$leafSql .= ' ORDER BY R1.SL_LAST_UPDATED DESC';
				CSqlUtil::PrepareSelectTop($leafSql, $top, 'mysql');
			}
		}

		return array(
			'ROOT_SQL' => $rootSql,
			'LEAF_SQL' => $leafSql
		);
	}
	static public function BuildUserSubscriptionFilterSql($vals, $key, $operation, $isNegative, $field, $fields, $filter)
	{
		if($key !== 'CRM_USER_SUBSCR')
		{
			return false;
		}

		$sqlData = self::BuildSubscriptionSelectSql($vals);
		$rootSql = isset($sqlData['ROOT_SQL']) ? $sqlData['ROOT_SQL'] : '';
		$leafSql = isset($sqlData['LEAF_SQL']) ? $sqlData['LEAF_SQL'] : '';

		if ($rootSql !== '')
		{
			$rootSql = "{$field} IN ($rootSql)";
		}

		if ($leafSql !== '')
		{
			$leafSql = "{$field} IN ($leafSql)";
		}

		if ($rootSql !== '' && $leafSql !== '')
		{
			return "{$rootSql} OR {$leafSql}";
		}

		if ($rootSql !== '')
		{
			return $rootSql;
		}

		if ($leafSql !== '')
		{
			return $leafSql;
		}

		return false;
	}
	static private function BuilUserAuthorshipSelectSql(&$params, $options = array())
	{
		global $DB;
		$userID = (int)($params['USER_ID'] ?? 0);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$allEntitySql = CCrmLiveFeedEntity::GetForSqlString($params['ENTITY_TYPES'] ?? null);

		if(!is_array($options))
		{
			$options = array();
		}

		$startTime = $options['START_TIME'] ?? '';
		if($startTime !== '')
		{
			$startTime = $DB->CharToDateFunction($DB->ForSql($startTime), 'FULL');
		}
		$top = (int)($options['TOP'] ?? 0);

		$sql = "SELECT L1.ID FROM b_sonet_log L1 WHERE L1.USER_ID = {$userID} AND L1.ENTITY_TYPE IN ({$allEntitySql})";
		if($startTime !== '')
		{
			$sql .= " AND L1.LOG_UPDATE >= {$startTime}";
		}

		if($top > 0)
		{
			$sql .= ' ORDER BY L1.LOG_UPDATE DESC';
			CSqlUtil::PrepareSelectTop($sql, $top, 'mysql');
		}
		return $sql;
	}

	static public function BuildUserAuthorshipFilterSql($vals, $key, $operation, $isNegative, $field, $fields, $filter)
	{
		if($key !== 'CRM_USER_AUTHOR')
		{
			return false;
		}

		$sql = self::BuilUserAuthorshipSelectSql($vals);
		return "{$field} IN ($sql)";
	}

	static private function BuilUserAddresseeSelectSql(&$params, $options = array())
	{
		global $DB;
		$userID = (int)($params['USER_ID'] ?? 0);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$startTime = $options['START_TIME'] ?? '';
		if($startTime !== '')
		{
			$startTime = $DB->CharToDateFunction($DB->ForSql($startTime), 'FULL');
		}
		$top = (int)($options['TOP'] ?? 0);

		$allEntitySql = CCrmLiveFeedEntity::GetForSqlString($params['ENTITY_TYPES'] ?? null);
		$sql = "SELECT L1.ID FROM b_sonet_log L1
				INNER JOIN b_sonet_log_right LR1
					ON L1.ID = LR1.LOG_ID AND LR1.GROUP_CODE = 'U{$userID}'
					AND L1.ENTITY_TYPE IN ({$allEntitySql})";

		if($startTime !== '')
		{
			$sql .= " AND L1.LOG_UPDATE >= {$startTime}";
		}

		if($top > 0)
		{
			$sql .= ' ORDER BY L1.LOG_UPDATE DESC';
			CSqlUtil::PrepareSelectTop($sql, $top, 'mysql');
		}
		return $sql;
	}
	static public function BuildUserAddresseeFilterSql($vals, $key, $operation, $isNegative, $field, $fields, $filter)
	{
		if($key !== 'CRM_USER_ADDRESSEE')
		{
			return false;
		}

		$sql = self::BuilUserAddresseeSelectSql($vals);
		return "{$field} IN ({$sql})";
	}
	static public function OnFillSocNetLogFields(&$fields)
	{
		if(!isset($fields['CRM_RELATION']))
		{
			$fields['CRM_RELATION'] = array('FIELD' => 'L.ID', 'WHERE' => array('CCrmLiveFeed', 'BuildRelationFilterSql'));
		}

		if(!isset($fields['CRM_USER_SUBSCR']))
		{
			$fields['CRM_USER_SUBSCR'] = array('FIELD' => 'L.ID', 'WHERE' => array('CCrmLiveFeed', 'BuildUserSubscriptionFilterSql'));
		}

		if(!isset($fields['CRM_USER_AUTHOR']))
		{
			$fields['CRM_USER_AUTHOR'] = array('FIELD' => 'L.ID', 'WHERE' => array('CCrmLiveFeed', 'BuildUserAuthorshipFilterSql'));
		}

		if(!isset($fields['CRM_USER_ADDRESSEE']))
		{
			$fields['CRM_USER_ADDRESSEE'] = array('FIELD' => 'L.ID', 'WHERE' => array('CCrmLiveFeed', 'BuildUserAddresseeFilterSql'));
		}
	}
	static public function OnBuildSocNetLogPerms(&$perms, $params)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		$aliasPrefix = isset($params['ALIAS_PREFIX']) ? $params['ALIAS_PREFIX'] : 'L';
		$permType = isset($params['PERM_TYPE']) ? $params['PERM_TYPE'] : 'READ';
		$options = isset($params['OPTIONS']) ? $params['OPTIONS'] : null;
		if(!is_array($options))
		{
			$options = array();
		}

		//The parameter 'IDENTITY_COLUMN' is required for CCrmPerms::BuildSql
		if(!(isset($options['IDENTITY_COLUMN'])
			&& is_string($options['IDENTITY_COLUMN'])
			&& $options['IDENTITY_COLUMN'] !== ''))
		{
			$options['IDENTITY_COLUMN'] = 'ENTITY_ID';
		}

		$filterParams = isset($params['FILTER_PARAMS']) ? $params['FILTER_PARAMS'] : null;
		if(!is_array($filterParams))
		{
			$filterParams = array();
		}

		//$entityType = isset($filterParams['ENTITY_TYPE']) ? $filterParams['ENTITY_TYPE'] : '';
		//$entityID = isset($filterParams['ENTITY_ID']) ? intval($filterParams['ENTITY_ID']) : 0;

		$affectedEntityTypes = isset($filterParams['AFFECTED_TYPES']) && is_array($filterParams['AFFECTED_TYPES'])
			? $filterParams['AFFECTED_TYPES'] : array();

		$result = array();
		if(empty($affectedEntityTypes))
		{
			//By default preparing SQL for all CRM types
			$activityPerms = array();

			$result[CCrmLiveFeedEntity::Lead] = CCrmPerms::BuildSql(CCrmOwnerType::LeadName, $aliasPrefix, $permType, $options);
			$activityPerms[CCrmLiveFeedEntity::Lead] = CCrmPerms::BuildSql(CCrmOwnerType::LeadName, 'R', $permType, array('IDENTITY_COLUMN' => 'PARENT_ENTITY_ID'));

			$result[CCrmLiveFeedEntity::Contact] = CCrmPerms::BuildSql(CCrmOwnerType::ContactName, $aliasPrefix, $permType, $options);
			$activityPerms[CCrmLiveFeedEntity::Contact] = CCrmPerms::BuildSql(CCrmOwnerType::ContactName, 'R', $permType, array('IDENTITY_COLUMN' => 'PARENT_ENTITY_ID'));

			$result[CCrmLiveFeedEntity::Company] = CCrmPerms::BuildSql(CCrmOwnerType::CompanyName, $aliasPrefix, $permType, $options);
			$activityPerms[CCrmLiveFeedEntity::Company] = CCrmPerms::BuildSql(CCrmOwnerType::CompanyName, 'R', $permType, array('IDENTITY_COLUMN' => 'PARENT_ENTITY_ID'));

			$dealPermissionTypes = array_merge(
				array(CCrmOwnerType::DealName),
				\Bitrix\Crm\Category\DealCategory::getPermissionEntityTypeList()
			);

			$result[CCrmLiveFeedEntity::Deal] = CCrmPerms::BuildSqlForEntitySet(
				$dealPermissionTypes,
				$aliasPrefix,
				$permType,
				$options
			);
			$activityPerms[CCrmLiveFeedEntity::Deal] = CCrmPerms::BuildSqlForEntitySet(
				$dealPermissionTypes,
				'R',
				$permType,
				array('IDENTITY_COLUMN' => 'PARENT_ENTITY_ID')
			);

			$result[CCrmLiveFeedEntity::Invoice] = CCrmPerms::BuildSql(CCrmOwnerType::InvoiceName, $aliasPrefix, $permType, $options);

			$isRestricted = false;
			$activityFeedEnityType = CCrmLiveFeedEntity::Activity;
			$relationTableName = CCrmSonetRelation::TABLE_NAME;
			foreach($activityPerms as $type => $sql)
			{
				if($sql === '')
				{
					$activityPerms[$type] = "SELECT R.ENTITY_ID FROM {$relationTableName} R WHERE R.SL_ENTITY_TYPE = '{$activityFeedEnityType}' AND R.SL_PARENT_ENTITY_TYPE = '{$type}'";
					continue;
				}

				if(!$isRestricted)
				{
					$isRestricted = true;
				}

				if($sql === false)
				{
					unset ($activityPerms[$type]);
					continue;
				}
				$activityPerms[$type] = "SELECT R.ENTITY_ID FROM {$relationTableName} R WHERE R.SL_ENTITY_TYPE = '{$activityFeedEnityType}' AND R.SL_PARENT_ENTITY_TYPE = '{$type}' AND {$sql}";
			}

			if(!$isRestricted)
			{
				$result[CCrmLiveFeedEntity::Activity] = '';
			}
			elseif(!empty($activityPerms))
			{
				$result[CCrmLiveFeedEntity::Activity] = $aliasPrefix.'.'.$options['IDENTITY_COLUMN'].' IN ('.(implode(' UNION ALL ', $activityPerms)).')';
			}
		}
		else
		{
			$dealPermissionTypes = array_merge(
				array(CCrmOwnerType::DealName),
				\Bitrix\Crm\Category\DealCategory::getPermissionEntityTypeList()
			);

			if(in_array(CCrmLiveFeedEntity::Activity, $affectedEntityTypes, true))
			{
				$activityPerms = array();

				$activityPerms[CCrmLiveFeedEntity::Lead] = CCrmPerms::BuildSql(CCrmOwnerType::LeadName, 'R', $permType, array('IDENTITY_COLUMN' => 'PARENT_ENTITY_ID'));
				$activityPerms[CCrmLiveFeedEntity::Contact] = CCrmPerms::BuildSql(CCrmOwnerType::ContactName, 'R', $permType, array('IDENTITY_COLUMN' => 'PARENT_ENTITY_ID'));
				$activityPerms[CCrmLiveFeedEntity::Company] = CCrmPerms::BuildSql(CCrmOwnerType::CompanyName, 'R', $permType, array('IDENTITY_COLUMN' => 'PARENT_ENTITY_ID'));

				$activityPerms[CCrmLiveFeedEntity::Deal] = CCrmPerms::BuildSqlForEntitySet(
					$dealPermissionTypes,
					'R',
					$permType,
					array('IDENTITY_COLUMN' => 'PARENT_ENTITY_ID')
				);

				$isRestricted = false;
				$activityFeedEnityType = CCrmLiveFeedEntity::Activity;
				$relationTableName = CCrmSonetRelation::TABLE_NAME;
				foreach($activityPerms as $type => $sql)
				{
					if($sql === '')
					{
						$activityPerms[$type] = "SELECT R.ENTITY_ID FROM {$relationTableName} R WHERE R.SL_ENTITY_TYPE = '{$activityFeedEnityType}' AND R.SL_PARENT_ENTITY_TYPE = '{$type}'";
						continue;
					}

					if(!$isRestricted)
					{
						$isRestricted = true;
					}

					if($sql === false)
					{
						unset ($activityPerms[$type]);
						continue;
					}
					$activityPerms[$type] = "SELECT R.ENTITY_ID FROM {$relationTableName} R WHERE R.SL_ENTITY_TYPE = '{$activityFeedEnityType}' AND R.SL_PARENT_ENTITY_TYPE = '{$type}' AND {$sql}";
				}
				if(!$isRestricted)
				{
					$result[CCrmLiveFeedEntity::Activity] = '';
				}
				elseif(!empty($activityPerms))
				{
					$result[CCrmLiveFeedEntity::Activity] = $aliasPrefix.'.'.$options['IDENTITY_COLUMN'].' IN ('.(implode(' UNION ALL ', $activityPerms)).')';
				}
			}

			if(in_array(CCrmLiveFeedEntity::Lead, $affectedEntityTypes, true))
			{
				$result[CCrmLiveFeedEntity::Lead] = CCrmPerms::BuildSql(CCrmOwnerType::LeadName, $aliasPrefix, $permType, $options);
			}

			if(in_array(CCrmLiveFeedEntity::Contact, $affectedEntityTypes, true))
			{
				$result[CCrmLiveFeedEntity::Contact] = CCrmPerms::BuildSql(CCrmOwnerType::ContactName, $aliasPrefix, $permType, $options);
			}

			if(in_array(CCrmLiveFeedEntity::Company, $affectedEntityTypes, true))
			{
				$result[CCrmLiveFeedEntity::Company] = CCrmPerms::BuildSql(CCrmOwnerType::CompanyName, $aliasPrefix, $permType, $options);
			}

			if(in_array(CCrmLiveFeedEntity::Deal, $affectedEntityTypes, true))
			{
				$result[CCrmLiveFeedEntity::Deal] = CCrmPerms::BuildSqlForEntitySet(
					$dealPermissionTypes,
					$aliasPrefix,
					$permType,
					$options
				);
			}

			if(in_array(CCrmLiveFeedEntity::Invoice, $affectedEntityTypes, true))
			{
				$result[CCrmLiveFeedEntity::Invoice] = CCrmPerms::BuildSql(CCrmOwnerType::InvoiceName, $aliasPrefix, $permType, $options);
			}
		}

		$resultSql = '';
		$isRestricted = false;

		if(!empty($result))
		{
			$entityTypeCol = 'ENTITY_TYPE';
			if(isset($options['ENTITY_TYPE_COLUMN'])
				&& is_string($options['ENTITY_TYPE_COLUMN'])
				&& $options['ENTITY_TYPE_COLUMN'] !== '')
			{
				$entityTypeCol = $options['ENTITY_TYPE_COLUMN'];
			}

			foreach($result as $type => &$sql)
			{
				if($sql === false)
				{
					//Access denied
					//$resultSql .= "({$aliasPrefix}.{$entityTypeCol} = '{$type}' AND 1<>1)";
					if(!$isRestricted)
					{
						$isRestricted = true;
					}
				}
				elseif(is_string($sql) && $sql !== '')
				{
					if($resultSql !== '')
					{
						$resultSql .= ' OR ';
					}
					$resultSql .= "({$aliasPrefix}.{$entityTypeCol} = '{$type}' AND {$sql})";
					if(!$isRestricted)
					{
						$isRestricted = true;
					}
				}
				else
				{
					if($resultSql !== '')
					{
						$resultSql .= ' OR ';
					}
					//All entities are allowed
					$resultSql .= "{$aliasPrefix}.{$entityTypeCol} = '{$type}'";
				}
			}
			unset($sql);
		}

		if($isRestricted)
		{
			if($resultSql !== '')
			{
				$perms[] = "({$resultSql})";
			}
			else
			{
				//Access denied
				$perms[] = false;
			}
		}
	}

	static public function OnBuildSocNetLogSql(&$arFields, &$arOrder, &$arFilter, &$arGroupBy, &$arSelectFields, &$arSqls)
	{
		if(!isset($arFilter['__CRM_JOINS']))
		{
			return;
		}

		$joins = $arFilter['__CRM_JOINS'];
		foreach($joins as &$join)
		{
			$sql = isset($join['SQL']) ? $join['SQL'] : '';
			if($sql !== '')
			{
				$arSqls['FROM'] .= ' '.$sql;
			}
		}
		unset($join);
	}

	static public function OnBuildSocNetLogFilter(&$filter, &$params, &$componentParams)
	{
		if(isset($filter['<=LOG_DATE']) && $filter['<=LOG_DATE'] === 'NOW')
		{
			//HACK: Clear filter by current time - is absolutely useless in CRM context and prevent db-engine from caching of query.
			unset($filter['<=LOG_DATE']);
		}

		if(isset($filter['SITE_ID']) && \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			//HACK: Clear filter by SITE_ID in bitrix24 context.
			unset($filter['SITE_ID']);
		}

		if(!is_array($params))
		{
			$params = array();
		}

		if(!(isset($params['AFFECTED_TYPES']) && is_array($params['AFFECTED_TYPES'])))
		{
			$params['AFFECTED_TYPES'] = array();
		}

		$entityType = isset($params['ENTITY_TYPE']) ? $params['ENTITY_TYPE'] : '';
		$entityID = isset($params['ENTITY_ID']) ? intval($params['ENTITY_ID']) : 0;
		$options = isset($params['OPTIONS']) ? $params['OPTIONS'] : null;
		if(!is_array($options))
		{
			$options = array();
		}

		$customData = isset($options['CUSTOM_DATA']) ? $options['CUSTOM_DATA'] : null;
		if(!is_array($customData))
		{
			$customData = array();
		}

		$presetTopID = isset($customData['CRM_PRESET_TOP_ID']) ? $customData['CRM_PRESET_TOP_ID'] : '';
		$presetID = isset($customData['CRM_PRESET_ID']) ? $customData['CRM_PRESET_ID'] : '';

		$entityTypeID = CCrmLiveFeedEntity::ResolveEntityTypeID($entityType);
		if($entityTypeID === CCrmOwnerType::Undefined)
		{
			$isActivityPresetEnabled = $presetID === 'activities';
			$isMessagePresetEnabled = $presetID === 'messages';
			$affectedEntityTypes = array();

			if($isActivityPresetEnabled)
			{
				$filter['ENTITY_TYPE'] = CCrmLiveFeedEntity::Activity;
				$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Activity);
				$affectedEntityTypes[] = CCrmLiveFeedEntity::Activity;
			}
			else
			{
				if($isMessagePresetEnabled)
				{
					$filter['@EVENT_ID'] = array(
						CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Lead, CCrmLiveFeedEvent::Message),
						CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Contact, CCrmLiveFeedEvent::Message),
						CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Company, CCrmLiveFeedEvent::Message),
						CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Deal, CCrmLiveFeedEvent::Message),
						CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Invoice, CCrmLiveFeedEvent::Message)
					);
				}
				else
				{
					//Prepare general crm entities log
					$filter['@ENTITY_TYPE'] = array(
						CCrmLiveFeedEntity::Lead,
						CCrmLiveFeedEntity::Contact,
						CCrmLiveFeedEntity::Company,
						CCrmLiveFeedEntity::Deal,
						CCrmLiveFeedEntity::Activity,
						CCrmLiveFeedEntity::Invoice
					);
				}
			}

			if(
				$presetTopID !== 'all'
				&& (!isset($filter["ID"]) || intval($filter["ID"]) <= 0)
			)
			{
				$joinData = array();

				$filterValue = array(
					'USER_ID' => CCrmSecurityHelper::GetCurrentUserID(),
					'ENTITY_TYPES' => $affectedEntityTypes // optimization
				);

				$sqlOptions = array('TOP' => 20000);
				$startTime = isset($filter['>=LOG_UPDATE']) ? $filter['>=LOG_UPDATE'] : '';
				if($startTime !== '')
				{
					$sqlOptions['START_TIME'] = $startTime;
				}

				$subscrSqlData = self::BuildSubscriptionSelectSql($filterValue, $sqlOptions);
				$subscrRootSql = isset($subscrSqlData['ROOT_SQL']) ? $subscrSqlData['ROOT_SQL'] : '';
				$subscrLeafSql = isset($subscrSqlData['LEAF_SQL']) ? $subscrSqlData['LEAF_SQL'] : '';

				if($subscrRootSql !== '')
				{
					$joinData[] = "({$subscrRootSql})";
				}
				if($subscrLeafSql !== '')
				{
					$joinData[] = "({$subscrLeafSql})";
				}

				$userAuthorshipSql = self::BuilUserAuthorshipSelectSql($filterValue, $sqlOptions);
				if($userAuthorshipSql !== '')
				{
					$joinData[] = "({$userAuthorshipSql})";
				}

				$userAddresseeSql = self::BuilUserAddresseeSelectSql($filterValue, $sqlOptions);
				if($userAddresseeSql !== '')
				{
					$joinData[] = "({$userAddresseeSql})";
				}

				if(!empty($joinData))
				{
					if(isset($filter['__CRM_JOINS']))
					{
						$filter['__CRM_JOINS'] = array();
					}

					$joinSql = implode(' UNION ', $joinData);
					$filter['__CRM_JOINS'][] = array(
						'TYPE' => 'INNER',
						'SQL' =>"INNER JOIN ({$joinSql}) T ON T.ID = L.ID"
					);
					AddEventHandler('socialnetwork',  'OnBuildSocNetLogSql', array(__class__, 'OnBuildSocNetLogSql'));
					if(isset($filter['>=LOG_UPDATE']))
					{
						unset($filter['>=LOG_UPDATE']);
					}
				}

				/*$filter['__INNER_FILTER_CRM'] = array(
					'__INNER_FILTER_CRM_SUBSCRIPTION' =>
						array(
							'LOGIC' => 'OR',
							'CRM_USER_SUBSCR' => array($filterValue),
							'CRM_USER_AUTHOR' => array($filterValue),
							'CRM_USER_ADDRESSEE' => array($filterValue)
						)
				);*/
			}
			else
			{
				$componentParams["SHOW_UNREAD"] = "N";
			}

			return;
		}

		if($entityID <= 0)
		{
			//Invalid arguments - entityType is specified, but entityID is not.
			return;
		}

		$isExtendedMode = $presetTopID === 'extended';
		$isActivityPresetEnabled = $presetID === 'activities';
		$isMessagePresetEnabled = $presetID === 'messages';
		$isPresetDisabled = !$isActivityPresetEnabled && !$isMessagePresetEnabled;

		$mainFilter = array();
		$level1Filter = array(
			'PARENT' => array(
				'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID),
				'ENTITY_ID' => $entityID
			),
			'LOGIC' => 'OR',
			'SUB_FILTERS' => array()
		);
		$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID);
		$level2Filter = null;

		//ACTIVITIES & MESSAGES -->
		if($isPresetDisabled || $isActivityPresetEnabled)
		{
			$level1Filter['SUB_FILTERS'][] = array('ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Activity));
			$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Activity);
		}

		if(!$isActivityPresetEnabled)
		{
			$mainFilter['LOGIC'] = 'OR';
			$mainFilter['__INNER_FILTER_CRM_ENTITY'] = array(
				'ENTITY_TYPE' => $entityType,
				'ENTITY_ID' => $entityID
			);

			if($isMessagePresetEnabled)
			{
				$mainFilter['__INNER_FILTER_CRM_ENTITY']['EVENT_ID'] = array(CCrmLiveFeedEvent::GetEventID($entityType, CCrmLiveFeedEvent::Message));
			}

			//MESSAGES -->
			$level1Filter['SUB_FILTERS'][] = array(
				'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Lead),
				'EVENT_ID' => CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Lead, CCrmLiveFeedEvent::Message)
			);
			$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Lead);

			$level1Filter['SUB_FILTERS'][] = array(
				'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Contact),
				'EVENT_ID' => CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Contact, CCrmLiveFeedEvent::Message)
			);
			$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Lead);

			$level1Filter['SUB_FILTERS'][] = array(
				'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Company),
				'EVENT_ID' => CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Company, CCrmLiveFeedEvent::Message)
			);
			$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Lead);

			$level1Filter['SUB_FILTERS'][] = array(
				'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Deal),
				'EVENT_ID' => CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Deal, CCrmLiveFeedEvent::Message)
			);
			$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Deal);
			//<-- MESSAGES
		}
		//<-- ACTIVITIES & MESSAGES

		switch($entityTypeID)
		{
			case CCrmOwnerType::Contact:
			{
				//DEALS -->
				$dealEvents = array();
				if($isPresetDisabled)
				{
					$dealEvents[] = CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Deal, CCrmLiveFeedEvent::Add);
					$dealEvents[] = CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Deal, CCrmLiveFeedEvent::Progress);
					$dealEvents[] = CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Deal, CCrmLiveFeedEvent::Client);
				}

				if(!empty($dealEvents))
				{
					$level1Filter['SUB_FILTERS'][] = array(
						'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Deal),
						'EVENT_ID' => $dealEvents
					);
					$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Deal);
				}
				//<-- DEALS

				//INVOICES -->
				if($isPresetDisabled)
				{
					$level1Filter['SUB_FILTERS'][] = array(
						'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Invoice)
					);
					$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Invoice);
				}
				//<-- INVOICES
			}
			break;
			case CCrmOwnerType::Company:
			{
				//CONTACTS -->
				$contactEvents = array();
				if($isPresetDisabled)
				{
					$contactEvents[] = CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Contact, CCrmLiveFeedEvent::Add);
				}

				if($isExtendedMode && $isPresetDisabled)
				{
					$contactEvents[] = CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Contact, CCrmLiveFeedEvent::Owner);
				}

				if(!empty($contactEvents))
				{
					$level1Filter['SUB_FILTERS'][] = array(
						'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Contact),
						'EVENT_ID' => $contactEvents
					);
					$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Contact);
				}
				//<-- CONTACTS

				//DEALS -->
				$dealEvents = array();
				if($isPresetDisabled)
				{
					$dealEvents[] = CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Deal, CCrmLiveFeedEvent::Add);
					$dealEvents[] = CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Deal, CCrmLiveFeedEvent::Progress);
					$dealEvents[] = CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Deal, CCrmLiveFeedEvent::Client);
				}

				if(!empty($dealEvents))
				{
					$level1Filter['SUB_FILTERS'][] = array(
						'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Deal),
						'EVENT_ID' => $dealEvents
					);
					$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Deal);
				}
				//<-- DEALS

				//INVOICES -->
				if($isPresetDisabled)
				{
					$level1Filter['SUB_FILTERS'][] = array(
						'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Invoice)
					);
					$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Invoice);
				}
				//<-- INVOICES

				//CONTACT ACTIVITIES -->
				if($isExtendedMode && ($isPresetDisabled || $isActivityPresetEnabled))
				{
					$level2Filter = array(
						'PARENT' => array(
							'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Contact),
							'PARENT' => array(
								'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Company),
								'ENTITY_ID' => $entityID
							)
						),
						'SUB_FILTERS' => array(
							array(
								'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Activity)
							)
						)
					);
				}
				//<-- CONTACT ACTIVITIES
			}
			break;
			case CCrmOwnerType::Deal:
				//INVOICES -->
				if($isPresetDisabled)
				{
					$level1Filter['SUB_FILTERS'][] = array(
						'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Invoice)
					);
					$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Invoice);
				}
				//<-- INVOICES
			break;
		}

		$relationFilters = array();
		if(!empty($level1Filter['SUB_FILTERS']))
		{
			$relationFilters[] = $level1Filter;
		}

		if(is_array($level2Filter))
		{
			$relationFilters[] = $level2Filter;
		}

		/*if(!empty($relationFilters))
		{
			$mainFilter['__INNER_FILTER_CRM_RELATION'] = array('CRM_RELATION' => $relationFilters);
		}
		$filter['__INNER_FILTER_CRM'] = $mainFilter;*/

		$joinData = array();
		if(!empty($mainFilter))
		{
			if(isset($mainFilter['__INNER_FILTER_CRM_ENTITY']))
			{
				$contextFilter = $mainFilter['__INNER_FILTER_CRM_ENTITY'];
				$entityTypeName =  $contextFilter['ENTITY_TYPE'];
				$entityID =  $contextFilter['ENTITY_ID'];
				$eventIDs =  isset($contextFilter['EVENT_ID']) ? $contextFilter['EVENT_ID'] : null;

				if($eventIDs !== null && !empty($eventIDs))
				{
					foreach($eventIDs as $k => $v)
					{
						$eventIDs[$k] = "'{$v}'";
					}

					$eventIDSql = implode(',', $eventIDs);
					$joinData[] = "(SELECT L1.ID FROM b_sonet_log L1 WHERE L1.ENTITY_TYPE = '{$entityTypeName}' AND L1.ENTITY_ID = {$entityID} AND L1.EVENT_ID IN({$eventIDSql}))";
				}
				else
				{
					$joinData[] = "(SELECT L1.ID FROM b_sonet_log L1 WHERE L1.ENTITY_TYPE = '{$entityTypeName}' AND L1.ENTITY_ID = {$entityID})";
				}
			}
		}

		if(!empty($relationFilters))
		{
			foreach($relationFilters as &$relationFilter)
			{
				$relationSql = self::BuildRelationSelectSql($relationFilter);
				if(is_string($relationSql) && $relationSql !== '')
				{
					$joinData[] = "({$relationSql})";
				}
			}
			unset($relationFilter);
		}

		if(!empty($joinData))
		{
			if(isset($filter['__CRM_JOINS']))
			{
				$filter['__CRM_JOINS'] = array();
			}

			$joinSql = implode(' UNION ', $joinData);
			$filter['__CRM_JOINS'][] = array(
				'TYPE' => 'INNER',
				'SQL' =>"INNER JOIN ({$joinSql}) T ON T.ID = L.ID"
			);
			AddEventHandler('socialnetwork',  'OnBuildSocNetLogSql', array(__class__, 'OnBuildSocNetLogSql'));
		}
	}
	static public function OnBuildSocNetLogOrder(&$arOrder, $arParams)
	{
		if (
			isset($arParams["CRM_ENTITY_TYPE"]) && $arParams["CRM_ENTITY_TYPE"] <> ''
			&& isset($arParams["CRM_ENTITY_ID"]) && intval($arParams["CRM_ENTITY_ID"]) > 0
		)
		{
			$arOrder = array("LOG_DATE"	=> "DESC");
		}
	}
	static public function OnSocNetLogFormatDestination(&$arDestination, $right_tmp, $arRights, $arParams, $bCheckPermissions)
	{
		if (preg_match('/^('.CCrmLiveFeedEntity::Contact.'|'.CCrmLiveFeedEntity::Lead.'|'.CCrmLiveFeedEntity::Company.'|'.CCrmLiveFeedEntity::Deal.')(\d+)$/', $right_tmp, $matches))
		{
			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->RegisterTag("crm_entity_name_".CCrmLiveFeedEntity::ResolveEntityTypeID($matches[1])."_".$matches[2]);
			}

			$arDestination[] = array(
				"TYPE" => $matches[1],
				"ID" => $matches[2],
				"CRM_PREFIX" => GetMessage('CRM_LF_'.$matches[1].'_DESTINATION_PREFIX'),
				"URL" => (
				(($arParams["MOBILE"] ?? null) !== 'Y')
						? CCrmOwnerType::GetEntityShowPath(CCrmLiveFeedEntity::ResolveEntityTypeID($matches[1]), $matches[2])
						: (
							isset($arParams["PATH_TO_".$matches[1]])
								? str_replace(
									["#company_id#", "#contact_id#", "#lead_id#", "#deal_id#"],
									$matches[2],
									$arParams["PATH_TO_".$matches[1]]
								)
								: '')
				),
				"TITLE" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmLiveFeedEntity::ResolveEntityTypeID($matches[1]), $matches[2], false))
			);
		}
		elseif (preg_match('/^(' . str_replace('#ID#', '(\d+)', CCrmLiveFeedEntity::DynamicPattern) . '|' . str_replace('#ID#', '(\d+)', CCrmLiveFeedEntity::SuspendedDynamicPattern) . ')(\d+)$/', $right_tmp, $matches))
		{
			$dynamicTypeId = (int)($matches[2] ?: $matches[3]);
			$itemId = $matches[4];

			if (
				($factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($dynamicTypeId))
				&& ($item = $factory->getItems([
					'select' => [ 'TITLE' ],
					'filter' => [ '=ID' => $itemId ],
				])[0])
			)
			{
				$arDestination[] = [
					'TYPE' => 'CRMDYNAMIC',
					'ID' => $matches[4],
					'CRM_PREFIX' => htmlspecialcharsbx($factory->getEntityDescription()),
					'URL' => (
						$arParams['MOBILE'] !== 'Y'
							? \Bitrix\Crm\Service\Container::getInstance()->getRouter()->getItemDetailUrl($dynamicTypeId, $matches[4])
							: ''
					),
					'TITLE' => htmlspecialcharsbx($item['TITLE']),
				];
			}
		}
	}

	static public function OnAfterSocNetLogFormatDestination(&$arDestinationList)
	{
		$corr = array(
			CCrmLiveFeedEntity::Contact => "C",
			CCrmLiveFeedEntity::Company => "CO",
			CCrmLiveFeedEntity::Deal => "D",
			CCrmLiveFeedEntity::Lead => "L",
		);

		foreach($arDestinationList as $key => $arDestination)
		{
			foreach($corr as $crmType => $prefix)
			{
				if ($arDestination["TYPE"] == $crmType)
				{
					foreach($arDestinationList as $key2 => $arDestination2)
					{
						if (
							isset($arDestination2["CRM_ENTITY"])
							&& $arDestination2["CRM_ENTITY"] == $prefix.'_'.$arDestination["ID"]
						)
						{
							$arDestinationList[$key]["CRM_USER_ID"] = $arDestinationList[$key2]["ID"];
							unset($arDestinationList[$key2]);
						}
					}
				}

			}

		}
	}
	static public function OnBeforeSocNetLogEntryGetRights($arEntryParams, &$arRights)
	{
		if (
			(
				!isset($arEntryParams["ENTITY_TYPE"])
				|| !isset($arEntryParams["ENTITY_ID"])
			)
			&& isset($arEntryParams["LOG_ID"])
			&& intval($arEntryParams["LOG_ID"]) > 0
		)
		{
			if ($arLog = CSocNetLog::GetByID($arEntryParams["LOG_ID"]))
			{
				$arEntryParams["ENTITY_TYPE"] = $arLog["ENTITY_TYPE"];
				$arEntryParams["ENTITY_ID"] = $arLog["ENTITY_ID"];
				$arEntryParams["EVENT_ID"] = $arLog["EVENT_ID"];
			}
		}

		if (
			!isset($arEntryParams["ENTITY_TYPE"])
			|| !in_array($arEntryParams["ENTITY_TYPE"], CCrmLiveFeedEntity::GetAll())
			|| !isset($arEntryParams["ENTITY_ID"])
		)
		{
			return true;
		}

		if ($arEntryParams["ENTITY_TYPE"] == CCrmLiveFeedEntity::Activity)
		{
			if (!isset($arEntryParams["ACTIVITY"]))
			{
				$arActivity = CCrmActivity::GetByID($arEntryParams["ENTITY_ID"]);

				if (!$arActivity)
				{
					return true;
				}

				$arEntryParams["ACTIVITY"] = $arActivity;
				$arEntryParams["ACTIVITY"]["COMMUNICATIONS"] = CCrmActivity::GetCommunications($arActivity["ID"]);
			}

			$arRights[] = CCrmLiveFeedEntity::GetByEntityTypeID($arEntryParams["ACTIVITY"]["OWNER_TYPE_ID"]).$arEntryParams["ACTIVITY"]["OWNER_ID"];
			$ownerEntityCode = $arEntryParams["ACTIVITY"]["OWNER_TYPE_ID"]."_".$arEntryParams["ACTIVITY"]["OWNER_ID"];

			if (!empty($arEntryParams["ACTIVITY"]["COMMUNICATIONS"]))
			{
				foreach ($arEntryParams["ACTIVITY"]["COMMUNICATIONS"] as $arActivityCommunication)
				{
					if ($arActivityCommunication["ENTITY_TYPE_ID"]."_".$arActivityCommunication["ENTITY_ID"] == $ownerEntityCode)
					{
						$arRights[] = CCrmLiveFeedEntity::GetByEntityTypeID($arActivityCommunication["ENTITY_TYPE_ID"]).$arActivityCommunication["ENTITY_ID"];
					}
				}
			}

			if (
				$arEntryParams["ACTIVITY"]["TYPE_ID"] == CCrmActivityType::Task
				&& intval($arEntryParams["ACTIVITY"]["ASSOCIATED_ENTITY_ID"]) > 0
				&& CModule::IncludeModule("tasks")
			)
			{
				$dbTask = CTasks::GetByID($arEntryParams["ACTIVITY"]["ASSOCIATED_ENTITY_ID"], false);
				if ($arTaskFields = $dbTask->Fetch())
				{
					$arTaskOwners =  isset($arTaskFields['UF_CRM_TASK']) ? $arTaskFields['UF_CRM_TASK'] : array();
					$arOwnerData = array();

					if(!is_array($arTaskOwners))
					{
						$arTaskOwners  = array($arTaskOwners);
					}

					$arFields['BINDINGS'] = array();

					if (CCrmActivity::TryResolveUserFieldOwners($arTaskOwners, $arOwnerData, CCrmUserType::GetTaskBindingField()))
					{
						foreach ($arOwnerData as $arOwnerInfo)
						{
							$arRights[] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::ResolveID($arOwnerInfo['OWNER_TYPE_NAME'])).$arOwnerInfo['OWNER_ID'];
						}
					}
				}
			}
		}
		elseif ($arEntryParams["ENTITY_TYPE"] == CCrmLiveFeedEntity::Invoice)
		{
			if (!isset($arEntryParams["INVOICE"]))
			{
				$arInvoice = CCrmInvoice::GetByID($arEntryParams["ENTITY_ID"]);
				if (!$arInvoice)
				{
					return true;
				}

				$arEntryParams["INVOICE"] = $arInvoice;
			}

			if ((int)$arEntryParams["INVOICE"]["UF_CONTACT_ID"] > 0)
			{
				$arRights[] = CCrmLiveFeedEntity::Contact.$arEntryParams["INVOICE"]["UF_CONTACT_ID"];
			}

			if ((int)$arEntryParams["INVOICE"]["UF_COMPANY_ID"] > 0)
			{
				$arRights[] = CCrmLiveFeedEntity::Company.$arEntryParams["INVOICE"]["UF_COMPANY_ID"];
			}

			if ((int)$arEntryParams["INVOICE"]["UF_DEAL_ID"] > 0)
			{
				$arRights[] = CCrmLiveFeedEntity::Deal.$arEntryParams["INVOICE"]["UF_DEAL_ID"];
			}
		}
		else
		{
			$arRights[] = $arEntryParams["ENTITY_TYPE"].$arEntryParams["ENTITY_ID"];
			if (in_array($arEntryParams["EVENT_ID"], array("crm_lead_message", "crm_deal_message", "crm_contact_message", "crm_company_message")))
			{
				$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arEntryParams["LOG_ID"]));
				while ($arRight = $dbRight->Fetch())
				{
					$arRights[] = $arRight["GROUP_CODE"];
				}
			}
		}

		return false;
	}
	static public function TryParseGroupCode($groupCode, &$data)
	{
		if(preg_match('/^([A-Z]+)([0-9]+)$/i', $groupCode, $m) !== 1)
		{
			return false;
		}

		$data['ENTITY_TYPE'] = isset($m[1]) ? $m[1] : '';
		$data['ENTITY_ID'] = isset($m[2]) ? intval($m[2]) : 0;
		return true;
	}
	static public function OnBeforeSocNetLogRightsAdd($logID, $groupCode)
	{
		if (!CModule::IncludeModule('socialnetwork'))
		{
			return;
		}

		if (!Settings\Crm::isLiveFeedRecordsGenerationEnabled())
		{
			return;
		}

		$logID = intval($logID);
		$groupCode = strval($groupCode);
		if($logID <= 0 || $groupCode === '')
		{
			return;
		}

		$dbResult = CSocNetLog::GetList(
			array(),
			array('ID' => $logID),
			false,
			false,
			array('ID', 'ENTITY_TYPE', 'ENTITY_ID', 'EVENT_ID')
		);

		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			return;
		}

		$logEntityType = isset($fields['ENTITY_TYPE']) ? $fields['ENTITY_TYPE'] : '';
		$logEntityID = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;
		$logEventID = isset($fields['EVENT_ID']) ? $fields['EVENT_ID'] : '';

		if(!CCrmLiveFeedEntity::IsDefined($logEntityType))
		{
			return;
		}

		$relations = array();

		$groupCodeData = array();
		if(!self::TryParseGroupCode($groupCode, $groupCodeData))
		{
			return;
		}

		$entityType = $groupCodeData['ENTITY_TYPE'];
		$entityID = $groupCodeData['ENTITY_ID'];
		if(!CCrmLiveFeedEntity::IsDefined($entityType)
			|| $entityID <= 0
			|| ($entityType === $logEntityType
			&& $entityID === $logEntityID))
		{
			return;
		}

		$relations[] = array(
			'ENTITY_TYPE_ID' => CCrmLiveFeedEntity::ResolveEntityTypeID($entityType),
			'ENTITY_ID' => $entityID
		);

		CCrmSonetRelation::RegisterRelation(
			$logID,
			$logEventID,
			CCrmLiveFeedEntity::ResolveEntityTypeID($entityType),
			$logEntityID,
			CCrmLiveFeedEntity::ResolveEntityTypeID($entityType),
			$entityID,
			CCrmSonetRelationType::Correspondence
		);
	}
	static public function OnBeforeSocNetLogCommentCounterIncrement($arLogFields)
	{
		if (
			is_array($arLogFields)
			&& array_key_exists("ID", $arLogFields)
			&& array_key_exists("EVENT_ID", $arLogFields)
			&&
			(
				mb_strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::LeadPrefix, 0) === 0
				|| mb_strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::ContactPrefix, 0) === 0
				|| mb_strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::CompanyPrefix, 0) === 0
				|| mb_strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::DealPrefix, 0) === 0
				|| mb_strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::ActivityPrefix, 0) === 0
				|| mb_strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::InvoicePrefix, 0) === 0
			)
		)
		{
			CCrmLiveFeed::CounterIncrement($arLogFields);
			return (Option::get('crm', 'enable_livefeed_merge', 'N') === 'Y');
		}

		return true;
	}
	static public function OnAfterSocNetLogEntryCommentAdd($arLogFields, $arParams = array())
	{
		if (
			is_array($arLogFields)
			&& array_key_exists("ID", $arLogFields)
			&& array_key_exists("EVENT_ID", $arLogFields)
			&& array_key_exists("USER_ID", $arLogFields)
			&& CCrmSecurityHelper::GetCurrentUserID()
			&& (
				mb_strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::LeadPrefix, 0) === 0
				|| mb_strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::ContactPrefix, 0) === 0
				|| mb_strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::CompanyPrefix, 0) === 0
				|| mb_strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::DealPrefix, 0) === 0
				|| mb_strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::ActivityPrefix, 0) === 0
				|| mb_strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::InvoicePrefix, 0) === 0
			)
		)
		{
			if (
				CCrmSecurityHelper::GetCurrentUserID() != $arLogFields["USER_ID"]
			&& CModule::IncludeModule("im")
		)
		{
			$genderSuffix = "";
			$dbUser = CUser::GetByID(CCrmSecurityHelper::GetCurrentUserID());
			if(
				($arUser = $dbUser->fetch())
				&& !empty($arUser["PERSONAL_GENDER"])
			)
			{
				$genderSuffix = "_".$arUser["PERSONAL_GENDER"];
			}

			$title = self::GetNotifyEntryTitle($arLogFields, "COMMENT");
			if ($title <> '')
			{
				if (
					!isset($arParams["PATH_TO_LOG_ENTRY"])
					|| $arParams["PATH_TO_LOG_ENTRY"] == ''
				)
				{
					$arParams["PATH_TO_LOG_ENTRY"] = '/crm/stream/?log_id=#log_id#';
				}

				$url = str_replace(array("#log_id#"), array($arLogFields["ID"]), $arParams["PATH_TO_LOG_ENTRY"]);
				$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && SITE_SERVER_NAME <> '') ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

				$arMessageFields = array(
					"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
					"TO_USER_ID" => $arLogFields["USER_ID"],
					"FROM_USER_ID" => CCrmSecurityHelper::GetCurrentUserID(),
					"NOTIFY_TYPE" => IM_NOTIFY_FROM,
					"NOTIFY_MODULE" => "crm",
					"LOG_ID" => $arLogFields["ID"],
					//"NOTIFY_EVENT" => "comment",
					"NOTIFY_EVENT" => "mention",
					"NOTIFY_TAG" => "CRM|LOG_COMMENT|".$arLogFields["ID"],
					"NOTIFY_MESSAGE" => GetMessage("CRM_LF_COMMENT_IM_NOTIFY".$genderSuffix, Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($title)."</a>")),
					"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_LF_COMMENT_IM_NOTIFY".$genderSuffix, Array("#title#" => htmlspecialcharsbx($title)))." (".$serverName.$url.")"
				);
				CIMNotify::Add($arMessageFields);
			}
		}

			if (
				is_array($arParams)
				&& isset($arParams["COMMENT_ID"])
				&& intval($arParams["COMMENT_ID"])
				&& IsModuleInstalled('mail')
				&& CModule::IncludeModule('socialnetwork')
			)
			{
				$arUserIdToMail = array();

				$res = CSocNetLogRights::GetList(
					array(),
					array(
						"LOG_ID" => $arLogFields["ID"]
					)
				);

				while($arLogRight = $res->fetch())
				{
					if (preg_match('/^U(\d+)$/i', $arLogRight["GROUP_CODE"], $matches))
					{
						$arUserIdToMail[] = $matches[1];
					}
				}

				if (!empty($arUserIdToMail))
				{
					\Bitrix\Socialnetwork\Util::notifyMail(array(
						"type" => "LOG_COMMENT",
						"siteId" => (is_array($arParams) && isset($arParams["SITE_ID"]) ? $arParams["SITE_ID"] : SITE_ID),
						"userId" => $arUserIdToMail,
						"authorId" => CCrmSecurityHelper::GetCurrentUserID(),
						"logEntryId" => $arLogFields["ID"],
						"logCommentId" => intval($arParams["COMMENT_ID"]),
						"logEntryUrl" => CComponentEngine::MakePathFromTemplate(
							'/pub/log_entry.php?log_id=#log_id#',
							array(
								"log_id"=> $arLogFields["ID"]
							)
						)
					));
				}

				if (
					($arComment = CSocNetLogComments::GetByID(intval($arParams["COMMENT_ID"])))
					&& ($arLog = CSocNetLog::GetByID($arComment["LOG_ID"]))
				)
				{
					\Bitrix\Crm\Activity\Provider\Livefeed::addComment($arComment, $arLog);
				}
			}
		}
	}
	static public function GetNotifyEntryTitle($arLogFields, $type = "COMMENT")
	{
		switch ($arLogFields["EVENT_ID"])
		{
			case "crm_lead_add":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_LEAD_ADD", array("#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $arLogFields["ENTITY_ID"], false)));
			case "crm_lead_message":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_LEAD_MESSAGE", array(
					"#message_title#" => CCrmLiveFeedComponent::ParseText($arLogFields["MESSAGE"], array(), array("MAX_LENGTH" => 50, "NOTIFY" => "Y")),
					"#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $arLogFields["ENTITY_ID"], false)
				));
			case "crm_lead_progress":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_LEAD_PROGRESS", array(
					"#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $arLogFields["ENTITY_ID"], false)
				));
			case "crm_company_add":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_COMPANY_ADD", array("#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $arLogFields["ENTITY_ID"], false)));
			case "crm_company_message":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_COMPANY_MESSAGE", array(
					"#message_title#" => CCrmLiveFeedComponent::ParseText($arLogFields["MESSAGE"], array(), array("MAX_LENGTH" => 50, "NOTIFY" => "Y")),
					"#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $arLogFields["ENTITY_ID"], false)
				));
			case "crm_contact_add":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_CONTACT_ADD", array("#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Contact, $arLogFields["ENTITY_ID"], false)));
			case "crm_contact_message":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_CONTACT_MESSAGE", array(
					"#message_title#" => CCrmLiveFeedComponent::ParseText($arLogFields["MESSAGE"], array(), array("MAX_LENGTH" => 50, "NOTIFY" => "Y")),
					"#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Contact, $arLogFields["ENTITY_ID"], false)
				));
			case "crm_deal_add":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_DEAL_ADD", array("#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $arLogFields["ENTITY_ID"], false)));
			case "crm_deal_message":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_DEAL_MESSAGE", array(
					"#message_title#" => CCrmLiveFeedComponent::ParseText($arLogFields["MESSAGE"], array(), array("MAX_LENGTH" => 50, "NOTIFY" => "Y")),
					"#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $arLogFields["ENTITY_ID"], false)
				));
			case "crm_deal_progress":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_DEAL_PROGRESS", array("#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $arLogFields["ENTITY_ID"], false)));
			case "crm_invoice_add":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_INVOICE_ADD", array("#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Invoice, $arLogFields["ENTITY_ID"], false)));
			case "crm_activity_add":
				if ($arActivity = CCrmActivity::GetByID($arLogFields["ENTITY_ID"]))
				{
					switch ($arActivity["TYPE_ID"])
					{
						case CCrmActivityType::Meeting:
							return GetMessage("CRM_LF_IM_".$type."_TITLE_ACTIVITY_MEETING_ADD", array("#title#" => $arActivity["SUBJECT"]));
						case CCrmActivityType::Call:
							return GetMessage("CRM_LF_IM_".$type."_TITLE_ACTIVITY_CALL_ADD", array("#title#" => $arActivity["SUBJECT"]));
						case CCrmActivityType::Email:
							return GetMessage("CRM_LF_IM_".$type."_TITLE_ACTIVITY_EMAIL_ADD", array("#title#" => $arActivity["SUBJECT"]));
						case CCrmActivityType::Task:
							return GetMessage("CRM_LF_IM_".$type."_TITLE_ACTIVITY_TASK_ADD", array("#title#" => $arActivity["SUBJECT"]));
					}
				}
		}
		return "";
	}

	public static function OnAddRatingVote($ratingVoteId, $ratingFields)
	{
		if (
			!Loader::includeModule('socialnetwork')
			|| !Loader::includeModule('im')
		)
		{
			return;
		}

		$livefeedData = \CSocNetLogTools::getDataFromRatingEntity($ratingFields['ENTITY_TYPE_ID'], $ratingFields['ENTITY_ID'], false);
		if (
			!is_array($livefeedData)
			|| !isset($livefeedData['LOG_ID'])
			|| (int)$livefeedData['LOG_ID'] <= 0
			|| isset($livefeedData['LOG_COMMENT_ID'])
			|| (int)$livefeedData['LOG_COMMENT_ID'] > 0
			|| $ratingFields['ENTITY_TYPE_ID'] === 'LOG_COMMENT'
		)
		{
			return;
		}

		$logFields = \CSocNetLog::getById($livefeedData['LOG_ID']);

		if (
			(int)$logFields['USER_ID'] === (int)$ratingFields['USER_ID']
			|| !isset($logFields['ENTITY_TYPE'])
			|| !in_array((string)$logFields['ENTITY_TYPE'], \CCrmLiveFeedEntity::getAll(), true)
		)
		{
			return;
		}

		$title = (string)self::getNotifyEntryTitle($logFields, 'LIKE');


		if ($title === '')
		{
			return;
		}

		if (
			!isset($ratingFields['PATH_TO_LOG_ENTRY'])
			|| (string)$ratingFields['PATH_TO_LOG_ENTRY'] === ''
		)
		{
			$ratingFields['PATH_TO_LOG_ENTRY'] = '/crm/stream/?log_id=#log_id#';
		}

		$url = str_replace('#log_id#', $logFields['ID'], $ratingFields['PATH_TO_LOG_ENTRY']);
		$serverName = (\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps() ? 'https' : 'http') . '://'
			. ((defined('SITE_SERVER_NAME') && SITE_SERVER_NAME <> '') ? SITE_SERVER_NAME : Option::get('main', 'server_name', ''));

		\CIMNotify::add([
			'MESSAGE_TYPE' => IM_MESSAGE_SYSTEM,
			'TO_USER_ID' => (int)$logFields['USER_ID'],
			'FROM_USER_ID' => (int)$ratingFields['USER_ID'],
			'NOTIFY_TYPE' => IM_NOTIFY_FROM,
			'NOTIFY_MODULE' => 'main',
			'NOTIFY_EVENT' => 'rating_vote',
			'NOTIFY_TAG' => 'RATING|'. ($ratingFields['VALUE'] >= 0 ? '' : 'DL|') . $ratingFields['ENTITY_TYPE_ID'] . '|' . $ratingFields['ENTITY_ID'],
			'NOTIFY_MESSAGE' => Loc::getMessage('CRM_LF_LIKE_IM_NOTIFY', [
				'#title#' => '<a href="' . $url . '" class="bx-notifier-item-action">' . htmlspecialcharsbx($title) . '</a>'
			]),
			'NOTIFY_MESSAGE_OUT' => Loc::getMessage('CRM_LF_LIKE_IM_NOTIFY', [
				'#title#' => htmlspecialcharsbx($title)
			]) . ' (' . $serverName . $url . ')'
		]);
	}

	static public function OnGetRatingContentOwner($params)
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		$livefeedCommentProvider = new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmEntityComment();
		if ($params['ENTITY_TYPE_ID'] === $livefeedCommentProvider->getContentTypeId())
		{
			$res = \Bitrix\Socialnetwork\LogCommentTable::getList([
				'filter' => [
					'ID' => $params['ENTITY_ID'],
				],
				'select' => [ 'USER_ID' ],
			]);
			if (
				($logCommentFields = $res->fetch())
				&& (int)$logCommentFields['USER_ID'] > 0
			)
			{
				return (int)$logCommentFields['USER_ID'];
			}
		}

		return false;
	}

	static public function OnGetMessageRatingVote(&$params, &$forEmail)
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return;
		}

		$livefeedCommentProvider = new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmEntityComment();
		if (!$forEmail && $params['ENTITY_TYPE_ID'] === $livefeedCommentProvider->getContentTypeId())
		{
			$type = ($params['VALUE'] >= 0 ? 'REACT' : 'DISLIKE');

			$genderSuffix = '';

			if (
				$type === 'REACT'
				&& !empty($params['USER_ID'])
				&& (int)$params['USER_ID'] > 0
			)
			{
				$res = \Bitrix\Main\UserTable::getList([
					'filter' => [
						'ID' => (int)$params['USER_ID']
					],
					'select' => [ 'PERSONAL_GENDER' ]
				]);
				if ($userFields = $res->fetch())
				{
					switch ($userFields['PERSONAL_GENDER'])
					{
						case 'M':
						case 'F':
							$genderSuffix = '_' . $userFields['PERSONAL_GENDER'];
							break;
						default:
							$genderSuffix = '';
					}
				}
			}

			$message = Loc::getMessage('CRM_LF_NOTIFICATIONS_' . $type . '_COMMENT' . $genderSuffix, [
				'#LINK#' => (
					(string) $params['ENTITY_LINK'] !== ''
						? '<a href="' . $params['ENTITY_LINK'] . '" class="bx-notifier-item-action">' . $params['ENTITY_TITLE'] . '</a>'
						: $params['ENTITY_TITLE']
				),
				'#REACTION#' => \CRatingsComponentsMain::getRatingLikeMessage(!empty($params['REACTION']) ? $params['REACTION'] : '')
			]);

			if ((string)$message !== '')
			{
				$params['MESSAGE'] = $message;
			}
		}
	}

	static public function OnAfterSocNetLogCommentAdd($commentId, array $fields = [])
	{
		$livefeedCommentProvider = new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmEntityComment();
		if (
			(int)$commentId > 0
			&& isset($fields['EVENT_ID'])
			&& in_array($fields['EVENT_ID'], $livefeedCommentProvider->getEventId())
			&& Loader::includeModule('socialnetwork')
		)
		{
			$process = true;

			if ((int)$fields['LOG_ID'] > 0)
			{
				$res = \Bitrix\Socialnetwork\LogTable::getList([
					'filter' => [
						'ID' => (int)$fields['LOG_ID']
					],
					'select' => [ 'ENTITY_TYPE', 'ENTITY_ID' ],
				]);

				if (
					($logFields = $res->fetch())
					&& ($logFields['ENTITY_TYPE'] === 'CRMACTIVITY')
					&& ((int)$logFields['ENTITY_ID'] > 0)
				)
				{
					$res = \CCrmActivity::getList(
						[],
						[
							'ID' => (int)$logFields['ENTITY_ID'],
							'CHECK_PERMISSIONS' => 'N'
						],
						false,
						false,
						['ID', 'TYPE_ID', 'PROVIDER_ID']
					);
					if ($activityFields = $res->fetch())
					{
						if (
							$activityFields['TYPE_ID'] === \CCrmActivityType::Task
							|| (
								$activityFields['TYPE_ID'] === \CCrmActivityType::Provider
								&& $activityFields['PROVIDER_ID'] === Task::getId()
							)
						)
						{
							$process = false;
						}
					}
				}
			}

			if ($process)
			{
				\Bitrix\Socialnetwork\LogCommentTable::update((int)$commentId, [
					'RATING_TYPE_ID' => $livefeedCommentProvider->getContentTypeId()
				]);
			}
		}
	}

	static public function OnSocNetLogRightsDelete($logID)
	{
		CCrmSonetRelation::UnRegisterRelationsByLogEntityID($logID, CCrmSonetRelationType::Correspondence);
	}
	static public function OnBeforeSocNetLogDelete($logID)
	{
		CCrmSonetRelation::UnRegisterRelationsByLogEntityID($logID);
	}
	static public function CreateLogMessage(&$fields, $options = array())
	{
		if (!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}

		global $APPLICATION, $DB;
		if(!is_array($options))
		{
			$options = array();
		}

		$entityTypeID = isset($fields['ENTITY_TYPE_ID']) ? intval($fields['ENTITY_TYPE_ID']) : CCrmOwnerType::Undefined;
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			$fields['ERROR'] = GetMessage('CRM_LF_MSG_ENTITY_TYPE_NOT_FOUND');
			return false;
		}

		$entityType = CCrmOwnerType::ResolveName($entityTypeID);
		$entityID = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;
		if($entityID < 0)
		{
			$fields['ERROR'] = GetMessage('CRM_LF_MSG_ENTITY_TYPE_NOT_FOUND');
			return false;
		}

		$message = isset($fields['MESSAGE']) && is_string($fields['MESSAGE']) ? $fields['MESSAGE'] : '';
		if($message === '')
		{
			$fields['ERROR'] = GetMessage('CRM_LF_MSG_EMPTY');
			return false;
		}

		$title = isset($fields['TITLE']) && is_string($fields['TITLE']) ? $fields['TITLE'] : '';
		if($title === '')
		{
			$title = self::UntitledMessageStub;
		}

		$userID = isset($fields['USER_ID']) ? intval($fields['USER_ID']) : 0;
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$bbCodeParser = new CTextParser();
		$bbCodeParser->allow["HTML"] = "Y";
		$eventText = $bbCodeParser->convert4mail($message);

		$CCrmEvent = new CCrmEvent();
		$eventID = $CCrmEvent->Add(
			array(
				'ENTITY_TYPE'=> $entityType,
				'ENTITY_ID' => $entityID,
				'EVENT_ID' => 'INFO',
				'EVENT_TYPE' => 0, //USER
				'EVENT_TEXT_1' => $eventText,
				'DATE_CREATE' => ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', SITE_ID),
				'FILES' => array()
			)
		);

		if(is_string($eventID))
		{
			//MS SQL RETURNS STRING INSTEAD INT
			$eventID = intval($eventID);
		}

		if(!(is_int($eventID) && $eventID > 0))
		{
			$fields['ERROR'] = 'Could not create event';
			return false;
		}

		$liveFeedEntityType = CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID);
		$eventID = CCrmLiveFeedEvent::GetEventID($liveFeedEntityType, CCrmLiveFeedEvent::Message);
		$eventFields = array(
			'EVENT_ID' => $eventID,
			'=LOG_DATE' => CDatabase::CurrentTimeFunction(),
			'TITLE' => $title,
			'MESSAGE' => $message,
			'TEXT_MESSAGE' => '',
			'MODULE_ID' => 'crm_shared',
			'CALLBACK_FUNC' => false,
			'ENABLE_COMMENTS' => 'Y',
			'PARAMS' => '',
			'USER_ID' => $userID,
			'ENTITY_TYPE' => $liveFeedEntityType,
			'ENTITY_ID' => $entityID,
			'SOURCE_ID' => $eventID,
			'URL' => CCrmUrlUtil::AddUrlParams(
				CCrmOwnerType::GetEntityShowPath($entityTypeID, $entityID),
				array()
			),
		);

		if (!empty($fields['UF']))
		{
			foreach($fields['UF'] as $fieldName => $arUserField)
			{
				$eventFields[$fieldName] = $arUserField;
			}
		}

		if(isset($fields['WEB_DAV_FILES']) && is_array($fields['WEB_DAV_FILES']))
		{
			$eventFields = array_merge($eventFields, $fields['WEB_DAV_FILES']);
		}

		$sendMessage = isset($options['SEND_MESSAGE']) && is_bool($options['SEND_MESSAGE']) ? $options['SEND_MESSAGE'] : false;

		$logEventID = CSocNetLog::Add($eventFields, $sendMessage);
		if(is_int($logEventID) && $logEventID > 0)
		{
			$arSocnetRights = array_merge($fields["RIGHTS"], array('U'.$userID));
			if (!empty($arSocnetRights))
			{
				$socnetPermsAdd = array();

				foreach($arSocnetRights as $perm_tmp)
				{
					if (preg_match('/^SG(\d+)$/', $perm_tmp, $matches))
					{
						if (!in_array("SG".$matches[1]."_".SONET_ROLES_USER, $arSocnetRights))
						{
							$socnetPermsAdd[] = "SG".$matches[1]."_".SONET_ROLES_USER;
						}

						if (!in_array("SG".$matches[1]."_".SONET_ROLES_MODERATOR, $arSocnetRights))
						{
							$socnetPermsAdd[] = "SG".$matches[1]."_".SONET_ROLES_MODERATOR;
						}

						if (!in_array("SG".$matches[1]."_".SONET_ROLES_OWNER, $arSocnetRights))
						{
							$socnetPermsAdd[] = "SG".$matches[1]."_".SONET_ROLES_OWNER;
						}
					}
				}
				if (count($socnetPermsAdd) > 0)
				{
					$arSocnetRights = array_merge($arSocnetRights, $socnetPermsAdd);
				}

				CSocNetLogRights::DeleteByLogID($logEventID);
				CSocNetLogRights::Add($logEventID, $arSocnetRights);

				\Bitrix\Main\FinderDestTable::merge(array(
					"CONTEXT" => "crm_post",
					"CODE" => \Bitrix\Main\FinderDestTable::convertRights($arSocnetRights, array("U".$userID))
				));

				if (
					array_key_exists("UF_SONET_LOG_DOC", $eventFields)
					&& is_array($eventFields["UF_SONET_LOG_DOC"])
					&& count($eventFields["UF_SONET_LOG_DOC"]) > 0
				)
				{
					if(!in_array("U".$userID, $arSocnetRights))
					{
						$arSocnetRights[] = "U".$userID;
					}
					CSocNetLogTools::SetUFRights($eventFields["UF_SONET_LOG_DOC"], $arSocnetRights);
				}
			}

			$arUpdateFields = array(
				"RATING_TYPE_ID" => "LOG_ENTRY",
				"RATING_ENTITY_ID" => $logEventID
			);
			CSocNetLog::Update($logEventID, $arUpdateFields);
			self::RegisterOwnershipRelations($logEventID, $eventID, $fields);
			\Bitrix\Crm\Timeline\LiveFeedController::getInstance()->onMessageCreate(
				$logEventID,
				array('FIELDS' => $fields)
			);

			$eventFields["LOG_ID"] = $logEventID;
			CCrmLiveFeed::CounterIncrement($eventFields);

			return $logEventID;
		}

		$ex = $APPLICATION->GetException();
		$fields['ERROR'] = $ex->GetString();
		return false;
	}

	static public function CreateLogEvent(&$fields, $eventType, $options = array())
	{
		if (!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}

		global $APPLICATION, $DB;
		if(!is_array($options))
		{
			$options = array();
		}

		$entityTypeID = isset($fields['ENTITY_TYPE_ID']) ? intval($fields['ENTITY_TYPE_ID']) : CCrmOwnerType::Undefined;
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			$fields['ERROR'] = 'Entity type is not found';
			return false;
		}

		//$entityType = CCrmOwnerType::ResolveName($entityTypeID);
		$entityID = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;
		if($entityID < 0)
		{
			$fields['ERROR'] = 'Entity ID is not found';
			return false;
		}

		if (!Settings\Crm::isLiveFeedRecordsGenerationEnabled())
		{
			return 0; // return so to distinguish with the boolean value ("false")
		}

		$message = isset($fields['MESSAGE']) && is_string($fields['MESSAGE']) ? $fields['MESSAGE'] : '';
		$title = isset($fields['TITLE']) && is_string($fields['TITLE']) ? $fields['TITLE'] : '';
		if($title === '')
		{
			$title = self::UntitledMessageStub;
		}

		$userID = isset($fields['USER_ID']) ? intval($fields['USER_ID']) : 0;
		if($userID <= 0)
		{
			if (isset($options['CURRENT_USER']))
			{
				$userID = (int)$options['CURRENT_USER'];
			}
			else
			{
				$userID = CCrmSecurityHelper::GetCurrentUserID();
			}
		}

		$sourceID = isset($fields['SOURCE_ID']) ? intval($fields['SOURCE_ID']) : 0;
		$url = isset($fields['URL']) ? $fields['URL'] : '';
		$params = isset($fields['PARAMS']) ? $fields['PARAMS'] : null;
		$liveFeedEntityType = CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID);
		$eventID = CCrmLiveFeedEvent::GetEventID($liveFeedEntityType, $eventType);

		$eventFields = array(
			'EVENT_ID' => $eventID,
			'=LOG_DATE' => CDatabase::CurrentTimeFunction(),
			'TITLE' => $title,
			'MESSAGE' => $message,
			'TEXT_MESSAGE' => '',
			'MODULE_ID' => ($eventID === 'crm_activity_add' && isset($options['ACTIVITY_PROVIDER_ID']) && ($options['ACTIVITY_PROVIDER_ID'] === \Bitrix\Crm\Activity\Provider\Task::getId() || $options['ACTIVITY_PROVIDER_ID'] === Task::getId()) ? 'crm_shared' : 'crm'),
			'CALLBACK_FUNC' => false,
			'ENABLE_COMMENTS' => 'Y',
			'PARAMS' => is_array($params) && !empty($params) ? serialize($params) : '',
			'ENTITY_TYPE' => $liveFeedEntityType,
			'ENTITY_ID' => $entityID,
			'SOURCE_ID' => $sourceID,
			'URL' => $url,
			'UF_SONET_LOG_DOC' => (!empty($fields["UF_SONET_LOG_DOC"]) ? $fields["UF_SONET_LOG_DOC"] : false),
			'UF_SONET_LOG_FILE' => (!empty($fields["UF_SONET_LOG_DOC"]) ? $fields["UF_SONET_LOG_FILE"] : false),
			'SITE_ID' =>  (!empty($fields["SITE_ID"]) ? $fields["SITE_ID"] : SITE_ID),
		);

		if($userID > 0)
		{
			$eventFields['USER_ID'] = $userID;
		}

		if (
			isset($fields['CONTEXT_USER_ID'])
			&& intval($fields['CONTEXT_USER_ID']) > 0
		)
		{
			$eventFields['CONTEXT_USER_ID'] = intval($fields['CONTEXT_USER_ID']);
		}
		$sendMessage = isset($options['SEND_MESSAGE']) && is_bool($options['SEND_MESSAGE']) ? $options['SEND_MESSAGE'] : false;

		$logEventID = CSocNetLog::Add($eventFields, $sendMessage);
		if(is_int($logEventID) && $logEventID > 0)
		{
			$arUpdateFields = array(
				'RATING_TYPE_ID' => 'LOG_ENTRY',
				'RATING_ENTITY_ID' => $logEventID
			);

			CSocNetLog::Update($logEventID, $arUpdateFields);
			self::RegisterOwnershipRelations($logEventID, $eventID, $fields);

			if (
				!empty($fields['LOG_RIGHTS'])
				&& is_array($fields['LOG_RIGHTS'])
			)
			{
				\CSocNetLogRights::Add($logEventID, $fields['LOG_RIGHTS']);
				if (Option::get('crm', 'enable_livefeed_merge', 'N') === 'Y')
				{
					\CSocNetLog::SendEvent($logEventID);
				}
			}

			$eventFields["LOG_ID"] = $logEventID;
			CCrmLiveFeed::CounterIncrement($eventFields);

			return $logEventID;
		}

		$ex = $APPLICATION->GetException();
		$fields['ERROR'] = $ex->GetString();
		return false;
	}
	static public function ConvertTasksLogEvent($arFields)
	{
		global $DB;

		if (
			!isset($arFields["LOG_ID"])
			|| intval($arFields["LOG_ID"]) <= 0
			|| !isset($arFields["ACTIVITY_ID"])
			|| intval($arFields["ACTIVITY_ID"]) <= 0
			|| !CModule::IncludeModule('socialnetwork')
		)
		{
			return false;
		}

		$logId = CSocNetLog::Update(
			(int)$arFields["LOG_ID"],
			array(
				"ENTITY_TYPE" => CCrmLiveFeedEntity::Activity,
				"ENTITY_ID" => (int)$arFields["ACTIVITY_ID"],
				"EVENT_ID" => "crm_activity_add",
				"TITLE_TEMPLATE" => "",
				"TITLE" => "__EMPTY__",
				"TEXT_MESSAGE" => "",
				"URL" => "",
				"MODULE_ID" => "crm_shared",
				"PARAMS" => "",
				"TMP_ID" => 0,
				"SOURCE_ID" => 0,
				"=LOG_UPDATE" => CDatabase::CurrentTimeFunction(),
				"RATING_TYPE_ID" => "LOG_ENTRY",
				"RATING_ENTITY_ID" => (int)$arFields["LOG_ID"]
			)
		);

		if ((int)$logId > 0)
		{
			$fields = array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ENTITY_ID' => (int)$arFields["ACTIVITY_ID"],
				'USER_ID' => 1,
				'MESSAGE' => '',
				'TITLE' => '',
				'PARENTS' => (!empty($arFields['PARENTS']) ? $arFields['PARENTS'] : array()),
				'PARENT_OPTIONS' => array (
					'ENTITY_TYPE_ID_KEY' => 'OWNER_TYPE_ID',
					'ENTITY_ID_KEY' => 'OWNER_ID'
				),
			);

			self::RegisterOwnershipRelations(
				$logId,
				"crm_activity_add",
				$fields
			);
		}

		return $logId;
	}

	static public function revertTasksLogEvent($params = array())
	{
		global $DB;

		$task = ($params['TASK'] ?? false);
		$activity = ($params['ACTIVITY'] ?? false);

		if (
			!is_array($task)
			|| empty($task)
			|| !is_array($activity)
			|| !is_set($activity['ID'])
			|| (int)$activity['ID'] <= 0
			|| !Main\Loader::includeModule('socialnetwork')
		)
		{
			return false;
		}

		$res = CSocNetLog::getList(
			array(),
			array(
				'ENTITY_TYPE' => CCrmLiveFeedEntity::Activity,
				'ENTITY_ID' => $activity['ID']
			),
			false,
			false,
			array('ID')
		);

		if (!($log = $res->fetch()))
		{
			return false;
		}

		$logFields = array(
			'TITLE' => $task["TITLE"],
			'MESSAGE' => '',
			'TEXT_MESSAGE' => '',
			'MODULE_ID' => 'tasks',
			'EVENT_ID' => 'tasks',
			'PARAMS' => serialize(array(
				'TYPE' => 'create',
				'CREATED_BY' => $task["CREATED_BY"],
				'PREV_REAL_STATUS' => $task['REAL_STATUS']
			)),
			'=LOG_DATE' => $DB->charToDateFunction($task["CREATED_DATE"], "FULL", SITE_ID),
			'SOURCE_ID' => $task["ID"],
			'RATING_TYPE_ID' => 'TASK',
			'RATING_ENTITY_ID' => $task["ID"]
		);

		if (intval($task['GROUP_ID']) > 0)
		{
			$logFields["ENTITY_TYPE"] = SONET_ENTITY_GROUP;
			$logFields["ENTITY_ID"] = intval($task['GROUP_ID']);
		}
		else
		{
			$logFields["ENTITY_TYPE"] = SONET_ENTITY_USER;
			$logFields["ENTITY_ID"] = intval($task["CREATED_BY"]);
		}

		$logId = CSocNetLog::update(
			$log['ID'],
			$logFields
		);

		if ($logId > 0)
		{
			// don't forget about rights
			$taskParticipantList = CTaskNotifications::getRecipientsIDs(
				$task,
				false
			);

			$rights = CTaskNotifications::__UserIDs2Rights($taskParticipantList);

			if (intval($task['GROUP_ID']) > 0)
			{
				$rights = array_merge(
					$rights,
					array('SG'.intval($task['GROUP_ID']))
				);
			}

			CSocNetLogRights::deleteByLogID($logId);
			CSocNetLogRights::add($logId, $rights);

			$res = CSocNetLogComments::getList(
				array(),
				array('LOG_ID' => $logId),
				false,
				false,
				array('ID')
			);
			while($comment = $res->fetch())
			{
				$commentFields = array(
					'ENTITY_TYPE' => $logFields["ENTITY_TYPE"],
					'ENTITY_ID' => $logFields["ENTITY_ID"],
					'EVENT_ID' => 'tasks_comment',
					'MODULE_ID' => ''
				);

				CSocNetLogComments::update($comment["ID"], $commentFields);
			}
		}

		return (intval($logId) > 0);
	}

	static public function GetLogEvents($sort, $filter, $select)
	{
		if(!(is_array($filter) && !empty($filter)))
		{
			return array();
		}

		if (!CModule::IncludeModule('socialnetwork'))
		{
			return array();
		}

		if(isset($filter['ENTITY_TYPE_ID']))
		{
			$filter['ENTITY_TYPE'] = CCrmLiveFeedEntity::GetByEntityTypeID($filter['ENTITY_TYPE_ID']);
			unset($filter['ENTITY_TYPE_ID']);
		}

		$dbResult = CSocNetLog::GetList(
			is_array($sort) ? $sort : array(),
			$filter,
			false,
			false,
			is_array($select) ? $select : array()
		);

		$result = array();
		if($dbResult)
		{
			while($ary = $dbResult->Fetch())
			{
				$result[] = &$ary;
				unset($ary);
			}
		}
		return $result;
	}
	static public function UpdateLogEvent($ID, $fields)
	{
		if (!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}

		if(isset($fields['ENTITY_TYPE_ID']))
		{
			$fields['ENTITY_TYPE'] = CCrmLiveFeedEntity::GetByEntityTypeID($fields['ENTITY_TYPE_ID']);
			unset($fields['ENTITY_TYPE_ID']);
		}

		$refreshDate = isset($fields['LOG_UPDATE']) || isset($fields['=LOG_UPDATE']);
		$result = CSocNetLog::Update($ID, $fields);
		if($result !== false)
		{
			CCrmSonetRelation::SynchronizeRelationLastUpdateTime($ID);

			if (
				!empty($fields['LOG_RIGHTS'])
				&& is_array($fields['LOG_RIGHTS'])
			)
			{
				CSocNetLogRights::DeleteByLogID($ID);
				CSocNetLogRights::Add($ID, $fields['LOG_RIGHTS']);
			}

		}
		return $result;
	}
	static public function DeleteLogEvent($ID, $options = array())
	{
		if (!CModule::IncludeModule('socialnetwork'))
		{
			return;
		}

		$ID = intval($ID);
		if($ID <= 0)
		{
			return;
		}

		CSocNetLog::Delete($ID);

		if(!is_array($options))
		{
			$options = array();
		}
		$unregisterRelation = !(isset($options['UNREGISTER_RELATION']) && $options['UNREGISTER_RELATION'] === false);
		if($unregisterRelation)
		{
			CCrmSonetRelation::UnRegisterRelationsByLogEntityID($ID);
		}
	}

	static public function DeleteLogEvents($params, $options = array())
	{
		if(!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}

		$entityTypeID = (int)(isset($params['ENTITY_TYPE_ID']) ? $params['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined);
		$liveFeedEntityType = CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID);
		if($liveFeedEntityType === CCrmLiveFeedEntity::Undefined)
		{
			return false;
		}

		$entityID = (int)(isset($params['ENTITY_ID']) ? $params['ENTITY_ID'] : 0);
		if($entityID <= 0)
		{
			return false;
		}

		$filter = array(
			'ENTITY_TYPE' => $liveFeedEntityType,
			'ENTITY_ID' => $entityID,
		);

		if(isset($params['INACTIVE']))
		{
			$filter['INACTIVE'] = ($params['INACTIVE'] === true || strcasecmp($params['INACTIVE'], 'Y') === 0)
				? 'Y' : 'N';
		}

		$dbRes = CSocNetLog::GetList(array('ID' => 'DESC'), $filter, false, false, array('ID'));
		while($arRes = $dbRes->Fetch())
		{
			CSocNetLog::Delete($arRes['ID']);
		}

		if(!is_array($options))
		{
			$options = array();
		}

		if(!isset($options['UNREGISTER_RELATION']) || $options['UNREGISTER_RELATION'])
		{
			CAllCrmSonetRelation::UnRegisterRelationsByEntity($entityTypeID, $entityID);
		}

		if(!isset($options['UNREGISTER_SUBSCRIPTION']) || $options['UNREGISTER_SUBSCRIPTION'])
		{
			CAllCrmSonetSubscription::UnRegisterSubscriptionByEntity($entityTypeID, $entityID);
		}

		return true;
	}
	static public function RebindAndActivate($srcEntityTypeID, $srcEntityID, $dstEntityTypeID, $dstEntityID, $isActive)
	{
		if(!CModule::IncludeModule('socialnetwork'))
		{
			return;
		}

		$srcEntityTypeID = (int)$srcEntityTypeID;
		$srcEntityID = (int)$srcEntityID;
		$dstEntityTypeID = (int)$dstEntityTypeID;
		$dstEntityID = (int)$dstEntityID;

		$dbRes = CSocNetLog::GetList(
			array('ID' => 'DESC'),
			array(
				'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID($srcEntityTypeID),
				'ENTITY_ID' => $srcEntityID,
				'!=INACTIVE' => !$isActive ? 'Y' : 'N'
			),
			false,
			false,
			array('ID')
		);

		$IDs = array();
		while($arRes = $dbRes->Fetch())
		{
			$IDs[] = (int)$arRes['ID'];
		}

		foreach($IDs as $ID)
		{
			CSocNetLog::Update(
				$ID,
				array(
					'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID($dstEntityTypeID),
					'ENTITY_ID' => $dstEntityID,
					'INACTIVE' => !$isActive ? 'Y' : 'N'
				)
			);
		}

		CCrmSonetRelation::TransferRelationsOwnership($srcEntityTypeID, $srcEntityID, $dstEntityTypeID, $dstEntityID);
		CCrmSonetSubscription::TransferSubscriptionOwnership($srcEntityTypeID, $srcEntityID, $dstEntityTypeID, $dstEntityID);
	}
	static public function Rebind($entityTypeID, $srcEntityID, $dstEntityID)
	{
		if(!CModule::IncludeModule('socialnetwork'))
		{
			return;
		}

		$srcEntityID = (int)$srcEntityID;
		$dstEntityID = (int)$dstEntityID;
		$liveFeedEntityType = CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID);
		$eventID = CCrmLiveFeedEvent::GetEventID($liveFeedEntityType, CCrmLiveFeedEvent::Message);

		$dbRes = CSocNetLog::GetList(
			array('ID' => 'DESC'),
			array(
				'EVENT_ID' => $eventID,
				'ENTITY_TYPE' => $liveFeedEntityType,
				'ENTITY_ID' => $srcEntityID
			),
			false,
			false,
			array('ID')
		);

		$IDs = array();
		while($arRes = $dbRes->Fetch())
		{
			$IDs[] = (int)$arRes['ID'];
		}

		foreach($IDs as $ID)
		{
			CSocNetLog::Update($ID, array('ENTITY_TYPE' => $liveFeedEntityType, 'ENTITY_ID' => $dstEntityID));
		}

		CCrmSonetRelation::TransferRelationsOwnership($entityTypeID, $srcEntityID, $entityTypeID, $dstEntityID);
	}
	static public function TransferOwnership($srcEntityTypeID, $srcEntityID, $dstEntityTypeID, $dstEntityID)
	{
		if(!CModule::IncludeModule('socialnetwork'))
		{
			return;
		}

		$srcEntityID = (int)$srcEntityID;
		$dstEntityID = (int)$dstEntityID;

		$srcLiveFeedEntityType = CCrmLiveFeedEntity::GetByEntityTypeID($srcEntityTypeID);

		$dbRes = CSocNetLog::GetList(
			array('ID' => 'DESC'),
			array(
				'ENTITY_TYPE' => $srcLiveFeedEntityType,
				'ENTITY_ID' => $srcEntityID
			),
			false,
			false,
			array('ID')
		);

		$IDs = array();
		while($arRes = $dbRes->Fetch())
		{
			$IDs[] = (int)$arRes['ID'];
		}

		$dstLiveFeedEntityType = CCrmLiveFeedEntity::GetByEntityTypeID($dstEntityTypeID);
		foreach($IDs as $ID)
		{
			CSocNetLog::Update($ID, array('ENTITY_TYPE' => $dstLiveFeedEntityType, 'ENTITY_ID' => $dstEntityID));
		}

		CCrmSonetRelation::TransferRelationsOwnership($srcEntityTypeID, $srcEntityID, $dstEntityTypeID, $dstEntityID);
	}
	static private function RegisterOwnershipRelations($logEntityID, $logEventID, &$fields)
	{
		if (!Settings\Crm::isLiveFeedRecordsGenerationEnabled())
		{
			return;
		}

		$entityTypeID = isset($fields['ENTITY_TYPE_ID']) ? (int)$fields['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
		if (!CCrmOwnerType::IsDefined($entityTypeID))
		{
			return;
		}

		$entityID = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;
		if ($entityID < 0)
		{
			return;
		}

		$parents = isset($fields['PARENTS']) && is_array($fields['PARENTS']) ? $fields['PARENTS'] : [];
		if (!empty($fields['PARENTS']))
		{
			$parentOptions = isset($fields['PARENT_OPTIONS']) && is_array($fields['PARENT_OPTIONS'])
				? $fields['PARENT_OPTIONS']
				: [];

			$parentOptions['TYPE_ID'] = CCrmSonetRelationType::Ownership;

			CCrmSonetRelation::RegisterRelationBundle($logEntityID, $logEventID, $entityTypeID, $entityID, $parents, $parentOptions);
		}
		else
		{
			$parentEntityTypeID = isset($fields['PARENT_ENTITY_TYPE_ID'])
				? intval($fields['PARENT_ENTITY_TYPE_ID'])
				: CCrmOwnerType::Undefined;
			$parentEntityID = isset($fields['PARENT_ENTITY_ID'])
				? intval($fields['PARENT_ENTITY_ID'])
				: 0;

			if (CCrmOwnerType::IsDefined($parentEntityTypeID) && $parentEntityID > 0)
			{
				CCrmSonetRelation::RegisterRelation($logEntityID, $logEventID, $entityTypeID, $entityID, $parentEntityTypeID, $parentEntityID, CCrmSonetRelationType::Ownership, 1);
			}
		}
	}

	static public function CounterIncrement($arLogFields)
	{
		global $DB;

		$data = self::PrepareCounterData($arLogFields);
		if(!is_array($data))
		{
			return;
		}

		$arEntities = isset($data['ENTITIES']) ? $data['ENTITIES'] : array();
		$conditionSql = "";
		foreach ($arEntities as $entityName => $arTmp)
		{
			foreach ($arTmp as $arEntity)
			{
				if ($conditionSql !== "")
				{
					$conditionSql .= " OR ";
				}

				$conditionSql .= "
					EXISTS (
							SELECT S.USER_ID
							FROM ".CCrmSonetSubscription::TABLE_NAME." S
							WHERE
								S.SL_ENTITY_TYPE = '".CCrmLiveFeedEntity::GetByEntityTypeID($arEntity["ENTITY_TYPE_ID"])."'
								AND S.ENTITY_ID = ".((int)$arEntity["ENTITY_ID"])."
								AND U.ID = S.USER_ID
						) ";
			}
		}

		$arUserID = isset($data['USERS']) ? $data['USERS'] : [];
		$authorID = CCrmSecurityHelper::GetCurrentUserID();

		$adminList = [];
		$res = Main\UserAccessTable::getList([
			'filter' => [
				'=ACCESS_CODE' => 'G1',
				'!=USER_ID' => $authorID
			],
			'select' => [ 'USER_ID' ]
		]);
		while ($recordFields = $res->fetch())
		{
			$adminList[] = $recordFields['USER_ID'];
		}
		$arUserID = array_unique(array_merge($arUserID, $adminList));

		if(count($arUserID) > 50)
		{
			$arUserIDChunks = array_chunk($arUserID, 50);
		}
		else
		{
			$arUserIDChunks = array($arUserID);
		}

		$logID = isset($arLogFields["LOG_ID"]) ? (int)$arLogFields["LOG_ID"] : 0;

		$chunksCount = count($arUserIDChunks);
		foreach($arUserIDChunks as $i => $arUserIDChunk)
		{
			if (empty($arUserIDChunk))
			{
				continue;
			}
			$sql = "SELECT U.ID as ID, 1 as CNT, '**' as SITE_ID ,'CRM_**' as CODE, 0 as SENT
				FROM b_user U
				WHERE
					U.ID IN (".implode(",", $arUserIDChunk).") ".
				(
					($conditionSql !== "" || $logID > 0)
					? "
					AND
					(
						".$conditionSql.
						($logID > 0
							?
								($conditionSql !== "" ? " OR " : "")."
								EXISTS (
									SELECT GROUP_CODE
									FROM b_sonet_log_right LR
									WHERE
										LR.LOG_ID = ".$logID."
										AND LR.GROUP_CODE = ".$DB->Concat("'U'", "U.ID")."
								) "
							: ""
						)."
					)
					"
					: ""
				);

			if($sql !== "")
			{
				CUserCounter::IncrementWithSelect(
					$sql,
					($i >= ($chunksCount - 1)), // $sendPull
					array(
						"CLEAN_CACHE" => ($i >= ($chunksCount - 1) ? "Y" : "N"),
						"USERS_TO_PUSH" => $arUserIDChunk
					)
				);
			}
		}
	}
	static private function PrepareCounterData($arLogFields)
	{
		global $DB;

		static $blogPostEventIdList = null;

		$author_id = CCrmSecurityHelper::GetCurrentUserID();
		if($author_id <= 0 && isset($arLogFields["USER_ID"]))
		{
			$author_id = intval($arLogFields["USER_ID"]);
		}

		if($author_id <= 0)
		{
			return null;
		}

		$entityTypeID = CCrmLiveFeedEntity::ResolveEntityTypeID($arLogFields["ENTITY_TYPE"]);
		$entityID = $arLogFields["ENTITY_ID"];

		$arEntities = array();

		if ($entityTypeID == CCrmOwnerType::Activity)
		{
			if ($arActivity = CCrmActivity::GetByID($entityID))
			{
				$entityTypeID = $arActivity["OWNER_TYPE_ID"];
				$entityID = $arActivity["OWNER_ID"];
				$entityName = CCrmOwnerType::ResolveName($entityTypeID);
				$bOpened = CCrmOwnerType::isOpened($entityTypeID, $entityID, false);
				$responsible_id = CCrmOwnerType::GetResponsibleID($entityTypeID, $entityID, false);

				if (
					intval($entityID) > 0
					&& $entityName
					&& intval($responsible_id) > 0
				)
				{
					if (!array_key_exists($entityName, $arEntities))
					{
						$arEntities[$entityName] = array();
					}

					$arEntities[$entityName][$entityTypeID."_".$entityID] = array(
						"ENTITY_TYPE_ID" => $entityTypeID,
						"ENTITY_ID" => $entityID,
						"ENTITY_NAME" => $entityName,
						"IS_OPENED" => $bOpened,
						"RESPONSIBLE_ID" => $responsible_id
					);
				}

				$arCommunications = CCrmActivity::GetCommunications($arActivity["ID"]);
				foreach ($arCommunications as $arActivityCommunication)
				{
					$entityTypeID = $arActivityCommunication["ENTITY_TYPE_ID"];
					$entityID = $arActivityCommunication["ENTITY_ID"];
					$entityName = CCrmOwnerType::ResolveName($entityTypeID);
					$bOpened = CCrmOwnerType::isOpened($entityTypeID, $entityID, false);
					$responsible_id = CCrmOwnerType::GetResponsibleID($entityTypeID, $entityID, false);

					if (
						intval($entityID) > 0
						&& $entityName
						&& intval($responsible_id) > 0
					)
					{
						if (!array_key_exists($entityName, $arEntities))
						{
							$arEntities[$entityName] = array();
						}

						$arEntities[$entityName][$entityTypeID."_".$entityID] = array(
							"ENTITY_TYPE_ID" => $entityTypeID,
							"ENTITY_ID" => $entityID,
							"ENTITY_NAME" => $entityName,
							"IS_OPENED" => $bOpened,
							"RESPONSIBLE_ID" => $responsible_id
						);
					}
				}
			}
		}
		elseif ($entityTypeID == CCrmOwnerType::Invoice)
		{
			if ($arInvoice = CCrmInvoice::GetByID($entityID))
			{
				$arBindings = array(
					CCrmOwnerType::Contact => $arInvoice["UF_CONTACT_ID"],
					CCrmOwnerType::Company => $arInvoice["UF_COMPANY_ID"],
					CCrmOwnerType::Deal => $arInvoice["UF_DEAL_ID"]
				);

				foreach($arBindings as $entityTypeID => $entityID)
				{
					if (intval($entityID) > 0)
					{
						$entityName = CCrmOwnerType::ResolveName($entityTypeID);
						$bOpened = CCrmOwnerType::isOpened($entityTypeID, $entityID, false);
						$responsible_id = CCrmOwnerType::GetResponsibleID($entityTypeID, $entityID, false);

						if (
							$entityName
							&& intval($responsible_id) > 0
						)
						{
							if (!array_key_exists($entityName, $arEntities))
							{
								$arEntities[$entityName] = array();
							}

							$arEntities[$entityName][$entityTypeID."_".$entityID] = array(
								"ENTITY_TYPE_ID" => $entityTypeID,
								"ENTITY_ID" => $entityID,
								"ENTITY_NAME" => $entityName,
								"IS_OPENED" => $bOpened,
								"RESPONSIBLE_ID" => $responsible_id
							);
						}
					}
				}
			}
		}
		else
		{
			$entityName = CCrmOwnerType::ResolveName($entityTypeID);
			$bOpened = CCrmOwnerType::isOpened($entityTypeID, $entityID, false);
			$responsible_id = CCrmOwnerType::GetResponsibleID($entityTypeID, $entityID, false);

			if (
				intval($entityID) > 0
				&& $entityName
				&& intval($responsible_id) > 0
			)
			{
				if (!array_key_exists($entityName, $arEntities))
				{
					$arEntities[$entityName] = array();
				}

				$arEntities[$entityName][$entityTypeID."_".$entityID] = array(
					"ENTITY_TYPE_ID" => $entityTypeID,
					"ENTITY_ID" => $entityID,
					"ENTITY_NAME" => $entityName,
					"IS_OPENED" => $bOpened,
					"RESPONSIBLE_ID" => $responsible_id
				);
			}
		}

		if ($blogPostEventIdList === null)
		{
			if (Main\Loader::includeModule('socialnetwork'))
			{
				$blogPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\BlogPost;
				$blogPostEventIdList = $blogPostLivefeedProvider->getEventId();
			}
			else
			{
				$blogPostEventIdList = array();
			}
		}

		if (
			$arLogFields["LOG_ID"] > 0
			&& in_array(
				$arLogFields["EVENT_ID"],
				array_merge($blogPostEventIdList, array("crm_lead_message", "crm_deal_message", "crm_contact_message", "crm_company_message")))
		)
		{
			$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arLogFields["LOG_ID"]));
			while ($arRight = $dbRight->Fetch())
			{
				if (preg_match('/^('.CCrmLiveFeedEntity::Contact.'|'.CCrmLiveFeedEntity::Lead.'|'.CCrmLiveFeedEntity::Company.'|'.CCrmLiveFeedEntity::Deal.')(\d+)$/', $arRight["GROUP_CODE"], $matches))
				{
					$entityTypeID = CCrmLiveFeedEntity::ResolveEntityTypeID($matches[1]);
					$entityID = $matches[2];
					$entityName = CCrmOwnerType::ResolveName($entityTypeID);
					$responsible_id = CCrmOwnerType::GetResponsibleID($entityTypeID, $entityID, false);

					if (!array_key_exists($entityName, $arEntities))
					{
						$arEntities[$entityName] = array();
					}

					if (
						$entityID > 0
						&& $entityName
						&& $responsible_id > 0
						&& !array_key_exists($entityTypeID."_".$entityID, $arEntities[$entityName])
					)
					{
						$arEntities[$entityName][$entityTypeID."_".$entityID] = array(
							"ENTITY_TYPE_ID" => $entityTypeID,
							"ENTITY_ID" => $entityID,
							"ENTITY_NAME" => $entityName,
							"IS_OPENED" => CCrmOwnerType::isOpened($entityTypeID, $entityID, false),
							"RESPONSIBLE_ID" => $responsible_id
						);
					}
				}
			}
		}

		$entityNameList = array_keys($arEntities);
		$arUserID = array();
		$permsList = array();

		if (!empty($entityNameList))
		{
			$sSql = "SELECT RL.RELATION, RP.ATTR, RP.ENTITY
				FROM b_crm_role_relation RL
				INNER JOIN b_crm_role_perms RP ON RL.ROLE_ID = RP.ROLE_ID AND RP.ENTITY IN (".implode(',', array_map(function($val) { return "'".$val."'"; }, $entityNameList)).") AND RP.PERM_TYPE = 'READ'
				GROUP BY RL.RELATION, RP.ATTR, RP.ENTITY
			";

			$res = $DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			while($row = $res->Fetch())
			{
				if (!isset($permsList[$row['ENTITY']]))
				{
					$permsList[$row['ENTITY']] = [];
				}
				$permsList[$row['ENTITY']][] = array(
					'RELATION' => $row['RELATION'],
					'ATTR' => $row['ATTR']
				);
			}

			foreach ($arEntities as $entityName => $arTmp)
			{
				$responsibleList = array();
				$bHasOpenEntity = false;

				foreach ($arTmp as $arEntity)
				{
					$responsibleList[] = intval($arEntity["RESPONSIBLE_ID"]);
					if ($arEntity["IS_OPENED"])
					{
						$bHasOpenEntity = true;
					}
				}

				$entityPermissions = (isset($permsList[$entityName]) ? $permsList[$entityName] : []);
				$selfRelationsList = $userRelationsList = $departmentRelationsList = $subDepartmentRelationsList = [];

				foreach($entityPermissions as $entityPermission)
				{
					$perm = $entityPermission['ATTR'];
					$relation = $entityPermission['RELATION'];

					switch ($perm)
					{
						case BX_CRM_PERM_SELF:
							if (!empty($responsibleList))
							{
								$selfRelationsList[] = $relation;
							}
							break;
						case BX_CRM_PERM_ALL:
						case BX_CRM_PERM_CONFIG:
						case BX_CRM_PERM_OPEN:
							if (
								$perm != BX_CRM_PERM_OPEN
								|| $bHasOpenEntity
							)
							{
								$userRelationsList[] = $relation;
							}
							break;
						case BX_CRM_PERM_DEPARTMENT:
							if (!empty($responsibleList))
							{
								$departmentRelationsList[] = $relation;
							}
							break;
						case BX_CRM_PERM_SUBDEPARTMENT:
							if (!empty($responsibleList))
							{
								$subDepartmentRelationsList[] = $relation;
							}
							break;
					}
				}

				$chunkSize = 500;

				if (!empty($selfRelationsList))
				{
					$chunks = array_chunk($selfRelationsList, $chunkSize);
					foreach($chunks as $selfRelationsListChunk)
					{
						$strSQL = "SELECT UA.USER_ID
							FROM b_user_access UA
							WHERE
								UA.USER_ID IN (".implode(', ', $responsibleList).")
								AND UA.ACCESS_CODE IN ('".implode("', '", $selfRelationsListChunk)."')";
						$rsUser = $DB->Query($strSQL, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
						while ($arUser = $rsUser->Fetch())
						{
							if ($arUser["USER_ID"] != $author_id)
							{
								$arUserID[] = $arUser["USER_ID"];
							}
						}
					}
				}

				if (!empty($userRelationsList))
				{
					$chunks = array_chunk($userRelationsList, $chunkSize);
					foreach($chunks as $userRelationsListChunk)
					{
						$strSQL = "SELECT UA.USER_ID
							FROM b_user_access UA
							WHERE
								UA.ACCESS_CODE IN ('".implode("', '", $userRelationsListChunk)."')";
						$rsUser = $DB->Query($strSQL, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
						while ($arUser = $rsUser->Fetch())
						{
							if ($arUser["USER_ID"] != $author_id)
							{
								$arUserID[] = $arUser["USER_ID"];
							}
						}
					}
				}

				if (!empty($departmentRelationsList))
				{
					$chunks = array_chunk($departmentRelationsList, $chunkSize);
					foreach($chunks as $departmentRelationsListChunk)
					{
						$strSQL = "SELECT UA.USER_ID
								FROM b_user_access UA
								INNER JOIN b_user_access UA1 ON
									UA1.USER_ID IN (".implode(',', $responsibleList).")
									AND UA1.ACCESS_CODE LIKE 'D%'
									AND UA1.ACCESS_CODE NOT LIKE 'DR%'
									AND UA1.ACCESS_CODE = UA.ACCESS_CODE
								INNER JOIN b_user_access UA2 ON
									UA2.USER_ID = UA.USER_ID
									AND UA2.ACCESS_CODE IN ('".implode("', '", $departmentRelationsListChunk)."')";
						$rsUser = $DB->Query($strSQL, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
						while ($arUser = $rsUser->Fetch())
						{
							if ($arUser["USER_ID"] != $author_id)
							{
								$arUserID[] = $arUser["USER_ID"];
							}
						}
					}
				}

				if (!empty($subDepartmentRelationsList))
				{
					$chunks = array_chunk($subDepartmentRelationsList, $chunkSize);
					foreach($chunks as $subDepartmentRelationsListChunk)
					{
						$strSQL = "SELECT UA.USER_ID
								FROM b_user_access UA
								INNER JOIN b_user_access UA1 ON
									UA1.USER_ID IN (".implode(',', $responsibleList).")
									AND UA1.ACCESS_CODE LIKE 'DR%'
									AND UA1.ACCESS_CODE = UA.ACCESS_CODE
								INNER JOIN b_user_access UA2 ON
									UA2.USER_ID = UA.USER_ID
									AND UA2.ACCESS_CODE IN ('".implode("', '", $subDepartmentRelationsListChunk)."')";
						$rsUser = $DB->Query($strSQL, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
						while ($arUser = $rsUser->Fetch())
						{
							if ($arUser["USER_ID"] != $author_id)
							{
								$arUserID[] = $arUser["USER_ID"];
							}
						}
					}
				}
			}

			$arUserID = array_unique($arUserID);
		}

		return array('ENTITIES' => $arEntities, 'USERS' => $arUserID);
	}
	static public function CheckCreatePermission($entityType, $entityID, $userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckUpdatePermission(
			CCrmPerms::ResolvePermissionEntityType(
				CCrmOwnerType::ResolveName(
					CCrmLiveFeedEntity::ResolveEntityTypeID($entityType)
				),
				$entityID
			),
			$entityID,
			$userPermissions
		);
	}

	public static function OnSendMentionGetEntityFields($arCommentFields)
	{
		if (!in_array($arCommentFields["ENTITY_TYPE"], CCrmLiveFeedEntity::GetAll()))
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
			),
			false,
			false,
			array("ID", "ENTITY_ID", "EVENT_ID")
		);

		if ($arLog = $dbLog->Fetch())
		{
			$genderSuffix = "";
			$dbUser = CUser::GetByID($arCommentFields["USER_ID"]);
			if($arUser = $dbUser->Fetch())
			{
				$genderSuffix = $arUser["PERSONAL_GENDER"];
			}

			switch ($arLog["EVENT_ID"])
			{
				case "crm_company_add":
					$entityName = GetMessage("CRM_LF_COMPANY_ADD_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_COMPANY_ADD|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_contact_add":
					$entityName = GetMessage("CRM_LF_CONTACT_ADD_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Contact, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_CONTACT_ADD|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_lead_add":
					$entityName = GetMessage("CRM_LF_LEAD_ADD_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_LEAD_ADD|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_deal_add":
					$entityName = GetMessage("CRM_LF_DEAL_ADD_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_DEAL_ADD|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_company_responsible":
					$entityName = GetMessage("CRM_LF_COMPANY_RESPONSIBLE_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_COMPANY_RESPONSIBLE|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_contact_responsible":
					$entityName = GetMessage("CRM_LF_CONTACT_RESPONSIBLE_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Contact, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_CONTACT_RESPONSIBLE|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_lead_responsible":
					$entityName = GetMessage("CRM_LF_LEAD_RESPONSIBLE_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_LEAD_RESPONSIBLE|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_deal_responsible":
					$entityName = GetMessage("CRM_LF_DEAL_RESPONSIBLE_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_DEAL_RESPONSIBLE|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_company_message":
					$entityName = GetMessage("CRM_LF_COMPANY_MESSAGE_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_COMPANY_MESSAGE|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_contact_message":
					$entityName = GetMessage("CRM_LF_CONTACT_MESSAGE_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Contact, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_CONTACT_MESSAGE|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_lead_message":
					$entityName = GetMessage("CRM_LF_LEAD_MESSAGE_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_LEAD_MESSAGE|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_deal_message":
					$entityName = GetMessage("CRM_LF_DEAL_MESSAGE_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_DEAL_MESSAGE|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_activity_add":
					if ($arActivity = CCrmActivity::GetByID($arLog["ENTITY_ID"]))
					{
						switch ($arActivity["OWNER_TYPE_ID"])
						{
							case CCrmOwnerType::Company:
								$ownerType = "COMPANY";
								break;
							case CCrmOwnerType::Contact:
								$ownerType = "CONTACT";
								break;
							case CCrmOwnerType::Lead:
								$ownerType = "LEAD";
								break;
							case CCrmOwnerType::Deal:
								$ownerType = "DEAL";
								break;

						}

						switch ($arActivity["TYPE_ID"])
						{
							case CCrmActivityType::Meeting:
								$activityType = "MEETING";
								break;
							case CCrmActivityType::Call:
								$activityType = "CALL";
								break;
							case CCrmActivityType::Email:
								$activityType = "EMAIL";
								break;
						}

						if (
							$ownerType
							&& $activityType
						)
						{
							$entityName = GetMessage("CRM_LF_ACTIVITY_".$activityType."_".$ownerType."_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption($arActivity["OWNER_TYPE_ID"], $arActivity["OWNER_ID"], false))));
							$notifyTag = "CRM_ACTIVITY_ADD|COMMENT_MENTION|".$arCommentFields["ID"];
						}
					}
					break;
				case "crm_invoice_add":
					if ($arInvoice = CCrmInvoice::GetByID($arLog["ENTITY_ID"]))
					{
						$entityName = GetMessage("CRM_LF_INVOICE_ADD_COMMENT_MENTION_TITLE", array("#id#" => $arInvoice["ID"]));
						$notifyTag = "CRM_INVOICE_ADD|COMMENT_MENTION|".$arCommentFields["ID"];
					}
					break;
				case "crm_lead_progress":
					$entityName = GetMessage("CRM_LF_LEAD_PROGRESS_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_LEAD_PROGRESS|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_deal_progress":
					$entityName = GetMessage("CRM_LF_DEAL_PROGRESS_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_DEAL_PROGRESS|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
			}

			if ($entityName)
			{
				$notifyMessage = GetMessage("CRM_LF_COMMENT_MENTION".($genderSuffix <> '' ? "_".$genderSuffix : ""), Array("#title#" => "<a href=\"#url#\" class=\"bx-notifier-item-action\">".$entityName."</a>"));
				$notifyMessageOut = GetMessage("CRM_LF_COMMENT_MENTION".($genderSuffix <> '' ? "_".$genderSuffix : ""), Array("#title#" => $entityName))." ("."#server_name##url#)";

				$strPathToLogCrmEntry = str_replace("#log_id#", $arLog["ID"], "/crm/stream/?log_id=#log_id#");
				$strPathToLogCrmEntryComment = $strPathToLogCrmEntry.(mb_strpos($strPathToLogCrmEntry, "?") !== false ? "&" : "?")."commentID=".$arCommentFields["ID"]."#com".$arCommentFields["ID"];

				if (in_array($arLog["EVENT_ID"], array("crm_company_message", "crm_contact_message", "crm_deal_message", "crm_lead_message")))
				{
					$strPathToLogEntry = str_replace("#log_id#", $arLog["ID"], COption::GetOptionString("socialnetwork", "log_entry_page", "/company/personal/log/#log_id#/", SITE_ID));
					$strPathToLogEntryComment = $strPathToLogEntry.(mb_strpos($strPathToLogEntry, "?") !== false ? "&" : "?")."commentID=".$arCommentFields["ID"]."#com".$arCommentFields["ID"];
				}

				$arReturn = array(
					"IS_CRM" => "Y",
					"URL" => $strPathToLogEntryComment,
					"CRM_URL" => $strPathToLogCrmEntryComment,
					"NOTIFY_MODULE" => "crm",
					"NOTIFY_TAG" => $notifyTag,
					"NOTIFY_MESSAGE" => $notifyMessage,
					"NOTIFY_MESSAGE_OUT" => $notifyMessageOut
				);

				return $arReturn;
			}

			return false;
		}
		else
		{
			return false;
		}
	}

	public static function GetShowUrl($logEventID)
	{
		return CComponentEngine::MakePathFromTemplate(
			'#SITE_DIR#crm/stream/?log_id=#log_id#',
			array('log_id' => $logEventID)
		);
	}

	public static function onAfterCommentAdd($entityType, $entityId, $arData)
	{
		global $USER_FIELD_MANAGER, $APPLICATION, $DB;

		// 'TK' is our entity type
		if (
			$entityType !== 'TK'
			|| intval($entityId) <= 0
			|| !CModule::IncludeModule('tasks')
			|| !CModule::IncludeModule('socialnetwork')
			|| !\Bitrix\Tasks\Integration\SocialNetwork::isEnabled()
		)
		{
			return;
		}

		$taskId = (int) $entityId;
		$messageId  = $arData['MESSAGE_ID'];

		$parser = new CTextParser();

		if (
			array_key_exists('AUTHOR_ID', $arData['PARAMS'])
			&& array_key_exists('EDIT_DATE', $arData['PARAMS'])
			&& array_key_exists('POST_DATE', $arData['PARAMS'])
		)
		{
			$messageAuthorId = $arData['PARAMS']['AUTHOR_ID'];
		}
		else
		{
			$arMessage = CForumMessage::GetByID($messageId);
			$messageAuthorId = $arMessage['AUTHOR_ID'];
		}

		$occurAsUserId = CTasksTools::getOccurAsUserId();
		if (!$occurAsUserId)
		{
			$occurAsUserId = ($messageAuthorId ?: 1);
		}

		try
		{
			$oTask = new \CTaskItem($taskId, \Bitrix\Tasks\Util\User::getAdminId());
			$arTask = $oTask->getData(true, [ 'select' => [ 'UF_CRM_TASK' ] ]);
		}
		catch (TasksException | CTaskAssertException $e)
		{
			return;
		}

		if (
			!isset($arTask)
			|| !isset($arTask['UF_CRM_TASK'])
			|| (
				is_array($arTask['UF_CRM_TASK'])
				&& (
					!isset($arTask['UF_CRM_TASK'][0])
					|| $arTask['UF_CRM_TASK'][0] == ''
				)
			)
			|| (
				!is_array($arTask['UF_CRM_TASK'])
				&& (
					$arTask['UF_CRM_TASK'] == ''
				)
			)
		)
		{
			return;
		}

		$dbCrmActivity = CCrmActivity::GetList(
			array(),
			array(
				'TYPE_ID' => CCrmActivityType::Task,
				'ASSOCIATED_ENTITY_ID' => $taskId,
				'CHECK_PERMISSIONS' => 'N'
			),
			false,
			false,
			array('ID')
		);
		$arCrmActivity = $dbCrmActivity->Fetch();
		if (!$arCrmActivity)
		{
			$dbCrmActivity = CCrmActivity::GetList(
				[],
				[
					'TYPE_ID' => CCrmActivityType::Provider,
					'PROVIDER_ID' => Task::getId(),
					'ASSOCIATED_ENTITY_ID' => $taskId,
					'CHECK_PERMISSIONS' => 'N',
				],
				false,
				false,
				['ID']
			);
			$arCrmActivity = $dbCrmActivity->Fetch();
			if (!$arCrmActivity)
			{
				return;
			}
		}

		$crmActivityId = $arCrmActivity['ID'];

		// sonet log
		$dbLog = CSocNetLog::GetList(
			array(),
			array(
				"EVENT_ID" => "crm_activity_add",
				"ENTITY_ID" => $crmActivityId
			),
			false,
			false,
			array("ID", "ENTITY_TYPE", "ENTITY_ID", "EVENT_ID", "USER_ID")
		);
		if ($arLog = $dbLog->Fetch())
		{
			$log_id = $arLog["ID"];
			$entity_type = $arLog["ENTITY_TYPE"];
			$entity_id = $arLog["ENTITY_ID"];

			$strURL = $APPLICATION->GetCurPageParam("", array("IFRAME", "MID", "SEF_APPLICATION_CUR_PAGE_URL", BX_AJAX_PARAM_ID, "result"));
			$strURL = ForumAddPageParams(
				$strURL,
				array(
					"MID" => $messageId,
					"result" => "reply"
				),
				false,
				false
			);
			$sText = (COption::GetOptionString("forum", "FILTER", "Y") === "Y" ? $arMessage["POST_MESSAGE_FILTER"] : $arMessage["POST_MESSAGE"]);

			$arFieldsForSocnet = array(
				"ENTITY_TYPE" => $entity_type,
				"ENTITY_ID" => $entity_id,
				"EVENT_ID" => "crm_activity_add_comment",
				"MESSAGE" => $sText,
				"TEXT_MESSAGE" => $parser->convert4mail($sText),
				"URL" => str_replace("?IFRAME=Y", "", str_replace("&IFRAME=Y", "", str_replace("IFRAME=Y&", "", $strURL))),
				"MODULE_ID" => "crm",
				"SOURCE_ID" => $messageId,
				"LOG_ID" => $log_id,
				"RATING_TYPE_ID" => "FORUM_POST",
				"RATING_ENTITY_ID" => $messageId
			);

			$arFieldsForSocnet["USER_ID"] = $occurAsUserId;
			$arFieldsForSocnet["=LOG_DATE"] = CDatabase::CurrentTimeFunction();

			$ufFileID = array();
			$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $messageId));
			while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
			{
				$ufFileID[] = $arAddedMessageFiles["FILE_ID"];
			}

			if (count($ufFileID) > 0)
			{
				$arFieldsForSocnet["UF_SONET_COM_FILE"] = $ufFileID;
			}

			$ufDocID = $USER_FIELD_MANAGER->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $messageId, LANGUAGE_ID);
			if ($ufDocID)
			{
				$arFieldsForSocnet["UF_SONET_COM_DOC"] = $ufDocID;
			}

			if (!empty($arData['AUX_DATA']))
			{
				if (is_array($arData['AUX_DATA']))
				{
					$arFieldsForSocnet['MESSAGE'] = $arFieldsForSocnet['TEXT_MESSAGE'] = \Bitrix\Socialnetwork\CommentAux\TaskInfo::getPostText();
				}
				else  // old comments, can be removed after forum 20.5.0
				{
					$arFieldsForSocnet['SHARE_DEST'] = $arData['AUX_DATA'];
				}
			}

			if ($logCommentId = CSocNetLogComments::Add($arFieldsForSocnet, false, false))
			{
				if (Option::get('crm', 'enable_livefeed_merge', 'N') === 'Y')
				{
					\CSocNetLog::counterIncrement(
						$logCommentId,
						false,
						false,
						'LC',
						\CSocNetLogRights::checkForUserAll($log_id)
					);
				}

				CCrmLiveFeed::CounterIncrement($arLog);
			}

		}
	}

	public static function AddCrmActivityComment($arFields)
	{
		global $USER_FIELD_MANAGER, $USER;

		if (!CModule::IncludeModule("forum"))
		{
			return false;
		}

		$ufFileID = array();
		$ufDocID = array();
		$sError = '';
		$messageID = $ufUrlPreview = false;

		$dbResult = CSocNetLog::GetList(
			array(),
			array("ID" => $arFields["LOG_ID"]),
			false,
			false,
			array('ID', 'ENTITY_ID', 'SOURCE_ID', 'SITE_ID', 'TITLE', 'PARAMS')
		);

		if ($arLog = $dbResult->Fetch())
		{
			$dbCrmActivity = CCrmActivity::GetList(
				array(),
				array(
					'ID' => $arLog['ENTITY_ID'],
					'CHECK_PERMISSIONS' => 'N'
				)
			);

			if ($arCrmActivity = $dbCrmActivity->Fetch())
			{
				if (
					(
						$arCrmActivity['TYPE_ID'] == CCrmActivityType::Task
						|| (
							(int)$arCrmActivity['TYPE_ID'] === CCrmActivityType::Provider
							&& $arCrmActivity['PROVIDER_ID'] === \Bitrix\Crm\Activity\Provider\Tasks\Task::getId()
						)
					)
					&& CModule::IncludeModule('tasks')
				)
				{
					$dbTask = CTasks::GetByID($arCrmActivity["ASSOCIATED_ENTITY_ID"], false);
					if ($arTaskFields = $dbTask->Fetch())
					{
						$FORUM_ID = \Bitrix\Tasks\Integration\SocialNetwork\Task::getCommentForumId();

						$arFieldsMessage = array(
							"POST_MESSAGE" => $arFields["TEXT_MESSAGE"],
							"USE_SMILES" => "Y",
							"PERMISSION_EXTERNAL" => "E",
							"PERMISSION" => "E",
							"APPROVED" => "Y"
						);

						$USER_FIELD_MANAGER->EditFormAddFields("SONET_COMMENT", $arTmp);
						if (is_array($arTmp))
						{
							if (array_key_exists("UF_SONET_COM_DOC", $arTmp))
							{
								$GLOBALS["UF_FORUM_MESSAGE_DOC"] = $arTmp["UF_SONET_COM_DOC"];
							}
							elseif (array_key_exists("UF_SONET_COM_FILE", $arTmp))
							{
								$arFieldsMessage["FILES"] = array();
								foreach($arTmp["UF_SONET_COM_FILE"] as $file_id)
								{
									$arFieldsMessage["FILES"][] = array("FILE_ID" => $file_id);
								}
							}

							if (array_key_exists("UF_SONET_COM_URL_PRV", $arTmp))
							{
								$GLOBALS["UF_FORUM_MES_URL_PRV"] = $arTmp["UF_SONET_COM_URL_PRV"];
							}
						}

						$feed = new \Bitrix\Forum\Comments\Feed(
							$FORUM_ID,
							array(
								"type" => "TK",
								"id" => $arTaskFields['ID'],
								"xml_id" => "TASK_".$arTaskFields['ID']
							),
							(
								is_object($USER)
								&& $USER instanceof \CUser
									? $USER->getId()
									: (isset($arFields['CURRENT_USER_ID']) ? $arFields['CURRENT_USER_ID'] : 0)
							)
						);

						\Bitrix\Tasks\Integration\SocialNetwork::disable(); // disable socnet on comment add to avoid recursion
						$message = $feed->add($arFieldsMessage);
						\Bitrix\Tasks\Integration\SocialNetwork::enable(); // enable it back

						if(is_array($message))
						{
							$messageID = $message['ID'];
						}
						else
						{
							foreach($feed->getErrors() as $error)
							{
								$sError .= $error->getMessage();
							}
						}

						// get UF DOC value and FILE_ID there
						if ($messageID > 0)
						{
							// legacy files? will it work?
							$dbAddedMessageFiles = CForumFiles::getList(array("ID" => "ASC"), array("MESSAGE_ID" => $messageID));
							while ($arAddedMessageFiles = $dbAddedMessageFiles->fetch())
								$ufFileID[] = $arAddedMessageFiles["FILE_ID"];

							// files in UF_*
							$ufDocID = $USER_FIELD_MANAGER->getUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $messageID, LANGUAGE_ID);
							$ufUrlPreview = $USER_FIELD_MANAGER->getUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MES_URL_PRV", $messageID, LANGUAGE_ID);
						}
					}
				}
				else
				{
					return array(
						"NO_SOURCE" => "Y"
					);
				}
			}
			else
			{
				$sError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
			}
		}
		else
		{
			$sError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
		}

		return array(
			"SOURCE_ID" => $messageID,
			"RATING_TYPE_ID" => "FORUM_POST",
			"RATING_ENTITY_ID" => $messageID,
			"ERROR" => $sError,
			"NOTES" => '',
			"UF" => array(
				"FILE" => $ufFileID,
				"DOC" => $ufDocID,
				"URL_PREVIEW" => $ufUrlPreview
			)
		);
	}

	public static function UpdateCrmActivityComment($arFields)
	{
		global $USER_FIELD_MANAGER;

		if (
			!isset($arFields["SOURCE_ID"])
			|| intval($arFields["SOURCE_ID"]) <= 0
		)
		{
			return false;
		}

		$ufFileID = array();
		$ufDocID = array();

		$dbResult = CSocNetLog::GetList(
			array(),
			array("ID" => $arFields["LOG_ID"]),
			false,
			false,
			array('ID', 'ENTITY_ID')
		);

		if ($arLog = $dbResult->Fetch())
		{
			$dbCrmActivity = CCrmActivity::GetList(
				array(),
				array(
					'ID' => $arLog['ENTITY_ID'],
					'CHECK_PERMISSIONS' => 'N'
				)
			);

			if (
				($arCrmActivity = $dbCrmActivity->Fetch())
				&& ($arCrmActivity['TYPE_ID'] == CCrmActivityType::Task)
				&& CModule::IncludeModule("forum")
			)
			{
				$messageId = intval($arFields["SOURCE_ID"]);

				if ($arForumMessage = CForumMessage::GetByID($messageId))
				{
					$arFieldsMessage = array(
						"POST_MESSAGE" => $arFields["TEXT_MESSAGE"],
						"USE_SMILES" => "Y",
						"APPROVED" => "Y",
						"SONET_PERMS" => array("bCanFull" => true)
					);

					$arTmp = array();
					$USER_FIELD_MANAGER->EditFormAddFields("SONET_COMMENT", $arTmp);
					if (is_array($arTmp))
					{
						if (array_key_exists("UF_SONET_COM_DOC", $arTmp))
						{
							$GLOBALS["UF_FORUM_MESSAGE_DOC"] = $arTmp["UF_SONET_COM_DOC"];
						}
						elseif (array_key_exists("UF_SONET_COM_FILE", $arTmp))
						{
							$arFieldsMessage["FILES"] = array();
							foreach($arTmp["UF_SONET_COM_FILE"] as $file_id)
							{
								$arFieldsMessage["FILES"][$file_id] = array("FILE_ID" => $file_id);
							}
							if (!empty($arFieldsMessage["FILES"]))
							{
								$arFileParams = array("FORUM_ID" => $arForumMessage["FORUM_ID"], "TOPIC_ID" => $arForumMessage["TOPIC_ID"]);
								if(CForumFiles::CheckFields($arFieldsMessage["FILES"], $arFileParams, "NOT_CHECK_DB"))
								{
									CForumFiles::Add(array_keys($arFieldsMessage["FILES"]), $arFileParams);
								}
							}
						}
					}

					$messageID = ForumAddMessage("EDIT", $arForumMessage["FORUM_ID"], $arForumMessage["TOPIC_ID"], $messageId, $arFieldsMessage, $sError, $sNote);
					unset($GLOBALS["UF_FORUM_MESSAGE_DOC"]);

					// get UF DOC value and FILE_ID there
					if ($messageID > 0)
					{
						$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $messageID));
						while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
						{
							$ufFileID[] = $arAddedMessageFiles["FILE_ID"];
						}

						$ufDocID = $USER_FIELD_MANAGER->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $messageID, LANGUAGE_ID);
					}
				}
				else
				{
					$sError = GetMessage("CRM_SL_UPDATE_COMMENT_SOURCE_ERROR");
				}

				return array(
					"ERROR" => $sError,
					"NOTES" => $sNote,
					"UF" => array(
						"FILE" => $ufFileID,
						"DOC" => $ufDocID
					)
				);
			}
		}

		return array(
			"NO_SOURCE" => "Y"
		);
	}

	public static function DeleteCrmActivityComment($arFields): array
	{
		if (
			!isset($arFields['SOURCE_ID'])
			|| (int)$arFields['SOURCE_ID'] <= 0
		)
		{
			return [
				'NO_SOURCE' => 'Y',
			];
		}

		$res = CSocNetLog::getList(
			[],
			[
				'ID' => $arFields['LOG_ID'],
			],
			false,
			false,
			[ 'ID', 'ENTITY_ID' ]
		);

		if ($logFields = $res->fetch())
		{
			$res = CCrmActivity::getList(
				[],
				[
					'ID' => $logFields['ENTITY_ID'],
					'CHECK_PERMISSIONS' => 'N',
				]
			);

			if ($crmActivityFields = $res->fetch())
			{
				if ((int)$crmActivityFields['TYPE_ID'] === CCrmActivityType::Task)
				{
					if (Loader::includeModule('forum'))
					{
						ForumActions('DEL', [
							'MID' => (int)$arFields['SOURCE_ID'],
							'PERMISSION' => 'Y',
						], $strErrorMessage, $strOKMessage);

						return [
							'ERROR' => $strErrorMessage,
							'NOTES' => $strOKMessage,
						];
					}

					return [
						'ERROR' => Loc::getMessage('CRM_SL_DELETE_COMMENT_SOURCE_ERROR_FORUM_NOT_INSTALLED'),
						'NOTES' => false,
					];
				}
			}
		}

		return [
			'NO_SOURCE' => 'Y',
		];
	}

	public static function GetLogEventLastUpdateTime($ID, $useTimeZome = true)
	{
		if(!$useTimeZome)
		{
			CTimeZone::Disable();
		}
		$dbResult = CSocNetLog::GetList(
			array(),
			array('ID' => $ID),
			false,
			false,
			array('ID', 'LOG_UPDATE')
		);

		$arFields = is_object($dbResult) ? $dbResult->Fetch() : null;
		$result = isset($arFields['LOG_UPDATE']) ? $arFields['LOG_UPDATE'] : '';

		if(!$useTimeZome)
		{
			CTimeZone::Enable();
		}

		return $result;
	}

	public static function SyncTaskEvent($arEntity, $arAllTaskFields)
	{
		if (!CModule::IncludeModule('socialnetwork'))
		{
			return;
		}

		if (
			$arAllTaskFields
			&& !empty($arAllTaskFields['GROUP_ID'])
			&& (int)$arAllTaskFields['GROUP_ID'] > 0
			&& $arEntity
			&& !empty($arEntity['ID'])
			&& (int)$arEntity['ID'] > 0
		)
		{
			$rsLog = CSocNetLog::GetList(
				array(),
				array(
					"EVENT_ID" => "crm_activity_add",
					"ENTITY_ID" => (int)$arEntity["ID"]
				),
				array("ID")
			);
			if ($arLog = $rsLog->Fetch())
			{
				$arSite = array();
				$rsGroupSite = CSocNetGroup::GetSite(intval($arAllTaskFields['GROUP_ID']));
				if ($rsGroupSite)
				{
					while($arGroupSite = $rsGroupSite->Fetch())
					{
						$arSite[] = $arGroupSite["LID"];
					}
				}
				if (!empty($arSite))
				{
					CSocNetLog::Update($arLog["ID"], array("SITE_ID" => $arSite));
				}
			}
		}
	}

	public static function PrepareOwnershipRelations($entityTypeID, array $entityIDs, array &$relations)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		$prefix = CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeID);
		foreach($entityIDs as $entityID)
		{
			$entityID = (int)$entityID;
			if($entityID <= 0)
			{
				continue;
			}

			$key = "{$prefix}_{$entityID}";
			if(!isset($relations[$key]))
			{
				$relations[$key] = array('ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID);
			}
		}
	}

	public static function deleteUserCrmConnection($UFValue)
	{
		if (!empty($UFValue))
		{
			$obUser = new CUser;

			$res = Main\UserTable::getList(array(
				'filter' => array(
					'=UF_USER_CRM_ENTITY' => $UFValue
				),
				'select' => array('ID')
			));
			while ($user = $res->Fetch())
			{
				$obUser->Update($user['ID'], array(
					'UF_USER_CRM_ENTITY' => false
				));
			}
		}
	}

	public static function hasEvents()
	{
		if(!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}

		$dbResult = CSocNetLog::GetList(
			array(),
			array('MODULE_ID' => array('crm', 'crm_shared')),
			false,
			array('nTopCount' => 1),
			array('ID')
		);

		return is_array($dbResult->Fetch());
	}

	public static function registerItemAdd(Item $item, Context $context): void
	{
		$params = array_merge(
			$item->getCompatibleData(),
			[
				'AUTHOR_ID' => $item->getCreatedBy(),
				'RESPONSIBLE_ID' => $item->getAssignedById(),
			],
		);

		if ($item->hasField(Item::FIELD_NAME_FM))
		{
			$multifields = $item->getFm()->toArray();
			$params['PHONES'] = \CCrmFieldMulti::ExtractValues($multifields, \Bitrix\Crm\Multifield\Type\Phone::ID);
			$params['EMAILS'] = \CCrmFieldMulti::ExtractValues($multifields, \Bitrix\Crm\Multifield\Type\Email::ID);
		}

		if ($item->hasField(Item::FIELD_NAME_TYPE_ID))
		{
			$params['TYPE'] = $item->getTypeId();
		}

		if ($item->hasField(Item\Contact::FIELD_NAME_PHOTO))
		{
			$params['PHOTO_ID'] = $item->get(Item\Contact::FIELD_NAME_PHOTO);
		}

		if ($item->hasField(Item\Company::FIELD_NAME_LOGO))
		{
			$params['LOGO_ID'] = $item->get(Item\Company::FIELD_NAME_LOGO);
		}

		$liveFeedFields = [
			'USER_ID' => $item->getCreatedBy(),
			'ENTITY_TYPE_ID' => $item->getEntityTypeId(),
			'ENTITY_ID' => $item->getId(),
			'TITLE' => Loc::getMessage(
				'CRM_LF_EVENT_ADD',
				[
					'#ENTITY_TYPE_CAPTION#' => htmlspecialcharsbx(\CCrmOwnerType::GetDescription($item->getEntityTypeId())),
				],
			),
			'PARAMS' => $params,
			'PARENTS' => static::prepareParentRelations($item),
		];

		$logEventId = static::CreateLogEvent(
			$liveFeedFields,
			\CCrmLiveFeedEvent::Add,
			[
				'CURRENT_USER' => $context->getUserId(),
			],
		);

		if ($logEventId === false)
		{
			return;
		}

		if (
			static::isNotificationEnabled($item->getEntityTypeId())
			&& $item->getCreatedBy() !== $item->getAssignedById()
		)
		{
			static::sendNotificationAboutResponsibleAdd($item);
		}
	}

	private static function isNotificationEnabled(int $entityTypeId): bool
	{
		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			return \Bitrix\Crm\Settings\LeadSettings::isEnabled();
		}

		return true;
	}

	private static function sendNotificationAboutResponsibleAdd(Item $item): void
	{
		if (!Loader::includeModule('im'))
		{
			return;
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($item->getEntityTypeId());

		$message = Loc::getMessage(
			'CRM_LF_EVENT_BECOME_RESPONSIBLE',
			[
				'#ENTITY_TYPE_CAPTION#' => htmlspecialcharsbx(\CCrmOwnerType::GetDescription($item->getEntityTypeId())),
				'#TITLE#' => htmlspecialcharsbx($item->getHeading()),
				'#URL#' => '#URL#',
			],
		);

		$url = Container::getInstance()->getRouter()->getItemDetailUrl($item->getEntityTypeId(), $item->getId());

		\CIMNotify::Add([
			'MESSAGE_TYPE' => IM_MESSAGE_SYSTEM,
			'TO_USER_ID' => $item->getAssignedById(),
			'FROM_USER_ID' => $item->getCreatedBy(),
			'NOTIFY_TYPE' => IM_NOTIFY_FROM,
			'NOTIFY_MODULE' => 'crm',
			//'NOTIFY_EVENT' => mb_strtolower($entityTypeName) . '_add',
			'NOTIFY_EVENT' => 'changeAssignedBy',
			'NOTIFY_TAG' => "CRM|{$entityTypeName}_RESPONSIBLE|" . $item->getId(),
			'NOTIFY_MESSAGE' => str_replace('#URL#', $url, $message),
			'NOTIFY_MESSAGE_OUT' => str_replace('#URL#', static::transformRelativeUrlToAbsolute($url), $message),
		]);
	}

	private static function transformRelativeUrlToAbsolute(Uri $url): Uri
	{
		$host = Application::getInstance()->getContext()->getRequest()->getServer()->getHttpHost();

		$absoluteUrl = new Uri((string)$url);

		return $absoluteUrl->setHost($host);
	}

	public static function registerItemUpdate(Item $itemBeforeSave, Item $item, Context $context): void
	{
		foreach (static::prepareUpdateEventData($itemBeforeSave, $item) as $singleEvent)
		{
			$logEventId = static::CreateLogEvent(
				$singleEvent['FIELDS'],
				$singleEvent['TYPE'],
				[
					'CURRENT_USER' => $context->getUserId(),
				],
			);

			if ($logEventId === false)
			{
				continue;
			}

			if (static::isNotificationEnabled($item->getEntityTypeId()))
			{
				if ($singleEvent['TYPE'] === \CCrmLiveFeedEvent::Responsible)
				{
					static::sendNotificationAboutAssignedChange($itemBeforeSave, $item);
				}

				if ($singleEvent['TYPE'] === \CCrmLiveFeedEvent::Progress)
				{
					static::sendNotificationAboutStageChange($itemBeforeSave, $item);
				}
			}
		}
	}

	private static function prepareUpdateEventData(Item $itemBeforeSave, Item $item): array
	{
		$difference = ComparerBase::compareEntityFields($itemBeforeSave->getData(Values::ACTUAL), $item->getData());
		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());
		if (!$factory)
		{
			return [];
		}

		$eventData = [];
		if ($factory->isStagesEnabled() && $difference->isChanged(Item::FIELD_NAME_STAGE_ID))
		{
			$eventData[\CCrmLiveFeedEvent::Progress] = [
				'TYPE' => \CCrmLiveFeedEvent::Progress,
				'FIELDS' => [
					'ENTITY_TYPE_ID' => $item->getEntityTypeId(),
					'ENTITY_ID' => $item->getId(),
					'USER_ID' => $item->getUpdatedBy(),
					'TITLE' => Loc::getMessage(
						'CRM_LF_EVENT_FIELD_CHANGED',
						['#FIELD_CAPTION#' => htmlspecialcharsbx($factory->getFieldCaption(Item::FIELD_NAME_STAGE_ID))]
					),
					'PARAMS' => [
						'START_STATUS_ID' => $difference->getPreviousValue(Item::FIELD_NAME_STAGE_ID),
						'FINAL_STATUS_ID' => $difference->getCurrentValue(Item::FIELD_NAME_STAGE_ID),
						'CATEGORY_ID' => $item->isCategoriesSupported() ? $item->getCategoryId() : null,
					],
				],
			];
		}

		if ($difference->isChanged(Item::FIELD_NAME_ASSIGNED))
		{
			$eventData[\CCrmLiveFeedEvent::Responsible] = [
				'TYPE' => \CCrmLiveFeedEvent::Responsible,
				'FIELDS' => [
					'ENTITY_TYPE_ID' => $item->getEntityTypeId(),
					'ENTITY_ID' => $item->getId(),
					'USER_ID' => $item->getUpdatedBy(),
					'TITLE' => Loc::getMessage(
						'CRM_LF_EVENT_FIELD_CHANGED',
						['#FIELD_CAPTION#' => htmlspecialcharsbx($factory->getFieldCaption(Item::FIELD_NAME_ASSIGNED))]
					),
					'PARAMS' => [
						'START_RESPONSIBLE_ID' => $difference->getPreviousValue(Item::FIELD_NAME_ASSIGNED),
						'FINAL_RESPONSIBLE_ID' => $difference->getCurrentValue(Item::FIELD_NAME_ASSIGNED),
					],
				],
			];
		}

		if ($factory->isClientEnabled() && $item->hasField(Item::FIELD_NAME_CONTACT_IDS))
		{
			$previousContactIds = $itemBeforeSave->remindActual(Item::FIELD_NAME_CONTACT_IDS);
			$currentContactIds = $item->getContactIds();

			$addedContactIds = array_diff($currentContactIds, $previousContactIds);
			$removedContactIds = array_diff($previousContactIds, $currentContactIds);

			$isContactsChanged = !empty($addedContactIds) || !empty($removedContactIds);

			if ($isContactsChanged || $difference->isChanged(Item::FIELD_NAME_COMPANY_ID))
			{
				$eventData[\CCrmLiveFeedEvent::Client] = [
					'CODE'=> 'CLIENT',
					'TYPE' => \CCrmLiveFeedEvent::Client,
					'FIELDS' => [
						'ENTITY_TYPE_ID' => $item->getEntityTypeId(),
						'ENTITY_ID' => $item->getId(),
						'USER_ID' => $item->getUpdatedBy(),
						'TITLE' => Loc::getMessage(
							'CRM_LF_EVENT_FIELD_CHANGED',
							['#FIELD_CAPTION#' => Loc::getMessage('C_CRM_LF_CLIENT_NAME_TITLE')]
						),
						'PARAMS' => [
							'ADDED_CLIENT_CONTACT_IDS' => $addedContactIds,
							'REMOVED_CLIENT_CONTACT_IDS' => $removedContactIds,
							'START_CLIENT_CONTACT_ID' => $removedContactIds[0] ?? 0,
							'FINAL_CLIENT_CONTACT_ID' => $addedContactIds[0] ?? 0,
							'START_CLIENT_COMPANY_ID' => $difference->getPreviousValue(Item::FIELD_NAME_COMPANY_ID),
							'FINAL_CLIENT_COMPANY_ID' => $difference->getCurrentValue(Item::FIELD_NAME_COMPANY_ID),
						]
					]
				];
			}
		}

		if ($item->getEntityTypeId() === \CCrmOwnerType::Contact && $item->hasField(Item\Contact::FIELD_NAME_COMPANY_IDS))
		{
			$previousCompanyIds = $itemBeforeSave->remindActual(Item\Contact::FIELD_NAME_COMPANY_IDS);
			$currentCompanyIds = $item->get(Item\Contact::FIELD_NAME_COMPANY_IDS);

			$addedCompanyIds = array_diff($currentCompanyIds, $previousCompanyIds);
			$removedCompanyIds = array_diff($previousCompanyIds, $currentCompanyIds);

			$isCompaniesChanged = !empty($addedCompanyIds) || !empty($removedCompanyIds);
			if ($isCompaniesChanged)
			{
				$parents = [];
				static::PrepareOwnershipRelations(
					\CCrmOwnerType::Company,
					array_merge($currentCompanyIds, $removedCompanyIds),
					$parents,
				);
				$parents = array_values($parents);

				$eventData[\CCrmLiveFeedEvent::Owner] = [
					'CODE'=> 'COMPANY',
					'TYPE' => \CCrmLiveFeedEvent::Owner,
					'FIELDS' => [
						'TITLE' => Loc::getMessage(
							'CRM_LF_EVENT_FIELD_CHANGED',
							['#FIELD_CAPTION#' => Loc::getMessage('C_CRM_LF_COMPANY_TITLE')]
						),
						'MESSAGE' => '',
						'PARAMS' => [
							'REMOVED_OWNER_COMPANY_IDS' => $removedCompanyIds,
							'ADDED_OWNER_COMPANY_IDS' => $addedCompanyIds,
							'START_OWNER_COMPANY_ID' => $removedCompanyIds[0] ?? 0,
							'FINAL_OWNER_COMPANY_ID' => $addedCompanyIds[0] ?? 0,
						],
						'PARENTS' => $parents,
					],
				];
			}
		}

		if ($difference->isChanged(Item::FIELD_NAME_TITLE))
		{
			$eventData[\CCrmLiveFeedEvent::Denomination] = [
				'TYPE' => \CCrmLiveFeedEvent::Denomination,
				'FIELDS' => [
					'ENTITY_TYPE_ID' => $item->getEntityTypeId(),
					'ENTITY_ID' => $item->getId(),
					'USER_ID' => $item->getUpdatedBy(),
					'TITLE' => Loc::getMessage(
						'CRM_LF_EVENT_FIELD_CHANGED',
						['#FIELD_CAPTION#' => htmlspecialcharsbx($factory->getFieldCaption(Item::FIELD_NAME_TITLE))]
					),
					'PARAMS' => [
						'START_TITLE' => $difference->getPreviousValue(Item::FIELD_NAME_TITLE),
						'FINAL_TITLE' => $difference->getCurrentValue(Item::FIELD_NAME_TITLE),
					]
				]
			];
		}

		$commonParents = static::prepareParentRelations($item);
		if (is_array($commonParents))
		{
			foreach ($eventData as &$dataAboutSingleEvent)
			{
				$specificForEventParents = $dataAboutSingleEvent['FIELDS']['PARENTS'] ?? null;
				if (is_array($specificForEventParents))
				{
					$parents = array_merge($commonParents, $specificForEventParents);
				}
				else
				{
					$parents = $commonParents;
				}

				$dataAboutSingleEvent['FIELDS']['PARENTS'] = $parents;
			}
			unset($dataAboutSingleEvent);
		}

		return $eventData;
	}

	private static function prepareParentRelations(Item $item): ?array
	{
		if (!static::isParentRelationsSupported($item->getEntityTypeId()))
		{
			return null;
		}

		$parents = [];

		if ($item->hasField(Item::FIELD_NAME_COMPANY_ID) && $item->getCompanyId() > 0)
		{
			static::PrepareOwnershipRelations(
				\CCrmOwnerType::Company,
				[$item->getCompanyId()],
				$parents,
			);
		}

		if ($item->hasField(Item\Contact::FIELD_NAME_COMPANY_IDS))
		{
			$companyIds = $item->get(Item\Contact::FIELD_NAME_COMPANY_IDS);
			if (!empty($companyIds))
			{
				static::PrepareOwnershipRelations(
					\CCrmOwnerType::Company,
					$companyIds,
					$parents
				);
			}
		}

		if ($item->hasField(Item::FIELD_NAME_CONTACT_ID) && $item->getContactId() > 0)
		{
			static::PrepareOwnershipRelations(
				\CCrmOwnerType::Contact,
				[$item->getContactId()],
				$parents,
			);
		}

		if ($item->hasField(Item::FIELD_NAME_CONTACT_IDS))
		{
			$contactIds = $item->getContactIds();
			if (!empty($contactIds))
			{
				static::PrepareOwnershipRelations(
					\CCrmOwnerType::Contact,
					$contactIds,
					$parents
				);
			}
		}

		return array_values($parents);
	}

	private static function isParentRelationsSupported(int $entityTypeId): bool
	{
		/*
		 * To be honest, I don't know why other entity types do not have parents in live feed.
		 * Maybe the reason is historical.
		 * The goal of this method is to simply maintain the current workflow and data preparation.
		 * Feel free to remove if you know that it's not needed.
		 */

		return (
			$entityTypeId === \CCrmOwnerType::Deal
			|| $entityTypeId === \CCrmOwnerType::Contact
		);
	}

	private static function sendNotificationAboutAssignedChange(Item $itemBeforeSave, Item $item): void
	{
		if (!Loader::includeModule('im'))
		{
			return;
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($item->getEntityTypeId());
		$notificationFields = [
			'MESSAGE_TYPE' => IM_MESSAGE_SYSTEM,
			'FROM_USER_ID' => $item->getUpdatedBy(),
			'NOTIFY_TYPE' => IM_NOTIFY_FROM,
			'NOTIFY_MODULE' => 'crm',
			//'NOTIFY_EVENT' => mb_strtolower($entityTypeName) . '_update',
			'NOTIFY_EVENT' => 'changeAssignedBy',
			'NOTIFY_TAG' => "CRM|{$entityTypeName}_RESPONSIBLE|" . $item->getId(),
		];

		$previousAssigned = $itemBeforeSave->remindActual(Item::FIELD_NAME_ASSIGNED);
		$currentAssigned = $item->getAssignedById();

		$url = Container::getInstance()->getRouter()->getItemDetailUrl($item->getEntityTypeId(), $item->getId());

		if ($currentAssigned !== $item->getUpdatedBy())
		{
			$notificationToNewAssigned = $notificationFields;

			$notificationToNewAssigned['TO_USER_ID'] = $currentAssigned;

			$message = Loc::getMessage(
				'CRM_LF_EVENT_BECOME_RESPONSIBLE',
				[
					'#ENTITY_TYPE_CAPTION#' => htmlspecialcharsbx(\CCrmOwnerType::GetDescription($item->getEntityTypeId())),
					'#TITLE#' => htmlspecialcharsbx($item->getHeading()),
					'#URL#' => '#URL#',
				],
			);

			$notificationToNewAssigned['NOTIFY_MESSAGE'] = str_replace('#URL#', $url, $message);
			$notificationToNewAssigned['NOTIFY_MESSAGE_OUT'] = str_replace('#URL#', static::transformRelativeUrlToAbsolute($url), $message);

			\CIMNotify::Add($notificationToNewAssigned);
		}

		if ($previousAssigned !== $item->getUpdatedBy())
		{
			$notificationToPreviousAssigned = $notificationFields;

			$notificationToPreviousAssigned['TO_USER_ID'] = $previousAssigned;

			$message = Loc::getMessage(
				'CRM_LF_EVENT_NO_LONGER_RESPONSIBLE',
				[
					'#ENTITY_TYPE_CAPTION#' => htmlspecialcharsbx(\CCrmOwnerType::GetDescription($item->getEntityTypeId())),
					'#TITLE#' => htmlspecialcharsbx($item->getHeading()),
					'#URL#' => '#URL#',
				],
			);

			$notificationToPreviousAssigned['NOTIFY_MESSAGE'] = str_replace('#URL#', $url, $message);
			$notificationToPreviousAssigned['NOTIFY_MESSAGE_OUT'] = str_replace('#URL#', static::transformRelativeUrlToAbsolute($url), $message);

			\CIMNotify::Add($notificationToPreviousAssigned);
		}
	}

	private static function sendNotificationAboutStageChange(Item $itemBeforeSave, Item $item): void
	{
		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());
		if (!$factory)
		{
			return;
		}

		if ($item->getAssignedById() !== $item->getUpdatedBy())
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($item->getEntityTypeId());

			$previousStage = $factory->getStage($itemBeforeSave->remindActual(Item::FIELD_NAME_STAGE_ID));
			$currentStage = $factory->getStage($item->getStageId());

			$message = Loc::getMessage(
				'CRM_LF_EVENT_STAGE_CHANGED',
				[
					'#ENTITY_TYPE_CAPTION#' => htmlspecialcharsbx(\CCrmOwnerType::GetDescription($item->getEntityTypeId())),
					'#TITLE#' => htmlspecialcharsbx($item->getHeading()),
					'#START_STAGE_CAPTION#' => $previousStage ? htmlspecialcharsbx($previousStage->getName()) : '',
					'#FINAL_STAGE_CAPTION#' => $currentStage ? htmlspecialcharsbx($currentStage->getName()) : '',
					'#URL#' => '#URL#',
				],
			);

			$url = Container::getInstance()->getRouter()->getItemDetailUrl($item->getEntityTypeId(), $item->getId());

			\CIMNotify::Add([
				'MESSAGE_TYPE' => IM_MESSAGE_SYSTEM,
				'TO_USER_ID' => $item->getAssignedById(),
				'FROM_USER_ID' => $item->getUpdatedBy(),
				'NOTIFY_TYPE' => IM_NOTIFY_FROM,
				'NOTIFY_MODULE' => 'crm',
				//'NOTIFY_EVENT' => mb_strtolower($entityTypeName) . '_update',
				'NOTIFY_EVENT' => 'changeStage',
				'NOTIFY_TAG' => "CRM|{$entityTypeName}_PROGRESS|" . $item->getId(),
				'NOTIFY_MESSAGE' => str_replace('#URL#', $url, $message),
				'NOTIFY_MESSAGE_OUT' => str_replace('#URL#', static::transformRelativeUrlToAbsolute($url), $message),
			]);
		}
	}
}

class CCrmLiveFeedFilter
{
	private $gridFormID = null;
	private $entityTypeID = CCrmOwnerType::Undefined;
	private $arCompanyItemsTop = null;
	private $arItems = null;

	public function __construct($params)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		$this->gridFormID = isset($params["GridFormID"]) ? $params["GridFormID"] : "";
		$this->entityTypeID = isset($params["EntityTypeID"]) ? intval($params["EntityTypeID"]) : CCrmOwnerType::Undefined;

		$this->arCompanyItemsTop = array(
			"clearall" => array(
				"ID" => "clearall",
				"SORT" => 100,
				"NAME" => GetMessage("CRM_LF_COMPANY_PRESET_TOP")
			),
			"extended" => array(
				"ID" => "extended",
				"SORT" => 100,
				"NAME" => GetMessage("CRM_LF_COMPANY_PRESET_TOP_EXTENDED")
			)
		);

		$this->arCommonItemsTop = array(
			"clearall" => array(
				"ID" => "clearall",
				"SORT" => 100,
				"NAME" => GetMessage("CRM_LF_COMMON_PRESET_MY")
			),
			"all" => array(
				"ID" => "all",
				"SORT" => 200,
				"NAME" => GetMessage("CRM_LF_COMMON_PRESET_ALL")
			)
		);

		$this->arItems = array(
			"messages" => array(
				"ID" => "messages",
				"SORT" => 100,
				"NAME" => GetMessage("CRM_LF_PRESET_MESSAGES"),
				"FILTER" => array(
					"EVENT_ID" => array()
				)
			),
			"activities" => array(
				"ID" => "activities",
				"SORT" => 100,
				"NAME" => GetMessage("CRM_LF_PRESET_ACTIVITIES"),
				"FILTER" => array(
					"EVENT_ID" => array()
				)
			)
		);
	}

	public function OnBeforeSonetLogFilterFill(&$arPageParamsToClear, &$arItemsTop, &$arItems, &$strAllItemTitle)
	{
		$arPageParamsToClear[] = $this->gridFormID."_active_tab";

		if ($this->entityTypeID == CCrmOwnerType::Company)
		{
			$arItemsTop = array_merge($arItemsTop, $this->arCompanyItemsTop);
		}
		elseif (empty($this->entityTypeID))
		{
			$arItemsTop = array_merge($arItemsTop, $this->arCommonItemsTop);
		}

		$arItems = array_merge($arItems, $this->arItems);

		$strAllItemTitle = GetMessage("CRM_LF_PRESET_ALL");

		return true;
	}

	public function OnSonetLogFilterProcess($presetFilterTopID, $presetFilterID, $arResultPresetFiltersTop, $arResultPresetFilters)
	{
		$result = array(
			"PARAMS" => array(
				"CUSTOM_DATA" => array(
					"CRM_PRESET_TOP_ID" => is_string($presetFilterTopID) ? $presetFilterTopID : '',
					"CRM_PRESET_ID" => is_string($presetFilterID) ? $presetFilterID : ''
				)
			)
		);

		return $result;
	}

	/*public static function Activate($params)
	{
		$self = new CCrmLiveFeedFilter($params);
		AddEventHandler("socialnetwork", "OnSonetLogFilterProcess", array($self, "OnSonetLogFilterProcess"));
	}*/
}

class CCrmLiveFeedComponent
{
	private $eventMeta = null;
	private $entityTypeID = null;
	private $fields = null;
	private $eventParams = null;
	private $activity = null;
	private $invoice = null;
	private $arSipServiceUrl = null;

	public function __construct($params)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		$this->fields = isset($params["FIELDS"]) && !empty($params["FIELDS"]) ? $params["FIELDS"] : false;
		$this->eventParams = isset($params["EVENT_PARAMS"]) ? $params["EVENT_PARAMS"] : array();
		$this->params = isset($params["PARAMS"]) ? $params["PARAMS"] : array();

		$this->arSipServiceUrl = array(
			CCrmOwnerType::Lead => SITE_DIR.'bitrix/components/bitrix/crm.lead.show/ajax.php?'.bitrix_sessid_get(),
			CCrmOwnerType::Company => SITE_DIR.'bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get(),
			CCrmOwnerType::Contact => SITE_DIR.'bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get()
		);

		if (!$this->fields)
		{
			throw new Exception("Empty fields");
		}

		$this->entityTypeID = CCrmLiveFeedEntity::ResolveEntityTypeID($this->fields["ENTITY_TYPE"]);

		if ($this->entityTypeID == CCrmOwnerType::Activity)
		{
			$this->activity = isset($params["ACTIVITY"]) ? $params["ACTIVITY"] : array();
			$this->eventMeta = array(
				CCrmActivityType::Meeting => array(
					"SUBJECT" => array(
						"CODE" => "COMBI_ACTIVITY_SUBJECT/ACTIVITY_ONCLICK",
						"FORMAT" => "COMBI_TITLE"
					),
					"LOCATION" => array(
						"CODE" => "ACTIVITY_LOCATION",
						"FORMAT" => "TEXT"
					),
					"DATE" => array(
						"CODE" => "ACTIVITY_START_END_TIME",
						"FORMAT" => "DATETIME"
					),
					"CLIENT_ID" => array(
						"CODE" => "ACTIVITY_COMMUNICATIONS",
						"FORMAT" => "COMMUNICATIONS"
					),
					"RESPONSIBLE" => array(
						"CODE" => "ACTIVITY_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				),
				CCrmActivityType::Call => array(
					"SUBJECT" => array(
						"CODE" => "COMBI_ACTIVITY_SUBJECT/ACTIVITY_ONCLICK",
						"FORMAT" => "COMBI_TITLE"
					),
					"DATE" => array(
						"CODE" => "ACTIVITY_START_END_TIME",
						"FORMAT" => "DATETIME"
					),
					"CLIENT_ID" => array(
						"CODE" => "ACTIVITY_COMMUNICATIONS",
						"FORMAT" => "COMMUNICATIONS"
					),
					"RESPONSIBLE" => array(
						"CODE" => "ACTIVITY_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				),
				CCrmActivityType::Email => array(
					"SUBJECT" => array(
						"CODE" => "COMBI_ACTIVITY_SUBJECT/ACTIVITY_ONCLICK",
						"FORMAT" => "COMBI_TITLE"
					),
					"DATE" => array(
						"CODE" => "ACTIVITY_START_END_TIME",
						"FORMAT" => "DATETIME"
					),
					"CLIENT_ID" => array(
						"CODE" => "ACTIVITY_COMMUNICATIONS",
						"FORMAT" => "COMMUNICATIONS"
					),
					"RESPONSIBLE" => array(
						"CODE" => "ACTIVITY_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				)
			);
		}
		elseif ($this->entityTypeID == CCrmOwnerType::Invoice)
		{
			$this->invoice = isset($params["INVOICE"]) ? $params["INVOICE"] : array();
			$this->eventMeta = array(
				"crm_invoice_add" => array(
					"INVOICE_ADD_TITLE" => array(
						"CODE" => "COMBI_INVOICE_ACCOUNT_NUMBER/INVOICE_ORDER_TOPIC/INVOICE_URL",
						"FORMAT" => "COMBI_TITLE_ID"
					),
					"PRICE" => array(
						"CODE" => array(
							"VALUE" => "INVOICE_PRICE",
							"CURRENCY" => "INVOICE_CURRENCY"
						),
						"FORMAT" => "SUM"
					),
					"STATUS" => array(
						"CODE" => "INVOICE_STATUS_ID",
						"FORMAT" => "INVOICE_PROGRESS",
					),
					"CLIENT_ID" => array(
						"CODE" => "COMBI_INVOICE_UF_CONTACT_ID/INVOICE_UF_COMPANY_ID",
						"FORMAT" => "COMBI_CLIENT",
					),
					"DEAL" => array(
						"CODE" => "INVOICE_UF_DEAL_ID",
						"FORMAT" => "DEAL_ID",
					),
					"RESPONSIBLE" => array(
						"CODE" => "INVOICE_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				)
			);
		}
		else
		{
			$this->eventMeta = array(
				"crm_lead_add" => array(
					"ADD_TITLE" => array(
						"CODE" => "EVENT_PARAMS_TITLE",
						"FORMAT" => "TEXT_ADD"
					),
					"STATUS" => array(
						"CODE" => "EVENT_PARAMS_STATUS_ID",
						"FORMAT" => "LEAD_PROGRESS",
					),
					"CLIENT_NAME" => array(
						"CODE" => "COMBI_EVENT_PARAMS_NAME/EVENT_PARAMS_LAST_NAME/EVENT_PARAMS_SECOND_NAME/EVENT_PARAMS_COMPANY_TITLE/EVENT_PARAMS_HONORIFIC",
						"FORMAT" => "COMBI_CLIENT_NAME",
					),
					"OPPORTUNITY" => array(
						"CODE" => array(
							"VALUE" => "EVENT_PARAMS_OPPORTUNITY",
							"CURRENCY" => "EVENT_PARAMS_CURRENCY_ID"
						),
						"FORMAT" => "SUM"
					),
					"RESPONSIBLE" => array(
						"CODE" => "EVENT_PARAMS_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				),
				"crm_lead_progress" => array(
					"FINAL_STATUS_ID" => array(
						"CODE" => "EVENT_PARAMS_FINAL_STATUS_ID",
						"FORMAT" => "LEAD_PROGRESS",
					),
					"START_STATUS_ID" => array(
						"CODE" => "EVENT_PARAMS_START_STATUS_ID",
						"FORMAT" => "LEAD_PROGRESS"
					)
				),
				"crm_lead_responsible" => array(
					"FINAL_RESPONSIBLE_ID" => array(
						"CODE" => "EVENT_PARAMS_FINAL_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID",
					),
					"START_RESPONSIBLE_ID" => array(
						"CODE" => "EVENT_PARAMS_START_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				),
				"crm_lead_denomination" => array(
					"FINAL_TITLE" => array(
						"CODE" => "EVENT_PARAMS_FINAL_TITLE",
						"FORMAT" => "TEXT",
					),
					"START_TITLE" => array(
						"CODE" => "EVENT_PARAMS_START_TITLE",
						"FORMAT" => "TEXT"
					)
				),
				"crm_lead_message" => array(
					"MESSAGE_TITLE" => array(
						"CODE" => "TITLE",
						"FORMAT" => "TEXT_FORMATTED_BOLD",
					),
					"MESSAGE" => array(
						"CODE" => "MESSAGE",
						"FORMAT" => "TEXT_FORMATTED",
					),
				),
				"crm_contact_add" => array(
					"ADD_TITLE" => array(
						"CODE" => "COMBI_EVENT_PARAMS_NAME/EVENT_PARAMS_LAST_NAME/EVENT_PARAMS_SECOND_NAME/EVENT_PARAMS_PHOTO_ID/EVENT_PARAMS_COMPANY_ID/EVENT_PARAMS_HONORIFIC/ENTITY_ID",
						"FORMAT" => "COMBI_CLIENT",
					),
					"PHONES" => array(
						"CODE" => "EVENT_PARAMS_PHONES",
						"FORMAT" => "PHONE",
					),
					"EMAILS" => array(
						"CODE" => "EVENT_PARAMS_EMAILS",
						"FORMAT" => "EMAIL",
					),
					"RESPONSIBLE" => array(
						"CODE" => "EVENT_PARAMS_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				),
				"crm_contact_owner" => array(
					"FINAL_OWNER_COMPANY_ID" => array(
						"CODE" => "EVENT_PARAMS_FINAL_OWNER_COMPANY_ID",
						"FORMAT" => "COMPANY_ID"
					),
					"START_OWNER_COMPANY_ID" => array(
						"CODE" => "EVENT_PARAMS_START_OWNER_COMPANY_ID",
						"FORMAT" => "COMPANY_ID",
					),
				),
				"crm_contact_responsible" => array(
					"FINAL_RESPONSIBLE_ID" => array(
						"CODE" => "EVENT_PARAMS_FINAL_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID",
					),
					"START_RESPONSIBLE_ID" => array(
						"CODE" => "EVENT_PARAMS_START_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				),
				"crm_contact_message" => array(
					"MESSAGE_TITLE" => array(
						"CODE" => "TITLE",
						"FORMAT" => "TEXT_FORMATTED_BOLD",
					),
					"MESSAGE" => array(
						"CODE" => "MESSAGE",
						"FORMAT" => "TEXT_FORMATTED",
					),
				),
				"crm_company_add" => array(
					"ADD_TITLE" => array(
						"CODE" => "COMBI_EVENT_PARAMS_TITLE/EVENT_PARAMS_LOGO_ID/ENTITY_ID",
						"FORMAT" => "COMBI_COMPANY",
					),
					"COMPANY_TYPE" => array(
						"CODE" => "EVENT_PARAMS_TYPE",
						"FORMAT" => "COMPANY_TYPE",
					),
					"REVENUE" => array(
						"CODE" => array(
							"VALUE" => "EVENT_PARAMS_REVENUE",
							"CURRENCY" => "EVENT_PARAMS_CURRENCY_ID"
						),
						"FORMAT" => "SUM"
					),
					"PHONES" => array(
						"CODE" => "EVENT_PARAMS_PHONES",
						"FORMAT" => "PHONE",
					),
					"EMAILS" => array(
						"CODE" => "EVENT_PARAMS_EMAILS",
						"FORMAT" => "EMAIL",
					),
					"RESPONSIBLE" => array(
						"CODE" => "EVENT_PARAMS_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				),
				"crm_company_responsible" => array(
					"FINAL_RESPONSIBLE_ID" => array(
						"CODE" => "EVENT_PARAMS_FINAL_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID",
					),
					"START_RESPONSIBLE_ID" => array(
						"CODE" => "EVENT_PARAMS_START_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				),
				"crm_company_denomination" => array(
					"FINAL_TITLE" => array(
						"CODE" => "EVENT_PARAMS_FINAL_TITLE",
						"FORMAT" => "TEXT",
					),
					"START_TITLE" => array(
						"CODE" => "EVENT_PARAMS_START_TITLE",
						"FORMAT" => "TEXT"
					)
				),
				"crm_company_message" => array(
					"MESSAGE_TITLE" => array(
						"CODE" => "TITLE",
						"FORMAT" => "TEXT_FORMATTED_BOLD",
					),
					"MESSAGE" => array(
						"CODE" => "MESSAGE",
						"FORMAT" => "TEXT_FORMATTED",
					),
				),
				"crm_deal_add" => array(
					"ADD_TITLE" => array(
						"CODE" => "EVENT_PARAMS_TITLE",
						"FORMAT" => "TEXT_ADD"
					),
					"STATUS" => array(
						"CODE" => "COMBI_EVENT_PARAMS_STAGE_ID/EVENT_PARAMS_CATEGORY_ID",
						"FORMAT" => "DEAL_PROGRESS",
					),
					"OPPORTUNITY" => array(
						"CODE" => array(
							"VALUE" => "EVENT_PARAMS_OPPORTUNITY",
							"CURRENCY" => "EVENT_PARAMS_CURRENCY_ID"
						),
						"FORMAT" => "SUM"
					),
					"CLIENT_ID" => array(
						"CODE" => "COMBI_EVENT_PARAMS_CONTACT_ID/EVENT_PARAMS_COMPANY_ID",
						"FORMAT" => "COMBI_CLIENT",
					),
					"RESPONSIBLE" => array(
						"CODE" => "EVENT_PARAMS_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				),
				"crm_deal_progress" => array(
					"FINAL_STATUS_ID" => array(
						"CODE" => "COMBI_EVENT_PARAMS_FINAL_STATUS_ID/EVENT_PARAMS_CATEGORY_ID",
						"FORMAT" => "DEAL_PROGRESS",
					),
					"START_STATUS_ID" => array(
						"CODE" => "COMBI_EVENT_PARAMS_START_STATUS_ID/EVENT_PARAMS_CATEGORY_ID",
						"FORMAT" => "DEAL_PROGRESS"
					)
				),
				"crm_deal_responsible" => array(
					"FINAL_RESPONSIBLE_ID" => array(
						"CODE" => "EVENT_PARAMS_FINAL_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID",
					),
					"START_RESPONSIBLE_ID" => array(
						"CODE" => "EVENT_PARAMS_START_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				),
				"crm_deal_denomination" => array(
					"FINAL_TITLE" => array(
						"CODE" => "EVENT_PARAMS_FINAL_TITLE",
						"FORMAT" => "TEXT",
					),
					"START_TITLE" => array(
						"CODE" => "EVENT_PARAMS_START_TITLE",
						"FORMAT" => "TEXT"
					)
				),
				"crm_deal_message" => array(
					"MESSAGE_TITLE" => array(
						"CODE" => "TITLE",
						"FORMAT" => "TEXT_FORMATTED_BOLD",
					),
					"MESSAGE" => array(
						"CODE" => "MESSAGE",
						"FORMAT" => "TEXT_FORMATTED",
					),
				),
				"crm_deal_client" => array(
					"FINAL_CLIENT_ID" => array(
						"CODE" => "COMBI_EVENT_PARAMS_FINAL_CLIENT_CONTACT_ID/EVENT_PARAMS_FINAL_CLIENT_COMPANY_ID",
						"FORMAT" => "COMBI_CLIENT",
					),
					"START_CLIENT_ID" => array(
						"CODE" => "COMBI_EVENT_PARAMS_START_CLIENT_CONTACT_ID/EVENT_PARAMS_START_CLIENT_COMPANY_ID",
						"FORMAT" => "COMBI_CLIENT",
					),
				)
			);
		}

		if (!array_key_exists($this->fields["EVENT_ID"], $this->eventMeta))
		{
			return false;
		}

		return true;
	}

	public function showField($arField, $arUF = array())
	{
		$strResult = "";

		switch($arField["FORMAT"])
		{
			case "LEAD_PROGRESS":
				if (!empty($arField["VALUE"]))
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding crm-feed-info-bar-cont">';
					$strResult .= CCrmViewHelper::RenderLeadStatusControl(array(
						'ENTITY_TYPE_NAME' => CCrmOwnerType::Lead,
						'REGISTER_SETTINGS' => true,
						'PREFIX' => "",
						'ENTITY_ID' => CCrmLiveFeedEntity::Lead,
						'CURRENT_ID' => $arField["VALUE"],
						'READ_ONLY' => true
					));
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "DEAL_PROGRESS":
				if (!empty($arField["VALUE"]))
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding crm-feed-info-bar-cont">';
					$strResult .= CCrmViewHelper::RenderDealStageControl(array(
							'ENTITY_TYPE_NAME' => CCrmOwnerType::Deal,
							'REGISTER_SETTINGS' => true,
							'PREFIX' => "",
							'ENTITY_ID' => CCrmLiveFeedEntity::Deal,
							'CURRENT_ID' => $arField["VALUE"]["STAGE_ID"],
							'READ_ONLY' => true,
							'CATEGORY_ID' => (isset($arField["VALUE"]["CATEGORY_ID"]) ? intval($arField["VALUE"]["CATEGORY_ID"]) : 0)
						));
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "INVOICE_PROGRESS":
				if (!empty($arField["VALUE"]))
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding crm-feed-info-bar-cont">';
					$strResult .= CCrmViewHelper::RenderInvoiceStatusControl(array(
						'ENTITY_TYPE_NAME' => CCrmOwnerType::Invoice,
						'REGISTER_SETTINGS' => true,
						'PREFIX' => "",
						'ENTITY_ID' => CCrmLiveFeedEntity::Invoice,
						'CURRENT_ID' => $arField["VALUE"],
						'READ_ONLY' => true
					));
					$strResult .= "</span>";
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "LEAD_STATUS":
				$infos = CCrmStatus::GetStatus('STATUS');
				if (
					!empty($arField["VALUE"])
					&& array_key_exists($arField["VALUE"], $infos)
				)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= $infos[$arField["VALUE"]]["NAME"];
					$strResult .= "</span>";
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "PERSON_NAME":
				if (is_array($arField["VALUE"]))
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= CUser::FormatName(CSite::GetNameFormat(), $arField["VALUE"]);
					$strResult .= "</span>";
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "PERSON_ID":
				if (intval($arField["VALUE"]) > 0)
				{
					$dbUser = CUser::GetByID(intval($arField["VALUE"]));
					if ($arUser = $dbUser->GetNext())
					{
						$strResult .= "#row_begin#";
						$strResult .= "#cell_begin_left#";
						$strResult .=  $arField["TITLE"].":";
						$strResult .= "#cell_end#";
						$strResult .= "#cell_begin_right#";

						if ($arUser["PERSONAL_PHOTO"] > 0)
						{
							$arFileTmp = CFile::ResizeImageGet(
								$arUser["PERSONAL_PHOTO"],
								array('width' => 100, 'height' => 100),
								BX_RESIZE_IMAGE_EXACT,
								false
							);
						}

						$strUser = "";

						$strUser .= '<span class="crm-feed-company-avatar">';
						if(is_array($arFileTmp) && isset($arFileTmp['src']))
						{
							if (($this->params["PATH_TO_USER"] ?? null) !== '')
							{
								$href = str_replace(["#user_id#", "#USER_ID#"], (int)$arField["VALUE"], ($this->params["PATH_TO_USER"] ?? null));
								$strUser .= '<a target="_blank" href="'. $href .'" class="ui-icon ui-icon-common-user" style="border: none;">'.
									"<i style=\"background: url('".Uri::urnEncode($arFileTmp['src'])."'); background-size: cover;\"></i>".
									'</a>';
							}
							else
							{
								$strUser .= '<img src="'.$arFileTmp['src'].'" alt=""/>';
							}
						}
						$strUser .= '</span><span class="crm-feed-client-right">';

						if (($this->params["PATH_TO_USER"] ?? null) !== '')
						{
							$href = str_replace(["#user_id#", "#USER_ID#"], intval($arField["VALUE"]), ($this->params["PATH_TO_USER"] ?? null));
							$strUser .= '<a class="crm-feed-user-name" target="_blank" href="'. $href .'">'.
								CUser::FormatName(CSite::GetNameFormat(), $arUser, true, false).
								'</a>';
						}
						else
						{
							$strUser .= '<span class="crm-feed-user-name">'.CUser::FormatName(CSite::GetNameFormat(), $arUser, true, false).'</span>';
						}

						if ($arUser["WORK_POSITION"] <> '')
						{
							$strUser .= '<span class="crm-detail-info-resp-descr">'.$arUser["WORK_POSITION"].'</span>';
						}

						$strUser .= '</span>';

						$strResult .= '<span class="crm-feed-user-block">'.$strUser.'</span>';

						$strResult .= "#cell_end#";
						$strResult .= "#row_end#";
					}
				}
				break;
			case "COMPANY_ID":
				if (intval($arField["VALUE"]) > 0)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= CCrmViewHelper::PrepareEntityBaloonHtml(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
							'ENTITY_ID' => $arField["VALUE"],
							'PREFIX' => "",
							'CLASS_NAME' => '',
							'CHECK_PERMISSIONS' => 'N'
						)
					);
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "COMPANY_TYPE":
				$infos = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
				if (
					!empty($arField["VALUE"])
					&& array_key_exists($arField["VALUE"], $infos)
				)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= $infos[$arField["VALUE"]];
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "CONTACT_ID":
				if (intval($arField["VALUE"]) > 0)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";

					$strResult .= '<div class="crm-feed-client-block">';
					$strResult .= '<span class="feed-com-avatar crm-feed-user-avatar">';
					$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $arField["VALUE"], 'CHECK_PERMISSIONS' => 'N', '@CATEGORY_ID' => 0,), false, false, array('PHOTO'));
					if (
						($arRes = $dbRes->Fetch())
						&& (intval($arRes["PHOTO"]) > 0)
					)
					{
						$arFileTmp = CFile::ResizeImageGet(
							$arRes["PHOTO"],
							array('width' => 100, 'height' => 100),
							BX_RESIZE_IMAGE_EXACT,
							false
						);

						if(
							is_array($arFileTmp)
							&& isset($arFileTmp["src"])
						)
						{
							$strResult .= '<img width="39" height="39" src="'.$arFileTmp['src'].'" alt="">';
						}
					}
					$strResult .= '</span>';

					$strResult .= CCrmViewHelper::PrepareEntityBaloonHtml(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
							'ENTITY_ID' => $arField["VALUE"],
							'PREFIX' => "",
							'CLASS_NAME' => '',
							'CHECK_PERMISSIONS' => 'N'
						)
					);

					$strResult .= '</div>';

					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "COMBI_CLIENT":
				if (
					is_array($arField["VALUE"])
					&& (
						(array_key_exists("CONTACT_ID", $arField["VALUE"]) && intval($arField["VALUE"]["CONTACT_ID"]) > 0)
						|| (array_key_exists("CONTACT_NAME", $arField["VALUE"]) && $arField["VALUE"]["CONTACT_NAME"] <> '')
						|| (array_key_exists("CONTACT_LAST_NAME", $arField["VALUE"]) && $arField["VALUE"]["CONTACT_LAST_NAME"] <> '')
						|| (array_key_exists("COMPANY_ID", $arField["VALUE"]) && intval($arField["VALUE"]["COMPANY_ID"]) > 0)
					)
				)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";

					if (
						(
							array_key_exists("CONTACT_ID", $arField["VALUE"])
							&& intval($arField["VALUE"]["CONTACT_ID"]) > 0
						)
						|| (
							array_key_exists("CONTACT_NAME", $arField["VALUE"])
							&& $arField["VALUE"]["CONTACT_NAME"] <> ''
						)
						|| (
							array_key_exists("CONTACT_LAST_NAME", $arField["VALUE"])
							&& $arField["VALUE"]["CONTACT_LAST_NAME"] <> ''
						)
					)
					{
						if (
							array_key_exists("CONTACT_ID", $arField["VALUE"])
							&& intval($arField["VALUE"]["CONTACT_ID"]) > 0
						)
						{
							$strResult .= '<div class="crm-feed-client-block">';
							$strResult .= '<span class="feed-com-avatar crm-feed-user-avatar">';
							$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $arField["VALUE"]["CONTACT_ID"], 'CHECK_PERMISSIONS' => 'N', '@CATEGORY_ID' => 0,), false, false, array('PHOTO', 'COMPANY_ID'));
							if ($arRes = $dbRes->Fetch())
							{
								$contactCompanyID = $arRes['COMPANY_ID'];
								if (intval($arRes["PHOTO"]) > 0)
								{
									$arFileTmp = CFile::ResizeImageGet(
										$arRes["PHOTO"],
										array('width' => 100, 'height' => 100),
										BX_RESIZE_IMAGE_EXACT,
										false
									);

									if(
										is_array($arFileTmp)
										&& isset($arFileTmp["src"])
									)
									{
										$strResult .= '<img width="39" height="39" src="'.$arFileTmp['src'].'" alt="">';
									}
								}
							}
							$strResult .= '</span>';
							$strResult .= '<span class="crm-feed-client-alignment"></span>';
							$strResult .= '<span class="crm-feed-client-right">';

							$strResult .= CCrmViewHelper::PrepareEntityBaloonHtml(
								array(
									'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
									'ENTITY_ID' => $arField["VALUE"]["CONTACT_ID"],
									'PREFIX' => '',
									'CLASS_NAME' => '',
									'CHECK_PERMISSIONS' => 'N'
								)
							);
						}
						else
						{
							$strResult .= '<div class="crm-feed-client-block">';
							$strResult .= '<span class="feed-com-avatar crm-feed-user-avatar">';

							if (intval($arField['VALUE']['PHOTO_ID']) > 0)
							{
								$arFileTmp = CFile::ResizeImageGet(
									$arField['VALUE']['PHOTO_ID'],
									array('width' => 100, 'height' => 100),
									BX_RESIZE_IMAGE_EXACT,
									false
								);

								if(
									is_array($arFileTmp)
									&& isset($arFileTmp["src"])
								)
								{
									$strResult .= '<img width="39" height="39" src="'.$arFileTmp['src'].'" alt="">';
								}
							}

							$strResult .= '</span>';
							$strResult .= '<span class="crm-feed-client-alignment"></span>';
							$strResult .= '<span class="crm-feed-client-right">';

							if (
								array_key_exists("ENTITY_ID", $arField["VALUE"])
								&& intval($arField["VALUE"]["ENTITY_ID"]) > 0
							)
							{
								$url = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Contact, $arField["VALUE"]["ENTITY_ID"], true);
							}

							$clientName = CCrmContact::PrepareFormattedName(
								array(
									'HONORIFIC' => isset($arField['VALUE']['HONORIFIC']) ? $arField['VALUE']['HONORIFIC'] : '',
									'NAME' => isset($arField['VALUE']['CONTACT_NAME']) ? $arField['VALUE']['CONTACT_NAME'] : '',
									'LAST_NAME' => isset($arField['VALUE']['CONTACT_LAST_NAME']) ? $arField['VALUE']['CONTACT_LAST_NAME'] : '',
									'SECOND_NAME' => isset($arField['VALUE']['CONTACT_SECOND_NAME']) ? $arField['VALUE']['CONTACT_SECOND_NAME'] : ''
								)
							);
							$strResult .= ($url <> '' ? '<a href="'.$url.'" class="crm-feed-client-name">'.$clientName.'</a>' : $clientName);
						}

						$strResult .= '<span class="crm-feed-client-company">';
						$strResult .= CCrmViewHelper::PrepareEntityBaloonHtml(
							array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
								'ENTITY_ID' => (
									array_key_exists("COMPANY_ID", $arField["VALUE"])
									&& intval($arField["VALUE"]["COMPANY_ID"]) > 0
										? $arField["VALUE"]["COMPANY_ID"]
										: intval($contactCompanyID)
								),
								'PREFIX' => '',
								'CLASS_NAME' => '',
								'CHECK_PERMISSIONS' => 'N'
							)
						);
						$strResult .= '</span>';
						$strResult .= '</span>'; // crm-feed-client-right

						$strResult .= '</div>';
					}
					else
					{
						$strResult .= CCrmViewHelper::PrepareEntityBaloonHtml(
							array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
								'ENTITY_ID' => $arField["VALUE"]["COMPANY_ID"],
								'PREFIX' => "",
								'CLASS_NAME' => '',
								'CHECK_PERMISSIONS' => 'N'
							)
						);
					}

					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}

				break;
			case "COMBI_COMPANY":
				if (
					is_array($arField["VALUE"])
					&& (array_key_exists("TITLE", $arField["VALUE"]) && $arField["VALUE"]["TITLE"] <> '')
				)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";

					$url = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Company, $arField["VALUE"]["ENTITY_ID"]);
					if (intval($arField['VALUE']['LOGO_ID']) > 0)
					{
						$arFileTmp = CFile::ResizeImageGet(
							$arField['VALUE']['LOGO_ID'],
							array('width' => 100, 'height' => 100),
							BX_RESIZE_IMAGE_EXACT,
							false
						);
					}

					if(is_array($arFileTmp) && isset($arFileTmp['src']))
					{
						$strResult .= '<span class="crm-feed-user-block" href="'.$url.'">';
							$strResult .= '<span class="crm-feed-company-avatar">';
								$strResult .= '<a href="'.$url.'" class="ui-icon ui-icon-common-user" style="border: none;">'.
									"<i style=\"background: url('".Uri::urnEncode($arFileTmp['src'])."'); background-size: cover;\"></i>".
									'</a>';
							$strResult .= '</span>';
							$strResult .= '<span class="crm-feed-client-right"><a href="'.$url.'" class="crm-feed-user-name">'.$arField["VALUE"]["TITLE"].'</a></span>';
						$strResult .= '</span>';
					}
					else
					{
						$strResult .= '<a class="crm-feed-info-link" href="'.$url.'">'.$arField["VALUE"]["TITLE"].'</a>';
					}

					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}

				break;
			case "COMBI_CLIENT_NAME":
				if (
					is_array($arField["VALUE"])
					&& (
						(array_key_exists("CONTACT_NAME", $arField["VALUE"]) && $arField["VALUE"]["CONTACT_NAME"] <> '')
						|| (array_key_exists("CONTACT_LAST_NAME", $arField["VALUE"]) && $arField["VALUE"]["CONTACT_LAST_NAME"] <> '')
						|| (array_key_exists("COMPANY_TITLE", $arField["VALUE"]) && $arField["VALUE"]["COMPANY_TITLE"] <> '')
					)
				)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";

					if (
						(
							array_key_exists("CONTACT_NAME", $arField["VALUE"])
							&& $arField["VALUE"]["CONTACT_NAME"] <> ''
						)
						|| (
							array_key_exists("CONTACT_LAST_NAME", $arField["VALUE"])
							&& $arField["VALUE"]["CONTACT_LAST_NAME"] <> ''
						)
					)
					{
						$strResult .= '<div class="crm-feed-client-block">';
						$strResult .= '<span class="feed-com-avatar crm-feed-user-avatar"></span>';

						$strResult .= '<span class="crm-feed-client-alignment"></span>';
						$strResult .= '<span class="crm-feed-client-right">';
							$strResult .= CCrmContact::PrepareFormattedName(
								array(
									"HONORIFIC" => $arField["VALUE"]["HONORIFIC"],
									"NAME" => $arField["VALUE"]["CONTACT_NAME"],
									"LAST_NAME" => $arField["VALUE"]["CONTACT_LAST_NAME"],
									"SECOND_NAME" => $arField["VALUE"]["CONTACT_SECOND_NAME"],
								)
							);
							$strResult .= '<span class="crm-feed-client-company">'.($arField["VALUE"]["COMPANY_TITLE"] <> '' ? $arField["VALUE"]["COMPANY_TITLE"] : "").'</span>';
						$strResult .= '</span>';
						$strResult .= '</div>';
					}
					else
					{
						$strResult .= $arField["VALUE"]["COMPANY_TITLE"];
					}

					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}

				break;
			case "DEAL_ID":
				if (intval($arField["VALUE"]) > 0)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= CCrmViewHelper::PrepareEntityBaloonHtml(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
							'ENTITY_ID' => $arField["VALUE"],
							'PREFIX' => "",
							'CLASS_NAME' => '',
							'CHECK_PERMISSIONS' => 'N'
						)
					);
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}

				break;
			case "COMMUNICATIONS":
				if (
					is_array($arField["VALUE"])
					&& count($arField["VALUE"]) > 0
				)
				{
					$arCommunication = $arField["VALUE"][0];

					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<div class="crm-feed-client-block">';

					if (in_array($arCommunication["ENTITY_TYPE_ID"], array(CCrmOwnerType::Company, CCrmOwnerType::Contact, CCrmOwnerType::Lead)))
					{
						$strResult .= '<span class="feed-com-avatar crm-feed-user-avatar">';
						if ($arCommunication["ENTITY_TYPE_ID"] == CCrmOwnerType::Contact)
						{
							$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $arCommunication["ENTITY_ID"], 'CHECK_PERMISSIONS' => 'N', '@CATEGORY_ID' => 0,), false, false, array('PHOTO'));
							if (
								($arRes = $dbRes->Fetch())
								&& (intval($arRes["PHOTO"]) > 0)
							)
							{
								$arFileTmp = CFile::ResizeImageGet(
									$arRes["PHOTO"],
									array('width' => 100, 'height' => 100),
									BX_RESIZE_IMAGE_EXACT,
									false
								);

								if(
									is_array($arFileTmp)
									&& isset($arFileTmp["src"])
								)
								{
									$strResult .= '<img width="39" height="39" src="'.$arFileTmp['src'].'" alt="">';
								}
							}
						}
						elseif ($arCommunication["ENTITY_TYPE_ID"] == CCrmOwnerType::Company)
						{
							$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $arCommunication["ENTITY_ID"], 'CHECK_PERMISSIONS' => 'N'), false, false, array('LOGO'));
							if (
								($arRes = $dbRes->Fetch())
								&& (intval($arRes["LOGO"]) > 0)
							)
							{
								$arFileTmp = CFile::ResizeImageGet(
									$arRes["LOGO"],
									array('width' => 100, 'height' => 100),
									BX_RESIZE_IMAGE_EXACT,
									false
								);

								if(
									is_array($arFileTmp)
									&& isset($arFileTmp["src"])
								)
								{
									$strResult .= '<img width="30" height="30" src="'.$arFileTmp['src'].'" alt="">';
								}
							}
						}
						$strResult .= '</span>';
					}

					$arBaloonFields = array(
						'ENTITY_TYPE_ID' => $arCommunication["ENTITY_TYPE_ID"],
						'ENTITY_ID' => $arCommunication["ENTITY_ID"],
						'PREFIX' => "",
						'CLASS_NAME' => 'crm-feed-client-name',
						'CHECK_PERMISSIONS' => 'N'
					);

					if (
						$arCommunication["ENTITY_TYPE_ID"] == CCrmOwnerType::Lead
						&& is_array($arCommunication["ENTITY_SETTINGS"])
					)
					{
						$arBaloonFields["TITLE"] = (isset($arCommunication["ENTITY_SETTINGS"]["LEAD_TITLE"]) ? htmlspecialcharsback($arCommunication["ENTITY_SETTINGS"]["LEAD_TITLE"]) : "");
						$arBaloonFields["NAME"] = (isset($arCommunication["ENTITY_SETTINGS"]["NAME"]) ? htmlspecialcharsback($arCommunication["ENTITY_SETTINGS"]["NAME"]) : "");
						$arBaloonFields["LAST_NAME"] = (isset($arCommunication["ENTITY_SETTINGS"]["LAST_NAME"]) ? htmlspecialcharsback($arCommunication["ENTITY_SETTINGS"]["LAST_NAME"]) : "");
						$arBaloonFields["SECOND_NAME"] = (isset($arCommunication["ENTITY_SETTINGS"]["SECOND_NAME"]) ? htmlspecialcharsback($arCommunication["ENTITY_SETTINGS"]["SECOND_NAME"]) : "");
					}

					$strResult .= '<span class="crm-feed-client-alignment"></span><span class="crm-feed-client-right">';
					$strResult .= '<div>'.CCrmViewHelper::PrepareEntityBaloonHtml($arBaloonFields).'</div>';

					switch ($arCommunication["TYPE"])
					{
						case 'EMAIL':
							$strResult .= '<div><a href="mailto:'.$arCommunication["VALUE"].'" class="crm-feed-client-phone">'.$arCommunication["VALUE"].'</a></div>';
							break;
						case 'PHONE':
							if (CCrmSipHelper::isEnabled())
							{
								ob_start();
								?>
								<script type="text/javascript">
								if (typeof (window.bSipManagerUrlDefined_<?=$arCommunication["ENTITY_TYPE_ID"]?>) === 'undefined')
								{
									window.bSipManagerUrlDefined_<?=$arCommunication["ENTITY_TYPE_ID"]?> = true;
									BX.ready(
										function()
										{
											var mgr = BX.CrmSipManager.getCurrent();
											mgr.setServiceUrl(
												"CRM_<?=CUtil::JSEscape(CCrmOwnerType::ResolveName($arCommunication["ENTITY_TYPE_ID"]))?>",
												"<?=CUtil::JSEscape($this->arSipServiceUrl[$arCommunication["ENTITY_TYPE_ID"]])?>"
											);

											if(typeof(BX.CrmSipManager.messages) === 'undefined')
											{
												BX.CrmSipManager.messages =
												{
													"unknownRecipient": "<?= GetMessageJS('CRM_LF_SIP_MGR_UNKNOWN_RECIPIENT')?>",
													"makeCall": "<?= GetMessageJS('CCRM_LF_SIP_MGR_MAKE_CALL')?>"
												};
											}
										}
									);
								}
								</script>
								<?
								$strResult .= ob_get_clean();
							}

							$strResult .= '<div><span class="crm-feed-num-block">'.CCrmViewHelper::PrepareMultiFieldHtml(
								'PHONE',
								array(
									'VALUE' => $arCommunication["VALUE"],
									'VALUE_TYPE_ID' => 'WORK'
								),
								array(
									'ENABLE_SIP' => true,
									'SIP_PARAMS' => array(
										'ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::ResolveName($arCommunication["ENTITY_TYPE_ID"]),
										'ENTITY_ID' => $arCommunication["ENTITY_ID"],
										'SRC_ACTIVITY_ID' => $this->activity && isset($this->activity['ID']) ? $this->activity["ID"] : 0
									)
								)
							).'</span></div>';

							if(defined("BX_COMP_MANAGED_CACHE"))
							{
								$GLOBALS["CACHE_MANAGER"]->RegisterTag("CRM_CALLTO_SETTINGS");
							}

							break;
					}

					if (is_array($arCommunication["ENTITY_SETTINGS"]) && isset($arCommunication["ENTITY_SETTINGS"]["COMPANY_TITLE"]))
					{
						if (isset($arCommunication["ENTITY_SETTINGS"]["COMPANY_ID"]))
						{
							$strResult .= '<a class="crm-feed-client-company" href="'.CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Company, $arCommunication["ENTITY_SETTINGS"]["COMPANY_ID"]).'">'.$arCommunication["ENTITY_SETTINGS"]["COMPANY_TITLE"].'</a>';
						}
						else
						{
							$strResult .= '<span class="crm-feed-client-company">'.$arCommunication["ENTITY_SETTINGS"]["COMPANY_TITLE"].'</span>';
						}
					}
					else
					{
						$strResult .= '<span class="crm-feed-client-company"></span>';
					}

					$strResult .= '</span>'; // crm-feed-client-right
					$strResult .= '</div>';

					$moreCnt = count($arField["VALUE"]) - 1;
					if ($moreCnt > 0)
					{
						$strResult .= "#clients_more_link#";
					}

					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}

				break;
			case "AVATAR_ID":
				if (intval($arField["VALUE"]) > 0)
				{
					$arFileTmp = CFile::ResizeImageGet(
						$arField["VALUE"],
						array('width' => $this->params["AVATAR_SIZE"], 'height' => $this->params["AVATAR_SIZE"]),
						BX_RESIZE_IMAGE_EXACT,
						false
					);

					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= '<img src="'.$arFileTmp["src"].'" border="0" alt="'.$this->params["AVATAR_SIZE"].'" width="" height="'.$this->params["AVATAR_SIZE"].'">';
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "SUM":
				if (intval($arField["VALUE"]["VALUE"]) > 0)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= '<span class="crm-feed-info-sum">'.CCrmCurrency::MoneyToString($arField["VALUE"]["VALUE"], $arField["VALUE"]["CURRENCY"]).'</span>';
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "PHONE":
			case "EMAIL":
				if (!empty($arField["VALUE"]))
				{
					$infos = CCrmFieldMulti::GetEntityTypes();

					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= CCrmViewHelper::PrepareFirstMultiFieldHtml(
						$arField["FORMAT"],
						$arField["VALUE"],
						$infos[$arField["FORMAT"]]
					);

					if(
						count($arField["VALUE"]) > 1
						|| (!empty($arField["VALUE"]["WORK"]) && count($arField["VALUE"]["WORK"]) > 1)
						|| (!empty($arField["VALUE"]["MOBILE"]) && count($arField["VALUE"]["MOBILE"]) > 1)
						|| (!empty($arField["VALUE"]["FAX"]) && count($arField["VALUE"]["FAX"]) > 1)
						|| (!empty($arField["VALUE"]["PAGER"]) && count($arField["VALUE"]["PAGER"]) > 1)
						|| (!empty($arField["VALUE"]["OTHER"]) && count($arField["VALUE"]["OTHER"]) > 1)
					)
					{
						$anchorID = mb_strtolower($arField["FORMAT"]);
						$strResult .= '<span style="margin-left: 10px;" class="crm-client-contacts-block-text-list-icon" id="'.htmlspecialcharsbx($anchorID).'"'.' onclick="'.CCrmViewHelper::PrepareMultiFieldValuesPopup($anchorID, $anchorID, $arField["FORMAT"], $arField["VALUE"], $infos[$arField["FORMAT"]]).'"></span>';
					}
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";

					if (
						$arField["FORMAT"] === "PHONE"
						&& defined("BX_COMP_MANAGED_CACHE")
					)
					{
						$GLOBALS["CACHE_MANAGER"]->RegisterTag("CRM_CALLTO_SETTINGS");
					}
				}
				break;
			case "TEXT_FORMATTED":
			case "TEXT_FORMATTED_BOLD":
				if ($arField["VALUE"] != CCrmLiveFeed::UntitledMessageStub)
				{
					$text_formatted = self::ParseText(htmlspecialcharsback($arField["VALUE"]), $arUF, $this->params);
					if ($text_formatted <> '')
					{
						$strResult .= "#row_begin#";
						$strResult .= "#cell_begin_colspan2#";
						if ($arField["FORMAT"] === "TEXT_FORMATTED_BOLD")
						{
							$strResult .=  "<b>".$text_formatted."</b>";
						}
						else
						{
							$strResult .=  $text_formatted;
						}
						$strResult .= "#cell_end#";
						$strResult .= "#row_end#";
					}
				}

				break;
			case "COMBI_TITLE":
				if (
					is_array($arField["VALUE"])
					&& array_key_exists("TITLE", $arField["VALUE"]) && $arField["VALUE"]["TITLE"] <> ''
				)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';

					if (array_key_exists("URL", $arField["VALUE"]) && $arField["VALUE"]["URL"] <> '')
					{
						$strResult .= '<a href="'.$arField["VALUE"]["URL"].'">'.$arField["VALUE"]["TITLE"].'</a>';
					}
					elseif (array_key_exists("ONCLICK", $arField["VALUE"]) && $arField["VALUE"]["ONCLICK"] <> '')
					{
						$strResult .= '<a href="javascript:void(0)" onclick="'.$arField["VALUE"]["ONCLICK"].'">'.$arField["VALUE"]["TITLE"].'</a>';
					}
					else
					{
						$strResult .= $arField["VALUE"]["TITLE"];
					}

					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}

				break;
			case "COMBI_TITLE_ID":
				if (
					is_array($arField["VALUE"])
					&& array_key_exists("TITLE", $arField["VALUE"]) && $arField["VALUE"]["TITLE"] <> ''
					&& array_key_exists("ID", $arField["VALUE"]) && $arField["VALUE"]["ID"] <> ''
				)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';

					if (array_key_exists("URL", $arField["VALUE"]) && $arField["VALUE"]["URL"] <> '')
					{
						$strResult .= '<a href="'.$arField["VALUE"]["URL"].'">'.GetMessage("C_CRM_LF_COMBI_TITLE_ID_VALUE", array("#ID#" => $arField["VALUE"]["ID"], "#TITLE#" => $arField["VALUE"]["TITLE"])).'</a>';
					}
					else
					{
						$strResult .= GetMessage("C_CRM_LF_COMBI_TITLE_ID_VALUE", array("#ID#" => $arField["VALUE"]["ID"], "#TITLE#" => $arField["VALUE"]["TITLE"]));
					}

					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}

				break;
			case "TEXT_ADD":
				if ($arField["VALUE"] <> '')
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= '<span class="crm-feed-info-name">'.$arField["VALUE"].'</span>';
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "TEXT":
			default:
				if ($arField["VALUE"] <> '')
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= $arField["VALUE"];
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
		}

		return $strResult;
	}

	public function formatFields()
	{
		$arReturn = array();

		if ($this->entityTypeID == CCrmOwnerType::Activity)
		{
			foreach($this->eventMeta[$this->activity["TYPE_ID"]] as $key => $arValue)
			{
				$arReturn[$key] = $this->formatField($key, $arValue);
			}
		}
		else
		{
			foreach($this->eventMeta[$this->fields["EVENT_ID"]] as $key => $arValue)
			{
				$arReturn[$key] = $this->formatField($key, $arValue);
			}
		}

		return $arReturn;
	}

	private function formatField($key, $arValue)
	{
		switch($key)
		{
			case "ADD_TITLE":
				$title = GetMessage("C_CRM_LF_".$this->fields["ENTITY_TYPE"]."_ADD_TITLE");
				break;
			default:
				$key1 = (
					$this->fields['ENTITY_TYPE'] == CCrmLiveFeedEntity::Deal
					&& in_array($key, array("STATUS", "FINAL_STATUS_ID", "START_STATUS_ID"))
						? "DEAL_".$key
						: $key
				);
				$title = GetMessage("C_CRM_LF_".$key1."_TITLE");
		}

		$value = $this->getValue($arValue["CODE"]);

		return array(
			"TITLE" => $title,
			"FORMAT" => $arValue["FORMAT"],
			"VALUE" => $value
		);
	}

	private function getValue($value_code)
	{
		if (!is_array($value_code))
		{
			if (mb_strpos($value_code, "COMBI_") === 0)
			{
				$arFieldName = explode("/", mb_substr($value_code, 6));
				if (is_array($arFieldName))
				{
					$arReturn = array();

					foreach($arFieldName as $fieldName)
					{
						if (mb_strpos($fieldName, "EVENT_PARAMS_") === 0)
						{
							$key = mb_substr($fieldName, 13);
						}
						elseif (mb_strpos($fieldName, "ACTIVITY_") === 0)
						{
							$key = mb_substr($fieldName, 9);
						}
						elseif (mb_strpos($fieldName, "INVOICE_") === 0)
						{
							$key = mb_substr($fieldName, 8);
						}
						else
						{
							$key = $fieldName;
						}

						if (mb_strpos($key, "CONTACT_ID") !== false)
						{
							$key = "CONTACT_ID";
						}
						elseif (mb_strpos($key, "LAST_NAME") !== false)
						{
							$key = "CONTACT_LAST_NAME";
						}
						elseif (mb_strpos($key, "SECOND_NAME") !== false)
						{
							$key = "CONTACT_SECOND_NAME";
						}
						elseif (mb_strpos($key, "NAME") !== false)
						{
							$key = "CONTACT_NAME";
						}
						elseif (mb_strpos($key, "COMPANY_TITLE") !== false)
						{
							$key = "COMPANY_TITLE";
						}
						elseif (mb_strpos($key, "COMPANY_ID") !== false)
						{
							$key = "COMPANY_ID";
						}
						elseif (
							mb_strpos($key, "TITLE") !== false
							|| mb_strpos($key, "ORDER_TOPIC") !== false
							|| mb_strpos($key, "SUBJECT") !== false
						)
						{
							$key = "TITLE";
						}
						elseif (mb_strpos($key, "ENTITY_ID") !== false)
						{
							$key = "ENTITY_ID";
						}
						elseif (mb_strpos($key, "PHOTO_ID") !== false)
						{
							$key = "PHOTO_ID";
						}
						elseif (mb_strpos($key, "LOGO_ID") !== false)
						{
							$key = "LOGO_ID";
						}
						elseif (mb_strpos($key, "START_STATUS_ID") !== false)
						{
							$key = "STAGE_ID";
						}
						elseif (mb_strpos($key, "FINAL_STATUS_ID") !== false)
						{
							$key = "STAGE_ID";
						}
						elseif (mb_strpos($key, "STAGE_ID") !== false)
						{
							$key = "STAGE_ID";
						}
						elseif (mb_strpos($key, "CATEGORY_ID") !== false)
						{
							$key = "CATEGORY_ID";
						}
						elseif (mb_strpos($key, "ID") !== false)
						{
							$key = "ID";
						}
						elseif ($key === "ACCOUNT_NUMBER")
						{
							$key = "ID";
						}

						$arReturn[$key] = $this->getValue($fieldName);
					}

					return $arReturn;
				}
			}
			elseif (mb_strpos($value_code, "EVENT_PARAMS_") === 0)
			{
				if (is_array($this->eventParams[mb_substr($value_code, 13)]))
				{
					array_walk($this->eventParams[mb_substr($value_code, 13)], array($this, '__htmlspecialcharsbx'));
					return $this->eventParams[mb_substr($value_code, 13)];
				}

				if (array_key_exists(mb_substr($value_code, 13), $this->eventParams))
				{
					return htmlspecialcharsbx($this->eventParams[mb_substr($value_code, 13)]);
				}

				return '';
			}
			elseif (mb_strpos($value_code, "ACTIVITY_ONCLICK") === 0)
			{
				return "BX.CrmActivityEditor.viewActivity('livefeed', ".$this->activity["ID"].", { 'enableInstantEdit':true, 'enableEditButton':true });";
			}
			elseif (mb_strpos($value_code, "ACTIVITY_") === 0)
			{
				$realKey = mb_substr($value_code, 9);

				if (is_array($this->activity[$realKey]))
				{
					array_walk($this->activity[$realKey], array($this, '__htmlspecialcharsbx'));
					return $this->activity[$realKey];
				}

				if (
					empty($this->activity[$realKey])
					&& $realKey === "SUBJECT"
				)
				{
					return GetMessage('C_CRM_LF_SUBJECT_TITLE_EMPTY');
				}

				if ($realKey === 'LOCATION' && Loader::includeModule('calendar'))
				{
					return htmlspecialcharsbx(CCalendar::GetTextLocation($this->activity[$realKey]));
				}

				return htmlspecialcharsbx($this->activity[$realKey]);
			}
			elseif (mb_strpos($value_code, "INVOICE_") === 0)
			{
				if (is_array($this->activity[mb_substr($value_code, 9)]))
				{
					array_walk($this->invoice[mb_substr($value_code, 8)], array($this, '__htmlspecialcharsbx'));
					return $this->invoice[mb_substr($value_code, 8)];
				}

				return htmlspecialcharsbx($this->invoice[mb_substr($value_code, 8)]);
			}
			else
			{
				if (is_array($this->activity[mb_substr($value_code, 9)]))
				{
					array_walk($this->fields[$value_code], array($this, '__htmlspecialcharsbx'));
					return $this->fields[$value_code];
				}

				return htmlspecialcharsbx($this->fields[$value_code]);
			}
		}
		else
		{
			$arReturn = array();
			foreach ($value_code as $key_tmp => $value_tmp)
			{
				$arReturn[$key_tmp] = $this->getValue($value_tmp);
			}
			return $arReturn;
		}
	}

	public static function ParseText($text, $arUF, $arParams)
	{
		static $parser = false;

		if (
			isset($arParams["NOTIFY"])
			&& $arParams["NOTIFY"] === "Y"
		)
		{
			$text = str_replace("\n", "", CTextParser::clearAllTags($text));
		}

		if (CModule::IncludeModule("forum"))
		{
			if (!$parser)
			{
				$parser = new forumTextParser(LANGUAGE_ID);
			}

			$parser->pathToUser = $arParams["PATH_TO_USER"];
			$parser->arUserfields = $arUF;
			$textFormatted = $parser->convert(
				$text,
				array(
					"HTML" => "N",
					"ALIGN" => "Y",
					"ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "QUOTE" => "Y",
					"CODE" => "Y", "FONT" => "Y",
					"LIST" => "Y", "SMILES" => "Y",
					"NL2BR" => "Y", "MULTIPLE_BR" => "N",
					"VIDEO" => "Y", "LOG_VIDEO" => "N",
					"SHORT_ANCHOR" => "Y",
					"USERFIELDS" => $arUF,
					"USER" => (
						isset($arParams["NOTIFY"])
						&& $arParams["NOTIFY"] === "Y"
							? "N"
							: "Y"
					)
				),
				"html"
			);
		}
		else
		{
			$parser = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
			$textFormatted = $parser->convert(
				$text,
				array(),
				array(
					"HTML" => "N",
					"ALIGN" => "Y",
					"ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "QUOTE" => "Y",
					"CODE" => "Y", "FONT" => "Y",
					"LIST" => "Y", "SMILES" => "Y",
					"NL2BR" => "Y", "MULTIPLE_BR" => "N",
					"VIDEO" => "Y", "LOG_VIDEO" => "N",
					"SHORT_ANCHOR" => "Y",
					"USERFIELDS" => $arUF
				)
			);
		}

		if (
			isset($arParams["MAX_LENGTH"])
			&& intval($arParams["MAX_LENGTH"]) > 0
		)
		{
			$textFormatted = $parser->html_cut($textFormatted, $arParams["MAX_LENGTH"]);
		}
		return $textFormatted;
	}

	private function __htmlspecialcharsbx(&$val, $key)
	{
		if (is_array($val))
		{
			array_walk($val, array($this, '__htmlspecialcharsbx'));
		}
		else
		{
			$val = htmlspecialcharsbx($val);
		}
	}

	public static function ProcessLogEventEditPOST($arPOST, $entityTypeID, $entityID, &$arResult, $arUfCode = array())
	{
		global $USER_FIELD_MANAGER;

		$arEntityData = array();
		$errors = array();

		$enableTitle = isset($arPOST['ENABLE_POST_TITLE']) && mb_strtoupper($arPOST['ENABLE_POST_TITLE']) === 'Y';
		$title = $enableTitle && isset($arPOST['POST_TITLE']) ? $arPOST['POST_TITLE'] : '';
		$message = isset($arPOST['MESSAGE']) ? htmlspecialcharsback($arPOST['MESSAGE']) : '';

		$arResult['EVENT']['MESSAGE'] = $message;
		$arResult['EVENT']['TITLE'] = $title;
		$arResult['ENABLE_TITLE'] = $enableTitle;

		$attachedFiles = array();
		$webDavFileFieldName = $arResult['WEB_DAV_FILE_FIELD_NAME'];

		if($webDavFileFieldName !== '')
		{
			if (
				!isset($arPOST[$webDavFileFieldName])
				&& isset($GLOBALS[$webDavFileFieldName])
				&& is_array($GLOBALS[$webDavFileFieldName])
			)
			{
				$arPOST[$webDavFileFieldName] = $GLOBALS[$webDavFileFieldName];
			}

			if (
				isset($arPOST[$webDavFileFieldName])
				&& is_array($arPOST[$webDavFileFieldName])
			)
			{
				foreach($arPOST[$webDavFileFieldName] as $fileID)
				{
					if($fileID === '')
					{
						continue;
					}

					//fileID:  "888|165|16"
					$attachedFiles[] = $fileID;
				}

				if(
					!empty($attachedFiles)
					&& is_array($arResult['WEB_DAV_FILE_FIELD'])
				)
				{
					$arResult['WEB_DAV_FILE_FIELD']['VALUE'] = $attachedFiles;
				}
			}
		}

		$allowToAll = \Bitrix\Socialnetwork\ComponentHelper::getAllowToAllDestination($arResult['USER_ID']);

		$arSocnetRights = array();
		$arUserIdToMail = array();
		$bMailModuleInstalled = IsModuleInstalled('mail');
		$arCrmEmailEntities = array();

		\Bitrix\Socialnetwork\ComponentHelper::convertSelectorRequestData($arPOST, [
			'crm' => true,
		]);

		self::ProcessLogEventEditPOSTCrmEmailUsers($arPOST, $arCrmEmailEntities);

		if(!empty($arPOST['SPERM']))
		{
			foreach($arPOST['SPERM'] as $v => $k)
			{
				if($v <> '' && is_array($k) && !empty($k))
				{
					foreach($k as $vv)
					{
						if($vv <> '')
						{
							$arSocnetRights[] = $vv;
						}
					}
				}
			}
		}

		if (in_array('UA', $arSocnetRights) && !$allowToAll)
		{
			foreach ($arSocnetRights as $key => $value)
			{
				if ($value === 'UA')
				{
					unset($arSocnetRights[$key]);
					break;
				}
			}
		}

		foreach ($arSocnetRights as $key => $value)
		{
			if ($value === 'UA')
			{
				$arSocnetRights[] = 'AU';
				unset($arSocnetRights[$key]);
				break;
			}
		}
		$arSocnetRights = array_unique($arSocnetRights);

		$allFeedEtityTypes = CCrmLiveFeedEntity::GetAll();
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		foreach ($arSocnetRights as $key => $value)
		{
			$groupCodeData = array();
			if (
				CCrmLiveFeed::TryParseGroupCode($value, $groupCodeData)
				&& in_array($groupCodeData['ENTITY_TYPE'], $allFeedEtityTypes, true)
			)
			{
				$groupCodeEntityType = $groupCodeData['ENTITY_TYPE'];
				$groupCodeEntityID = $groupCodeData['ENTITY_ID'];

				if(!CCrmLiveFeed::CheckCreatePermission($groupCodeEntityType, $groupCodeEntityID, $userPerms))
				{
					$canonicalEntityTypeID = CCrmLiveFeedEntity::ResolveEntityTypeID($groupCodeEntityType);
					$errors[] = GetMessage(
						'CRM_SL_EVENT_EDIT_PERMISSION_DENIED',
						array('#TITLE#' => CCrmOwnerType::GetCaption($canonicalEntityTypeID, $groupCodeEntityID, false))
					);
				}
				else
				{
					$arEntityData[] = array('ENTITY_TYPE' => $groupCodeEntityType, 'ENTITY_ID' => $groupCodeEntityID);
				}
			}
			else
			{
				if (preg_match('/^SG(\d+)$/', $value, $matches))
				{
					$arResult["FEED_DESTINATION"]['SELECTED']['SG'.$matches[1]] = 'sonetgroups';
				}
				elseif (preg_match('/^DR(\d+)$/', $value, $matches))
				{
					$arResult["FEED_DESTINATION"]['SELECTED']['DR'.$matches[1]] = 'department';
				}
				elseif (preg_match('/^U(\d+)$/', $value, $matches))
				{
					$arResult["FEED_DESTINATION"]['SELECTED']['U'.$matches[1]] = 'users';
				}
			}

			if (
				$bMailModuleInstalled
				&& preg_match('/^U(\d+)$/i', $value, $matches)
			)
			{
				$arUserIdToMail[] = intval($matches[1]);
			}
		}

		if(!(CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0) && !empty($arEntityData))
		{
			$entityData = $arEntityData[0];
			$entityTypeID = CCrmLiveFeedEntity::ResolveEntityTypeID($entityData['ENTITY_TYPE']);
			$entityID = $entityData['ENTITY_ID'];
		}

		if(!empty($arEntityData))
		{
			$arResult['ENTITY_DATA'] = $arEntityData;
		}

		if(!(CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0))
		{
			$errors[] = GetMessage('CRM_SL_EVENT_EDIT_ENTITY_NOT_DEFINED');
		}

		if($message === '')
		{
			$errors[] = GetMessage('CRM_SL_EVENT_EDIT_EMPTY_MESSAGE');
		}

		if(empty($errors))
		{
			$fields = array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID,
				'USER_ID' => $arResult['USER_ID'],
				'TITLE' => $title,
				'MESSAGE' => $message,
				'RIGHTS' => $arSocnetRights
			);

			$parents = array();
			CCrmOwnerType::TryGetOwnerInfos($entityTypeID, $entityID, $parents, array('ENABLE_MAPPING' => true));
			foreach($arEntityData as $entityData)
			{
				$curEntityTypeID = CCrmLiveFeedEntity::ResolveEntityTypeID($entityData['ENTITY_TYPE']);
				$curEntityID = $entityData['ENTITY_ID'];
				$entityKey = "{$curEntityTypeID}_{$curEntityID}";

				if(!isset($parents[$entityKey]) && !($curEntityTypeID === $entityTypeID && $curEntityID === $entityID))
				{
					$parents[$entityKey] = array('ENTITY_TYPE_ID' => $curEntityTypeID, 'ENTITY_ID' => $curEntityID);
				}
			}

			if(!empty($parents))
			{
				$fields['PARENTS'] = array_values($parents);
			}

			if(!empty($attachedFiles))
			{
				$fields['WEB_DAV_FILES'] = array($webDavFileFieldName => $attachedFiles);
			}

			$fields['UF'] = array();
			if (
				is_array($arUfCode)
				&& !empty($arUfCode)
			)
			{
				$arTmp = array();
				$USER_FIELD_MANAGER->EditFormAddFields("SONET_LOG", $arTmp);

				foreach ($arTmp as $key => $value)
				{
					if (in_array($key, $arUfCode))
					{
						$fields['UF'][$key] = $value;
					}
				}
			}

			$messageID = CCrmLiveFeed::CreateLogMessage($fields);
			if(!(is_int($messageID) && $messageID > 0))
			{
				$errors[] = isset($fields['ERROR']) ? $fields['ERROR'] : 'UNKNOWN ERROR';
			}
			else
			{
				if (
					!empty($arUserIdToMail)
					&& CModule::IncludeModule('socialnetwork')
				)
				{
					$arUserIdToMail = array_unique($arUserIdToMail);
					\Bitrix\Socialnetwork\Util::notifyMail(array(
						"type" => "LOG_ENTRY",
						"siteId" => SITE_ID,
						"userId" => $arUserIdToMail,
						"authorId" => $arResult['USER_ID'],
						"logEntryId" => $messageID,
						"logEntryUrl" => CComponentEngine::MakePathFromTemplate(
							'/pub/log_entry.php?log_id=#log_id#',
							array(
								"log_id"=> $messageID
							)
						)
					));
				}

				$arUsers = $arMention = array();

				if (IsModuleInstalled("im"))
				{
					$arUserIDSent = array();

					// send recipients notifications
					if (!empty($arSocnetRights))
					{
						foreach($arSocnetRights as $v)
						{
							if (mb_substr($v, 0, 1) === "U")
							{
								$u = (int)mb_substr($v, 1);
								if (
									$u > 0
									&& !in_array($u, $arUsers)
									&& $u != $arResult['USER_ID']
								)
								{
									$arUsers[] = $u;
								}
							}
						}
					}

					//  send mention notifications
					preg_match_all("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/is".BX_UTF_PCRE_MODIFIER, $message, $arMention);
					if (
						!empty($arMention)
						&& !empty($arMention[1])
					)
					{
						$arMention = $arMention[1];
						$arMention = array_unique($arMention);
					}
					else
					{
						$arMention = array();
					}
				}

				if (
					(
						!empty($arUsers)
						|| !empty($arMention)
					)
					&& CModule::IncludeModule("im")
				)
				{
					$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && SITE_SERVER_NAME <> '') ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

					$strIMMessageTitle = str_replace(Array("\r\n", "\n"), " ", ($title <> '' ? $title : $message));

					if (CModule::IncludeModule("blog"))
					{
						$strIMMessageTitle = trim(blogTextParser::killAllTags($strIMMessageTitle));
					}
					$strIMMessageTitle = TruncateText($strIMMessageTitle, 100);
					$strIMMessageTitleOut = TruncateText($strIMMessageTitle, 255);

					$strLogEntryURL = COption::GetOptionString("socialnetwork", "log_entry_page", SITE_DIR."company/personal/log/#log_id#/", SITE_ID);
					$strLogEntryURL = CComponentEngine::MakePathFromTemplate(
						$strLogEntryURL,
						array(
							"log_id" => $messageID
						)
					);

					$strLogEntryCrmURL = CComponentEngine::MakePathFromTemplate(
						SITE_DIR."crm/stream/?log_id=#log_id#",
						array(
							"log_id" => $messageID
						)
					);

					$url = $strLogEntryURL;

					$genderSuffix = "";
					$dbUser = CUser::GetByID($arResult['USER_ID']);

					if($arUser = $dbUser->Fetch())
					{
						switch ($arUser["PERSONAL_GENDER"])
						{
							case "M":
								$genderSuffix = "_M";
								break;
							case "F":
								$genderSuffix = "_F";
								break;
							default:
								$genderSuffix = "";
						}
					}

					if (!empty($arUsers))
					{
						foreach($arUsers as $val)
						{
							$arMessageFields = array(
								"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
								"TO_USER_ID" => "",
								"FROM_USER_ID" => $arResult['USER_ID'],
								"NOTIFY_TYPE" => IM_NOTIFY_FROM,
								"NOTIFY_MODULE" => "crm",
								"NOTIFY_EVENT" => "post"
							);

							$arMessageFields["TO_USER_ID"] = $val;
							$arMessageFields["NOTIFY_TAG"] = "CRM|POST|".$messageID;
							$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
								"CRM_LF_EVENT_IM_POST".$genderSuffix,
								array(
									"#title#" => "<a href=\"".$strLogEntryCrmURL."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($strIMMessageTitle)."</a>"
								)
							);
							$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
								"CRM_LF_EVENT_IM_POST".$genderSuffix,
								array(
									"#title#" => htmlspecialcharsbx($strIMMessageTitleOut)
								)
							)." (".$serverName.$strLogEntryCrmURL.")";

							CIMNotify::Add($arMessageFields);
						}
					}

					if (!empty($arMention))
					{
						$arMessageFields = array(
							"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
							"TO_USER_ID" => "",
							"FROM_USER_ID" => $arResult['USER_ID'],
							"NOTIFY_TYPE" => IM_NOTIFY_FROM,
							"NOTIFY_MODULE" => "crm",
							"NOTIFY_EVENT" => "mention"
						);

						foreach($arMention as $val)
						{
							$val = intval($val);
							if (
								$val > 0
								&& ($val != $arResult['USER_ID'])
							)
							{
								$bHasAccess = false;
/*
commented in http://jabber.bx/view.php?id=0063797

								if (in_array('U'.$val, $arSocnetRights))
								{
									$url = $strLogEntryURL;
									$bHasAccess = true;
								}

								if (!$bHasAccess)
								{
									$arAccessCodes = array();
									$dbAccess = CAccess::GetUserCodes($val);
									while($arAccess = $dbAccess->Fetch())
									{
										$arAccessCodes[] = $arAccess["ACCESS_CODE"];
									}

									$arTmp = array_intersect($arAccessCodes, $arSocnetRights);
									if (!empty($arTmp))
									{
										$url = $strLogEntryURL;
										$bHasAccess = true;
									}
								}
*/
								if (!$bHasAccess)
								{
									$userPermissions = CCrmPerms::GetUserPermissions($val);
									foreach($arEntityData as $arEntity)
									{
										if (CCrmAuthorizationHelper::CheckReadPermission(CCrmOwnerType::ResolveName(CCrmLiveFeedEntity::ResolveEntityTypeID($arEntity['ENTITY_TYPE'])), $arEntity['ENTITY_ID'], $userPermissions))
										{
											$url = $strLogEntryCrmURL;
											$bHasAccess = true;
											break;
										}
									}
								}

								if ($bHasAccess)
								{
									$arMessageFields["TO_USER_ID"] = $val;
									$arMessageFields["NOTIFY_TAG"] = "CRM|MESSAGE_MENTION|".$messageID;
									$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
										"CRM_SL_EVENT_IM_MENTION_POST".$genderSuffix,
										array(
											"#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($strIMMessageTitle)."</a>"
										)
									);
									$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
										"CRM_SL_EVENT_IM_MENTION_POST".$genderSuffix,
										array(
											"#title#" => htmlspecialcharsbx($strIMMessageTitleOut)
										)
									)." (".$serverName.$url.")";

									CIMNotify::Add($arMessageFields);
								}
							}
						}
					}
				}

				if (!empty($arMention))
				{
					$arMentionedDestCode = array();
					foreach($arMention as $val)
					{
						$arMentionedDestCode[] = "U".$val;
					}

					\Bitrix\Main\FinderDestTable::merge(array(
						"CONTEXT" => "mention",
						"CODE" => array_unique($arMentionedDestCode)
					));
				}

				if (
					!empty($arCrmEmailEntities)
					&& (
						!empty($arCrmEmailEntities[CCrmLiveFeedEntity::Contact])
						|| !empty($arCrmEmailEntities[CCrmLiveFeedEntity::Company])
						|| !empty($arCrmEmailEntities[CCrmLiveFeedEntity::Lead])
					)
				)
				{
					$communications = $bindings = array();

					foreach(\CCrmLiveFeedEntity::GetAll() as $type)
					{
						if (
							!empty($arCrmEmailEntities[$type])
							&& is_array($arCrmEmailEntities[$type])
						)
						{
							foreach($arCrmEmailEntities[$type] as $crmEmailEntity)
							{
								$bindings = self::mergeBindings($bindings, array(array(
									'OWNER_ID' => intval($crmEmailEntity["ID"]),
									'OWNER_TYPE_ID' => \CCrmLiveFeedEntity::resolveEntityTypeID($type)
								)));

								$communications = self::mergeCommunications($communications, array(array(
									'ID' => 0,
									'TYPE' => 'EMAIL',
									'VALUE' => $crmEmailEntity["EMAIL"],
									'ENTITY_ID' => intval($crmEmailEntity["ID"]),
									'ENTITY_TYPE_ID' => \CCrmLiveFeedEntity::resolveEntityTypeID($type)
								)));
							}
						}
					}

					if (
						!empty($bindings)
						&& !empty($communications)
					)
					{
						\Bitrix\Crm\Activity\Provider\Livefeed::addActivity(array(
							"TYPE" => "ENTRY",
							"COMMUNICATIONS" => $communications,
							"BINDINGS" => $bindings,
							"TITLE" => $title,
							"MESSAGE" => $message,
							"USER_ID" => $arResult['USER_ID'],
							"ENTITY_ID" => $messageID
						));
					}
				}

				return $messageID;
			}
		}

		return $errors;
	}

	public static function ProcessLogEventEditPOSTCrmEmailUsers(&$arPOST, &$arCrmEmailEntities = array())
	{
		$arResult = array();

		if (
			isset($arPOST['SPERM'])
			&& isset($arPOST['SPERM']['UE'])
			&& is_array($arPOST['SPERM']['UE'])
			&& CModule::IncludeModule('mail')
		)
		{
			foreach ($arPOST['SPERM']['UE'] as $key => $userEmail)
			{
				if (!check_email($userEmail))
				{
					continue;
				}

				$bFound = false;

				if (
					isset($arPOST["INVITED_USER_CRM_ENTITY"])
					&& !empty($arPOST["INVITED_USER_CRM_ENTITY"][$userEmail])
				)
				{
					$arFilter = array(
						array(
							'LOGIC' => 'OR',
							'=EMAIL' => $userEmail,
							'UF_USER_CRM_ENTITY' => $arPOST["INVITED_USER_CRM_ENTITY"][$userEmail]
						)
					);
				}
				else
				{
					$arFilter = array(
						'=EMAIL' => $userEmail,
					);
				}

				$rsUser = \Bitrix\Main\UserTable::getList(array(
					'order' => array('ID' => 'ASC'),
					'filter' => $arFilter,
					'select' => array('ID', 'UF_USER_CRM_ENTITY')
				));

				while ($arEmailUser = $rsUser->fetch())
				{
					if (intval($arEmailUser["ID"]) > 0)
					{
						$arPOST["SPERM"]["U"][] = "U".$arEmailUser["ID"];

						if (!empty($arEmailUser["UF_USER_CRM_ENTITY"]))
						{
							$res = self::resolveLFEntityFromUF($arEmailUser["UF_USER_CRM_ENTITY"]);
							if (!empty($res))
							{
								[$k, $v] = $res;

								if ($k && $v)
								{
									if (!isset($arPOST["SPERM"][$k]))
									{
										$arPOST["SPERM"][$k] = array();
									}
									$arPOST["SPERM"][$k][] = $k.$v;

									if (!isset($arCrmEmailEntities[$k]))
									{
										$arCrmEmailEntities[$k] = array();
									}
									$arCrmEmailEntities[$k][] = array(
										"ID" => $v,
										"EMAIL" => $userEmail
									);
								}
							}
						}
						$bFound = true;
					}
				}

				if ($bFound)
				{
					continue;
				}

				$userFields = array(
					'EMAIL' => $userEmail,
					'NAME' => (
						isset($arPOST["INVITED_USER_NAME"])
						&& isset($arPOST["INVITED_USER_NAME"][$userEmail])
							? $arPOST["INVITED_USER_NAME"][$userEmail]
							: ''
					),
					'LAST_NAME' => (
						isset($arPOST["INVITED_USER_LAST_NAME"])
						&& isset($arPOST["INVITED_USER_LAST_NAME"][$userEmail])
							? $arPOST["INVITED_USER_LAST_NAME"][$userEmail]
							: ''
					),
					'UF' => array(
						'UF_USER_CRM_ENTITY' => $arPOST["INVITED_USER_CRM_ENTITY"][$userEmail]
					)
				);

				$res = self::resolveLFEntityFromUF($arPOST["INVITED_USER_CRM_ENTITY"][$userEmail]);
				if (!empty($res))
				{
					[$k, $v] = $res;
					if ($k && $v)
					{
						if (
							$k == CCrmLiveFeedEntity::Contact
							&& ($contact = \CCrmContact::GetByID($v))
							&& intval($contact['PHOTO']) > 0
						)
						{
							$userFields['PERSONAL_PHOTO_ID'] = intval($contact['PHOTO']);
						}
					}
				}

				// invite email user by email
				$invitedUserId = \Bitrix\Mail\User::create($userFields);

				if (
					intval($invitedUserId) <= 0
					&& $invitedUserId->LAST_ERROR <> ''
				)
				{
					$strError = $invitedUserId->LAST_ERROR;
				}

				if (
					!$strError
					&& intval($invitedUserId) > 0
				)
				{
					if (!isset($arPOST["SPERM"]["U"]))
					{
						$arPOST["SPERM"]["U"] = array();
					}
					$arPOST["SPERM"]["U"][] = "U".$invitedUserId;

					$res = self::resolveLFEntityFromUF($arPOST["INVITED_USER_CRM_ENTITY"][$userEmail]);
					if (!empty($res))
					{
						[$k, $v] = $res;

						if ($k && $v)
						{
							if (!isset($arPOST["SPERM"][$k]))
							{
								$arPOST["SPERM"][$k] = array();
							}
							$arPOST["SPERM"][$k][] = $k.$v;

							if (!isset($arCrmEmailEntities[$k]))
							{
								$arCrmEmailEntities[$k] = array();
							}
							$arCrmEmailEntities[$k][] = array(
								"ID" => $v,
								"EMAIL" => $userEmail
							);
						}
					}

					if (Loader::includeModule('intranet') && class_exists('\Bitrix\Intranet\Integration\Mail\EmailUser'))
					{
						\Bitrix\Intranet\Integration\Mail\EmailUser::invite($invitedUserId);
					}
				}
				else
				{
					$arResult["ERROR_MESSAGE"] .= $strError;
				}
			}
			unset($arPOST["SPERM"]["UE"]);
		}

		if (
			isset($arPOST['SPERM'])
			&& isset($arPOST['SPERM']['U'])
			&& is_array($arPOST['SPERM']['U'])
		)
		{
			$arUserId = array();
			foreach ($arPOST['SPERM']['U'] as $key => $code)
			{
				if (preg_match('/^U(\d+)$/', $code, $matches))
				{
					$arUserId[] = intval($matches[1]);
				}
			}
			if (!empty($arUserId))
			{
				$rsUser = \Bitrix\Main\UserTable::getList(array(
					'order' => array(),
					'filter' => array(
						'ID' => $arUserId
					),
					'select' => array('ID', 'EMAIL', 'UF_USER_CRM_ENTITY')
				));

				while ($arEmailUser = $rsUser->fetch())
				{
					if (!empty($arEmailUser['UF_USER_CRM_ENTITY']))
					{
						$res = self::resolveLFEntityFromUF($arEmailUser["UF_USER_CRM_ENTITY"]);
						if (!empty($res))
						{
							[$k, $v] = $res;

							if ($k && $v)
							{
								if (!isset($arPOST["SPERM"][$k]))
								{
									$arPOST["SPERM"][$k] = array();
								}
								$arPOST["SPERM"][$k][] = $k.$v;

								if (!isset($arCrmEmailEntities[$k]))
								{
									$arCrmEmailEntities[$k] = array();
								}
								$arCrmEmailEntities[$k][] = array(
									"ID" => $v,
									"EMAIL" => $arEmailUser['EMAIL']
								);
							}
						}
					}
				}
			}
		}

		return $arResult;
	}

	/**
	 * @deprecated
	 * use
	 * CCrmLiveFeedComponent::resolveLFEntutyFromUF($ufValue)
	 */
	public static function resolveLFEntutyFromUF($ufValue)
	{
		return self::resolveLFEntityFromUF($ufValue);
	}

	public static function resolveLFEntityFromUF($ufValue)
	{
		$result = false;

		$k = $v = false;
		if (preg_match('/^C_(\d+)$/', $ufValue, $matches))
		{
			$k = CCrmLiveFeedEntity::Contact;
			$v = $matches[1];
		}
		elseif (preg_match('/^CO_(\d+)$/', $ufValue, $matches))
		{
			$k = CCrmLiveFeedEntity::Company;
			$v = $matches[1];
		}
		elseif (preg_match('/^L_(\d+)$/', $ufValue, $matches))
		{
			$k = CCrmLiveFeedEntity::Lead;
			$v = $matches[1];
		}

		if ($k && $v)
		{
			$result = array($k, $v);
		}

		return $result;
	}

	public static function needToProcessRequest($method, $request)
	{
		return ($method === 'POST' && isset($request['save']) && $request['save'] === 'Y')
			|| ($method === 'GET' && isset($request['SONET_FILTER_MODE']) && $request['SONET_FILTER_MODE'] !== '')
			|| ($method === 'GET' && isset($request['log_filter_submit']) && $request['log_filter_submit'] === 'Y')
			|| ($method === 'GET' && isset($request['preset_filter_id']) && $request['preset_filter_id'] !== '')
			|| ($method === 'GET' && isset($request['preset_filter_top_id']) && $request['preset_filter_top_id'] !== '');
	}

	public static function OnSonetLogCounterClear($counterType, $timeStamp)
	{
		global $USER;

		if (
			in_array($counterType, array('CRM_**', 'CRM_**_ALL'))
			&& $USER->IsAuthorized()
		)
		{
			\Bitrix\Crm\Activity\Provider\Livefeed::readComments(array(
				"USER_ID" => $USER->GetId(),
				"TIMESTAMP" => $timeStamp
			));
		}

	}

	public static function createContact($params)
	{
		global $USER;

		$contactId = false;

		if (
			!empty($params['EMAIL'])
			&& check_email($params['EMAIL'], true)
		)
		{
			$CCrmContact = new CCrmContact();

			if (
				empty($params['NAME'])
				&& empty($params['LAST_NAME'])
			)
			{
				$params['LAST_NAME'] = $params['EMAIL'];
			}

			$fields = array(
				'NAME' => (!empty($params['NAME']) ? $params['NAME'] : ''),
				'LAST_NAME' => (!empty($params['LAST_NAME']) ? $params['LAST_NAME'] : ''),
				'FM' => array(
					'EMAIL' => array(
						'n1' => array (
							'VALUE' => $params['EMAIL'],
							'VALUE_TYPE' => 'WORK',
						)
					)
				),
				'TYPE_ID' => 'CLIENT',
				'SOURCE_ID' => 'EMAIL',
				'RESPONSIBLE_ID' => $USER->GetId()
			);

			$contactId = $CCrmContact->Add($fields, true, array('REGISTER_SONET_EVENT' => true, 'DISABLE_USER_FIELD_CHECK' => true));
		}

		return $contactId;
	}

	public static function processCrmBlogPostRights($logId, $logFields = array(), $blogPostFields = array(), $action = 'new')
	{
		global $USER;

		$logId = intval($logId);
		if (
			$logId > 0
			&& Main\Loader::includeModule('socialnetwork')
		)
		{
			$bOldCrm = ($logFields['ENTITY_TYPE'] == CCrmLiveFeedEntity::Contact);
			$bNewCrm = false;

			$bindings = array();
			$communications = array();
			$sonetRights = array();
			$logRightsToAdd = array();

			$rsLogRights = CSocNetLogRights::GetList(
				array(),
				array(
					'LOG_ID' => $logId
				)
			);
			while($arLogRights = $rsLogRights->fetch())
			{
				$sonetRights[] = $arLogRights['GROUP_CODE'];
			}

			foreach ($sonetRights as $rightCode)
			{
				if (preg_match('/^CRMCONTACT(\d+)$/', $rightCode, $matches))
				{
					$entityId = intval($matches[1]);
					$bNewCrm = true;

					$bindings = self::mergeBindings($bindings, array(array(
						'OWNER_ID' => $entityId,
						'OWNER_TYPE_ID' => CCrmOwnerType::Contact
					)));

					$rsUser = \Bitrix\Main\UserTable::getList(array(
						'order' => array(),
						'filter' => array(
							'UF_USER_CRM_ENTITY' => 'C_'.$entityId,
							'=EXTERNAL_AUTH_ID' => 'email'
						),
						'select' => array('ID', 'EMAIL')
					));

					if ($arUser = $rsUser->fetch())
					{
						$communications = self::mergeCommunications($communications, array(array(
							'ID' => 0,
							'TYPE' => 'EMAIL',
							'VALUE' => $arUser['EMAIL'],
							'ENTITY_ID' => $entityId,
							'ENTITY_TYPE_ID' => CCrmOwnerType::Contact
						)));
					}
				}
				elseif (preg_match('/^U(\d+)$/', $rightCode, $matches))
				{
					$rsUser = \Bitrix\Main\UserTable::getList(array(
						'order' => array(),
						'filter' => array(
							'ID' => intval($matches[1]),
							'=EXTERNAL_AUTH_ID' => 'email'
						),
						'select' => array('ID', 'EMAIL', 'UF_USER_CRM_ENTITY')
					));
					if (
						($arUser = $rsUser->fetch())
						&& !empty($arUser["UF_USER_CRM_ENTITY"])
						&& preg_match('/^C_(\d+)$/', $arUser["UF_USER_CRM_ENTITY"], $matches2)
					)
					{
						$bNewCrm = true;
						$entityId = intval($matches2[1]);

						$bindings = self::mergeBindings($bindings, array(array(
							'OWNER_ID' => $entityId,
							'OWNER_TYPE_ID' => CCrmOwnerType::Contact
						)));

						$communications = self::mergeCommunications($communications, array(array(
							'ID' => 0,
							'TYPE' => 'EMAIL',
							'VALUE' => $arUser['EMAIL'],
							'ENTITY_ID' => $entityId,
							'ENTITY_TYPE_ID' => CCrmOwnerType::Contact
						)));

						$newRight = CCrmLiveFeedEntity::Contact.$entityId;
						if (!in_array($newRight, $sonetRights))
						{
							$logRightsToAdd[] = $newRight;
						}
					}
				}
			}

			if ($bNewCrm && !$bOldCrm)
			{
				CSocNetLog::Update($logId, array(
					"ENTITY_TYPE" => CCrmLiveFeedEntity::Contact,
					"ENTITY_ID" => $entityId
				));
			}

			if (
				$action === 'new'
				&& $bNewCrm
			)
			{
				CSocNetLogRights::DeleteByLogID($logId);
				CSocNetLogRights::Add($logId, array_merge($sonetRights, $logRightsToAdd));
			}
			elseif (
				$action === 'edit'
				&& ($bNewCrm || $bOldCrm)
			)
			{
				CSocNetLogRights::DeleteByLogID($logId);
				CSocNetLogRights::Add($logId, array_merge($sonetRights, $logRightsToAdd));

				if ($bOldCrm && !$bNewCrm)
				{
					$arActivityIdToDelete = array();
					$rsActivity = CCrmActivity::GetList(
						array(),
						array("ASSOCIATED_ENTITY_ID" => $logId),
						false,
						false,
						array("ID")
					);
					while($arActivity = $rsActivity->fetch())
					{
						$arActivityIdToDelete[] = $arActivity["ID"];
					}

					foreach($arActivityIdToDelete as $activityId)
					{
						CCrmActivity::Delete($activityId, false);
					}

					CSocNetLog::Update($logId, array(
						"ENTITY_TYPE" => SONET_ENTITY_USER,
						"ENTITY_ID" => $logFields['USER_ID']
					));
				}
			}
			elseif (
				$action == 'share'
				&& ($bNewCrm || $bOldCrm)
			)
			{
				CSocNetLogRights::DeleteByLogID($logId);
				CSocNetLogRights::Add($logId, array_merge($sonetRights, $logRightsToAdd));
			}

			if ($bNewCrm && !$bOldCrm)
			{
				\Bitrix\Crm\Activity\Provider\Livefeed::addActivity(array(
					"TYPE" => "BLOG_POST",
					"COMMUNICATIONS" => $communications,
					"BINDINGS" => $bindings,
					"TITLE" => ($blogPostFields['MICRO'] === 'Y' ? '' : $blogPostFields['TITLE']),
					"MESSAGE" => $blogPostFields['DETAIL_TEXT'],
					"USER_ID" => (isset($blogPostFields['AUTHOR_ID']) ? $blogPostFields['AUTHOR_ID'] : $USER->getId()),
					"ENTITY_ID" => $logId
				));

				CCrmLiveFeed::CounterIncrement(array(
					"USER_ID" => (is_array($logFields["USER_ID"]) && isset($logFields["USER_ID"]) ? $logFields["USER_ID"] : $USER->getId()),
					"ENTITY_TYPE" => CCrmLiveFeedEntity::Contact,
					"ENTITY_ID" => $entityId,
					"LOG_ID" => $logId,
					"EVENT_ID" => (is_array($logFields["EVENT_ID"]) && isset($logFields["EVENT_ID"]) ? $logFields["EVENT_ID"] : 'blog_post')
				));
			}
			elseif ($bOldCrm && $bNewCrm)
			{
				$rsActivity = CCrmActivity::GetList(
					array(),
					array(
						'ASSOCIATED_ENTITY_ID' => $logId
					),
					false,
					array('nTopCount' => 1)
				);
				if ($arActivity = $rsActivity->fetch())
				{
					CCrmActivity::SaveBindings($arActivity['ID'], self::mergeBindings($bindings, CCrmActivity::GetBindings($arActivity['ID'])), false, false);
					CCrmActivity::SaveCommunications($arActivity['ID'], self::mergeCommunications($communications, CCrmActivity::GetCommunications($arActivity['ID'])), $arActivity, true, false);
				}
			}
		}
	}

	private static function mergeCommunications($commList1, $commList2)
	{
		if (!is_array($commList2))
		{
			$commList2 = array();
		}
		$result = $commList2;

		foreach($commList1 as $comm1)
		{
			$found = false;
			foreach($result as $comm2)
			{
				if (
					$comm1['TYPE'] == $comm2['TYPE']
					&& $comm1['VALUE'] == $comm2['VALUE']
					&& $comm1['ENTITY_TYPE_ID'] == $comm2['ENTITY_TYPE_ID']
					&& $comm1['ENTITY_ID'] == $comm2['ENTITY_ID']
				)
				{
					$found = true;
					break;
				}
			}

			if (!$found)
			{
				$result[] = $comm1;
			}
		}

		return $result;
	}

	private static function mergeBindings($bindList1, $bindList2)
	{
		if (!is_array($bindList2))
		{
			$bindList2 = array();
		}
		$result = $bindList2;

		foreach($bindList1 as $bind1)
		{
			$found = false;
			foreach($result as $bind2)
			{
				if (
					$bind1['OWNER_TYPE_ID'] == $bind2['OWNER_TYPE_ID']
					&& $bind1['OWNER_ID'] == $bind2['OWNER_ID']
				)
				{
					$found = true;
					break;
				}
			}

			if (!$found)
			{
				$result[] = $bind1;
			}
		}

		return $result;
	}

	public static function processCrmBlogComment($params = array())
	{
		static $blogPostEventIdList = null;

		$blogPostId = (int)($params["POST_ID"] ?? 0);
		$blogCommentId = (int)($params["COMMENT_ID"] ?? 0);
		$arAuthor = (isset($params["AUTHOR"]) && is_array($params["AUTHOR"]) ? $params["AUTHOR"] : array());
		$arUserId = (isset($params["USER_ID"]) && is_array($params["USER_ID"]) ? $params["USER_ID"] : array());

		if (
			empty($arUserId)
			|| empty($arAuthor)
			|| $blogPostId <= 0
			|| $blogCommentId <= 0
		)
		{
			return false;
		}

		if (Main\Loader::includeModule('socialnetwork'))
		{
			if ($blogPostEventIdList === null)
			{
				$blogPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\BlogPost;
				$blogPostEventIdList = $blogPostLivefeedProvider->getEventId();
			}

			$res = CSocNetLog::GetList(
				array(),
				array(
					'EVENT_ID' => $blogPostEventIdList,
					'ENTITY_TYPE' => CCrmLiveFeedEntity::Contact,
					'SOURCE_ID' => $blogPostId
				),
				false,
				array(
					'nTopCount' => 1
				),
				array('ID', 'ENTITY_TYPE', 'ENTITY_ID')
			);
			if ($log = $res->Fetch())
			{
				$res = CCrmActivity::getList(
					array(),
					array(
						'=PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\Livefeed::PROVIDER_ID,
						'=PROVIDER_TYPE_ID' => \Bitrix\Crm\Activity\Provider\Livefeed::PROVIDER_TYPE_ID_ENTRY,
						'=ASSOCIATED_ENTITY_ID' => $log['ID'],
						'CHECK_PERMISSIONS' => 'N'
					),
					false,
					false,
					array('ID', 'COMMUNICATIONS')
				);

				if (
					($parentActivity = $res->fetch())
					&& Main\Loader::includeModule('blog')
					&& ($comment = CBlogComment::getByID($blogCommentId))
				)
				{
					\Bitrix\Crm\Activity\Provider\Livefeed::addActivity(array(
						"TYPE" => (!empty($arAuthor["EXTERNAL_AUTH_ID"]) && $arAuthor["EXTERNAL_AUTH_ID"] === 'email' ? 'BLOG_COMMENT_IN' : 'BLOG_COMMENT_OUT'),
						"COMMUNICATIONS" => $parentActivity['COMMUNICATIONS'],
						"BINDINGS" => \CCrmActivity::getBindings($parentActivity['ID']),
						"MESSAGE" => $comment['POST_TEXT'],
						"USER_ID" => $comment['AUTHOR_ID'],
						"RESPONSIBLE_USER_ID" => $log['USER_ID'],
						"ENTITY_ID" => $comment["ID"],
						"PARENT_ID" => $parentActivity['ID']
					));

					CCrmLiveFeed::CounterIncrement(array(
						"USER_ID" => $comment['USER_ID'],
						"ENTITY_TYPE" => $log['ENTITY_TYPE'],
						"ENTITY_ID" => $log['ENTITY_ID'],
						"LOG_ID" => $log['ID'],
						"EVENT_ID" => $log['EVENT_ID']
					));
				}
			}
		}

		return true;
	}
}