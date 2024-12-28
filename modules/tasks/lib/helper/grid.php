<?php

namespace Bitrix\Tasks\Helper;

use Bitrix\Main\Grid as MainGrid;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Grid\Scope\Scope;

class Grid extends Common
{
	protected static ?array $instance = null;

	public function getVisibleColumns(bool $isExport = false): array
	{
		$columns = $this->getOptions()->GetVisibleColumns();

		if (empty($columns))
		{
			$columns = $this->getDefaultVisibleColumns($isExport);
		}

		return $columns;
	}

	public function getOptions(): ?MainGrid\Options
	{
		static $instance = null;

		if (!$instance)
		{
			return new MainGrid\Options($this->getId());
		}

		return $instance;
	}

	public function setScope(string $scope): static
	{
		if (in_array($scope, Scope::getAll(true), true))
		{
			$this->scope = $scope;
		}

		return $this;
	}

	public function getAllColumns(): array
	{
		$columns = [
			'ID',
			'TITLE',
			'DESCRIPTION',
			'ACTIVITY_DATE',
			'DEADLINE',
			'ORIGINATOR_NAME',
			'RESPONSIBLE_NAME',
			'A', //ACCOMPLICE
			'U', //AUDITOR
			'STATUS',
			'GROUP_NAME',
			'CREATED_DATE',
			'DATE_START',
			'CHANGED_DATE',
			'CLOSED_DATE',
			'TIME_ESTIMATE',
			'ALLOW_TIME_TRACKING',
			'MARK',
			'ALLOW_CHANGE_DEADLINE',
			'TIME_SPENT_IN_LOGS',
			'FLAG_COMPLETE',
			'TAG',
			'STAGE_ID',
			'UF_CRM_TASK_LEAD',
			'UF_CRM_TASK_CONTACT',
			'UF_CRM_TASK_COMPANY',
			'UF_CRM_TASK_DEAL',
			'UF_CRM_TASK',

			'PARENT_ID',
			'PARENT_TITLE',
		];

		if (FlowFeature::isOn())
		{
			$columns[] = 'FLOW';
		}

		return $columns;
	}

	protected function resolveChangedScope(): void
	{
		unset(static::$instance[$this->id]);
		$this->id = static::getDefaultId($this->groupId, $this->scope, $this->context);
		static::$instance[$this->id] = $this;
	}

	private function getDefaultVisibleColumns(bool $isExport = false): array
	{
		$columns = [
			'TITLE',
			'ACTIVITY_DATE',
			'DEADLINE',
			'ORIGINATOR_NAME',
			'RESPONSIBLE_NAME',
			'GROUP_NAME',
			'TAG',
			'STAGE_ID',
		];

		if (!$isExport && FlowFeature::isOn())
		{
			$columns[] = 'FLOW';
		}

		return $columns;
	}
}