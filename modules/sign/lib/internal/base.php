<?php
namespace Bitrix\Sign\Internal;

use Bitrix\Main\EventManager;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Error;
use Bitrix\Sign\Main\User;

class BaseTable
{
	public static $internalClass = null;

	/**
	 * Returns internal class (must declarative in external class).
	 * @return string
	 */
	private static function getCallingClass(): string
	{
		if (static::$internalClass === null)
		{
			throw new \Bitrix\Main\SystemException(
				'Variable static::$internalClass must be declarative in external class.'
			);
		}

		$class = '\\' . __NAMESPACE__ . '\\' . static::$internalClass;
		if (class_exists($class))
		{
			return $class;
		}

		return '\\' . __NAMESPACE__ . '\\Integration\\' . static::$internalClass;
	}

	/**
	 * Updates entity data.
	 * @param array $data New blank data.
	 * @return bool
	 */
	public function setData(array $data): bool
	{
		if (!isset($this->data))
		{
			return false;
		}

		foreach ($data as $key => $val)
		{
			if (!array_key_exists($key, $this->data))
			{
				unset($data[$key]);
			}
		}

		$res = self::update($this->data['ID'], $data);
		if (!$res->isSuccess())
		{
			Error::getInstance()->addFromResult($res);
			return false;
		}

		foreach ($data as $key => $val)
		{
			$this->data[$key] = $val;
		}

		return true;
	}

	/**
	 * Prepares row, remove som unique data for row.
	 * @param array $row Row to duplicate.
	 * @return array
	 */
	protected function prepareRowForDuplicate(array $row): array
	{
		unset($row['ID']);
		unset($row['CREATED_BY_ID']);
		unset($row['MODIFIED_BY_ID']);
		unset($row['DATE_CREATE']);
		unset($row['DATE_MODIFY']);
		unset($row['DATE_SIGN']);
		unset($row['DATE_DOC_DOWNLOAD']);
		unset($row['DATE_DOC_VERIFY']);

		return $row;
	}

	/**
	 * Returns table's map.
	 * @return array
	 */
	public static function getMap(): array
	{
		/** @var DataManager $class */
		$class = self::getCallingClass();
		return $class::getMap();
	}

	/**
	 * Creates new record and return it new result.
	 * @param array $fields Fields array.
	 * @return AddResult
	 */
	public static function add(array $fields): AddResult
	{
		$uid = User::getInstance()->getId();
		$uid = $uid ? : 0;
		$date = new DateTime;

		if (!isset($fields['CREATED_BY_ID']))
		{
			$fields['CREATED_BY_ID'] = $uid;
		}
		if (!isset($fields['MODIFIED_BY_ID']))
		{
			$fields['MODIFIED_BY_ID'] = $uid;
		}
		if (!isset($fields['DATE_CREATE']))
		{
			$fields['DATE_CREATE'] = $date;
		}
		if (!isset($fields['DATE_MODIFY']))
		{
			$fields['DATE_MODIFY'] = $date;
		}

		/** @var DataManager $class */
		$class = self::getCallingClass();
		return $class::add($fields);
	}

	/**
	 * Updates exists record.
	 * @param int $id Record key.
	 * @param array $fields Fields array.
	 * @return Result
	 */
	public static function update(int $id, array $fields = []): Result
	{
		$uid = User::getInstance()->getId();
		$date = new DateTime;

		if (isset($fields['ID']))
		{
			unset($fields['ID']);
		}
		if (!isset($fields['MODIFIED_BY_ID']))
		{
			$fields['MODIFIED_BY_ID'] = $uid;
		}
		else if (!$fields['MODIFIED_BY_ID'])
		{
			unset($fields['MODIFIED_BY_ID']);
		}
		if (!isset($fields['DATE_MODIFY']))
		{
			$fields['DATE_MODIFY'] = $date;
		}
		if (!$fields['DATE_MODIFY'])
		{
			unset($fields['DATE_MODIFY']);
		}

		/** @var DataManager $class */
		$class = self::getCallingClass();
		return $class::update($id, $fields);
	}

	/**
	 * Deletes exists record.
	 * @param int $id Record key.
	 * @return Result
	 */
	public static function delete(int $id): Result
	{
		/** @var DataManager $class */
		$class = self::getCallingClass();
		return $class::delete($id);
	}

	/**
	 * Returns records of table.
	 * @param array $params Params array like ORM style.
	 * @return \Bitrix\Main\DB\Result
	 */
	public static function getList(array $params = []): \Bitrix\Main\DB\Result
	{
		/** @var DataManager $class */
		$class = self::getCallingClass();
		return $class::getList($params);
	}

	/**
	 * Registers calllback for internal table.
	 * @param string $code Type of callback.
	 * @param callable $callback Callback.
	 * @return void
	 */
	public static function callback(string $code, callable $callback): void
	{
		$class = self::getCallingClass();
		if (mb_substr(mb_strtolower($class), -5) == 'table')
		{
			$class = mb_substr($class, 0, -5);
			if ($class)
			{
				$eventManager = EventManager::getInstance();
				$eventManager->addEventHandler(
					'sign',
					$class . '::' . $code,
					$callback
				);
			}
		}
	}
}
