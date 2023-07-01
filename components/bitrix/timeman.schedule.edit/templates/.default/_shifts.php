<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

?>
<div class="timeman-schedule-form-block" data-role="work-time-block">
	<div class="timeman-schedule-form-title">
		<div class="timeman-schedule-form-title-inner">
			<span class="timeman-schedule-form-title-text"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_WORK_TIME_TITLE')); ?></span>
		</div>
	</div><?
	global $APPLICATION;
	ob_start();
	$APPLICATION->IncludeComponent(
		"bitrix:timeman.interface.popup.timepicker",
		".default",
		[
			'TIME_PICKER_CONTENT_ATTRIBUTE_DATA_ROLE' => 'timeman-shift-work-time-content',
			'START_INPUT_NAME' => 'startTimeHidden',
			'START_INPUT_ID' => 'shiftStartTimeClock',
			'END_INPUT_NAME' => 'endTimeHidden',
			'END_INPUT_ID' => 'shiftEndTimeClock',
		]
	);
	echo ob_get_clean();

	?>
	<div class="timeman-schedule-form-worktime"
			data-role="timeman-shifts-wrapper">
		<? $shiftTemplate = new \Bitrix\Timeman\Form\Schedule\ShiftForm(); ?>
		<? foreach (array_merge([$shiftTemplate], $scheduleForm->getShiftForms()) as $shiftIndex => $shiftForm) : ?>
			<? $shiftFormName = $shiftForm->getFormName() . ($shiftIndex === 0 ? 'Template' : ''); ?>
			<? $shiftFormName = htmlspecialcharsbx($scheduleForm->getFormName() . '[' . $shiftFormName . ']'); ?>
			<div class="timeman-schedule-form-worktime-inner  <? echo($shiftIndex === 0 ? 'timeman-hide' : ''); ?>"
					data-role="timeman-schedule-shift-form-container-<?= $shiftIndex; ?>"
			>
				<input type="hidden" name="<?= $shiftFormName . "[$shiftIndex][shiftId]" ?>" value="<?= htmlspecialcharsbx($shiftForm->shiftId) ?>"
						data-role="shift-id">

				<div class="timeman-schedule-form-worktime-item"
						data-role="timeman-schedule-shift-workdays-block"
				>
					<div class="timeman-schedule-form-worktime-title"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_WORK_DAYS_TITLE')); ?></div>
					<div class="timeman-schedule-form-worktime-value">
						<?
						$workdaysText = end($arResult['shiftWorkdaysOptions']);
						if (isset($arResult['shiftWorkdaysOptions'][$shiftForm->workDays]))
						{
							$workdaysText = $arResult['shiftWorkdaysOptions'][$shiftForm->workDays];
						}
						$shiftWorkdaysOption = array_keys($arResult['shiftWorkdaysOptions']);
						$workdaysValue = $shiftForm->workDays ?: end($shiftWorkdaysOption);
						?>
						<div class="">
							<input type="hidden"
									data-role="timeman-schedule-shift-workdays-input"
									name="<?= $shiftFormName . "[$shiftIndex][workDays]" ?>"
									value="<?= htmlspecialcharsbx($workdaysValue) ?>"
							>
							<span class="timeman-schedule-form-worktime-value-text timeman-schedule-form-workdays"
									data-role="timeman-schedule-workdays-toggle"><?=
								htmlspecialcharsbx($workdaysText)
								?></span>
						</div>

					</div>
				</div>

				<div class="timeman-schedule-form-worktime-subject"
						data-role="timeman-schedule-shift-name-input-block"
				>
					<? $shiftName = $shiftForm->name === null ? Loc::getMessage('TIMEMAN_SHIFT_EDIT_DEFAULT_SHIFT_NAME') : $shiftForm->name; ?>
					<input class="timeman-hide timeman-schedule-form-worktime-subject-text timeman-schedule-form-worktime-subject-text-input"
							data-role="timeman-schedule-shift-name-input"
							title="shift-name"
							name="<?= $shiftFormName . "[$shiftIndex][name]" ?>"
							value="<?= htmlspecialcharsbx($shiftName); ?>">
					<span class="timeman-schedule-form-worktime-subject-text"
							data-role="timeman-schedule-shift-name-span"><?= htmlspecialcharsbx($shiftName);
						?></span>
					<span class="timeman-schedule-form-worktime-subject-edit-icon"
							data-role="timeman-schedule-shift-pencil-name"></span>
				</div>

				<div class="timeman-schedule-form-worktime-item">
					<div class="timeman-schedule-form-worktime-title">
						<?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_WORK_TIME_TITLE')); ?>
					</div>
					<div class="timeman-schedule-form-worktime-value">
						<span class="timeman-schedule-form-worktime-value-text"
								data-role="timeman-shift-work-time-toggle">
							<span class="timeman-schedule-form-worktime-input-value-text"
									data-role="timeman-shift-link-start-time"><?=
								htmlspecialcharsbx($shiftForm->getFormattedStartTime())
								?></span>
							<input name="<?= $shiftFormName . "[$shiftIndex][startTimeFormatted]" ?>"
									autocomplete="off"
									data-role="start-seconds-input"
									type="hidden"
									value="<?= htmlspecialcharsbx($shiftForm->getFormattedStartTime()) ?>">
							-
							<span class="timeman-schedule-form-worktime-input-value-text"
									data-role="timeman-shift-link-end-time"><?=
								htmlspecialcharsbx($shiftForm->getFormattedEndTime())
								?></span>
							<input name="<?= $shiftFormName . "[$shiftIndex][endTimeFormatted]" ?>"
									data-role="end-seconds-input"
									autocomplete="off"
									type="hidden"
									value="<?= htmlspecialcharsbx($shiftForm->getFormattedEndTime()) ?>">
						</span>
					</div>
				</div>

				<div class="timeman-schedule-form-worktime-item">
					<div class="timeman-schedule-form-worktime-title"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_BREAK_DURATION_TITLE')); ?></div>
					<div class="timeman-schedule-form-worktime-value">
						<span
							class="timeman-schedule-form-worktime-value-text"
							data-role="timeman-shift-break-toggle"
						><?= htmlspecialcharsbx($shiftForm->getFormattedBreakDuration()) ?>
						</span>
						<input
							name="<?= $shiftFormName . "[$shiftIndex][breakDurationFormatted]" ?>"
							data-role="timeman-shift-break-time"
							type="hidden"
							class="timeman-schedule-form-worktime-input-value-text"
							value="<?= htmlspecialcharsbx($shiftForm->getFormattedBreakDuration()) ?>"
						>
					</div>
				</div>
				<div class="timeman-schedule-form-worktime-item">
					<div class="timeman-schedule-form-worktime-title"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SHIFT_EDIT_SHIFT_DURATION_TITLE')); ?></div>
					<div class="timeman-schedule-form-worktime-value">
						<div class="timeman-schedule-form-worktime-duration">
							<span data-role="duration-without-break"></span>
						</div>
					</div>
				</div>
				<div class="timeman-schedule-form-worktime-delete"
						data-role="timeman-schedule-form-worktime-delete-btn">
					<span class="timeman-schedule-form-worktime-delete-icon"></span>
				</div>
				<?
				$isFixedSchedule = $scheduleForm->isFixed() === null ? true : $scheduleForm->isFixed();
				$hideDaysSelector = $workdaysText !== $arResult['customWorkdaysText'] || !$isFixedSchedule; ?>
				<div class="timeman-schedule-form-worktime-days <? if ($hideDaysSelector): ?>timeman-hide<? endif; ?>"
						data-role="timeman-schedule-shift-workdays-selector">
					<span class="timeman-schedule-form-worktime-days-inner">

						<? $shiftFormWorkDays = array_map('intval', str_split($shiftForm->workDays ?? '')); ?>
						<? foreach ($arResult['weekDays'] as $dayNumber => $text) : ?>
							<span class="timeman-schedule-form-worktime-day"
									data-role="timeman-schedule-shift-work-days">
								<input class="timeman-schedule-form-worktime-day-check" type="checkbox"
										data-role="timeman-schedule-shift-work-day-item"
										id="<?= htmlspecialcharsbx('day-' . $shiftIndex . '-' . $dayNumber) ?>"
									<? if (in_array($dayNumber, $shiftFormWorkDays, true)): ?>checked<? endif; ?>
										value="<?= htmlspecialcharsbx($dayNumber); ?>"
								>
								<label class="timeman-schedule-form-worktime-day-label"
										for="<?= htmlspecialcharsbx('day-' . $shiftIndex . '-' . $dayNumber) ?>"><?=
									htmlspecialcharsbx($text);
									?></label>
							</span>
						<? endforeach; ?>
				</div>
			</div>
		<? endforeach; ?>

	</div>
	<div class="timeman-schedule-form-worktime-add">
		<span class="timeman-schedule-form-worktime-add-plus">+</span>
		<span class="timeman-schedule-form-worktime-add-link"
				data-role="timeman-schedule-form-shift-add"
		><?= htmlspecialcharsbx(
				$scheduleForm->isShifted() ? Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_ADD_WORK_SHIFT_TITLE') : Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_ADD_WORK_TIME_TITLE')
			); ?></span>
	</div>
</div>