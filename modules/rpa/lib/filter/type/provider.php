<?php

namespace Bitrix\Rpa\Filter\Type;

use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Filter\Field;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rpa\Components\Base;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Integration\Bizproc\TaskManager;

class Provider extends EntityDataProvider
{
	protected $settings;

	public function __construct(Settings $settings)
	{
		$this->settings = $settings;
		Base::loadBaseLanguageMessages();
	}

	public function getSettings(): Settings
	{
		return $this->settings;
	}

	protected function getFieldName($fieldID): string
	{
		$result = null;

		if($fieldID === TaskManager::TASKS_FILTER_FIELD)
		{
			$result = Loc::getMessage('RPA_FILTER_HAS_TASKS_TITLE');
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
		];

		if(Driver::getInstance()->isAutomationEnabled())
		{
			$fields[TaskManager::TASKS_FILTER_FIELD] = [
				'options' => [
					'type' => 'list',
					'partial' => true,
					'default' => true,
				],
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
					'context' => 'PRA_TYPE_FILTER_CREATED_BY',
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
		elseif($fieldID === TaskManager::TASKS_FILTER_FIELD)
		{
			$result = [
				'params' => ['multiple' => 'N'],
				'items' => [
					TaskManager::TASKS_FILTER_HAS_TASKS_VALUE => Loc::getMessage('RPA_COMMON_HAS'),
					TaskManager::TASKS_FILTER_NO_TASKS_VALUE => Loc::getMessage('RPA_COMMON_HAS_NO'),
				],
			];
		}

		return $result;
	}

	public function getGridColumns(): array
	{
		return [
			[
				'id' => 'ID',
				'name' => 'ID',
				'default' => true,
				'sort' => 'ID',
			],
			[
				'id' => 'TITLE',
				'name' => $this->getFieldName('TITLE'),
				'default' => true,
				'sort' => 'TITLE',
			],
			[
				'id' => 'CREATED_BY',
				'name' => $this->getFieldName('CREATED_BY'),
				'default' => true,
				'sort' => 'CREATED_BY',
			],
		];
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
			$userId = (int)mb_substr($requestFilter['CREATED_BY'], 1);
			if($userId > 0)
			{
				$filter['=CREATED_BY'] = $userId;
			}
		}
		if(isset($requestFilter[TaskManager::TASKS_FILTER_FIELD]) && in_array($requestFilter[TaskManager::TASKS_FILTER_FIELD], [TaskManager::TASKS_FILTER_NO_TASKS_VALUE, TaskManager::TASKS_FILTER_HAS_TASKS_VALUE]))
		{
			$taskManager = Driver::getInstance()->getTaskManager();
			if($taskManager)
			{
				$filterIds = array_keys($taskManager->getUserIncompleteTasksByType());
				if($requestFilter[TaskManager::TASKS_FILTER_FIELD] === TaskManager::TASKS_FILTER_HAS_TASKS_VALUE)
				{
					if(!empty($filterIds))
					{
						$filter['@ID'] = $filterIds;
					}
					else
					{
						$filter['=ID'] = 0;
					}
				}
				elseif($requestFilter[TaskManager::TASKS_FILTER_FIELD] === TaskManager::TASKS_FILTER_NO_TASKS_VALUE)
				{
					if(!empty($filterIds))
					{
						$filter['!@ID'] = $filterIds;
					}
				}
			}
		}
	}
}