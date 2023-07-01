<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Extension::load(['ui.buttons', 'ui.dialogs.messagebox', 'ui.buttons.icons']);
CJSCore::Init(['date']);
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/js/timeman/component/basecomponent.js');
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/components/bitrix/timeman.schedule.shiftplan/templates/.default/js/table.js');
$APPLICATION->setTitle(htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_PLAN_TITLE', ['#SCHEDULE_NAME#' => $arResult['SCHEDULE_NAME']])));

$APPLICATION->includeComponent('bitrix:timeman.worktime.grid', '', [
	'IS_SLIDER' => $arResult['isSlider'],
	'GRID_ID' => $arResult['GRID_ID'],
	'SCHEDULE_ID' => $arResult['SCHEDULE_ID'],
	'SHOW_ADD_SHIFT_BTN' => $arResult['SHOW_ADD_SHIFT_BTN'],
	'ADD_SHIFT_LINK' => $arResult['ADD_SHIFT_LINK'] ?? null,
	'TODAY_POSITIONED_LEFT' => true,
	'SHOW_PRINT_BTN' => false,
	'IS_SHIFTPLAN' => true,
	'SHOW_DELETE_USER_BTN' => $arResult['SHOW_DELETE_USER_BTN'],
	'GRID_OPTIONS' => [
		'FILTER_FIELDS_SHOW_ALL' => false,
		'FILTER_FIELDS_REPORT_APPROVED' => false,
		'FILTER_FIELDS_SCHEDULES' => false,
		'FILTER_FIELDS_SHIFTS_EXISTENCE' => true,
		'ENABLE_STATS_COLUMNS' => false,
	],
	'SHIFT_PLAN_FORM_NAME' => (new \Bitrix\Timeman\Form\Schedule\ShiftPlanForm())->getFormName(),
], $component);
?>

<script>
	BX.ready(function ()
	{
		BX.message({
			TM_SHIFT_PLAN_MENU_ADD_SHIFT_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TM_SHIFT_PLAN_MENU_ADD_SHIFT_TITLE'))?>',
			TM_SHIFT_PLAN_MENU_DELETE_SHIFT_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TM_SHIFT_PLAN_MENU_DELETE_SHIFT_TITLE'))?>',
			TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_NO: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_NO'))?>',
			TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_YES: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_YES'))?>',
			TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM'))?>',
			TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_TITLE'))?>'
		});
		new BX.Timeman.Component.Schedule.ShiftPlan({
			scheduleId: <?= CUtil::PhpToJSObject($arResult['SCHEDULE_ID'])?>,
			gridId: <?= CUtil::PhpToJSObject($arResult['GRID_ID'])?>,
			isSlider: <?= CUtil::PhpToJSObject($arResult['isSlider']);?>,
			errorCodeOverlappingPlans: <?= CUtil::PhpToJSObject($arResult['errorCodeOverlappingPlans']);?>
		});
	});
</script>