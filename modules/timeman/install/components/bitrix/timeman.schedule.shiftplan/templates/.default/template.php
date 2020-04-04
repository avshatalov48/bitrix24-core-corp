<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Timeman\Helper\DateTimeHelper;

$dateHelper = new DateTimeHelper();
Extension::load("ui.buttons");
Extension::load("ui.buttons.icons");
CJSCore::Init(['date']);

\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/js/timeman/component/basecomponent.js');
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/components/bitrix/timeman.schedule.shiftplan/templates/.default/js/table.js');
$APPLICATION->setTitle(htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_PLAN_TITLE', ['#SCHEDULE_NAME#' => $arResult['SCHEDULE_NAME']])));
?>


<div class="timeman-report-container timeman-report-container-plan" data-role="shift-plans-container">
	<?
	$APPLICATION->includeComponent('bitrix:timeman.worktime.grid', '', [
		'IS_SLIDER' => $arResult['isSlider'],
		'GRID_ID' => $arResult['GRID_ID'],
		'SCHEDULE_ID' => $arResult['SCHEDULE_ID'],
		'SHOW_CREATE_SHIFT_BTN' => $arResult['SHOW_CREATE_SHIFT_BTN'],
		'SHOW_EDIT_SCHEDULE_BTN' => $arResult['SHOW_EDIT_SCHEDULE_BTN'],
		'SHOW_ADD_SHIFT_PLAN_BTN' => $arResult['SHOW_ADD_SHIFT_PLAN_BTN'],
		'SHOW_PRINT_BTN' => true,
		'SHOW_DELETE_USER_BTN' => $arResult['SHOW_DELETE_USER_BTN'],
		'SHOW_GRID_SETTINGS_BTN' => false,
		'GRID_OPTIONS' => [
			'FILTER_FIELDS_SHOW_ALL' => false,
			'FILTER_FIELDS_REPORT_APPROVED' => false,
			'ENABLE_STATS_COLUMNS' => false,
		],
		'SHOW_DELETE_SHIFT_PLAN_BTN' => $arResult['SHOW_DELETE_SHIFT_PLAN_BTN'],
		'SHIFT_PLAN_FORM_NAME' => (new \Bitrix\Timeman\Form\Schedule\ShiftPlanForm())->getFormName(),
		'SHOW_START_FINISH' => true,
	], $component);


	?>
	<? if ($arResult['SHOW_ADD_USER_BUTTON']): ?>
		<div class="timeman-grid-user timeman-grid-user-add" data-role="add-user-btn">
			<div class="timeman-grid-add-btn"></div>
			<span class="timeman-grid-add-btn-text"><?= htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_PLAN_USER_ADD')) ?></span>
		</div>
	<? endif; ?>
	<?


	?>
	<div class="main-ui-hide" data-role="user-selector">
		<?
		$APPLICATION->IncludeComponent(
			"bitrix:main.user.selector",
			"",
			[
				'ID' => 'tm-shiftplan-users-id',
				'INPUT_NAME' => 'tm-shiftplan-users',
			]
		);
		?>
	</div>
</div>

<script>
	BX.ready(function ()
	{
		BX.message({
			TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_NO: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_NO'))?>',
			TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_YES: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_YES'))?>',
			TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM'))?>',
			TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_TITLE'))?>'
		});
		new BX.Timeman.Component.Schedule.ShiftPlan({
			scheduleId: <?= CUtil::PhpToJSObject($arResult['SCHEDULE_ID'])?>,
			gridId: <?= CUtil::PhpToJSObject($arResult['GRID_ID'])?>,
			isSlider: <?= CUtil::PhpToJSObject($arResult['isSlider']);?>
		});
	});
</script>