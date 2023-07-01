<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/tools/clock.php");
\Bitrix\Main\UI\Extension::load("ui.hint");
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/components/bitrix/timeman.schedule.edit/templates/.default/js/violations.js');
/** @var \Bitrix\Timeman\Form\Schedule\ViolationForm $violationForm */
$violationForm = ($arResult['violationForm'] ?? null) ? $arResult['violationForm'] : $scheduleForm->violationForm;
if ($arResult['violationForm'] ?? null)
{
	$violationFormName = $violationForm->getFormName();
}
else
{
	$violationFormName = $scheduleForm->getFormName() . '[' . $violationForm->getFormName() . ']';
}
$violationFormName = htmlspecialcharsbx($violationFormName);

$showContainer = $violationForm->showViolationContainer($scheduleForm->isShifted());
?>
<div class="timeman-schedule-form-violation-container">
	<div class=" timeman-schedule-form-violation-block"
			data-role="violation-fix-schedule">
		<input class="timeman-schedule-form-checkbox" type="checkbox" id="violation-start-end"
			<?= $violationForm->showStartEndViolations() ? 'checked' : ''; ?>
				name="<?= $violationFormName . "[saveStartEndViolations]"; ?>">
		<div class="timeman-schedule-form-violation-inner timeman-schedule-form-violation-inner-start-end">
			<label class="timeman-schedule-form-violation-label" for="violation-start-end"><?= htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_START_END_BLOCK_TITLE')); ?></label>
			<div class="timeman-schedule-form-violation-hidden">
				<div class="timeman-schedule-form-violation-options">
					<div class="timeman-schedule-form-violation-option
							<?= $violationForm->showExactStartEndDay() ? 'timeman-schedule-form-violation-option-selected' : ''; ?>"
							data-role="exact-time-block-toggle">
						<div class="timeman-schedule-form-violation-value">
							<input class="timeman-schedule-form-violation-hidden-input" id="violation-option-right-time" type="radio"
								<?= $violationForm->showExactStartEndDay() ? 'checked' : ''; ?>
									data-role="exact-time-block-input"
									name="<?= $violationFormName . "[useExactStartEndDay]"; ?>"
							>
							<label class="timeman-schedule-form-violation-hidden-label" for="violation-option-right-time"><?=
								htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_EXACT_TIME_BLOCK_TITLE')); ?></label>
							<span class="timeman-schedule-form-violation-help" data-hint-html data-hint="<?php echo htmlspecialcharsbx($arResult['hintExactStartEndDay']); ?>"></span>
						</div>
						<div class="timeman-schedule-form-violation-detail timeman-schedule-form-violation-detail-start-end">
							<div class="timeman-schedule-form-violation-detail-inner" data-role="start-control">
								<span class="timeman-schedule-form-violation-detail-title"><?=
									htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_MAX_EXACT_START_TITLE'));
									?></span>
								<span class="timeman-schedule-form-violation-detail-text">
									<span class="timeman-schedule-form-worktime-input-value-text"
											data-role="max-exact-start-link"
											data-input-selector-role="max-exact-start-input"
									><?=
										htmlspecialcharsbx($violationForm->getFormattedMaxExactStart()) ?>
									</span>
									<input name="<?= $violationFormName . "[maxExactStartFormatted]" ?>"
											data-role="max-exact-start-input"
											type="hidden"
											value="<?= htmlspecialcharsbx($violationForm->getFormattedMaxExactStart()) ?>">
								</span>
							</div>
							<div class="timeman-schedule-form-violation-detail-inner" data-role="end-control">
								<span class="timeman-schedule-form-violation-detail-title"><?= htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_MIN_END_START_TITLE')); ?></span>
								<span class="timeman-schedule-form-violation-detail-text">
									<span class="timeman-schedule-form-worktime-input-value-text"
											data-role="min-exact-end-link"
											data-input-selector-role="min-exact-end-input"
									><?=
										htmlspecialcharsbx($violationForm->getFormattedMinExactEnd())
										?></span>
									<input name="<?= $violationFormName . "[minExactEndFormatted]" ?>"
											data-role="min-exact-end-input"
											class="timeman-schedule-form-worktime-input-value-text"
											type="hidden"
											value="<?= htmlspecialcharsbx($violationForm->getFormattedMinExactEnd()) ?>">
								</span>
							</div>
						</div>
					</div>
					<div class="timeman-schedule-form-violation-option
							<?= $violationForm->showRelativeStartEndDay() ? 'timeman-schedule-form-violation-option-selected' : ''; ?>"
							data-role="relative-time-block-toggle">
						<div class="timeman-schedule-form-violation-value">
							<input class="timeman-schedule-form-violation-hidden-input" id="timeline-relative-time" type="radio"
								<?= $violationForm->showRelativeStartEndDay() ? 'checked' : ''; ?>
									name="<?= $violationFormName . "[useRelativeStartEndDay]"; ?>"
									data-role="relative-time-block-input"
							>
							<label class="timeman-schedule-form-violation-hidden-label" for="timeline-relative-time"><?=
								htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_RELATIVE_TIME_BLOCK_TITLE'))
								?></label>
							<span class="timeman-schedule-form-violation-help" data-hint-html data-hint="<?php echo htmlspecialcharsbx($arResult['hintRelativeStartEndDay']); ?>"></span>
						</div>
						<div class="timeman-schedule-form-violation-detail">
							<div class="timeman-schedule-form-violation-detail-inner" data-role="start-control">
								<span class="timeman-schedule-form-violation-detail-title"><?=
									htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_START_TIME_TITLE'))
									?></span>
								<span>
									<span class="timeman-schedule-form-violation-detail-text"><?
										?>
										<span class="timeman-schedule-form-worktime-input-value-text"
												data-role="relative-start-from-link"
												data-input-selector-role="relative-start-from-input"
										><?= htmlspecialcharsbx($violationForm->getFormattedRelativeStartFrom()) ?></span>
										<input name="<?= $violationFormName . "[relativeStartFromFormatted]" ?>"
												data-role="relative-start-from-input"
												type="hidden"
												value="<?= htmlspecialcharsbx($violationForm->getFormattedRelativeStartFrom()) ?>"><?
										?></span>
									<span class="timeman-schedule-form-violation-detail-text-separator">-</span>
									<span class="timeman-schedule-form-violation-detail-text"><?
										?>
										<span class="timeman-schedule-form-worktime-input-value-text"
												data-role="relative-start-to-link"
												data-input-selector-role="relative-start-to-input"
										><?= htmlspecialcharsbx($violationForm->getFormattedRelativeStartTo()) ?>
										</span>
										<input name="<?= $violationFormName . "[relativeStartToFormatted]" ?>"
												data-role="relative-start-to-input"
												type="hidden"
												value="<?= htmlspecialcharsbx($violationForm->getFormattedRelativeStartTo()) ?>"><?
										?></span>
								</span>
							</div>
							<div class="timeman-schedule-form-violation-detail-inner" data-role="end-control">
								<span class="timeman-schedule-form-violation-detail-title"><?=
									htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_END_TIME_TITLE'))
									?></span>
								<span><?
									?>
									<span class="timeman-schedule-form-violation-detail-text"><?
										?>
										<span class="timeman-schedule-form-worktime-input-value-text"
												data-role="relative-end-from-link"
												data-input-selector-role="relative-end-from-input"
										><?= htmlspecialcharsbx($violationForm->getFormattedRelativeEndFrom()) ?>
										</span>
										<input name="<?= $violationFormName . "[relativeEndFromFormatted]" ?>"
												data-role="relative-end-from-input"
												type="hidden"
												value="<?= htmlspecialcharsbx($violationForm->getFormattedRelativeEndFrom()) ?>"><?
										?></span>
									<span class="timeman-schedule-form-violation-detail-text-separator">-</span>
									<span class="timeman-schedule-form-violation-detail-text"><?
										?>
										<span class="timeman-schedule-form-worktime-input-value-text"
												data-role="relative-end-to-link"
												data-input-selector-role="relative-end-to-input"
										><?= htmlspecialcharsbx($violationForm->getFormattedRelativeEndTo()) ?></span>
										<input name="<?= $violationFormName . "[relativeEndToFormatted]" ?>"
												data-role="relative-end-to-input"
												type="hidden"
												value="<?= htmlspecialcharsbx($violationForm->getFormattedRelativeEndTo()) ?>"><?
										?></span>
								</span>
							</div>
						</div>
					</div>
					<div class="timeman-schedule-form-violation-option
							<?= $violationForm->showOffsetStartEndDay() ? 'timeman-schedule-form-violation-option-selected' : ''; ?>"
							data-role="offset-time-block-toggle">
						<div class="timeman-schedule-form-violation-value">
							<input class="timeman-schedule-form-violation-hidden-input" id="violation-option-offset-time" type="radio"
								<?= $violationForm->showOffsetStartEndDay() ? 'checked' : ''; ?>
									data-role="offset-time-block-input"
									name="<?= $violationFormName . "[useOffsetStartEndDay]"; ?>"
							>
							<label class="timeman-schedule-form-violation-hidden-label" for="violation-option-offset-time"><?=
								htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_OFFSET_TIME_BLOCK_TITLE')); ?></label>
							<span class="timeman-schedule-form-violation-help" data-hint-html data-hint="<?php echo htmlspecialcharsbx($arResult['hintOffsetStartEndDay']); ?>"></span>
						</div>
						<div class="timeman-schedule-form-violation-detail timeman-schedule-form-violation-detail-start-end">
							<div class="timeman-schedule-form-violation-detail-inner" data-role="start-control">
								<span class="timeman-schedule-form-violation-detail-title"><?=
									htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_MAX_OFFSET_START_LINK_TITLE'));
									?></span>
								<span class="timeman-schedule-form-violation-detail-text">
									<span class="timeman-schedule-form-worktime-input-value-text"
											data-role="max-offset-start-link"
											data-input-selector-role="max-offset-start-input"><?=
										htmlspecialcharsbx($violationForm->getFormattedMaxOffsetStart())
										?></span>
									<input name="<?= $violationFormName . "[maxOffsetStartFormatted]" ?>"
											data-role="max-offset-start-input"
											type="hidden"
											value="<?= htmlspecialcharsbx($violationForm->getFormattedMaxOffsetStart()) ?>">
								</span>
							</div>
							<div class="timeman-schedule-form-violation-detail-inner" data-role="end-control">
								<span class="timeman-schedule-form-violation-detail-title"><?= htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_MIN_OFFSET_END_LINK_TITLE')); ?></span>
								<span class="timeman-schedule-form-violation-detail-text">
									<span class="timeman-schedule-form-worktime-input-value-text"
											data-role="min-offset-end-link"
											data-input-selector-role="min-offset-end-input"><?=
										htmlspecialcharsbx($violationForm->getFormattedMinOffsetEnd()) ?>
									</span>
									<input name="<?= $violationFormName . "[minOffsetEndFormatted]" ?>"
											data-role="min-offset-end-input"
											type="hidden"
											value="<?= htmlspecialcharsbx($violationForm->getFormattedMinOffsetEnd()) ?>">
								</span>
							</div>
						</div>
					</div>
				</div>
				<div class="timeman-schedule-form-settings">
					<div class="timeman-schedule-form-settings-name-block">
						<span class="timeman-schedule-form-settings-name"><?= htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_NOTIFICATION_TO_TITLE')); ?></span>
					</div>
					<?
					$APPLICATION->IncludeComponent(
						"bitrix:main.user.selector",
						"",
						[
							'ID' => 'violation-start-end-notify',
							'INPUT_NAME' => $violationFormName . '[startEndNotifyUsers][]',
							'LIST' => $violationForm->startEndNotifyUsers,
							'USE_SYMBOLIC_ID' => true,
							'SELECTOR_OPTIONS' => [
								'enableUserManager' => 'Y',
							],
						]
					);
					?>
				</div>
			</div>
		</div>
	</div>
	<div class="timeman-schedule-form-violation-block"
			data-role="violation-fix-schedule">
		<input type="checkbox" class="timeman-schedule-form-checkbox" id="violation-count"
			<?= $violationForm->showHoursPerDayViolations() ? 'checked' : ''; ?>
				name="<?= $violationFormName . "[saveHoursPerDayViolations]"; ?>">
		<div class="timeman-schedule-form-violation-inner timeman-schedule-form-violation-inner-count">
			<label class="timeman-schedule-form-violation-label" for="violation-count"><?= htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_HOURS_PER_DAY_BLOCK_TITLE')); ?></label>
			<span class="timeman-schedule-form-violation-help" data-hint="<?php echo htmlspecialcharsbx($arResult['hintMinDayDuration']); ?>"></span>
			<div class="timeman-schedule-form-violation-hidden">
				<div class="timeman-schedule-form-violation-options">
					<div class="timeman-schedule-form-violation-detail">
						<div class="timeman-schedule-form-violation-detail-inner">
							<span class="timeman-schedule-form-violation-detail-title"><?= htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_MIN_DAY_DURATION_TITLE')); ?></span>
							<span class="timeman-schedule-form-violation-detail-text">
								<span class="timeman-schedule-form-worktime-input-value-text"
										data-role="min-day-duration-link"
										data-input-selector-role="min-day-duration-input"><?=
									htmlspecialcharsbx($violationForm->getFormattedMinDayDuration())
									?></span>
								<input name="<?= $violationFormName . "[minDayDurationFormatted]" ?>"
										data-role="min-day-duration-input"
										autocomplete="off"
										type="hidden"
										value="<?= htmlspecialcharsbx($violationForm->getFormattedMinDayDuration()) ?>">
							</span>

						</div>
					</div>
				</div>
				<div class="timeman-schedule-form-settings">
					<div class="timeman-schedule-form-settings-name-block">
						<span class="timeman-schedule-form-settings-name"><?= htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_NOTIFICATION_TO_TITLE')); ?></span>
					</div>
					<?
					$APPLICATION->IncludeComponent(
						"bitrix:main.user.selector",
						"",
						[
							'ID' => 'violation-hours-per-day-notify',
							'INPUT_NAME' => $violationFormName . '[hoursPerDayNotifyUsers][]',
							'LIST' => $violationForm->hoursPerDayNotifyUsers,
							'USE_SYMBOLIC_ID' => true,
							'SELECTOR_OPTIONS' => [
								'enableUserManager' => 'Y',
							],
						]
					);
					?>
				</div>
			</div>
		</div>
	</div>
	<div class="timeman-schedule-form-violation-block"
			data-role="violation-any-schedule">
		<input type="checkbox" class="timeman-schedule-form-checkbox" id="violation-edit"
			<?= $violationForm->showEditWorktimeViolations() ? 'checked' : ''; ?>
				name="<?= $violationFormName . "[saveEditWorktimeViolations]"; ?>">
		<div class="timeman-schedule-form-violation-inner timeman-schedule-form-violation-inner-count">
			<label class="timeman-schedule-form-violation-label" for="violation-edit"><?=
				htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_EDIT_WORKTIME_BLOCK_TITLE'));
				?></label>
			<span class="timeman-schedule-form-violation-help" data-hint="<?php echo htmlspecialcharsbx($arResult['hintEditDay']); ?>"></span>
			<div class="timeman-schedule-form-violation-hidden">
				<div class="timeman-schedule-form-violation-options">
					<div class="timeman-schedule-form-violation-detail">
						<div class="timeman-schedule-form-violation-detail-inner">
							<span class="timeman-schedule-form-violation-detail-title"><?=
								htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_CHANGE_DAY_DURATION_TITLE'))
								?></span>
							<span class="timeman-schedule-form-violation-detail-text">
								<span class="timeman-schedule-form-worktime-input-value-text"
										data-role="allow-manual-change-time-link"
										data-input-selector-role="allow-manual-change-time-input"><?=
									htmlspecialcharsbx($violationForm->getFormattedMaxAllowedToEditWorkTime())
									?></span>
								<input name="<?= $violationFormName . "[maxAllowedToEditWorkTimeFormatted]" ?>"
										data-role="allow-manual-change-time-input"
										autocomplete="off"
										type="hidden"
										value="<?= htmlspecialcharsbx($violationForm->getFormattedMaxAllowedToEditWorkTime()) ?>">
							</span>
						</div>
					</div>
				</div>
				<div class="timeman-schedule-form-settings">
					<div class="timeman-schedule-form-settings-name-block">
						<span class="timeman-schedule-form-settings-name"><?= htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_NOTIFICATION_TO_TITLE')); ?></span>
					</div>
					<?
					$APPLICATION->IncludeComponent(
						"bitrix:main.user.selector",
						"",
						[
							'ID' => 'violation-edit-worktime-notify',
							'INPUT_NAME' => $violationFormName . '[editWorktimeNotifyUsers][]',
							'LIST' => $violationForm->editWorktimeNotifyUsers,
							'USE_SYMBOLIC_ID' => true,
							'SELECTOR_OPTIONS' => [
								'enableUserManager' => 'Y',
							],
						]
					);
					?>
				</div>
			</div>
		</div>
	</div>
	<div class="timeman-schedule-form-violation-block timeman-schedule-form-violation-block-period"
			data-role="violation-fix-schedule">
		<input class="timeman-schedule-form-checkbox" id="violation-period" type="checkbox"
			<?= $violationForm->showHoursForPeriodViolations() ? 'checked' : ''; ?>
				name="<?= $violationFormName . "[saveHoursForPeriodViolations]"; ?>">
		<div class="timeman-schedule-form-violation-inner timeman-schedule-form-violation-inner-period">
			<label class="timeman-schedule-form-violation-label" for="violation-period"><?=
				htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_HOURS_LACK_FOR_PERIOD_BLOCK_TITLE'))
				?></label>
			<span class="timeman-schedule-form-violation-help" data-hint="<?php echo htmlspecialcharsbx($arResult['hintHoursLackForPeriod']); ?>"></span>
			<div class="timeman-schedule-form-violation-hidden">
				<div class="timeman-schedule-form-violation-detail">
					<div class="timeman-schedule-form-violation-detail-inner">
						<label class="timeman-schedule-form-violation-detail-title"><?=
							htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_HOURS_COUNT_TITLE'))
							?></label>
						<input class="timeman-schedule-form-settings-select timeman-schedule-form-settings-select-detail timeman-schedule-form-settings-number"
								type="number"
								name="<?= $violationFormName . '[maxWorkTimeLackForPeriod]'; ?>"
								value="<?= htmlspecialcharsbx($violationForm->getFormattedMaxWorkTimeLackForPeriod()) ?>">
					</div>
				</div>
				<div class="timeman-schedule-form-settings">
					<div class="timeman-schedule-form-settings-name-block">
						<span class="timeman-schedule-form-settings-name"><?=
							htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_NOTIFICATION_TO_TITLE'))
							?></span>
					</div>
					<?
					$APPLICATION->IncludeComponent(
						"bitrix:main.user.selector",
						"",
						[
							'ID' => 'violation-hours-per-period-notify',
							'INPUT_NAME' => $violationFormName . '[hoursPerPeriodNotifyUsers][]',
							'LIST' => $violationForm->hoursPerPeriodNotifyUsers,
							'USE_SYMBOLIC_ID' => true,
							'SELECTOR_OPTIONS' => [
								'enableUserManager' => 'Y',
							],
						]
					);
					?>
				</div>
			</div>
		</div>
	</div>
	<div class="timeman-schedule-form-violation-block"
			data-role="violation-shift-schedule">
		<input type="checkbox" class="timeman-schedule-form-checkbox" id="violation-shift-delay"
			<?= $violationForm->showShiftDelayViolations() ? ' checked ' : ' '; ?>
				name="<?= $violationFormName . "[saveShiftDelayViolations]"; ?>"
		>
		<div class="timeman-schedule-form-violation-inner timeman-schedule-form-violation-inner-count">
			<label class="timeman-schedule-form-violation-label" for="violation-shift-delay"><?=
				htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_SHIFT_BLOCK_DELAY'))
				?></label>
			<div class="timeman-schedule-form-violation-hidden">
				<div class="timeman-schedule-form-violation-options">
					<div class="timeman-schedule-form-violation-detail">
						<div class="timeman-schedule-form-violation-detail-inner">
							<span class="timeman-schedule-form-violation-detail-title"><?=
								htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_SHIFT_DELAY_ALLOWED'))
								?></span>
							<span class="timeman-schedule-form-violation-detail-text">
								<span class="timeman-schedule-form-worktime-input-value-text"
										data-role="allow-shift-start-delay-link"
										data-input-selector-role="allow-shift-start-delay-input"
								><?=
									htmlspecialcharsbx($violationForm->getFormattedMaxShiftStartDelay())
									?></span>
								<input name="<?= $violationFormName . "[maxShiftStartDelayFormatted]" ?>"
										data-role="allow-shift-start-delay-input"
										autocomplete="off"
										type="hidden"
										value="<?= htmlspecialcharsbx($violationForm->getFormattedMaxShiftStartDelay()) ?>">
							</span>
						</div>
					</div>
				</div>
				<div class="timeman-schedule-form-settings">
					<div class="timeman-schedule-form-settings-name-block">
						<span class="timeman-schedule-form-settings-name"><?= htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_NOTIFICATION_TO_TITLE')); ?></span>
					</div>
					<?
					$APPLICATION->IncludeComponent(
						"bitrix:main.user.selector",
						"",
						[
							'ID' => 'shift-time-notify-users-notify',
							'INPUT_NAME' => $violationFormName . '[shiftTimeNotifyUsers][]',
							'LIST' => $violationForm->shiftTimeNotifyUsers,
							'USE_SYMBOLIC_ID' => true,
							'SELECTOR_OPTIONS' => [
								'enableUserManager' => 'Y',
							],
						]
					);
					?>
				</div>
			</div>
		</div>
	</div>
	<div class="timeman-schedule-form-violation-block"
			data-role="violation-shift-schedule">
		<input type="checkbox" class="timeman-schedule-form-checkbox" id="violation-shift-missed"
			<?= $violationForm->showShiftStartViolations() ? 'checked' : ''; ?>
				name="<?= $violationFormName . '[missedShiftStart]'; ?>"
		>
		<div class="timeman-schedule-form-violation-inner timeman-schedule-form-violation-inner-count">
			<label class="timeman-schedule-form-violation-label" for="violation-shift-missed"><?=
				htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_SHIFT_MISSED'))
				?></label>
			<div class="timeman-schedule-form-violation-hidden">
				<div class="timeman-schedule-form-settings">
					<div class="timeman-schedule-form-settings-name-block">
						<span class="timeman-schedule-form-settings-name"><?=
							htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_NOTIFICATION_TO_TITLE'))
							?></span>
					</div>
					<?
					$APPLICATION->IncludeComponent(
						"bitrix:main.user.selector",
						"",
						[
							'ID' => 'shift-check-notify-users',
							'INPUT_NAME' => $violationFormName . '[shiftCheckNotifyUsers][]',
							'LIST' => $violationForm->shiftCheckNotifyUsers,
							'USE_SYMBOLIC_ID' => 'Y',
							'SELECTOR_OPTIONS' => [
								'enableUserManager' => 'Y',
							],
						]
					);
					?>
				</div>
			</div>
		</div>
	</div>
</div>
<?


// popup content


?>
<div class="bx-tm-popup-edit-clock-wnd  timeman-pick-time-hide-clock">
	<?
	global $APPLICATION;
	ob_start();
	$APPLICATION->IncludeComponent(
		"bitrix:timeman.interface.popup.timepicker",
		".default",
		[
			'BREAK_LENGTH_INPUT_NAME' => 'plainTimeHidden',
			'BREAK_LENGTH_INPUT_ID' => 'plainTimeClock',
			'SHOW_START_END_BLOCKS' => false,
			'BREAK_LENGTH_ATTRIBUTE_DATA_ROLE' => 'plain-time-content',
		]
	);
	echo ob_get_clean();

	?>
</div>