<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

CJSCore::Init("sidepanel");
CJSCore::Init("CJSTask");
CJSCore::Init("tasks_integration_socialnetwork");

global $APPLICATION;

$APPLICATION->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");

Extension::load(['ui.counter', 'ui.label', 'tasks.list.item']);
?>

<?php
if (\Bitrix\Tasks\Util\DisposableAction::needConvertTemplateFiles())
{
	$APPLICATION->IncludeComponent(
		"bitrix:tasks.util.process",
		'',
		array(),
		false,
		array("HIDE_ICONS" => "Y")
	);
}

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."page-one-column");
?>

<?php $APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.header',
	'',
	array(
		'FILTER_ID' => $arParams["FILTER_ID"],
		'GRID_ID'   => $arParams["GRID_ID"],

		'FILTER'    => $arResult['FILTER'],
		'PRESETS'   => $arResult['PRESETS'],

		'SHOW_QUICK_FORM'  => 'Y',
		'GET_LIST_PARAMS'  => $arResult['GET_LIST_PARAMS'],
		'COMPANY_WORKTIME' => $arResult['COMPANY_WORKTIME'],
		'NAME_TEMPLATE'    => $arParams['NAME_TEMPLATE'],
		'PROJECT_VIEW'     => $arParams['PROJECT_VIEW'],

		'USER_ID'  => $arParams['USER_ID'],
		'GROUP_ID' => $arParams['GROUP_ID'],

		'MARK_ACTIVE_ROLE' => $arParams['MARK_ACTIVE_ROLE'],
		'MARK_SECTION_ALL' => $arParams['MARK_SECTION_ALL'],
		'MARK_SECTION_PROJECTS' => $arParams['MARK_SECTION_PROJECTS'],
		'MARK_SPECIAL_PRESET' => $arParams['MARK_SPECIAL_PRESET'],

		'PATH_TO_USER_TASKS'                   => $arParams['PATH_TO_USER_TASKS'],
		'PATH_TO_USER_TASKS_TASK'              => $arParams['PATH_TO_USER_TASKS_TASK'],
		'PATH_TO_USER_TASKS_VIEW'              => $arParams['PATH_TO_USER_TASKS_VIEW'],
		'PATH_TO_USER_TASKS_REPORT'            => $arParams['PATH_TO_USER_TASKS_REPORT'],
		'PATH_TO_USER_TASKS_TEMPLATES'         => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],

		'PATH_TO_GROUP'              => $arParams['PATH_TO_GROUP'],
		'PATH_TO_GROUP_TASKS'        => $arParams['PATH_TO_GROUP_TASKS'],
		'PATH_TO_GROUP_TASKS_TASK'   => $arParams['PATH_TO_GROUP_TASKS_TASK'],
		'PATH_TO_GROUP_TASKS_VIEW'   => $arParams['PATH_TO_GROUP_TASKS_VIEW'],
		'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'],

		'PATH_TO_USER_PROFILE'       => $arParams['PATH_TO_USER_PROFILE'],
		'PATH_TO_MESSAGES_CHAT'      => $arParams['PATH_TO_MESSAGES_CHAT'],
		'PATH_TO_VIDEO_CALL'         => $arParams['PATH_TO_VIDEO_CALL'],
		'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT'],

		'USE_EXPORT'             => 'Y',
		// export on role pages and all
		'USE_AJAX_ROLE_FILTER'  => 'Y',
		'USE_GROUP_BY_SUBTASKS'  => 'Y',
		'USE_GROUP_BY_GROUPS'    => $arParams['NEED_GROUP_BY_GROUPS'] === 'Y' ? 'Y' : 'N',
		'GROUP_BY_PROJECT'       => $arResult['GROUP_BY_PROJECT'],
		'SHOW_USER_SORT'         => 'Y',
		'SORT_FIELD'             => $arParams['SORT_FIELD'],
		'SORT_FIELD_DIR'         => $arParams['SORT_FIELD_DIR'],
		'SHOW_SECTION_TEMPLATES' => $arParams['GROUP_ID'] > 0 ? 'N' : 'Y',
		'DEFAULT_ROLEID'		 =>	$arParams['DEFAULT_ROLEID']
	),
	$component,
	array('HIDE_ICONS' => true)
); ?>

<?php
if (is_array($arResult['ERROR']['FATAL']) && !empty($arResult['ERROR']['FATAL'])):
	foreach ($arResult['ERROR']['FATAL'] as $error):
		echo ShowError($error['MESSAGE']);
	endforeach;

	return;
endif
?>

<? if (is_array($arResult['ERROR']['WARNING'])): ?>
	<? foreach ($arResult['ERROR']['WARNING'] as $error): ?>
		<?=ShowError($error['MESSAGE'])?>
	<? endforeach ?>
<? endif ?>



<?php

//region Navigation
ob_start();
$APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.pagenavigation',
	'',
	[
		'PAGE_NUM' => $arResult['CURRENT_PAGE'],
		'ENABLE_NEXT_PAGE' => $arResult['ENABLE_NEXT_PAGE'],
		'URL' => $APPLICATION->GetCurPage()
	],
	$component,
	array('HIDE_ICONS' => 'Y')
);
$navigationHtml = ob_get_contents();
ob_end_clean();
//endregion

$rowCountHtml = str_replace(
	array('%prefix%', '%all%', '%show%', '%filter%', '%parameters%'),
	array(CUtil::JSEscape(mb_strtolower($arParams['GRID_ID'])), GetMessage('TASKS_ROW_COUNT_TITLE'), GetMessage('TASKS_SHOW_ROW_COUNT'), \CUtil::PhpToJSObject($arParams['GET_LIST_PARAMETERS']['legacyFilter']), \CUtil::PhpToJSObject($arParams['PROVIDER_PARAMETERS'])),
	'<div id="%prefix%_row_count_wrapper" class="tasks-list-row-count-wrapper">%all%: 
		<a id="%prefix%_row_count" onclick="BX.Tasks.GridActions.getTotalCount(\'%prefix%\', %filter%, %parameters%)">
			%show%
		</a>
		<svg class="tasks-circle-loader-circular" viewBox="25 25 50 50">
			<circle class="tasks-circle-loader-path" cx="50" cy="50" r="20" fill="none" stroke-width="1" stroke-miterlimit="10"></circle>
		</svg>
	</div>'
);

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	array(
		'GRID_ID'   => $arParams['GRID_ID'],
		'HEADERS'   => ($arResult['HEADERS'] ?? []),
		'SORT'      => ($arParams['SORT'] ?? []),
		'SORT_VARS' => ($arParams['SORT_VARS'] ?? []),
		'ROWS'      => $arResult['ROWS'],

		'AJAX_MODE'           => 'Y',
		//Strongly required
		"AJAX_OPTION_JUMP"    => "N",
		"AJAX_OPTION_STYLE"   => "N",
		"AJAX_OPTION_HISTORY" => "N",

		"ALLOW_COLUMNS_SORT"      => true,
		"ALLOW_ROWS_SORT"         => $arResult['CAN']['SORT'] || $arParams['SCRUM_BACKLOG'] == 'Y',
		"ALLOW_COLUMNS_RESIZE"    => true,
		"ALLOW_HORIZONTAL_SCROLL" => true,
		"ALLOW_SORT"              => $arParams['SCRUM_BACKLOG'] != 'Y',
		"ALLOW_PIN_HEADER"        => true,
		"ACTION_PANEL"            => $arResult['GROUP_ACTIONS'],

		"SHOW_CHECK_ALL_CHECKBOXES" => true,
		"SHOW_ROW_CHECKBOXES"       => true,
		"SHOW_ROW_ACTIONS_MENU"     => true,
		"SHOW_GRID_SETTINGS_MENU"   => true,
		"SHOW_NAVIGATION_PANEL"     => true,
		"SHOW_PAGINATION"           => true,
		"SHOW_SELECTED_COUNTER"     => true,
		"SHOW_TOTAL_COUNTER"        => true,
		"SHOW_PAGESIZE"             => true,
		"SHOW_ACTION_PANEL"         => true,

		"MESSAGES" => $arResult['MESSAGES'],

		"ENABLE_COLLAPSIBLE_ROWS" => true,
		"SHOW_MORE_BUTTON" => false,
		'~NAV_PARAMS'       => $arResult['GET_LIST_PARAMS']['NAV_PARAMS'],
		"PAGE_SIZES"        => $arResult['PAGE_SIZES'],
		"DEFAULT_PAGE_SIZE" => 50,

		"TOTAL_ROWS_COUNT_HTML" => $rowCountHtml,
		"NAV_STRING" => $navigationHtml,
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
?>

<script>
	BX.ready(
		function() {
			BX.Tasks.GridActions.gridId = '<?=$arParams['GRID_ID']?>';
			BX.Tasks.GridActions.defaultPresetId = '<?=$arResult['DEFAULT_PRESET_KEY']?>';
			BX.message({
				TASKS_CONFIRM_GROUP_ACTION: '<?=GetMessage('TASKS_CONFIRM_GROUP_ACTION')?>',
				TASKS_DELETE_SUCCESS: '<?=GetMessage('TASKS_DELETE_SUCCESS')?>',

				TASKS_MARK: '<?=GetMessageJS('TASKS_JS_MARK')?>',
				TASKS_MARK_NONE: '<?=GetMessageJS('TASKS_JS_MARK_NONE')?>',
				TASKS_MARK_N: '<?=GetMessageJS('TASKS_JS_MARK_N')?>',
				TASKS_MARK_P: '<?=GetMessageJS('TASKS_JS_MARK_P')?>',

				TASKS_TASK_CONFIRM_START_TIMER_TITLE: '<?=GetMessageJS('TASKS_TASK_CONFIRM_START_TIMER_TITLE')?>',
				TASKS_TASK_CONFIRM_START_TIMER: '<?=GetMessageJS('TASKS_TASK_CONFIRM_START_TIMER')?>',
				TASKS_CLOSE_PAGE_CONFIRM: '<?=GetMessageJS('TASKS_CLOSE_PAGE_CONFIRM')?>'
			});

			BX.Tasks.GridInstance = new BX.Tasks.Grid(<?=Json::encode([
				'gridId' => $arParams['GRID_ID'],
				'userId' => $arResult['USER_ID'],
				'ownerId' => $arResult['OWNER_ID'],
				'groupId' => (int)$arParams['GROUP_ID'],
				'sorting' => $arResult['SORTING'],
				'groupByGroups' => ($arResult['GROUP_BY_PROJECT'] ? 'true' : 'false'),
				'groupBySubTasks' => ($arResult['GROUP_BY_SUBTASK'] ? 'true' : 'false'),
				'taskList' => $arResult['LIST'],
				'arParams' => $arParams,
			])?>);

			new BX.Tasks.Grid.Sorting({
				gridId: '<?=$arParams['GRID_ID']?>',
				currentGroupId: <?=intval($arParams['GROUP_ID'])?>,
				treeMode: <?=($arParams["NEED_GROUP_BY_SUBTASKS"] === "Y") ? "true" : "false"?>,
				messages: {
					TASKS_ACCESS_DENIED: "<?=GetMessageJS("TASKS_ACCESS_DENIED")?>"
				}
			});
		});
</script>
