<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */
$map = $dialog->getMap();
?>
<div class="crm-automation-popup-settings">
<textarea
	name="<?=htmlspecialcharsbx($map['CommentText']['FieldName'])?>"
	class="crm-automation-popup-textarea"
	placeholder="<?=htmlspecialcharsbx($map['CommentText']['Name'])?>"
	data-role="inline-selector-target"
><?=htmlspecialcharsbx($dialog->getCurrentValue($map['CommentText']['FieldName']))?></textarea>
</div>
<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title crm-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($map['CommentUser']['Name'])?>:
	</span>
	<?=$dialog->renderFieldControl($map['CommentUser'])?>
</div>