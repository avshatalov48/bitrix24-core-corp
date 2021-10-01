<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class UserFieldDataProvider extends \Bitrix\Main\Filter\EntityUFDataProvider
{
	/**
	 * Prepare field list.
	 * @return Field[]
	 */
	public function prepareFields(): array
	{
		$userFields = $this->getUserFields();
		$result = parent::prepareFields();
		foreach($result as $fieldName => $field)
		{
			if($userFields[$fieldName]['USER_TYPE_ID'] === 'resourcebooking')
			{
				unset($result[$fieldName]);
			}
		}

		return $result;
	}

	/**
	 * @param string $fieldID
	 * @return array[]|null
	 */
	public function prepareFieldData($fieldID): ?array
	{
		$userFields = $this->getUserFields();
		if(!isset($userFields[$fieldID]))
		{
			return null;
		}

		$userField = $userFields[$fieldID];

		if($userField['USER_TYPE']['USER_TYPE_ID'] === 'crm')
		{
			$settings = (
				isset($userField['SETTINGS']) && is_array($userField['SETTINGS'])
				? $userField['SETTINGS']
				: []
			);
			$isMultiple = (isset($userField['MULTIPLE']) && $userField['MULTIPLE'] === 'Y');

			return [
				'params' => ElementType::getDestSelectorParametersForFilter($settings, $isMultiple),
			];
		}

		return parent::prepareFieldData($fieldID);
	}
}
