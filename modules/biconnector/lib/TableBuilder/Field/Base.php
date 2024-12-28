<?php

namespace Bitrix\BIConnector\TableBuilder\Field;

use Bitrix\Main;

abstract class Base
{
	private Main\DB\PgsqlSqlHelper|Main\DB\MysqliSqlHelper $sqlHelper;

	public function __construct(
		readonly protected string $name,
	)
	{
		$sqlHelper = Main\Application::getInstance()->getConnection()->getSqlHelper();
		$this->sqlHelper = $sqlHelper;
	}

	public function getName(): string
	{
		return $this->sqlHelper->forSql($this->name);
	}

	abstract public function getField(): string;
}
