<?php

namespace Bitrix\Tasks\Helper;

use Bitrix\Main\Grid as MainGrid;
use Bitrix\Tasks\Grid\Scope\Scope;

class Grid extends Common
{
	protected static ?array $instance = null;

	public function getVisibleColumns(): array
	{
		$columns = $this->getOptions()->GetVisibleColumns();

		if (empty($columns))
		{
			$columns = $this->getDefaultVisibleColumns();
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
			$this->resolveChangedScope();
		}

		return $this;
	}

	public function getAllColumns(): array
	{
		return [
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
			'UF_CRM_TASK_LEAD',
			'UF_CRM_TASK_CONTACT',
			'UF_CRM_TASK_COMPANY',
			'UF_CRM_TASK_DEAL',
			'UF_CRM_TASK',

			'PARENT_ID',
			'PARENT_TITLE',
		];
	}

	protected function resolveChangedScope(): void
	{
		unset(static::$instance[$this->id]);
		$this->id = static::getDefaultId($this->groupId, $this->scope, $this->context);
		static::$instance[$this->id] = $this;
	}

	private function getDefaultVisibleColumns(): array
	{
		return [
			'TITLE',
			'ACTIVITY_DATE',
			'DEADLINE',
			'ORIGINATOR_NAME',
			'RESPONSIBLE_NAME',
			'GROUP_NAME',
			'TAG',
		];
	}
}