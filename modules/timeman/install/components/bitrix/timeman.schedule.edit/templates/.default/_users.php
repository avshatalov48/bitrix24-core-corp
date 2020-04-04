<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
};

use Bitrix\Main\Localization\Loc; ?>
<div class="timeman-schedule-form-settings" data-role="assignments-wrapper">
	<div class="timeman-schedule-form-settings-name-block">
		<span class="timeman-schedule-form-settings-name"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_USERS_TITLE')); ?></span>
	</div>
	<?
	$APPLICATION->IncludeComponent(
		"bitrix:main.user.selector",
		"",
		[
			'ID' => $scheduleForm->getFormName() . '-assignments-id',
			'INPUT_NAME' => $scheduleForm->getFormName() . '[assignments][]',
			'LIST' => $scheduleForm->assignments,
			'SELECTOR_OPTIONS' => [
				'departmentSelectDisable' => 'N',
				'enableDepartments' => 'Y',
				'enableAll' => 'Y',
			],
		]
	);
	?>
</div>
<div class="timeman-schedule-form-error" data-role="timeman-schedule-assignments-error-block"></div>
<div class="timeman-schedule-form-excluded-link-wrapper">
	<span class="timeman-schedule-form-worktime-value-text timeman-schedule-form-excluded-link"
			data-role="timeman-excluded-users-show-btn"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_EXCLUDE_USERS_TITLE')); ?></span>
</div>

<div class="timeman-schedule-form-settings timeman-schedule-users-exclude-container <?= empty($scheduleForm->assignmentsExcluded) ? 'timeman-hide' : ''; ?>"
		data-role="excluded-container">
	<div class="timeman-schedule-form-settings-name-block">
		<span class="timeman-schedule-form-settings-name"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_EXCLUDE_USERS_SUB_TITLE')); ?></span>
	</div>
	<?
	$APPLICATION->IncludeComponent(
		"bitrix:main.user.selector",
		"",
		[
			'ID' => $scheduleForm->getFormName() . '-exclude-assignments-id',
			'INPUT_NAME' => $scheduleForm->getFormName() . '[assignmentsExcluded][]',
			'LIST' => $scheduleForm->assignmentsExcluded,
			'SELECTOR_OPTIONS' => [
				'departmentSelectDisable' => 'N',
				'enableDepartments' => 'Y',
			],
		]
	);
	?>
</div>