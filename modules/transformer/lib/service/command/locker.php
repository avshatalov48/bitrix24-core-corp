<?php

namespace Bitrix\Transformer\Service\Command;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\DB\SqlHelper;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Transformer\Command;

final class Locker
{
	private Connection $connection;
	private SqlHelper $sqlHelper;

	public function __construct()
	{
		$this->connection = Application::getConnection();
		$this->sqlHelper = $this->connection->getSqlHelper();
	}

	public function lock(Command $command): bool
	{
		$guid = $command->getGuid();

		if (empty($guid))
		{
			throw new InvalidOperationException('Unsaved command could not be locked');
		}

		return $this->connection->lock($this->getLockName($guid));
	}

	public function unlock(Command $command): bool
	{
		$guid = $command->getGuid();

		if (empty($guid))
		{
			throw new InvalidOperationException('Unsaved command could not be locked');
		}

		return $this->connection->unlock($this->getLockName($guid));
	}

	private function getLockName(string $guid): string
	{
		return "transformer_command_{$this->sqlHelper->forSql($guid)}";
	}
}
