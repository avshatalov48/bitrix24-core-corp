<?php

namespace Bitrix\BIConnector\Integration\Tasks;

use Bitrix\BIConnector\DataSource\DatasetField;
use Bitrix\BIConnector\DataSource\Field\IntegerField;
use Bitrix\BIConnector\DataSource\UserFieldDataset;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Tasks\Util\Userfield;

class TaskUserField extends UserFieldDataset
{
	protected const FIELD_NAME_PREFIX = 'TASK_UF_FIELD_';
	protected array $userFields = [];

	protected function getResultTableName(): string
	{
		return 'task_uf';
	}

	public function getSqlTableAlias(): string
	{
		return 'TUF';
	}

	protected function getConnectionTableName(): string
	{
		return 'b_uts_tasks_task';
	}

	protected function getTableDescription(): string
	{
		return $this->getMessage('TASK_UF_TABLE');
	}

	protected function getRawUserFields(): array
	{
		return Userfield\Task::getScheme(0, 0, $this->languageId);
	}

	/**
	 * @return Result
	 */
	protected function onBeforeEvent(): Result
	{
		if (!Loader::includeModule('tasks'))
		{
			$result = new Result();

			$result->addError(new Error('Module is not installed'));

			return $result;
		}

		return parent::onBeforeEvent();
	}

	protected function getFields(): array
	{
		$fields = [
			(new IntegerField('TASK_ID'))
				->setName($this->getAliasFieldName('VALUE_ID'))
				->setPrimary()
			,
		];

		$ufFields = array_filter(
			parent::getFields(),
			static fn (DatasetField $field): bool => !in_array($field->getCode(), ['UF_TASK_WEBDAV_FILES', 'UF_MAIL_MESSAGE'], true)
		);

		$ufMailField = new IntegerField('UF_MAIL_MESSAGE');
		if (!empty($this->userFields['UF_MAIL_MESSAGE']['USER_TYPE']['DESCRIPTION']))
		{
			$ufMailField->setDescription($this->userFields['UF_MAIL_MESSAGE']['USER_TYPE']['DESCRIPTION']);
		}
		$ufFields[] = $ufMailField;

		return array_merge($fields, $ufFields);
	}
}
