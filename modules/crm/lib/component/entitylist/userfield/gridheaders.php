<?php

namespace Bitrix\Crm\Component\EntityList\UserField;

use Bitrix\Main\Localization\Loc;

class GridHeaders
{
	private bool $withHtmlSpecialchars = true;
	private bool $forImport = false;
	private bool $withEnumFieldValues = false;

	public function __construct(private \CCrmUserType $userType)
	{
	}

	public function setWithHtmlSpecialchars(bool $withHtmlSpecialchars): GridHeaders
	{
		$this->withHtmlSpecialchars = $withHtmlSpecialchars;

		return $this;
	}

	public function setForImport(bool $forImport): GridHeaders
	{
		$this->forImport = $forImport;

		return $this;
	}

	public function setWithEnumFieldValues(bool $withEnumFieldValues): GridHeaders
	{
		$this->withEnumFieldValues = $withEnumFieldValues;

		return $this;
	}

	public function append(array &$headers, array $fieldNames = []): void
	{
		$userFields = $this->userType->GetAbstractFields();
		foreach ($userFields as $fieldName => $userField)
		{
			if (!empty($fieldNames) && !in_array($fieldName, $fieldNames, true))
			{
				continue;
			}

			$headerData = $this->getHeaderData($fieldName, $userField);
			if (!$headerData)
			{
				continue;
			}
			if ($this->withHtmlSpecialchars)
			{
				$headerData['name'] = htmlspecialcharsbx($headerData['name'] ?? '');
			}
			if ($this->forImport)
			{
				$headerData['mandatory'] = ($userField['MANDATORY'] === 'Y' ? 'Y' : 'N');
			}
			if ($this->withEnumFieldValues && $headerData['type'] === 'list')
			{
				$headerData['editable'] = $this->loadEnumValues($userField);
			}

			$headers[$fieldName] = $headerData;
		}
	}

	private function getHeaderData(string $fieldName, array $userField): ?array
	{
		//NOTE: SHOW_IN_LIST affect only default fields. All fields are allowed in list.
		//if(!isset($userField['SHOW_IN_LIST']) || $userField['SHOW_IN_LIST'] !== 'Y')
		//	continue;

		$editable = true;
		$sType = $userField['USER_TYPE']['BASE_TYPE'];
		if (
			$userField['EDIT_IN_LIST'] === 'N'
			|| $userField['MULTIPLE'] === 'Y'
			|| $userField['USER_TYPE']['BASE_TYPE'] === 'file'
			|| $userField['USER_TYPE']['USER_TYPE_ID'] === 'employee'
			|| $userField['USER_TYPE']['USER_TYPE_ID'] === 'crm'
			|| $userField['USER_TYPE']['USER_TYPE_ID'] === 'hlblock'
		)
		{
			$editable = false;
		}
		elseif (
			in_array($userField['USER_TYPE']['USER_TYPE_ID'], ['enumeration', 'iblock_section', 'iblock_element'])
		)
		{
			$sType = 'list';
		}
		elseif ($userField['USER_TYPE']['USER_TYPE_ID'] == 'boolean')
		{
			$sType = 'list';
		}
		elseif ($userField['USER_TYPE']['BASE_TYPE'] == 'datetime')
		{
			$sType = 'date';
		}
		elseif ($userField['USER_TYPE']['USER_TYPE_ID'] == 'crm_status')
		{
			$sType = 'list';
		}
		elseif (mb_substr($userField['USER_TYPE']['USER_TYPE_ID'], 0, 5) === 'rest_')
		{
			// skip REST type fields here
			return null;
		}

		if ($sType === 'string')
		{
			$sType = 'text';
		}
		elseif ($sType === 'int' || $sType === 'double')
		{
			//HACK: \CMainUIGrid::prepareEditable does not recognize 'number' type
			$sType = 'int';
		}

		$fieldLabel = $userField['LIST_COLUMN_LABEL']
			?? $userField['EDIT_FORM_LABEL']
			?? $userField['LIST_FILTER_LABEL'];

		return [
			'id' => $fieldName,
			'name' => htmlspecialcharsbx($fieldLabel),
			'sort' => $userField['MULTIPLE'] == 'N' ? $fieldName : false,
			'default' => $userField['SHOW_IN_LIST'] == 'Y',
			'editable' => $editable,
			'type' => $sType,
		];
	}

	private function loadEnumValues(mixed $userField): array|bool
	{
		$items = [
			'items' => ['' => ''],
		];

		if (in_array($userField['USER_TYPE']['USER_TYPE_ID'], ['enumeration', 'iblock_section', 'iblock_element']))
		{
			if (is_callable([$userField['USER_TYPE']['CLASS_NAME'], 'GetList']))
			{
				$rsEnum = call_user_func_array([$userField['USER_TYPE']['CLASS_NAME'], 'GetList'], [$userField]);
				if (is_object($rsEnum) && is_subclass_of($rsEnum, 'CAllDBResult'))
				{
					$maxEditableCount = (int)\Bitrix\Main\Config\Option::get('crm', '~enumeration_max_editable_inline_count', 1000);
					if ($rsEnum->SelectedRowsCount() <= $maxEditableCount)
					{
						while ($ar = $rsEnum->GetNext())
						{
							$items['items'][$ar['ID']] = htmlspecialcharsback($ar['VALUE']);
						}
					}
					else
					{
						$items = false;
					}
				}
			}
		}
		elseif ($userField['USER_TYPE']['USER_TYPE_ID'] == 'boolean')
		{
			//Default value must be placed at first position.
			$defaultValue = isset($userField['SETTINGS']['DEFAULT_VALUE'])
				? (int)$userField['SETTINGS']['DEFAULT_VALUE']
				: 0;
			if ($defaultValue === 1)
			{
				$items = [
					'items' => [
						'1' => Loc::getMessage('MAIN_YES'),
						'0' => Loc::getMessage('MAIN_NO')
					]
				];
			}
			else
			{
				$items = [
					'items' => [
						'0' => Loc::getMessage('MAIN_NO'),
						'1' => Loc::getMessage('MAIN_YES')
					]
				];
			}
		}
		elseif ($userField['USER_TYPE']['USER_TYPE_ID'] == 'crm_status')
		{
			$ar = \CCrmStatus::GetStatusList($userField['SETTINGS']['ENTITY_TYPE'] ?? null);
			$items = [
				'items' => ['' => ''] + $ar,
			];
		}

		return $items;
	}
}
