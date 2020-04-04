<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$startTimeInputName = $arResult['WORKTIME_RECORD_FORM_NAME'] . '[recordedStartTime]';
$endTimeInputName = $arResult['WORKTIME_RECORD_FORM_NAME'] . '[recordedStopTime]';
$breakLengthInputName = $arResult['WORKTIME_RECORD_FORM_NAME'] . '[recordedBreakLengthTime]';

$arResult['FIELD_CELLS']['START']['DATA_ROLE'] = 'start-time';
$arResult['FIELD_CELLS']['BREAK']['DATA_ROLE'] = 'break-time';
$arResult['FIELD_CELLS']['END']['DATA_ROLE'] = 'end-time';
$arResult['FIELD_CELLS']['DURATION']['DATA_ROLE'] = 'duration-time';

$APPLICATION->IncludeComponent(
	"bitrix:timeman.interface.popup.timepicker",
	".default",
	[
		'TIME_PICKER_CONTENT_ATTRIBUTE_DATA_ROLE' => 'timeman-time-picker-content',
		'START_INPUT_NAME' => 'startTime',
		'START_INPUT_ID' => 'startTimeClock',
		'END_INPUT_NAME' => 'endTime',
		'END_INPUT_ID' => 'endTimeClock',
		'START_DATE_INPUT_SELECTOR_ROLE' => 'start-date',
		'END_DATE_INPUT_SELECTOR_ROLE' => 'end-date',
		'END_INIT_TIME' => $arResult['FIELD_CELLS']['END']['TIME_PICKER_INIT_TIME'],
		'SHOW_EDIT_REASON' => false,
		'SHOW_EDIT_BREAK_LENGTH' => true,
		'SHOW_START_DATE_PICKER' => true,
		'SHOW_END_DATE_PICKER' => true,
		'START_DATE_DEFAULT_VALUE' => $arResult['startTimestamp'],
		'END_DATE_DEFAULT_VALUE' => $arResult['endTimestamp'],
		'EDIT_BREAK_LENGTH_ATTRIBUTE_NAME' => 'breakLength',
		'EDIT_REASON_ATTRIBUTE_NAME' => $arResult['WORKTIME_EVENT_FORM_NAME'] . '[reason]',
		'START_INIT_TIME' => $arResult['FIELD_CELLS']['START']['RECORDED_VALUE'],
		'BREAK_LENGTH_VALUE' => $arResult['FIELD_CELLS']['BREAK']['RECORDED_VALUE'],
	]
);
?>
<input type="hidden" name="scheduleId" value="<?= htmlspecialcharsbx($arResult['record']['SCHEDULE_ID']) ?>">
<input type="hidden" name="shiftId" value="<?= htmlspecialcharsbx($arResult['record']['SHIFT_ID']) ?>">
<div class="timeman-report-decs">
	<form data-role="worktime-record-form">
		<input type="hidden" name="<?php echo $arResult['WORKTIME_RECORD_FORM_NAME']; ?>[id]" value="<?= htmlspecialcharsbx($arResult['RECORD_ID']) ?>">
		<input type="hidden" name="<?php echo $arResult['WORKTIME_RECORD_FORM_NAME']; ?>[recordedStartDateFormatted]" data-role="start-date">
		<input type="hidden" name="<?php echo $arResult['WORKTIME_RECORD_FORM_NAME']; ?>[recordedStopDateFormatted]" data-role="end-date">
		<input type="hidden" name="<?= htmlspecialcharsbx($startTimeInputName); ?>"
				value="<?= htmlspecialcharsbx($arResult['RECORDED_START_TIME']) ?>">
		<input type="hidden" name="<?= htmlspecialcharsbx($endTimeInputName); ?>"
				value="<?= htmlspecialcharsbx($arResult['RECORDED_STOP_TIME']) ?>">
		<input type="hidden" name="<?= htmlspecialcharsbx($breakLengthInputName); ?>"
				value="<?= htmlspecialcharsbx($arResult['BREAK_LENGTH_RECORDED_TIME']) ?>">

		<div class="timeman-report-time-list">
			<? foreach ($arResult['FIELD_CELLS'] as $index => $fieldCell) : ?>
				<? $cssClasses = 'timeman-report-time-item-data'; ?>
				<? $changedTime = false; ?>
				<? if (!$arResult['IS_RECORD_APPROVED'] && !empty($fieldCell['WARNINGS'])): ?>
					<? $cssClasses = 'timeman-report-time-item-unapproved'; ?>
				<? else: ?>
					<? if (!empty($fieldCell['VIOLATIONS'])): ?>
						<? foreach ($fieldCell['VIOLATIONS'] as $violation) : ?>
							<? /** @var \Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolation $violation */
							if ($violation->isManuallyChangedTime()): ?>
								<? $cssClasses = 'timeman-report-time-item-changed'; ?>
							<? else: ?>
								<? $cssClasses = 'timeman-report-time-item-unapproved'; ?>
								<? break; ?>
							<? endif; ?>
						<? endforeach; ?>
					<? endif; ?>
				<? endif; ?>
				<div class="timeman-report-time-item <?= htmlspecialcharsbx($cssClasses); ?> <?= $fieldCell['HIDE'] ? 'timeman-hide' : ''; ?>"
						data-role="<?= htmlspecialcharsbx($fieldCell['DATA_ROLE']) ?>-container">
					<div class="timeman-report-time-item-title">
						<span class="timeman-report-time-item-title-text"><?= htmlspecialcharsbx($fieldCell['TITLE']) ?></span>
					</div>
					<div class="timeman-report-time-item-inner">
						<div class="timeman-report-time-item-data">
								<span class="timeman-report-time-item-value" data-role="<?= htmlspecialcharsbx($fieldCell['DATA_ROLE']) ?>">
									<?= htmlspecialcharsbx($fieldCell['RECORDED_VALUE']) ?>
								</span>
							<? if (isset($fieldCell['DATE'])): ?>
								<span class="timeman-report-time-item-value-real">
									<?= '(' . htmlspecialcharsbx($fieldCell['DATE']) . ')'; ?>
								</span>
							<? endif; ?>
							<div class="<?= $fieldCell['CHANGED_TIME'] ? '' : 'timeman-hide'; ?>">
								<span class="timeman-report-time-item-value-real">
									<?= '(' . htmlspecialcharsbx(Loc::getMessage('JS_CORE_TMR_REPORT_ORIG') . ' ' . $fieldCell['ACTUAL_VALUE']) . ')'; ?>
								</span>
								<div class="<?= empty($fieldCell['ACTUAL_INFO']) ? 'timeman-hide' : ''; ?>">
									<div class="timeman-report-time-item-edited
									<? echo $arResult['IS_RECORD_APPROVED'] ? 'timeman-report-time-item-edited-confirmed' : 'timeman-report-time-item-edited-warning'; ?>">
										<?= htmlspecialcharsbx(Loc::getMessage('JS_CORE_TMR_A') . ' ' . $fieldCell['ACTUAL_INFO']['EDITED_USER_TIME']); ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			<? endforeach; ?>
		</div>
	</form>
</div>
<? foreach ($arResult['FIELD_CELLS'] as $item) : ?>
	<? if ($item['ACTUAL_INFO']): ?>
		<div class="timeman-report-decs">
			<div class="timeman-report-title timeman-report-title-edited">
				<div class="timeman-report-title-text"><?=
					htmlspecialcharsbx($item['ACTUAL_INFO']['TITLE'])
					?></div>
			</div>
			<div class="timeman-report-decs-inner">
				<?= htmlspecialcharsbx($item['ACTUAL_INFO']['EDITED_REASON']); ?>
			</div>
		</div>
	<? endif; ?>
<? endforeach; ?>
