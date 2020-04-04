<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$stage = $map['TargetStage'];
$selected = $dialog->getCurrentValue($stage['FieldName']);
?>
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title"><?=htmlspecialcharsbx($stage['Name'])?>: </span>
	<select class="bizproc-automation-popup-settings-dropdown" name="<?=htmlspecialcharsbx($stage['FieldName'])?>">
		<?foreach ($stage['Options'] as $value => $optionLabel):?>
			<option value="<?=htmlspecialcharsbx($value)?>"
				<?=($value == $selected) ? ' selected' : ''?>
			><?=htmlspecialcharsbx($optionLabel)?></option>
		<?endforeach;?>
	</select>
</div>