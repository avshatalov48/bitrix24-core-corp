<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */
$map = $dialog->getMap();
?>
<input type="hidden" name="event_type" value="INFO">
<div class="crm-automation-popup-settings">
<textarea
	name="<?=htmlspecialcharsbx($map['EventText']['FieldName'])?>"
	class="crm-automation-popup-textarea"
	placeholder="<?=htmlspecialcharsbx($map['EventText']['Name'])?>"
	data-role="inline-selector-target"
><?=htmlspecialcharsbx($dialog->getCurrentValue($map['EventText']['FieldName']))?></textarea>
</div>
<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title crm-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($map['EventUser']['Name'])?>:
	</span>
	<?=$dialog->renderFieldControl($map['EventUser'])?>
</div>