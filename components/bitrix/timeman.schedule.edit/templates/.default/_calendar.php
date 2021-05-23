<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
use Bitrix\Main\Localization\Loc;

?>
<div class="timeman-schedule-form-block " data-role="timeman-schedule-calendars-container">
	<div class="timeman-schedule-form-title">
		<div class="timeman-schedule-form-title-inner">
			<span class="timeman-schedule-form-title-text"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_HOLIDAYS_TITLE')); ?></span>
			<select class="timeman-schedule-form-settings-select timeman-schedule-form-settings-select-calendar-year"
					data-role="calendar-year">
				<? $currentYear = (int)date('Y'); ?>
				<? $yearDelta = 5; ?>
				<? for ($year = $currentYear - $yearDelta; $year < $currentYear + $yearDelta; $year++): ?>
					<option value="<?= (int)$year; ?>" <?= $year === $currentYear ? 'selected' : ''; ?>>
						<?= (int)$year; ?></option>
				<? endfor; ?>
			</select>
		</div>
	</div>
	<div class="timeman-schedule-form-quarter">
		<div class="timeman-schedule-form-quarter-block">
			<div class="timeman-schedule-form-quarter-select">
				<input class="timeman-schedule-form-quarter-switcher" type="radio" name="quarter" id="quarter-1" checked
						data-role="quarter-switcher"
						data-quarter="1">
				<input class="timeman-schedule-form-quarter-switcher" type="radio" name="quarter" id="quarter-2"
						data-role="quarter-switcher"
						data-quarter="2">
				<input class="timeman-schedule-form-quarter-switcher" type="radio" name="quarter" id="quarter-3"
						data-role="quarter-switcher"
						data-quarter="3">
				<input class="timeman-schedule-form-quarter-switcher" type="radio" name="quarter" id="quarter-4"
						data-role="quarter-switcher"
						data-quarter="4">
				<div class="timeman-schedule-form-quarter-list">
					<label class="timeman-schedule-form-quarter-item" for="quarter-1">
						<span class="timeman-schedule-form-quarter-text"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_HOLIDAYS_1_QUARTER')); ?></span>
					</label>
					<label class="timeman-schedule-form-quarter-item" for="quarter-2">
						<span class="timeman-schedule-form-quarter-text"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_HOLIDAYS_2_QUARTER')); ?></span>
					</label>
					<label class="timeman-schedule-form-quarter-item" for="quarter-3">
						<span class="timeman-schedule-form-quarter-text"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_HOLIDAYS_3_QUARTER')); ?></span>
					</label>
					<label class="timeman-schedule-form-quarter-item" for="quarter-4">
						<span class="timeman-schedule-form-quarter-text"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_HOLIDAYS_4_QUARTER')); ?></span>
					</label>
				</div>
			</div>
			<div class="timeman-schedule-form-quarter-settings"
					data-role="calendars-settings-btn">
				<span class="timeman-schedule-form-quarter-settings-icon"></span>
			</div>
		</div><?

		# calendars;
		$calendarFormName = $scheduleForm->getFormName() . '[' . $scheduleForm->calendarForm->getFormName() . ']';
		$calendarFormName = htmlspecialcharsbx($calendarFormName);
		?>
		<input type="hidden"
				name="<?= $calendarFormName . '[calendarId]' ?>"
				value="<?= htmlspecialcharsbx($scheduleForm->calendarForm->calendarId) ?>">
		<input type="hidden"
				data-role="calendar-parent-id"
				name="<?= $calendarFormName . '[parentId]' ?>"
				value="<?= htmlspecialcharsbx($scheduleForm->calendarForm->parentId) ?>">

		<div class="timeman-schedule-form-calendar"
				data-role="calendars-wrap"
		>
			<div class="timeman-schedule-form-calendar-item"
					data-role="calendar"
			></div>
			<div class="timeman-schedule-form-calendar-item"
					data-role="calendar"
			></div>
			<div class="timeman-schedule-form-calendar-item"
					data-role="calendar"
			></div>
		</div>
		<div class="timeman-schedule-form-calendar-days">
						<span class="timeman-schedule-form-calendar-days-block">
							<span class="timeman-schedule-form-calendar-days-value"
									data-role="calendar-weekends"></span>
							<?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_CALENDAR_DAYS_WEEKEND')); ?>,
						</span>
			<span class="timeman-schedule-form-calendar-days-block">
							<span class="timeman-schedule-form-calendar-days-value timeman-schedule-form-calendar-days-value-holiday"
									data-role="calendar-holidays"></span>
				<?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_CALENDAR_DAYS_HOLIDAY')); ?>
						</span>
		</div>
	</div>
</div>