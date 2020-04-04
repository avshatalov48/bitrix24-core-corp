<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Grid;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\UserField;

\Bitrix\Main\UI\Extension::load("ui.notification");

$folder = $this->GetFolder();

if (\Bitrix\Main\ModuleManager::isModuleInstalled('rest'))
{
	$APPLICATION->IncludeComponent(
		'bitrix:app.placement',
		'menu',
		array(
			'PLACEMENT'         => "TASK_LIST_CONTEXT_MENU",
			"PLACEMENT_OPTIONS" => array(),
			//			'INTERFACE_EVENT' => 'onCrmLeadListInterfaceInit',
			'MENU_EVENT_MODULE' => 'tasks',
			'MENU_EVENT'        => 'onTasksBuildContextMenu',
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
}


$arResult['TEMPLATE_DATA'] = array(
	'EXTENSION_ID' => 'tasks_task_list_component_ext_'.md5($folder)
);

CJSCore::Init(array('tasks_util_query', 'task_popups'));

//region TITLE
if ($arParams['GROUP_ID'] > 0)
{
	$sTitle = $sTitleShort = GetMessage("TASKS_TITLE_GROUP_TASKS");
}
else
{
	if ($arParams["USER_ID"] == Util\User::getId())
	{
		$sTitle = $sTitleShort = GetMessage("TASKS_TITLE_MY");
	}
	else
	{
		$sTitle = CUser::FormatName($arParams["NAME_TEMPLATE"], $arResult["USER"], true, false).
				  ": ".
				  GetMessage("TASKS_TITLE");
		$sTitleShort = GetMessage("TASKS_TITLE");
	}
}
$APPLICATION->SetPageProperty("title", $sTitle);
$APPLICATION->SetTitle($sTitleShort);
//endregion TITLE

if (isset($arParams["SET_NAVCHAIN"]) && $arParams["SET_NAVCHAIN"] != "N")
{
	$APPLICATION->AddChainItem(GetMessage("TASKS_TITLE"));
}
/** END:TITLE */

$arParams['HEADERS'] = array(
	'ID' => array(
		'id' => 'ID',
		'name' => GetMessage('TASKS_COLUMN_ID'),
		'sort' => 'ID',
		'first_order' => 'desc',
		'editable' => false
	),
	'TITLE' => array(
		'id' => 'TITLE',
		'name' => GetMessage('TASKS_COLUMN_TITLE'),
		'sort' => 'TITLE',
		'first_order' => 'desc',
		'editable' => false,
		'type' => 'custom',
		'prevent_default' => false,
		'shift' => true
	),

	'DEADLINE' => array(
		'id' => 'DEADLINE',
		'name' => GetMessage('TASKS_COLUMN_DEADLINE'),
		'sort' => 'DEADLINE',
		'first_order' => 'desc',
		'editable' => false
	),

	'ORIGINATOR_NAME' => array(
		'id' => 'ORIGINATOR_NAME',
		'name' => GetMessage('TASKS_COLUMN_ORIGINATOR_NAME'),
		'sort' => 'ORIGINATOR_NAME',
		'first_order' => 'desc',
		'editable' => false
	),
	'RESPONSIBLE_NAME' => array(
		'id' => 'RESPONSIBLE_NAME',
		'name' => GetMessage('TASKS_COLUMN_RESPONSIBLE_NAME'),
		'sort' => 'RESPONSIBLE_NAME',
		'first_order' => 'desc',
		'editable' => false
	),
	//	array('id' => 'PRIORITY', 'name' => GetMessage('TASKS_COLUMN_PRIORITY'), 'sort' => 'PRIORITY', 'first_order' => 'desc', 'editable' => false),
	'STATUS' => array(
		'id' => 'STATUS',
		'name' => GetMessage('TASKS_COLUMN_STATUS'),
		'sort' => 'REAL_STATUS',
		'first_order' => 'desc',
		'editable' => false
	),
	'GROUP_NAME' => array(
		'id' => 'GROUP_NAME',
		'name' => GetMessage('TASKS_COLUMN_GROUP_NAME'),
		'sort' => false,
		'first_order' => 'desc',
		'editable' => false
	),
	'CREATED_DATE' => array(
		'id' => 'CREATED_DATE',
		'name' => GetMessage('TASKS_COLUMN_CREATED_DATE'),
		'sort' => 'CREATED_DATE',
		'first_order' => 'desc',
		'editable' => false
	),
	'CHANGED_DATE' => array(
		'id' => 'CHANGED_DATE',
		'name' => GetMessage('TASKS_COLUMN_CHANGED_DATE'),
		'sort' => 'CHANGED_DATE',
		'first_order' => 'desc',
		'editable' => false
	),
	'CLOSED_DATE' => array(
		'id' => 'CLOSED_DATE',
		'name' => GetMessage('TASKS_COLUMN_CLOSED_DATE'),
		'sort' => 'CLOSED_DATE',
		'first_order' => 'desc',
		'editable' => false
	),
	'TIME_ESTIMATE' => array(
		'id' => 'TIME_ESTIMATE',
		'name' => GetMessage('TASKS_COLUMN_TIME_ESTIMATE'),
		'sort' => 'TIME_ESTIMATE',
		'first_order' => 'desc',
		'default' => false
	),
	'ALLOW_TIME_TRACKING' => array(
		'id' => 'ALLOW_TIME_TRACKING',
		'name' => GetMessage('TASKS_COLUMN_ALLOW_TIME_TRACKING'),
		'sort' => 'ALLOW_TIME_TRACKING',
		'first_order' => 'desc',
		'default' => false
	),
	'MARK' => array(
		'id' => 'MARK',
		'name' => GetMessage('TASKS_COLUMN_MARK'),
		'sort' => 'MARK',
		'first_order' => 'desc',
		'editable' => false
	),
	'ALLOW_CHANGE_DEADLINE' => array(
		'id' => 'ALLOW_CHANGE_DEADLINE',
		'name' => GetMessage('TASKS_COLUMN_ALLOW_CHANGE_DEADLINE'),
		'sort' => 'ALLOW_CHANGE_DEADLINE',
		'default' => false,
		'first_order' => 'desc'
	),
	'TIME_SPENT_IN_LOGS' => array(
		'id' => 'TIME_SPENT_IN_LOGS',
		'name' => GetMessage('TASKS_COLUMN_TIME_SPENT_IN_LOGS'),
		'sort' => 'TIME_SPENT_IN_LOGS',
		'first_order' => 'desc',
		'default' => false
	),
	'FLAG_COMPLETE' => array(
		'id' => 'FLAG_COMPLETE',
		'name' => GetMessage('TASKS_COLUMN_FLAG_COMPLETE'),
		'sort' => false,
		'editable' => false
	),
	'TAG' => array(
		'id' => 'TAG',
		'name' => GetMessage('TASKS_COLUMN_TAG'),
		'sort' => false,
		'editable' => false
	),
);

if (\Bitrix\Main\Loader::includeModule('crm'))
{
	$arParams['HEADERS']['UF_CRM_TASK_LEAD'] = array(
		'id' => 'UF_CRM_TASK_LEAD',
		'name' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Lead),
		'sort' => false,
		'first_order' => 'desc',
		'editable' => false
	);
	$arParams['HEADERS']['UF_CRM_TASK_CONTACT'] = array(
		'id' => 'UF_CRM_TASK_CONTACT',
		'name' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Contact),
		'sort' => false,
		'first_order' => 'desc',
		'editable' => false
	);
	$arParams['HEADERS']['UF_CRM_TASK_COMPANY'] = array(
		'id' => 'UF_CRM_TASK_COMPANY',
		'name' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Company),
		'sort' => false,
		'first_order' => 'desc',
		'editable' => false
	);
	$arParams['HEADERS']['UF_CRM_TASK_DEAL'] = array(
		'id' => 'UF_CRM_TASK_DEAL',
		'name' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Deal),
		'sort' => false,
		'first_order' => 'desc',
		'editable' => false
	);
}


foreach ($arParams['UF'] as $ufName => $ufItem)
{
	$arParams['HEADERS'][$ufName] = array(
		'id' => $ufName,
		'name' => $ufItem['EDIT_FORM_LABEL'],
		'sort' => false,
		'first_order' => 'desc',
		'editable' => false
	);
}

// if key 'default' is present, don't change it
foreach ($arParams['COLUMNS'] as $columnId)
{
	if (array_key_exists($columnId, $arParams['HEADERS']))
	{
		if (!array_key_exists('default', $arParams['HEADERS'][$columnId]))
		{
			$arParams['HEADERS'][$columnId]['default'] = true;
		}
	}
}

if (!function_exists('prepareTaskRowActions'))
{
	function prepareTaskRowActions($row, $arParams)
	{
		$userId = Util\User::getId();

		$urlPath = $arParams['GROUP_ID'] > 0 ? $arParams['PATH_TO_GROUP_TASKS_TASK']
			: $arParams['PATH_TO_USER_TASKS_TASK'];

		$actions = array();
		$actions[] = array(
			"text" => GetMessageJS('TASKS_VIEW_TASK'),
			'href' => CComponentEngine::MakePathFromTemplate(
				$urlPath,
				array(
					'group_id' => $arParams['GROUP_ID'],
					'user_id' => $userId,
					'action' => 'view',
					'task_id' => $row['ID']
				)
			)
		);
		if ($row['ACTION']['EDIT'])
		{
			$actions[] = array(
				"text" => GetMessageJS('TASKS_EDIT_TASK'),
				'href' => CComponentEngine::MakePathFromTemplate(
					$urlPath,
					array(
						'group_id' => $arParams['GROUP_ID'],
						'user_id' => $userId,
						'action' => 'edit',
						'task_id' => $row['ID']
					)
				)
			);
		}
		$actions[] = array(
			"text" => GetMessageJS('TASKS_ADD_SUB_TASK'),
			'href' => CComponentEngine::MakePathFromTemplate(
					$urlPath,
					array(
						'group_id' => $arParams['GROUP_ID'],
						'user_id' => $userId,
						'action' => 'edit',
						'task_id' => 0
					)
				).'?PARENT_ID='.$row['ID'].'&viewType=VIEW_MODE_LIST'
		);

		if ($row['ACTION']['ADD_FAVORITE'])
		{
			$actions[] = array(
				"text" => GetMessageJS('TASKS_TASK_ADD_TO_FAVORITES'),
				'onclick' => 'BX.Tasks.GridActions.action("addToFavorite", '.$row['ID'].');'
			);
		}
		if ($row['ACTION']['DELETE_FAVORITE'])
		{
			$actions[] = array(
				"text" => GetMessageJS('TASKS_TASK_REMOVE_FROM_FAVORITES'),
				'onclick' => 'BX.Tasks.GridActions.action("removeFromFavorite", '.$row['ID'].');'
			);
		}

		if ($row['ACTION']['COMPLETE'])
		{
			$actions[] = array(
				"text" => GetMessageJS('TASKS_CLOSE_TASK'),
				'onclick' => 'BX.Tasks.GridActions.action("complete", '.$row['ID'].');'
			);
		}
		if ($row['ACTION']['RENEW'])
		{
			$actions[] = array(
				"text" => GetMessageJS('TASKS_RENEW_TASK'),
				'onclick' => 'BX.Tasks.GridActions.action("renew", '.$row['ID'].');'
			);
		}
		if ($row['ACTION']['ACCEPT'])
		{
			$actions[] = array(
				"text" => GetMessageJS('TASKS_ACCEPT_TASK'),
				'onclick' => 'BX.Tasks.GridActions.action("accept", '.$row['ID'].');'
			);
		}
		if ($row['ACTION']['DECLINE'])
		{
			$actions[] = array(
				"text" => GetMessageJS('TASKS_DECLINE_TASK'),
				'onclick' => 'BX.Tasks.GridActions.action("decline", '.$row['ID'].');'
			);
		}
		if ($row['ACTION']['APPROVE'])
		{
			$actions[] = array(
				"text" => GetMessageJS('TASKS_APPROVE_TASK'),
				'onclick' => 'BX.Tasks.GridActions.action("approve", '.$row['ID'].');'
			);
		}
		if ($row['ACTION']['DISAPPROVE'])
		{
			$actions[] = array(
				"text" => GetMessageJS('TASKS_DISAPPROVE_TASK'),
				'onclick' => 'BX.Tasks.GridActions.action("disapprove", '.$row['ID'].');'
			);
		}
		if ($row['ACTION']['START'])
		{
			$actions[] = array(
				"text" => GetMessageJS('TASKS_START_TASK'),
				'onclick' => 'BX.Tasks.GridActions.action("start", '.$row['ID'].');'
			);
		}
		if ($row['ACTION']['PAUSE'])
		{
			$actions[] = array(
				"text" => GetMessageJS('TASKS_PAUSE_TASK'),
				'onclick' => 'BX.Tasks.GridActions.action("pause", '.$row['ID'].');'
			);
		}
		if ($row['ACTION']['DEFER'])
		{
			$actions[] = array(
				"text" => GetMessageJS('TASKS_DEFER_TASK'),
				'onclick' => 'BX.Tasks.GridActions.action("defer", '.$row['ID'].');'
			);
		}

		$actions[] = array(
			"text" => GetMessageJS('TASKS_COPY_TASK'),
			'href' => CComponentEngine::MakePathFromTemplate(
					$urlPath,
					array(
						'group_id' => $arParams['GROUP_ID'],
						'user_id' => $userId,
						'action' => 'edit',
						'task_id' => 0
					)
				).'?COPY='.$row['ID'].'&viewType=VIEW_MODE_LIST'
		);

		if (tasksCheckRightEditPlan($row) == 'Y')
		{
			$actions[] = array(
				"text" => GetMessageJS('TASKS_ADD_TASK_TO_TIMEMAN_EX'),
				"onclick" => 'BX.Tasks.GridActions.action("add2Timeman", '.$row['ID'].');'
			);
		}

		if ($row['ACTION']['REMOVE'])
		{
			$actions[] = array(
				"text" => GetMessageJS('TASKS_DELETE_TASK'),
				'onclick' => 'BX.Tasks.GridActions.action("delete", '.$row['ID'].');'
			);
		}

		$eventParam = array(
			'ID' => $row['ID']
		);
		foreach (GetModuleEvents('tasks', 'onTasksBuildContextMenu', true) as $event)
		{
			ExecuteModuleEventEx($event, array('TASK_LIST_CONTEXT_MENU', $eventParam, &$actions));
		}

		return $actions;
	}
}

if (!function_exists('tasksCheckRightEditPlan'))
{
	function tasksCheckRightEditPlan($row)
	{
		static $arTasksInPlan = null;

		$can = 'N';
		if ((($row["RESPONSIBLE_ID"] == Util\User::getId()) || ($row['ACCOMPLICES'] && in_array(Util\User::getId(), $row['ACCOMPLICES']))) &&
			CModule::IncludeModule("intranet") &&
			(!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite()))
		{
			$can = 'Y';

			if ($arTasksInPlan === null)
			{
				$arTasksInPlan = CTaskPlannerMaintance::getCurrentTasksList();
			}

			// If in day plan already
			if (is_array($arTasksInPlan) && in_array($row["ID"], $arTasksInPlan))
			{
				$can = 'N';
			}
		}

		return $can;
	}
}

if (!function_exists('prepareTaskRowTitle'))
{
	function prepareTaskRowTitle($row, $arParams)
	{
		$taskUrlTemplate = $arParams['GROUP_ID'] > 0 ? $arParams['PATH_TO_GROUP_TASKS_TASK']
			: $arParams['PATH_TO_USER_TASKS_TASK'];
		$taskUrl = CComponentEngine::MakePathFromTemplate(
			$taskUrlTemplate,
			array(
				"user_id" => $arParams['USER_ID'],
				"task_id" => $row["ID"],
				"action" => "view",
				'group_id' => $arParams['GROUP_ID']
			)
		);

		$append = '<span class="task-title-indicators">';
		if ((int)$row['COMMENTS_COUNT'] > 0)
		{
			$append .= '<a href="'.
					   $taskUrl.
					   '#comments" class="task-title-comments-grid" title="'.
					   GetMessage('TASKS_TASK_COMMENTS').
					   ' ('.
					   (int)$row['COMMENTS_COUNT'].
					   ')">'.
					   (int)$row['COMMENTS_COUNT'].
					   '</a>';
		}

		$updatesCount = CTasks::GetUpdatesCount(array($row['ID'] => $row['VIEWED_DATE'])); //TODO: not perf
		$updatesCount = $updatesCount[$row['ID']];
		if ((int)$updatesCount)
		{
			$append .= '<a href="'.
					   $taskUrl.
					   '#updates" class="task-item-updates-grid" title="'.
					   GetMessage('TASKS_LOG_WHAT').
					   ' ('.
					   (int)$updatesCount.
					   ')"><span class="task-item-updates-inner">'.
					   (int)$updatesCount.
					   '</span></a>';
		}

		if ((int)$row['PRIORITY'] == 2)
		{
			$append .= '<span class="task-priority-high"></span> ';
		}

		$append .= '<span class="task-timer" id="task-timer-block-container-'.$row['ID'].'"></span>';
		$append .= '</span>';

		$cssClass = 'task-status-text-color-'.\tasksStatus2String($row['STATUS']);
		$title = '<a href="'.
				 $taskUrl.
				 '" class="task-title '.
				 $cssClass.
				 '">'.
				 htmlspecialcharsbx($row['TITLE']).
				 '</a> '.
				 $append;
		$title .= prepareTimeTracking($row, $arParams);

		return $title;
	}
}

if (!function_exists('prepareTimeTracking'))
{
	function prepareTimeTracking($row, $arParams)
	{
		$taskId = (int)$row['ID'];
		$timeSpentInLogs = (int)$row['TIME_SPENT_IN_LOGS'];
		$timeEstimate = (int)$row['TIME_ESTIMATE'];
		$allowTimeTracking = $row['ALLOW_TIME_TRACKING'] === 'Y';

		$currentTaskTimerRunForUser = ($arParams['TIMER'] !== false) && ($arParams['TIMER']['TASK_ID'] == $row['ID']);

		$taskTimersTotalValue = $arParams['TIMER'] && $arParams['TIMER']['RUN_TIME'] && ($currentTaskTimerRunForUser)
			? (int)$arParams['TIMER']['RUN_TIME'] : 0;

		$canStartTask = $row['ACTION']['DAYPLAN.TIMER.TOGGLE'] == true;
		ob_start();
		if ($allowTimeTracking && $canStartTask)
		{
			?>
			<script>
				BX.Tasks.GridActions.redrawTimerNode(<?=$taskId?>,<?=$timeSpentInLogs?>,<?=$timeEstimate?>,
					'<?=$currentTaskTimerRunForUser?>',<?=$taskTimersTotalValue?>, <?=(int)$canStartTask?>
				);

			</script>
			<?php
		}

		return ob_get_clean();
	}
}

if (!function_exists('prepareTaskRow'))
{
	function prepareTaskRow($row, $arParams)
	{
		$group = \Bitrix\Tasks\Integration\SocialNetwork\Group::getData(array($row['GROUP_ID']));
		$groupName = htmlspecialcharsbx($group[$row['GROUP_ID']]['NAME']);

		$originatorUrl = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_USER_PROFILE'],
			array("user_id" => $row["CREATED_BY"])
		);
		$responsibleUrl = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_USER_PROFILE'],
			array("user_id" => $row["RESPONSIBLE_ID"])
		);

		$groupUrl = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_GROUP'],
			array("group_id" => $row["GROUP_ID"])
		);

		$resultRow = array(
			'ID' => $row['ID'],
			'PARENT_ID' => $row['PARENT_ID'],
			'TITLE' => prepareTaskRowTitle($row, $arParams),
			'DEADLINE' => prepareDeadline($row['ID'], $row['DEADLINE'], $row['ACTION']['CHANGE_DEADLINE'] == 1),
			'ORIGINATOR_NAME' => prepareTaskRowUserBaloonHtml(
				array(
					'PREFIX'           => 'TASKS_ORIGINATOR_'.$row['ID'],
					'USER_NAME'        => $arParams['~USER_NAMES'][$row['CREATED_BY']],
					'USER_PROFILE_URL' => $originatorUrl,
					'USER_ID'          => $row['CREATED_BY'],
					'TASK_ID'          =>$row['ID']
				)
			),
			'RESPONSIBLE_NAME' => prepareTaskRowUserBaloonHtml(
				array(
					'PREFIX'           => 'TASKS_RESPONSIBLE_'.$row['ID'],
					'USER_NAME'        => $arParams['~USER_NAMES'][$row['RESPONSIBLE_ID']],
					'USER_PROFILE_URL' => $responsibleUrl,
					'USER_ID'          => $row['RESPONSIBLE_ID'],
					'TASK_ID'          =>$row['ID']
				)
			),
			'GROUP_NAME' => '<a href="'.$groupUrl.'" target="_blank">'.$groupName.'</a>',
			'STATUS' => GetMessage('TASKS_STATUS_'.$row['REAL_STATUS']),
			'PRIORITY' => GetMessage('TASKS_PRIORITY_'.$row['PRIORITY']),

			'CREATED_DATE' => formatDateTasks($row["CREATED_DATE"]),
			'CLOSED_DATE' => formatDateTasks($row['CLOSED_DATE']),
			'CHANGED_DATE' => formatDateTasks($row['CHANGED_DATE']),

			'TIME_ESTIMATE' => sprintf(
				'%02d:%02d',
				floor($row['TIME_ESTIMATE'] / 3600),    // hours
				floor($row['TIME_ESTIMATE'] / 60) % 60    // minutes
			),
			'TIME_SPENT_IN_LOGS' => sprintf(
				'%02d:%02d',
				floor($row['TIME_SPENT_IN_LOGS'] / 3600),    // hours
				floor($row['TIME_SPENT_IN_LOGS'] / 60) % 60    // minutes
			),
			'MARK' => prepareMark($row, $arParams),
			//			'UF_CRM_TASK' => prepareCRM($row, $arParams),
			'ALLOW_TIME_TRACKING' => GetMessage('TASKS_ALLOW_TIME_TRACKING_'.$row['ALLOW_TIME_TRACKING']),
			'ALLOW_CHANGE_DEADLINE' => GetMessage('TASKS_ALLOW_CHANGE_DEADLINE_'.$row['ALLOW_CHANGE_DEADLINE']),
			'FLAG_COMPLETE' => prepareComplete($row, $arParams),
			'TAG' => prepareTag($row, $arParams),
		);

		foreach ($arParams['UF'] as $ufName => $ufItem)
		{
			$resultRow[$ufName] = prepareUF($ufName, $row, $arParams);
		}

		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			$resultRow['UF_CRM_TASK_LEAD'] = prepareCRMField('L', $row);
			$resultRow['UF_CRM_TASK_CONTACT'] = prepareCRMField('C', $row);
			$resultRow['UF_CRM_TASK_COMPANY'] = prepareCRMField('CO', $row);
			$resultRow['UF_CRM_TASK_DEAL'] = prepareCRMField('D', $row);
		}

		return $resultRow;
	}
}

if (!function_exists('prepareCRMField'))
{
	function prepareCRMField($fieldId, $row)
	{
		$collection = [];
		if (empty($row['UF_CRM_TASK']))
		{
			return '';
		}

		foreach ($row['UF_CRM_TASK'] as $value)
		{
			$crmElement = explode('_', $value);
			$type = $crmElement[0];
			if ($type != $fieldId)
			{
				continue;
			}

			$typeId = CCrmOwnerType::ResolveID(CCrmOwnerTypeAbbr::ResolveName($type));
			$title = CCrmOwnerType::GetCaption($typeId, $crmElement[1]);
			$url = CCrmOwnerType::GetShowUrl($typeId, $crmElement[1]);

			if (!isset($collection[$type]))
			{
				$collection[$type] = array();
			}

			if ($title)
			{
				$collection[$type][] = '<a href="'.$url.'">'.$title.'</a>';
			}
		}

		$html = [];
		if ($collection)
		{
			$html[]= '<div class="tasks-list-crm-div">';
			foreach ($collection as $type => $items)
			{
				if (empty($items))
				{
					continue;
				}

				$html[]= implode(', ', $items);
				$html[]= '</div>';
			}
			$html[]= '</div>';
		}

		return join('', $html);
	}
}

if (!function_exists('prepareCRM'))
{
	function prepareCRM($row, $arParams)
	{
		if (empty($row['UF_CRM_TASK']))
		{
			return GetMessage('TASKS_NOT_PRESENT');
		}

		$collection = array();
		sort($row['UF_CRM_TASK']);
		foreach ($row['UF_CRM_TASK'] as $value)
		{
			$crmElement = explode('_', $value);
			$type = $crmElement[0];
			$typeId = CCrmOwnerType::ResolveID(CCrmOwnerTypeAbbr::ResolveName($type));
			$title = CCrmOwnerType::GetCaption($typeId, $crmElement[1]);
			$url = CCrmOwnerType::GetShowUrl($typeId, $crmElement[1]);

			if (!isset($collection[$type]))
			{
				$collection[$type] = array();
			}

			if ($title)
			{
				$collection[$type][] = '<a href="'.$url.'">'.htmlspecialcharsbx($title).'</a>';
			}
		}

		$html = [];
		if ($collection)
		{
			$html[]= '<div class="tasks-list-crm-div">';
			$prevType = null;
			foreach ($collection as $type => $items)
			{
				if (empty($items))
				{
					continue;
				}

				$html[]= '<div class="tasks-list-crm-div-wrapper">';
				if ($type !== $prevType)
				{
					$html[]= '<span class="tasks-list-crm-div-type">'.GetMessage('TASKS_LIST_CRM_TYPE_'.$type).':</span>';
				}

				$prevType = $type;

				$html[]= implode(', ', $items);
				$html[]= '</div>';
			}
			$html[]= '</div>';
		}

		return join('', $html);
	}
}

if (!function_exists('prepareUF'))
{
	function prepareUF($fieldName, $row, $arParams)
	{
		$fieldValue = $row[$fieldName];
		$userFieldData = $arParams['UF'][$fieldName];

		if ($userFieldData['USER_TYPE_ID'] !== 'boolean' && empty($fieldValue) && $fieldValue !== '0')
		{
			return GetMessage('TASKS_NOT_PRESENT');
		}

		if ($fieldName == 'UF_CRM_TASK')
		{
			return prepareCRM($row, $arParams);
		}

		if ($userFieldData['USER_TYPE_ID'] == 'boolean')
		{
			$fieldValue = (empty($fieldValue)? GetMessage('TASKS_UF_FIELD_BOOLEAN_NO') : GetMessage('TASKS_UF_FIELD_BOOLEAN_YES'));
		}

		if (is_array($fieldValue))
		{
			return join(
				', ',
				array_map(
					function($item) {
						return htmlspecialcharsbx($item);
					},
					$fieldValue
				)
			);
		}
		else
		{
			return htmlspecialcharsbx($fieldValue);
		}
	}
}

if (!function_exists('prepareMark'))
{
	function prepareMark($row, $arParams)
	{
		ob_start();

		if ($row['ACTION']['EDIT'])
		{
			?>
			<a href="javascript: void(0)"
			   class="task-grade-and-report <?php if ($row['MARK']): ?>task-grade-<?=$row['MARK'] == 'N' ? "minus"
				   : "plus"?><?php endif ?> <?=($row["ADD_IN_REPORT"] == "Y") ? 'task-in-report' : ''?>"
			   onclick="event.stopPropagation();return BX.Tasks.GridActions.onMarkChangeClick(<?=$row["ID"]?>, this, {listValue : '<?=($row["MARK"] ==
																																	   "N" ||
																																	   $row["MARK"] ==
																																	   "P"
				   ? $row["MARK"] : "NULL")?>' });"
			   title="<?=GetMessage("TASKS_JS_MARK")?>: <?=GetMessage(
				   "TASKS_JS_MARK_".($row["MARK"] == "N" || $row["MARK"] == "P" ? $row["MARK"] : "NONE")
			   )?>">
				<span class="task-grade-and-report-inner">
					<i class="task-grade-and-report-icon"></i>
				</span>
			</a>
			<?php
		}
		else
		{
			?>
			<span href="javascript: void(0)"
				  class="<?php if ($row['MARK']): ?>task-grade-<?=$row['MARK'] == 'N' ? "minus"
					  : "plus"?><?php endif ?> <?=($row["ADD_IN_REPORT"] == "Y") ? 'task-in-report' : ''?>"
				  title="<?=GetMessage("TASKS_JS_MARK")?>: <?=GetMessage(
					  "TASKS_JS_MARK_".($row["MARK"] ? $row["MARK"] : "NONE")
				  )?>">
				<span class="task-grade-and-report-inner task-grade-and-report-default-cursor">
					<i class="task-grade-and-report-icon task-grade-and-report-default-cursor"></i>
				</span>
			</span>
			<?php
		}

		return ob_get_clean();
	}
}

if (!function_exists('prepareTaskGroupActions'))
{
	function prepareTaskGroupActions($arResult, $arParams)
	{
		$prefix = $arParams['GRID_ID'];
		$snippet = new Grid\Panel\Snippet();

		$actionList = array(
			array('NAME' => GetMessage('TASKS_LIST_CHOOSE_ACTION'), 'VALUE' => 'none')
		);

		$applyButton = $snippet->getApplyButton(
			array(
				'ONCHANGE' => array(
					array(
						'ACTION' => Grid\Panel\Actions::CALLBACK,
						'DATA' => array(
							array(
								'JS' => 'BX.Tasks.GridActions.confirmGroupAction(\''.$arParams['GRID_ID'].'\')'
							)
						)
					)
				)
			)
		);

		$actionList[] = array(
			'NAME' => GetMessage('TASKS_LIST_GROUP_ACTION_COMPLETE'),
			'VALUE' => 'complete',
			'ONCHANGE' => array(
				array(
					'ACTION' => Grid\Panel\Actions::RESET_CONTROLS
				)
			)
		);  // complete

		$actionList[] = array(
			'NAME' => GetMessage('TASKS_LIST_GROUP_ACTION_SET_DEADLINE'),
			'VALUE' => 'setdeadline',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::DATE,
							'ID' => 'action_set_deadline',
							'NAME' => 'ACTION_SET_DEADLINE',
							'VALUE' => '',
							'TIME' => true
						)
					)
				)
			)
		);  // set deadline

		$actionList[] = array(
			'NAME' => GetMessageJS('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_RIGHT'),
			'VALUE' => 'adjustdeadline',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::TEXT,
							'ID' => 'action_move_deadline_num',
							'NAME' => 'num',
							'VALUE' => ''
						),
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
							'ID' => 'action_move_deadline_type',
							'NAME' => 'type',
							'ITEMS' => array(
								array(
									'NAME' => GetMessageJS('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_AT_DAY'),
									'VALUE' => 'day'
								),
								array(
									'NAME' => GetMessageJS('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_AT_WEEK'),
									'VALUE' => 'week'
								),
								array(
									'NAME' => GetMessageJS('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_AT_MONTH'),
									'VALUE' => 'month'
								),
							)
						)
					)
				)
			)
		);  // adjustdeadline

		$actionList[] = array(
			'NAME' => GetMessageJS('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_LEFT'),
			'VALUE' => 'substractdeadline',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::TEXT,
							'ID' => 'action_move_deadline_num',
							'NAME' => 'num',
							'VALUE' => ''
						),
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
							'ID' => 'action_move_deadline_type',
							'NAME' => 'type',
							'ITEMS' => array(
								array(
									'NAME' => GetMessageJS('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_AT_DAY'),
									'VALUE' => 'day'
								),
								array(
									'NAME' => GetMessageJS('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_AT_WEEK'),
									'VALUE' => 'week'
								),
								array(
									'NAME' => GetMessageJS('TASKS_LIST_GROUP_ACTION_MOVE_DEADLINE_AT_MONTH'),
									'VALUE' => 'month'
								),
							)
						)
					)
				)
			)
		);  // substractdeadline

		$actionList[] = array(
			'NAME' => GetMessage('TASKS_LIST_GROUP_ACTION_SET_TASK_CONTROL'),
			'VALUE' => 'settaskcontrol',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
							'ID' => 'action_set_task_control',
							'NAME' => 'value',
							'ITEMS' => array(
								array(
									'NAME' => GetMessage('TASKS_LIST_GROUP_ACTION_SET_TASK_CONTROL_YES'),
									'VALUE' => 'Y'
								),
								array(
									'NAME' => GetMessage('TASKS_LIST_GROUP_ACTION_SET_TASK_CONTROL_NO'),
									'VALUE' => 'N'
								)
							)
						)
					)
				)
			)
		);  // set task control option

		$actionList[] = array(
			'NAME' => GetMessage('TASKS_LIST_GROUP_ACTION_CHANGE_RESPONSIBLE'),
			'VALUE' => 'setresponsible',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::TEXT,
							'ID' => 'action_set_responsible_text',
							'NAME' => 'responsibleText',
							'VALUE' => '',
							'SIZE' => 1
						),
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::HIDDEN,
							'ID' => 'action_set_responsible',
							'NAME' => 'responsibleId',
							'VALUE' => '',
							'SIZE' => 1
						)
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(
						array(
							'JS' => 'BX.Tasks.GridActions.initPopupBaloon(\'user\', \'action_set_responsible_text\',\'action_set_responsible\');'
						)
					)
				)
			)
		);  // set responsible

		$actionList[] = array(
			'NAME' => GetMessage('TASKS_LIST_GROUP_ACTION_CHANGE_ORIGINATOR'),
			'VALUE' => 'setoriginator',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::TEXT,
							'ID' => 'action_set_originator_text',
							'NAME' => 'originatorText',
							'VALUE' => '',
							'SIZE' => 1
						),
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::HIDDEN,
							'ID' => 'action_set_originator',
							'NAME' => 'originatorId',
							'VALUE' => '',
							'SIZE' => 1
						)
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(
						array(
							'JS' => 'BX.Tasks.GridActions.initPopupBaloon(\'user\', \'action_set_originator_text\',\'action_set_originator\');'
						)
					)
				)
			)
		);  // set originator

		$actionList[] = array(
			'NAME' => GetMessage('TASKS_LIST_GROUP_ACTION_ADD_AUDITOR'),
			'VALUE' => 'addauditor',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::TEXT,
							'ID' => 'action_set_auditor_text',
							'NAME' => 'auditorText',
							'VALUE' => '',
							'SIZE' => 1
						),
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::HIDDEN,
							'ID' => 'action_set_auditor',
							'NAME' => 'auditorId',
							'VALUE' => '',
							'SIZE' => 1
						)
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(
						array(
							'JS' => 'BX.Tasks.GridActions.initPopupBaloon(\'user\', \'action_set_auditor_text\',\'action_set_auditor\');'
						)
					)
				)
			)
		);  // set auditor

		$actionList[] = array(
			'NAME' => GetMessage('TASKS_LIST_GROUP_ACTION_ADD_ACCOMPLICE'),
			'VALUE' => 'addaccomplice',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::TEXT,
							'ID' => 'action_set_accomplice_text',
							'NAME' => 'accompliceText',
							'VALUE' => '',
							'SIZE' => 1
						),
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::HIDDEN,
							'ID' => 'action_set_accomplice',
							'NAME' => 'accompliceId',
							'VALUE' => '',
							'SIZE' => 1
						)
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(
						array(
							'JS' => 'BX.Tasks.GridActions.initPopupBaloon(\'user\', \'action_set_accomplice_text\',\'action_set_accomplice\');'
						)
					)
				)
			)
		);  // set accomplice

		if (!($arResult['VIEW_STATE']['SPECIAL_PRESET_SELECTED']['CODENAME'] == 'FAVORITE' &&
			  $arResult['VIEW_STATE']['SECTION_SELECTED']['CODENAME'] == 'VIEW_SECTION_ADVANCED_FILTER'))
		{
			$actionList[] = array(
				'NAME' => GetMessage('TASKS_LIST_GROUP_ACTION_ADD_FAVORITE'),
				'VALUE' => 'addtofavorite',
				'ONCHANGE' => array(
					'ACTION' => Grid\Panel\Actions::RESET_CONTROLS
				)
			);
		} //

		$actionList[] = array(
			'NAME' => GetMessage('TASKS_LIST_GROUP_ACTION_DELETE_FAVORITE'),
			'VALUE' => 'removefromfavorite',
			'ONCHANGE' => array(
				array(
					'ACTION' => Grid\Panel\Actions::RESET_CONTROLS
				)
			)
		);  // remove favorite

		$actionList[] = array(
			'NAME' => GetMessage('TASKS_LIST_GROUP_ACTION_SET_GROUP'),
			'VALUE' => 'setgroup',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::TEXT,
							'ID' => 'action_set_group_search',
							'NAME' => 'ACTION_SET_GROUP_SEARCH'
						),
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::HIDDEN,
							'ID' => 'action_set_group_id',
							'NAME' => 'groupId'
						)
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(
						array(
							'JS' => 'BX.Tasks.GridActions.initPopupBaloon(\'group\', \'action_set_group_search\',\'action_set_group_id\');'
						)
					)
				),
			)
		);  // set group

		$actionList[] = array(
			'NAME' => GetMessage('TASKS_LIST_GROUP_ACTION_REMOVE'),
			'VALUE' => 'delete',
			'ONCHANGE' => array(
				array(
					'ACTION' => Grid\Panel\Actions::RESET_CONTROLS
				)
			)
		);  // remove task

		$groupActions = array(
			'GROUPS' => array(
				array(
					'ITEMS' => array(
						array(
							"TYPE" => Grid\Panel\Types::DROPDOWN,
							"ID" => "action_button_{$prefix}",
							"NAME" => "action_button_{$prefix}",
							"ITEMS" => $actionList
						),
						$applyButton,
						$snippet->getForAllCheckbox()
					)
				)
			)
		);

		return $groupActions;
	}
}

if (!function_exists('formatDateTasks'))
{
	function formatDateTasks($date)
	{
		if (!$date)
		{
			return GetMessage('TASKS_NOT_PRESENT');
		}

		$stamp = MakeTimeStamp($date);

		$currentTimeFormat = "HH:MI:SS";
		$format = 'j F';

		if (LANGUAGE_ID == "en")
		{
			$format = "F j";
		}
		if (LANGUAGE_ID == "de")
		{
			$format = "j. F";
		}

		if (date('Y') != date('Y', $stamp))
		{
			if (LANGUAGE_ID == "en")
			{
				$format .= ",";
			}

			$format .= ' Y';
		}

		$resSite = CSite::GetByID(SITE_ID);
		if ($site = $resSite->Fetch())
		{
			$currentTimeFormat = str_replace($site["FORMAT_DATE"] . " ", "", $site["FORMAT_DATETIME"]);
		}

		if (date('Hi', $stamp) > 0)
		{
			$format .= ', ' . ($currentTimeFormat == "HH:MI:SS"? " G:i" : " g:i a");
		}

		return UI::formatDateTime($stamp, $format);
	}
}

if (!function_exists('prepareDeadline'))
{

	function prepareDeadline($taskId, $deadline, $canChange = false)
	{
		$str = formatDateTasks($deadline);

		if ($canChange)
		{
			return '<span class="task-deadline-datetime"><span class="task-deadline-date"
						onclick="BX.Tasks.GridActions.onDeadlineChangeClick('.
				   (int)$taskId.
				   ', this, \''.
				   $deadline.
				   '\');event.stopPropagation();">'.
				   $str.
				   '</span></span>';
		}
		else
		{
			return $str;
		}
	}
}

if (!function_exists('prepareTag'))
{

	function prepareTag($row, $arParams)
	{
		$list = array();

		if (!array_key_exists('TAG', $row) || !is_array($row['TAG']))
		{
			return '';
		}

		foreach ($row['TAG'] as $tag)
		{
			$list[] = '<a href="javascript:;" onclick="BX.Tasks.GridActions.filter(\''.$tag.'\')">#'.$tag.'</a>';
		}

		return join(', ', $list);
	}
}

if (!function_exists('prepareTaskRowUserBaloonHtml'))
{
	function prepareTaskRowUserBaloonHtml($arParams)
	{
		if (!is_array($arParams))
		{
			return '';
		}

		$users = Util\User::getData(array($arParams['USER_ID']));
		$user = $users[$arParams['USER_ID']];

		$user['AVATAR'] = \Bitrix\Tasks\UI::getAvatar($user['PERSONAL_PHOTO'], 100, 100);
		$user['IS_EXTERNAL'] = \Bitrix\Tasks\Util\User::isExternalUser($user['ID']);
		$user['IS_CRM'] = array_key_exists('UF_USER_CRM_ENTITY', $user) && !empty($user['UF_USER_CRM_ENTITY']);
		$arParams['USER_PROFILE_URL'] = $user['IS_EXTERNAL']
			? \Bitrix\Tasks\Integration\Socialnetwork\Task::addContextToURL(
				$arParams['USER_PROFILE_URL'],
				$arParams['TASK_ID']
			) : $arParams['USER_PROFILE_URL'];

		// $arParams['USER']['IS_EXTERNAL']   = true || false
		$userIcon = '';
		if ($user['IS_EXTERNAL'])
		{
			$userIcon = 'tasks-grid-avatar-extranet';
		}
		if ($user["EXTERNAL_AUTH_ID"] == 'email')
		{
			$userIcon = 'tasks-grid-avatar-mail';
		}
		if ($user["IS_CRM"])
		{
			$userIcon = 'tasks-grid-avatar-crm';
		}

		$userAvatar = 'tasks-grid-avatar-empty';
		if ($user['AVATAR'])
		{
			$userAvatar = '';
		}

		$userName = '<span class="tasks-grid-avatar  '.$userAvatar.' '.$userIcon.'" 
			'.($user['AVATAR'] ? 'style="background-image: url(\''.$user['AVATAR'].'\')"' : '').'></span>';

		$userName .= '<span class="tasks-grid-username-inner '.$userIcon.'">'.htmlspecialcharsbx($arParams['USER_NAME']).'</span>';

		$profilePath = isset($arParams['USER_PROFILE_URL']) ? $arParams['USER_PROFILE_URL'] : '';

		return '<div class="tasks-grid-username-wrapper"><a href="'.
			   htmlspecialcharsbx($profilePath).
			   '" class="tasks-grid-username">'.
			   $userName.
			   '</a></div>';
	}
}

if (!function_exists('prepareComplete'))
{
	function prepareComplete($row, $arParams)
	{
		if (!is_array($arParams))
		{
			return '';
		}

		$title = '';
		$onclick = '';
		$class = '';

		if ($row['ACTION']['COMPLETE'])
		{
			$title = GetMessageJS('TASKS_CLOSE_TASK');
			$onclick = 'BX.Tasks.GridActions.action("complete", '.$row['ID'].');';
			$class = 'task-complete-action-need-complete';
		}
		else if ($row['REAL_STATUS'] == CTasks::STATE_COMPLETED)
		{
			$title = GetMessageJS("TASKS_FINISHED");
			$class = 'task-complete-action-completed';
		}

		if ($title != '')
		{
			return "<a class=\"task-complete-action {$class}\" href=\"javascript:;\" title=\"{$title}\" onclick='{$onclick}'></a>";
		}
	}
}

$arResult['ROWS'] = array();

$groupByProject =
	isset($arParams['VIEW_STATE']['SUBMODES']['VIEW_SUBMODE_WITH_GROUPS']['SELECTED']) &&
	$arParams['VIEW_STATE']['SUBMODES']['VIEW_SUBMODE_WITH_GROUPS']['SELECTED'] === "Y"
;

if (!empty($arResult['LIST']))
{
	$prevGroupId = 0;

	$users = [];
	foreach ($arResult['LIST'] as $row)
	{
		$users[] = $row['CREATED_BY'];
		$users[] = $row['RESPONSIBLE_ID'];
	}
	$arParams['~USER_NAMES'] = \Bitrix\Tasks\Util\User::getUserName(array_unique($users));

	foreach ($arResult['LIST'] as $row)
	{
		if ($groupByProject && $prevGroupId != $row['GROUP_ID'])
		{
			$group = SocialNetwork\Group::getData(array($row['GROUP_ID']));
			$groupName = htmlspecialcharsbx($group[$row['GROUP_ID']]['NAME']);
			$groupUrl = CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_GROUP'],
				array("group_id" => $row["GROUP_ID"])
			);

			$groupItem = array(
				"id" => 'group_'.$row["GROUP_ID"],
				"has_child" => true,
				"parent_id" => 0,
				"custom" => '<div class="tasks-grid-wrapper"><a href="'.
							$groupUrl.
							'" class="tasks-grid-group-link">'.
							$groupName.
							'</a></div>',
				"not_count" => true,
				"draggable" => false,
				"group_id" => $row["GROUP_ID"],
				"attrs" => array(
					"data-type" => "group",
					"data-group-id" => $row["GROUP_ID"],
					"data-can-create-tasks" => SocialNetwork\Group::can(
						$row["GROUP_ID"],
						SocialNetwork\Group::ACTION_CREATE_TASKS
					) ? "true" : "false",
					"data-can-edit-tasks" => SocialNetwork\Group::can(
						$row["GROUP_ID"],
						SocialNetwork\Group::ACTION_EDIT_TASKS
					) ? "true" : "false"
				)
			);

			$arResult['ROWS'][] = $groupItem;
		}

		$rowItem = array(
			"id" => $row["ID"],
			'has_child' => array_key_exists($row['ID'], $arResult['SUB_TASK_COUNTERS']),
			'parent_id' => \Bitrix\Main\Grid\Context::isInternalRequest() ? $row['PARENT_ID'] : 0,
			"parent_group_id" => $row["GROUP_ID"],
			'actions' => prepareTaskRowActions($row, $arParams),
			'attrs' => array(
				"data-type" => "task",
				"data-group-id" => $row['GROUP_ID'],
				"data-can-edit" => $row['ACTION']['EDIT'] === true ? "true" : "false"
			),
			'columns' => prepareTaskRow($row, $arParams)
		);

		$arResult['ROWS'][] = $rowItem;

		$prevGroupId = $row['GROUP_ID'];
	}
}

$arResult['GROUP_ACTIONS'] = prepareTaskGroupActions($arResult, $arParams);