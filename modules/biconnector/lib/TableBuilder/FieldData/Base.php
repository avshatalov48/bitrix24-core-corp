<?php

namespace Bitrix\BIConnector\TableBuilder\FieldData;

use Bitrix\Main;

abstract class Base
{
	protected Main\DB\PgsqlSqlHelper|Main\DB\MysqliSqlHelper $sqlHelper;

	public function __construct(
		readonly protected string $field,
		readonly protected mixed $value,
	)
	{
		$sqlHelper = Main\Application::getInstance()->getConnection()->getSqlHelper();
		$this->sqlHelper = $sqlHelper;
	}

	public function getField(): string
	{
		return $this->sqlHelper->forSql($this->field);
	}

	abstract public function getFormattedValue(): mixed;
}
