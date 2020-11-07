<?php

namespace Bitrix\Rpa\Filter\Item;

use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Filter\Field;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rpa\Components\Base;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Integration\Bizproc\TaskManager;
use Bitrix\Rpa\Model\Stage;

class Provider extends EntityDataProvider
{
	public const FIELD_STAGE_SEMANTIC         = 'STAGE_SEMANTIC';
	public const FIELD_STAGE_SEMANTIC_IN_WORK = 'STAGE_SEMANTIC_WORK';
	public const FIELD_STAGE_SEMANTIC_SUCCESS = 'STAGE_SEMANTIC_SUCCESS';
	public const FIELD_STAGE_SEMANTIC_FAIL    = 'STAGE_SEMANTIC_FAIL';

	protected $settings;

	public function __construct(Settings $itemSettings)
	{
		$this->settings = $itemSettings;
		Base::loadBaseLanguageMessages();
	}

	public function getSettings(): Settings
	{
		return $this->settings;
	}

	protected function getFieldName($fieldID): string
	{
		$result = null;

		if ($fieldID === 'STAGE_ID')
		{
			$result = Loc::getMessage('RPA_COMMON_STAGE');
		}
		elseif ($fieldID === static::FIELD_STAGE_SEMANTIC)
		{
			$result = Loc::getMessage('RPA_FILTER_STAGE_SEMANTIC_TITLE');
		}
		elseif ($fieldID === TaskManager::TASKS_FILTER_FIELD)
		{
			$result = Loc::getMessage('RPA_FILTER_HAS_TASKS_TITLE');
		}
		else
		{
			$result = Loc::getMessage('RPA_ITEM_'.$fieldID);
		}

		if (!is_string($result))
		{
			$result = $fieldID;
		}

		return $result;
	}

	protected function getByFields(): array
	{
		return [
			'CREATED_BY', 'MOVED_BY', 'UPDATED_BY',
		];
	}

	protected function getTimeFields(): array
	{
		return [
			'CREATED_TIME', 'MOVED_TIME', 'UPDATED_TIME',
		];
	}

	/**
	 * Prepare field list.
	 * @return Field[]
	 */
	public function prepareFields(): array
	{
		$result = [];

		$fields = [
			static::FIELD_STAGE_SEMANTIC => [
				'options' => [
					'type' => 'list',
					'partial' => true,
					'default' => true,
				],
			],
			'ID' => [
				'options' => [
					'type' => 'number',
				]
			],
			'STAGE_ID' => [
				'options' => [
					'type' => 'list',
					'partial' => true,
					'default' => true,
				],
			],
			//			'XML_ID' => [],
		];

		$byFieldOption = [
			'options' => [
				'default' => false,
				'type' => 'dest_selector',
				'partial' => true,
			],
		];
		foreach ($this->getByFields() as $fieldName)
		{
			$fields[$fieldName] = $byFieldOption;
		}
		foreach ($this->getTimeFields() as $fieldName)
		{
			$fields[$fieldName] = [
				'options' => [
					'type' => 'date',
					'data' => [
						'time' => true,
						'exclude' => [
							\Bitrix\Main\UI\Filter\DateType::TOMORROW,
							\Bitrix\Main\UI\Filter\DateType::NEXT_DAYS,
							\Bitrix\Main\UI\Filter\DateType::NEXT_WEEK,
							\Bitrix\Main\UI\Filter\DateType::NEXT_MONTH,
						],
					],
				],
			];
		}

		if (Driver::getInstance()->isAutomationEnabled())
		{
			$fields[TaskManager::TASKS_FILTER_FIELD] = [
				'options' => [
					'type' => 'list',
					'partial' => true,
					'default' => true,
				],
			];
		}

		foreach ($fields as $name => $field)
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

		if ($fieldID === 'STAGE_ID')
		{
			$result = [
				'params' => ['multiple' => 'Y'],
				'items' => $this->getStageItems(),
			];
		}
		elseif ($fieldID === TaskManager::TASKS_FILTER_FIELD)
		{
			$result = [
				'params' => ['multiple' => 'N'],
				'items' => [
					TaskManager::TASKS_FILTER_HAS_TASKS_VALUE => Loc::getMessage('RPA_COMMON_HAS'),
					TaskManager::TASKS_FILTER_NO_TASKS_VALUE => Loc::getMessage('RPA_COMMON_HAS_NO'),
				],
			];
		}
		elseif ($fieldID === static::FIELD_STAGE_SEMANTIC)
		{
			$result = [
				'params' => ['multiple' => 'Y',],
				'items' => [
					static::FIELD_STAGE_SEMANTIC_IN_WORK => Loc::getMessage('RPA_FILTER_STAGE_SEMANTIC_WORK_ITEM'),
					static::FIELD_STAGE_SEMANTIC_SUCCESS => Loc::getMessage('RPA_FILTER_STAGE_SEMANTIC_SUCCESS_ITEM'),
					static::FIELD_STAGE_SEMANTIC_FAIL => Loc::getMessage('RPA_FILTER_STAGE_SEMANTIC_FAIL_ITEM'),
				],
			];
		}
		elseif (in_array($fieldID, $this->getByFields()))
		{
			$result = [
				'params' => [
					'apiVersion' => 3,
					'context' => 'PRA_ITEM_'.$this->getSettings()->getType()->getId().'__FILTER_'.$fieldID,
					'multiple' => 'Y',
					'contextCode' => 'U',
					'useSearch' => 'Y',
					'departmentFlatEnable' => 'N',
					'enableAll' => 'N',
					'enableUsers' => 'Y',
					'enableSonetgroups' => 'N',
					'enableDepartments' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U',
				]
			];
		}

		return $result;
	}

	protected function getStageItems(): array
	{
		$result = [];

		$stages = $this->getSettings()->getType()->getStages();
		foreach ($stages as $stage)
		{
			$result[$stage->getId()] = $stage->getName();
		}

		return $result;
	}

	public function getGridColumns(): array
	{
		$columns = [
			[
				'id' => 'ID',
				'name' => 'ID',
				'default' => true,
				'sort' => 'ID',
			],
			[
				'id' => 'STAGE_ID',
				'name' => Loc::getMessage('RPA_COMMON_STAGE'),
				'default' => true,
				'sort' => 'STAGE_ID',
			],
			//			[
			//				'id' => 'XML_ID',
			//				'name' => 'XML_ID',
			//				'default' => false,
			//				'sort' => 'XML_ID',
			//			],
		];

		$names = array_merge($this->getByFields(), $this->getTimeFields());
		foreach ($names as $fieldName)
		{
			$columns[] = [
				'id' => $fieldName,
				'name' => $this->getFieldName($fieldName),
				'default' => ($fieldName === 'CREATED_BY'),
				'sort' => $fieldName,
			];
		}

		return $columns;
	}

	public function prepareListFilter(array &$filter, array $requestFilter)
	{
		if (isset($requestFilter['ID_from']) && $requestFilter['ID_from'] > 0)
		{
			$filter['>=ID'] = $requestFilter['ID_from'];
		}
		if (isset($requestFilter['ID_to']) && $requestFilter['ID_to'] > 0)
		{
			$filter['<=ID'] = $requestFilter['ID_to'];
		}
		if (isset($requestFilter['STAGE_ID']) && !empty($requestFilter['STAGE_ID']))
		{
			$filter['=STAGE_ID'] = $requestFilter['STAGE_ID'];
		}
		if (isset($requestFilter['FIND']) && !empty($requestFilter['FIND']))
		{
			$itemIndexEntity = Driver::getInstance()->getFactory()->getItemIndexEntity($this->getSettings()->getType());
			if ($itemIndexEntity && $itemIndexEntity->fullTextIndexEnabled('SEARCH_CONTENT'))
			{
				$itemIndexDataClass = Driver::getInstance()->getFactory()->getItemIndexDataClass($this->getSettings()->getType());
				$filter['*FULL_TEXT.SEARCH_CONTENT'] = $itemIndexDataClass::prepareFullTextQuery($requestFilter['FIND']);
			}
			elseif ($this->getSettings()->getType()->getUserFieldCollection()->getByName($this->getSettings()->getType()->getItemUfNameFieldName()))
			{
				$filter['=%'.$this->getSettings()->getType()->getItemUfNameFieldName()] = '%'.$requestFilter['FIND'].'%';
			}
		}
		//		if(isset($requestFilter['XML_ID']) && !empty($requestFilter['XML_ID']))
		//		{
		//			$filter['=XML_ID'] = $requestFilter['XML_ID'];
		//		}
		if (isset($requestFilter[TaskManager::TASKS_FILTER_FIELD]))
		{
			$this->processTasksFilter($requestFilter[TaskManager::TASKS_FILTER_FIELD], $filter);
		}
		if (isset($requestFilter[static::FIELD_STAGE_SEMANTIC]))
		{
			$semanticFilter = [];
			if (in_array(static::FIELD_STAGE_SEMANTIC_IN_WORK, $requestFilter[static::FIELD_STAGE_SEMANTIC], true))
			{
				$semanticFilter[] = [
					'STAGE.SEMANTIC' => ''
				];
			}
			if (in_array(static::FIELD_STAGE_SEMANTIC_SUCCESS, $requestFilter[static::FIELD_STAGE_SEMANTIC], true))
			{
				$semanticFilter[] = [
					'STAGE.SEMANTIC' => Stage::SEMANTIC_SUCCESS,
				];
			}
			if (in_array(static::FIELD_STAGE_SEMANTIC_FAIL, $requestFilter[static::FIELD_STAGE_SEMANTIC], true))
			{
				$semanticFilter[] = [
					'STAGE.SEMANTIC' => Stage::SEMANTIC_FAIL,
				];
			}
			if (!empty($semanticFilter))
			{
				$filter[] = array_merge([
					'LOGIC' => 'OR',
				], $semanticFilter);
			}
		}
		foreach ($this->getByFields() as $fieldName)
		{
			if (isset($requestFilter[$fieldName]) && !empty($requestFilter[$fieldName]))
			{
				$filter['='.$fieldName] = $requestFilter[$fieldName];
			}
		}
		foreach ($this->getTimeFields() as $fieldName)
		{
			if (!empty($requestFilter[$fieldName.'_from']))
			{
				$filter['>='.$fieldName] = $requestFilter[$fieldName.'_from'];
			}
			if (!empty($requestFilter[$fieldName.'_to']))
			{
				$filter['<='.$fieldName] = $requestFilter[$fieldName.'_to'];
			}
		}
	}

	public function processTasksFilter($tasksFilter, &$filter): void
	{
		if(!in_array(
			$tasksFilter,
			[TaskManager::TASKS_FILTER_NO_TASKS_VALUE, TaskManager::TASKS_FILTER_HAS_TASKS_VALUE],
			true)
		)
		{
			return;
		}
		$taskManager = Driver::getInstance()->getTaskManager();
		if ($taskManager)
		{
			$filterIds = [];
			$tasks = $taskManager->getUserIncompleteTasksForType($this->getSettings()->getType()->getId());
			if (!empty($tasks))
			{
				foreach ($tasks as $task)
				{
					$filterIds[] = $this->getItemIdFromTaskInfo($task);
				}
			}
			if ($tasksFilter === TaskManager::TASKS_FILTER_HAS_TASKS_VALUE)
			{
				if (!empty($filterIds))
				{
					$filter['@ID'] = $filterIds;
				}
				else
				{
					$filter['=ID'] = 0;
				}
			}
			elseif ($tasksFilter === TaskManager::TASKS_FILTER_NO_TASKS_VALUE)
			{
				if (!empty($filterIds))
				{
					$filter['!@ID'] = $filterIds;
				}
			}
		}
	}

	private function getItemIdFromTaskInfo(array $task): ?int
	{
		if (isset($task['PARAMETERS']['DOCUMENT_ID']) && is_array($task['PARAMETERS']['DOCUMENT_ID']))
		{
			[, , $documentId] = $task['PARAMETERS']['DOCUMENT_ID'];

			return \Bitrix\Rpa\Integration\Bizproc\Document\Item::getDocumentItemId($documentId);
		}

		return null;
	}
}