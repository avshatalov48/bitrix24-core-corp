<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */


$map = $dialog->getMap();
$messageText = $map['MessageText'];
$phoneType = $map['PhoneType'];
$phoneTypeValue = $dialog->getCurrentValue($phoneType['FieldName'], '');
$providerId = $map['ProviderId'];
$providerIdValue = $dialog->getCurrentValue($providerId['FieldName'], '');

$messageFrom = $map['MessageFrom'];
$messageFromValue = $dialog->getCurrentValue($messageFrom['FieldName'], '');

$data = $dialog->getRuntimeData();
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
		<select name="<?=htmlspecialcharsbx($providerId['FieldName'])?>" id="BPCSSA-provider-id">
			<?
			foreach ($providerId['Options'] as $key => $option):
				$selected = ($providerIdValue === $key) ? 'selected' : '';
				?>
				<option value="<?=htmlspecialcharsbx($key)?>" <?=$selected?>><?=htmlspecialcharsbx($option)?></option>
			<?endforeach;?>
		</select>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?=htmlspecialcharsbx($messageFrom['Name'])?>:</span></td>
	<td width="60%">
		<select name="<?=htmlspecialcharsbx($messageFrom['FieldName'])?>" id="BPCSSA-message-from">
			<option value=""><?=GetMessage('CRM_SSMSA_FROM_DEFAULT')?></option>
		</select>
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
<script>
	BX.ready(function()
	{
		var providers = <?=\Bitrix\Main\Web\Json::encode($data['providers'])?>;

		var providerSelect = BX('BPCSSA-provider-id');
		var fromSelect = BX('BPCSSA-message-from');

		var getFromList = function(providerId)
		{
			for (var i = 0; i < providers.length; ++i)
			{
				if (providers[i]['ID'] === providerId)
				{
					return providers[i]['FROM_LIST'] || [];
				}
			}

			return [];
		};

		var fillFromSelect = function(fromList, selected)
		{
			var i;
			for (i = 1; i < fromSelect.children.length; ++i)
			{
				fromSelect.removeChild(fromSelect.children[i]);
			}

			for (i = 0; i < fromList.length; ++i)
			{
				var optionProps = {
					props: {value: fromList[i]['id']},
					text: fromList[i]['name']
				};

				if (fromList[i]['id'] === selected)
				{
					optionProps['attrs'] = {selected: 'selected'};
				}

				fromSelect.appendChild(BX.create('option', optionProps));
			}
		};

		BX.bind(providerSelect, 'change', function()
		{
			fillFromSelect(getFromList(this.value));
		});

		fillFromSelect(
			getFromList(providerSelect.value),
			'<?=CUtil::jsEscape($messageFromValue)?>'
		);
	});
</script>