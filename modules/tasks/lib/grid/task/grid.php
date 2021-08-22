<?php
namespace Bitrix\Tasks\Grid\Task;

use Bitrix\Main\Grid\Column;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;

/**
 * Class Grid
 *
 * @package Bitrix\Tasks\Grid\Task
 */
class Grid extends \Bitrix\Tasks\Grid
{
	/**
	 * @return array[]
	 * @throws LoaderException
	 */
	public function prepareHeaders(): array
	{
		$headers = [
			'ID' => [
				'id' => 'ID',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_ID'),
				'sort' => 'ID',
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			],
			'TITLE' => [
				'id' => 'TITLE',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_TITLE'),
				'sort' => 'TITLE',
				'first_order' => 'desc',
				'editable' => false,
				'prevent_default' => false,
				'shift' => true,
				'default' => true,
			],
			'ACTIVITY_DATE' => [
				'id' => 'ACTIVITY_DATE',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_ACTIVITY_DATE'),
				'sort' => 'ACTIVITY_DATE',
				'first_order' => 'desc',
				'editable' => false,
				'default' => true,
				'width' => 250
			],
			'DEADLINE' => [
				'id' => 'DEADLINE',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_DEADLINE'),
				'sort' => 'DEADLINE',
				'first_order' => 'desc',
				'editable' => false,
				'default' => true,
				'type' => Column\Type::LABELS,
			],
			'ORIGINATOR_NAME' => [
				'id' => 'ORIGINATOR_NAME',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_ORIGINATOR_NAME'),
				'sort' => 'ORIGINATOR_NAME',
				'first_order' => 'desc',
				'editable' => false,
				'default' => true,
			],
			'RESPONSIBLE_NAME' => [
				'id' => 'RESPONSIBLE_NAME',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_RESPONSIBLE_NAME'),
				'sort' => 'RESPONSIBLE_NAME',
				'first_order' => 'desc',
				'editable' => false,
				'default' => true,
			],
			'REAL_STATUS' => [
				'id' => 'STATUS',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_STATUS'),
				'sort' => 'REAL_STATUS',
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			],
			'GROUP_NAME' => [
				'id' => 'GROUP_NAME',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_GROUP_NAME'),
				'sort' => false,
				'first_order' => 'desc',
				'editable' => false,
				'default' => true,
			],
			'CREATED_DATE' => [
				'id' => 'CREATED_DATE',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_CREATED_DATE'),
				'sort' => 'CREATED_DATE',
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			],
			'CHANGED_DATE' => [
				'id' => 'CHANGED_DATE',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_CHANGED_DATE'),
				'sort' => 'CHANGED_DATE',
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			],
			'CLOSED_DATE' => [
				'id' => 'CLOSED_DATE',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_CLOSED_DATE'),
				'sort' => 'CLOSED_DATE',
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			],
			'TIME_ESTIMATE' => [
				'id' => 'TIME_ESTIMATE',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_TIME_ESTIMATE'),
				'sort' => 'TIME_ESTIMATE',
				'first_order' => 'desc',
				'default' => false,
			],
			'ALLOW_TIME_TRACKING' => [
				'id' => 'ALLOW_TIME_TRACKING',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_ALLOW_TIME_TRACKING'),
				'sort' => 'ALLOW_TIME_TRACKING',
				'first_order' => 'desc',
				'default' => false,
			],
			'MARK' => [
				'id' => 'MARK',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_MARK'),
				'sort' => 'MARK',
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			],
			'ALLOW_CHANGE_DEADLINE' => [
				'id' => 'ALLOW_CHANGE_DEADLINE',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_ALLOW_CHANGE_DEADLINE'),
				'sort' => 'ALLOW_CHANGE_DEADLINE',
				'first_order' => 'desc',
				'default' => false,
			],
			'TIME_SPENT_IN_LOGS' => [
				'id' => 'TIME_SPENT_IN_LOGS',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_TIME_SPENT_IN_LOGS'),
				'sort' => 'TIME_SPENT_IN_LOGS',
				'first_order' => 'desc',
				'default' => false,
			],
			'FLAG_COMPLETE' => [
				'id' => 'FLAG_COMPLETE',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_FLAG_COMPLETE'),
				'sort' => false,
				'editable' => false,
				'default' => false,
			],
			'TAG' => [
				'id' => 'TAG',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_TAG'),
				'sort' => false,
				'editable' => false,
				'default' => true,
				'type' => Column\Type::TAGS,
			],
		];

		if (Loader::includeModule('crm'))
		{
			$headers['UF_CRM_TASK_LEAD'] = [
				'id' => 'UF_CRM_TASK_LEAD',
				'name' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Lead),
				'sort' => false,
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			];
			$headers['UF_CRM_TASK_CONTACT'] = [
				'id' => 'UF_CRM_TASK_CONTACT',
				'name' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Contact),
				'sort' => false,
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			];
			$headers['UF_CRM_TASK_COMPANY'] = [
				'id' => 'UF_CRM_TASK_COMPANY',
				'name' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Company),
				'sort' => false,
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			];
			$headers['UF_CRM_TASK_DEAL'] = [
				'id' => 'UF_CRM_TASK_DEAL',
				'name' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Deal),
				'sort' => false,
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			];
		}

		$parameters = $this->getParameters();

		foreach ($parameters['UF'] as $ufName => $ufItem)
		{
			$headers[$ufName] = [
				'id' => $ufName,
				'name' => $ufItem['EDIT_FORM_LABEL'],
				'sort' => false,
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			];
		}

		// if key 'default' is present, don't change it
		foreach ($parameters['COLUMNS'] as $columnId)
		{
			if (array_key_exists($columnId, $headers) && !array_key_exists('default', $headers[$columnId]))
			{
				$headers[$columnId]['default'] = true;
			}
		}

		if (
			array_key_exists('SORT', $parameters)
			&& array_key_exists(($key = key($parameters['SORT'])), $headers)
		)
		{
			$headers[$key]['color'] = Column\Color::BLUE;
		}

		return $headers;
	}

	/**
	 * @return array
	 */
	public function prepareRows(): array
	{
		$preparedRows = [];

		foreach ($this->getRows() as $key => $data)
		{
			$row = new Row($data, $this->getParameters());
			$preparedRows[$key] = [
				'actions' => $row->prepareActions(),
				'content' => $row->prepareContent(),
				'cellActions' => $row->prepareCellActions(),
				'counters' => $row->prepareCounters(),
			];
		}

		return $preparedRows;
	}

	public function prepareGroupActions(): array
	{
		return [];
	}
}