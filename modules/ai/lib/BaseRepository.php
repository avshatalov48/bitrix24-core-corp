<?php declare(strict_types=1);

namespace Bitrix\AI;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Connection;
use Bitrix\Main\DB\PgsqlConnection;
use Bitrix\Main\DB\SqlHelper;
use Bitrix\Main\ORM\Fields\ExpressionField;

abstract class BaseRepository
{
	private ?SqlHelper $sqlHelper = null;
	private ?Connection $connection = null;

	protected function getConnection(): Connection
	{
		if (is_null($this->connection))
		{
			$this->connection = Application::getConnection();
		}

		return $this->connection;
	}

	protected function getSqlHelper()
	{
		if (is_null($this->sqlHelper))
		{
			$this->sqlHelper = $this->getConnection()->getSqlHelper();
		}

		return $this->sqlHelper;
	}

	protected function getSqlByDbType(string $mysqlQuery, string $pgsqlQuery): string
	{
		return $this->getConnection() instanceof PgsqlConnection
			? $pgsqlQuery
			: $mysqlQuery;
	}

	protected function getFieldByDbType(ExpressionField $mysqlQuery, ExpressionField $pgsqlQuery): ExpressionField
	{
		return $this->getConnection() instanceof PgsqlConnection
			? $pgsqlQuery
			: $mysqlQuery;
	}
}
