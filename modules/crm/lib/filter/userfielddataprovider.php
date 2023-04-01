<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Category\ItemCategoryUserField;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UI\Filter\EntityHandler;
use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Filter\EntityUFDataProvider;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class UserFieldDataProvider extends EntityUFDataProvider
{
	/**
	 * Prepare field list.
	 * @return Field[]
	 */
	public function prepareFields(): array
	{
		$userFields = $this->getUserFields();

		$result = parent::prepareFields();
		if(!empty($result))
		{
			$settings = $this->getSettings();
			if(method_exists($settings, 'getCategoryId'))
			{
				$categoryId = $settings->getCategoryId() ?? 0;
				$entityTypeId = $settings->getEntityTypeID();
				$result = (new ItemCategoryUserField($entityTypeId))->filter($categoryId, $result);
			}
		}

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

	public function prepareFilterValue(array $rawFilterValue): array
	{
		global $USER_FIELD_MANAGER;

		$filterValue = parent::prepareFilterValue($rawFilterValue);

		$factory = Container::getInstance()->getFactory($this->getSettings()->getEntityTypeID());
		if (!$factory)
		{
			return $filterValue;
		}

		$USER_FIELD_MANAGER->AdminListAddFilter($factory->getUserFieldEntityId(), $filterValue);

		return $filterValue;
	}

	public function prepareListFilter(array &$filter, array $filterFields, array $requestFilter)
	{
		$userFields = $this->getUserFields();
		foreach($filterFields as $filterField)
		{
			$id = $filterField['id'];
			if (isset($userFields[$id]))
			{
				$isProcessed = false;
				if (isset($filterField['type']))
				{
					if ($filterField['type'] === 'number' || $filterField['type'] === 'date' || $filterField['type'] === 'datetime')
					{
						if (!empty($requestFilter[$id.'_from']))
						{
							$filter['>='.$id] = $requestFilter[$id.'_from'];
						}
						if (!empty($requestFilter[$id.'_to']))
						{
							$filter['<='.$id] = $requestFilter[$id.'_to'];
						}
						if ($filterField['type'] === 'number' && $requestFilter[$id] === false)
						{
							$filter[$id] = $requestFilter[$id];
						}
						elseif ($filterField['type'] === 'number' && $requestFilter['!'.$id] === false)
						{
							$filter['!'.$id] = $requestFilter['!'.$id];
						}
						$isProcessed = true;
					}
					if ($filterField['type'] === 'string' || $filterField['type'] === 'text')
					{
						if (isset($requestFilter[$id]) && $requestFilter[$id] === false)
						{
							$filter[$id] = $requestFilter[$id];
						}
						elseif (isset($requestFilter['!' . $id]) && $requestFilter['!' . $id] === false)
						{
							$filter['!' . $id] = $requestFilter['!' . $id];
						}
					}
				}
				if (!$isProcessed && isset($requestFilter[$id]))
				{
					$filter[$id] = $requestFilter[$id];
					if ($userFields[$id]['USER_TYPE_ID'] === 'crm')
					{
						EntityHandler::internalize($filterFields, $filter);
					}
				}
			}
		}
	}
}
