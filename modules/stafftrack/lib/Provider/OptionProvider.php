<?php

namespace Bitrix\StaffTrack\Provider;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\StaffTrack\Model\Option;
use Bitrix\StaffTrack\Model\OptionTable;
use Bitrix\StaffTrack\Dictionary;
use Bitrix\StaffTrack\Trait\Singleton;

class OptionProvider
{
	use Singleton;

	private static array $cache = [];

	/**
	 * @param int $userId
	 * @param Dictionary\Option $option
	 * @return Option|null
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getOption(int $userId, Dictionary\Option $option): ?Option
	{
		$options = $this->getUserOptions($userId);

		return $options[$option->value] ?? null;
	}

	/**
	 * @param int $userId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getUserOptions(int $userId): array
	{
		$cachedOptions = $this->getFromCache($userId);
		if (!empty($cachedOptions))
		{
			return $cachedOptions;
		}

		$result = [];

		$optionCollection = OptionTable::query()
			->setSelect(['*'])
			->where('USER_ID', $userId)
			->exec()->fetchCollection()
		;

		foreach ($optionCollection as $option)
		{
			$result[$option->getName()] = $option;
		}

		$this->saveInCache($result);

		return $result;
	}

	/**
	 * @param int $userId
	 * @return void
	 */
	public function invalidateCache(int $userId): void
	{
		self::$cache[$userId] = [];
	}

	/**
	 * @param int $userId
	 * @return array|null
	 */
	private function getFromCache(int $userId): ?array
	{
		return self::$cache[$userId] ?? null;
	}

	/**
	 * @param array $options
	 * @return void
	 */
	private function saveInCache(array $options): void
	{
		/** @var Option $option */
		foreach ($options as $option)
		{
			self::$cache[$option->getUserId()] ??= [];
			self::$cache[$option->getUserId()][$option->getName()] = $option;
		}
	}
}
