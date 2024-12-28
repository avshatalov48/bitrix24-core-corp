<?php
namespace Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Socialnetwork\Helper\Feature;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

/**
 * Class TaskLimit
 *
 * @package Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit
 */
class ScrumLimit extends Limit
{
	protected static $variableName = Bitrix24\FeatureDictionary::VARIABLE_SCRUM_CREATE_LIMIT;

	/**
	 * Checks if limit exceeded
	 *
	 * @param int $limit
	 * @return bool
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function isLimitExceeded(int $limit = 0): bool
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return false;
		}

		if (self::isFeatureEnabledByTrial())
		{
			return false;
		}

		$limit = ($limit > 0 ? $limit : static::getLimit());

		if ($limit === 0)
		{
			return true;
		}

		return (static::getCurrentValue() >= $limit);
	}

	public static function getSidePanelId(int $limit = 0): string
	{
		return 'limit_' . Feature::SCRUM_CREATE;
	}

	/**
	 * Returns current value to compare with limit
	 *
	 * @return int
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected static function getCurrentValue(): int
	{
		if (Loader::includeModule('socialnetwork'))
		{
			$query = WorkgroupTable::query();

			$query->setSelect(['ID']);
			$query->setOrder(['ID' => 'DESC']);
			$query->countTotal(true);

			$query->whereNotNull('SCRUM_MASTER_ID');

			$result = $query->exec();

			return $result->getCount();
		}

		return 0;
	}

	public static function isFeatureEnabled(): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		if (Feature::isFeatureEnabled(Feature::SCRUM_CREATE))
		{
			return true;
		}

		return false;
	}

	public static function isFeatureEnabledByTrial(): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		if (Feature::isFeatureEnabledByTrial(Feature::SCRUM_CREATE))
		{
			return true;
		}

		return false;
	}

	public static function canTurnOnTrial(): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		return Feature::canTurnOnTrial(Feature::SCRUM_CREATE);
	}

	public static function turnOnTrial(): void
	{
		Feature::turnOnTrial(Feature::SCRUM_CREATE);
	}

	public static function getFeatureId(): string
	{
		return Feature::SCRUM_CREATE;
	}

	public static function getLimitCode(): string
	{
		return self::getSidePanelId();
	}
}
