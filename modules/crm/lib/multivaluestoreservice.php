<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Type\DateTime;

/**
 * Storage for unique key-value pairs. Not tied to any crm functionality.
 * Like Options, you can use it to store intermediate data, but without the restrictions imposed by options.
 */
class MultiValueStoreService
{
	use Singleton;

	private const MAX_SIZE = 255;

	/**
	 * @param string $key max size 255
	 * @param string $value max size 255
	 * @return void
	 */
	public function add(string $key, string $value): void
	{
		$this->validateKeyValue($key, $value);

		$connection = Application::getConnection();
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$sql = $sqlHelper->getInsertIgnore(
			'b_crm_multi_value_store',
			' (TYPE_KEY, VALUE) ',
			sprintf("VALUES(%s, %s);", $sqlHelper->convertToDbString($key), $sqlHelper->convertToDbString($value))
		);

		$connection->query($sql);
	}

	public function set(string $key, string $value): void
	{
		$this->validateKeyValue($key, $value);

		$this->deleteAll($key);
		$this->add($key, $value);
	}

	public function get(string $key, ?int $limit = null): array
	{
		$connection = Application::getConnection();
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$sql = sprintf(
			"SELECT VALUE FROM b_crm_multi_value_store WHERE TYPE_KEY = %s",
			$sqlHelper->convertToDbString($key)
		);

		if ($limit !== null)
		{
			$sql .= ' LIMIT ' . $limit;
		}

		return array_column($connection->query($sql)->fetchAll(), 'VALUE');
	}

	public function getFirstValue(string $key): ?string
	{
		$values = $this->get($key);

		return array_shift($values);
	}

	/**
	 * Get values by key created less than $minCreatedAt. Without order.
	 *
	 * @return string[]
	 */
	public function getKeyByCreatedLt(string $key, DateTime $minCreatedAt, ?int $limit = null): array
	{
		$connection = Application::getConnection();
		$sqlHelper = Application::getConnection()->getSqlHelper();
		$sql = sprintf(
			"SELECT VALUE FROM b_crm_multi_value_store WHERE TYPE_KEY = %s AND CREATED_AT < %s",
			$sqlHelper->convertToDbString($key),
			$sqlHelper->convertToDbDateTime($minCreatedAt)
		);

		if ($limit !== null)
		{
			$sql .= ' LIMIT ' . $limit;
		}

		return array_column($connection->query($sql)->fetchAll(), 'VALUE');
	}

	public function delete(string $key, string $value): void
	{
		$connection = Application::getConnection();
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$sql = sprintf(
			"DELETE FROM b_crm_multi_value_store WHERE TYPE_KEY = %s AND VALUE = %s",
			$sqlHelper->convertToDbString($key),
			$sqlHelper->convertToDbString($value)
		);

		$connection->query($sql);
	}

	public function deleteAll(string $key): void
	{
		$connection = Application::getConnection();
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$sql = sprintf(
			"DELETE FROM b_crm_multi_value_store WHERE TYPE_KEY = %s",
			$sqlHelper->convertToDbString($key)
		);

		$connection->query($sql);
	}

	/**
	 * @throws SqlQueryException
	 */
	public function deleteAllByValue(string $value): void
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$value = $sqlHelper->convertToDbString($value);

		$sql = "
			DELETE FROM b_crm_multi_value_store
			WHERE VALUE = {$value}
		";

		$connection->query($sql);
	}

	/**
	 * @throws ArgumentOutOfRangeException
	 */
	private function validateKeyValue(string $key, string $value): void
	{
		if (mb_strlen($key) > self::MAX_SIZE)
		{
			throw new ArgumentOutOfRangeException('key', null, self::MAX_SIZE);
		}

		if (mb_strlen($value) > self::MAX_SIZE)
		{
			throw new ArgumentOutOfRangeException('value', null, self::MAX_SIZE);
		}
	}
}
