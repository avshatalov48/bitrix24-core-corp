<?php

namespace Bitrix\StaffTrack\Dictionary;

use Bitrix\Main\Localization\Loc;

enum Location: string
{
	case OFFICE = 'STAFFTRACK_LOCATION_OFFICE';
	case REMOTELY = 'STAFFTRACK_LOCATION_REMOTELY';
	case OUTSIDE = 'STAFFTRACK_LOCATION_OUTSIDE';
	case HOME = 'STAFFTRACK_LOCATION_HOME';
	case CUSTOM = 'STAFFTRACK_LOCATION_CUSTOM';
	case DELETED = 'STAFFTRACK_LOCATION_DELETED';

	/**
	 * @param string $name
	 * @return string
	 */
	public static function getName(string $name): string
	{
		return self::tryFrom($name)
			? Loc::getMessage($name)
			: $name
		;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public static function getFullName(string $name): string
	{
		return self::tryFrom($name)
			? Loc::getMessage($name . '_FULL')
			: $name
		;
	}

	/**
	 * @return array
	 */
	public static function getList(): array
	{
		$result = [];
		foreach (self::cases() as $phrase)
		{
			$result[$phrase->value] = [
				'name' => Loc::getMessage($phrase->value),
				'fullName' => Loc::getMessage($phrase->value . '_FULL')
			];
		}

		return $result;
	}
}
