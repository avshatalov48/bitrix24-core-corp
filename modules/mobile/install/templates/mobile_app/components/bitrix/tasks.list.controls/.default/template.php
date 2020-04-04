<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$GLOBALS['APPLICATION']->addHeadScript(SITE_TEMPLATE_PATH.'/tasks/logic.js');
$GLOBALS['APPLICATION']->addHeadScript($templateFolder.'/logic.js');

// enabling application cache
$frame = \Bitrix\Main\Page\Frame::getInstance();
$frame->setEnable();
$frame->setUseAppCache();
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("page", "tasks.roles");
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("MobileAPIVersion", CMobile::getApiVersion());
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("MobilePlatform", CMobile::getPlatform());
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("version", "v1.3");
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("LanguageId", LANGUAGE_ID);

/*
if (CModule::IncludeModule('pull'))
{
	$rc = CPullWatch::Add($arParams['USER_ID'], 'TASKS_GENERAL_' . $arParams['USER_ID']);
}
*/

if (($arParams['SHOW_SECTIONS_BAR'] === 'Y') || ($arParams['SHOW_FILTER_BAR'] === 'Y') || ($arParams['SHOW_COUNTERS_BAR'] === 'Y'))
{
	$arResult['PATH_TEMPLATE'] = str_replace(
		array(
			'#USER_ID#',
			'#user_id#'
		),
		array(
			'%USER_ID%',
			'%USER_ID%'
		),
		$arParams['PATH_TO_USER_TASKS']
	);

	$data = array(
		'ITEMS' => array()
	);
	$counters = array();
	$counterToRole = array();
	if(is_array($arResult['VIEW_STATE']))
	{
		foreach($arResult['VIEW_STATE']['ROLES'] as $roleCode => $roleData)
		{
			$parameters = array(
				'GROUP_LIST_MODE' => 'N',
				'F_STATE[0]' => 'sR'.base_convert($roleData['ID'], 10, 32),
				'F_STATE[1]' => 'sR'.base_convert(CTaskListState::VIEW_TASK_CATEGORY_IN_PROGRESS, 10, 32),
			);
			$counter = array(
				'VALUE' => 0,
				'PLURAL' => 2
			);
			if(is_array($arResult['VIEW_COUNTERS']['ROLES'][$roleCode]['TOTAL']))
			{
				$counter = $arResult['VIEW_COUNTERS']['ROLES'][$roleCode]['TOTAL'];
				$counter['VALUE'] = $counter['COUNTER'];
				unset($counter['COUNTER']);

				if((string) $counter['COUNTER_ID'] != '')
					$counterToRole[$counter['COUNTER_ID']] = $roleCode;
			}

			$counters[$roleCode] = intval($counter['VALUE']);

			$data['ITEMS'][$roleCode] = array(
				'ID' => $roleCode,
				'TITLE' => $roleData['TITLE'],
				'URL' => CHTTP::urlAddParams($arResult['PATH_TEMPLATE'], $parameters),
				'COUNTER' => $counter
			);
		}
	}

	// special presets, like "favorite"
	if(is_array($arResult['VIEW_STATE']['SPECIAL_PRESETS']))
	{
		foreach($arResult['VIEW_STATE']['SPECIAL_PRESETS'] as $presetId => $presetData)
		{
			$parameters = array(
				'GROUP_LIST_MODE' => 'N',
				'F_FILTER_SWITCH_PRESET' => $presetId,
				'F_STATE[0]' => 'sC'.base_convert(CTaskListState::VIEW_TASK_CATEGORY_ALL, 10, 32),
			);

			$data['ITEMS'][$presetId] = array(
				'ID' => $presetId,
				'TITLE' => $presetData['TITLE'],
				'URL' => CHTTP::urlAddParams($arResult['PATH_TEMPLATE'], $parameters),
				'COUNTER' => array(
					'VALUE' => 0,
					'PLURAL' => 2
				)
			);
		}
	}

	// "all" link
	$parameters = array(
		'GROUP_LIST_MODE' => 'N',
		'F_FILTER_SWITCH_PRESET' => CTaskFilterCtrl::STD_PRESET_ALL_MY_TASKS
	);
	$data['ITEMS']['ALL'] = array(
		'ID' => 'ALL',
		'TITLE' => Loc::getMessage('MB_TASKS_PANEL_TAB_ALL'),
		'URL' => CHTTP::urlAddParams($arResult['PATH_TEMPLATE'], $parameters),
		'COUNTER' => array(
			'VALUE' => 0,
			'PLURAL' => 2
		)
	);

	// "projects" link
	$parameters = array(
		'GROUP_LIST_MODE' => 'Y',
		'F_FILTER_SWITCH_PRESET' => CTaskFilterCtrl::STD_PRESET_ALL_MY_TASKS
	);
	$data['ITEMS']['PROJECTS'] = array(
		'ID' => 'PROJECTS',
		'TITLE' => Loc::getMessage('MB_TASKS_PANEL_TAB_PROJECTS'),
		'URL' => CHTTP::urlAddParams($arResult['PATH_TEMPLATE'], $parameters),
		'COUNTER' => array(
			'VALUE' => 0,
			'PLURAL' => 2
		)
	);

	$APPLICATION->IncludeComponent(
		'bitrix:mobile.tasks.view',
		'task.groups',
		array_merge($arParams, array(
			'DATA' => $data,
			'LOGIC_INSTANCE_CODE' => 'taskgroups'
		)), 
		false
	);
	?>

	<script>
		BX.message(<?=CUtil::PhpToJSObject(array(
			'PAGE_TITLE' => 				Loc::getMessage('MB_TASKS_GENERAL_TITLE'),
			'MB_TASKS_PULLDOWN_PULL' => 	Loc::getMessage('MB_TASKS_TASKS_LIST_PULLDOWN_PULL'),
			'MB_TASKS_PULLDOWN_DOWN' => 	Loc::getMessage('MB_TASKS_TASKS_LIST_PULLDOWN_DOWN'),
			'MB_TASKS_PULLDOWN_LOADING' => 	Loc::getMessage('MB_TASKS_TASKS_LIST_PULLDOWN_LOADING'),
			'MB_TASKS_ROLES_TASK_ADD' => 	Loc::getMessage('MB_TASKS_ROLES_TASK_ADD'),
		))?>);
		BX['taskroles'] = new BX.Mobile.Tasks.roles(<?=CUtil::PhpToJSObject(array(
			'siteId' => SITE_ID,
			'usePull' => true,
			'counterToRole' => $counterToRole,
			'path' => array(
				'taskCreate' => str_replace(
					array('#TASK_ID#', '#task_id#'),
					0,	// create new task
					$arParams['PATH_TO_USER_TASKS_EDIT']
				)
			)
		))?>);
		BX['taskroles'].instance('taskgroups', BX['taskgroups']);
	</script>

	<?
	$frame->startDynamicWithID("mobile-tasks-roles");
	?>
		<script>
			BX['taskroles'].dynamicActions(<?=CUtil::PhpToJsObject(array(
				'counters' => $counters,
				'userId' => intval($arParams['USER_ID'])
			))?>);
		</script>
	<?

	$frame->finishDynamicWithID("mobile-tasks-roles", $stub = "", $containerId = null, $useBrowserStorage = true);
}