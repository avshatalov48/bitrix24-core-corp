<?php

namespace Bitrix\BIConnector\TableBuilder;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalDatasetFieldTable;

/**
 * Builder for table
 */
class Builder
{
	private const TABLE_NAME_MAX_LENGTH = 64;
	private const COLUMN_NAME_MAX_LENGTH = 32;

	private Main\DB\Connection|Main\Data\Connection $connection;
	private string $tableName;
	private FieldCollection $tableFieldCollection;

	public function __construct()
	{
		$this->connection = Main\Application::getInstance()->getConnection();
	}

	/**
	 * Sets name of table
	 *
	 * @param string $name
	 * @return $this
	 */
	public function setName(string $name): self
	{
		$this->tableName = $name;

		return $this;
	}

	/**
	 * Sets collection of fields
	 *
	 * @param FieldCollection $fieldCollection
	 * @return $this
	 */
	public function setFieldCollection(FieldCollection $fieldCollection): self
	{
		$this->tableFieldCollection = $fieldCollection;

		return $this;
	}

	/**
	 * Build sql query for creating table
	 *
	 * @return Main\Result
	 */
	public function build(): Main\Result
	{
		$result = new Main\Result();

		$checkResult = $this->check();
		if (!$checkResult->isSuccess())
		{
			$result->addErrors($checkResult->getErrors());

			return $result;
		}

		$query = $this->generateQuery();
		$result->setData([
			'query' => $query,
		]);

		return $result;
	}

	private function check(): Main\Result
	{
		$result = new Main\Result();

		if (!preg_match(ExternalDatasetTable::TABLE_NAME_REGEXP, $this->tableName))
		{
			$result->addError(new Main\Error(Loc::getMessage('BICONNECTOR_TABLE_BUILDER_TABLE_NAME_ERROR')));
		}

		if (mb_strlen($this->tableName) > self::TABLE_NAME_MAX_LENGTH)
		{
			$result->addError(
				new Main\Error(
					Loc::getMessage(
						'BICONNECTOR_TABLE_BUILDER_TABLE_NAME_LONG_ERROR',
						[
							'#MAX_LENGTH#' => self::TABLE_NAME_MAX_LENGTH,
						]
					)
				)
			);
		}

		if ($this->tableFieldCollection->isEmpty())
		{
			$result->addError(new Main\Error(Loc::getMessage('BICONNECTOR_TABLE_BUILDER_FIELDS_NOT_FOUND')));
		}

		/** @var Field\Base $field */
		foreach ($this->tableFieldCollection as $field)
		{
			if (!preg_match(ExternalDatasetFieldTable::FIELD_NAME_REGEXP, $field->getName()))
			{
				$result->addError(
					new Main\Error(
						Loc::getMessage(
							'BICONNECTOR_TABLE_BUILDER_FIELD_NAME_ERROR',
							[
								'#FIELD_NAME#' => $field->getName(),
							]
						)
					)
				);
			}

			if (mb_strlen($field->getName()) > self::COLUMN_NAME_MAX_LENGTH)
			{
				$result->addError(
					new Main\Error(
						Loc::getMessage(
							'BICONNECTOR_TABLE_BUILDER_FIELD_NAME_LONG_ERROR',
							[
								'#FIELD_NAME#' => $field->getName(),
								'#MAX_LENGTH#' => self::COLUMN_NAME_MAX_LENGTH,
							]
						)
					)
				);
			}
		}

		return $result;
	}

	private function generateQuery(): string
	{
		$fields = [];
		foreach ($this->tableFieldCollection as $field)
		{
			$fields[] = $this->getTableField($field);
		}

		return sprintf(
			"CREATE TABLE IF NOT EXISTS `%s` (%s);",
			$this->getTableName(),
			implode(",\n", $fields)
		);
	}

	private function getTableName(): string
	{
		return $this->connection->getSqlHelper()->forSql($this->tableName);
	}

	private function getTableField(Field\Base $field): string
	{
		return $field->getField();
	}
}
