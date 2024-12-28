<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Tasks\Update\TagConverter;
use Bitrix\UI\Toolbar\Facade\Toolbar;

/** @var $APPLICATION CMain */
/** @var array $arResult */
/** @var array $arParams */
/** @var $component CBitrixComponent */
/** @var $this CBitrixComponentTemplate */

Extension::load([
	'main.core',
	'ui.sidepanel.layout',
	'sidepanel',
	'tasks.tag',
]);

if ((int)$arResult['GROUP_ID'] > 0 && !$arResult['CAN_SEE_GROUP_TAGS'])
{
	$APPLICATION->setTitle(Loc::getMessage('TASKS_TAG_LIST_TAGS_APP_TITLE'));

	$APPLICATION->IncludeComponent(
		'bitrix:tasks.error',
		'',
		[
			'TITLE' => Loc::getMessage('TASKS_TAG_LIST_FORBIDDEN_SEE_GROUP_TAGS_TITLE_OR_NOT_FOUND'),
			'DESCRIPTION' => Loc::getMessage('TASKS_TAG_LIST_FORBIDDEN_SEE_GROUP_TAGS_DESCRIPTION'),
		]
	);
	return;
}

if ((int)$arResult['GROUP_ID'] > 0)
{
	$title = $arResult['IS_COLLAB']
		? 'TASKS_TAG_LIST_GROUP_TAGS_COLLAB_APP_TITLE'
		: 'TASKS_TAG_LIST_GROUP_TAGS_GRID_APP_TITLE';

	$APPLICATION->setTitle(Loc::getMessage($title, [
		'#PROJECT#' => $arResult['GROUP_NAME'],
	]));
}
else
{
	$APPLICATION->setTitle(Loc::getMessage('TASKS_TAG_LIST_MY_TAGS_GRID_APP_TITLE'));
}

if (TagConverter::isProceed())
{
	$APPLICATION->IncludeComponent('bitrix:tasks.interface.emptystate', '', [
		'TITLE' => Loc::getMessage('TAGS_IS_CONVERTING_TITLE'),
		'TEXT' => Loc::getMessage('TAGS_IS_CONVERTING_TEXT'),
	]);

	return;
}

Toolbar::addFilter([
	'FILTER_ID' => $arResult['FILTER_ID'],
	'GRID_ID' => $arResult['GRID_ID'],
	'FILTER' => $arResult['FILTER'],
	'ENABLE_LABEL' => true,
	'ENABLE_LIVE_SEARCH' => true,
]);

ob_start();
?>
<div class="tasks-tag-list-page-nav">
	<?php
	$APPLICATION->IncludeComponent(
		'bitrix:main.pagenavigation',
		'modern',
		[
			'NAV_OBJECT' => $arResult['NAV_OBJECT'],
			'PAGE_NUM' => $arResult['CURRENT_PAGE'],
			'ENABLE_NEXT_PAGE' => $arResult['ENABLE_NEXT_PAGE'],
			'URL' => $APPLICATION->GetCurPage(),
			'ENABLE_LAST_PAGE' => false,
		],
		$component,
		['HIDE_ICONS' => 'Y']
	);
	?>
</div>
<?php
$navigationHtml = ob_get_clean();

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'FILTER_ID' => $arResult['FILTER_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'COLUMNS' => $arResult['COLUMNS'],
		'ROWS' => $arResult['ROWS'],

		'ACTION_PANEL' => $arResult['ACTION_PANEL'],

		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_STYLE' => 'N',
		'AJAX_OPTION_HISTORY' => 'N',

		'ALLOW_SORT' => true,
		'ENABLE_FIELDS_SEARCH' => true,

		'PATH_TO_USER_TASKS_TASK' => $arParams['PATH_TO_USER_TASKS_TASK'],

		'SHOW_CHECK_ALL_CHECKBOXES' => $arResult['CAN_USE_GRID_ACTIONS'],
		'SHOW_ROW_CHECKBOXES' => $arResult['CAN_USE_GRID_ACTIONS'],
		'SHOW_ROW_ACTIONS_MENU' => $arResult['CAN_USE_GRID_ACTIONS'],
		'SHOW_GRID_SETTINGS_MENU' => true,
		'SHOW_SELECTED_COUNTER' => false,
		'SHOW_TOTAL_COUNTER' => false,
		'SHOW_ACTION_PANEL' => $arResult['CAN_USE_GRID_ACTIONS'],

		'NAV_PARAMS' => [
			'SEF_MODE' => 'N',
		],

		'TOTAL_ROWS_COUNT' => $arResult['NAV_OBJECT']->getRecordCount(),

		'NAV_PARAM_NAME' => 'page',
		'NAV_STRING' => $navigationHtml,
		'ENABLE_NEXT_PAGE' => $arResult['ENABLE_NEXT_PAGE'],
		'CURRENT_PAGE' => $arResult['CURRENT_PAGE'],

		'POPUP_COMPONENT_NAME' => 'bitrix:tasks.tag.list',
	],
	$component,
	['HIDE_ICONS' => 'Y']
);
?>
<script>
	BX.ready(function() {
		BX.Tasks.TagActionsObject =
			new BX.Tasks.TagActions(
				'<?=$arParams['PATH_TO_USER_TASKS_TASK']?>',
				'<?=$arParams['PATH_TO_USER']?>',
				null,
				null,
				'<?=$arResult['GROUP_ID']?>',
			);
	});
</script>
