<?php

namespace Bitrix\Crm\FieldContext\Model;

use Bitrix\Crm\FieldContext\EntityFieldContextTable;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Data\DataManager;

abstract class Base extends DataManager implements EntityFieldContextTable
{
	final public static function deleteByFieldName(string $fieldName): void
	{
		$sql = new SqlExpression(
			"DELETE FROM ?# WHERE FIELD_NAME = ?s",
			static::getTableName(),
			$fieldName
		);

		$connection = Application::getConnection();
		$connection->query($sql->compile());
	}

	final public static function deleteByItemId(int $id): void
	{
		$idColumnName = (new static())->getIdColumnName();
		$sql = new SqlExpression(
			"DELETE FROM ?# WHERE {$idColumnName} = ?i",
			static::getTableName(),
			$id
		);

		$connection = Application::getConnection();
		$connection->query($sql->compile());
	}
}
