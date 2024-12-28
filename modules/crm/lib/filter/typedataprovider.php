<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\AutomatedSolution\AutomatedSolutionManager;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Filter\Field;
use Bitrix\Main\Localization\Loc;

class TypeDataProvider extends EntityDataProvider
{
	protected TypeSettings $settings;

	private AutomatedSolutionManager $automatedSolutionManager;

	private bool $isCustomSectionsAvailable;

	public function __construct(TypeSettings $settings)
	{
		$this->settings = $settings;
		Container::getInstance()->getLocalization()->loadMessages();

		$this->automatedSolutionManager = Container::getInstance()->getAutomatedSolutionManager();

		$this->isCustomSectionsAvailable = Integration\IntranetManager::isCustomSectionsAvailable();
		if (!$this->isCustomSectionsAvailable)
		{
			$this->settings->setIsExternalDynamicalTypes(false);
		}
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
					'default' => true,
				]
			],
			'TITLE' => [
				'options' => [
					'default' => true,
				],
			],
			'CREATED_BY' => [
				'options' => [
					'default' => true,
					'type' => 'dest_selector',
					'partial' => true,
				]
			],
			'UPDATED_BY' => [
				'options' => [
					'default' => true,
					'type' => 'dest_selector',
					'partial' => true,
				],
			],
			'CREATED_TIME' => [
				'options' => [
					'default' => true,
					'type' => 'date',
				]
			],

			'UPDATED_TIME' => [
				'options' => [
					'default' => true,
					'type' => 'date',
				]
			],
		];

		if ($this->isCustomSectionsAvailable && $this->settings->getIsExternalDynamicalTypes())
		{
			$fields['AUTOMATED_SOLUTION'] = [
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

		if ($fieldID === 'CREATED_BY' || $fieldID === 'UPDATED_BY')
		{
			$result = [
				'params' => [
					'apiVersion' => 3,
					'context' => "CRM_TYPE_FILTER_{$fieldID}",
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
		elseif ($fieldID === 'AUTOMATED_SOLUTION')
		{
			$solutions = $this->automatedSolutionManager->getAutomatedSolutionsFilteredByPermissions();

			$items = [];
			foreach ($solutions as $solution)
			{
				$items[$solution['ID']] = $solution['TITLE'];
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
		$result = [];

		$result[] = [
			'id' => 'ID',
			'name' => 'ID',
			'default' => true,
			'sort' => 'ID',
		];

		$result[] = [
			'id' => 'ENTITY_TYPE_ID',
			'name' => $this->getFieldName('ENTITY_TYPE_ID'),
			'default' => false,
			'sort' => 'ENTITY_TYPE_ID',
			'width' => 200
		];

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

		$result[] = [
			'id' => 'CREATED_TIME',
			'name' => $this->getFieldName('CREATED_TIME'),
			'default' => false,
			'sort' => 'CREATED_TIME',
		];

		$result[] = [
			'id' => 'UPDATED_BY',
			'name' => $this->getFieldName('UPDATED_BY'),
			'default' => true,
			'sort' => 'UPDATED_BY',
		];

		$result[] = [
			'id' => 'UPDATED_TIME',
			'name' => $this->getFieldName('UPDATED_TIME'),
			'default' => true,
			'sort' => 'UPDATED_TIME',
		];

		$result[] = [
			'id' => 'LAST_ACTIVITY_TIME',
			'name' => $this->getFieldName('LAST_ACTIVITY_TIME'),
			'default' => true,
			'sort' => false,
		];

		if ($this->isCustomSectionsAvailable && $this->settings->getIsExternalDynamicalTypes())
		{
			$result[] = [
				'id' => 'AUTOMATED_SOLUTION',
				'name' => $this->getFieldName('AUTOMATED_SOLUTION'),
				'default' => true,
				'sort' => false,
			];
		}

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
		if(isset($requestFilter['TITLE']) && !empty(trim($requestFilter['TITLE'])))
		{
			$titleSearch = trim($requestFilter['TITLE']);
		}
		elseif(isset($requestFilter['FIND']) && !empty(trim($requestFilter['FIND'])))
		{
			$titleSearch = trim($requestFilter['FIND']);
		}
		if($titleSearch)
		{
			$filter['TITLE'] = '%'.$titleSearch.'%';
		}

		if(!empty($requestFilter['CREATED_BY']))
		{
			$userId = (int)substr($requestFilter['CREATED_BY'], 1);
			if($userId > 0)
			{
				$filter['=CREATED_BY'] = $userId;
			}
		}

		if(!empty($requestFilter['UPDATED_BY']))
		{
			$userId = (int)substr($requestFilter['UPDATED_BY'], 1);
			if($userId > 0)
			{
				$filter['=UPDATED_BY'] = $userId;
			}
		}

		if (isset($requestFilter['CREATED_TIME_from']) && !empty(trim($requestFilter['CREATED_TIME_from'])))
		{
			$filter['>=CREATED_TIME'] = $requestFilter['CREATED_TIME_from'];
		}
		if (isset($requestFilter['CREATED_TIME_to']) && !empty(trim($requestFilter['CREATED_TIME_to'])))
		{
			$filter['<=CREATED_TIME'] = $requestFilter['CREATED_TIME_to'];
		}

		if (isset($requestFilter['UPDATED_TIME_from']) && !empty(trim($requestFilter['UPDATED_TIME_from'])))
		{
			$filter['>=UPDATED_TIME'] = $requestFilter['UPDATED_TIME_from'];
		}
		if (isset($requestFilter['UPDATED_TIME_to']) && !empty(trim($requestFilter['UPDATED_TIME_to'])))
		{
			$filter['<=UPDATED_TIME'] = $requestFilter['UPDATED_TIME_to'];
		}

		$this->appendAutomatedSolutionFilter($filter, $requestFilter);
	}

	protected function appendAutomatedSolutionFilter(array &$filter, array $requestFilter): void
	{
		$automatedSolutionId = (int)($requestFilter['AUTOMATED_SOLUTION'] ?? null);
		if ($automatedSolutionId > 0)
		{
			$filter['=CUSTOM_SECTION_ID'] = $automatedSolutionId;
		}
		else
		{
			$this->appendAutomatedSolutionDefaultFilter($filter);
		}
	}

	protected function appendAutomatedSolutionDefaultFilter(array &$filter): void
	{
		if ($this->settings->getIsExternalDynamicalTypes())
		{
			// grid in automation should not show CRM types
			$filter['!=CUSTOM_SECTION_ID'] = null;
		}
		else
		{
			// grid in crm should not show automation types
			$filter['=CUSTOM_SECTION_ID'] = null;
		}
	}
}
