<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$messageText = $map['MessageText'];

$toUsers = $dialog->getCurrentValue($map['ToUsers']);
if ($dialog->getCurrentValue($map['ToHead']['FieldName']) !== 'N')
{
	if (!is_array($toUsers))
	{
		$toUsers = (array) $toUsers;
	}

	if (empty($toUsers))
	{
		$toUsers[] = 'CONTROL_HEAD';
	}
}
?>
<div class="crm-automation-popup-settings">
	<textarea name="<?=htmlspecialcharsbx($messageText['FieldName'])?>"
			class="crm-automation-popup-textarea"
			placeholder="<?=htmlspecialcharsbx($messageText['Name'])?>"
			data-role="inline-selector-target"
	><?=htmlspecialcharsbx($dialog->getCurrentValue($messageText['FieldName'], $messageText['Default']))?></textarea>
</div>
<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title crm-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($map['ToUsers']['Name'])?>:
	</span>
	<?=$dialog->renderFieldControl($map['ToUsers'], $toUsers)?>
</div>