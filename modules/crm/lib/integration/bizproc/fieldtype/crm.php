<?php

namespace Bitrix\Crm\Integration\BizProc\FieldType;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\InvoiceSettings;
use Bitrix\Main,
	Bitrix\Bizproc\FieldType,
	Bitrix\Main\Page\Asset,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Crm extends UserFieldBase
{
	/**
	 * @inheritdoc
	 */
	public static function renderControlSingle(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		static $rendered;

		if (!$rendered)
		{
			\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
			Asset::getInstance()->addCss('/bitrix/js/crm/css/crm.css');
			$rendered = true;
		}

		return parent::renderControlSingle($fieldType, $field, $value, $allowSelection, $renderMode);
	}

	/**
	 * @inheritdoc
	 */
	public static function renderControlOptions(FieldType $fieldType, $callbackFunctionName, $value)
	{
		$entity = $fieldType->getOptions();

		if (empty($entity) || !is_array($entity))
		{
			$entity = static::getDefaultFieldSettings();
		}
		$htmlPieces = static::renderSettingsHtmlPieces($callbackFunctionName, $entity);
		$result = $htmlPieces['inputs'];
		$result .= $htmlPieces['button'];
		$result .= "<script>\n" . $htmlPieces['collectSettingsFunction'] . "\n</script>";
		$result .= '<!--__modifyOptionsPromt:'.Loc::getMessage('CRM_BP_FIELDTYPE_UF_CRM').'-->';

		return $result;
	}

	/**
	 * Return default settings.
	 *
	 * @return array|string[]
	 */
	public static function getDefaultFieldSettings(): array
	{
		return [
			'LEAD' => 'Y',
			'CONTACT' => 'Y',
			'COMPANY' => 'Y',
			'DEAL' => 'Y'
		];
	}

	/**
	 * Return html pieces to render settings.
	 *
	 * @param string $callbackFunctionName
	 * @param array $settings
	 * @return array
	 * @throws Main\InvalidOperationException
	 */
	public static function renderSettingsHtmlPieces(
		string $callbackFunctionName,
		array $settings = []
	): array
	{
		$idsPrefix = $settings['idsPrefix'] ?? 'WFSFormOptionsX';
		$buttonLabel = $settings['buttonLabel'] ?? 'OK';
		$settingsName = $settings['settingsName'] ?? 'ENTITY';
		$isAssociativeValues = $settings['isAssociativeValues'] ?? false;
		$collectSettingsFunctionName = $settings['collectSettingsFunctionName'] ?? $idsPrefix . 'CRM';
		$inputs = '';
		$collectSettingsFunction = "function " . $collectSettingsFunctionName . "()\n{\n\tvar a = {};";

		$entityTypeIds = [
			\CCrmOwnerType::Lead,
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
			\CCrmOwnerType::Deal,
		];
		if (InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
		{
			$entityTypeIds[] = \CCrmOwnerType::SmartInvoice;
		}
		$dynamicTypes = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadCategories' => false,
			'isLoadStages' => false,
		])->getTypes();

		foreach ($dynamicTypes as $type)
		{
			if ($type->getIsUseInUserfieldEnabled())
			{
				$entityTypeIds[] = $type->getEntityTypeId();
			}
		}

		foreach ($entityTypeIds as $entityTypeId)
		{
			$entityName = \CCrmOwnerType::ResolveName($entityTypeId);
			$idAttribute = $idsPrefix . \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId);
			$isChecked = (isset($settings[$entityName]) && $settings[$entityName] === 'Y');
			$name = $isAssociativeValues ? $settingsName . '[' . $entityName . ']' : $settingsName . '[]';
			$value = $isAssociativeValues ? 'Y' : $entityName;
			$inputs .= '<input type="checkbox" id="' . htmlspecialcharsbx($idAttribute) . '" name="' . $name . '" value="' . $value . '" '
				. ($isChecked ? 'checked="checked"': '') . '> '
				. htmlspecialcharsbx(\CCrmOwnerType::GetDescription($entityTypeId)) . '<br/>';
			$collectSettingsFunction .= "\n\ta[\"" . $entityName . "\"] = document.getElementById(\"" . $idAttribute . "\").checked ? \"Y\" : \"N\";";
		}

		$button = '<input type="button" onclick="'
			.htmlspecialcharsbx($callbackFunctionName).'(' . $collectSettingsFunctionName . '())" value="' . htmlspecialcharsbx($buttonLabel) . '" />';

		$collectSettingsFunction .= "\n\treturn a;\n}";

		return [
			'inputs' => $inputs,
			'button' => $button,
			'collectSettingsFunctionName' => $collectSettingsFunctionName,
			'collectSettingsFunction' => $collectSettingsFunction,
		];
	}

	/**
	 * @inheritdoc
	 */
	protected static function formatValuePrintable(FieldType $fieldType, $value)
	{
		$options = $fieldType->getOptions();
		$defaultTypeName = '';
		foreach($options as $typeName => $flag)
		{
			if($flag === 'Y')
			{
				$defaultTypeName = $typeName;
				break;
			}
		}

		if($defaultTypeName === '')
		{
			$defaultTypeName = 'LEAD';
		}

		return static::prepareCrmUserTypeValueView($value, $defaultTypeName);
	}

	private static function prepareCrmUserTypeValueView($value, $defaultTypeName = '')
	{
		$parts = explode('_', $value);

		$entityTypeId = null;
		$entityId = null;

		if (count($parts) > 1)
		{
			$entityTypeId = \CCrmOwnerType::ResolveID(
				\CCrmOwnerTypeAbbr::ResolveName($parts[0] . $parts[1])
				?: \CCrmOwnerTypeAbbr::ResolveName($parts[0])
			);
			$entityId = (int)end($parts);
		}
		elseif ($defaultTypeName !== '')
		{
			$entityTypeId = \CCrmOwnerType::ResolveID($defaultTypeName);
			$entityId = (int)$value;
		}

		return
			!is_null($entityTypeId) && !is_null($entityId)
				? \CCrmOwnerType::GetCaption($entityTypeId, $entityId, false)
				: $value
		;
	}

	public static function toSingleValue(FieldType $fieldType, $value)
	{
		if (is_array($value))
		{
			reset($value);
			$value = (string)current($value);
		}

		return $value;
	}
}
