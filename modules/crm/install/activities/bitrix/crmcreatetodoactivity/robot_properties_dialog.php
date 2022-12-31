<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();

$timeInterval = \Bitrix\Crm\Automation\Helper::parseDateTimeInterval(
	$dialog->getCurrentValue($map['Deadline'])
);
$day = $timeInterval['d'];
$time = ($timeInterval['h'] > 0 ? $timeInterval['h'] : 12) . ':' . (max($timeInterval['i'], 0));

?>
<div class="bizproc-automation-popup-settings">
	<?= $dialog->renderFieldControl($map['Description']) ?>
</div>
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title"><?= htmlspecialcharsbx($map['Deadline']['Name']) ?>: </span>
	<select class="bizproc-automation-popup-settings-dropdown"
			name="<?= htmlspecialcharsbx($map['Deadline']['FieldName']) ?>_interval_d">
		<option value="0" <?= ($day == 0) ? 'selected' : '' ?>>
			<?= GetMessage('CRM_BP_CREATE_TODO_RPD_DAY_0') ?>
		</option>
		<option value="1" <?= ($day == 1) ? 'selected' : '' ?>>
			<?= GetMessage('CRM_BP_CREATE_TODO_RPD_DAY_1') ?>
		</option>
		<option value="2" <?= ($day == 2) ? 'selected' : '' ?>>
			<?= GetMessage('CRM_BP_CREATE_TODO_RPD_DAY_2') ?>
		</option>
		<option value="3" <?= ($day == 3) ? 'selected' : '' ?>>
			<?= GetMessage('CRM_BP_CREATE_TODO_RPD_DAY_3') ?>
		</option>
	</select>
	<input
		class="bizproc-automation-popup-settings-select"
		readonly="readonly"
		value="<?= $time ?>"
		data-role="time-selector"
		name="<?= htmlspecialcharsbx($map['Deadline']['FieldName']) ?>_interval_t"
	>
</div>
<div class="bizproc-automation-popup-settings">
<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
	<?= htmlspecialcharsbx($map['Responsible']['Name']) ?>:
</span>
	<?= $dialog->renderFieldControl($map['Responsible']) ?>
</div>
<div class="bizproc-automation-popup-checkbox">

	<div class="bizproc-automation-popup-checkbox-item">
		<label class="bizproc-automation-popup-chk-label">
			<input type="checkbox"
				name="<?= htmlspecialcharsbx($map['AutoComplete']['FieldName']) ?>"
				value="Y"
				class="bizproc-automation-popup-chk"
				<?= $dialog->getCurrentValue($map['AutoComplete']) === 'Y' ? 'checked' : '' ?>
				data-role="save-state-checkbox"
				data-save-state-key="activity_auto_complete"
			>
			<?= htmlspecialcharsbx($map['AutoComplete']['Name']) ?>
		</label>
	</div>
</div>
