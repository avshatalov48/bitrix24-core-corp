<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Component\BaseUfComponent;
use Bitrix\Crm\UserField\Types\StatusType;

/**
 * Class StatusUfComponent
 */
class StatusUfComponent extends BaseUfComponent
{
	protected static function getUserTypeId(): string
	{
		return StatusType::USER_TYPE_ID;
	}

	/**
	 * @param string $fieldName
	 * @param string|null $value
	 * @return string
	 */
	public function selectBoxFromArray(string $fieldName, ?string $value): string
	{
		$statuses = CCrmStatus::GetStatusList($this->arResult['userField']['SETTINGS']['ENTITY_TYPE']);

		$elements = (
		$this->arResult['userField']['MANDATORY'] === 'N'
			?
			['reference' => [''], 'reference_id' => ['']]
			:
			[]
		);

		foreach($statuses as $id => $name)
		{
			$elements['reference'][] = $name;
			$elements['reference_id'][] = $id;
		}

		return SelectBoxFromArray($fieldName, $elements, $value);
	}
}