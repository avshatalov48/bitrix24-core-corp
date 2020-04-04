<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$title = $map['DealTitle'];
$category = $map['CategoryId'];
$selected = $dialog->getCurrentValue($category['FieldName']);
?>
<div class="crm-automation-popup-settings">
	<input name="<?=htmlspecialcharsbx($title['FieldName'])?>" type="text" class="crm-automation-popup-input"
		   value="<?=htmlspecialcharsbx(\Bitrix\Crm\Automation\Helper::convertExpressions($dialog->getCurrentValue($title['FieldName']), $dialog->getDocumentType()))?>"
		   placeholder="<?=htmlspecialcharsbx($title['Name'])?>"
		   data-role="inline-selector-target"
	>
</div>
<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title"><?=htmlspecialcharsbx($category['Name'])?>: </span>
	<select class="crm-automation-popup-settings-dropdown" name="<?=htmlspecialcharsbx($category['FieldName'])?>">
		<?foreach ($category['Options'] as $value => $optionLabel):?>
			<option value="<?=htmlspecialcharsbx($value)?>"
				<?=($value == $selected) ? ' selected' : ''?>
			><?=htmlspecialcharsbx($optionLabel)?></option>
		<?endforeach;?>
	</select>
</div>
<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title crm-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($map['Responsible']['Name'])?>:
	</span>
	<?=$dialog->renderFieldControl($map['Responsible'])?>
</div>