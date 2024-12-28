<?php

namespace Bitrix\BIConnector\Integration\Tasks;

use Bitrix\BIConnector\DataSource\Field\DateTimeField;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\BIConnector\DataSource\Dataset;
use Bitrix\BIConnector\DataSource\Field\IntegerField;
use Bitrix\BIConnector\DataSource\Field\StringField;

class TaskEfficiency extends Dataset
{
	protected const FIELD_NAME_PREFIX = 'TASK_EFFICIENCY_';

	protected function getResultTableName(): string
	{
		return 'task_efficiency';
	}

	public function getSqlTableAlias(): string
	{
		return 'TE';
	}

	protected function getConnectionTableName(): string
	{
		return 'b_tasks_effective';
	}

	protected function getTableDescription(): string
	{
		return $this->getMessage('TASK_EFFICIENCY_TABLE_MSGVER_1');
	}

	protected function onBeforeEvent(): Result
	{
		$result = parent::onBeforeEvent();

		if (!Loader::includeModule('tasks'))
		{
			$result->addError(new Error('Module is not installed'));
		}

		return $result;
	}

	protected function getFields(): array
	{
		return [
			(new IntegerField('ID'))
				->setPrimary()
			,
			(new IntegerField('TASK_ID'))
				->setDescription($this->getMessage('TASK_EFFICIENCY_TASK_ID_MSGVER_1'))
				->setDescriptionFull($this->getMessage('TASK_EFFICIENCY_TASK_ID_MSGVER_1_FULL'))
			,
			(new DateTimeField('DATETIME'))
				->setDescription($this->getMessage('TASK_EFFICIENCY_DATETIME_MSGVER_1'))
				->setDescriptionFull($this->getMessage('TASK_EFFICIENCY_DATETIME_MSGVER_1_FULL'))
			,
			(new DateTimeField('DATETIME_REPAIR'))
				->setDescription($this->getMessage('TASK_EFFICIENCY_DATETIME_REPAIR_MSGVER_1'))
				->setDescriptionFull($this->getMessage('TASK_EFFICIENCY_DATETIME_REPAIR_MSGVER_1_FULL'))
			,
			(new StringField('IS_VIOLATION'))
				->setDescription($this->getMessage('TASK_EFFICIENCY_IS_VIOLATION_MSGVER_1'))
				->setDescriptionFull($this->getMessage('TASK_EFFICIENCY_IS_VIOLATION_MSGVER_1_FULL'))
			,
		];
	}
}