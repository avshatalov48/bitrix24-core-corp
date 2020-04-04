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

$selectedProviderId = (string)$dialog->getCurrentValue($providerId['FieldName'], '');
$selectedMessageFrom = (string)$dialog->getCurrentValue($map['MessageFrom']['FieldName'], '');
$selectedRecipientType = $dialog->getCurrentValue($recipientType['FieldName']);

$data = $dialog->getRuntimeData();

if (!$selectedRecipientType)
{
	$selectedRecipientType = CBPCrmSendSmsActivity::RECIPIENT_TYPE_ENTITY;
	$dialogContext = $dialog->getContext();
	if (isset($dialogContext['ADD_MENU_CATEGORY']) && $dialogContext['ADD_MENU_CATEGORY'] === 'employee')
	{
		$selectedRecipientType = CBPCrmSendSmsActivity::RECIPIENT_TYPE_USER;
	}
}
?>
<div class="crm-automation-popup-settings">
	<textarea name="<?=htmlspecialcharsbx($messageText['FieldName'])?>"
			class="crm-automation-popup-textarea"
			placeholder="<?=htmlspecialcharsbx($messageText['Name'])?>"
			data-role="inline-selector-target"
	><?=htmlspecialcharsbx($dialog->getCurrentValue($messageText['FieldName'], ''))?></textarea>
	<div class="crm-automation-popup-sms-symbol-counter"><?=GetMessage("CRM_SSMSA_SMS_SYMBOLS")?><?
		?><span class="crm-automation-popup-sms-symbol-counter-number" data-role="sms-length-counter">0</span><?
		?><?=GetMessage("CRM_SSMSA_SMS_SYMBOLS_FROM")?><?
		?><span class="crm-automation-popup-sms-symbol-counter-number">200</span>
	</div>
</div>
<? if ($selectedRecipientType === CBPCrmSendSmsActivity::RECIPIENT_TYPE_USER):?>
<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title crm-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($recipientUser['Name'])?>:
	</span>
	<?=$dialog->renderFieldControl($recipientUser)?>
</div>
<?else:?>
<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title"><?=htmlspecialcharsbx($phoneType['Name'])?>:</span>
	<select class="crm-automation-popup-settings-dropdown" name="<?=htmlspecialcharsbx($phoneType['FieldName'])?>">
		<?foreach ($phoneType['Options'] as $value => $optionLabel):?>
			<option value="<?=htmlspecialcharsbx($value)?>"
				<?=($value == $phoneTypeValue) ? ' selected' : ''?>
			><?=htmlspecialcharsbx($optionLabel)?></option>
		<?endforeach;?>
	</select>
</div>
<?endif;?>
<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title crm-automation-popup-settings-title-top"><?=htmlspecialcharsbx($providerId['Name'])?>:</span>
	<div class="crm-automation-popup-select crm-automation-popup-select-margin-down-s">
		<div data-role="provider-label" class="crm-automation-popup-settings-dropdown-flexible crm-automation-popup-settings-dropdown"><?=GetMessage('CRM_SSMSA_RPD_CHOOSE_PROVIDER')?></div>
		<a data-role="provider-manage-url" href="" class="crm-automation-popup-settings-button-right crm-automation-popup-settings-button" style="visibility: hidden" target="_blank"><?=GetMessage('CRM_SSMSA_RPD_PROVIDER_MANAGE_URL')?></a>
		<input type="hidden" name="<?=htmlspecialcharsbx($providerId['FieldName'])?>" value="<?=htmlspecialcharsbx($selectedProviderId)?>">
		<input type="hidden" name="<?=htmlspecialcharsbx($map['MessageFrom']['FieldName'])?>" value="<?=htmlspecialcharsbx($selectedMessageFrom)?>">
	</div>
	<div data-role="provider-notice" class="crm-automation-popup-settings-alert" style="display: none"
		data-text-demo="<?=htmlspecialcharsbx(GetMessage('CRM_SSMSA_RPD_PROVIDER_IS_DEMO'))?>"
		data-text-cantuse="<?=htmlspecialcharsbx(GetMessage('CRM_SSMSA_RPD_PROVIDER_CANT_USE'))?>"></div>
</div>
<input type="hidden" name="<?=htmlspecialcharsbx($recipientType['FieldName'])?>" value="<?=htmlspecialcharsbx($selectedRecipientType)?>">
<script>
	BX.ready(function()
	{
		var dialog = BX.Bizproc.Automation.Designer.getRobotSettingsDialog();
		if (!dialog)
		{
			return;
		}

		var providerIdInput = dialog.form.elements['<?=CUtil::JSEscape($providerId['FieldName'])?>'];
		var messageFromInput = dialog.form.elements['<?=CUtil::JSEscape($map['MessageFrom']['FieldName'])?>'];
		var providerLabelNode = dialog.form.querySelector('[data-role="provider-label"]');
		var manageUrlNode = dialog.form.querySelector('[data-role="provider-manage-url"]');
		var noticeNode = dialog.form.querySelector('[data-role="provider-notice"]');
		var textareaNode = dialog.form.elements['<?=CUtil::JSEscape($messageText['FieldName'])?>'];
		var smsLengthCounter = dialog.form.querySelector('[data-role="sms-length-counter"]');

		var menuId = 'BPCRMSSMSA_menu' + Math.random();
		var providers = <?=\Bitrix\Main\Web\Json::encode($data['providers'])?>;

		var getProviderInfo = function(id)
		{
			for (var i = 0; i < providers.length; ++i)
			{
				if (providers[i]['ID'] === id)
				{
					return providers[i];
				}
			}
			return null;
		};

		var setProviderLabel = function(providerInfo, fromId)
		{
			var providerLabel = providerInfo['NAME'];
			var fromLabel = null;
			if (fromId)
			{
				fromLabel = fromId;
				providerInfo['FROM_LIST'].forEach(function(item)
				{
					if (item.id === fromId)
					{
						fromLabel = item.name;
					}
				});
			}

			if (providerInfo['ID'] === 'rest')
			{
				providerLabelNode.textContent = fromLabel;
			}
			else if (fromLabel)
			{
				providerLabelNode.textContent = providerLabel + ' (' + fromLabel + ')';
			}
			else
			{
				providerLabelNode.textContent = providerLabel;
			}
		};

		var getNoticeText = function(providerInfo)
		{
			if (providerInfo['IS_INTERNAL'])
			{
				if (providerInfo['IS_DEMO'])
				{
					return noticeNode.getAttribute('data-text-demo');
				}
				else if (!providerInfo['CAN_USE'])
				{
					return noticeNode.getAttribute('data-text-cantuse');
				}
			}
			return '';
		};

		var setProvider = function(providerId, fromId)
		{
			var info = getProviderInfo(providerId);
			if (!info)
			{
				return;
			}

			providerIdInput.value = info['ID'];
			messageFromInput.value = fromId;

			setProviderLabel(info, fromId);

			manageUrlNode.href = info['MANAGE_URL'] ? info['MANAGE_URL'] : '#';
			BX.style(manageUrlNode, 'visibility', info['MANAGE_URL'] ? 'visible' : 'hidden');

			var noticeText = getNoticeText(info);
			noticeNode.textContent = noticeText? noticeText : '';
			BX.style(noticeNode, 'display', noticeText? '' : 'none');
		};


		var onItemClick = function(e, item)
		{
			this.popupWindow.close();
			setProvider(item.providerId, item.fromId);
		};

		BX.bind(providerLabelNode, 'click', function(e)
		{
			var element = this;
			var menuItems = [];

			for (var i = 0; i < providers.length; ++i)
			{
				var proviver = providers[i];

				if (proviver['ID'] === 'rest')
				{
					proviver['FROM_LIST'].forEach(function(item)
					{
						menuItems.push({
							text: item.name,
							providerId: 'rest',
							fromId: item.id,
							onclick: onItemClick
						});
					});
				}
				else
				{
					var menuItem = {
						text: proviver['NAME'],
						providerId: proviver['ID'],
						fromId: '',
						onclick: onItemClick
					};

					if (proviver['FROM_LIST'].length > 1)
					{
						var subItems = [];

						proviver['FROM_LIST'].forEach(function(item)
						{
							subItems.push({
								text: item.name,
								providerId: proviver['ID'],
								fromId: item.id,
								onclick: onItemClick
							});
						});

						menuItem['items'] = subItems;
					}

					menuItems.push(menuItem);
				}
			}

			menuItems.push({delimiter: true}, {
				text: '<?=GetMessageJS('CRM_SSMSA_RPD_MARKETPLACE')?>',
				href: '/marketplace/category/crm_robot_sms/',
				target: '_blank'
			});

			BX.PopupMenu.show(
				menuId,
				element,
				menuItems,
				{
					zIndex: 200,
					autoHide: true,
					offsetLeft: 40,
					angle: { position: 'top', offset: 0 },
					closeByEsc: true
				}
			);
		});

		setProvider(providerIdInput.value, messageFromInput.value);

		var updateSmsCounter = function()
		{
			var origLength = this.value.length;
			var textLength = this.value.replace(/\{\{[^\}\}]+\}\}/g, '').length;
			var hasVariables = (origLength !== textLength);
			smsLengthCounter.innerHTML = hasVariables ?  '&asymp;' + textLength : textLength;
			var classFn = (!hasVariables && origLength >= 200) ? 'addClass' : 'removeClass';
			BX[classFn](smsLengthCounter, 'crm-automation-popup-sms-symbol-counter-number-overhead');
		};

		BX.bind(textareaNode, 'bxchange', updateSmsCounter.bind(textareaNode));
		updateSmsCounter.call(textareaNode);
	});
</script>