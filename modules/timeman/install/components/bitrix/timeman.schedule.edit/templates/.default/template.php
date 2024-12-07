<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Timeman\Helper\Form\Schedule\ScheduleFormHelper;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Helper\Form\Schedule\CalendarFormHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;

\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/js/timeman/component/basecomponent.js');
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/components/bitrix/timeman.schedule.edit/templates/.default/js/calendar-exclusions.js');
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/components/bitrix/timeman.schedule.shift.edit/templates/.default/shift.js');
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/components/bitrix/timeman.schedule.edit/templates/.default/js/shift-multiple.js');
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/components/bitrix/timeman.interface.popup.timepicker/templates/.default/duration-picker.js');
\Bitrix\Main\UI\Extension::load(['ui.buttons', 'ui.alerts', 'loader', 'ui.design-tokens', 'ui.fonts.opensans']);

global $APPLICATION;

/** @var \Bitrix\Timeman\Form\Schedule\ScheduleForm $scheduleForm */
$scheduleForm = $arResult['scheduleForm'];
$scheduleFormName = htmlspecialcharsbx($scheduleForm->getFormName());
$scheduleIdFormName = $scheduleFormName . '[id]';
$existedScheduleTitle = Loc::getMessage('TM_SCHEDULE_READ_TITLE');
if ($arResult['canUpdateSchedule'])
{
	$existedScheduleTitle = Loc::getMessage('TM_SCHEDULE_EDIT_TITLE');
}
$APPLICATION->setTitle(htmlspecialcharsbx(
		$arResult['isNewSchedule'] ?
			Loc::getMessage('TM_SCHEDULE_CREATE_TITLE')
			: $existedScheduleTitle
	)
);
if ($arResult['showShiftPlanBtn'])
{
	$this->SetViewTarget('pagetitle') ?>
	<a href="<?php echo $arResult['shiftPlanLink']; ?>" class="ui-btn ui-btn-themes ui-btn-light-border"><?php echo htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_EDIT_SHIFT_PLAN_BTN_TITLE')); ?></a>
	<?
	$this->EndViewTarget();
}
if (\Bitrix\Main\Loader::includeModule('ui'))
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.feedback.form',
		'',
		$arResult['feedbackParams']
	);
}
?>
<div class="timeman-schedule-form-wrap <? if ($arResult['isSlider']): ?>timeman-schedule-form-slider<? endif; ?>" data-role="schedule-container">
	<div class="timeman-schedule-form-inner">
		<form action="" class="timeman-schedule-form-form-js" method="post"
				data-role="timeman-schedule-form"><?

			// SCHEDULE FIELDS ;

			?>
			<div data-role="timeman-schedule-edit-error-block"></div>
			<div class="ui-alert ui-alert-danger timeman-hide" data-role="timeman-schedule-error-msg-block">
				<div class="ui-alert-message" data-role="timeman-schedule-error-msg"></div>
			</div>

			<input type="hidden" name="<?= htmlspecialcharsbx($scheduleIdFormName) ?>" value="<?= $arResult['SCHEDULE_ID'] > 0 ? (int)$arResult['SCHEDULE_ID'] : ''; ?>">
			<div class="timeman-schedule-form-block <? if ($arResult['isSlider']): ?>timeman-schedule-form-block-title-slider<? endif; ?>">
				<div class="timeman-schedule-form-settings">
					<div class="timeman-schedule-form-settings-inner">
						<div class="timeman-schedule-form-settings-name-block">
							<span class="timeman-schedule-form-settings-name"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_SCHEDULE_TYPE_TITLE')); ?></span>
						</div>
						<select name="<?= $scheduleFormName; ?>[type]" class="timeman-schedule-form-settings-select"
								data-role="timeman-schedule-type-select">
							<? foreach (ScheduleFormHelper::getScheduleTypes() as $optValue => $textValue) : ?>
								<option value="<?= htmlspecialcharsbx($optValue); ?>" <?= $scheduleForm->type === $optValue ? 'selected' : ''; ?>>
									<?= htmlspecialcharsbx($textValue); ?></option>
							<? endforeach; ?>
						</select>
					</div>
					<div class="timeman-schedule-form-settings-inner">
						<div class="timeman-schedule-form-settings-name-block">
							<span class="timeman-schedule-form-settings-name"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_NAME_TITLE')); ?></span>
						</div>
						<input class="timeman-schedule-form-title-input"
								data-role="schedule-name"
								data-autoname="<?= $arResult['isNewSchedule'] ? '' : 'false'; ?>"
								type="text"
								name="<?= $scheduleFormName; ?>[name]"
								placeholder="<?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_DEFAULT_TITLE')); ?>"
								value="<?= htmlspecialcharsbx($scheduleForm->name) ?: ''; ?>"
						>
					</div>
					<div class="timeman-schedule-form-settings-outer-report-period">
						<div class="timeman-schedule-form-settings-inner timeman-schedule-form-settings-inner-report-period">
							<div class="timeman-schedule-form-settings-name-block">
								<span class="timeman-schedule-form-settings-name"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_REPORT_PERIOD_TITLE')); ?></span>
							</div>
							<select name="<?= $scheduleFormName; ?>[reportPeriod]" class="timeman-schedule-form-settings-select"
									data-role="timeman-report-period-select">
								<? foreach (ScheduleFormHelper::getReportPeriods() as $optValue => $textValue) : ?>
									<option value="<?= htmlspecialcharsbx($optValue); ?>" <?= $scheduleForm->reportPeriod === $optValue ? 'selected' : ''; ?>>
										<?= htmlspecialcharsbx($textValue); ?></option>
								<? endforeach; ?>
							</select>
						</div>
						<?
						$reportCss = '';
						$reportPeriodsValues = ScheduleFormHelper::getReportPeriodsValues();
						$period = $scheduleForm->reportPeriod ?: reset($reportPeriodsValues);
						if (!in_array($period, $arResult['WEEKS_PERIODS'], true))
						{
							$reportCss = 'timeman-hide';
						}
						?>
						<div class="timeman-schedule-form-settings-inner timeman-schedule-form-settings-inner-start-week-day <?= $reportCss ?>"
								data-role="timeman-report-period-start-week-day-block">
							<div class="timeman-schedule-form-settings-name-block">
								<span class="timeman-schedule-form-settings-name"><?=
									htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_REPORT_PERIOD_START_WEEK_DAY_TITLE'));
									?></span>
							</div>
							<select name="<?= $scheduleFormName; ?>[reportPeriodStartWeekDay]" class="timeman-schedule-form-settings-select">
								<? foreach (ScheduleFormHelper::getReportPeriodWeekDays() as $optValue => $textValue) : ?>
									<option value="<?= htmlspecialcharsbx($optValue); ?>" <?= $scheduleForm->reportPeriodStartWeekDay === $optValue ? 'selected' : ''; ?>>
										<?= htmlspecialcharsbx($textValue); ?></option>
								<? endforeach; ?>
							</select>
						</div>
					</div><?


					require_once '_users.php';


					?></div>
			</div><?


			require_once '_shifts.php';


			require_once '_calendar.php';


			require_once '_violations.php';
			?>

			<div class="timeman-schedule-form-block" data-role="worktime-restrictions">
				<div class="timeman-schedule-form-title">
					<div class="timeman-schedule-form-title-inner">
						<span class="timeman-schedule-form-title-text"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_REPORT_RESTRICTIONS_TITLE')); ?></span>
					</div>
				</div>
				<div class="timeman-schedule-form-limit <?php echo $scheduleForm->isShifted() ? '' : 'timeman-hide'; ?>" data-role="max-shift-start-offset-block">
					<div class="timeman-schedule-form-limit-title">
						<span class="timeman-schedule-form-limit-title-text"><?php echo Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_WORKTIME_RESTRICTION_MAX_START_OFFSET'); ?></span>
						<span class="timeman-schedule-form-violation-help" data-hint="<?php echo htmlspecialcharsbx($arResult['hintWorktimeRestrictionMaxStartOffset']); ?>"></span>
					</div>
					<div class="timeman-schedule-form-limit-inner">
						<div class="timeman-schedule-form-limit-item">
							<span class="timeman-schedule-form-violation-detail-text">
								<span class="timeman-schedule-form-worktime-input-value-text"
										data-role="max-shift-start-offset-link"
										data-input-selector-role="max-shift-start-offset-input"
								><?=
									htmlspecialcharsbx($scheduleForm->restrictionsForm->getFormattedMaxShiftStartOffset())
									?></span>
								<input name="<?= $scheduleFormName . "[" . $scheduleForm->restrictionsForm->getFormName() . "][maxShiftStartOffsetFormatted]" ?>"
										data-role="max-shift-start-offset-input"
										class="timeman-schedule-form-worktime-input-value-text"
										type="hidden"
										value="<?= htmlspecialcharsbx($scheduleForm->restrictionsForm->getFormattedMaxShiftStartOffset()) ?>">
							</span>
						</div>
					</div>
				</div>
				<div class="timeman-schedule-form-limit">
					<div class="timeman-schedule-form-limit-title">
						<span class="timeman-schedule-form-limit-title-text"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_REPORT_ALLOWED_DEVICES_TITLE')); ?></span>
					</div>
					<div class="timeman-schedule-form-limit-inner">
						<div class="timeman-schedule-form-limit-item">
							<input class="timeman-schedule-form-violation-hidden-input" type="checkbox" id="browser"
									data-role="startTimeAllowedDevice"
								<?= $scheduleForm->isBrowserDeviceAllowed() || $arResult['isNewSchedule'] ? 'checked' : ''; ?>
									name="<?= $scheduleFormName . '[allowedDevices][browser]'; ?>">
							<label class="timeman-schedule-form-violation-hidden-label" for="browser"><?=
								htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_REPORT_ALLOW_BROWSER'));
								?></label>
						</div>
						<? if (\Bitrix\Main\Loader::includeModule('faceid') && \Bitrix\FaceId\FaceId::isAvailable()): ?>
							<div class="timeman-schedule-form-limit-item">
								<input class="timeman-schedule-form-violation-hidden-input" type="checkbox" id="Bitrix24.Time"
										data-role="startTimeAllowedDevice"
									<?= $scheduleForm->isB24TimeDeviceAllowed() || $arResult['isNewSchedule'] ? 'checked' : ''; ?>
										name="<?= $scheduleFormName . '[allowedDevices][b24time]'; ?>"
								>
								<label class="timeman-schedule-form-violation-hidden-label" for="Bitrix24.Time"><?=
									htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_REPORT_ALLOW_B24_TIME'));
									?></label>
							</div>
						<? endif; ?>
						<div class="timeman-schedule-form-limit-item">
							<input class="timeman-schedule-form-violation-hidden-input" type="checkbox" id="mobile"
									data-role="startTimeAllowedDevice"
								<?= $scheduleForm->isMobileDeviceAllowed() || $arResult['isNewSchedule'] ? 'checked' : ''; ?>
									name="<?= $scheduleFormName . '[allowedDevices][mobile]'; ?>"
							>
							<label class="timeman-schedule-form-violation-hidden-label" for="mobile"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_REPORT_ALLOW_MOBILE_APP')); ?></label>
						</div>
					</div>
				</div>
			</div>
			<div class="timeman-schedule-form-buttons">
				<div class="timeman-schedule-form-buttons-inner">
					<? if ($arResult['canUpdateSchedule']): ?>
						<button class="ui-btn ui-btn-md ui-btn-success"
								data-role="timeman-schedule-btn-save"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_EDIT_BTN_SAVE_TITLE')); ?></button>
					<? endif; ?>
					<button class="ui-btn ui-btn-md ui-btn-link"
							data-role="timeman-schedule-btn-cancel"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_EDIT_BTN_CANCEL_TITLE')); ?></button>
				</div>
			</div>
		</form>
	</div>
</div>
<?
$shiftWorkdaysOptions = [];
foreach ($arResult['shiftWorkdaysOptions'] as $index => $title)
{
	$shiftWorkdaysOptions[] = [
		'id' => $index,
		'title' => $title,
	];
}
?>
<script>
	BX.ready(function ()
	{
		BX.message({
			AMPM_MODE: <?= CUtil::PhpToJSObject((bool)\IsAmPmMode(true)); ?>,
			TIMEMAN_SHIFT_EDIT_POPUP_PICK_TIME_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_POPUP_PICK_TIME_TITLE'))?>',
			TIMEMAN_SHIFT_EDIT_POPUP_WORK_TIME_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_POPUP_WORK_TIME_TITLE'))?>',
			TIMEMAN_SHIFT_EDIT_BTN_SET_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_BTN_SET_TITLE'))?>',
			TIMEMAN_SHIFT_EDIT_BTN_CANCEL_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_BTN_CANCEL_TITLE'))?>',
			TIMEMAN_SHIFT_EDIT_BTN_SAVE_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_EDIT_BTN_SAVE_TITLE')) ?>',
			TIMEMAN_SCHEDULE_EDIT_CALENDAR_TEMPLATES: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_CALENDAR_TEMPLATES')) ?>',
			TIMEMAN_SCHEDULE_EDIT_NAME_DEFAULT_FIXED: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_NAME_DEFAULT_FIXED')) ?>',
			TIMEMAN_SCHEDULE_EDIT_NAME_DEFAULT_FLEXTIME: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_NAME_DEFAULT_FLEXTIME')) ?>',
			TIMEMAN_SCHEDULE_EDIT_NAME_DEFAULT_SHIFT: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_NAME_DEFAULT_SHIFT')) ?>',
			TIMEMAN_SCHEDULE_EDIT_CALENDAR_MANAGE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_CALENDAR_MANAGE')) ?>',
			TIMEMAN_SCHEDULE_EDIT_CALENDAR_CLEAR_HOLIDAYS: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_CALENDAR_CLEAR_HOLIDAYS')) ?>',
			TIMEMAN_SCHEDULE_EDIT_CALENDAR_ADD_RUS_HOLIDAYS_HINT: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_CALENDAR_ADD_RUS_HOLIDAYS_HINT')) ?>',
			TIMEMAN_SHIFT_EDIT_DEFAULT_NAME: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_DEFAULT_NAME')) ?>',
			TM_SCHEDULE_SAVE_CONFIRM_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_SAVE_CONFIRM_TITLE')) ?>',
			TM_SCHEDULE_SAVE_CONFIRM: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_SAVE_CONFIRM')) ?>',
			TM_SCHEDULE_SAVE_CONFIRM_NO: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_SAVE_CONFIRM_NO')) ?>',
			TM_SCHEDULE_SAVE_CONFIRM_YES: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_SAVE_CONFIRM_YES')) ?>',
			TIMEMAN_SCHEDULE_FOR_ALL_USERS_WARNING: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SCHEDULE_FOR_ALL_USERS_WARNING')) ?>',
			TIMEMAN_SCHEDULE_EDIT_DEPARTMENT_WILL_BE_EXCLUDED: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_DEPARTMENT_WILL_BE_EXCLUDED')) ?>',
			TIMEMAN_SCHEDULE_EDIT_ALREADY_ASSIGNED_FEMALE_WARNING: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_ALREADY_ASSIGNED_FEMALE_WARNING')) ?>',
			TIMEMAN_SCHEDULE_EDIT_ALREADY_ASSIGNED_DEPARTMENT_WARNING: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_ALREADY_ASSIGNED_DEPARTMENT_WARNING')) ?>',
			TIMEMAN_SCHEDULE_EDIT_ALREADY_ASSIGNED_MALE_WARNING: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_ALREADY_ASSIGNED_MALE_WARNING')) ?>',
			TIMEMAN_SCHEDULE_EDIT_ADD_WORK_TIME_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_ADD_WORK_TIME_TITLE')) ?>',
			TIMEMAN_SCHEDULE_EDIT_ADD_WORK_SHIFT_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_ADD_WORK_SHIFT_TITLE')) ?>',
			TIMEMAN_SCHEDULE_EDIT_SYSTEM_CALENDAR_HOLIDAYS_OTHER_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_SYSTEM_CALENDAR_HOLIDAYS_OTHER_TITLE')) ?>',
			TIMEMAN_SCHEDULE_EDIT_SYSTEM_CALENDAR_HOLIDAYS_OF_COUNTRIES_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_SYSTEM_CALENDAR_HOLIDAYS_OF_COUNTRIES_TITLE')) ?>',
			TIMEMAN_SHIFT_EDIT_POPUP_FORMAT_HOUR: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_POPUP_FORMAT_HOUR')) ?>',
			TIMEMAN_SHIFT_EDIT_POPUP_FORMAT_MINUTE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_SHIFT_EDIT_POPUP_FORMAT_MINUTE')) ?>'
		});
		new BX.Timeman.Component.Schedule.Edit({
			containerSelector: '[data-role="schedule-container"]',
			isSlider: "<?= CUtil::JSEscape($arResult['isSlider'])?>",
			scheduleId: "<?= CUtil::JSEscape($arResult['SCHEDULE_ID'])?>",
			isScheduleFixed: <?= CUtil::PhpToJSObject($scheduleForm->isFixed() === null ? true : $scheduleForm->isFixed())?>,
			shiftedScheduleTypeName: <?= CUtil::PhpToJSObject(Schedule::getShiftedScheduleTypeName())?>,
			flextimeScheduleTypeName: <?= CUtil::PhpToJSObject(Schedule::getFlextimeScheduleTypeName())?>,
			fixedScheduleTypeName: <?= CUtil::PhpToJSObject(Schedule::getFixedScheduleTypeName())?>,
			shiftWorkdaysOptions: <?= CUtil::PhpToJSObject($shiftWorkdaysOptions);?>,
			calendarExclusionsFormName: "<?= CUtil::JSEscape($calendarFormName . '[datesJson]')?>",
			controlledStart: <?= CUtil::PhpToJSObject(ScheduleTable::CONTROLLED_ACTION_START);?>,
			selectedAssignmentCodes: <?= CUtil::PhpToJSObject($arResult['selectedAssignmentCodes']);?>,
			selectedAssignmentCodesExcluded: <?= CUtil::PhpToJSObject($arResult['selectedAssignmentCodesExcluded']);?>,
			assignmentsMap: <?= CUtil::PhpToJSObject($arResult['assignmentsMap']);?>,
			controlledStartEnd: <?= CUtil::PhpToJSObject(ScheduleTable::CONTROLLED_ACTION_START_AND_END);?>,
			scheduleIdFormName: "<?= CUtil::JSEscape($scheduleIdFormName)?>",
			calendarExclusions: <?= CUtil::PhpToJSObject(CalendarFormHelper::convertDatesToViewFormat($scheduleForm->calendarForm->dates));?>,
			customWorkdaysText: <?= CUtil::PhpToJSObject($arResult['customWorkdaysText']);?>,
			scheduleFormName: <?= CUtil::PhpToJSObject($scheduleForm->getFormName());?>,
			calendarTemplates: <?= CUtil::PhpToJSObject($arResult['calendarTemplates']);?>
		});
	});
</script>
