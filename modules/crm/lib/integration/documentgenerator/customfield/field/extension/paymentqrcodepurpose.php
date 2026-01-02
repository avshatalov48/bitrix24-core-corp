<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\Extension;

use Bitrix\Crm\Format\PlaceholderFormatter;
use Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\CustomField;
use Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\FieldInterface;
use Bitrix\Crm\Integration\DocumentGenerator\CustomField\FieldController;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

final class PaymentQrCodePurpose
{
	public const FIELD_UID = 'payment_qr_code_purpose';
	private const FIELD_JS_COMPONENT_NAME = 'crm.field.inline-placeholder-selector';

	public static function getField(array $input): FieldInterface
	{
		Extension::load(self::FIELD_JS_COMPONENT_NAME);
		Asset::getInstance()->addJs('/bitrix/js/crm/common.js');

		return (new CustomField(self::FIELD_UID))
			->setTitle(Loc::getMessage('CRM_DOCGEN_CUSTOM_FIELD_QR_CODE_PURPOSE_TITLE'))
			->setHtml('<div id="payment-qr-code-purpose-selector-input" ></div>')
			->setJavascript(self::generateJavaScript($input))
		;
	}

	public static function getValue(int $templateId, ItemIdentifier $identifier): ?string
	{
		if ($templateId <= 0)
		{
			return null;
		}

		$savedValues = (new FieldController($templateId))->load();
		$displayFormat = $savedValues[self::FIELD_UID] ?? '';
		$externalFormat = PlaceholderFormatter::convertToExternalFormat(
			$identifier->getEntityTypeId(),
			$displayFormat
		);

		$result = DocumentGeneratorManager::getInstance()->replacePlaceholdersInText(
			$identifier->getEntityTypeId(),
			$identifier->getEntityId(),
			$externalFormat,
			'',
			false
		);

		return isset($result)
			? html_entity_decode($result)
			: null
		;
	}

	private static function generateJavaScript(array $input): string
	{
		$fieldUid = self::FIELD_UID;
		$displayValue =	isset($input[$fieldUid]) ? Json::encode($input[$fieldUid]) : '""';
		$entityTypeIds = Json::encode(array_map(
			static fn (string $input) => mb_strtolower(substr(strrchr($input, '\\'), 1)),
			DocumentGeneratorManager::getInstance()->getCrmOwnerTypeProvidersMap()
		));

		return <<<JS
			BX.ready(function() {
				const entityTypeIdsMap = JSON.parse('$entityTypeIds');
				const resolveEntityTypeId = function (data, map)
				{
					const sortedEntities = Object.entries(map).sort(([, nameA], [, nameB]) => nameB.length - nameA.length);
					const foundKeys = new Set();

					for (const inputString of data) 
					{
						const providerName = inputString.split('\\\\').pop();
						if (!providerName)
						{
							continue;
						}

						for (const [key, entityName] of sortedEntities) 
						{
							if (providerName.startsWith(entityName))
							{
								foundKeys.add(key);
								break;
							}
						}
					}

					return Array.from(foundKeys);
				}

				const inlinePlaceholderSelector = new BX.Crm.Field.InlinePlaceholderSelector({
					target: document.getElementById('payment-qr-code-purpose-selector-input'),
					entityTypeIds: [], // 1. empty by default
					multiple: true,
					value: $displayValue,
					onBeforeMenuOpen: () => {
						const providers = BX.DocumentGenerator?.UploadTemplate?.providerSelector?.getValue() || [];
						if (BX.Type.isArrayFilled(providers))
						{
							const entityTypeIds = resolveEntityTypeId(providers, entityTypeIdsMap);
							inlinePlaceholderSelector.setEntityTypeIds(entityTypeIds); // 2. set entityTypeIds here
						}
					}
				});
				inlinePlaceholderSelector.show();
				
				if (BX.Type.isPlainObject(BX.DocumentGenerator?.UploadTemplate?.customFieldExtentions))
				{
					BX.DocumentGenerator.UploadTemplate.customFieldExtentions["$fieldUid"] = inlinePlaceholderSelector;
				}
			});
JS;
	}
}
