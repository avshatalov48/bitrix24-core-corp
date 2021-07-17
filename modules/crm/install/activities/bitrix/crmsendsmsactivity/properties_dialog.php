<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */


$map = $dialog->getMap();
$messageText = $map['MessageText'];
$phoneType = $map['PhoneType'];
$phoneTypeValue = $dialog->getCurrentValue($phoneType['FieldName'], '');
$providerId = $map['ProviderId'];
?>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?=htmlspecialcharsbx($messageText['Name'])?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("text", $messageText['FieldName'], $dialog->getCurrentValue($messageText['FieldName']), Array('rows'=> 7))?>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><?=htmlspecialcharsbx($phoneType['Name'])?>:</td>
	<td width="60%">
		<select name="<?=htmlspecialcharsbx($phoneType['FieldName'])?>">
			<?
			foreach ($phoneType['Options'] as $key => $option):
				$selected = ($phoneTypeValue === $key) ? 'selected' : '';
				?>
				<option value="<?=htmlspecialcharsbx($key)?>" <?=$selected?>><?=htmlspecialcharsbx($option)?></option>
			<?endforeach;?>
		</select>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?=htmlspecialcharsbx($providerId['Name'])?>:</span></td>
	<td width="60%">
		<?= $dialog->renderFieldControl($providerId, null, true, 0) ?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span class="adm-required-field"><?=htmlspecialcharsbx($map['RecipientType']['Name'])?>:</span></td>
	<td width="60%">
		<select name="<?=htmlspecialcharsbx($map['RecipientType']['FieldName'])?>">
			<?
			$recipientTypeValue = $dialog->getCurrentValue($map['RecipientType']['FieldName'],'');
			foreach ($map['RecipientType']['Options'] as $key => $option):
				$selected = ($recipientTypeValue === $key) ? 'selected' : '';
				?>
				<option value="<?=htmlspecialcharsbx($key)?>" <?=$selected?>><?=htmlspecialcharsbx($option)?></option>
			<?endforeach;?>
		</select>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><?=htmlspecialcharsbx($map['RecipientUser']['Name'])?>:</td>
	<td width="60%">
		<? echo $dialog->getFieldTypeObject($map['RecipientUser'])->renderControl(array(
			'Form' => $dialog->getFormName(),
			'Field' => $map['RecipientUser']['FieldName']
		), $dialog->getCurrentValue($map['RecipientUser']['FieldName']), true, 0);
		?>
	</td>
</tr>
