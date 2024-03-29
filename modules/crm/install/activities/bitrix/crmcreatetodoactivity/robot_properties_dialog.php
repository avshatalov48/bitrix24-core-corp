<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
?>
<div class="bizproc-automation-popup-settings">
	<?= $dialog->renderFieldControl($map['Description']) ?>
</div>
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title"><?= htmlspecialcharsbx($map['Deadline']['Name']) ?>: </span>
	<?= $dialog->renderFieldControl($map['Deadline']) ?>
</div>
<div class="bizproc-automation-popup-settings">
<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
	<?= htmlspecialcharsbx($map['Responsible']['Name']) ?>:
</span>
	<?= $dialog->renderFieldControl($map['Responsible']) ?>
</div>
<?php if (isset($map['AutoComplete'])):?>
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
<?php endif;
