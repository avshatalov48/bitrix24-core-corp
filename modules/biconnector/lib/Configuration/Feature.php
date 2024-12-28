<?php
namespace Bitrix\BIConnector\Configuration;

use Bitrix\Bitrix24;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

/**
 * Class Feature
 * Provides unified methods for check B24 tariff restrictions.
 *
 * @package Bitrix\BIConnector\Configuration
 */
final class Feature
{
	public const BI_BUILDER = 'bi_constructor';
	public const BI_BUILDER_RIGHTS = 'bi_constructor_rights';
	public const BI_EXTERNAL_ENTITIES = 'bi_constructor_external_entities';
	public const BI_BUILDER_EXPORT = 'bi_constructor_export';

	public static function isBuilderEnabled(): bool
	{
		return self::isFeatureEnabled(self::BI_BUILDER);
	}

	public static function isBiBuilderRightsEnabled(): bool
	{
		return self::isBuilderEnabled() && self::isFeatureEnabled(self::BI_BUILDER_RIGHTS);
	}

	public static function isExternalEntitiesEnabled(): bool
	{
		return
			self::isBuilderEnabled()
			&& self::isFeatureEnabled(self::BI_EXTERNAL_ENTITIES)
		;
	}

	public static function isSourceExpensesEnabled(): bool
	{
		return self::isExternalEntitiesEnabled();
	}

	public static function isBiBuilderExportEnabled(): bool
	{
		return
			self::isBuilderEnabled()
			&& self::isFeatureEnabled(self::BI_BUILDER_EXPORT)
		;
	}

	public static function checkFeatureOption(string $optionName): bool
	{
		return Option::get('biconnector', $optionName, 'N') === 'Y';
	}

	/**
	 * @param string $featureId
	 * @return bool
	 *
	 * @throws Main\LoaderException
	 */
	private static function isFeatureEnabled(string $featureId): bool
	{
		if (Loader::includeModule('bitrix24'))
		{
			return Bitrix24\Feature::isFeatureEnabled($featureId);
		}

		return true;
	}
}
