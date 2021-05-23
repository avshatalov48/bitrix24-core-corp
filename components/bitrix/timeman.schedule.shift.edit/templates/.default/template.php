<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/tools/clock.php");

\Bitrix\Main\UI\Extension::load(["ui.buttons", "ui.alerts"]);
CJSCore::Init(['popup', 'ui']);
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/js/timeman/component/basecomponent.js');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/timeman.schedule.edit/templates/.default/style.css');
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/components/bitrix/timeman.schedule.shift.edit/templates/.default/shift.js');
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/components/bitrix/timeman.interface.popup.timepicker/templates/.default/duration-picker.js');

$APPLICATION->SetTitle(htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SHIFT_EDIT_PAGE_TITLE')));
/** @var \Bitrix\Timeman\Form\Schedule\ShiftForm $shiftForm */
$shiftForm = $arResult['shiftForm'];
$shiftFormName = htmlspecialcharsbx($shiftForm->getFormName());
?>

<div class="timeman-schedule-form-wrap timeman-schedule-form-wrap-shift <? if ($arResult['isSlider']): ?>timeman-schedule-form-slider<? endif; ?>"
		data-role="timeman-shift-edit-container">
	<form action="" data-role="timeman-shift-edit-form">

		<input type="hidden" name="<?php echo $shiftFormName; ?>[shiftId]" value="<?= htmlspecialcharsbx($arResult['SHIFT_ID']) ?>">
		<input type="hidden" name="<?php echo $shiftFormName; ?>[scheduleId]" value="<?= htmlspecialcharsbx($arResult['SCHEDULE_ID']) ?>">

		<div data-role="timeman-shift-edit-error-block">
		</div>
		<div class="ui-alert ui-alert-danger main-ui-hide"
				data-role="timeman-shift-edit-error-msg">
		</div>

		<div class="timeman-schedule-form-settings-inner">
			<div class="timeman-schedule-form-settings-name-block">
				<span class="timeman-schedule-form-settings-name"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SHIFT_EDIT_NAME_TITLE')); ?></span>
			</div>
			<input class="timeman-schedule-form-settings-input"
					name="<?= $shiftFormName; ?>[name]"
					placeholder="<?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SHIFT_EDIT_DEFAULT_SHIFT_NAME')); ?>"
					value="<?= htmlspecialcharsbx($shiftForm->name); ?>">
		</div>
		<div class="timeman-schedule-form-worktime-inner">
			<div class="timeman-schedule-form-worktime-item">
				<div class="timeman-schedule-form-worktime-title"><?=
					htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SHIFT_EDIT_SHIFT_WORK_TIME_TITLE')); ?></div>
				<div class="timeman-schedule-form-worktime-value">
					<span class="timeman-schedule-form-worktime-value-text"
							data-role="timeman-shift-work-time-toggle">
						<span class="timeman-schedule-form-worktime-input-value-text"
								data-role="timeman-shift-link-start-time"><?=
							htmlspecialcharsbx($shiftForm->getFormattedStartTime())
							?></span>
						<input name="<?= $shiftFormName . "[startTimeFormatted]" ?>"
								autocomplete="off"
								data-role="start-seconds-input"
								type="hidden"
								value="<?= htmlspecialcharsbx($shiftForm->getFormattedStartTime()) ?>">
						-
						<span class="timeman-schedule-form-worktime-input-value-text"
								data-role="timeman-shift-link-end-time"><?=
							htmlspecialcharsbx($shiftForm->getFormattedEndTime())
							?></span>
						<input name="<?= $shiftFormName . "[endTimeFormatted]" ?>"
								data-role="end-seconds-input"
								autocomplete="off"
								type="hidden"
								value="<?= htmlspecialcharsbx($shiftForm->getFormattedEndTime()) ?>">
					</span>
				</div>
			</div>
			<div class="timeman-schedule-form-worktime-item">
				<div class="timeman-schedule-form-worktime-title"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SHIFT_EDIT_BREAK_DURATION_TITLE')); ?></div>
				<div class="timeman-schedule-form-worktime-value">
					<span class="timeman-schedule-form-worktime-value-text"
							data-role="timeman-shift-break-toggle"><?php echo
						htmlspecialcharsbx($shiftForm->getFormattedBreakDuration()); ?></span>
					<input name="<?= $shiftFormName; ?>[breakDurationFormatted]"
							type="hidden"
							data-role="timeman-shift-break-time"
							value="<?= htmlspecialcharsbx($shiftForm->getFormattedBreakDuration()); ?>">
					<div class="timeman-schedule-form-worktime-duration">
						<?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SHIFT_EDIT_BREAK_DURATION_TOTAL_TITLE')); ?>
						<span data-role="duration-without-break"></span>
					</div>
				</div>
			</div>
		</div>
		<div class="timeman-schedule-form-buttons">
			<div class="timeman-schedule-form-buttons-inner">
				<button class="ui-btn ui-btn-md ui-btn-success"
						data-role="timeman-shift-btn-save"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SHIFT_EDIT_BTN_SAVE_TITLE')); ?></button>
				<button class="ui-btn ui-btn-md ui-btn-link"
						data-role="timeman-shift-btn-cancel"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SHIFT_EDIT_BTN_CANCEL_TITLE')); ?></button>
			</div>
		</div>
	</form>


	<?
	global $APPLICATION;
	ob_start();

	$APPLICATION->IncludeComponent(
		"bitrix:timeman.interface.popup.timepicker",
		".default",
		[
			'TIME_PICKER_CONTENT_ATTRIBUTE_DATA_ROLE' => 'timeman-shift-work-time-content',
			'START_INPUT_NAME' => 'startTimeHidden',
			'START_INPUT_ID' => 'shiftStartTimeClock',
			'START_INIT_TIME' => $shiftForm->getFormattedStartTime(),
			'END_INPUT_NAME' => 'endTimeHidden',
			'END_INPUT_ID' => 'shiftEndTimeClock',
			'END_INIT_TIME' => $shiftForm->getFormattedEndTime(),
		]
	);
	echo ob_get_clean();
	?>

	<script>
		BX.ready(function ()
		{
			BX.message({
				AMPM_MODE: <?= CUtil::PhpToJSObject((bool)\IsAmPmMode(true)); ?>,
				TIMEMAN_SHIFT_EDIT_POPUP_PICK_TIME_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_POPUP_PICK_TIME_TITLE')) ?>',
				TIMEMAN_SHIFT_EDIT_POPUP_WORK_TIME_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_POPUP_WORK_TIME_TITLE')) ?>',
				TIMEMAN_SHIFT_EDIT_BTN_SET_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_BTN_SET_TITLE')) ?>',
				TIMEMAN_SHIFT_EDIT_BTN_CANCEL_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_BTN_CANCEL_TITLE')) ?>',
				TIMEMAN_SHIFT_EDIT_BTN_SAVE_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_BTN_SAVE_TITLE')) ?>',
				TIMEMAN_SHIFT_EDIT_POPUP_FORMAT_HOUR: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_POPUP_FORMAT_HOUR')) ?>',
				TIMEMAN_SHIFT_EDIT_POPUP_FORMAT_MINUTE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_POPUP_FORMAT_MINUTE')) ?>'
			});
			new BX.Timeman.Component.Schedule.ShiftEdit({
				containerSelector: '[data-role="timeman-shift-edit-container"]',
				isSlider: "<?= CUtil::JSEscape($arResult['isSlider'])?>"
			});
		});
	</script>
</div>