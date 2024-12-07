<?php

namespace Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Integration\IM;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;
use Bitrix\Tasks\Util\User;
use Bitrix\UI;

Loc::loadMessages(__FILE__);

/**
 * Class FilterLimit
 *
 * @package Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit
 */
class FilterLimit extends Limit
{
	protected static $variableName = Bitrix24\FeatureDictionary::VARIABLE_SEARCH_LIMIT;

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
		$limit = ($limit > 0 ? $limit : static::getLimit());

		return (static::isLimitExist($limit) && static::getCurrentValue() > $limit);
	}

	/**
	 * @param array|null $params
	 * @return array|null
	 */
	public static function prepareStubInfo(array $params = null): ?array
	{
		if ($params === null)
		{
			$params = [];
		}

		if (!isset($params['REPLACEMENTS']))
		{
			$params['REPLACEMENTS'] = [];
		}
		$params['REPLACEMENTS']['#LIMIT#'] = static::getVariable();

		$prefix = 'TASKS_RESTRICTION_B24_RESTRICTION_LIMIT_FILTER_STUB';

		$helpdeskUrl = UI\Util::getArticleUrlByCode('9745327');
		$helpdeskLink = '<a href="'.$helpdeskUrl.'">'.Loc::getMessage("{$prefix}_HELPDESK_LINK").'</a>';

		$params['TITLE'] = $params['TITLE'] ?? Loc::getMessage("{$prefix}_TITLE_V2");
		$params['CONTENT'] = $params['CONTENT'] ?? Loc::getMessage("{$prefix}_CONTENT_V2", ['#HELPDESK_LINK#' => $helpdeskLink]);

		return Bitrix24::prepareStubInfo($params);
	}

	/**
	 * @param int|null $userId
	 * @param int $warningCount
	 * @throws Main\LoaderException
	 */
	public static function notifyLimitWarning(int $userId = null, int $warningCount = 0): void
	{
		if ($userId === null)
		{
			$userId = User::getId();
		}

		if (!$userId)
		{
			return;
		}

		static::setUserNotifiedCount($userId, $warningCount);

		if (Loader::includeModule('ui'))
		{
			$limit = static::getLimit();
			$prefix = 'TASKS_RESTRICTION_B24_RESTRICTION_LIMIT_FILTER_NOTIFICATION';
			$helpdeskUrl = UI\Util::getArticleUrlByCode('9745327');
			$helpdeskLink = '<a href="'.$helpdeskUrl.'">'.Loc::getMessage("{$prefix}_HELPDESK_LINK").'</a>';
			$message = Loc::getMessage($prefix, [
				'#COUNT#' => $warningCount,
				'#LIMIT#' => $limit,
				'#HELPDESK_LINK#' => $helpdeskLink,
			]);
			$messageOut = Loc::getMessage($prefix, [
				'#COUNT#' => $warningCount,
				'#LIMIT#' => $limit,
				'#HELPDESK_LINK#' => '('.Loc::getMessage("{$prefix}_HELPDESK_LINK").': '.$helpdeskUrl.')',
			]);
			$notificationFields = [
				'MESSAGE_TYPE' => IM_MESSAGE_SYSTEM,
				'TO_USER_ID' => $userId,
				'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
				'NOTIFY_MODULE' => 'tasks',
				'NOTIFY_EVENT' => 'filter_limit_notification',
				'NOTIFY_TAG' => 'TASKS|FILTER_LIMIT_NOTIFICATION',
				'NOTIFY_MESSAGE' => $message,
				'NOTIFY_MESSAGE_OUT' => $messageOut,
			];
			IM::notifyAdd($notificationFields);
		}
	}

	/**
	 * @param int|null $userId
	 * @return int
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getLimitWarningValue(int $userId = null): int
	{
		if ($userId === null)
		{
			$userId = User::getId();
		}

		if (!$userId)
		{
			return 0;
		}

		$limit = static::getLimit();
		if ($limit <= 0 || Bitrix24::isLicensePaid())
		{
			return 0;
		}

		return static::calculateLimitWarningValue(
			static::getUserNotifiedCount($userId),
			static::getCurrentValue(),
			$limit
		);
	}

	/**
	 * @param int $notifiedCount
	 * @param int $count
	 * @param int $limit
	 * @return int
	 */
	protected static function calculateLimitWarningValue(int $notifiedCount, int $count, int $limit): int
	{
		if ($count > $limit || $count <= $notifiedCount)
		{
			return 0;
		}

		$thresholds = [50, 100];
		foreach ($thresholds as $threshold)
		{
			$notificationLimit = $limit - $threshold;
			if ($notificationLimit <= 0)
			{
				continue;
			}

			if ($count >= $notificationLimit && $notifiedCount < $notificationLimit)
			{
				return $notificationLimit;
			}
		}

		return 0;
	}

	/**
	 * @param int $userId
	 * @return int
	 */
	protected static function getUserNotifiedCount(int $userId): int
	{
		return (int)User::getOption('tasks_entity_search_limit_notification', $userId, 0);
	}

	/**
	 * @param int $userId
	 * @param int $count
	 */
	protected static function setUserNotifiedCount(int $userId, int $count): void
	{
		User::setOption('tasks_entity_search_limit_notification', $count, $userId);
	}
}