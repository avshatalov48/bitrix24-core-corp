<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$status = $map['TargetStatus'];
$selected = $dialog->getCurrentValue($status['FieldName']);
?>
<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title"><?=htmlspecialcharsbx($status['Name'])?>: </span>
	<select class="crm-automation-popup-settings-dropdown" name="<?=htmlspecialcharsbx($status['FieldName'])?>">
		<?foreach ($status['Options'] as $value => $optionLabel):?>
			<option value="<?=htmlspecialcharsbx($value)?>"
				<?=($value == $selected) ? ' selected' : ''?>
			><?=htmlspecialcharsbx($optionLabel)?></option>
		<?endforeach;?>
	</select>
</div>
<?if (isset($map['ModifiedBy'])):?>
	<div class="crm-automation-popup-settings">
		<span class="crm-automation-popup-settings-title crm-automation-popup-settings-title-autocomplete">
			<?=htmlspecialcharsbx($map['ModifiedBy']['Name'])?>:
		</span>
		<?=$dialog->renderFieldControl($map['ModifiedBy'])?>
	</div>
<?endif;?>