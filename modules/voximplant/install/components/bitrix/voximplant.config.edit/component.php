<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\Asr\Language;

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (!CModule::IncludeModule('voximplant'))
	return;

$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
if (!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE, \Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
{
	ShowError(GetMessage('COMP_VI_ACCESS_DENIED'));
	return;
}

/**
 * @var $arParams array
 * @var $arResult array
 * @var $this CBitrixComponent
 * @var $APPLICATION CMain
 */
/********************************************************************
 * Input params
 ********************************************************************/
/***************** BASE ********************************************/
$arParams["ID"] = (int)($arParams["ID"] ?? $_REQUEST["ID"]);
/********************************************************************
 * /Input params
 ********************************************************************/
$account = new CVoxImplantAccount();
$arResult = array(
	"ITEM" => Bitrix\Voximplant\ConfigTable::getById($arParams["ID"])->fetch(),
	"QUEUES" => \Bitrix\Voximplant\Model\QueueTable::getList(array('select' => array('ID', 'NAME')))->fetchAll(),
	"IVR_MENUS" => \Bitrix\Voximplant\Model\IvrTable::getList(array('select' => array('ID', 'NAME')))->fetchAll(),
	"TRANSCRIBE_LANGUAGES" => \Bitrix\Voximplant\Asr\Language::getList(),
	"TRANSCRIBE_PROVIDERS" => \Bitrix\Voximplant\Asr\Provider::getList(),
	"SIP_CONFIG" => array(),
	"NUMBER" => array(),
	"CALLER_ID" => array(),
	"SHOW_DIRECT_CODE" => true,
	"SHOW_IVR" => true,
	"SHOW_MELODIES" => true,
	"SHOW_RULE_VOICEMAIL" => true,
	"SHOW_TRANSCRIPTION" => !in_array(
		\Bitrix\Main\Application::getInstance()->getLicense()->getRegion(),
		\Bitrix\Voximplant\Transcript::getHiddenRegions(),
		true
	),
);
$melodies = ["MELODY_WELCOME", "MELODY_WAIT", "MELODY_HOLD", "MELODY_VOICEMAIL", "WORKTIME_DAYOFF_MELODY", "MELODY_RECORDING", "MELODY_VOTE", "MELODY_VOTE_END", "MELODY_ENQUEUE"];
if ($arResult["ITEM"])
{
	$name = $arResult["ITEM"]["PHONE_NAME"] ?: CVoxImplantConfig::GetDefaultPhoneName($arResult["ITEM"]);
	if($name != "")
	{
		$GLOBALS["APPLICATION"]->SetTitle(htmlspecialcharsbx($name));
	}

	if (!empty($arResult["ITEM"]["WORKTIME_DAYOFF"]))
	{
		$arResult["ITEM"]["WORKTIME_DAYOFF"] = explode(",", $arResult["ITEM"]["WORKTIME_DAYOFF"]);
	}

	if ($arResult["ITEM"]["PORTAL_MODE"] == CVoxImplantConfig::MODE_SIP)
	{
		$viSip = new CVoxImplantSip();
		$arResult["SIP_CONFIG"] = $viSip->Get($arParams["ID"]);
		$arResult["SIP_CONFIG"]['PHONE_NAME'] = $arResult['ITEM']['PHONE_NAME'];

		$sipNumbers = \Bitrix\Voximplant\Model\ExternalLineTable::getList([
			"filter" => [
				"=SIP_ID" => $arResult["SIP_CONFIG"]["ID"]
			]
		])->fetchAll();
		$arResult["SIP_CONFIG"]["NUMBERS"] = array_map(
			function($numberFields)
			{
				return [
					"id" => $numberFields["NUMBER"],
					"name" => \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($numberFields["NUMBER"])->format(),
					"data" => [
						"canRemove" => $numberFields["IS_MANUAL"] === "Y"
					]
				];
			},
			$sipNumbers
		);
	}
	else if($arResult["ITEM"]["PORTAL_MODE"] == CVoxImplantConfig::MODE_RENT)
	{
		$arResult["NUMBER"] = \Bitrix\Voximplant\Model\NumberTable::getRow(["filter" => ["=CONFIG_ID" => $arResult["ITEM"]["ID"]]]);
	}
	else if($arResult["ITEM"]["PORTAL_MODE"] == CVoxImplantConfig::MODE_LINK)
	{
		$arResult["CALLER_ID"] = \Bitrix\Voximplant\Model\CallerIdTable::getRow(["filter" => ["=CONFIG_ID" => $arResult["ITEM"]["ID"]]]);
	}

	if ($arResult["ITEM"]["IVR"] == "Y" && !\Bitrix\Voximplant\Ivr\Ivr::isEnabled())
		$arResult["ITEM"]["IVR"] = "N";


	if ($arResult["ITEM"]["BACKUP_LINE"] == '')
		$arResult["ITEM"]["BACKUP_LINE"] = $arResult["ITEM"]["SEARCH_ID"];

	if ($arResult['ITEM']['TRANSCRIBE_LANG'] == '')
	{
		$arResult['ITEM']['TRANSCRIBE_LANG'] = \Bitrix\Voximplant\Asr\Language::getDefault(\Bitrix\Main\Context::getCurrent()->getLanguage());
	}
	if ($arResult['ITEM']['TRANSCRIBE_PROVIDER'] == '')
	{
		$arResult['ITEM']['TRANSCRIBE_PROVIDER'] = \Bitrix\Voximplant\Asr\Provider::getDefault($arResult['ITEM']['TRANSCRIBE_LANG']);
	}

	if ($arResult["ITEM"]["CAN_BE_SELECTED"] == "Y" && !\Bitrix\Voximplant\Limits::canSelectLine())
		$arResult["ITEM"]["CAN_BE_SELECTED"] = "N";

	$lineAccessCodes = array();
	$cursor = \Bitrix\Voximplant\Model\LineAccessTable::getList(array(
		'select' => array('ACCESS_CODE'),
		'filter' => array(
			'=CONFIG_ID' => $arResult['ITEM']['ID']
		)
	));
	while ($row = $cursor->fetch())
	{
		$lineAccessCodes[] = $row['ACCESS_CODE'];
	}

	$arResult['ITEM']['LINE_ACCESS'] = $lineAccessCodes;
}

if (empty($arResult["ITEM"]))
	return;

$request = \Bitrix\Main\Context::getCurrent()->getRequest();
if ($request->isPost() && check_bitrix_sessid())
{
	$post = $request->getPostList()->toArray();

	$post['SIP']['PHONE_NAME'] ??= null;
	$post['SIP']['SERVER'] ??= null;
	$post['SIP']['LOGIN'] ??= null;
	$post['SIP']['PASSWORD'] ??= null;
	$post['SIP']['NEED_UPDATE'] ??= null;
	$post['SIP']['DETECT_LINE_NUMBER'] ??= null;
	$post['SIP']['LINE_DETECT_HEADER_ORDER'] ??= null;
	$post['SIP']['AUTH_USER'] ??= null;
	$post['SIP']['OUTBOUND_PROXY'] ??= null;
	$post['SIP']['LINE_DETECT_HEADER_ORDER'] ??= null;
	$post['SIP']['LINE_DETECT_HEADER_ORDER'] ??= null;
	$post['CAN_BE_SELECTED'] ??= null;
	$post['TRANSCRIBE'] ??= null;
	$post['TRANSCRIBE_LANG'] ??= null;
	$post['TRANSCRIBE_PROVIDER'] ??= null;
	$post['CAN_BE_SELECTED'] ??= null;
	$post['USE_SPECIFIC_BACKUP_NUMBER'] ??= null;
	$post['BACKUP_NUMBER'] ??= null;
	$post['IVR_ID'] ??= null;
	$post['IVR'] ??= null;
	$post['DIRECT_CODE'] ??= null;
	$post['DIRECT_CODE_RULE'] ??= null;
	$post['CRM'] ??= null;
	$post['CRM_RULE'] ??= null;
	$post['CRM_CREATE'] ??= null;
	$post['CRM_CREATE_CALL_TYPE'] ??= null;
	$post['CRM_FORWARD'] ??= null;
	$post['CRM_TRANSFER_CHANGE'] ??= null;
	$post['CRM_SOURCE'] ??= null;
	$post['TIMEMAN'] ??= null;
	$post['QUEUE_ID'] ??= null;
	$post['FORWARD_LINE_ENABLED'] ??= null;
	$post['FORWARD_LINE'] ??= null;
	$post['RECORDING'] ??= null;
	$post['RECORDING_NOTICE'] ??= null;
	$post['RECORDING_STEREO'] ??= null;
	$post['VOTE'] ??= null;
	$post['MELODY_LANG'] ??= null;
	$post['MELODY_WELCOME_ENABLE'] ??= null;
	$post['WORKTIME_HOLIDAYS'] ??= null;
	$post['WORKTIME_ENABLE'] ??= null;
	$post['WORKTIME_TIMEZONE'] ??= null;
	$post['WORKTIME_DAYOFF'] ??= null;
	$post['WORKTIME_FROM'] ??= null;
	$post['WORKTIME_TO'] ??= null;
	$post['WORKTIME_DAYOFF_RULE'] ??= null;
	$post['WORKTIME_DAYOFF_NUMBER'] ??= null;
	$post['WORKTIME_DAYOFF_MELODY'] ??= null;
	$post['USE_SIP_TO'] ??= null;
	$post['CALLBACK_REDIAL'] ??= null;
	$post['CALLBACK_REDIAL_ATTEMPTS'] ??= null;
	$post['CALLBACK_REDIAL_PERIOD'] ??= null;
	$post['LINE_PREFIX'] ??= null;
	$post['BACKUP_LINE'] ??= null;
	$post['REDIRECT_WITH_CLIENT_NUMBER'] ??= null;
	$post['IFRAME'] ??= null;
	$post['LINE_ACCESS'] ??= [];

	$skipSaving = false;
	$arFieldsSip = Array();

	if($arResult["ITEM"]["PORTAL_MODE"] == CVoxImplantConfig::MODE_SIP)
	{
		$viSip = new CVoxImplantSip();
		$sipFields = array(
			'TYPE' => $arResult["SIP_CONFIG"]["TYPE"],
			'PHONE_NAME' => $post['SIP']['PHONE_NAME'],
			'SERVER' => $post['SIP']['SERVER'],
			'LOGIN' => $post['SIP']['LOGIN'],
			'PASSWORD' => $post['SIP']['PASSWORD'],
			'NEED_UPDATE' => $post['SIP']['NEED_UPDATE'],
			'DETECT_LINE_NUMBER' => $post['SIP']['DETECT_LINE_NUMBER'] == 'Y' ? 'Y' : 'N',
			'LINE_DETECT_HEADER_ORDER' => $post['SIP']['LINE_DETECT_HEADER_ORDER'],

		);

		if ($arResult["SIP_CONFIG"]['TYPE'] == CVoxImplantSip::TYPE_CLOUD)
		{
			$sipFields['AUTH_USER'] = $post['SIP']['AUTH_USER'];
			$sipFields['OUTBOUND_PROXY'] = $post['SIP']['OUTBOUND_PROXY'];
		}
		$result = $viSip->Update($arParams["ID"], $sipFields);

		if (!$result)
		{
			$skipSaving = true;
			$error = $viSip->GetError()->msg;
		}

		$arFieldsSip = Array(
			'PHONE_NAME' => $post['SIP']['PHONE_NAME'],
			'SERVER' => $post['SIP']['SERVER'],
			'LOGIN' => $post['SIP']['LOGIN'],
			'PASSWORD' => $post['SIP']['PASSWORD'],
			'AUTH_USER' => $post['SIP']['AUTH_USER'],
			'OUTBOUND_PROXY' => $post['SIP']['OUTBOUND_PROXY'],
		);
	}

	$workTimeDayOff = "";
	if (isset($post["WORKTIME_DAYOFF"]) && is_array($post["WORKTIME_DAYOFF"]))
	{
		$arAvailableValues = array('MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU');
		foreach ($post["WORKTIME_DAYOFF"] as $key => $value)
		{
			if (!in_array($value, $arAvailableValues))
				unset($post["WORKTIME_DAYOFF"][$key]);
		}
		if (!empty($post["WORKTIME_DAYOFF"]))
			$workTimeDayOff = implode(",", $post["WORKTIME_DAYOFF"]);
	}

	$workTimeFrom = "";
	$workTimeTo = "";
	if ($post["WORKTIME_FROM"] != '' && $post["WORKTIME_TO"] != '')
	{
		preg_match("/^\d{1,2}(\.\d{1,2})?$/i", $post["WORKTIME_FROM"], $matchesFrom);
		preg_match("/^\d{1,2}(\.\d{1,2})?$/i", $post["WORKTIME_TO"], $matchesTo);

		if (isset($matchesFrom[0]) && isset($matchesTo[0]))
		{
			$workTimeFrom = $post['WORKTIME_FROM'];
			$workTimeTo = $post['WORKTIME_TO'];

			if ($workTimeFrom > 23.30)
			{
				$workTimeFrom = 23.30;
			}
			if ($workTimeTo <= $workTimeFrom)
			{
				$workTimeTo = $workTimeFrom < 23.30 ? $workTimeFrom + 1 : 23.59;
			}
		}
	}

	$workTimeHolidays = "";
	if (!empty($post["WORKTIME_HOLIDAYS"]))
	{
		preg_match("/^(\d{1,2}\.\d{1,2},?)+$/i", $post["WORKTIME_HOLIDAYS"], $matches);

		if (isset($matches[0]))
		{
			$workTimeHolidays = $post["WORKTIME_HOLIDAYS"];
		}
	}

	if ($post["WORKTIME_DAYOFF_RULE"] == CVoxImplantIncoming::RULE_PSTN_SPECIFIC)
	{
		if ($post["WORKTIME_DAYOFF_NUMBER"] == '')
		{
			$post["WORKTIME_DAYOFF_RULE"] = CVoxImplantIncoming::RULE_HUNGUP;
		}
		else
		{
			$post["WORKTIME_DAYOFF_NUMBER"] = mb_substr($post["WORKTIME_DAYOFF_NUMBER"], 0, 20);
		}
	}
	else
	{
		$post["WORKTIME_DAYOFF_NUMBER"] = '';
	}

	if (!\Bitrix\Voximplant\Limits::canSelectCallSource())
	{
		$post["CRM_SOURCE"] = 'CALL';
	}

	if (!\Bitrix\Voximplant\Transcript::isEnabled())
	{
		$post['TRANSCRIBE'] = 'N';
	}

	if ($post['TRANSCRIBE'] === 'N')
	{
		$post['TRANSCRIBE_LANG'] = null;
		$post['TRANSCRIBE_PROVIDER'] = null;
	}
	else
	{
		if ($post['TRANSCRIBE_LANG'] !== Language::RUSSIAN_RU)
		{
			$post['TRANSCRIBE_PROVIDER'] = null;
		}
	}

	if (!\Bitrix\Voximplant\Limits::canSelectLine())
	{
		$post["CAN_BE_SELECTED"] = "N";
	}

	if ($post["CAN_BE_SELECTED"] == "Y")
	{
		$post["LINE_PREFIX"] = CVoxImplantPhone::stripLetters($post["LINE_PREFIX"]);
		if (!is_array($post["LINE_ACCESS"]))
			$post["LINE_ACCESS"] = array();
	}
	else
	{
		$post["LINE_PREFIX"] = null;
		$post["LINE_ACCESS"] = array();
	}

	if ($post["USE_SPECIFIC_BACKUP_NUMBER"] !== "Y")
	{
		$post["BACKUP_NUMBER"] = "";
	}

	$normalizedBackupNumber = null;
	if ($post["BACKUP_NUMBER"] != "")
	{
		$normalizedBackupNumber = CVoxImplantPhone::Normalize($post["BACKUP_NUMBER"], 1);
		if (!$normalizedBackupNumber)
		{
			$skipSaving = true;
			$error = GetMessage("COMP_VI_WRONG_BACKUP_NUMBER");
		}
		if (!isset($post["BACKUP_LINE"]))
		{
			$post["BACKUP_LINE"] = $arResult["ITEM"]["SEARCH_ID"];
		}
	}

	$ivrIds = array_map(function($r) { return $r["ID"]; }, $arResult["IVR_MENUS"]);
	$post["IVR_ID"] = (int)$post["IVR_ID"] ?: null;
	if(!in_array($post["IVR_ID"], $ivrIds))
	{
		$post["IVR"] = "N";
	}

	$arFields = Array(
		"DIRECT_CODE" => $post["DIRECT_CODE"] == "Y" ? "Y" : "N",
		"DIRECT_CODE_RULE" => $post["DIRECT_CODE_RULE"],
		"CRM" => $post["CRM"] == "Y" ? "Y" : "N",
		"CRM_RULE" => $post["CRM_RULE"],
		"CRM_CREATE" => $post["CRM_CREATE"],
		"CRM_CREATE_CALL_TYPE" => $post["CRM_CREATE_CALL_TYPE"],
		"CRM_FORWARD" => ($post["CRM_FORWARD"] === "Y" ? "Y" : "N"),
		"CRM_TRANSFER_CHANGE" => $post["CRM_TRANSFER_CHANGE"] == "Y" ? "Y" : "N",
		"CRM_SOURCE" => $post["CRM_SOURCE"],
		"TIMEMAN" => $post["TIMEMAN"] == "Y" ? "Y" : "N",
		"IVR" => \Bitrix\Voximplant\Ivr\Ivr::isEnabled() && $post["IVR"] == "Y" ? "Y" : "N",
		"IVR_ID" => (int)$post["IVR_ID"],
		"QUEUE_ID" => $post["QUEUE_ID"],
		"FORWARD_LINE" => isset($post["FORWARD_LINE_ENABLED"]) ? $post["FORWARD_LINE"] : CVoxImplantConfig::FORWARD_LINE_DEFAULT,
		"RECORDING" => $post["RECORDING"] === "Y" ? "Y" : "N",
		"RECORDING_NOTICE" => ($post["RECORDING"] === "Y" && $post["RECORDING_NOTICE"] === "Y") ? "Y" : "N",
		"RECORDING_STEREO" => ($post["RECORDING"] === "Y" && $post["RECORDING_STEREO"] === "Y") ? "Y" : "N",
		"VOTE" => \Bitrix\Voximplant\Limits::canVote() && $post["VOTE"] === "Y" ? "Y" : "N",
		"MELODY_LANG" => $post["MELODY_LANG"],
		"MELODY_WELCOME_ENABLE" => $post["MELODY_WELCOME_ENABLE"] === "Y" ? "Y" : "N",
		"WORKTIME_ENABLE" => $post["WORKTIME_ENABLE"] == "Y" ? "Y" : "N",
		"WORKTIME_FROM" => $workTimeFrom,
		"WORKTIME_TO" => $workTimeTo,
		"WORKTIME_HOLIDAYS" => $workTimeHolidays,
		"WORKTIME_DAYOFF" => $workTimeDayOff,
		"WORKTIME_TIMEZONE" => $post["WORKTIME_TIMEZONE"],
		"WORKTIME_DAYOFF_RULE" => $post["WORKTIME_DAYOFF_RULE"],
		"WORKTIME_DAYOFF_NUMBER" => $post["WORKTIME_DAYOFF_NUMBER"],
		"WORKTIME_DAYOFF_MELODY" => $post["WORKTIME_DAYOFF_MELODY"],
		"USE_SIP_TO" => $post["USE_SIP_TO"] == "Y" ? "Y" : "N",
		"TRANSCRIBE" => $post["TRANSCRIBE"] == "Y" ? "Y" : "N",
		"TRANSCRIBE_LANG" => $post["TRANSCRIBE_LANG"],
		"TRANSCRIBE_PROVIDER" => $post["TRANSCRIBE_PROVIDER"],
		"CALLBACK_REDIAL" => $post["CALLBACK_REDIAL"] == "Y" ? "Y" : "N",
		"CALLBACK_REDIAL_ATTEMPTS" => $post["CALLBACK_REDIAL"] == "Y" ? $post["CALLBACK_REDIAL_ATTEMPTS"] : null,
		"CALLBACK_REDIAL_PERIOD" => $post["CALLBACK_REDIAL"] == "Y" ? $post["CALLBACK_REDIAL_PERIOD"] : null,
		"CAN_BE_SELECTED" => $post["CAN_BE_SELECTED"] == "Y" ? "Y" : "N",
		"LINE_PREFIX" => $post["LINE_PREFIX"],
		"BACKUP_NUMBER" => $normalizedBackupNumber,
		"BACKUP_LINE" => $post["BACKUP_LINE"],
		"REDIRECT_WITH_CLIENT_NUMBER" => $post["REDIRECT_WITH_CLIENT_NUMBER"] == "Y" ? "Y" : "N",
	);
	if (!$skipSaving)
	{
		foreach ($melodies as $melody)
		{
			$arFields[$melody] = $post[$melody] ?? null;
			if (isset($post[$melody."_del"]))
			{
				CFile::Delete($post[$melody]);
				$arFields[$melody] = 0;
			}
		}

		\Bitrix\Voximplant\Model\LineAccessTable::deleteByConfigId($arResult["ITEM"]["ID"]);
		foreach ($post["LINE_ACCESS"] as $accessCode)
		{
			\Bitrix\Voximplant\Model\LineAccessTable::add(array(
				'CONFIG_ID' => $arResult["ITEM"]["ID"],
				'ACCESS_CODE' => $accessCode
			));
		}

		CVoxImplantUser::clearCache();
		CVoxImplantConfig::saveBackupNumber($arResult["ITEM"]["SEARCH_ID"], $normalizedBackupNumber, $post["BACKUP_LINE"]);
		$res = Bitrix\Voximplant\ConfigTable::update($arParams["ID"], $arFields);
		if ($res->isSuccess())
		{
			$iframe = $post['IFRAME'] === 'Y' ? '&IFRAME=Y' : '';
			LocalRedirect($request->getRequestUri());
		}
		$error = $res->getErrorMessages();
	}

	$arResult = array(
		"ERROR" => $error,
		"ITEM" => array_merge($arResult["ITEM"], $arFields),
		"SIP_CONFIG" => array_merge($arResult["SIP_CONFIG"], $arFieldsSip)
	);
}

$arResult['CRM_SOURCES'] = CModule::IncludeModule('crm') ? CCrmStatus::GetStatusList('SOURCE') : Array();

if (!isset($arResult['CRM_SOURCES'][$arResult['ITEM']['CRM_SOURCE']]))
{
	if (isset($arResult['CRM_SOURCES']['CALL']))
	{
		$arResult['ITEM']['CRM_SOURCE'] = 'CALL';
	}
	else if (isset($arResult['CRM_SOURCES']['OTHER']))
	{
		$arResult['ITEM']['CRM_SOURCE'] = 'OTHER';
	}
}

foreach ($melodies as $id)
{
	if ($arResult["ITEM"][$id] > 0)
	{
		$res = CFile::GetFileArray($arResult["ITEM"][$id]);
		if ($res)
		{
			$arResult["ITEM"]["~".$id] = $res;
		}
		else
		{
			$arResult["ITEM"][$id] = 0;
		}
	}
}
$arResult["ITEM"]["MELODY_LANG"] = (empty($arResult["ITEM"]["MELODY_LANG"])? mb_strtoupper(LANGUAGE_ID) : $arResult["ITEM"]["MELODY_LANG"]);
$arResult["ITEM"]["MELODY_LANG"] = (in_array($arResult["ITEM"]["MELODY_LANG"], CVoxImplantConfig::GetMelodyLanguages()) ? $arResult["ITEM"]["MELODY_LANG"] : "EN");
$arResult["DEFAULT_MELODIES"] = CVoxImplantConfig::GetDefaultMelodies(false);

if (IsModuleInstalled('bitrix24'))
{
	$arResult['LINK_TO_DOC'] = (in_array(LANGUAGE_ID, Array("ru", "kz", "ua", "by")) ? 'https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=52&CHAPTER_ID=02564' : 'https://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=55&LESSON_ID=6635');
}
else
{
	$arResult['LINK_TO_DOC'] = (in_array(LANGUAGE_ID, Array("ru", "kz", "ua", "by")) ? 'https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=48&CHAPTER_ID=02699' : 'https://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=26&LESSON_ID=6734');
}

//for work time block
$arResult["TIME_ZONE_ENABLED"] = CTimeZone::Enabled();
$arResult["TIME_ZONE_LIST"] = CTimeZone::GetZones();

if (empty($arResult["ITEM"]["WORKTIME_TIMEZONE"]))
{
	if (LANGUAGE_ID == "ru")
		$arResult["ITEM"]["WORKTIME_TIMEZONE"] = "Europe/Moscow";
	elseif (LANGUAGE_ID == "de")
		$arResult["ITEM"]["WORKTIME_TIMEZONE"] = "Europe/Berlin";
	elseif (LANGUAGE_ID == "ua")
		$arResult["ITEM"]["WORKTIME_TIMEZONE"] = "Europe/Kiev";
	else
		$arResult["ITEM"]["WORKTIME_TIMEZONE"] = "America/New_York";
}

$arResult["WEEK_DAYS"] = Array('MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU');

$arResult["WORKTIME_LIST_FROM"] = array();
$arResult["WORKTIME_LIST_TO"] = array();
if (CModule::IncludeModule("calendar"))
{
	$arResult["WORKTIME_LIST_FROM"][strval(0)] = CCalendar::FormatTime(0, 0);
	for ($i = 0; $i < 24; $i++)
	{
		if ($i !== 0)
		{
			$arResult["WORKTIME_LIST_FROM"][strval($i)] = CCalendar::FormatTime($i, 0);
			$arResult["WORKTIME_LIST_TO"][strval($i)] = CCalendar::FormatTime($i, 0);
		}
		$arResult["WORKTIME_LIST_FROM"][strval($i).'.30'] = CCalendar::FormatTime($i, 30);
		$arResult["WORKTIME_LIST_TO"][strval($i).'.30'] = CCalendar::FormatTime($i, 30);
	}
	$arResult["WORKTIME_LIST_TO"][strval('23.59')] = CCalendar::FormatTime(23, 59);
}

$arResult['FORWARD_LINES'] = CVoxImplantConfig::GetPortalNumbers();
unset($arResult['FORWARD_LINES'][$arResult["ITEM"]["SEARCH_ID"]]);
$arResult['BACKUP_LINES'] = CVoxImplantConfig::GetPortalNumbers();
foreach ($arResult['BACKUP_LINES'] as $lineId => $lineTitle)
{
	if ($lineId == $arResult['ITEM']['SEARCH_ID'])
	{
		$arResult['BACKUP_LINES'][$lineId] = $lineTitle.' ('.GetMessage('VI_CONFIG_CURRENT_CONNECTION').')';
	}
}

if (!empty($arResult["SIP_CONFIG"]) && $arResult["SIP_CONFIG"]['TYPE'] == CVoxImplantSip::TYPE_CLOUD)
{
	unset($arResult['FORWARD_LINES']['reg'.$arResult['SIP_CONFIG']['REG_ID']]);
}
$arResult['RECORD_LIMIT'] = \CVoxImplantAccount::GetRecordLimit($arResult["ITEM"]["PORTAL_MODE"]);
$arResult['DEFAULTS'] = array(
	'MAXIMUM_GROUPS' => \Bitrix\Voximplant\Limits::getMaximumGroups()
);

$arResult['IFRAME'] = $_REQUEST['IFRAME'] === 'Y';

$arResult['CONFIG_MENU'] = [];
if($arResult['ITEM']['PORTAL_MODE'] === CVoxImplantConfig::MODE_SIP)
{
	$arResult['CONFIG_MENU']["sip"] = [
		"PAGE" => "sip",
		"NAME" => Loc::getMessage("VOX_CONFIG_EDIT_CONNECTION_SETTINGS"),
		"ACTIVE" => true,
		"ATTRIBUTES" => [
			"data-role" => "menu-item",
			"data-page" => "sip"
		]
	];
}

$arResult['CONFIG_MENU']["routing"] = [
	"PAGE" => "routing",
	"NAME" => $arResult["ITEM"]["PORTAL_MODE"] === CVoxImplantConfig::MODE_LINK ? Loc::getMessage("VOX_CONFIG_EDIT_CALLBACK_ROUTING") : Loc::getMessage("VOX_CONFIG_EDIT_CALL_ROUTING"),
	"ACTIVE" => $arResult["ITEM"]["PORTAL_MODE"] !== CVoxImplantConfig::MODE_SIP,
	"ATTRIBUTES" => [
		"data-role" => "menu-item",
		"data-page" => "routing"
	]
];

if($arResult['ITEM']['PORTAL_MODE'] === CVoxImplantConfig::MODE_SIP)
{
	$arResult['CONFIG_MENU']["sip-numbers"] = [
		"PAGE" => "sip-numbers",
		"NAME" => Loc::getMessage("VOX_CONFIG_EDIT_SIP_NUMBERS"),
		"ATTRIBUTES" => [
			"data-role" => "menu-item",
			"data-page" => "sip-numbers"
		]
	];
}

if(IsModuleInstalled("crm"))
{
	$arResult['CONFIG_MENU']["crm"] = [
		"PAGE" => "crm",
		"NAME" => Loc::getMessage("VOX_CONFIG_EDIT_CRM_INTEGRATION"),
		"ATTRIBUTES" => [
			"data-role" => "menu-item",
			"data-page" => "crm"
		]
	];
}

$arResult['CONFIG_MENU']["recording"] = [
	"PAGE" => "recording",
	"NAME" => Loc::getMessage("VOX_CONFIG_EDIT_RECORDING_AND_RATING"),
	"ATTRIBUTES" => [
		"data-role" => "menu-item",
		"data-page" => "recording"
	]
];

$arResult['CONFIG_MENU']["worktime"] = [
	"PAGE" => "worktime",
	"NAME" => Loc::getMessage("VOX_CONFIG_EDIT_WORKTIME"),
	"ATTRIBUTES" => [
		"data-role" => "menu-item",
		"data-page" => "worktime"
	]
];

$arResult['CONFIG_MENU']["other"] = [
	"PAGE" => "other",
	"NAME" => Loc::getMessage("VOX_CONFIG_EDIT_OTHER"),
	"ATTRIBUTES" => [
		"data-role" => "menu-item",
		"data-page" => "other"
	]
];

$arResult['CONFIG_MENU']["melodies"] = [
	"PAGE" => "melodies",
	"NAME" => Loc::getMessage("VOX_CONFIG_EDIT_MELODIES_MSGVER_1"),
	"ATTRIBUTES" => [
		"data-role" => "menu-item",
		"data-page" => "melodies"
	]
];

if($arResult["ITEM"]["PORTAL_MODE"] == CVoxImplantConfig::MODE_RENT || $arResult["ITEM"]["PORTAL_MODE"] == CVoxImplantConfig::MODE_LINK)
{
	$arResult['CONFIG_MENU']["unlink"] = [
		"PAGE" => "unlink",
		"NAME" => $arResult["ITEM"]["PORTAL_MODE"] === CVoxImplantConfig::MODE_SIP ? Loc::getMessage("VOX_CONFIG_EDIT_DELETE_CONNECTION") : Loc::getMessage("VOX_CONFIG_EDIT_DISCONNECT_NUMBER"),
		"ATTRIBUTES" => [
			"data-role" => "menu-item",
			"data-page" => "unlink"
		]
	];
}

$arResult["MELODIES"] = [
	"welcome" => [
		"TITLE" => GetMessage("VI_CONFIG_EDIT_WELCOMING_TUNE"),
		"TIP" => GetMessage("VI_CONFIG_EDIT_WELCOMING_TUNE_TIP_MSGVER_1"),
		"MELODY" => (array_key_exists("~MELODY_WELCOME", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_WELCOME"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $arResult["DEFAULT_MELODIES"]["MELODY_WELCOME"])),
		"MELODY_ID" => $arResult["ITEM"]["MELODY_WELCOME"],
		"DEFAULT_MELODY" => $arResult["DEFAULT_MELODIES"]["MELODY_WELCOME"],
		"CHECKBOX" => "MELODY_WELCOME_ENABLE",
		"INPUT_NAME" => "MELODY_WELCOME",
		"HIDDEN" => false
	],
	"recording" => [
		"TITLE" => GetMessage("VI_CONFIG_EDIT_RECORDING_TUNE_MSGVER_1"),
		"TIP" => GetMessage("VI_CONFIG_EDIT_RECORDING_TUNE_TIP_MSGVER_1"),
		"MELODY" => (array_key_exists("~MELODY_RECORDING", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_RECORDING"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $arResult["DEFAULT_MELODIES"]["MELODY_RECORDING"])),
		"MELODY_ID" => $arResult["ITEM"]["MELODY_RECORDING"],
		"DEFAULT_MELODY" => $arResult["DEFAULT_MELODIES"]["MELODY_RECORDING"],
		"INPUT_NAME" => "MELODY_RECORDING",
		"HIDDEN" => true
	],
	"wait" => [
		"TITLE" => GetMessage("VI_CONFIG_EDIT_WAITING_TUNE_MSGVER_1"),
		"TIP" => GetMessage("VI_CONFIG_EDIT_WAITING_TUNE_TIP_MSGVER_1"),
		"MELODY" => (array_key_exists("~MELODY_WAIT", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_WAIT"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $arResult["DEFAULT_MELODIES"]["MELODY_WAIT"])),
		"MELODY_ID" => $arResult["ITEM"]["MELODY_WAIT"],
		"DEFAULT_MELODY" => $arResult["DEFAULT_MELODIES"]["MELODY_WAIT"],
		"INPUT_NAME" => "MELODY_WAIT",
		"HIDDEN" => true
	],
	"enqueue" => [
		"TITLE" => GetMessage("VI_CONFIG_EDIT_ENQUEUE_TUNE_MSGVER_1"),
		"TIP" => GetMessage("VI_CONFIG_EDIT_ENQUEUE_TUNE_TIP_MSGVER_1"),
		"MELODY" => (array_key_exists("~MELODY_ENQUEUE", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_ENQUEUE"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $arResult["DEFAULT_MELODIES"]["MELODY_ENQUEUE"])),
		"MELODY_ID" => $arResult["ITEM"]["MELODY_ENQUEUE"],
		"DEFAULT_MELODY" => $arResult["DEFAULT_MELODIES"]["MELODY_ENQUEUE"],
		"INPUT_NAME" => "MELODY_ENQUEUE",
		"HIDDEN" => true
	],
	"hold" => [
		"TITLE" => GetMessage("VI_CONFIG_EDIT_HOLDING_TUNE"),
		"TIP" => GetMessage("VI_CONFIG_EDIT_HOLDING_TUNE_TIP_MSGVER_1"),
		"MELODY" => (array_key_exists("~MELODY_HOLD", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_HOLD"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $arResult["DEFAULT_MELODIES"]["MELODY_HOLD"])),
		"MELODY_ID" => $arResult["ITEM"]["MELODY_HOLD"],
		"DEFAULT_MELODY" => $arResult["DEFAULT_MELODIES"]["MELODY_HOLD"],
		"INPUT_NAME" => "MELODY_HOLD",
		"HIDDEN" => true
	],
	"voicemail" => [
		"TITLE" => GetMessage("VI_CONFIG_EDIT_AUTO_ANSWERING_TUNE"),
		"TIP" => GetMessage("VI_CONFIG_EDIT_AUTO_ANSWERING_TUNE_TIP_MSGVER_1"),
		"MELODY" => (array_key_exists("~MELODY_VOICEMAIL", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_VOICEMAIL"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $arResult["DEFAULT_MELODIES"]["MELODY_VOICEMAIL"])),
		"MELODY_ID" => $arResult["ITEM"]["MELODY_VOICEMAIL"],
		"DEFAULT_MELODY" => $arResult["DEFAULT_MELODIES"]["MELODY_VOICEMAIL"],
		"INPUT_NAME" => "MELODY_VOICEMAIL",
		"HIDDEN" => true
	],
	"vote" => [
		"TITLE" => GetMessage("VI_CONFIG_EDIT_VOTE_TUNE_MSGVER_1"),
		"TIP" => GetMessage("VI_CONFIG_EDIT_VOTE_TUNE_TIP_MSGVER_1"),
		"MELODY" => (array_key_exists("~MELODY_VOTE", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_VOTE"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $arResult["DEFAULT_MELODIES"]["MELODY_VOTE"])),
		"MELODY_ID" => $arResult["ITEM"]["MELODY_VOTE"],
		"DEFAULT_MELODY" => $arResult["DEFAULT_MELODIES"]["MELODY_VOTE"],
		"INPUT_NAME" => "MELODY_VOTE",
		"HIDDEN" => true
	],
	"vote_end" => [
		"TITLE" => GetMessage("VI_CONFIG_EDIT_VOTE_END_TUNE_MSGVER_1"),
		"TIP" => GetMessage("VI_CONFIG_EDIT_VOTE_END_TUNE_TIP_MSGVER_1"),
		"MELODY" => (array_key_exists("~MELODY_VOTE_END", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_VOTE_END"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $arResult["DEFAULT_MELODIES"]["MELODY_VOTE_END"])),
		"MELODY_ID" => $arResult["ITEM"]["MELODY_VOTE_END"],
		"DEFAULT_MELODY" => $arResult["DEFAULT_MELODIES"]["MELODY_VOTE_END"],
		"INPUT_NAME" => "MELODY_VOTE_END",
		"HIDDEN" => true
	]
];

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;
?>