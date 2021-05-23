<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$cancelReason = $map['CancelReason'];
$status = $map['CancelStatusId'];
$selected = $dialog->getCurrentValue($status['FieldName']);
?>
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title"><?=htmlspecialcharsbx($status['Name'])?>: </span>
	<select class="bizproc-automation-popup-settings-dropdown" name="<?=htmlspecialcharsbx($status['FieldName'])?>">
		<?foreach ($status['Options'] as $value => $optionLabel):?>
			<option value="<?=htmlspecialcharsbx($value)?>"
				<?=($value == $selected) ? ' selected' : ''?>
			><?=htmlspecialcharsbx($optionLabel)?></option>
		<?endforeach;?>
	</select>
</div>
<div class="bizproc-automation-popup-settings">
	<?= $dialog->renderFieldControl($cancelReason)?>
</div>