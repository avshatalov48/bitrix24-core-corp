<?php

namespace Bitrix\BIConnector\ExternalSource\Importer;

use Bitrix\Main;
use Bitrix\BIConnector;

abstract class Importer
{
	protected Settings $settings;
	private BIConnector\ExternalSource\FileReader\Base $reader;
	private FieldCollection $importerFieldCollection;
	private Main\DB\Connection|Main\Data\Connection $connection;
	private BIConnector\TableBuilder\FieldCollection $tableBuilderFieldCollection;

	public function __construct(Settings $settings)
	{
		$this->connection = Main\Application::getInstance()->getConnection();

		$this->settings = $settings;
		$this->reader = $settings->reader;
		$this->importerFieldCollection = $settings->fieldCollection;
	}

	/**
	 * Imports data from file to table
	 *
	 * @return Main\Result
	 */
	public function import(): Main\Result
	{
		$result = new Main\Result();

		$this->tableBuilderFieldCollection = new BIConnector\TableBuilder\FieldCollection();

		/** @var Field $field */
		foreach ($this->importerFieldCollection as $field)
		{
			$this->tableBuilderFieldCollection->add(
				BIConnector\TableBuilder\Field\Factory::getField($field->type, $field->name)
			);
		}

		$this->connection->startTransaction();

		$buildTableResult = $this->buildTable();
		if (!$buildTableResult->isSuccess())
		{
			$this->connection->rollbackTransaction();
			$result->addErrors($buildTableResult->getErrors());

			return $result;
		}

		$buildRowsResult = $this->buildRows();
		if (!$buildRowsResult->isSuccess())
		{
			$this->connection->rollbackTransaction();
			$result->addErrors($buildRowsResult->getErrors());

			return $result;
		}

		$this->connection->commitTransaction();

		return $result;
	}

	/**
	 * Re-Imports data from file to table
	 *
	 * @return Main\Result
	 */
	public function reImport(): Main\Result
	{
		$result = $this->truncateTable();
		if (!$result->isSuccess())
		{
			return $result;
		}

		return $this->import();
	}

	private function truncateTable(): Main\Result
	{
		$result = new Main\Result();

		$connection = Main\Application::getInstance()->getConnection();
		try
		{
			$connection->query(sprintf('TRUNCATE TABLE `%s`;', $this->getTableName()));
		}
		catch (Main\DB\SqlQueryException $exception)
		{
			$result->addError(new Main\Error($exception->getMessage()));
		}

		return $result;
	}

	private function buildTable(): Main\Result
	{
		$result = new Main\Result();

		$builder = new BIConnector\TableBuilder\Builder();
		$builder->setName($this->getTableName());
		$builder->setFieldCollection($this->tableBuilderFieldCollection);

		$buildResult = $builder->build();
		if ($buildResult->isSuccess())
		{
			$createTableResult = $this->createTable($buildResult->getData()['query']);
			if (!$createTableResult->isSuccess())
			{
				$result->addErrors($createTableResult->getErrors());

				return $result;
			}
		}
		else
		{
			$result->addErrors($buildResult->getErrors());
		}

		return $result;
	}

	private function buildRows(): Main\Result
	{
		$result = new Main\Result();

		$count = 0;
		$portion = 1000;

		$tableBuilderRows = [];
		foreach ($this->reader->readAllRowsByOne() as $row)
		{
			$fieldDataCollection = new BIConnector\TableBuilder\FieldDataCollection();
			foreach ($row as $index => $value)
			{
				/** @var Field $field */
				$field = $this->importerFieldCollection[$index];
				$value = $this->convertData($field, $value);

				$fieldData = BIConnector\TableBuilder\FieldData\Factory::getFieldData(
					$field->type,
					$field->name,
					$value
				);
				$fieldDataCollection->add($fieldData);
			}

			$tableBuilderRows[] = new BIConnector\TableBuilder\Row($fieldDataCollection);

			$count++;
			if ($count % $portion === 0)
			{
				$insertRowsResult = $this->insertRows($tableBuilderRows);
				if (!$insertRowsResult->isSuccess())
				{
					$result->addErrors($insertRowsResult->getErrors());

					return $result;
				}

				$tableBuilderRows = [];
			}
		}

		if (!empty($tableBuilderRows))
		{
			$insertRowsResult = $this->insertRows($tableBuilderRows);
			if (!$insertRowsResult->isSuccess())
			{
				$result->addErrors($insertRowsResult->getErrors());

				return $result;
			}
		}

		return $result;
	}

	private function createTable(string $sql): Main\Result
	{
		$result = new Main\Result();

		try
		{
			$this->connection->query($sql);
		}
		catch (Main\DB\SqlQueryException $exception)
		{
			$result->addError(new Main\Error($exception->getMessage()));
		}

		return $result;
	}

	private function insertRows(array $tableBuilderRows): Main\Result
	{
		$result = new Main\Result();

		$rowCollection = new BIConnector\TableBuilder\RowCollection();
		$rowCollection->addRows($tableBuilderRows);

		$builder = new BIConnector\TableBuilder\RowBuilder($this->getTableName(), $this->tableBuilderFieldCollection);
		$builder->setRowCollection($rowCollection);

		$buildRowsResult = $builder->build();
		if ($buildRowsResult->isSuccess())
		{
			try
			{
				$this->connection->query($buildRowsResult->getData()['query']);
			}
			catch (Main\DB\SqlQueryException $exception)
			{
				$result->addError(new Main\Error($exception->getMessage()));

				return $result;
			}
		}
		else
		{
			$result->addErrors($buildRowsResult->getErrors());
		}

		return $result;
	}

	private function convertData(Field $field, string $value): mixed
	{
		$result = $value;

		$type = $field->type;
		switch ($type)
		{
			case BIConnector\ExternalSource\FieldType::Int:
				$result = BIConnector\ExternalSource\TypeConverter::convertToInt($value);

				break;
			case BIConnector\ExternalSource\FieldType::String:
				$result = BIConnector\ExternalSource\TypeConverter::convertToString($value);

				break;

			case BIConnector\ExternalSource\FieldType::Double:
				$delimiter = $field->format;
				$result = BIConnector\ExternalSource\TypeConverter::convertToDouble(
					$value,
					delimiter: $delimiter
				);

				break;

			case BIConnector\ExternalSource\FieldType::Date:
				$format = $field->format;
				$result = BIConnector\ExternalSource\TypeConverter::convertToDate(
					$value,
					$format
				);

				break;

			case BIConnector\ExternalSource\FieldType::DateTime:
				$format = $field->format;
				$result = BIConnector\ExternalSource\TypeConverter::convertToDateTime(
					$value,
					$format
				);

				break;

			case BIConnector\ExternalSource\FieldType::Money:
				$delimiter = $field->format;
				$result = BIConnector\ExternalSource\TypeConverter::convertToMoney(
					$value,
					delimiter: $delimiter
				);

				break;
		}

		return $result;
	}

	abstract protected function getTableName(): string;
}
