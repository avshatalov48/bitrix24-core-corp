<?php

use \Bitrix\Main\Localization\Loc;

define('STOP_STATISTICS',    true);
define('NO_AGENT_CHECK',     true);
define('DisableEventsCheck', true);

define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CUtil::JSPostUnescape();

CModule::IncludeModule('tasks');

Loc::loadMessages(__FILE__);

$SITE_ID = isset($_GET["SITE_ID"]) ? $_GET["SITE_ID"] : SITE_ID;

if (isset($_GET["nt"]))
{
	preg_match_all("/(#NAME#)|(#NOBR#)|(#\/NOBR#)|(#LAST_NAME#)|(#SECOND_NAME#)|(#NAME_SHORT#)|(#SECOND_NAME_SHORT#)|\s|\,/", urldecode($_GET["nt"]), $matches);
	$nameTemplate = implode("", $matches[0]);
}
elseif (isset($_POST['NAME_TEMPLATE']))
{
	preg_match_all("/(#NAME#)|(#NOBR#)|(#\/NOBR#)|(#LAST_NAME#)|(#SECOND_NAME#)|(#NAME_SHORT#)|(#SECOND_NAME_SHORT#)|\s|\,/", urldecode($_POST['NAME_TEMPLATE']), $matches);
	$nameTemplate = implode("", $matches[0]);
}
else
	$nameTemplate = CSite::GetNameFormat(false);

$arParams = array();
$arParams["NAME_TEMPLATE"] = $nameTemplate;

$loggedInUserId = 0;
if (isset($GLOBALS['USER']) && is_object($GLOBALS['USER']) && $GLOBALS['USER']->isAuthorized())
	$loggedInUserId = (int) $GLOBALS['USER']->GetID();

if (check_bitrix_sessid() && ($loggedInUserId > 0))
{
	$action = '';
	if (isset($_GET['action']))
		$action = $_GET['action'];

	if ($action === 'render_task_log_last_row_with_date_change')
	{
		$arParams["PATH_TO_USER_PROFILE"] = (string) $_POST["PATH_TO_USER_PROFILE"];

		$authorUserId = (int) $loggedInUserId;
		$taskId = (int) $_POST['task_id'];

		$rsLog = CTaskLog::GetList(
			array('CREATED_DATE' => 'DESC'), 
			array("TASK_ID" => $taskId)
		);

		$arData = false;
		while ($arLog = $rsLog->GetNext())
		{
			// wait for DEADLINE field
			if ($arLog['FIELD'] !== 'DEADLINE')
				continue;

			// Yeah, we found it!
			$arData = $arLog;
			break;
		}

		// If row found
		if ($arData !== false)
		{
			$rsCurUserData = $USER->GetByID($authorUserId);
			$arCurUserData = $rsCurUserData->Fetch();

			$strDateFrom = $strDateTo = '';

			if ($arData['FROM_VALUE'])
			{
				$strDateFrom = \Bitrix\Tasks\UI::formatDateTime($arData['FROM_VALUE'], '^'.\Bitrix\Tasks\UI::getDateTimeFormat());
			}

			if ($arData['TO_VALUE'])
			{
				$strDateTo = \Bitrix\Tasks\UI::formatDateTime($arData['TO_VALUE'], '^'.\Bitrix\Tasks\UI::getDateTimeFormat());
			}

			$arResult = array(
				'td1' => '<span class="task-log-date">' . FormatDateFromDB($arData['CREATED_DATE']) . '</span>',
				'td2' => '<a class="task-log-author" target="_top" href="' 
					. CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_USER_PROFILE'], 
						array('user_id' => $authorUserId)
						) 
					. '">' 
					. htmlspecialcharsbx(tasksFormatNameShort(
						$arCurUserData["NAME"], 
						$arCurUserData["LAST_NAME"], 
						$arCurUserData["LOGIN"], 
						$arCurUserData["SECOND_NAME"], 
						$arParams["NAME_TEMPLATE"]))
					. '</a>',
				'td3' => '<span class="task-log-where">' . GetMessage("TASKS_LOG_DEADLINE")  . '</span>',
				'td4' => '<span class="task-log-what">'
					. $strDateFrom
					. '<span class="task-log-arrow">&rarr;</span>'
					. $strDateTo
					. '</span>'
				);

			header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
			echo CUtil::PhpToJsObject($arResult);
		}
	}
	elseif ($action === 'remove_file')
	{
		try
		{
			CTaskAssert::log(
				'remove_file: fileId=' . $_POST['fileId'] . ', taskId=' . $_POST['taskId']
				. ', userId=' . $loggedInUserId,
				CTaskAssert::ELL_INFO
			);
			CTaskAssert::assert(isset($_POST['fileId'], $_POST['taskId']));
			$oTaskItem = new CTaskItem($_POST['taskId'], $loggedInUserId);
			$oTaskItem->removeAttachedFile($_POST['fileId']);
			echo 'Success';
		}
		catch (Exception $e)
		{
			echo 'Error occured';
			CTaskAssert::logWarning(
				'Unable to remove_file: fileId=' . $_POST['fileId'] 
				. ', taskId=' . $_POST['taskId'] . ', userId=' . $loggedInUserId
			);
		}
	}
	elseif ($action === 'render_task_detail_part')
	{
		if (isset($_POST['BLOCK']))
		{
			switch ($_POST['BLOCK'])
			{
				case 'buttons':
				case 'right_sidebar':
					if (($_POST['IS_IFRAME'] === 'true') || ($_POST['IS_IFRAME'] === true) || ($_POST['IS_IFRAME'] === 'Y'))
						$isIframe = true;
					else
						$isIframe = false;

					$APPLICATION->IncludeComponent(
						"bitrix:tasks.task.detail.parts",
						".default",
						array(
							'INNER_HTML'           => $_POST['INNER_HTML'],
							'MODE'                 => $_POST['MODE'],
							'BLOCKS'               => array($_POST['BLOCK']),
							'IS_IFRAME'            => $isIframe,
							'PATH_TO_TEMPLATES_TEMPLATE' => $_POST['PATH_TO_TEMPLATES_TEMPLATE'],
							'PATH_TO_USER_PROFILE' => $_POST['PATH_TO_USER_PROFILE'],
							'PATH_TO_TASKS_TASK'   => $_POST['PATH_TO_TASKS_TASK'],
							'FIRE_ON_CHANGED_EVENT' => $_POST['FIRE_ON_CHANGED_EVENT'],
							'NAME_TEMPLATE'        => $nameTemplate,
							'LOAD_TASK_DATA'       => 'Y',
							'TASK_ID'              => (int) $_POST['TASK_ID']
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);
				break;

				default:
					CTaskAssert::logError('[0x4fae6498] Unexpected $_POST[\'BLOCK\'] value: ' . $_POST['BLOCK']);
				break;
			}
		}
		else
			CTaskAssert::logError('[0x0907bb31] $_POST[\'BLOCK\'] expected, but not given');
	}
	elseif ($action === 'render_comments')
	{
			CModule::IncludeModule('tasks');
			CModule::IncludeModule('forum');
			$permission = 'A';
			$oTask = CTaskItem::getInstanceFromPool($_POST['taskId'], $loggedInUserId);
			$arTask = $oTask->getData($bEscape = false);

			$arTaskUsers = CTasks::__GetSearchPermissions($arTask);
			if (($USER->CanAccess($arTaskUsers) === true) || $USER->IsAdmin() || CTasksTools::IsPortalB24Admin() )
				$permission = 'M';

			$APPLICATION->RestartBuffer();
			header('Content-Type: text/html; charset=' . LANG_CHARSET);

			$APPLICATION->IncludeComponent("bitrix:forum.comments", "bitrix24", array(
					"FORUM_ID" => $_POST['forumId'],
					"ENTITY_TYPE" => "TK",
					"ENTITY_ID" => $_POST['taskId'],
					"ENTITY_XML_ID" => "TASK_".$_POST['taskId'],
					"URL_TEMPLATES_PROFILE_VIEW" => $_POST['PATH_TO_USER_PROFILE'],
					//"CACHE_TYPE" => $arParams["CACHE_TYPE"],
					//"CACHE_TIME" => $arParams["CACHE_TIME"],
					"MESSAGES_PER_PAGE" => $_POST['ITEM_DETAIL_COUNT'],
					"PAGE_NAVIGATION_TEMPLATE" => "arrows",
					"DATE_TIME_FORMAT" => \Bitrix\Tasks\UI::getDateTimeFormat(),
					"PATH_TO_SMILE" => $_POST['PATH_TO_FORUM_SMILE'],
					"EDITOR_CODE_DEFAULT" => "N",
					"SHOW_MODERATION" => "Y",
					"SHOW_AVATAR" => "Y",
					"SHOW_RATING" => $_POST['SHOW_RATING'],
					"RATING_TYPE" => $_POST['RATING_TYPE'],
					"SHOW_MINIMIZED" => "N",
					"USE_CAPTCHA" => "N",
					'PREORDER' => 'N',
					"SHOW_LINK_TO_FORUM" => "N",
					"SHOW_SUBSCRIBE" => "N",
					"FILES_COUNT" => 10,
					"SHOW_WYSIWYG_EDITOR" => "Y",
					"AUTOSAVE" => true,
					"PERMISSION" => $permission,
					"NAME_TEMPLATE" => $_POST["NAME_TEMPLATE"],
					"MESSAGE_COUNT" => 3,
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);

		require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
		exit();
	}
	else
	{
		CTaskAssert::logError('[0x447f7b28] Unknown action: ' . $action);
	}
}

CMain::FinalActions(); // to make events work on bitrix24
