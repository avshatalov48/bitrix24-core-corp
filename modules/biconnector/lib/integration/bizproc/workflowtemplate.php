<?php

namespace Bitrix\BIConnector\Integration\Bizproc;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\BIConnector\DataSource\Field\IntegerField;
use Bitrix\BIConnector\DataSource\Field\StringField;
use Bitrix\BIConnector\DataSource\Field\DateTimeField;
use Bitrix\BIConnector\DataSource\Dataset;

class WorkflowTemplate extends Dataset
{
	protected const FIELD_NAME_PREFIX = 'WORKFLOW_TEMPLATE_FIELD_';

	protected function getResultTableName(): string
	{
		return 'bizproc_workflow_template';
	}

	public function getSqlTableAlias(): string
	{
		return 'WT';
	}

	protected function getConnectionTableName(): string
	{
		return 'b_bp_workflow_template';
	}

	protected function getTableDescription(): string
	{
		return $this->getMessage('WORKFLOW_TEMPLATE_TABLE');
	}

	protected function onBeforeEvent(): Result
	{
		$result = parent::onBeforeEvent();

		if (!Loader::includeModule('bizproc'))
		{
			$result->addError(new Error('Module is not installed'));
		}

		return $result;
	}

	protected function getFields(): array
	{
		$userJoin = $this->createJoin(
			'USER',
			"INNER JOIN b_user USER ON USER.ID = {$this->getAliasFieldName('USER_ID')}",
			"LEFT JOIN b_user USER ON USER.ID = {$this->getAliasFieldName('USER_ID')}"
		);

		return [
			(new IntegerField('ID'))
				->setPrimary()
			,
			(new StringField('WORKFLOW_TEMPLATE'))
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
			(new StringField('MODULE_ID')),
			(new StringField('ENTITY')),
			(new StringField('DOCUMENT_TYPE')),
			(new StringField('DOCUMENT_STATUS')),
			(new StringField('NAME')),
			(new DateTimeField('MODIFIED'))
				->setCallback(
					static function ($value): ?string {
						$modifiedTimeTimestamp = strtotime($value);

						return $modifiedTimeTimestamp && $modifiedTimeTimestamp > 0 ? (string)$value : null;
					}
				)
			,
			(new StringField('IS_MODIFIED'))
				->setName("
					if(
						{$this->getAliasFieldName('IS_MODIFIED')} = 'Y',
						'Y',
						'N'
					)"
				)
			,
			(new IntegerField('USER_ID')),
			(new StringField('USER_NAME'))
				->setName("
					if(
						{$this->getAliasFieldName('USER_ID')} > 0,
						concat_ws(
							' ', 
							nullif({$userJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$userJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($userJoin)
			,
			(new StringField('USER'))
				->setName("
					if(
						{$this->getAliasFieldName('USER_ID')} > 0,
						concat_ws(
							' ', 
							concat('[', {$this->getAliasFieldName('USER_ID')}, ']'), 
							nullif({$userJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$userJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($userJoin)
			,
			(new StringField('SYSTEM_CODE')),
			(new StringField('IS_SYSTEM'))
				->setName("
					if(
						{$this->getAliasFieldName('IS_SYSTEM')} = 'Y',
						'Y',
						'N'
					)"
				)
			,
			(new StringField('ACTIVE'))
				->setName("
					if(
						{$this->getAliasFieldName('ACTIVE')} = 'Y',
						'Y',
						'N'
					)"
				)
			,
			(new StringField('TYPE')),
		];
	}
}
