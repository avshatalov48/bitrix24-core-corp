<?php

namespace Bitrix\BIConnector\DataSource;

use Bitrix\Main\DB\Connection;
use Bitrix\Main\DB\SqlHelper;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Event;
use Bitrix\Main\Result;

abstract class Dataset
{
	protected const FIELD_NAME_PREFIX = '';

	protected function __construct(
		private readonly Connection $dataConnection,
		protected readonly ?string $languageId = null
	)
	{
	}

	/**
	 * Return final dataset table name
	 *
	 * @return string
	 */
	abstract protected function getResultTableName(): string;

	/**
	 * Return table alias for sql selection
	 *
	 * @return string
	 */
	abstract public function getSqlTableAlias(): string;

	/**
	 * Return internal bitrix table name
	 *
	 * @return string
	 */
	abstract protected function getConnectionTableName(): string;

	/**
	 * Return `DatasetField[]` array for building select table description
	 *
	 * @return array
	 */
	abstract protected function getFields(): array;

	/**
	 * @return Result
	 */
	protected function onBeforeEvent(): Result
	{
		return new Result();
	}

	/**
	 * @return string
	 */
	protected function getTableDescription(): string
	{
		return $this->getMessage('TABLE_DESCRIPTION', $this->getConnectionTableName());
	}

	/**
	 * @return DatasetFilter|null
	 */
	protected function getFilter(): ?DatasetFilter
	{
		return null;
	}

	/**
	 * @return array|null
	 */
	protected function getDictionaries(): array
	{
		return [];
	}

	/**
	 * @return array
	 */
	protected function getDatasetFields(): array
	{
		$result = [];

		foreach ($this->getFields() as $field)
		{
			if ($field instanceof DatasetField)
			{
				$field->setDataset($this);
				$result[$field->getCode()] = $field;
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	protected function getResultFields(): array
	{
		$result = [];

		foreach ($this->getDatasetFields() as $field)
		{
			$result[$field->getCode()] = $field->getFormatted();
		}

		return $result;
	}

	/**
	 * @return array
	 */
	protected function getResult(): array
	{
		$result = [
			'TABLE_NAME' => $this->getConnectionTableName(),
			'TABLE_ALIAS' => $this->getSqlTableAlias(),
			'TABLE_DESCRIPTION' => $this->getTableDescription(),
			'FIELDS' => $this->getResultFields(),
		];

		if (!empty($this->getFilter()?->filterFields()))
		{
			$result['FILTER'] = $this->getFilter()->datasetFilter();
			$result['FILTER_FIELDS'] = [];
			foreach ($this->getFilter()->filterFields() as $field)
			{
				$result['FILTER_FIELDS'][$field->getCode()] =
					$field
						->setDataset($this)
						->getFormatted()
				;
			}
		}

		if (!empty($this->getDictionaries()))
		{
			$result['DICTIONARY'] = $this->getDictionaries();
		}

		return $result;
	}

	/**
	 * @param string $alias
	 * @param string $joinInner
	 * @param string $joinLeft
	 *
	 * @return JoinSelection
	 */
	protected function createJoin(string $alias, string $joinInner, string $joinLeft): JoinSelection
	{
		return new JoinSelection(
			$this,
			$alias,
			$joinInner,
			$joinLeft
		);
	}

	/**
	 * @param string $code
	 *
	 * @return string
	 */
	public function getAliasFieldName(string $code): string
	{
		return $this->getSqlHelper()->quote("{$this->getSqlTableAlias()}.{$code}");
	}

	/**
	 * @param string $phraseCode
	 * @param string $defaultValue
	 *
	 * @return string
	 */
	public function getMessage(string $phraseCode, string $defaultValue = ''): string
	{
		return Loc::getMessage($phraseCode, null, $this->languageId) ?? $defaultValue ;
	}

	/**
	 * @return string
	 */
	public function getFieldNamePrefix(): string
	{
		return static::FIELD_NAME_PREFIX;
	}

	/**
	 * @return SqlHelper
	 */
	public function getSqlHelper(): SqlHelper
	{
		return $this->dataConnection->getSqlHelper();
	}

	/**
	 * Event handler for OnBIConnectorDataSources event.
	 * Adds a key from `getResultTableName` to the second event parameter.
	 * Fills it with data to retrieve information from table.
	 *
	 * @param Event $event Event data.
	 *
	 * @return void
	 */
	public static function onBIConnectorDataSources(Event $event)
	{
		$params = $event->getParameters();
		$manager = $params[0];
		$languageId = $params[2];
		$connection = $manager->getDatabaseConnection();
		if (!$connection)
		{
			return;
		}

		$dataset = new static($connection, $languageId);
		if (!$dataset->onBeforeEvent()->isSuccess())
		{
			return;
		}

		$result = &$params[1];
		$tableName = $dataset->getResultTableName();
		$result[$tableName] = $dataset->getResult();
	}
}
