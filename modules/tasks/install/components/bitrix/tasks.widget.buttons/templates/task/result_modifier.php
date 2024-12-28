<?

use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Internals\Task\Status;
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

/**
 * The originator has permissions to complete, but if the task awaiting validation, we're should hide the Complete button
 * #100526
 */
if ((int)$data['STATUS'] === Status::SUPPOSEDLY_COMPLETED)
{
	$can['COMPLETE'] = false;
}

$taskId = (int)$arParams["TASK"]["ID"];
$userId = (int)($arParams['USER_ID'] ?? 0);
$groupId = (int)($arParams['GROUP_ID'] ?? 0);

$data["TIME_ESTIMATE"] = (int)$data["TIME_ESTIMATE"];
$data["TIME_ELAPSED"] = (int)$data["TIME_ELAPSED"];

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

$analyticsParams = [
	'ta_sec' => \Bitrix\Tasks\Helper\Analytics::SECTION['tasks'],
	'ta_sub' => \Bitrix\Tasks\Helper\Analytics::SUB_SECTION['task_card'],
	'ta_el' => \Bitrix\Tasks\Helper\Analytics::ELEMENT['context_menu']
];

$copyUrl = (new \Bitrix\Main\Web\Uri($arResult['COPY_URL']))->addParams($analyticsParams);
$arResult['COPY_URL'] = $copyUrl->getUri();

$createSubTaskUrl = (new \Bitrix\Main\Web\Uri($arResult['CREATE_SUBTASK_URL']))->addParams($analyticsParams);
$arResult['CREATE_SUBTASK_URL'] = $createSubTaskUrl->getUri();

$classes = array();

if ($data['ACTION']['TAKE'])
{
	$classes[] = 'take';
}
elseif($can["DAYPLAN.TIMER.TOGGLE"])
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
$additionalTabs = [];
if (Bitrix\Main\Loader::includeModule('rest'))
{
	\CJSCore::Init(['applayout', 'marketplace']);

	$restReplacement = 'TASK_LIST_CONTEXT_MENU';
	$placementHandlerList = \Bitrix\Rest\PlacementTable::getHandlersList($restReplacement);

	if (count($placementHandlerList) > 0)
	{
		foreach ($placementHandlerList as $placementHandler)
		{
			$additionalTabs[] = [
				'ID' => "activity_rest_{$placementHandler['APP_ID']}_{$placementHandler['ID']}",
				'NAME' => ($placementHandler['TITLE'] !== '' ? $placementHandler['TITLE'] : $placementHandler['APP_NAME']),
				'ONCLICK'=> 'BX.rest.AppLayout.openApplication(
                    "'.$placementHandler['APP_ID'].'",
                    {
                        TASK_ID: '.$data['ID'].'
                    },
                    {
                        PLACEMENT: "'.$restReplacement.'",
                        PLACEMENT_ID: "'.$placementHandler['ID'].'"
                    }
                );',
			];
		}
	}

	$additionalTabs[] = [
		'ID' => 'activity_rest_applist',
		'NAME' => \Bitrix\Main\Localization\Loc::getMessage('TASKS_REST_BUTTON_TITLE_2'),
		'SLIDER' => true,
		'ONCLICK' =>'BX.rest.Marketplace.open({PLACEMENT:"'.\CUtil::JSEscape($restReplacement).'"})',
	];
}

if (\Bitrix\Main\ModuleManager::isModuleInstalled('rest'))
{
	$APPLICATION->IncludeComponent(
		'bitrix:app.placement',
		'menu',
		array(
			'PLACEMENT'         => "TASK_LIST_CONTEXT_MENU",
			"PLACEMENT_OPTIONS" => array(),
			//            'INTERFACE_EVENT' => 'onCrmLeadListInterfaceInit',
			'MENU_EVENT_MODULE' => 'tasks',
			'MENU_EVENT'        => 'onTasksBuildContextMenu',
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);

	$eventParam = [
		'ID' => ($row['ID'] ?? null),
	];
	$actions = [];
	foreach (GetModuleEvents('tasks', 'onTasksBuildContextMenu', true) as $event)
	{
		ExecuteModuleEventEx($event, array('TASK_LIST_CONTEXT_MENU', $eventParam, &$actions));
	}
}
//endregion

$taskDelegatingExceeded = !Bitrix24::checkFeatureEnabled(Bitrix24\FeatureDictionary::TASK_DELEGATING);

$isCollab = (bool)($arParams['IS_COLLAB'] ?? false);
$arResult['JS_DATA'] = [
	'can'                => $can,
	'taskId'             => $taskId,
	'publicMode'         => $arParams["PUBLIC_MODE"],
	'data'             => [
		'TIME_ESTIMATE' => ($taskData['TIME_ESTIMATE'] ?? null),
		'TIME_ELAPSED' => ($taskData['TIME_ELAPSED'] ?? null),
		'TIMER_IS_RUNNING_FOR_CURRENT_USER' => ($taskData['TIMER_IS_RUNNING_FOR_CURRENT_USER'] ?? null),
		'ALLOW_TIME_TRACKING' => ($data['ALLOW_TIME_TRACKING'] ?? null),
	],
	'copyUrl'            => $arResult['COPY_URL'],
	'createSubtaskUrl' => $arResult['CREATE_SUBTASK_URL'],
	'listUrl'            => $arParams["PATH_TO_TASKS"],
	'goToListOnDelete' => $arParams["REDIRECT_TO_LIST_ON_DELETE"],
	'additional_actions' => $actions,
	'additional_tabs'    => $additionalTabs,
	'taskLimitExceeded' => $arResult['TASK_LIMIT_EXCEEDED'],
	'groupId' => $arParams['TASK']['GROUP_ID'],
	'parentId' => (int) $arParams['TASK']['PARENT_ID'],
	'isScrumTask' => (bool) $arParams['IS_SCRUM_TASK'],
	'isCollab' => $isCollab,
	'showAhaStartFlowTask' => (bool) $arParams['SHOW_AHA_START_FLOW_TASK'],
	'currentUserId' => Util\User::getId(),
	'taskDelegatingExceeded' => $taskDelegatingExceeded,
	'taskDelegatingFeatureId' => Bitrix24\FeatureDictionary::TASK_DELEGATING,
	'flowId' => (int)($data['FLOW_ID'] ?? 0),
	'isExtranetUser' => (bool) Bitrix\Tasks\Integration\Extranet\User::isExtranet(),
];

if ($isCollab)
{
	$analytics = \Bitrix\Tasks\Helper\Analytics::getInstance($userId);

	$arResult['JS_DATA']['collabAnalytics'] = [
		'p2' => $analytics->getUserTypeParameter(),
		'p4' => $analytics->getCollabParameter($groupId),
	];
}
