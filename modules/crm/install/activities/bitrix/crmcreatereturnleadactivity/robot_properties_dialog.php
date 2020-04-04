<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$title = $map['LeadTitle'];
?>
<div class="crm-automation-popup-settings">
	<input name="<?=htmlspecialcharsbx($title['FieldName'])?>" type="text" class="crm-automation-popup-input"
	   value="<?=htmlspecialcharsbx($dialog->getCurrentValue($title['FieldName']))?>"
	   placeholder="<?=htmlspecialcharsbx($title['Name'])?>"
	   data-role="inline-selector-target"
	>
</div>
<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title crm-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($map['Responsible']['Name'])?>:
	</span>
	<?=$dialog->renderFieldControl($map['Responsible'])?>
</div>