<?php

namespace Bitrix\Tasks\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

trait InsertIgnoreTrait
{
	public function insertIgnore(): Result
	{
		$result = new Result();
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$query = $helper->getInsertIgnore(
			static::$dataClass::getTableName(),
			$this->quoteInsertFields(),
			"VALUES {$this->getInsertValues()}"
		);

		try
		{
			Application::getConnection()->query($query);
		}
		catch (SqlQueryException $e)
		{
			$result->addError(Error::createFromThrowable($e));
		}

		return $result;
	}

	private function quoteInsertFields(): string
	{
		$fields = $this->getInsertFields();
		$helper = Application::getConnection()->getSqlHelper();
		$quotedFields = array_map(fn(string $field): string => $helper->quote($field), $fields);

		return '(' . implode(',', $quotedFields) . ')';
	}
}