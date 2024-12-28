<?php

namespace Bitrix\BIConnector\Integration\Tasks;

use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\BIConnector\DataSource\Dataset;
use Bitrix\BIConnector\DataSource\Field\IntegerField;
use Bitrix\BIConnector\DataSource\Field\StringField;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;

class Flow extends Dataset
{
	protected const FIELD_NAME_PREFIX = 'FLOW_FIELD_';

	protected function getResultTableName(): string
	{
		return 'flow';
	}

	public function getSqlTableAlias(): string
	{
		return 'TF';
	}

	protected function getConnectionTableName(): string
	{
		return 'b_tasks_flow';
	}

	protected function getTableDescription(): string
	{
		return $this->getMessage('FLOW_TABLE');
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
		$creatorJoin = $this->createJoin(
			'CREATOR',
			"INNER JOIN b_user CREATOR ON CREATOR.ID = {$this->getAliasFieldName('CREATOR_ID')}",
			"LEFT JOIN b_user CREATOR ON CREATOR.ID = {$this->getAliasFieldName('CREATOR_ID')}",
		);

		$ownerJoin = $this->createJoin(
			'OWNER',
			"INNER JOIN b_user OWNER ON OWNER.ID = {$this->getAliasFieldName('OWNER_ID')}",
			"LEFT JOIN b_user OWNER ON OWNER.ID = {$this->getAliasFieldName('OWNER_ID')}",
		);

		$groupJoin = $this->createJoin(
			'SGROUP',
			"INNER JOIN b_sonet_group SGROUP ON SGROUP.ID = {$this->getAliasFieldName('GROUP_ID')}",
			"LEFT JOIN b_sonet_group SGROUP ON SGROUP.ID = {$this->getAliasFieldName('GROUP_ID')}",
		);

		$tasksJoin = $this->createJoin(
			'TFT',
			"INNER JOIN b_tasks_flow_task TFT ON TFT.FLOW_ID = {$this->getAliasFieldName('ID')}",
			"LEFT JOIN b_tasks_flow_task TFT ON TFT.FLOW_ID = {$this->getAliasFieldName('ID')}",
		);

		$expiredTasksJoin = $this->createJoin(
			'EXPIRED',
			"INNER JOIN (
						SELECT BTFT.TASK_ID AS EXPIRED_TASKS_IDS, BTFT.FLOW_ID
						FROM b_tasks_effective BTE
							INNER JOIN b_tasks_flow_task BTFT ON BTE.TASK_ID = BTFT.TASK_ID
						WHERE BTE.IS_VIOLATION = 'Y'
					) EXPIRED ON EXPIRED.FLOW_ID = {$this->getAliasFieldName('ID')}",
			"LEFT JOIN (
						SELECT BTFT.TASK_ID AS EXPIRED_TASKS_IDS, BTFT.FLOW_ID
						FROM b_tasks_effective BTE
							INNER JOIN b_tasks_flow_task BTFT ON BTE.TASK_ID = BTFT.TASK_ID
						WHERE BTE.IS_VIOLATION = 'Y'
					) EXPIRED ON EXPIRED.FLOW_ID = {$this->getAliasFieldName('ID')}",
		);

		return [
			(new IntegerField('ID'))
				->setPrimary()
			,
			(new StringField('NAME')),
			(new StringField('FLOW'))
				->setName("
					if(
						{$this->getAliasFieldName('ID')} > 0,
						concat_ws(
							' ', 
							concat('[', {$this->getAliasFieldName('ID')}, ']'), 
							nullif({$this->getAliasFieldName('NAME')}, '')
						),
						NULL
					)"
				)
			,
			(new IntegerField('CREATOR_ID'))
				->setName($this->getAliasFieldName('CREATOR_ID'))
			,
			(new StringField('CREATOR_NAME'))
				->setName("
					if(
						{$this->getAliasFieldName('CREATOR_ID')} > 0,
						concat_ws(
							' ', 
							nullif({$creatorJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$creatorJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($creatorJoin)
			,
			(new StringField('CREATOR'))
				->setName("
					if(
						{$this->getAliasFieldName('CREATOR_ID')} > 0,
						concat_ws(
							' ', 
							concat('[', {$this->getAliasFieldName('CREATOR_ID')}, ']'), 
							nullif({$creatorJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$creatorJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($creatorJoin)
			,
			(new IntegerField('OWNER_ID'))
				->setName($this->getAliasFieldName('OWNER_ID'))
			,
			(new StringField('OWNER_NAME'))
				->setName("
					if(
						{$this->getAliasFieldName('OWNER_ID')} > 0,
						concat_ws(
							' ', 
							nullif({$ownerJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$ownerJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($ownerJoin)
			,
			(new StringField('OWNER'))
				->setName("
					if(
						{$this->getAliasFieldName('OWNER_ID')} > 0,
						concat_ws(
							' ', 
							concat('[', {$this->getAliasFieldName('OWNER_ID')}, ']'), 
							nullif({$ownerJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$ownerJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($ownerJoin)
			,
			(new IntegerField('PLANNED_COMPLETION_TIME'))
				->setMetric()
			,
			(new StringField('DISTRIBUTION_TYPE'))
				->setDictionary([
					FlowDistributionType::MANUALLY->value => $this->getMessage('FLOW_FIELD_DISTRIBUTION_TYPE_VALUE_TYPE_MANUALLY'),
					FlowDistributionType::QUEUE->value => $this->getMessage('FLOW_FIELD_DISTRIBUTION_TYPE_VALUE_TYPE_QUEUE'),
					FlowDistributionType::HIMSELF->value => $this->getMessage('FLOW_FIELD_DISTRIBUTION_TYPE_VALUE_TYPE_HIMSELF'),
				]),
			(new StringField('HAS_TEMPLATE'))
				->setName("
					if(
						{$this->getAliasFieldName('TEMPLATE_ID')} >= 1,
						'Y',
						'N'
					)"
				),
			(new StringField('ACTIVE'))
				->setName("
					if(
						{$this->getAliasFieldName('ACTIVE')} >= 1,
						'Y',
						'N'
					)"
				),
			(new IntegerField('GROUP_ID')),
			(new StringField('GROUP_NAME'))
				->setName($groupJoin->getJoinFieldName('NAME'))
				->setJoin($groupJoin)
			,
			(new StringField('GROUP_INFO'))
				->setName("
					if(
						{$this->getAliasFieldName('GROUP_ID')} > 0,
						concat_ws(
							' ', 
							concat('[', {$this->getAliasFieldName('GROUP_ID')}, ']'), 
							nullif({$groupJoin->getJoinFieldName('NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($groupJoin)
			,
			(new StringField('TASKS_IDS'))
				->setName($tasksJoin->getJoinFieldName('TASK_ID'))
				->setJoin($tasksJoin)
				->setMultiple()
			,
			(new StringField('EXPIRED_TASKS_IDS'))
				->setName($expiredTasksJoin->getJoinFieldName('EXPIRED_TASKS_IDS'))
				->setJoin($expiredTasksJoin)
				->setMultiple()
			,
		];
	}
}
