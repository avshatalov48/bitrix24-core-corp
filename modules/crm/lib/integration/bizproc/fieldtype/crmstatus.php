<?php
namespace Bitrix\Crm\Integration\BizProc\FieldType;

use Bitrix\Main,
	Bitrix\Bizproc\FieldType,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CrmStatus extends UserFieldBase
{
	/**
	 * @inheritdoc
	 */
	public static function renderControlOptions(FieldType $fieldType, $callbackFunctionName, $value)
	{
		$statusId = $fieldType->getOptions();
		$entityTypes = \CCrmStatus::GetEntityTypes();
		$default = 'STATUS';
		$result = '<select id="WFSFormOptionsX" onchange="'
			.Main\Text\HtmlFilter::encode($callbackFunctionName)
			.'(this.options[this.selectedIndex].value)">';

		foreach ($entityTypes as $entityType)
		{
			$result .= '<option value="'
				.Main\Text\HtmlFilter::encode($entityType['ID'])
				.'"'.(($entityType['ID'] == $statusId) ? ' selected="selected"' : '').'>'
				.Main\Text\HtmlFilter::encode($entityType['NAME']).'</option>';

			if ($entityType['ID'] == $statusId)
			{
				$default = $entityType['ID'];
			}
		}
		$result .= '</select><!--__defaultOptionsValue:'.Main\Text\HtmlFilter::encode($default)
			.'--><!--__modifyOptionsPromt:'.Loc::getMessage('CRM_BP_FIELDTYPE_UF_CRM_STATUS').'-->';

		$fieldType->setOptions($default);

		return $result;
	}
}