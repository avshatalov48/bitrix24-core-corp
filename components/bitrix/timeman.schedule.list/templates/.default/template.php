<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Types;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Service\DependencyManager;
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/js/timeman/component/basecomponent.js');

$urlManager = DependencyManager::getInstance()->getUrlManager();
if (defined('SITE_TEMPLATE_ID') && SITE_TEMPLATE_ID == 'bitrix24')
{
	if ($arResult['SHOW_ADD_SCHEDULE_BUTTON'])
	{
		$this->SetViewTarget('pagetitle'); ?>
		<a href="<?= $arResult['addScheduleUrl'] ?>" class="ui-btn ui-btn-md ui-btn-primary">
			<?= htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_LIST_ADD')) ?>
		</a>
		<? $this->EndViewTarget();
	}
} ?>
<?
foreach ($arResult['ITEMS'] as $item)
{
	$gridActions = [];
	if ($item['CAN_READ_SHIFT_PLAN'])
	{
		if ($item['IS_SHIFTED'])
		{
			$gridActions[] = [
				'TITLE' => Loc::getMessage('TM_SCHEDULE_LIST_ACTION_PLAN'),
				'TEXT' => htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_LIST_ACTION_PLAN')),
				'HREF' => $urlManager->getUriTo($urlManager::URI_SCHEDULE_SHIFTPLAN, ['SCHEDULE_ID' => $item['ID']]),
			];
		}
	}
	if ($item['CAN_READ_WORKTIME'])
	{
		$gridActions[] = [
			'TITLE' => htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_LIST_ACTION_WORKTIME_STATS')),
			'TEXT' => htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_LIST_ACTION_WORKTIME_STATS')),
			'HREF' => $urlManager->getUriTo($urlManager::URI_WORKTIME_STATS),
		];
	}

	if ($item['CAN_READ_SCHEDULE'])
	{
		$updateText = $item['CAN_UPDATE_SCHEDULE'] ? Loc::getMessage('TM_SCHEDULE_LIST_ACTION_EDIT') : Loc::getMessage('TM_SCHEDULE_LIST_ACTION_READ');
		$gridActions[] = [
			'TITLE' => htmlspecialcharsbx($updateText),
			'TEXT' => htmlspecialcharsbx($updateText),
			'HREF' => $urlManager->getUriTo($urlManager::URI_SCHEDULE_UPDATE, ['SCHEDULE_ID' => $item['ID']]),
			'DEFAULT' => true,
		];
	}
	if ($item['CAN_EDIT'])
	{
		$gridActions[] = [
			'TITLE' => htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_LIST_ACTION_DELETE')),
			'TEXT' => htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_LIST_ACTION_DELETE')),
			'ONCLICK' => 'BX.Timeman.Component.Schedule.List' . CUtil::JSEscape($component->getComponentId()) . '.onDeleteScheduleClick(event, ' . intval($item['ID']) . ', \'' . CUtil::JSEscape($item['NAME']) . '\')',
		];
	}
	$arResult['SHOW_ROW_ACTIONS_MENU'] = $arResult['SHOW_ROW_ACTIONS_MENU'] || count($gridActions);

	$arResult['GRID_DATA'][] = [
		'id' => $item['ID'],
		'actions' => $gridActions,
		'data' => $item,
		'editable' => (bool) ($arResult['CAN_EDIT'] ?? false),
		'columns' => [
			'NAME' => $item['CAN_READ_SCHEDULE'] ?
				'<a href="' . $urlManager->getUriTo('scheduleUpdate', ['SCHEDULE_ID' => $item['ID']]) . '"' .
				'" target="_self" ' .
				'" data-role="name"' .
				'>' .
				$item['NAME'] . '</a>'
				: $item['NAME'],
			'SCHEDULE_TYPE' => '<span ' .
							   '" data-role="type">' . $item['SCHEDULE_TYPE'] . '</span>',
			'REPORT_PERIOD' => '<span ' .
							   '" data-role="period">' . $item['REPORT_PERIOD'] . '</span>',
			'USER_COUNT' => '<span ' .
							'" data-role="user-count">' . intval($item['USER_COUNT']) . '</span>',
		],
	];
}

/** @var \Bitrix\Main\UI\PageNavigation $navigation */
$navigation = $arResult['NAVIGATION'];
$actionPanelData = [];
if ($arResult['canDeleteSchedules'])
{
	$actionPanelData['GROUPS'] = [
		[
			'ITEMS' => [
				[
					'TYPE' => Types::BUTTON,
					'ID' => 'apply_button',
					'CLASS' => 'apply',
					'TEXT' => htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_LIST_ACTION_DELETE')),
					'ONCHANGE' => [
						[
							'ACTION' => Actions::CALLBACK,
							'CONFIRM' => true,
							'DATA' => [
								[
									'JS' => 'BX.Timeman.Component.Schedule.List' . CUtil::JSEscape($component->getComponentId()) . '.onDeleteSchedulesListClick()',
								],
							],
						],
					],
				],
			],
		],
	];
}

$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
	'GRID_ID' => $arResult['GRID_ID'],
	'HEADERS' => $arResult['HEADERS'],
	'ROWS' => $arResult['GRID_DATA'],

	'SORT' => isset($arParams['SORT']) ? $arParams['SORT'] : [],
	'SORT_VARS' => isset($arParams['SORT_VARS']) ? $arParams['SORT_VARS'] : [],

	'NAV_OBJECT' => $navigation,
	'PAGE_SIZES' => $navigation->getPageSizes(),
	'DEFAULT_PAGE_SIZE' => $navigation->getPageSize(),
	'TOTAL_ROWS_COUNT' => $navigation->getRecordCount(),
	'NAV_PARAM_NAME' => $navigation->getId(),
	'CURRENT_PAGE' => $navigation->getCurrentPage(),
	'PAGE_COUNT' => $navigation->getPageCount(),
	'SHOW_MORE_BUTTON' => true,

	'SHOW_CHECK_ALL_CHECKBOXES' => $arResult['SHOW_CHECK_ALL_CHECKBOXES'],
	'SHOW_ROW_CHECKBOXES' => $arResult['SHOW_ROW_CHECKBOXES'],
	'SHOW_ROW_ACTIONS_MENU' => $arResult['SHOW_ROW_ACTIONS_MENU'],
	'SHOW_SELECTED_COUNTER' => $arResult['SHOW_SELECTED_COUNTER'],
	'SHOW_ACTION_PANEL' => $arResult['SHOW_ACTION_PANEL'],
	'SHOW_GRID_SETTINGS_MENU' => true,
	'SHOW_NAVIGATION_PANEL' => true,
	'SHOW_PAGINATION' => true,
	'SHOW_TOTAL_COUNTER' => true,
	'SHOW_PAGESIZE' => true,

	'AJAX_MODE' => 'Y',
	'AJAX_OPTION_JUMP' => 'N',
	'AJAX_OPTION_STYLE' => 'N',
	'AJAX_OPTION_HISTORY' => 'N',

	'ALLOW_COLUMNS_SORT' => true,
	'ALLOW_ROWS_SORT' => false,
	'ALLOW_COLUMN_RESIZE' => true,
	'ALLOW_HORIZONTAL_SCROLL' => true,
	'ALLOW_SORT' => true,
	'ALLOW_PIN_HEADER' => true,

	'ACTION_PANEL' => $actionPanelData,

	'MESSAGES' => $arResult['MESSAGES'] ?? '',
], $component, ['HIDE_ICONS' => 'Y']);
?>
<script>
	BX.ready(function ()
	{
		BX.message({
			TM_SCHEDULE_DELETE_CONFIRM_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_DELETE_CONFIRM_TITLE'))?>',
			TM_SCHEDULE_DELETE_CONFIRM: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_DELETE_CONFIRM'))?>',
			TM_SCHEDULE_LIST_DELETE_CONFIRM: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_LIST_DELETE_CONFIRM'))?>',
			TM_SCHEDULE_DELETE_CONFIRM_NO: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_DELETE_CONFIRM_NO'))?>',
			TM_SCHEDULE_DELETE_CONFIRM_YES: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_DELETE_CONFIRM_YES'))?>'
		});

		BX.Timeman.Component.Schedule.List<?=CUtil::JSEscape($component->getComponentId())?> = new BX.Timeman.Component.Schedule.List({
			gridId: '<?= CUtil::JSEscape($arResult['GRID_ID'])?>',
			scheduleCreateSliderWidth: 1400
		});
	});
</script>