<?php
namespace Bitrix\Tasks\Util\Restriction\Bitrix24Restriction;

use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

/**
 * Class Limit
 *
 * @package Bitrix\Tasks\Util\Restriction\Bitrix24Restriction
 */
class Limit extends Bitrix24Restriction
{
	public const DEFAULT_LIMIT = 100;
	public const OPTION_LIMIT_KEY = '_tasks_restrict_limit_b';

	protected static array $listRelatedFeatures = [];

	public static function getLimitLock(string $featureId, ?string $bindElement = null): string
	{
		\Bitrix\Main\UI\Extension::load(['ui.info-helper']);

		$onLockClick = static::getLimitLockClick($featureId, $bindElement);

		$lockStyle = 'margin-right: 10px; align-self: center; cursor: pointer;';

		return <<<HTML
			<div class="tariff-lock" onclick="$onLockClick" style="$lockStyle"></div>
		HTML;
	}

	public static function getLimitLockClick(
		string $featureId,
		?string $bindElement = 'this',
		?string $analyticsSource = null,
	): string
	{
		$bindElement = $bindElement ?? 'null';
		$limitAnalyticsLabels = '{}';

		if ($analyticsSource !== null)
		{
			$limitAnalyticsLabels = \Bitrix\Main\Web\Json::encode([
				'module' => 'tasks',
				'source' => $analyticsSource,
			]);
		}

		$params = [
			'featureId' => "'$featureId'",
			'bindElement' => $bindElement,
			'limitAnalyticsLabels' => $limitAnalyticsLabels,
		];

		$params = '{' . implode(', ', array_map(
			function ($key, $value) {
				return "$key: $value";
			},
			array_keys($params),
			$params
		)) . '}';

		return "
			BX.Runtime.loadExtension('tasks.limit').then((exports) => {
				const { Limit } = exports;
				Limit.showInstance($params);
			});
		";
	}

	public static function getFeatureId(): string
	{
		return '';
	}

	public static function getLimitCode(): string
	{
		return '';
	}

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

		$optionLimit = static::getOptionLimit();
		if (!is_null($optionLimit))
		{
			$limit = $optionLimit;
			return ($limit === 0) || static::getCurrentValue() > $limit;
		}

		return (static::isLimitExist($limit) && static::getCurrentValue() > $limit);
	}

	/**
	 * Checks if limit exist
	 *
	 * @param int $limit
	 * @return bool
	 */
	public static function isLimitExist(int $limit = 0): bool
	{
		return ($limit > 0 ? true : static::getLimit() > 0);
	}

	/**
	 * The method checks whether the transferred feature is related to the current limit.
	 * That is, it is responsible for the operation of the same functionality.
	 *
	 * @param string $featureId Feature id.
	 * @return bool
	 */
	public static function isRelatedWithFeature(string $featureId): bool
	{
		return in_array($featureId, static::$listRelatedFeatures, true);
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
		return static::getTasksCount();
	}

	/**
	 * Returns limit
	 *
	 * @return int
	 */
	protected static function getLimit(): int
	{
		return max((int)static::getVariable(), 0);
	}

	/**
	 * @return int|null
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	protected static function getOptionLimit(): ?int
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return null;
		}

		$key = \CBitrix24::getLicenseType().static::OPTION_LIMIT_KEY;

		$value = Option::getRealValue('tasks', $key, '');
		if (!is_null($value))
		{
			$value = (int) $value;
		}

		return $value;
	}
}
