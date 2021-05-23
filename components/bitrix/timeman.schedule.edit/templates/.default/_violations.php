<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Helper\Form\Schedule\ScheduleFormHelper;

/** @var \Bitrix\Timeman\Form\Schedule\ViolationForm $violationForm */
$violationForm = $scheduleForm->violationForm;
$violationFormName = $scheduleForm->getFormName() . '[' . $violationForm->getFormName() . ']';
$violationFormName = htmlspecialcharsbx($violationFormName);

$showContainer = $violationForm->showViolationContainer($scheduleForm->isShifted());
?>
<div class="timeman-schedule-form-block timeman-schedule-form-block-control <?= $showContainer ? 'timeman-schedule-form-wrap-open' : ''; ?>"
		data-role="violations-container">
	<input type="hidden" name="<?= $violationFormName . "[scheduleId]"; ?>" value="<?= htmlspecialcharsbx($violationForm->scheduleId); ?>">

	<div class="timeman-schedule-form-title">
		<div class="timeman-schedule-form-title-inner">
			<span class="timeman-schedule-form-title-text"><?= htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_CONTROL_TIME_TITLE')); ?></span>
		</div>
	</div>
	<div class="timeman-schedule-form-settings-inner">
		<div class="timeman-schedule-form-settings-name-block">
			<span class="timeman-schedule-form-settings-name"><?= htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_CONTROLLED_TITLE')); ?></span>
		</div>
		<select name="<?= htmlspecialcharsbx($scheduleForm->getFormName()); ?>[controlledActions]" class="timeman-schedule-form-settings-select"
				data-role="controlled-actions"
				data-autoaction="<?= $arResult['isNewSchedule'] ? 'true' : ''; ?>">
			<? foreach (ScheduleFormHelper::getControlledActionTypes() as $optValue => $textValue) : ?>
				<option value="<?= htmlspecialcharsbx($optValue); ?>" <?= $scheduleForm->controlledActions === $optValue ? 'selected' : ''; ?>>
					<?= htmlspecialcharsbx($textValue); ?></option>
			<? endforeach; ?>
		</select>
	</div>
	<div class="timeman-schedule-form-violation">
		<div class="timeman-schedule-form-violation-title" data-role="timeman-schedule-violation-toggle">
			<span class="timeman-schedule-form-violation-title-text"><?= htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_VIOLATION_CONTROL_RECORD_TITLE')); ?></span>
		</div>

		<?


		require_once '_violations_inner.php';
		?>
	</div>
</div>