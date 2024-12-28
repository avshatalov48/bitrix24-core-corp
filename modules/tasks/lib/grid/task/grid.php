<?php

namespace Bitrix\Tasks\Grid\Task;

use Bitrix\Main\Grid\Column;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\Flow\FlowFeature;
use CCrmOwnerType;
use Bitrix\Tasks\Integration\Extranet\User;

/**
 * Class Grid
 *
 * @package Bitrix\Tasks\Grid\Task
 */
class Grid extends \Bitrix\Tasks\Grid\Grid
{
	public function prepareHeaders(): array
	{
		$this
			->fillWithDefaultHeaders()
			->fillWithCrmHeaders()
			->fillWithUfHeaders()
			->markDefaultHeaders()
			->colorize()
			->applyStrategies();

		return $this->headers;
	}

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

	private function fillWithDefaultHeaders(): static
	{
		$isExtranet = User::isExtranet((int)CurrentUser::get()->getId());
		$isCollaber = User::isCollaber((int)CurrentUser::get()->getId());
		$this->headers = [];

		$this->headers['ID'] = [
			'id' => 'ID',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_ID'),
			'sort' => 'ID',
			'first_order' => 'desc',
			'editable' => false,
			'default' => false,
		];

		$this->headers['TITLE'] = [
			'id' => 'TITLE',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_TITLE'),
			'sort' => 'TITLE',
			'first_order' => 'desc',
			'editable' => false,
			'prevent_default' => false,
			'shift' => true,
			'default' => true,
		];

		$this->headers['STAGE_ID'] = [
			'id' => 'STAGE_ID',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_STAGE_ID'),
			'sort' => false,
			'editable' => false,
			'default' => true,
			'type' => Column\Type::CUSTOM,
		];

		$this->headers['ACTIVITY_DATE'] = [
			'id' => 'ACTIVITY_DATE',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_ACTIVITY_DATE'),
			'sort' => 'ACTIVITY_DATE',
			'first_order' => 'desc',
			'editable' => false,
			'default' => true,
			'width' => 250,
		];

		$this->headers['DEADLINE'] = [
			'id' => 'DEADLINE',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_DEADLINE'),
			'sort' => 'DEADLINE',
			'first_order' => 'desc',
			'editable' => false,
			'default' => true,
			'type' => Column\Type::LABELS,
		];

		$this->headers['ORIGINATOR_NAME'] = [
			'id' => 'ORIGINATOR_NAME',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_ORIGINATOR_NAME'),
			'sort' => 'ORIGINATOR_NAME',
			'first_order' => 'desc',
			'editable' => false,
			'default' => true,
		];

		$this->headers['RESPONSIBLE_NAME'] = [
			'id' => 'RESPONSIBLE_NAME',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_ASSIGNEE_NAME'),
			'sort' => 'RESPONSIBLE_NAME',
			'first_order' => 'desc',
			'editable' => false,
			'default' => true,
		];

		$this->headers['REAL_STATUS'] = [
			'id' => 'STATUS',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_STATUS'),
			'sort' => 'REAL_STATUS',
			'first_order' => 'desc',
			'editable' => false,
			'default' => false,
		];

		$groupName = $isCollaber ? Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_COLLAB_NAME') : Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_GROUP_NAME');

		$this->headers['GROUP_NAME'] = [
			'id' => 'GROUP_NAME',
			'name' => $groupName,
			'sort' => false,
			'first_order' => 'desc',
			'editable' => false,
			'default' => true,
		];

		if (FlowFeature::isOn() && !$isExtranet)
		{
			$this->headers['FLOW'] = [
				'id' => 'FLOW',
				'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_FLOW'),
				'sort' => false,
				'editable' => false,
				'default' => false,
				'type' => Column\Type::CUSTOM,
			];
		}

		$this->headers['CREATED_DATE'] = [
			'id' => 'CREATED_DATE',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_CREATED_DATE'),
			'sort' => 'CREATED_DATE',
			'first_order' => 'desc',
			'editable' => false,
			'default' => false,
		];

		$this->headers['CHANGED_DATE'] = [
			'id' => 'CHANGED_DATE',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_CHANGED_DATE'),
			'sort' => 'CHANGED_DATE',
			'first_order' => 'desc',
			'editable' => false,
			'default' => false,
		];

		$this->headers['CLOSED_DATE'] = [
			'id' => 'CLOSED_DATE',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_CLOSED_DATE'),
			'sort' => 'CLOSED_DATE',
			'first_order' => 'desc',
			'editable' => false,
			'default' => false,
		];

		$this->headers['TIME_ESTIMATE'] = [
			'id' => 'TIME_ESTIMATE',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_TIME_ESTIMATE'),
			'sort' => 'TIME_ESTIMATE',
			'first_order' => 'desc',
			'default' => false,
		];

		$this->headers['ALLOW_TIME_TRACKING'] = [
			'id' => 'ALLOW_TIME_TRACKING',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_ALLOW_TIME_TRACKING'),
			'sort' => 'ALLOW_TIME_TRACKING',
			'first_order' => 'desc',
			'default' => false,
		];

		$this->headers['MARK'] = [
			'id' => 'MARK',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_MARK'),
			'sort' => 'MARK',
			'first_order' => 'desc',
			'editable' => false,
			'default' => false,
		];

		$this->headers['ALLOW_CHANGE_DEADLINE'] = [
			'id' => 'ALLOW_CHANGE_DEADLINE',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_ALLOW_CHANGE_DEADLINE'),
			'sort' => 'ALLOW_CHANGE_DEADLINE',
			'first_order' => 'desc',
			'default' => false,
		];

		$this->headers['TIME_SPENT_IN_LOGS'] = [
			'id' => 'TIME_SPENT_IN_LOGS',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_TIME_SPENT_IN_LOGS'),
			'sort' => 'TIME_SPENT_IN_LOGS',
			'first_order' => 'desc',
			'default' => false,
		];

		$this->headers['FLAG_COMPLETE'] = [
			'id' => 'FLAG_COMPLETE',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_FLAG_COMPLETE'),
			'sort' => false,
			'editable' => false,
			'default' => false,
		];

		$this->headers['TAG'] = [
			'id' => 'TAG',
			'name' => Loc::getMessage('TASKS_GRID_TASK_GRID_HEADER_TAG'),
			'sort' => false,
			'editable' => false,
			'default' => true,
			'type' => Column\Type::TAGS,
		];

		return $this;
	}

	private function fillWithCrmHeaders(): static
	{
		if (Loader::includeModule('crm'))
		{
			$this->headers['UF_CRM_TASK_LEAD'] = [
				'id' => 'UF_CRM_TASK_LEAD',
				'name' => CCrmOwnerType::GetDescription(CCrmOwnerType::Lead),
				'sort' => false,
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			];
			$this->headers['UF_CRM_TASK_CONTACT'] = [
				'id' => 'UF_CRM_TASK_CONTACT',
				'name' => CCrmOwnerType::GetDescription(CCrmOwnerType::Contact),
				'sort' => false,
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			];
			$this->headers['UF_CRM_TASK_COMPANY'] = [
				'id' => 'UF_CRM_TASK_COMPANY',
				'name' => CCrmOwnerType::GetDescription(CCrmOwnerType::Company),
				'sort' => false,
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			];
			$this->headers['UF_CRM_TASK_DEAL'] = [
				'id' => 'UF_CRM_TASK_DEAL',
				'name' => CCrmOwnerType::GetDescription(CCrmOwnerType::Deal),
				'sort' => false,
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			];
		}

		return $this;
	}

	private function fillWithUfHeaders(): static
	{
		$parameters = $this->getParameters();

		foreach ($parameters['UF'] as $ufName => $ufItem)
		{
			$this->headers[$ufName] = [
				'id' => $ufName,
				'name' => $ufItem['EDIT_FORM_LABEL'],
				'sort' => false,
				'first_order' => 'desc',
				'editable' => false,
				'default' => false,
			];
		}

		return $this;
	}

	private function markDefaultHeaders(): static
	{
		$parameters = $this->getParameters();

		// if key 'default' is present, don't change it
		foreach ($parameters['COLUMNS'] as $columnId)
		{
			if (array_key_exists($columnId, $this->headers) && !array_key_exists('default', $this->headers[$columnId]))
			{
				$this->headers[$columnId]['default'] = true;
			}
		}

		return $this;
	}

	private function colorize(): static
	{
		$parameters = $this->getParameters();

		if (
			array_key_exists('SORT', $parameters)
			&& array_key_exists(($key = key($parameters['SORT'])), $this->headers)
		)
		{
			$this->headers[$key]['color'] = Column\Color::BLUE;
		}

		return $this;
	}
}
