<?php
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';

/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @global boolean $hasAccess */

require 'page_include.php';

$logEntryId = (isset($_REQUEST["log_id"]) ? intval($_REQUEST["log_id"]) : false);

if ($logEntryId && $hasAccess)
{
	$arComponentParams = array(
//		'IS_CRM' => 'Y',
		"PUBLIC_MODE" => "Y",
		'USE_FOLLOW' => 'N',
		'LOG_ID' => $logEntryId,
		'PATH_TO_LOG_ENTRY' => SITE_DIR."pub/log_entry.php?log_id=#log_id#",
//		'CRM_EXTENDED_MODE' => 'Y',
		'CRM_ENABLE_ACTIVITY_EDITOR' => false,
		'HIDE_EDIT_FORM' => 'Y',
		'USE_COMMENTS' => 'Y',
		'SHOW_EVENT_ID_FILTER' => 'N',
		'SHOW_REFRESH' => 'N',
		'SHOW_NAV_STRING' => 'N',
		'SHOW_YEAR' => 'Y',
		'SHOW_LOGIN' => 'Y',
		'SET_TITLE' => 'N',
		'NAME_TEMPLATE' => CSite::GetNameFormat(),
		'DATE_TIME_FORMAT' => (
			LANGUAGE_ID == 'en'
				? "j F Y g:i a"
				: (
					LANGUAGE_ID == 'de'
						? "j. F Y, G:i"
						: "j F Y G:i"
			)
		),
		'CACHE_TYPE' => 'A',
		'CACHE_TIME' => 3600,
		'SHOW_RATING' => 'Y',
		'USE_FAVORITES' => 'N'
	);

	$arReturn = $APPLICATION->IncludeComponent(
		"bitrix:socialnetwork.log.ex",
		"",
		$arComponentParams,
		null,
		array('HIDE_ICONS' => 'Y')
	);
}
else
{
	$arReturn = array(
		'ERROR_CODE' => !$USER->isAuthorized() ? 'NO_AUTH' : !$blogGroupId ? 'NO_BLOG' : !$postId ? 'NO_POST' : 'NO_RIGHTS'
	);
}

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
?>
