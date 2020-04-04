<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$messageText = $map['MessageText'];
?>
<div class="crm-automation-popup-settings">
	<textarea name="<?=htmlspecialcharsbx($messageText['FieldName'])?>"
			class="crm-automation-popup-textarea"
			placeholder="<?=htmlspecialcharsbx($messageText['Name'])?>"
			data-role="inline-selector-target"><?
		echo htmlspecialcharsbx($dialog->getCurrentValue($messageText['FieldName'], $messageText['Default']))
	?></textarea>
</div>
<div class="crm-automation-popup-checkbox">
	<div class="crm-automation-popup-checkbox-item">
		<label class="crm-automation-popup-chk-label">
			<input type="hidden" name="<?=htmlspecialcharsbx($map['IsSystem']['FieldName'])?>" value="N">
			<input type="checkbox" name="<?=htmlspecialcharsbx($map['IsSystem']['FieldName'])?>" value="Y" class="crm-automation-popup-chk" <?=$dialog->getCurrentValue($map['IsSystem']['FieldName']) === 'Y' ? 'checked' : ''?>>
			<?=htmlspecialcharsbx($map['IsSystem']['Name'])?>
		</label>
		<?if (!empty($map['IsSystem']['Description'])):?>
		<span class="bizproc-automation-status-help" data-hint="<?=htmlspecialcharsbx($map['IsSystem']['Description'])?>">?</span>
		<?endif?>
	</div>
</div>