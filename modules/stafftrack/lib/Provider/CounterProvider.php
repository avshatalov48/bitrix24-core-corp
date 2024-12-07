<?php

namespace Bitrix\StaffTrack\Provider;

use Bitrix\StaffTrack\Dictionary\Mute;
use Bitrix\StaffTrack\Feature;
use Bitrix\StaffTrack\Model\Counter;
use Bitrix\StaffTrack\Model\CounterTable;
use Bitrix\StaffTrack\Trait\Singleton;

class CounterProvider
{
	use Singleton;

	private static array $cache = [];

	/**
	 * @param int $userId
	 * @return Counter|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function get(int $userId): ?Counter
	{
		if (!array_key_exists($userId, $this::$cache))
		{
			self::$cache[$userId] = $this->load($userId);
		}

		return self::$cache[$userId];
	}

	/**
	 * @param int $userId
	 * @return Counter|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function load(int $userId): ?Counter
	{
		return CounterTable::query()
			->setSelect(['*'])
			->setLimit(1)
			->setCacheTtl(86400)
			->where('USER_ID', $userId)
			->exec()->fetchObject()
		;
	}

	/**
	 * @param int $userId
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isNeededToShow(int $userId): bool
	{
		if (!Feature::isCheckInEnabledBySettings() || !Feature::isCheckInEnabled())
		{
			return false;
		}

		$counter = $this->get($userId);

		if (!$counter)
		{
			return false;
		}

		if (Mute::isMutedStatus($counter))
		{
			return false;
		}

		return !ShiftProvider::getInstance($userId)->hasActiveShift();
	}
}