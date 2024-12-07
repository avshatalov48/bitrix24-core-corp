<?php

namespace Bitrix\BIConnector\Integration\Tasks;

use Bitrix\BIConnector\DataSource\DatasetFilter;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\BIConnector\DataSource\Dataset;
use Bitrix\BIConnector\DataSource\Field\IntegerField;
use Bitrix\BIConnector\DataSource\Field\StringField;
use Bitrix\Tasks\Kanban\StagesTable;

class TaskStages extends Dataset
{
	protected const FIELD_NAME_PREFIX = 'TASK_STAGES_FIELD_';

	protected function getResultTableName(): string
	{
		return 'task_stages';
	}

	public function getSqlTableAlias(): string
	{
		return 'TS';
	}

	protected function getConnectionTableName(): string
	{
		return 'b_tasks_stages';
	}

	protected function getTableDescription(): string
	{
		return $this->getMessage('TASK_STAGES_TABLE');
	}

	protected function getFilter(): DatasetFilter
	{
		return new DatasetFilter(
			[
				'!=ENTITY_TYPE' => StagesTable::WORK_MODE_USER,
			],
			[
				new StringField('ENTITY_TYPE')
			]
		);
	}

	/**
	 * @return Result
	 */
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
		$systemScrumTitles = [];
		$stages = StagesTable::getStages();
		foreach ($stages as $stage)
		{
			if (!empty($stage['SYSTEM_TYPE']))
			{
				$systemScrumTitles[$stage['SYSTEM_TYPE']] = $stage['TITLE'];
			}
		}

		$titleField = new StringField('TITLE', null, $this);
		if (!empty($systemScrumTitles))
		{
			$titleField->setDictionary($systemScrumTitles);
			$titleField->setName("
				if(
					{$this->getAliasFieldName('TITLE')} is NULL,
					{$titleField->mapDictionaryToSqlCase($this->getAliasFieldName('SYSTEM_TYPE'))},
					{$this->getAliasFieldName('TITLE')}
				)
			");
		}

		$groupJoin = $this->createJoin(
			"SGROUP",
			"INNER JOIN b_sonet_group SGROUP ON SGROUP.ID = {$this->getAliasFieldName('ENTITY_ID')}",
			"LEFT JOIN b_sonet_group SGROUP ON SGROUP.ID = {$this->getAliasFieldName('ENTITY_ID')}",
		);

		return [
			(new IntegerField('ID'))
				->setPrimary()
			,
			$titleField,
			(new IntegerField('SORT')),
			(new StringField('COLOR')),
			(new IntegerField('GROUP_ID'))
				->setName($this->getAliasFieldName('ENTITY_ID'))
			,
			(new StringField('GROUP_NAME'))
				->setName($groupJoin->getJoinFieldName('NAME'))
				->setJoin($groupJoin)
			,
			(new StringField('GROUP_INFO'))
				->setName("
					if(
						{$this->getAliasFieldName('ENTITY_ID')} > 0,
						concat_ws(
							' ', 
							concat('[', {$this->getAliasFieldName('ENTITY_ID')}, ']'), 
							nullif({$groupJoin->getJoinFieldName('NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($groupJoin)
			,
		];
	}
}
