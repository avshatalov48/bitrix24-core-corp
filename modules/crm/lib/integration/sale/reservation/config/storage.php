<?php

namespace Bitrix\Crm\Integration\Sale\Reservation\Config;

use Bitrix\Crm\Integration\Sale\Reservation\Config\Entity\Entity;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Config\Option;

class Storage
{
	private const RESERVATION_OPTION_TEMPLATE = 'catalog_reservation_';

	/**
	 * @param Entity $entity
	 */
	public static function saveEntityValues(Entity $entity): void
	{
		$scheme = $entity::getScheme();
		$values = $entity->getValues();

		$result = [];
		foreach ($scheme as $schemeItem)
		{
			if (!isset($values[$schemeItem['code']]))
			{
				continue;
			}
			$value = $values[$schemeItem['code']];

			if ($schemeItem['type'] === TypeDictionary::LIST)
			{
				$listValues = array_column($schemeItem['values'], 'code');
				if (!in_array($value, $listValues, true))
				{
					continue;
				}
			}
			elseif ($schemeItem['type'] === TypeDictionary::OPTION)
			{
				$value = (bool)$value;
			}
			elseif ($schemeItem['type'] === TypeDictionary::TEXT)
			{
				$value = (string)$value;
			}
			elseif ($schemeItem['type'] === TypeDictionary::INTEGER)
			{
				$value = (int)$value > 0 ? (int)$value : 0;
			}

			$result[$schemeItem['code']] = $value;
		}

		Option::set(
			'crm',
			self::RESERVATION_OPTION_TEMPLATE . $entity::getCode(),
			Json::encode($result)
		);
	}

	/**
	 * @param Entity $entity
	 * @return array
	 */
	public static function getEntityValues(Entity $entity): array
	{
		$result = [];

		$values = Option::get(
			'crm',
			self::RESERVATION_OPTION_TEMPLATE . $entity::getCode()
		);
		$values = $values ? Json::decode($values) : [];

		$scheme = $entity::getScheme();
		foreach ($scheme as $schemeItem)
		{
			$result[$schemeItem['code']] = $values[$schemeItem['code']] ?? $schemeItem['default'];
		}

		return $result;
	}
}
