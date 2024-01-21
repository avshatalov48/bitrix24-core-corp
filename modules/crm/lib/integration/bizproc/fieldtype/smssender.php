<?php

namespace Bitrix\Crm\Integration\BizProc\FieldType;

use Bitrix\Main;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Localization\Loc;
use Bitrix\Bizproc;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Main\Web\Json;

class SmsSender extends Bizproc\BaseType\Select
{
	public static function getName(): string
	{
		return Loc::getMessage('CRM_BP_FIELDTYPE_SMS_SENDER') ?: parent::getName();
	}

	protected static function getFieldOptions(Bizproc\FieldType $fieldType)
	{
		$options = static::makeProvidersSelectOptions(static::getProvidersList());
		return static::normalizeOptions($options);
	}

	public static function renderControlSingle(Bizproc\FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		return static::renderControl($fieldType, $field, $value, $allowSelection, $renderMode);
	}

	public static function renderControlMultiple(Bizproc\FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		return static::renderControl($fieldType, $field, $value, $allowSelection, $renderMode);
	}

	protected static function renderControl(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		$providers = static::getProvidersList();
		$fieldName = htmlspecialcharsbx(static::generateControlName($field));

		$textChoose = htmlspecialcharsbx(Loc::getMessage('CRM_BP_FIELDTYPE_SMS_SENDER_CHOOSE'));
		$textDemo = htmlspecialcharsbx(Loc::getMessage('CRM_BP_FIELDTYPE_SMS_SENDER_IS_DEMO'));
		$textCantUse = htmlspecialcharsbx(Loc::getMessage('CRM_BP_FIELDTYPE_SMS_SENDER_CANT_USE'));
		$textManage = htmlspecialcharsbx(Loc::getMessage('CRM_BP_FIELDTYPE_SMS_SENDER_MANAGE_URL'));

		$isPublicControl = $renderMode & FieldType::RENDER_MODE_PUBLIC;

		$selectorValue = null;
		$typeValue = $value;
		if (is_array($typeValue))
		{
			$typeValue = (string)current($value);
		}
		if (\CBPActivity::isExpression($typeValue))
		{
			$selectorValue = $typeValue;
			$typeValue = '';
		}

		$valueHtml = htmlspecialcharsbx((string)$typeValue);

		if ($selectorValue && $isPublicControl)
		{
			$valueHtml = htmlspecialcharsbx($selectorValue);
			$textChoose = $valueHtml;
		}

		$selectorAttributes = '';

		if ($allowSelection && $isPublicControl)
		{
			$selectorAttributes = sprintf(
				'data-role="inline-selector-target" data-property="%s" ',
				htmlspecialcharsbx(Main\Web\Json::encode($fieldType->getProperty()))
			);
		}

		$controlId = htmlspecialcharsbx(static::generateControlId($field));
		$labelId = $controlId . '_label';
		$className = htmlspecialcharsbx(static::generateControlClassName($fieldType, $field));

		$node = <<<HTML
			<div class="bizproc-automation-popup-select bizproc-automation-popup-select-margin-down-s">
				<input type="hidden" name="{$fieldName}" value="{$valueHtml}" id="{$controlId}">
				<label
					id="{$labelId}"
					for="{$controlId}"
					{$selectorAttributes}
					data-role="inline-selector-target" 
					class="{$className} bizproc-type-control-select-wide"
				>{$textChoose}</label>
				<a
					data-role="provider-manage-url"
				 	href=""
				 	class="bizproc-automation-popup-settings-button-right bizproc-automation-popup-settings-button"
				 	style="visibility: hidden"
				 	target="_blank">{$textManage}</a>
			</div>
			<div
				data-role="provider-notice"
				class="bizproc-automation-popup-settings-alert"
				style="display: none"
				data-text-demo="{$textDemo}"
				data-text-cantuse="{$textCantUse}"></div>
HTML;

		if ($allowSelection && !$isPublicControl)
		{
			$node .= static::renderControlSelector($field, $selectorValue, true, '', $fieldType);
		}

		return $node . static::getJs($providers, $controlId, $labelId);
	}

	private static function getProvidersList()
	{
		$result = [
			[
				'IS_INTERNAL' => false,
				'ID' => '',
				'NAME' => Loc::getMessage('CRM_BP_FIELDTYPE_SMS_SENDER_CHOOSE'),
				'CAN_USE' => true,
				'FROM_LIST' => [],
			],
			[
				'IS_INTERNAL' => false,
				'ID' => ':default:',
				'NAME' => Loc::getMessage('CRM_BP_FIELDTYPE_SMS_SENDER_DEFAULT'),
				'CAN_USE' => true,
				'FROM_LIST' => [],
			],
		];

		foreach (SmsManager::getSenderInfoList(true) as $sender)
		{
			if ($sender['isTemplatesBased'])
			{
				continue;
			}

			$providerData = [
				'IS_INTERNAL' => $sender['isConfigurable'],
				'ID' => $sender['id'],
				'NAME' => $sender['name'],
				'CAN_USE' => $sender['canUse'],
				'FROM_LIST' => $sender['fromList'],
			];

			if ($sender['isConfigurable'])
			{
				$providerData['IS_DEMO'] = $sender['isDemo'];
				$providerData['MANAGE_URL'] = $sender['manageUrl'];
			}
			$result[] = $providerData;
		}

		return $result;
	}

	private static function makeProvidersSelectOptions(array $providers)
	{
		$options = [];
		foreach ($providers as $provider)
		{
			$options[$provider['ID']] = $provider['NAME'];
		}

		return $options;
	}

	private static function getJs(array $providers, string $controlId, string $labelId)
	{
		$providersJs = Json::encode($providers);
		$controlIdJs = \CUtil::JSEscape($controlId);
		$labelIdJs = \CUtil::JSEscape($labelId);
		$marketJs = \CUtil::JSEscape(Loc::getMessage('CRM_BP_FIELDTYPE_SMS_SENDER_MARKETPLACE'));
		$marketLink = \CUtil::JSEscape(\Bitrix\Crm\Integration\Market\Router::getCategoryPath('crm_robot_sms'));

		return <<<HTML
			<script>
				BX.ready(function()
				{
					var labelNode = document.getElementById('{$labelIdJs}');
					var valueNode = document.getElementById('{$controlIdJs}');
					
					if (!labelNode || !valueNode)
					{
						return;
					}
					
					var manageUrlNode = labelNode.closest('form').querySelector('[data-role="provider-manage-url"]');
					var noticeNode = labelNode.closest('form').querySelector('[data-role="provider-notice"]');
			
					var menuId = 'BPCRMSSMSA_menu' + Math.random();
					var providers = {$providersJs};
			
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
							labelNode.textContent = fromLabel;
						}
						else if (fromLabel)
						{
							labelNode.textContent = providerLabel + ' (' + fromLabel + ')';
						}
						else
						{
							labelNode.textContent = providerLabel;
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
			
					var setProvider = function(providerId)
					{
						valueNode.value = providerId;
						
						var fromId = '';
						providerId = providerId.split('@');
						if (providerId.length > 1)
						{
							fromId = providerId.shift();
						}
						providerId = providerId.join('');

						var info = getProviderInfo(providerId);
						if (!info)
						{
							return;
						}

						setProviderLabel(info, fromId);
			
						manageUrlNode.href = info['MANAGE_URL'] ? info['MANAGE_URL'] : '#';
						BX.style(manageUrlNode, 'visibility', info['MANAGE_URL'] ? 'visible' : 'hidden');
			
						var noticeText = getNoticeText(info);
						noticeNode.textContent = noticeText? noticeText : '';
						BX.style(noticeNode, 'display', noticeText? '' : 'none');
					};
			
					var onItemClick = function(e, item)
					{
						(this.getRootMenuWindow() || this.popupWindow).close();
						setProvider(item.providerId);
					};
			
					BX.bind(labelNode, 'click', function(e)
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
										providerId: [item.id, 'rest'].join('@'),
										onclick: onItemClick
									});
								});
							}
							else
							{
								var menuItem = {
									text: proviver['NAME'],
									providerId: proviver['ID'],
									onclick: onItemClick
								};
			
								if (proviver['FROM_LIST'].length > 1)
								{
									var subItems = [];
			
									proviver['FROM_LIST'].forEach(function(item)
									{
										subItems.push({
											text: item.name,
											providerId: [item.id, proviver['ID']].join('@'),
											onclick: onItemClick
										});
									});
			
									menuItem['items'] = subItems;
								}
			
								menuItems.push(menuItem);
							}
						}
			
						menuItems.push({delimiter: true}, {
							text: '{$marketJs}',
							href: '{$marketLink}',
							target: '_blank'
						});
			
						BX.PopupMenu.show(
							menuId,
							element,
							menuItems,
							{
								overlay: { backgroundColor: 'transparent' },
								autoHide: true,
								offsetLeft: 40,
								angle: { position: 'top', offset: 0 },
								closeByEsc: true
							}
						);
					});
			
					setProvider(valueNode.value);
				});
			</script>
HTML;
	}
}
