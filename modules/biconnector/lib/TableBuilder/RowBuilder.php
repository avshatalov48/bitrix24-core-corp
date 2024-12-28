<?php

namespace Bitrix\BIConnector\TableBuilder;

use Bitrix\Main;

class RowBuilder
{
	private Main\DB\Connection|Main\Data\Connection $connection;
	private string $tableName;
	private RowCollection $rowCollection;
	private FieldCollection $fieldCollection;

	public function __construct(string $tableName, FieldCollection $fieldCollection)
	{
		$this->tableName = $tableName;
		$this->fieldCollection = $fieldCollection;

		$this->connection = Main\Application::getInstance()->getConnection();
	}

	/**
	 * Sets collection of rows
	 *
	 * @param RowCollection $rowCollection
	 * @return $this
	 */
	public function setRowCollection(RowCollection $rowCollection): self
	{
		$this->rowCollection = $rowCollection;

		return $this;
	}

	/**
	 * Build sql query for inserting rows into table
	 *
	 * @return Main\Result
	 */
	public function build(): Main\Result
	{
		$result = new Main\Result();

		$query = sprintf(
			'INSERT INTO %s (%s) VALUES %s;',
			$this->getTableName(),
			implode(', ', $this->getColumns()),
			implode(', ', $this->getValues())
		);
		$result->setData([
			'query' => $query,
		]);

		return $result;
	}

	private function getTableName(): string
	{
		return $this->connection->getSqlHelper()->forSql($this->tableName);
	}

	private function getColumns(): array
	{
		$fields = [];
		foreach ($this->fieldCollection as $field)
		{
			$fields[] = $this->getTableField($field);
		}

		return $fields;
	}

	private function getValues(): array
	{
		$rows = [];
		/** @var Row $row */
		foreach ($this->rowCollection as $row)
		{
			$rows[] = $row->getRowValue();
		}

		return $rows;
	}

	private function getTableField(Field\Base $field): string
	{
		return sprintf('`%s`', $field->getName());
	}
}
