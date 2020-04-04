<?

use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arResult['HELPER'] = $helper = new \Bitrix\Tasks\UI\Component\TemplateHelper('TasksWidgetButtonsTask', $this, array(
	'RELATION' => array(
		'tasks_util',
		'popup',
		'tasks_util_widget',
		'tasks_dayplan',
	),
));
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

$can =& $arParams["TASK"]["ACTION"];
$data =& $arParams["TASK"];
$taskId = intval($arParams["TASK"]["ID"]);

$data["TIME_ESTIMATE"] = intval($data["TIME_ESTIMATE"]);
$data["TIME_ELAPSED"] = intval($data["TIME_ELAPSED"]);

$this->__component->tryParseBooleanParameter($arParams["REDIRECT_TO_LIST_ON_DELETE"], true);

// urls
$arResult['VIEW_URL'] = UI\Task::makeActionUrl($arParams["PATH_TO_TASKS_TASK"], $taskId, 'view');
$arResult['EDIT_URL'] = UI\Task::makeActionUrl($arParams["PATH_TO_TASKS_TASK"], $taskId, 'edit');
$arResult['COPY_URL'] = UI\Task::makeActionUrl($arParams["PATH_TO_TASKS_TASK_COPY"], 0, 'edit');
$arResult['CREATE_SUBTASK_URL'] = UI\Task::makeActionUrl($arParams["PATH_TO_TASKS_TASK_CREATE_SUBTASK"], 0, 'edit');

$arResult['EDIT_URL'] = Util::replaceUrlParameters($arResult['EDIT_URL'], array(
	'BACKURL' => $arResult['VIEW_URL'],
	'SOURCE' => 'view',
), array(), array('encode' => true));
$arResult['COPY_URL'] = Util::replaceUrlParameters($arResult['COPY_URL'], array(
	//'BACKURL' => $arResult['VIEW_URL'],
	'SOURCE' => 'view',
), array(), array('encode' => true));
$arResult['CREATE_SUBTASK_URL'] = Util::replaceUrlParameters($arResult['CREATE_SUBTASK_URL'], array(
	//'BACKURL' => $arResult['VIEW_URL'],
	'SOURCE' => 'view',
), array(), array('encode' => true));

$classes = array();
if($can["DAYPLAN.TIMER.TOGGLE"])
{
	$classes[] = 'timer-visible';
	$classes[] = 'timer-'.($data["TIMER_IS_RUNNING_FOR_CURRENT_USER"] ? 'pause' : 'start');
}
else
{
	if ($data['ACTION']['PAUSE'])
	{
		$classes[] = 'pause';
	}
	elseif ($data['ACTION']['START'])
	{
		$classes[] = 'start';
	}
}

if ($can["COMPLETE"])
{
	$classes[] = 'complete';
}

if ($can["APPROVE"])
{
	$classes[] = 'approve';
}

if ($can["DISAPPROVE"])
{
	$classes[] = 'disapprove';
}

if ($can["EDIT"] && !$arParams["PUBLIC_MODE"])
{
	$classes[] = 'edit';
}

if ($data["TIME_ESTIMATE"] > 0 && $data["TIME_ELAPSED"] > $data["TIME_ESTIMATE"])
{
	$classes[] = 'timer-overtime';
}

if ($data['TIMER_IS_RUNNING_FOR_CURRENT_USER'])
{
	$classes[] = 'timer-running';
}

if (!$arParams["PUBLIC_MODE"] || $can["RENEW"])
{
	$classes[] = 'more-button';
}

$arResult['CLASSES'] = $classes;

//region Rest
$this->arResult['ADDITIONAL_TABS'] = array();
$this->arResult['ENABLE_REST'] = false;
if (Bitrix\Main\Loader::includeModule('rest'))
{
	$this->arResult['ENABLE_REST'] = true;
	\CJSCore::Init(array('marketplace'));
	\CJSCore::Init(array('applayout'));

	$this->arResult['REST_PLACEMENT'] = 'TASK_LIST_CONTEXT_MENU';
	$placementHandlerList = \Bitrix\Rest\PlacementTable::getHandlersList($this->arResult['REST_PLACEMENT']);

	if (count($placementHandlerList) > 0)
	{
		foreach ($placementHandlerList as $placementHandler)
		{
			$this->arResult['ADDITIONAL_TABS'][] = [
				'ID'   => 'activity_rest_'.$placementHandler['APP_ID'].'_'.$placementHandler['ID'],
				'NAME' => strlen($placementHandler['TITLE']) > 0 ? $placementHandler['TITLE'] : $placementHandler['APP_NAME'],

				'ONCLICK'=> 'BX.rest.AppLayout.openApplication(
					"'.$placementHandler['APP_ID'].'",
					{
						TASK_ID: '.$data['ID'].'
					},
					{
						PLACEMENT: "'.$this->arResult['REST_PLACEMENT'].'",
						PLACEMENT_ID:  "'.$placementHandler['ID'].'"
					}
				);'
			];
		}
	}

	$this->arResult['ADDITIONAL_TABS'][] = array(
		'ID'     => 'activity_rest_applist',
		'NAME'   => \Bitrix\Main\Localization\Loc::getMessage('TASKS_REST_BUTTON_TITLE'),
		'SLIDER' => true,
		'ONCLICK' =>'BX.rest.Marketplace.open({PLACEMENT:"'.\CUtil::JSEscape($this->arResult['REST_PLACEMENT']).'"})'
	);
}

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

	$eventParam = array(
		'ID' => $row['ID']
	);
	$actions = [];
	foreach (GetModuleEvents('tasks', 'onTasksBuildContextMenu', true) as $event)
	{
		ExecuteModuleEventEx($event, array('TASK_LIST_CONTEXT_MENU', $eventParam, &$actions));
	}
}
//endregion

$arResult['JS_DATA'] = [
	'can'                => $can,
	'taskId'             => $taskId,
	'publicMode'         => $arParams["PUBLIC_MODE"],
	'data'               => [
		'TIME_ESTIMATE' => $taskData['TIME_ESTIMATE'],
		'TIME_ELAPSED' => $taskData['TIME_ELAPSED'],
		'TIMER_IS_RUNNING_FOR_CURRENT_USER' => $taskData['TIMER_IS_RUNNING_FOR_CURRENT_USER']
	],
	'copyUrl'            => $arResult['COPY_URL'],
	'createSubtaskUrl'   => $arResult['CREATE_SUBTASK_URL'],
	'listUrl'            => $arParams["PATH_TO_TASKS"],
	'goToListOnDelete'   => $arParams["REDIRECT_TO_LIST_ON_DELETE"],
	'additional_actions' => $actions,
	'additional_tabs'    => $this->arResult['ADDITIONAL_TABS']
];