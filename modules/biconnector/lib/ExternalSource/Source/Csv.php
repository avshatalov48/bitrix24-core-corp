<?php

namespace Bitrix\BIConnector\ExternalSource\Source;

use Bitrix\Main;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset;
use Bitrix\BIConnector\ExternalSource\DatasetManager;

class Csv extends Base
{
	private ExternalDataset $dataset;
	private Main\DB\Connection|Main\Data\Connection $connection;

	public const TABLE_NAME_PREFIX = 'b_biconnector_external_source_csv_';

	/**
	 * @param int $id dataset id
	 */
	public function __construct(int $id)
	{
		parent::__construct($id);

		$this->connection = Main\Application::getConnection();
	}

	/**
	 * @inheritDoc
	 */
	public function connect(string $host, string $username, string $password): Main\Result
	{
		$this->dataset = DatasetManager::getById($this->id);

		return new Main\Result();
	}

	/**
	 * @inheritDoc
	 */
	public function getEntityList(): array
	{
		return [self::getFullTableName($this->dataset->getName())];
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(string $entityName): array
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getFirstNData(string $entityName, int $n): array
	{
		$result = [];

		if ($n < 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'n');
		}

		if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $entityName))
		{
			throw new Main\ArgumentException('Invalid table name', 'table');
		}

		$fullTableName = self::getFullTableName($entityName);

		$query = sprintf('SELECT * FROM %s LIMIT %d', $fullTableName, $n);
		try
		{
			$queryResult = $this->connection->query($query);
			while ($row = $queryResult->fetch())
			{
				$result[] = $row;
			}
		}
		catch (Main\DB\SqlQueryException $exception)
		{
			$result = [];
		}

		return $result;
	}

	/**
	 * @see DatasetManager::EVENT_ON_AFTER_DELETE_DATASET
	 *
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function onAfterDeleteDataset(Main\Event $event): Main\EventResult
	{
		foreach ($event->getResults() as $result)
		{
			if ($result->getType() === Main\EventResult::ERROR)
			{
				return new Main\EventResult(Main\EventResult::ERROR);
			}
		}

		/** @var ExternalDataset $dataset */
		$dataset = $event->getParameter('dataset');
		$name = $dataset->getName();

		$connection = Main\Application::getInstance()->getConnection();
		try
		{
			$connection->query(sprintf('DROP TABLE IF EXISTS `%s`;', self::getFullTableName($name)));
		}
		catch (Main\DB\SqlQueryException $exception)
		{
			return new Main\EventResult(Main\EventResult::ERROR, new Main\Error($exception->getMessage()));
		}

		return new Main\EventResult(Main\EventResult::SUCCESS);
	}

	private static function getFullTableName(string $table): string
	{
		return self::TABLE_NAME_PREFIX . $table;
	}
}
