<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Integration;
use Bitrix\Crm\Integration\Intranet\CustomSection\CustomSectionQueries;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Filter\Field;
use Bitrix\Main\Localization\Loc;

class TypeDataProvider extends EntityDataProvider
{
	protected TypeSettings $settings;

	private CustomSectionQueries $customSectionQueries;

	private bool $isCustomSectionsAvailable;

	public function __construct(TypeSettings $settings)
	{
		$this->settings = $settings;
		Container::getInstance()->getLocalization()->loadMessages();

		$this->customSectionQueries = CustomSectionQueries::getInstance();

		$this->isCustomSectionsAvailable = Integration\IntranetManager::isCustomSectionsAvailable();
	}

	public function getSettings(): TypeSettings
	{
		return $this->settings;
	}

	protected function getFieldName($fieldID): string
	{
		$result = null;

		$name = Loc::getMessage("CRM_TYPE_FILTER_$fieldID");
		if (!empty($name))
		{
			return $name;
		}

		$entity = $this->settings->getEntity();
		if($entity->hasField($fieldID))
		{
			$result = $entity->getField($fieldID)->getTitle();
		}

		if(!is_string($result))
		{
			$result = $fieldID;
		}

		return $result;
	}

	/**
	 * Prepare field list.
	 * @return Field[]
	 */
	public function prepareFields(): array
	{
		$result = [];

		$fields = [
			'ID' => [
				'options' => [
					'type' => 'number',
					'default' => false,
				]
			],
			'ENTITY_TYPE_ID' => [
				'options' => [
					'default' => true,
					'type' => 'number',
				]
			],
			'TITLE' => [
				'options' => [
					'default' => true,
				],
			],
			'CREATED_BY' => [
				'options' => [
					'default' => false,
					'type' => 'dest_selector',
					'partial' => true,
				]
			],
		];

		if ($this->isCustomSectionsAvailable)
		{
			$fields['CUSTOM_SECTION'] = [
				'options' => [
					'default' => true,
					'partial' => true,
					'type' => 'list',
				]
			];
		}

		foreach($fields as $name => $field)
		{
			$result[$name] = $this->createField($name, (!empty($field['options']) ? $field['options'] : []));
		}

		return $result;
	}

	/**
	 * Prepare complete field data for specified field.
	 * @param string $fieldID Field ID.
	 * @return array|null
	 */
	public function prepareFieldData($fieldID): ?array
	{
		$result = null;

		if ($fieldID === 'CREATED_BY')
		{
			$result = [
				'params' => [
					'apiVersion' => 3,
					'context' => 'CRM_TYPE_FILTER_CREATED_BY',
					'multiple' => 'N',
					'contextCode' => 'U',
					'enableDepartments' => 'N',
					'departmentFlatEnable' => 'N',
					'enableAll' => 'N',
					'enableUsers' => 'Y',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'N',
					'isNumeric' => 'N',
				]
			];
		}
		elseif ($fieldID === 'CUSTOM_SECTION')
		{
			$sections = $this->customSectionQueries->findAllRelatedByCrmType();

			$items = [];
			// This column for smart process without bindings to custom section must call by specific name
			$items['-1'] = Loc::getMessage('CRM_TYPE_LIST_CUSTOM_SECTION_DEFAULT_VALUE');

			foreach ($sections as $row)
			{
				$title = $row['SECTION_TITLE'];
				$id = $row['CUSTOM_SECTION_ID'];
				$items[$id] = $title;
			}

			$result = [
				'items' => $items,
				'params' => [
					'multiple' => 'N',
				],
			];
		}

		return $result;
	}

	public function getGridColumns(): array
	{
		$result = [
			[
				'id' => 'ID',
				'name' => 'ID',
				'default' => false,
				'sort' => 'ID',
			],
			[
				'id' => 'ENTITY_TYPE_ID',
				'name' => $this->getFieldName('ENTITY_TYPE_ID'),
				'default' => true,
				'sort' => 'ENTITY_TYPE_ID',
				'width' => 200
			],
		];

		if ($this->isCustomSectionsAvailable)
		{
			$result[] = [
				'id' => 'CUSTOM_SECTION',
				'name' => $this->getFieldName('CUSTOM_SECTION'),
				'default' => true,
				'sort' => false,
			];
		}

		$result[] = [
			'id' => 'TITLE',
			'name' => $this->getFieldName('TITLE'),
			'default' => true,
			'sort' => 'TITLE',
		];

		$result[] = [
			'id' => 'CREATED_BY',
			'name' => $this->getFieldName('CREATED_BY'),
			'default' => false,
			'sort' => 'CREATED_BY',
		];

		return $result;
	}

	public function prepareListFilter(array &$filter, array $requestFilter)
	{
		if(isset($requestFilter['ID_from']) && $requestFilter['ID_from'] > 0)
		{
			$filter['>=ID'] = (int) $requestFilter['ID_from'];
		}
		if(isset($requestFilter['ID_to']) && $requestFilter['ID_to'] > 0)
		{
			$filter['<=ID'] = (int) $requestFilter['ID_to'];
		}
		if(isset($requestFilter['ENTITY_TYPE_ID_from']) && $requestFilter['ENTITY_TYPE_ID_from'] > 0)
		{
			$filter['>=ENTITY_TYPE_ID'] = (int) $requestFilter['ENTITY_TYPE_ID_from'];
		}
		if(isset($requestFilter['ENTITY_TYPE_ID_to']) && $requestFilter['ENTITY_TYPE_ID_to'] > 0)
		{
			$filter['<=ENTITY_TYPE_ID'] = (int) $requestFilter['ENTITY_TYPE_ID_to'];
		}
		$titleSearch = null;
		if(isset($requestFilter['TITLE']) && !empty($requestFilter['TITLE']))
		{
			$titleSearch = $requestFilter['TITLE'];
		}
		elseif(isset($requestFilter['FIND']) && !empty($requestFilter['FIND']))
		{
			$titleSearch = $requestFilter['FIND'];
		}
		if($titleSearch)
		{
			$filter['TITLE'] = '%'.$titleSearch.'%';
		}
		if(isset($requestFilter['CREATED_BY']) && !empty($requestFilter['CREATED_BY']))
		{
			$userId = (int)substr($requestFilter['CREATED_BY'], 1);
			if($userId > 0)
			{
				$filter['=CREATED_BY'] = $userId;
			}
		}

		$this->appendCustomSectionFilter($filter, $requestFilter);
	}

	private function appendCustomSectionFilter(array &$filter, array $requestFilter): void
	{
		if (!$this->isCustomSectionsAvailable)
		{
			return;
		}

		if (!isset($requestFilter['CUSTOM_SECTION']))
		{
			return;
		}

		$customSectionId = (int)$requestFilter['CUSTOM_SECTION'];

		if ($customSectionId > 0)
		{
			$foundSections = $this->customSectionQueries->findSettingsById($customSectionId);
		}
		else
		{
			$foundSections = $this->customSectionQueries->findAllRelatedByCrmType();
		}

		$typeIds = [];
		foreach ($foundSections as $row)
		{
			$typeId = Integration\IntranetManager::getEntityTypeIdByPageSettings($row['SETTINGS']);

			if ($typeId !== null)
			{
				$typeIds[] = $typeId;
			}
		}

		if (empty($typeIds))
		{
			return;
		}

		if ($customSectionId > 0)
		{
			$filter['@ENTITY_TYPE_ID'] = $typeIds;
		}
		else
		{
			$filter[]['!@ENTITY_TYPE_ID'] = $typeIds;
		}
	}
}