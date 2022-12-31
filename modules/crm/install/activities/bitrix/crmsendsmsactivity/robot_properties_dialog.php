<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$messageText = $map['MessageText'];
$phoneType = $map['PhoneType'];
$phoneTypeValue = (string)$dialog->getCurrentValue($phoneType['FieldName'], '');
$providerId = $map['ProviderId'];
$recipientType = $map['RecipientType'];
$recipientUser = $map['RecipientUser'];

$selectedRecipientType = $dialog->getCurrentValue($recipientType['FieldName']);

if (!$selectedRecipientType)
{
	$selectedRecipientType = CBPCrmSendSmsActivity::RECIPIENT_TYPE_ENTITY;
	$dialogContext = $dialog->getContext();
	if (isset($dialogContext['addMenuGroup']) && in_array($dialogContext['addMenuGroup'], ['informingEmployee', 'employee_category']))
	{
		$selectedRecipientType = CBPCrmSendSmsActivity::RECIPIENT_TYPE_USER;
	}
}
?>
<div class="bizproc-automation-popup-settings">
	<?= $dialog->renderFieldControl($messageText)?>
	<div class="bizproc-automation-popup-sms-symbol-counter"><?=GetMessage("CRM_SSMSA_SMS_SYMBOLS")?><?
		?><span class="bizproc-automation-popup-sms-symbol-counter-number" data-role="sms-length-counter">0</span><?
		?><?=GetMessage("CRM_SSMSA_SMS_SYMBOLS_FROM")?><?
		?><span class="bizproc-automation-popup-sms-symbol-counter-number">200</span>
	</div>
</div>
<? if ($selectedRecipientType === CBPCrmSendSmsActivity::RECIPIENT_TYPE_USER):?>
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($recipientUser['Name'])?>:
	</span>
	<?=$dialog->renderFieldControl($recipientUser)?>
</div>
<?else:?>
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title"><?=htmlspecialcharsbx($phoneType['Name'])?>:</span>
	<select class="bizproc-automation-popup-settings-dropdown" name="<?=htmlspecialcharsbx($phoneType['FieldName'])?>">
		<?foreach ($phoneType['Options'] as $value => $optionLabel):?>
			<option value="<?=htmlspecialcharsbx($value)?>"
				<?=($value == $phoneTypeValue) ? ' selected' : ''?>
			><?=htmlspecialcharsbx($optionLabel)?></option>
		<?endforeach;?>
	</select>
</div>
<?endif;?>
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-top"><?=htmlspecialcharsbx($providerId['Name'])?>:</span>
	<?= $dialog->renderFieldControl($providerId) ?>
</div>
<input type="hidden" name="<?=htmlspecialcharsbx($recipientType['FieldName'])?>" value="<?=htmlspecialcharsbx($selectedRecipientType)?>">
<script>
	BX.ready(function()
	{
		var dialog = BX.Bizproc.Automation.Designer.getInstance().getRobotSettingsDialog();
		if (!dialog)
		{
			return;
		}

		var textareaNode = dialog.form.elements['<?=CUtil::JSEscape($messageText['FieldName'])?>'];
		var smsLengthCounter = dialog.form.querySelector('[data-role="sms-length-counter"]');

		var updateSmsCounter = function()
		{
			var origLength = this.value.length;
			var textLength = this.value.replace(/\{\{[^\}\}]+\}\}/g, '').length;
			var hasVariables = (origLength !== textLength);
			smsLengthCounter.innerHTML = hasVariables ?  '&asymp;' + textLength : textLength;
			var classFn = (!hasVariables && origLength >= 200) ? 'addClass' : 'removeClass';
			BX[classFn](smsLengthCounter, 'bizproc-automation-popup-sms-symbol-counter-number-overhead');
		};

		BX.bind(textareaNode, 'bxchange', updateSmsCounter.bind(textareaNode));
		updateSmsCounter.call(textareaNode);
	});
</script>