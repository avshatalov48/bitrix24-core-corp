<?php
namespace Bitrix\Crm\Integration\BizProc\FieldType;

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

		if (empty($entity))
		{
			$entity = array('LEAD' => 'Y', 'CONTACT' => 'Y', 'COMPANY' => 'Y', 'DEAL' => 'Y');
		}
		$result = '<input type="checkbox" id="WFSFormOptionsXL" name="ENITTY[]" value="LEAD" '
			.($entity['LEAD'] == 'Y'? 'checked="checked"': '').'> '
			.\CCrmOwnerType::GetDescription(\CCrmOwnerType::Lead).' <br/>';
		$result .= '<input type="checkbox" id="WFSFormOptionsXC"  name="ENITTY[]" value="CONTACT" '
			.($entity['CONTACT'] == 'Y'? 'checked="checked"': '').'> '
			.\CCrmOwnerType::GetDescription(\CCrmOwnerType::Contact).'<br/>';
		$result .= '<input type="checkbox" id="WFSFormOptionsXCO" name="ENITTY[]" value="COMPANY" '
			.($entity['COMPANY'] == 'Y'? 'checked="checked"': '').'> '
			.\CCrmOwnerType::GetDescription(\CCrmOwnerType::Company).'<br/>';
		$result .= '<input type="checkbox" id="WFSFormOptionsXD"  name="ENITTY[]" value="DEAL" '
			.($entity['DEAL'] == 'Y'? 'checked="checked"': '').'> '
			.\CCrmOwnerType::GetDescription(\CCrmOwnerType::Deal).'<br/>';
		$result .= '<input type="button" onclick="'
			.Main\Text\HtmlFilter::encode($callbackFunctionName).'(WFSFormOptionsXCRM())" value="OK" />';
		$result .= '<script>
					function WFSFormOptionsXCRM()
					{
						var a = {};
						a["LEAD"] = BX("WFSFormOptionsXL").checked ? "Y" : "N";
						a["CONTACT"] = BX("WFSFormOptionsXC").checked ? "Y" : "N";
						a["COMPANY"] = BX("WFSFormOptionsXCO").checked ? "Y" : "N";
						a["DEAL"] = BX("WFSFormOptionsXD").checked ? "Y" : "N";
						return a;
					}
				</script>';
		$result .= '<!--__modifyOptionsPromt:'.Loc::getMessage('CRM_BP_FIELDTYPE_UF_CRM').'-->';

		return $result;
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
}