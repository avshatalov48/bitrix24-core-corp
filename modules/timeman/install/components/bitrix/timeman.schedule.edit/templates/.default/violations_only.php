<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;

\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/js/timeman/component/basecomponent.js');
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/components/bitrix/timeman.interface.popup.timepicker/templates/.default/duration-picker.js');
\Bitrix\Main\UI\Extension::load(["ui.buttons", "ui.alerts"]);
global $APPLICATION;

/** @var \Bitrix\Timeman\Form\Schedule\ScheduleForm $scheduleForm */
$scheduleForm = $arResult['scheduleForm'];
if ($arResult['ENTITY_TYPE_USER'])
{
	$APPLICATION->setTitle(htmlspecialcharsbx(
			Loc::getMessage('TM_SCHEDULE_VIOLATIONS_USER_TITLE_PERSONAL', [
				'#SCHEDULE_NAME#' => $scheduleForm->getSchedule()->getName(),
				'#NAME#' => $arResult['ENTITY_NAME'],
			]))
	);
}
else
{
	$APPLICATION->setTitle(htmlspecialcharsbx(
			Loc::getMessage('TM_SCHEDULE_VIOLATIONS_DEPARTMENT_TITLE_PERSONAL', [
				'#SCHEDULE_NAME#' => $scheduleForm->getSchedule()->getName(),
				'#NAME#' => $arResult['ENTITY_NAME'],
			]))
	);
}

/** @var \Bitrix\Timeman\Form\Schedule\ViolationForm $violationForm */
$violationForm = $arResult['violationForm'];
$violationFormName = $violationForm->getFormName();
$violationFormName = htmlspecialcharsbx($violationFormName);
?>
<div class="timeman-schedule-form-wrap-open" data-role="violations-container">
	<div data-role="timeman-violations-edit-error-block"></div>
	<div class="ui-alert ui-alert-danger timeman-hide" data-role="timeman-violations-error-msg-block"></div>
	<form action="" method="post" data-role="timeman-violations-personal-form">
		<input type="hidden"
				name="<?= $violationFormName . "[scheduleId]"; ?>"
				value="<?php echo $violationForm->scheduleId > 0 ? (int)$violationForm->scheduleId : ''; ?>"
		>
		<input type="hidden"
				name="<?= $violationFormName . "[entityCode]"; ?>"
				value="<?php echo htmlspecialcharsbx($arResult['ENTITY_CODE']); ?>"
		>
		<input type="hidden"
				name="<?= $violationFormName . "[id]"; ?>"
				value="<?php echo $violationForm->id > 0 ? (int)$violationForm->id : ''; ?>"
		>
		<?


		require_once '_violations_inner.php';


		?>
	</form>
</div>
<?php
$buttons = [];
if ($arResult['canUpdatePersonalViolations'])
{
	$buttons[] = [
		'TYPE' => 'save',
		'ID' => 'tm-schedule-personal-violations-save',
	];
}
$buttons[] = [
	'TYPE' => 'close',
	'LINK' => '',
];
$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
	'BUTTONS' => $buttons,
]);
?>
<script type="text/javascript">
	BX.ready(function ()
	{
		BX.message({
			AMPM_MODE: <?= CUtil::PhpToJSObject((bool)\IsAmPmMode(true)); ?>,
			TIMEMAN_SHIFT_EDIT_POPUP_PICK_TIME_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_POPUP_PICK_TIME_TITLE'))?>',
			TIMEMAN_SHIFT_EDIT_POPUP_WORK_TIME_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_POPUP_WORK_TIME_TITLE'))?>',
			TIMEMAN_SHIFT_EDIT_BTN_SET_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_BTN_SET_TITLE'))?>',
			TIMEMAN_SHIFT_EDIT_BTN_CANCEL_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_BTN_CANCEL_TITLE'))?>',
			TIMEMAN_SHIFT_EDIT_BTN_SAVE_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_EDIT_BTN_SAVE_TITLE')) ?>',
			TM_SCHEDULE_SAVE_CONFIRM_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_SAVE_CONFIRM_TITLE')) ?>',
			TM_SCHEDULE_SAVE_CONFIRM: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_SAVE_CONFIRM')) ?>',
			TM_SCHEDULE_SAVE_CONFIRM_NO: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_SAVE_CONFIRM_NO')) ?>',
			TM_SCHEDULE_SAVE_CONFIRM_YES: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_SAVE_CONFIRM_YES')) ?>',
			TIMEMAN_SHIFT_EDIT_POPUP_FORMAT_HOUR: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_POPUP_FORMAT_HOUR')) ?>',
			TIMEMAN_SHIFT_EDIT_POPUP_FORMAT_MINUTE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_POPUP_FORMAT_MINUTE')) ?>'
		});
		new BX.Timeman.Component.Schedule.Edit.Violations({
			shiftedScheduleTypeName: <?= CUtil::PhpToJSObject(Schedule::getShiftedScheduleTypeName())?>,
			flextimeScheduleTypeName: <?= CUtil::PhpToJSObject(Schedule::getFlextimeScheduleTypeName())?>,
			fixedScheduleTypeName: <?= CUtil::PhpToJSObject(Schedule::getFixedScheduleTypeName())?>,
			scheduleType: <?= CUtil::PhpToJSObject($scheduleForm->getSchedule()->getScheduleType())?>,
			controlType: <?= CUtil::PhpToJSObject($scheduleForm->getSchedule()->getControlledActions())?>,
			controlledStart: <?= CUtil::PhpToJSObject(ScheduleTable::CONTROLLED_ACTION_START);?>,
			controlledStartEnd: <?= CUtil::PhpToJSObject(ScheduleTable::CONTROLLED_ACTION_START_AND_END);?>,
			isSlider: <?= CUtil::PhpToJSObject($arResult['isSlider'])?>
		});
	});
</script>
