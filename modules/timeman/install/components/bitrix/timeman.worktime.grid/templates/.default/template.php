<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Timeman\Helper\DateTimeHelper;
use Bitrix\UI\Buttons\Color;

if ($arResult['PARTIAL_ITEM'] === 'shiftCell')
{
	return;
}
\Bitrix\Main\Loader::includeModule('ui');
$dateHelper = new DateTimeHelper();
Extension::load(['ui.buttons', 'ui.buttons.icons', 'ui.hint', 'loader']);
CJSCore::Init(['timeman', 'sidepanel', 'date']);
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/js/timeman/component/basecomponent.js');

$filterId = $arResult['FILTER_ID'];
if ($arResult['showFilter'])
{
	\Bitrix\UI\Toolbar\Facade\Toolbar::addFilter([
		'FILTER_ID' => $arResult['FILTER']['ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'FILTER' => $arResult['FILTER']['FIELDS'],
		'FILTER_ROWS' => $arResult['FILTER']['ROWS'],
		'FILTER_PRESETS' => $arResult['FILTER']['PRESETS'],
		'ENABLE_LIVE_SEARCH' => false,
		'ENABLE_LABEL' => true,
		'RESET_TO_DEFAULT_MODE' => true,
		'VALUE_REQUIRED' => true,
	]);
}
?>
<? if ($arResult['SHOW_SCHEDULES_LIST_BTN']):
	\Bitrix\UI\Toolbar\Facade\Toolbar::addButton(
		(new \Bitrix\UI\Buttons\SettingsButton([]))
			->setDataRole('worktime-grid-config-btn')
	);
endif; ?>
<? if ($arResult['SHOW_SHIFTPLAN_LIST_BTN']):
	\Bitrix\UI\Toolbar\Facade\Toolbar::addButton(
		(new \Bitrix\UI\Buttons\Button([]))
			->setText(htmlspecialcharsbx(Loc::getMessage('TM_WORKTIME_STATS_SHIFTPLANS')))
			->setDataRole('shift-plans-btn')
			->setColor(Bitrix\UI\Buttons\Color::LIGHT_BORDER)
			->setDropdown(true)
	);
endif; ?>
<? if ($arResult['SHOW_ADD_SCHEDULE_BTN']):
	\Bitrix\UI\Toolbar\Facade\Toolbar::addButton(
		(new \Bitrix\UI\Buttons\Button([]))
			->setText(htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_LIST_ADD')))
			->setDataRole('timeman-add-schedule-btn')
			->setColor(Bitrix\UI\Buttons\Color::PRIMARY)
	);
endif; ?>
<? if ($arResult['SHOW_PRINT_BTN']):
	\Bitrix\UI\Toolbar\Facade\Toolbar::addButton(
		(new \Bitrix\UI\Buttons\Button([]))
			->setDataRole('print-shift-plan-btn')
			->setColor(Bitrix\UI\Buttons\Color::LIGHT_BORDER)
			->setIcon(Bitrix\UI\Buttons\Icon::PRINTER)
	);
endif; ?>
<? if ($arResult['SHOW_EDIT_SCHEDULE_BTN']):
	\Bitrix\UI\Toolbar\Facade\Toolbar::addButton(
		(new \Bitrix\UI\Buttons\SettingsButton([]))
			->setDataRole('edit-schedule-btn')
			->setLink($arResult['URLS']['SCHEDULE_EDIT'])
	);
endif; ?>
<? if ($arResult['SHOW_CREATE_SHIFT_BTN']):
	\Bitrix\UI\Toolbar\Facade\Toolbar::addButton(
		(new \Bitrix\UI\Buttons\Button([]))
			->setDataRole('add-shift-btn')
			->setColor(Color::PRIMARY)
			->setText(htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_PLAN_WORKSHIFT_ADD')))
	);
endif; ?>

<div class="timeman-report-container timeman-report-container-plan" data-role="shift-records-container">
	<div class="timeman-top-block">
		<div class="timeman-top-title-container" id="timeman-grid-navigation-container">
			<? $this->setViewTarget('timeman-grid-navigation-container'); ?>
			<? // this html block will be replaced after every grid reload (for updating hrefs to next and prev period) ?>
			<div class="timeman-top-title-month" data-role="tm-grid-navigation-arrows">
				<a href="<?= $arResult['URLS']['PERIOD_PREV'] ?>" class="timeman-navigation-previous"
						data-start-datesel="<?= $arResult['URLS']['PERIOD_PREV_PARTS']['REPORT_PERIOD_datesel'] ?>"
						data-start-to="<?= $arResult['URLS']['PERIOD_PREV_PARTS']['REPORT_PERIOD_to'] ?>"
						data-start-from="<?= $arResult['URLS']['PERIOD_PREV_PARTS']['REPORT_PERIOD_from'] ?>"
						data-role="navigation-period"></a>

				<span class="timeman-navigation-current">
					<h2 class="timeman-top-title" data-role="dates-calendar-toggle">
						<input type="hidden" value="<?php echo reset($arResult['DATES'])->toString(); ?>" data-role="month-navigation">
						<?php echo $dateHelper->formatDate($arResult['TIMEMAN_WORKTIME_GRID_COLUMNS_DATE_FORMAT_DAY_FULL_MONTH'], reset($arResult['DATES'])); ?>
						-
						<?php echo $dateHelper->formatDate($arResult['TIMEMAN_WORKTIME_GRID_COLUMNS_DATE_FORMAT_DAY_FULL_MONTH'], end($arResult['DATES'])); ?>
					</h2>
				</span>

				<a href="<?= $arResult['URLS']['PERIOD_NEXT'] ?>" class="timeman-navigation-next"
						data-start-datesel="<?= $arResult['URLS']['PERIOD_NEXT_PARTS']['REPORT_PERIOD_datesel'] ?>"
						data-start-to="<?= $arResult['URLS']['PERIOD_NEXT_PARTS']['REPORT_PERIOD_to'] ?>"
						data-start-from="<?= $arResult['URLS']['PERIOD_NEXT_PARTS']['REPORT_PERIOD_from'] ?>"
						data-role="navigation-period"></a>
			</div>
			<? $this->endViewTarget(); ?>
			<?= $APPLICATION->getViewContent('timeman-grid-navigation-container') ?>
		</div>

		<div class="timeman-top-title-right">
			<div class="timeman-top-title-today <?php if ($arResult['SHOW_GRID_SETTINGS_BTN']): ?>timeman-top-title-today-separator-right<? endif; ?>">
				<a href="<?= $arResult['URLS']['PERIOD_TODAY'] ?>" class="timeman-top-title-today-text"
						data-role="tm-navigation-today"
						data-start-datesel="<?= $arResult['URLS']['PERIOD_TODAY_PARTS']['REPORT_PERIOD_datesel'] ?>"
						data-start-to="<?= $arResult['URLS']['PERIOD_TODAY_PARTS']['REPORT_PERIOD_to'] ?>"
						data-start-from="<?= $arResult['URLS']['PERIOD_TODAY_PARTS']['REPORT_PERIOD_from'] ?>"><?=
					htmlspecialcharsbx(Loc::getMessage('TM_WORKTIME_GRID_TODAY')); ?></a>
			</div>
			<?php if ($arResult['SHOW_GRID_SETTINGS_BTN']): ?>
				<div class="timeman-top-title-settings" data-role="grid-options">
					<span class="timeman-top-title-settings-icon"></span>
				</div>
			<? endif; ?>
		</div>
	</div>
	<?
	// it is for partial dynamic html replacement after grid reload
	$this->setViewTarget('timeman-grid-navigation-container-script'); ?>
	<script>
		BX('timeman-grid-navigation-container').innerHTML = '<?=\CUtil::jsEscape($APPLICATION->getViewContent('timeman-grid-navigation-container')) ?>';
		BX.Timeman.Component.Worktime.Grid<?=CUtil::JSEscape($arResult['GRID_ID'])?>.addEventHandlersInsideGrid();
	</script>
	<?
	$this->endViewTarget();
	addEventHandler('main', 'onAfterAjaxResponse', function () {
		global $APPLICATION;
		return $APPLICATION->getViewContent('timeman-grid-navigation-container-script');
	});
	?>
	<?
	/** @var \Bitrix\Main\UI\PageNavigation $navigation */
	$navigation = $arResult['NAV_OBJECT'];
	foreach ($arResult['HEADERS'] as $index => $item)
	{
		$arResult['HEADERS'][$index]['width'] = '110';
		if ($index === 0)
		{
			$arResult['HEADERS'][$index]['width'] = '230';
		}
		if (in_array($item['id'], ['WORKED_DAYS', 'WORKED_HOURS', 'PERCENTAGE_OF_VIOLATIONS'], true))
		{
			$arResult['HEADERS'][$index]['width'] = '80';
		}
	}
	$APPLICATION->includeComponent('bitrix:main.ui.grid', '', [
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'ROWS' => $arResult['ROWS'],

		'NAV_OBJECT' => $navigation,
		'PAGE_SIZES' => $navigation->getPageSizes(),
		'DEFAULT_PAGE_SIZE' => $navigation->getPageSize(),
		'TOTAL_ROWS_COUNT' => $navigation->getRecordCount(),
		'NAV_PARAM_NAME' => $navigation->getId(),
		'CURRENT_PAGE' => $navigation->getCurrentPage(),
		'PAGE_COUNT' => $navigation->getPageCount(),
		'SHOW_MORE_BUTTON' => true,

		'SHOW_SELECTED_COUNTER' => false,
		'SHOW_CHECK_ALL_CHECKBOXES' => false,
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_ROW_ACTIONS_MENU' => false,
		'SHOW_GRID_SETTINGS_MENU' => false,
		'SHOW_PAGESIZE' => true,
		'SHOW_ACTION_PANEL' => true,
		'ALLOW_STICKED_COLUMNS' => true,


		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_JUMP' => "N",
		'AJAX_OPTION_STYLE' => "N",
		'AJAX_OPTION_HISTORY' => "N",

		'ALLOW_COLUMNS_SORT' => false,
		'ALLOW_ROWS_SORT' => false,
		'ALLOW_COLUMN_RESIZE' => false,
		'ALLOW_HORIZONTAL_SCROLL' => true,
		'ALLOW_SORT' => false,
		'ALLOW_PIN_HEADER' => false,

		'ACTION_PANEL' => $arResult['GROUP_ACTIONS'],

		'MESSAGES' => $arResult['MESSAGES'] ?: false,
		'FLEXIBLE_LAYOUT' => true,
	], $component, ['HIDE_ICONS' => 'Y']);

	?>
</div>
<script type="text/template" id="tm-settings-popup-menu">
	<div class="tm-settings-popup-wrapper">
		<form>
			<div class="timeman-entity-config-block" data-role="tm-settings">
				<label class="period-setting-label"><?php echo htmlspecialcharsbx(Loc::getMessage('JS_CORE_TM')); ?></label>
				<select name="UF_TIMEMAN" class="timeman-grid-settings-select"
					<? if (!$arResult['canManageSettings']): ?> disabled="disabled"<? endif; ?>>
					<option value=""><?php echo htmlspecialcharsbx(Loc::getMessage('JS_CORE_TMR_INHERIT')); ?></option>
					<option value="Y"><?php echo htmlspecialcharsbx(Loc::getMessage('JS_CORE_TMR_ON')); ?></option>
					<option value="N"><?php echo htmlspecialcharsbx(Loc::getMessage('JS_CORE_TMR_OFF')); ?></option>
				</select>
			</div>
			<div class="timeman-entity-config-block" data-role="tm-settings">
				<label class="period-setting-label"><?php echo htmlspecialcharsbx(Loc::getMessage('TM_WORKTIME_GRID_CONFIG_HINT_REPORT_REQ')); ?></label>
				<select name="UF_TM_REPORT_REQ" class="timeman-grid-settings-select"
					<? if (!$arResult['canManageSettings']): ?> disabled="disabled"<? endif; ?>>
					<option value=""><?php echo htmlspecialcharsbx(Loc::getMessage('JS_CORE_TMR_INHERIT')); ?></option>
					<option value="Y"><?php echo htmlspecialcharsbx(Loc::getMessage('TM_WORKTIME_GRID_CONFIG_HINT_REPORT_REQ_Y')); ?></option>
					<option value="N"><?php echo htmlspecialcharsbx(Loc::getMessage('TM_WORKTIME_GRID_CONFIG_HINT_REPORT_REQ_N')); ?></option>
					<option value="A"><?php echo htmlspecialcharsbx(Loc::getMessage('TM_WORKTIME_GRID_CONFIG_HINT_REPORT_REQ_A')); ?></option>
				</select>
			</div>
			<div class="tm-settings-popup-violations-wrapper" data-role="schedule-personal-violations">
				<label class="period-setting-label"><?= htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_CONTROL_RECORD_TITLE')); ?></label>
				<span class="tm-settings-popup-violations-schedules-link"
						data-role="schedules-list-toggle"><?= htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_CONTROL_SCHEDULE_TITLE')); ?></span>
			</div>
		</form>
	</div>
</script>

<script>
	BX.ready(function ()
	{
		BX.message({
			TIMEMAN_GRID_MENU_SCHEDULE_PERSONAL_VIOLATIONS_PREFIX: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_GRID_MENU_SCHEDULE_PERSONAL_VIOLATIONS_PREFIX')) ?>'
		});
		BX.Timeman.Component.Worktime.Grid<?= CUtil::JSEscape($arResult['GRID_ID'])?> = new BX.Timeman.Component.Worktime.Grid({
			flexibleScheduleTypeName: <?= CUtil::PhpToJSObject(\Bitrix\Timeman\Model\Schedule\Schedule::getFlextimeScheduleTypeName())?>,
			gridId: <?= CUtil::PhpToJSObject($arResult['GRID_ID'])?>,
			<? if($arResult['showFilter']) :?>
			filterId: <?= CUtil::PhpToJSObject($arResult['FILTER']['ID'])?>,
			<? endif;?>
			todayWord: <?= CUtil::PhpToJSObject(Loc::getMessage('TM_WORKTIME_GRID_TODAY'))?>,
			isSlider: <?= CUtil::PhpToJSObject($arResult['isSlider']);?>,
			canReadSchedules: <?= CUtil::PhpToJSObject($arResult['canReadSchedules']);?>,
			usersIds: <?= CUtil::PhpToJSObject($arResult['usersIds']);?>,
			departmentsIds: <?= CUtil::PhpToJSObject($arResult['departmentsIds']);?>,
			canManageSettings: <?= CUtil::PhpToJSObject($arResult['canManageSettings']);?>,
			departmentsData: <?= CUtil::PhpToJSObject($arResult['DEPARTMENTS']);?>
		});
	});
</script>