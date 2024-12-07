<?php

use Bitrix\Tasks\Internals\Task\MetaStatus;
use Bitrix\Tasks\Internals\Task\Status;

IncludeModuleLangFile(__FILE__);

class TasksException extends \Bitrix\Tasks\Exception
{
	const TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE  = 0x000001;
	const TE_ACCESS_DENIED                     = 0x100002;
	const TE_ACTION_NOT_ALLOWED                = 0x000004;
	const TE_ACTION_FAILED_TO_BE_PROCESSED     = 0x000008;
	const TE_TRYED_DELEGATE_TO_WRONG_PERSON    = 0x000010;
	const TE_FILE_NOT_ATTACHED_TO_TASK         = 0x000020;
	const TE_UNKNOWN_ERROR                     = 0x000040;
	const TE_FILTER_MANIFEST_MISMATCH          = 0x000080;
	const TE_WRONG_ARGUMENTS                   = 0x000100;
	const TE_ITEM_NOT_FOUND_OR_NOT_ACCESSIBLE  = 0x000200;
	const TE_SQL_ERROR                         = 0x000400;

	const TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE = 0x100000;

	private static $errSymbolsMap = array(
		'TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE'  => 0x000001,
		'TE_ACCESS_DENIED'                     => 0x100002,
		'TE_ACTION_NOT_ALLOWED'                => 0x000004,
		'TE_ACTION_FAILED_TO_BE_PROCESSED'     => 0x000008,
		'TE_TRYED_DELEGATE_TO_WRONG_PERSON'    => 0x000010,
		'TE_FILE_NOT_ATTACHED_TO_TASK'         => 0x000020,
		'TE_UNKNOWN_ERROR'                     => 0x000040,
		'TE_FILTER_MANIFEST_MISMATCH'          => 0x000080,
		'TE_WRONG_ARGUMENTS'                   => 0x000100,
		'TE_ITEM_NOT_FOUND_OR_NOT_ACCESSIBLE'  => 0x000200,
		'TE_SQL_ERROR'                         => 0x000400,

		'TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE' => 0x100000
	);

	public function checkIsActionNotAllowed()
	{
		return $this->checkOfType(self::TE_ACTION_NOT_ALLOWED);
	}

	public function checkOfType($type)
	{
		return $this->getCode() & $type;
	}

	protected function dumpAuxError()
	{
		return false;
	}

	public function __construct($message = false, $code = 0)
	{
		$parameters = array();

		if(!$message)
		{
			$message = $GLOBALS['APPLICATION']->GetException();
		}

		// exception extra data goes to log
		if($this->checkOfType(self::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE) && $message !== false)
		{
			$parameters['AUX']['ERROR'] = unserialize($message, ['allowed_classes' => false]);
		}

		parent::__construct(
			$message,
			$parameters,
			array(
				'CODE' => $code
			)
		);
	}

	/**
	 * @deprecated
	 */
	public static function renderErrorCode($e)
	{
		$errCode    = $e->getCode();
		$strErrCode = $errCode . '/';

		if ($e instanceof TasksException)
		{
			$strErrCode .= 'TE';

			foreach (self::$errSymbolsMap as $symbol => $code)
			{
				if ($code & $errCode)
					$strErrCode .= '/'.mb_substr($symbol, 3);
			}
		}
		elseif ($e instanceof CTaskAssertException)
			$strErrCode .= 'CTAE';
		else
			$strErrCode .= 'Unknown';

		return ($strErrCode);
	}

	public function isSerialized(): bool
	{
		try
		{
			$result = unserialize($this->getMessage(), ['allowed_classes' => false]);
			if ($result === false)
			{
				return false;
			}

			return true;
		}
		catch (ErrorException)
		{
			return false;
		}
	}
}


class CTasksPerHitOption
{
	public static function set($moduleId, $optionName, $value)
	{
		self::managePerHitOptions('write', $moduleId, $optionName, $value);
	}


	public static function get($moduleId, $optionName)
	{
		return (self::managePerHitOptions('read', $moduleId, $optionName));
	}


	public static function getHitTimestamp()
	{
		static $t = null;

		if ($t === null)
			$t = time();

		return ($t);
	}


	private static function managePerHitOptions($operation, $moduleId, $optionName, $value = null)
	{
		static $arOptions = array();

		$oName = $moduleId . '::' . $optionName;

		if ( ! array_key_exists($oName, $arOptions) )
			$arOptions[$oName] = null;

		$rc = null;

		if ($operation === 'read')
			$rc = $arOptions[$oName];
		elseif ($operation === 'write')
			$arOptions[$oName] = $value;
		else
			CTaskAssert::assert(false);

		return ($rc);
	}
}


function tasksFormatDate($in_date)
{
	$date = $in_date;
	$strDate = false;

	if (!is_int($in_date))
		$date = MakeTimeStamp($in_date);

	if ( ($date === false) || ($date === -1) || ($date === 0) )
		$date = MakeTimeStamp ($in_date);

	// It can be other date on server (relative to client), ...
	$bTzWasDisabled = ! CTimeZone::enabled();

	if ($bTzWasDisabled)
		CTimeZone::enable();

	$ts = time() + CTimeZone::getOffset();		// ... so shift cur timestamp to compensate it.

	if ($bTzWasDisabled)
		CTimeZone::disable();

	$curDateStrAtClient       = date('d.m.Y', $ts);
	$yesterdayDateStrAtClient = date('d.m.Y', strtotime('-1 day', $ts));


	if ($curDateStrAtClient === date('d.m.Y', $date))
	{
		$strDate = FormatDate("today", $date);
	}
	elseif ($yesterdayDateStrAtClient === date('d.m.Y', $date))
	{
		$strDate = FormatDate("yesterday", $date);
	}
	//	disabled, since it is not clear for foreigners
	//	elseif (date("Y", $now) == date("Y", $date))
	//	{
	//		$strDate = ToLower(FormatDate("j F", $date));
	//	}
	else
	{
		if (defined('FORMAT_DATE'))
		{
			$strDate = \Bitrix\Tasks\UI::formatDateTime($date, FORMAT_DATE);
		}
		else
			$strDate = FormatDate("d.m.Y", $date);
	}

	return $strDate;
}

/**
 * @param $arParams
 * @return mixed|string
 * @deprecated
 */
function tasksPeriodToStr($arParams)
{
	return \Bitrix\Tasks\UI\Task\Template::makeReplicationPeriodString($arParams);
}


function taskMessSuffix($number)
{
	switch ($number)
	{
		case 2:
			return "_ND";
		case 3:
			return "_RD";
		default:
			return "_TH";
	}
}

function tasksFormatName($name, $lastName, $login, $secondName = "", $nameTemplate = "", $bEscapeSpecChars = false)
{
	if ($nameTemplate != "")
	{
		$result = CUser::FormatName($nameTemplate, array(	"NAME" 			=> $name,
															"LAST_NAME" 	=> $lastName,
															"SECOND_NAME" 	=> $secondName,
															"LOGIN"			=> $login),
			true,
			$bEscapeSpecChars);

		return $result;
	}

	if ($name || $lastName)
	{
		$rc = $name.($name && $lastName ? " " : "").$lastName;
	}
	else
	{
		$rc = $login;
	}

	if ($bEscapeSpecChars)
		$rc = htmlspecialcharsbx($rc);

	return ($rc);
}

function tasksFormatNameShort($name, $lastName, $login, $secondName = "", $nameTemplate = "", $bEscapeSpecChars = false)
{
	if ($nameTemplate != "")
	{
		$result = CUser::FormatName($nameTemplate, array(	"NAME" 			=> $name,
															"LAST_NAME" 	=> $lastName,
															"SECOND_NAME" 	=> $secondName,
															"LOGIN"			=> $login),
			true,
			$bEscapeSpecChars);

		return $result;
	}

	if ($name && $lastName)
	{
		if ( ! $bEscapeSpecChars )
			$rc = $lastName." ".mb_substr(htmlspecialcharsBack($name), 0, 1).".";
		else
			$rc = $lastName." ".mb_substr($name, 0, 1).".";
	}
	elseif ($lastName)
	{
		$rc = $lastName;
	}
	elseif ($name)
	{
		$rc = $name;
	}
	else
	{
		$rc = $login;
	}

	if ($bEscapeSpecChars)
		$rc = htmlspecialcharsbx($rc);

	return ($rc);
}


function tasksFormatHours($hours)
{
	$hoursOriginal = $hours = intval($hours);

	$hours %= 100;
	if ($hours >= 5 && $hours <= 20)
		return $hoursOriginal. " ".GetMessage("TASKS_HOURS_P");

	$hours %= 10;
	if ($hours == 1)
		return $hoursOriginal. " ".GetMessage("TASKS_HOURS_N");

	if ($hours >= 2 && $hours <= 4)
		return $hoursOriginal. " ".GetMessage("TASKS_HOURS_G");

	return $hoursOriginal. " ".GetMessage("TASKS_HOURS_P");

}


function tasksTimeCutZeros($time)
{
	if (IsAmPmMode())
	{
		return trim(mb_substr($time, 11, 11) == "12:00:00 am"? mb_substr($time, 0, 10) : mb_substr($time, 0, 22));
	}
	else
	{
		return mb_substr($time, 11, 8) == "00:00:00"? mb_substr($time, 0, 10) : mb_substr($time, 0, 16);
	}

}

/**@deprecated
 *
 * @param $task
 * @param $arPaths
 * @param string $site_id
 * @param bool $bGantt
 * @param bool $top
 * @param bool $bSkipJsMenu
 * @param array $params
 */
function tasksGetItemMenu($task, $arPaths, $site_id = SITE_ID, $bGantt = false, $top = false, $bSkipJsMenu = false, array $params = array())
{
	$userId = \Bitrix\Tasks\Util\User::getId();

	$arAllowedTaskActions = array();
	if (isset($task['META:ALLOWED_ACTIONS']))
	{
		$arAllowedTaskActions = $task['META:ALLOWED_ACTIONS'];
	}
	elseif ($task['ID'])
	{
		$oTask = CTaskItem::getInstanceFromPool($task['ID'], $userId);
		$arAllowedTaskActions = $oTask->getAllowedTaskActionsAsStrings();
		$task['META:ALLOWED_ACTIONS'] = $arAllowedTaskActions;
	}

	$analyticsSectionCode = $task['GROUP_ID']
		? \Bitrix\Tasks\Helper\Analytics::SECTION['project']
		: \Bitrix\Tasks\Helper\Analytics::SECTION['tasks']
	;

	$editUrl = \Bitrix\Tasks\Slider\Path\TaskPathMaker::getPath([
		"task_id" => $task["ID"],
		'user_id' => $userId,
		'group_id' => $task['GROUP_ID'],
		"action" => "edit"
	]);

	$viewUrl = new \Bitrix\Main\Web\Uri(
		\Bitrix\Tasks\Slider\Path\TaskPathMaker::getPath([
			'task_id' => $task['ID'],
			'user_id' => $userId,
			'group_id' => $task['GROUP_ID'],
			'action' => 'view'
		])
	);

	$addPath = \Bitrix\Tasks\Slider\Path\TaskPathMaker::getPath([
		"task_id" => 0,
		"action" => 'edit',
		'user_id' => $userId,
		'group_id' => $task['GROUP_ID']
	]);

	$viewUrl->addParams([
		'ta_sec' => $analyticsSectionCode,
		'ta_sub' => \Bitrix\Tasks\Helper\Analytics::SUB_SECTION['gantt'],
		'ta_el' => \Bitrix\Tasks\Helper\Analytics::ELEMENT['context_menu'],
	]);

	$subtaskUrl = new \Bitrix\Main\Web\Uri($addPath);
	$subtaskUrl->addParams([
		'PARENT_ID' => $task['ID'],
		'ta_sec' => $analyticsSectionCode,
		'ta_sub' => \Bitrix\Tasks\Helper\Analytics::SUB_SECTION['gantt'],
		'ta_el' => \Bitrix\Tasks\Helper\Analytics::ELEMENT['context_menu'],
	]);

	$copyUrl = new \Bitrix\Main\Web\Uri($addPath);
	$copyUrl->addParams([
		'COPY' => $task['ID'],
		'ta_sec' => $analyticsSectionCode,
		'ta_sub' => \Bitrix\Tasks\Helper\Analytics::SUB_SECTION['gantt'],
		'ta_el' => \Bitrix\Tasks\Helper\Analytics::ELEMENT['context_menu'],
	]);

	$inFavorite = false;
	if(
		isset($params['VIEW_STATE'])
		&& is_array($params['VIEW_STATE'])
		&& $params['VIEW_STATE']['SECTION_SELECTED']['CODENAME'] === 'VIEW_SECTION_ADVANCED_FILTER'
		&& ($params['VIEW_STATE']['SPECIAL_PRESET_SELECTED']['CODENAME'] ?? null) === 'FAVORITE'
	)
	{
		$inFavorite = true;
	}

	?>
		{
			text : "<?=GetMessage("TASKS_VIEW_TASK")?>",
			title : "<?=GetMessage("TASKS_VIEW_TASK_EX")?>",
			className : "menu-popup-item-view",
			href : "<? echo CUtil::JSEscape($viewUrl->getUri())?>"
		},

		<? if ($arAllowedTaskActions['ACTION_EDIT']):?>
		{
			text : "<?=GetMessage("TASKS_EDIT_TASK")?>",
			title : "<?=GetMessage("TASKS_EDIT_TASK_EX")?>",
			className : "menu-popup-item-edit",
			href : "<? echo CUtil::JSEscape($editUrl)?>"
		},
		<? endif?>

		{
			text : "<?=GetMessage("TASKS_ADD_SUBTASK"); ?>",
			title : "<?=GetMessage("TASKS_ADD_SUBTASK"); ?>",
			className : "menu-popup-item-create",
			href : "<? echo CUtil::JSEscape($subtaskUrl->getUri())?>"
		},

		<?

		if ($bGantt && ($arAllowedTaskActions['ACTION_EDIT'] || $arAllowedTaskActions['ACTION_CHANGE_DEADLINE']))
		{
			?>
			{
				text : "<? if(!$task["DEADLINE"]):?><?=GetMessage("TASKS_ADD_DEADLINE")?><? else:?><?=GetMessage("TASKS_REMOVE_DEADLINE")?><? endif?>",
				title : "<? if(!$task["DEADLINE"]):?><?=GetMessage("TASKS_ADD_DEADLINE")?><? else:?><?=GetMessage("TASKS_REMOVE_DEADLINE")?><? endif?>",
				className : "<? if(!$task["DEADLINE"]):?>task-menu-popup-item-add-deadline<? else:?>task-menu-popup-item-remove-deadline<? endif?>",
				onclick : BX.CJSTask.fixWindow(function(window, top, event, item)
				{
					var BX = top.BX;

					if (BX.hasClass(item.layout.item, "task-menu-popup-item-add-deadline"))
					{
						BX.removeClass(item.layout.item, "task-menu-popup-item-add-deadline");
						BX.addClass(item.layout.item, "task-menu-popup-item-remove-deadline");
						item.layout.text.innerHTML = "<?=GetMessage("TASKS_REMOVE_DEADLINE")?>";

						var deadline = BX.GanttChart.convertDateFromUTC(this.params.task.dateEnd);
						deadline.setDate(deadline.getDate() + 3);

						if(typeof top.COMPANY_WORKTIME != 'undefined')
							deadline = BX.CJSTask.addTimeToDate(deadline, top.COMPANY_WORKTIME);

						this.params.task.setDateDeadline(deadline);
						this.params.task.redraw();
						this.popupWindow.close();

						// this should pass through
						var data = {
							mode : "deadline",
							sessid : BX.message("bitrix_sessid"),
							id : this.params.task.id,
							deadline : top.tasksFormatDate(deadline)
						};
						BX.ajax.post(top.ajaxUrl, data);
					}
					else
					{
						BX.removeClass(item.layout.item, "task-menu-popup-item-remove-deadline");
						BX.addClass(item.layout.item, "task-menu-popup-item-add-deadline");
						item.layout.text.innerHTML = "<?=GetMessage("TASKS_ADD_DEADLINE")?>";
						this.params.task.setDateDeadline(null);
						this.params.task.redraw();
						this.popupWindow.close();

						var data = {
							mode : "deadline",
							sessid : BX.message("bitrix_sessid"),
							id : this.params.task.id,
							deadline : ""
						};
						BX.ajax.post(top.ajaxUrl, data);
					}
				})
			},
			<?
		}

		if ($arAllowedTaskActions['ACTION_ADD_FAVORITE'])
		{
			?>{
				text : "<?=GetMessage("ACTION_ADD_FAVORITE")?>",
				title : "<?=GetMessage("ACTION_ADD_FAVORITE")?>",
				className : "task-menu-popup-item-favorite",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.AddToFavorite) || (top && top.AddToFavorite) || BX.DoNothing;
					fn(<?= (int)$task["ID"] ?>);
					this.popupWindow.close();
				})
			},
			<?
		}

		if ($arAllowedTaskActions['ACTION_DELETE_FAVORITE'])
		{
			?>{
				text : "<?=GetMessage("ACTION_DELETE_FAVORITE")?>",
				title : "<?=GetMessage("ACTION_DELETE_FAVORITE")?>",
				className : "task-menu-popup-item-favorite",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.DeleteFavorite) || (top && top.DeleteFavorite) || BX.DoNothing;
					fn(<?= (int)$task["ID"] ?>, {mode: 'delete-subtree', rowDelete: <?=($inFavorite ? 'true' : 'false')?>});
					this.popupWindow.close();
				})
			},
			<?
		}

		if ($arAllowedTaskActions['ACTION_COMPLETE'])
		{
			?>{
				text : "<?=GetMessage("TASKS_CLOSE_TASK")?>",
				title : "<?=GetMessage("TASKS_CLOSE_TASK")?>",
				className : "menu-popup-item-complete",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.CloseTask) || (top && top.CloseTask) || BX.DoNothing;
					fn(<?= (int)$task["ID"] ?>, '<?= $analyticsSectionCode ?>');
					this.popupWindow.close();
				})
			},<?
		}

	if ($arAllowedTaskActions['ACTION_START'])
		{
			?>{
				text : "<?=GetMessage("TASKS_START_TASK")?>",
				title : "<?=GetMessage("TASKS_START_TASK")?>",
				className : "menu-popup-item-begin",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.StartTask) || (top && top.StartTask) || BX.DoNothing;
					fn(<?= (int)$task["ID"] ?>);
					this.popupWindow.close();
				})
			},<?
		}

		if ($arAllowedTaskActions['ACTION_PAUSE'])
		{
			?>{
				text : "<?=GetMessage("TASKS_PAUSE_TASK")?>",
				title : "<?=GetMessage("TASKS_PAUSE_TASK")?>",
				className : "task-menu-popup-item-pause",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.PauseTask) || (top && top.PauseTask) || BX.DoNothing;
					fn(<?= (int)$task["ID"] ?>);
					this.popupWindow.close();
				})
			},<?
		}

		if ($arAllowedTaskActions['ACTION_RENEW'])
		{
			?>{
				text : "<?=GetMessage("TASKS_RENEW_TASK")?>",
				title : "<?=GetMessage("TASKS_RENEW_TASK")?>",
				className : "menu-popup-item-reopen",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.RenewTask) || (top && top.RenewTask) || BX.DoNothing;
					fn(<?= (int)$task["ID"] ?>);
					this.popupWindow.close();
				})
			},<?
		}

		if ($arAllowedTaskActions['ACTION_DEFER'])
		{
			?>{
				text : "<?=GetMessage("TASKS_DEFER_TASK")?>",
				title : "<?=GetMessage("TASKS_DEFER_TASK")?>",
				className : "menu-popup-item-hold",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.DeferTask) || (top && top.DeferTask) || BX.DoNothing;
					fn(<?= (int)$task["ID"] ?>);
					this.popupWindow.close();
				})
			},<?
		}

		if ($arAllowedTaskActions['ACTION_APPROVE'])
		{
			?>{
				text : "<?=GetMessage("TASKS_APPROVE_TASK")?>",
				title : "<?=GetMessage("TASKS_APPROVE_TASK")?>",
				className : "menu-popup-item-accept",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.tasksListNS) || (top && top.tasksListNS) || BX.DoNothing;
					fn.approveTask(<?= (int)$task["ID"] ?>);
					this.popupWindow.close();
				})
			},<?
		}

		if ($arAllowedTaskActions['ACTION_DISAPPROVE'])
		{
			?>{
				text : "<?=GetMessage("TASKS_REDO_TASK_MSGVER_1")?>",
				title : "<?=GetMessage("TASKS_REDO_TASK_MSGVER_1")?>",
				className : "menu-popup-item-remake",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.tasksListNS) || (top && top.tasksListNS) || BX.DoNothing;
					fn.disapproveTask(<?= (int)$task["ID"] ?>);
					this.popupWindow.close();
				})
			},<?
		}

		?>

		{
			text : "<?=GetMessage("TASKS_COPY_TASK")?>",
			title : "<?=GetMessage("TASKS_COPY_TASK_EX")?>",
			className : "menu-popup-item-copy",
			href : "<? echo CUtil::JSEscape($copyUrl->getUri())?>"
		},

		<?

		// Only responsible person and accomplices can add task to day plan
		// And we must be not at extranet site
		if (
			(
			$task["RESPONSIBLE_ID"] == $userId
			|| (
				is_array($task['ACCOMPLICES'] ?? null)
				&& in_array($userId, $task['ACCOMPLICES'])
				)
			)
			&& (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite())
		)
		{
			$arTasksInPlan = CTaskPlannerMaintance::getCurrentTasksList();

			// If not in day plan already
			if (!(is_array($arTasksInPlan) && in_array($task["ID"], $arTasksInPlan)))
			{
				?>
				{
					text : "<?=GetMessage("TASKS_ADD_TASK_TO_TIMEMAN")?>",
					title : "<?=GetMessage("TASKS_ADD_TASK_TO_TIMEMAN_EX")?>",
					className : "menu-popup-item-add-to-tm",
					onclick : BX.CJSTask.fixWindow(function(window, top, event, item) {
						var fn = (window && window.Add2Timeman) || (top && top.Add2Timeman) || BX.DoNothing;
						fn(this, <?=intval($task["ID"])?>);
					})
				},<?
			}
		}

		if ($arAllowedTaskActions['ACTION_REMOVE'])
		{
			?>
			{
				text : "<?=GetMessage("TASKS_DELETE_TASK")?>",
				title : "<?=GetMessage("TASKS_DELETE_TASK")?>",
				className : "menu-popup-item-delete",
				onclick : BX.CJSTask.fixWindow(function(window, top, event)
				{
					var fn = (window && window.DeleteTask) || (top && top.DeleteTask) || BX.DoNothing;
					this.menuItems = [];
					this.bindElement.onclick = function() { return (false); };
					fn(<?= (int)$task["ID"] ?>);
					this.popupWindow.close();
				})
			},<?
		}
		?>
		{}
	<?
}


function tasksRenderListItem($task, $childrenCount, $arPaths, $depth = 0,
	$plain = false, $defer = false, $site_id = SITE_ID, $updatesCount = 0,
	$projectExpanded = true, $taskAdded = false,
	$componentName = "bitrix:tasks.list.item", $componentTemplate = ".default",
	$userNameTemplate = "", $arAllowedTaskActions = null, $ynIframe = 'N'
)
{
	global $APPLICATION;

	$APPLICATION->IncludeComponent(
		$componentName, $componentTemplate, array(
			"TASK" => $task,
			"CHILDREN_COUNT" => $childrenCount,
			"PATHS" => $arPaths,
			"DEPTH" => $depth,
			"PLAIN" => $plain,
			"DEFER" => $defer,
			"SITE_ID" => $site_id,
			"UPDATES_COUNT" => $updatesCount,
			"PROJECT_EXPANDED" => $projectExpanded,
			"TASK_ADDED" => $taskAdded,
			'ALLOWED_ACTIONS' => $arAllowedTaskActions,
			'IFRAME'          => $ynIframe,
			"NAME_TEMPLATE" => $userNameTemplate
		), null, array("HIDE_ICONS" => "Y")
	);
}

function templatesGetListItemActions($template, $arPaths)
{
	$addTaskUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TASKS_TASK_ADD_BY_TEMPLATE"], array("task_id" => 0, "action" => "edit"));
	$addTaskUrl .= (mb_strpos($addTaskUrl, "?") === false ? "?" : "&")."TEMPLATE=".$template["ID"];

	$viewUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => $template["ID"], "action" => "view"));
	$editUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => $template["ID"], "action" => "edit"));
	$addSubTmplUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"))."?BASE_TEMPLATE=".intval($template["ID"]);
	$copyUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"))."?COPY=".intval($template["ID"]);
	?>

	{ text : "<?php echo GetMessage("TASKS_VIEW_TASK")?>", title : "<?php echo GetMessage("TASKS_VIEW_TASK")?>", className : "menu-popup-item-view", href : "<?php echo CUtil::JSEscape($viewUrl)?>" },

	<?if(!intval($template['BASE_TEMPLATE_ID']) && $template['TPARAM_TYPE'] != CTaskTemplates::TYPE_FOR_NEW_USER):?>
		{ text : "<?php echo GetMessage("TASKS_ADD_TEMPLATE_TASK")?>", title : "<?php echo GetMessage("TASKS_ADD_TEMPLATE_TASK")?>", className : "menu-popup-item-create", href : "<?php echo CUtil::JSEscape($addTaskUrl)?>" },
	<?endif?>

	<?if($template['TPARAM_TYPE'] != CTaskTemplates::TYPE_FOR_NEW_USER):?>
		{ text : "<?=GetMessage("TASKS_ADD_SUB_TEMPLATE")?>", title : "<?php echo GetMessage("TASKS_ADD_SUB_TEMPLATE")?>", className : "menu-popup-item-create", href : "<?=CUtil::JSEscape($addSubTmplUrl)?>" },
	<?endif?>

	{ text : "<?=GetMessage("TASKS_TEMPLATE_COPY")?>", title : "<?php echo GetMessage("TASKS_TEMPLATE_COPY")?>", className : "menu-popup-item-copy", href : "<?=CUtil::JSEscape($copyUrl)?>" },

	<?if($template['ALLOWED_ACTIONS']['UPDATE']):?>
		{ text : "<?php echo GetMessage("TASKS_EDIT_TASK")?>", title : "<?php echo GetMessage("TASKS_EDIT_TASK")?>", className : "menu-popup-item-edit", href : "<?php echo CUtil::JSEscape($editUrl)?>" },
	<?endif?>
	<?if($template['ALLOWED_ACTIONS']['DELETE']):?>
		{ text : "<?php echo GetMessage("TASKS_DELETE_TASK")?>", title : "<?php echo GetMessage("TASKS_DELETE_TASK")?>", className : "menu-popup-item-delete", onclick : function() { if(confirm("<?php echo GetMessage("TASKS_DELETE_TASKS_CONFIRM")?>")){this.menuItems = []; DeleteTemplate(<?php echo $template["ID"]?>);} this.popupWindow.close(); } },
	<?endif?>

	<?
}

function templatesRenderListItem($template, $arPaths, $depth = 0, $plain = false, $defer = false, $nameTemplate = "")
{
	$anchor_id = RandString(8);

	$addUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"));
	$addUrl .= (mb_strpos($addUrl, "?") === false ? "?" : "&")."TEMPLATE=".$template["ID"];
	$editUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => $template["ID"], "action" => "edit"));
	?>
	<script<?php echo $defer ? "  defer=\"defer\"" : ""?>>
		tasksMenuPopup[<?php echo $template["ID"]?>] = [
			<?templatesGetListItemActions($template, $arPaths)?>
		];
	</script>
	<tr class="task-list-item task-depth-<?php echo $depth?>" id="template-<?php echo $template["ID"]?>" ondblclick="jsUtils.Redirect([], '<?php echo CUtil::JSEscape(CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => $template["ID"], "action" => "edit")))?>');" title="<?php echo GetMessage("TASKS_DOUBLE_CLICK")?>">
		<td class="task-title-column">
			<div class="task-title-container">
				<div class="task-title-info">
					<?php if ($template["MULTITASK"] == "Y"):?><span class="task-title-multiple" title="<?php echo GetMessage("TASKS_MULTITASK")?>"></span><?php endif?><a href="<?php echo CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => $template["ID"], "action" => "edit"))?>" class="task-title-link" title=""><?php echo $template["TITLE"]?></a>
				</div>
			</div>
		</td>
		<td class="task-menu-column"><a href="javascript: void(0)" class="task-menu-button" onclick="return ShowMenuPopup(<?php echo $template["ID"]?>, this);" title="<?php echo GetMessage("TASKS_MENU")?>"><i class="task-menu-button-icon"></i></a></td>
		<td class="task-flag-column">&nbsp;</td>
		<td class="task-priority-column">
			<i class="task-priority-icon task-priority-<?php if ($template["PRIORITY"] == 0):?>low<?php elseif ($template["PRIORITY"] == 2):?>high<?php else:?>medium<?php endif?>" title="<?php echo GetMessage("TASKS_PRIORITY")?>: <?php echo GetMessage("TASKS_PRIORITY_".$template["PRIORITY"])?>"></i>
		</td>
		<td class="task-deadline-column"><?php if ($template["DEADLINE"]):?><span class="task-deadline-datetime"><span class="task-deadline-date"><?php echo tasksFormatDate($template["DEADLINE"])?></span></span><?php if(date("H:i", strtotime($template["DEADLINE"])) != "00:00"):?> <span class="task-deadline-time"><?php echo date("H:i", strtotime($template["DEADLINE"]))?></span><?php endif?><?php else:?>&nbsp;<?php endif?></td>
		<td class="task-responsible-column"><a class="task-responsible-link" href="<?php echo CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_USER_PROFILE"], array("user_id" => $template["RESPONSIBLE_ID"]))?>" id="anchor_responsible_<?php echo $anchor_id?>" bx-tooltip-user-id="<?=$template["RESPONSIBLE_ID"]?>"><?php echo tasksFormatNameShort($template["RESPONSIBLE_NAME"], $template["RESPONSIBLE_LAST_NAME"], $template["RESPONSIBLE_LOGIN"], $template["RESPONSIBLE_SECOND_NAME"], $nameTemplate)?></a></td>
		<td class="task-director-column"><a class="task-director-link" href="<?php echo CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_USER_PROFILE"], array("user_id" => $template["CREATED_BY"]))?>" id="anchor_created_<?php echo $anchor_id?>" bx-tooltip-user-id="<?=$template["CREATED_BY"]?>"><?php echo tasksFormatNameShort($template["CREATED_BY_NAME"], $template["CREATED_BY_LAST_NAME"], $template["CREATED_BY_LOGIN"], $template["CREATED_BY_SECOND_NAME"], $nameTemplate)?></a></td>
		<td class="task-grade-column">&nbsp;</td>
		<td class="task-complete-column">&nbsp;</td>
	</tr>
	<?php
}

/**@deprecated
 *
 * @param $arTask
 * @param $childrenCount
 * @param $arPaths
 * @param bool $bParent
 * @param bool $bGant
 * @param bool $top
 * @param string $nameTemplate
 * @param array $arAdditionalFields
 * @param bool $bSkipJsMenu
 * @param array $params
 *
 */
function tasksRenderJSON(
	$arTask, $childrenCount, $arPaths, $bParent = false, $bGant = false,
	$top = false, $nameTemplate = "", $arAdditionalFields = array(), $bSkipJsMenu = false, array $params = array()
)
{
	$userId = \Bitrix\Tasks\Util\User::getId();

	if (array_key_exists('USER_ID', $params))
	{
		$profileUserId = (int)$params['USER_ID'];
	}
	else
	{
		$profileUserId = $userId;
	}

	$arAllowedTaskActions = array();
	if (isset($arTask['META:ALLOWED_ACTIONS']))
		$arAllowedTaskActions = $arTask['META:ALLOWED_ACTIONS'];
	elseif ($arTask['ID'])
	{
		$oTask = CTaskItem::getInstanceFromPool($arTask['ID'], $userId);
		$arAllowedTaskActions = $oTask->getAllowedTaskActionsAsStrings();
		$arTask['META:ALLOWED_ACTIONS'] = $arAllowedTaskActions;
	}

	$runningTaskId = $runningTaskTimer = null;
	if (
		isset($arTask['ALLOW_TIME_TRACKING'])
		&& $arTask['ALLOW_TIME_TRACKING'] === 'Y'
	)
	{
		$oTimer           = CTaskTimerManager::getInstance($userId);
		$runningTaskData  = $oTimer->getRunningTask(false);
		if ($runningTaskData && is_array($runningTaskData))
		{
			$runningTaskId    = $runningTaskData['TASK_ID'];
			$runningTaskTimer = time() - $runningTaskData['TIMER_STARTED_AT'];
		}
	}

	$canCreateTasks = false;
	$canEditTasks = false;
	if ($arTask["GROUP_ID"])
	{
		$canCreateTasks = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $arTask["GROUP_ID"], "tasks", "create_tasks");
		$canEditTasks = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $arTask["GROUP_ID"], "tasks", "edit_tasks");
	}
	?>
	{
		id : <?=intval($arTask["ID"])?>,
		name : "<?=CUtil::JSEscape($arTask["TITLE"])?>",
		<?if ($arTask["GROUP_ID"]):?>
			projectId : <?=intval($arTask["GROUP_ID"])?>,
			projectName : '<?=CUtil::JSEscape($arTask['GROUP_NAME'] ?? null)?>',
			projectCanCreateTasks: <?=CUtil::PhpToJSObject($canCreateTasks)?>,
			projectCanEditTasks: <?=CUtil::PhpToJSObject($canEditTasks)?>,
		<?else:?>
			projectId : 0,
		<?endif?>
		status : "<?=tasksStatus2String($arTask["STATUS"])?>",
		realStatus : "<?=intval($arTask["REAL_STATUS"])?>",
		url: '<?=CUtil::JSEscape(CComponentEngine::MakePathFromTemplate(
				$arPaths["PATH_TO_TASKS_TASK"],
				array(
					"task_id" => $arTask["ID"],
					"user_id" => $profileUserId,
					"action" => "view",
					"group_id" => $arTask["GROUP_ID"]
				)
			));?>',
		priority : <?=intval($arTask["PRIORITY"])?>,
		mark : <?php echo !$arTask["MARK"] ? "null" : "'".CUtil::JSEscape($arTask["MARK"])."'"?>,
		responsible: '<?=CUtil::JSEscape(tasksFormatNameShort($arTask["RESPONSIBLE_NAME"], $arTask["RESPONSIBLE_LAST_NAME"], $arTask["RESPONSIBLE_LOGIN"], $arTask["RESPONSIBLE_SECOND_NAME"], $nameTemplate))?>',
		director: '<?=CUtil::JSEscape(tasksFormatNameShort($arTask["CREATED_BY_NAME"], $arTask["CREATED_BY_LAST_NAME"], $arTask["CREATED_BY_LOGIN"], $arTask["CREATED_BY_SECOND_NAME"], $nameTemplate))?>',
		responsibleId : <?=intval($arTask["RESPONSIBLE_ID"])?>,
		directorId : <?=intval($arTask["CREATED_BY"])?>,
		responsible_name: '<?=CUtil::JSEscape($arTask["RESPONSIBLE_NAME"]); ?>',
		responsible_second_name: '<?=CUtil::JSEscape($arTask["RESPONSIBLE_SECOND_NAME"]); ?>',
		responsible_last_name: '<?=CUtil::JSEscape($arTask["RESPONSIBLE_LAST_NAME"]); ?>',
		responsible_login: '<?=CUtil::JSEscape($arTask["RESPONSIBLE_LOGIN"]); ?>',
		director_name: '<?=CUtil::JSEscape($arTask["CREATED_BY_NAME"]); ?>',
		director_second_name: '<?=CUtil::JSEscape($arTask["CREATED_BY_SECOND_NAME"]); ?>',
		director_last_name: '<?=CUtil::JSEscape($arTask["CREATED_BY_LAST_NAME"]); ?>',
		director_login: '<?=CUtil::JSEscape($arTask["CREATED_BY_LOGIN"]); ?>',
		dateCreated : <?tasksJSDateObject($arTask["CREATED_DATE"], $top)?>,

		<?php
			$links = $arTask['LINKS'] ?? null;
		?>
		links: <?=CUtil::PhpToJSObject($links, false, false, true)?>,

		<?php if ($arTask["START_DATE_PLAN"]):?>dateStart : <?php tasksJSDateObject($arTask["START_DATE_PLAN"], $top)?>,<?php else:?>dateStart: null,<?php endif?>

		<?php if ($arTask["END_DATE_PLAN"]):?>dateEnd : <?php tasksJSDateObject($arTask["END_DATE_PLAN"], $top)?>,<?php else:?>dateEnd: null,<?php endif?>

		<?php if ($arTask["DATE_START"]):?>dateStarted: <?php tasksJSDateObject($arTask["DATE_START"], $top)?>,<?php endif?>

		dateCompleted : <?php if ($arTask["CLOSED_DATE"]):?><?php tasksJSDateObject($arTask["CLOSED_DATE"], $top)?><?php else:?>null<?php endif?>,

		<?php if ($arTask["DEADLINE"]):?>dateDeadline : <?php tasksJSDateObject($arTask["DEADLINE"], $top)?>,<?php else:?>dateDeadline: null,<?php endif?>

		canEditPlanDates : <?php if ($arAllowedTaskActions['ACTION_CHANGE_DEADLINE']):?>true<?php else:?>false<?php endif?>,

		canEdit: <?=(isset($arTask["META:ALLOWED_ACTIONS"]) && $arTask["META:ALLOWED_ACTIONS"]["ACTION_EDIT"] ? "true" : "false")?>,

		<?if ($arTask["PARENT_ID"] && $bParent):?>
			parentTaskId : <?=intval($arTask["PARENT_ID"])?>,
		<?else:?>
			parentTaskId : 0,
		<?endif?>

		<?php
			if (isset($arTask["FILES"]) && is_array($arTask["FILES"]) && sizeof($arTask["FILES"])):
				$i = 0;
		?>
			files: [
				<?php
					foreach($arTask["FILES"] as $file):
						$i++;
				?>
				{ name : '<?php echo CUtil::JSEscape($file["ORIGINAL_NAME"])?>', url : '/bitrix/components/bitrix/tasks.task.detail/show_file.php?fid=<?=intval($file["ID"])?>', size : '<?php echo CUtil::JSEscape(CFile::FormatSize($file["FILE_SIZE"]))?>' }<?php if ($i != sizeof($arTask["FILES"])):?>,<?php endif?>
				<?php endforeach?>
			],
		<?php endif?>

		<?php
		if (($arTask['ACCOMPLICES'] ?? null) && is_array($arTask['ACCOMPLICES']))
		{
			$i = 0;
			echo 'accomplices: [';
			foreach($arTask['ACCOMPLICES'] as $ACCOMPLICE_ID)
			{
				if ($i++)
					echo ',';

				echo '{ id: ' . (int) $ACCOMPLICE_ID . ' }';
			}
			echo '], ';
		}
		?>

		<?php
		if (($arTask['AUDITORS'] ?? null) && is_array($arTask['AUDITORS']))
		{
			$i = 0;
			echo 'auditors: [';
			foreach($arTask['AUDITORS'] as $AUDITOR_ID)
			{
				if ($i++)
					echo ',';

				echo '{ id: ' . (int) $AUDITOR_ID . ' }';
			}
			echo '], ';
		}
		?>

		isSubordinate: <?php echo $arTask["SUBORDINATE"] == "Y" ? "true" : "false"?>,
		isInReport: <?php echo $arTask["ADD_IN_REPORT"] == "Y" ? "true" : "false"?>,
		hasChildren : <?php
			if (((int) $childrenCount) > 0)
				echo 'true';
			else
				echo 'false';
		?>,
		childrenCount : <?php echo (int) $childrenCount; ?>,
		canEditDeadline : <?php
			if ($arAllowedTaskActions['ACTION_CHANGE_DEADLINE'])
				echo 'true';
			else
				echo 'false';
		?>,
		canStartTimeTracking : <?php if ($arAllowedTaskActions['ACTION_START_TIME_TRACKING']):?>true<?php else:?>false<?php endif?>,
		ALLOW_TIME_TRACKING : <?php
			if (isset($arTask['ALLOW_TIME_TRACKING']) && ($arTask['ALLOW_TIME_TRACKING'] === 'Y'))
				echo 'true';
			else
				echo 'false';
		?>,
		matchWorkTime: <?=($arTask['MATCH_WORK_TIME'] == 'Y' ? 'true' : 'false')?>,
		TIMER_RUN_TIME : <?php if ($runningTaskId == $arTask['ID']) echo (int) $runningTaskTimer; else echo 'false'; ?>,
		TIME_SPENT_IN_LOGS : <?php echo (int) $arTask['TIME_SPENT_IN_LOGS']; ?>,
		TIME_ESTIMATE : <?php echo (int) $arTask['TIME_ESTIMATE']; ?>,
		IS_TASK_TRACKING_NOW : <?php if ($runningTaskId == $arTask['ID']) echo 'true'; else echo 'false'; ?>,
		menuItems: [<?php tasksGetItemMenu($arTask, $arPaths, SITE_ID, $bGant, $top, $bSkipJsMenu, $params)?>],

		<?$arTask['SE_PARAMETER'] = is_array($arTask['SE_PARAMETER'] ?? null) ? $arTask['SE_PARAMETER'] : [];?>
		<?$seParameters = array();?>
		<?foreach($arTask['SE_PARAMETER'] as $k => $v):?>
			<?if($v['VALUE'] == 'Y' || $v['VALUE'] == 'N'):?>
				<?
				$code = $v['CODE'];
				if($code == \Bitrix\Tasks\Internals\Task\ParameterTable::PARAM_SUBTASKS_AUTOCOMPLETE)
				{
					$code = 'completeTasksFromSubTasks';
				}
				elseif($code == \Bitrix\Tasks\Internals\Task\ParameterTable::PARAM_SUBTASKS_TIME)
				{
					$code = 'projectPlanFromSubTasks';
				}
				?>
				<?$seParameters[$code] = $v['VALUE'] == 'Y';?>
			<?endif?>
		<?endforeach?>
		parameters: <?=json_encode($seParameters)?>

		<?php
		foreach ($arAdditionalFields as $key => $value)
			echo ', ' . $key . ' : ' . $value . "\n";
		?>
	}
<?php
}


function tasksJSDateObject($date, $top = false)
{
	$ts = MakeTimeStamp($date);
	?>
	new <?php if ($top):?>top.<?php endif?>Date(<?php
		echo date("Y", $ts); ?>, <?php
		echo date("n", $ts) - 1; ?>, <?php
		echo date("j", $ts); ?>, <?php
		echo date("G", $ts); ?>, <?php
		echo (date("i", $ts) + 0); ?>, <?php
		echo (date("s", $ts) + 0); ?>)
	<?php
}


function tasksStatus2String($status)
{
	$arMap = [
		MetaStatus::EXPIRED => 'overdue',
		MetaStatus::UNSEEN => 'new',
		Status::NEW => 'accepted',
		MetaStatus::EXPIRED_SOON => 'overdue-soon',
		Status::PENDING => 'accepted',
		Status::IN_PROGRESS => 'in-progress',
		Status::SUPPOSEDLY_COMPLETED => 'waiting',
		Status::COMPLETED => 'completed',
		Status::DEFERRED => 'delayed',
		Status::DECLINED => 'declined',
	];

	$strStatus = "";
	if (isset($arMap[$status]))
		$strStatus = $arMap[$status];

	return $strStatus;
}


function tasksServerName($server_name = false)
{
	if (!$server_name)
	{
		if (defined("SITE_SERVER_NAME") && SITE_SERVER_NAME)
		{
			$server_name = SITE_SERVER_NAME;
		}
		else
		{
			$server_name = COption::GetOptionString("main", "server_name", $_SERVER['HTTP_HOST']);
		}
	}

	if (
		(mb_substr(mb_strtolower($server_name), 0, 8) !== 'https://')
		&& (mb_substr(mb_strtolower($server_name), 0, 7) !== 'http://')
	)
	{
		if (CMain::IsHTTPS())
			$server_name = 'https://' . $server_name;
		else
			$server_name = 'http://' . $server_name;
	}

	$server_name_wo_protocol = str_replace(
		array('http://', 'https://', 'HTTP://', 'HTTPS://'), 	// Yeah, I know: 'hTtpS://', ...
		array('', '', '', ''),
		$server_name
	);

	// Cutoff all what is after '/' (include '/' itself)
	$slashPos = mb_strpos($server_name_wo_protocol, '/');
	if ($slashPos >= 1)
	{
		$length = $slashPos;
		$server_name_wo_protocol = mb_substr(0, $length);
	}

	$isServerPortAlreadyGiven = false;
	if (mb_strpos($server_name_wo_protocol, ':') !== false)
		$isServerPortAlreadyGiven = true;

	$server_port = '';

	if (
		!$isServerPortAlreadyGiven
		&& isset($_SERVER['SERVER_PORT'])
		&& ($_SERVER['SERVER_PORT'] <> '')
		&& ($_SERVER['SERVER_PORT'] != '80')
		&& ($_SERVER['SERVER_PORT'] != '443')
	)
	{
		$server_port = ':' . $_SERVER['SERVER_PORT'];
	}

	if ( ! $isServerPortAlreadyGiven )
		$server_name .= $server_port;

	return ($server_name);
}


function tasksGetLastSelected($arManagers, $bSubordinateOnly = false, $nameTemplate = "")
{
	static $arLastUsers;

	$userId = \Bitrix\Tasks\Util\User::getId();

	if (!isset($arLastUsers))
	{
		$arSubDeps = CTasks::GetSubordinateDeps();

		$arLastSelected = CUserOptions::GetOption("tasks", "user_search", array());
		if (is_array($arLastSelected) && ($arLastSelected['last_selected'] ?? null) <> '')
			$arLastSelected = array_unique(explode(',', $arLastSelected['last_selected']));
		else
			$arLastSelected = false;

		if (is_array($arLastSelected))
		{
			$currentUser = array_search($userId, $arLastSelected);
			if ($currentUser !== false)
			{
				unset($arLastSelected[$currentUser]);
			}
			array_unshift($arLastSelected, $userId);
		}
		else
		{
			$arLastSelected = is_array($arLastSelected) ? $arLastSelected : [];

			$arLastSelected[] = $userId;
		}

		$arFilter = array('ACTIVE' => 'Y');
		if ($bSubordinateOnly)
		{
			$arFilter["UF_DEPARTMENT"] = $arSubDeps;
		}
		else
		{
			$arFilter['!UF_DEPARTMENT'] = false;
		}
		$arFilter['ID'] = is_array($arLastSelected) ? implode('|', array_slice($arLastSelected, 0, 10)) : '-1';
		$dbRes = CUser::GetList('last_name', 'asc', $arFilter, array('SELECT' => array('UF_DEPARTMENT')));
		$arLastUsers = array();
		while ($arRes = $dbRes->GetNext())
		{
			$arPhoto = array('IMG' => '');

			if (!$arRes['PERSONAL_PHOTO'])
			{
				switch ($arRes['PERSONAL_GENDER'])
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
				$arRes['PERSONAL_PHOTO'] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, isset($arParams['SITE_ID']) ? $arParams['SITE_ID'] : SITE_ID);
			}

			if ($arRes['PERSONAL_PHOTO'] > 0)
				$arPhoto = CIntranetUtils::InitImage($arRes['PERSONAL_PHOTO'], 30, 0, BX_RESIZE_IMAGE_EXACT);

			$arLastUsers[$arRes['ID']] = array(
				'ID' => $arRes['ID'],
				'NAME' => CUser::FormatName(empty($nameTemplate) ? CSite::GetNameFormat() : $nameTemplate, $arRes, true, false),
				'LOGIN' => $arRes['LOGIN'],
				'EMAIL' => $arRes['EMAIL'],
				'WORK_POSITION' => htmlspecialcharsBack($arRes['WORK_POSITION'] ? $arRes['WORK_POSITION'] : $arRes['PERSONAL_PROFESSION']),
				'PHOTO' => isset($arPhoto['CACHE']['src']) ? $arPhoto['CACHE']['src'] : "",
				'HEAD' => false,
				'SUBORDINATE' => is_array($arSubDeps) && is_array($arRes['UF_DEPARTMENT']) && array_intersect($arRes['UF_DEPARTMENT'], $arSubDeps) ? 'Y' : 'N',
				'SUPERORDINATE' => in_array($arRes["ID"], $arManagers) ? 'Y' : 'N'
			);
		}
	}

	return $arLastUsers;
}


define("TASKS_FILTER_SESSION_INDEX", "FILTER");


function tasksGetFilter($fieldName)
{
	if (isset($_GET[$fieldName]))
	{
		$_SESSION[TASKS_FILTER_SESSION_INDEX][$fieldName] = $_GET[$fieldName];
	}

	return $_SESSION[TASKS_FILTER_SESSION_INDEX][$fieldName];
}


function tasksPredefinedFilters($userID, $roleFilterSuffix = "")
{
	return array(
		"ROLE" => array(
			array("TITLE" => GetMessage("TASKS_FILTER_MY".$roleFilterSuffix), "FILTER" => array("DOER" => $userID), "CLASS" => "inbox", "COUNT" => "-", "STATUS_FILTER" => 0),
			array("TITLE" => GetMessage("TASKS_FILTER_RESPONSIBLE".$roleFilterSuffix), "FILTER" => array("RESPONSIBLE_ID" => $userID), "CLASS" => "my-responsibility", "COUNT" => "-", "STATUS_FILTER" => 0),
			array("TITLE" => GetMessage("TASKS_FILTER_ACCOMPLICE".$roleFilterSuffix), "FILTER" => array("ACCOMPLICE" => $userID), "CLASS" => "my-complicity", "COUNT" => "-", "STATUS_FILTER" => 0),
			array("TITLE" => GetMessage("TASKS_FILTER_IN_REPORT".$roleFilterSuffix), "FILTER" => array("RESPONSIBLE_ID" => $userID, "ADD_IN_REPORT" => "Y"), "CLASS" => "my-report", "COUNT" => "-", "STATUS_FILTER" => 0),
			array("TITLE" => GetMessage("TASKS_FILTER_CREATOR".$roleFilterSuffix), "FILTER" => array("CREATED_BY" => $userID), "CLASS" => "outbox", "COUNT" => "-", "STATUS_FILTER" => 1),
			array("TITLE" => GetMessage("TASKS_FILTER_FOR_REPORT".$roleFilterSuffix), "FILTER" => array("CREATED_BY" => $userID, "ADD_IN_REPORT" => "Y"), "CLASS" => "my-report", "COUNT" => "-", "STATUS_FILTER" => 1),
			array("TITLE" => GetMessage("TASKS_FILTER_AUDITOR".$roleFilterSuffix), "FILTER" => array("AUDITOR" => $userID), "CLASS" => "under-control", "COUNT" => "-", "STATUS_FILTER" => 0),
			array("TITLE" => GetMessage("TASKS_FILTER_ALL"), "FILTER" => array("MEMBER" => $userID), "CLASS" => "anybox", "COUNT" => "-", "STATUS_FILTER" => 0)
		),
		"STATUS" => array(
			array(
				array("TITLE" => GetMessage("TASKS_FILTER_ACTIVE"), "FILTER" => array("STATUS" => array(-2, -1, 1, 2, 3)), "CLASS" => "open", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_NEW"), "FILTER" => array("STATUS" => array(-2, 1)), "CLASS" => "new", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_IN_PROGRESS"), "FILTER" => array("STATUS" => 3), "CLASS" => "in-progress", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_ACCEPTED"), "FILTER" => array("STATUS" => 2), "CLASS" => "accepted", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_OVERDUE"), "FILTER" => array("STATUS" => -1), "CLASS" => "overdue", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_DELAYED"), "FILTER" => array("STATUS" => 6), "CLASS" => "delayed", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_CLOSED"), "FILTER" => array("STATUS" => array(4, 5)), "CLASS" => "completed", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_ALL"), "FILTER" => array(), "CLASS" => "any", "COUNT" => "-")
			),
			array(
				array("TITLE" => GetMessage("TASKS_FILTER_ACTIVE"), "FILTER" => array("STATUS" => array(-1, 1, 2, 3, 4, 7)), "CLASS" => "open", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_NOT_ACCEPTED"), "FILTER" => array("STATUS" => 1), "CLASS" => "new", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_IN_CONTROL"), "FILTER" => array("STATUS" => array(4, 7)), "CLASS" => "waiting", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_IN_PROGRESS"), "FILTER" => array("STATUS" => 3), "CLASS" => "in-progress", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_ACCEPTED"), "FILTER" => array("STATUS" => 2), "CLASS" => "accepted", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_OVERDUE"), "FILTER" => array("STATUS" => -1), "CLASS" => "overdue", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_DELAYED"), "FILTER" => array("STATUS" => 6), "CLASS" => "delayed", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_CLOSED"), "FILTER" => array("STATUS" => array(4, 5)), "CLASS" => "completed", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_ALL"), "FILTER" => array(), "CLASS" => "any", "COUNT" => "-")
			)
		)
	);
}

/**
 * @param $component
 * @param bool $bShowError
 * @param string $errText
 *
 * @deprecated
 */
function ShowInFrame(&$component, $bShowError = false, $errText = '')
{
	global $APPLICATION;

	$APPLICATION->RestartBuffer();
	?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LANGUAGE_ID?>" lang="<?=LANGUAGE_ID?>">
		<head><?php
			$APPLICATION->ShowHead();
			$APPLICATION->AddHeadString('
				<style>
				body {background: #fff !important; text-align: left !important; color: #000 !important;}
				div.bx-core-dialog-overlay {opacity: 0 !important; -moz-opacity: 0 !important; -khtml-opacity: 0 !important; filter:progid:DXImageTransform.Microsoft.Alpha(opacity=0) !important;}
				div#tasks-content-outer {padding: 15px;}
				.task-comment-content{ font-family:Verdana, sans-serif; padding-top:6px; word-wrap: break-word; width: 620px; overflow: hidden;}
				.task-detail-description { font-size:13px; color:#222; padding: 0 0 5px; word-wrap: break-word; width: 585px; overflow: hidden;}
				</style>
			', false, true);
		?></head>
		<body class="<?$APPLICATION->ShowProperty("BodyClass");?>" onload="if (window.top.BX.TasksIFrameInst) window.top.BX.TasksIFrameInst.onTaskLoaded();">
			<div id="tasks-content-outer">
				<table cellpadding="0" cellspading="0" width="100%">
					<tr>
						<td valign="top"><?php
							if ($bShowError)
							{
								?><div id="task-reminder-link"><?php
									ShowError($errText);
								?></div><?php
							}
							else
								$component->IncludeComponentTemplate();
						?></td>
						<?php if($APPLICATION->GetViewContent("sidebar_tools_1") <> ''): ?>
							<td width="10"></td>
							<td valign="top" width="230"><?php $APPLICATION->ShowViewContent("sidebar_tools_1") ?></td>
						<?php endif?>
					</tr>
				</table>
			</div>
		</body>
	</html><?
	require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
	die();
}


function __checkForum($forumID)
{
	if (!($settingsForumID = COption::GetOptionString("tasks", "task_forum_id")))
	{
		if ( (int) $forumID > 0 )
			COption::SetOptionString("tasks", "task_forum_id", intval($forumID));
	}

	if (IsModuleInstalled('extranet'))
	{
		if (-1 === COption::GetOptionString('tasks', 'task_extranet_forum_id', -1, $siteId = ''))
		{
			try
			{
				$extranetForumID = CTasksTools::GetForumIdForExtranet();
				COption::SetOptionString('tasks', 'task_extranet_forum_id', $extranetForumID, '', $siteId = '');
			}
			catch (TasksException $e)
			{
				COption::SetOptionString('tasks', 'task_extranet_forum_id', (int) $forumID, '', $siteId = '');
			}
		}
	}

	if (CModule::IncludeModule("forum") && $forumID && COption::GetOptionString("tasks", "forum_checked", false))
	{
		$arGroups = array();
		$rs = CGroup::GetList('id', 'asc');
		while($ar = $rs->Fetch())
			$arGroups[$ar['ID']] = 'A';

		CForumNew::Update($forumID, array("GROUP_ID"=>$arGroups, "INDEXATION" => "Y"));
		COption::RemoveOption("tasks", "forum_checked");
	}
}


/**
 * This function is deprecated. See CTaskFiles::removeTemporaryFile()
 *
 * @deprecated
 */
function deleteUploadedFiles($arFileIDs)
{
	$arFileIDs = (array) $arFileIDs;
	foreach($arFileIDs as $fileID)
	{
		$key = array_search(intval($fileID), $_SESSION["TASKS_UPLOADED_FILES"]);
		if ($key !== false)
		{
			unset($_SESSION["TASKS_UPLOADED_FILES"][$key]);
		}
	}
}


/**
 * This function is deprecated. See CTaskFiles::saveFileTemporary()
 *
 * @deprecated
 */
function addUploadedFiles($arFileIDs)
{
	$arFileIDs = (array) $arFileIDs;
	if (!is_array($_SESSION["TASKS_UPLOADED_FILES"]))
		$_SESSION["TASKS_UPLOADED_FILES"] = array();
	$_SESSION["TASKS_UPLOADED_FILES"] = array_merge($_SESSION["TASKS_UPLOADED_FILES"], $arFileIDs);
}


/**
 * This function is deprecated.
 *
 * @deprecated
 */
function cleanupUploadedFiles()
{
	if (isset($_SESSION["TASKS_UPLOADED_FILES"]) && is_array($_SESSION["TASKS_UPLOADED_FILES"]))
	{
		foreach($_SESSION["TASKS_UPLOADED_FILES"] as $fileID)
		{
			CFile::Delete($fileID);
		}
		$_SESSION["TASKS_UPLOADED_FILES"] = array();
	}
}


if ( ! function_exists('tasksFormatFileSize') )
{
	function tasksFormatFileSize($in)
	{
		return(CFile::FormatSize($in));
	}
}