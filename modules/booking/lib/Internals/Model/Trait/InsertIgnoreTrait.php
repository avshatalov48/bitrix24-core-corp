<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model\Trait;

use Bitrix\Main\Application;

// TODO: move to \Bitrix\Main\ORM\Data\Internal\InsertIgnoreTrait
trait InsertIgnoreTrait
{
	abstract static function getTableName(): string;

	public static function insertIgnore(array $insertFields): void
	{
		self::insertIgnoreMulti([ $insertFields ]);
	}

	public static function insertIgnoreMulti(array $insertRows): void
	{
		[$columns, $inserts] = self::prepareInsertMulti($insertRows);
		if (empty($columns) || empty($inserts))
		{
			return;
		}

		$sqlHelper = Application::getConnection()->getSqlHelper();
		$tableName = $sqlHelper->quote(self::getTableName());
		$sql = $sqlHelper->getInsertIgnore($tableName, "($columns)", "VALUES $inserts");

		Application::getConnection()->queryExecute($sql);
	}

	private static function prepareInsertMulti(array $fields): array
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();
		$inserts = [];
		$columns = [];

		foreach ($fields as $insertField)
		{
			$insert = $sqlHelper->prepareInsert(self::getTableName(), $insertField);
			$columns = $insert[0];
			$inserts[] = "($insert[1])";
		}

		return [$columns, implode(',', $inserts)];
	}
}
