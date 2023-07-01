<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Slider\Exception\SliderException;
use Bitrix\Tasks\Slider\Factory\SliderFactory;
use Bitrix\Tasks\UI\ScopeDictionary;
use Bitrix\Tasks\Update\TagConverter;

Loc::loadMessages(__FILE__);

CJSCore::Init([
	'clipboard',
	'sidepanel',
	'tasks_integration_socialnetwork',
	'CJSTask',
]);

global $APPLICATION;

Asset::getInstance()->addJs("/bitrix/js/tasks/task-iframe-popup.js");
$APPLICATION->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");

Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.counter',
	'ui.entity-selector',
	'ui.icons.b24',
	'ui.label',
	'ui.migrationbar',
	'ui.tour',
	'tasks.runtime',
	'tasks.task-model',
]);

//Checking for working tags agent
$tagsAreConverting = TagConverter::isProceed();

$APPLICATION->IncludeComponent(
	"bitrix:tasks.iframe.popup",
	".default",
	[],
	null,
	["HIDE_ICONS" => "Y"]
);

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty(
	'BodyClass',
	"{$bodyClass} page-one-column transparent-workarea"
);

$APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.header',
	'',
	[
		'FILTER_ID' => $arParams['FILTER_ID'] ?? null,
		'GRID_ID' => $arParams['GRID_ID'] ?? null,
		'FILTER' => $arResult['FILTER'] ?? null,
		'PRESETS' => $arResult['PRESETS'] ?? null,

		'SHOW_QUICK_FORM' => 'Y',
		'GET_LIST_PARAMS' => $arResult['GET_LIST_PARAMS'] ?? null,
		'COMPANY_WORKTIME' => $arResult['COMPANY_WORKTIME'] ?? null,
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'] ?? null,
		'PROJECT_VIEW' => $arParams['PROJECT_VIEW'] ?? null,

		'USER_ID' => $arParams['USER_ID'] ?? null,
		'GROUP_ID' => $arParams['GROUP_ID'] ?? null,

		'MARK_ACTIVE_ROLE' => $arParams['MARK_ACTIVE_ROLE'] ?? null,
		'MARK_SECTION_ALL' => $arParams['MARK_SECTION_ALL'] ?? null,
		'MARK_SECTION_PROJECTS' => $arParams['MARK_SECTION_PROJECTS'] ?? null,
		'MARK_SPECIAL_PRESET' => $arParams['MARK_SPECIAL_PRESET'] ?? null,

		'PATH_TO_USER_TASKS' => $arParams['PATH_TO_USER_TASKS'] ?? null,
		'PATH_TO_USER_TASKS_TASK' => $arParams['PATH_TO_USER_TASKS_TASK'] ?? null,
		'PATH_TO_USER_TASKS_VIEW' => $arParams['PATH_TO_USER_TASKS_VIEW'] ?? null,
		'PATH_TO_USER_TASKS_REPORT' => $arParams['PATH_TO_USER_TASKS_REPORT'] ?? null,
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'] ?? null,
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'] ?? null,
		'PATH_TO_GROUP' => $arParams['PATH_TO_GROUP'] ?? null,
		'PATH_TO_GROUP_TASKS' => $arParams['PATH_TO_GROUP_TASKS'] ?? null,
		'PATH_TO_GROUP_TASKS_TASK' => $arParams['PATH_TO_GROUP_TASKS_TASK'] ?? null,
		'PATH_TO_GROUP_TASKS_VIEW' => $arParams['PATH_TO_GROUP_TASKS_VIEW'] ?? null,
		'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'] ?? null,
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'] ?? null,
		'PATH_TO_MESSAGES_CHAT' => $arParams['PATH_TO_MESSAGES_CHAT'] ?? null,
		'PATH_TO_VIDEO_CALL' => $arParams['PATH_TO_VIDEO_CALL'] ?? null,
		'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT'] ?? null,

		'USE_EXPORT' => 'Y',
		// export on role pages and all
		'USE_AJAX_ROLE_FILTER' => 'Y',
		'USE_GROUP_BY_SUBTASKS' => 'Y',
		'USE_GROUP_BY_GROUPS' => ((isset($arParams['NEED_GROUP_BY_GROUPS']) && $arParams['NEED_GROUP_BY_GROUPS'] === 'Y') ? 'Y' : 'N'),
		'GROUP_BY_PROJECT' => $arResult['GROUP_BY_PROJECT'] ?? null,
		'SHOW_USER_SORT' => 'Y',
		'SORT_FIELD' => $arParams['SORT_FIELD'] ?? null,
		'SORT_FIELD_DIR' => $arParams['SORT_FIELD_DIR'] ?? null,
		'SHOW_SECTION_TEMPLATES' => ((isset($arParams['GROUP_ID']) && $arParams['GROUP_ID'] > 0) ? 'N' : 'Y'),
		'DEFAULT_ROLEID' =>	$arParams['DEFAULT_ROLEID'] ?? null,

		'SCOPE' => ScopeDictionary::SCOPE_TASKS_GRID,
	],
	$component,
	['HIDE_ICONS' => true]
);
?>

<?php
if (
	isset($arResult['ERROR']['FATAL'])
	&& is_array($arResult['ERROR']['FATAL'])
	&& !empty($arResult['ERROR']['FATAL'])
):
	foreach ($arResult['ERROR']['FATAL'] as $error):
		echo ShowError($error['MESSAGE']);
	endforeach;

	return;
endif
?>

<? if (
	isset($arResult['ERROR']['WARNING'])
	&& is_array($arResult['ERROR']['WARNING'])
): ?>
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
		'PAGE_NUM' => $arResult['CURRENT_PAGE'] ?? null,
		'ENABLE_NEXT_PAGE' => $arResult['ENABLE_NEXT_PAGE'] ?? null,
		'URL' => $APPLICATION->GetCurPage() . '?F_STATE=sV80',
	],
	$component,
	array('HIDE_ICONS' => 'Y')
);
$navigationHtml = ob_get_contents();
ob_end_clean();
//endregion

$rowCountHtml = str_replace(
	[
		'%prefix%',
		'%all%',
		'%show%',
		'%userid%',
		'%groupid%',
		'%parameters%'
	],
	[
		CUtil::JSEscape(mb_strtolower($arParams['GRID_ID'])),
		GetMessage('TASKS_ROW_COUNT_TITLE'),
		GetMessage('TASKS_SHOW_ROW_COUNT'),
		$arParams['USER_ID'],
		$arParams['GROUP_ID'],
		\CUtil::PhpToJSObject($arParams['PROVIDER_PARAMETERS'])
	],
	'<div id="%prefix%_row_count_wrapper" class="tasks-list-row-count-wrapper">%all%: 
		<a id="%prefix%_row_count" onclick="BX.Tasks.GridActions.getTotalCount(\'%prefix%\', %userid%, %groupid%, %parameters%)">
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
		'STUB'      => (count($arResult['ROWS']) > 0 ? null : $arResult['STUB']),

		'AJAX_MODE'           => 'Y',
		//Strongly required
		"AJAX_OPTION_JUMP"    => "N",
		"AJAX_OPTION_STYLE"   => "N",
		"AJAX_OPTION_HISTORY" => "N",

		"ALLOW_COLUMNS_SORT"      => true,
		"ALLOW_ROWS_SORT"         => $arResult['CAN']['SORT'],
		"ALLOW_COLUMNS_RESIZE"    => true,
		"ALLOW_HORIZONTAL_SCROLL" => true,
		"ALLOW_SORT"              => true,
		"ALLOW_PIN_HEADER"        => true,
		'ALLOW_CONTEXT_MENU'      => true,
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
		"SHOW_MORE_BUTTON"			=> true,
		"ENABLE_NEXT_PAGE"			=> $arResult['ENABLE_NEXT_PAGE'],
		"CURRENT_PAGE"				=> $arResult['CURRENT_PAGE'],
		"NAV_PARAM_NAME" 			=> 'page',

		"MESSAGES" => $arResult['MESSAGES'],

		"ENABLE_COLLAPSIBLE_ROWS" => true,
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
			BX.Tasks.GridActions.tagsAreConverting = '<?=$tagsAreConverting?>';

			BX.message({
				TASKS_CONFIRM_GROUP_ACTION: '<?=GetMessageJS('TASKS_CONFIRM_GROUP_ACTION')?>',
				TASKS_DELETE_SUCCESS: '<?=GetMessageJS('TASKS_DELETE_SUCCESS')?>',
				TASKS_LIST_ACTION_PING_NOTIFICATION: '<?= GetMessageJS('TASKS_LIST_ACTION_PING_NOTIFICATION') ?>',
				TASKS_LIST_GROUP_ACTION_PING_NOTIFICATION: '<?= GetMessageJS('TASKS_LIST_GROUP_ACTION_PING_NOTIFICATION') ?>',
				TASKS_LIST_ACTION_COPY_LINK_NOTIFICATION: '<?= GetMessageJS('TASKS_LIST_ACTION_COPY_LINK_NOTIFICATION') ?>',
				TASKS_MARK: '<?=GetMessageJS('TASKS_JS_MARK_MSGVER_1')?>',
				TASKS_MARK_NONE: '<?=GetMessageJS('TASKS_JS_MARK_NONE')?>',
				TASKS_MARK_N: '<?=GetMessageJS('TASKS_JS_MARK_N')?>',
				TASKS_MARK_P: '<?=GetMessageJS('TASKS_JS_MARK_P')?>',
				TASKS_TASK_CONFIRM_START_TIMER_TITLE: '<?=GetMessageJS('TASKS_TASK_CONFIRM_START_TIMER_TITLE')?>',
				TASKS_TASK_CONFIRM_START_TIMER: '<?=GetMessageJS('TASKS_TASK_CONFIRM_START_TIMER')?>',
				TASKS_CLOSE_PAGE_CONFIRM: '<?=GetMessageJS('TASKS_CLOSE_PAGE_CONFIRM')?>',
				TASKS_TASK_LIST_TAGS_ARE_CONVERTING_TITLE: '<?=GetMessageJS('TASKS_TASK_LIST_TAGS_ARE_CONVERTING_TITLE')?>',
				TASKS_TASK_LIST_TAGS_ARE_CONVERTING_TEXT: '<?=GetMessageJS('TASKS_TASK_LIST_TAGS_ARE_CONVERTING_TEXT')?>',
				TASKS_TASK_LIST_TAGS_ARE_CONVERTING_COME_BACK_LATER: '<?=GetMessageJS('TASKS_TASK_LIST_TAGS_ARE_CONVERTING_COME_BACK_LATER')?>',
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
				'calendarSettings' => $arResult['CALENDAR_SETTINGS'],
				'lastGroupId' => $arResult['LAST_GROUP_ID'],
				'migrationBarOptions' => [
					'title' => Loc::getMessage('TASKS_GRID_STUB_MIGRATION_TITLE'),
					'buttonMigrate' => Loc::getMessage('TASKS_GRID_STUB_MIGRATION_BUTTON_MIGRATE'),
					'other' => Loc::getMessage('TASKS_GRID_STUB_MIGRATION_OTHER'),
					'items' => [
						"{$templateFolder}/images/tasks-projects-jira.svg",
						"{$templateFolder}/images/tasks-projects-asana.svg",
						"{$templateFolder}/images/tasks-projects-trello.svg",
					],
				],
			])?>);

			new BX.Tasks.Grid.Sorting({
				gridId: '<?=$arParams['GRID_ID']?>',
				currentGroupId: <?=intval($arParams['GROUP_ID'])?>,
				treeMode: <?=($arParams["NEED_GROUP_BY_SUBTASKS"] === "Y") ? "true" : "false"?>,
				messages: {
					TASKS_ACCESS_DENIED: "<?=GetMessageJS("TASKS_ACCESS_DENIED")?>"
				}
			});

			BX.Tasks.TourGuideController = new BX.Tasks.TourGuideController(<?=
				Json::encode([
					'gridId' => $arParams['GRID_ID'],
					'userId' => $arResult['USER_ID'],
					'tours' => $arResult['tours'],
				])
			?>);
		}
	);
</script>

<?php if (isset($arParams['TAGS_SLIDER']))
{
	$ownerId = (int)$arParams['USER_ID'];
	$queryParams = '';
	if ($arParams['TAGS_SLIDER_GROUP_ID'])
	{
		$queryParams = '?GROUP_ID=' . $arParams['TAGS_SLIDER_GROUP_ID'];
	}

	$factory = new SliderFactory();
	try
	{
		$factory->setQueryParams($queryParams);

		$slider = $factory->createEntityListSlider(SliderFactory::TAGS, $ownerId, SliderFactory::PERSONAL_CONTEXT);
		$slider->open();
	}
	catch (SliderException $exception)
	{
		$exception->show();
	}
}