<?php

namespace Bitrix\Crm\Config;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Bitrix24;

/**
 * Class Feature
 * Provides unified methods for check B24 tariff limits and Bitrix edition limits.
 *
 * @package Bitrix\Crm\Config
 */
final class Feature
{
	private const PRODUCT_LIMIT = 'crm_catalog_product_limit';

	private const PRODUCT_LIMIT_VARIABLE = 'crm_catalog_product_limit';

	/** @var null|bool sign of the presence of Bitrix24 */
	private static $bitrix24Included = null;

	/** @var array bitrix24 articles about tarif features */
	private static $bitrix24helpCodes = [
		self::PRODUCT_LIMIT => 'limit_crm_produkts_catalog',
	];

	private static $helpCodesCounter = 0;
	private static $initUi = false;

	/**
	 * @return int
	 */
	public static function getProductLimit(): int
	{
		$result = 0;
		if (self::isBitrix24())
		{
			$result = (int)Bitrix24\Feature::getVariable(self::PRODUCT_LIMIT_VARIABLE);
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public static function getProductLimitVariable(): string
	{
		return self::PRODUCT_LIMIT_VARIABLE;
	}

	/**
	 * Returns url description for help article about product limits.
	 *
	 * @return array|null
	 */
	public static function getProductLimitHelpLink(): ?array
	{
		return self::getHelpLink(self::PRODUCT_LIMIT);
	}

	/**
	 * Init ui scope for show help links on internal pages.
	 *
	 * @return void
	 */
	public static function initUiHelpScope(): void
	{
		if (!self::isBitrix24())
		{
			return;
		}
		if (self::$helpCodesCounter <= 0 || self::$initUi)
		{
			return;
		}
		if (Loader::includeModule('ui'))
		{
			self::$initUi = true;
			Main\UI\Extension::load('ui.info-helper');
		}
	}

	/**
	 * Returns javascript link to bitrx24 feature help article.
	 *
	 * @param string $featureId
	 * @return array|null
	 */
	private static function getHelpLink(string $featureId): ?array
	{
		if (!self::isBitrix24())
		{
			return null;
		}
		if (!isset(self::$bitrix24helpCodes[$featureId]))
		{
			return null;
		}
		self::$helpCodesCounter++;

		return [
			'TYPE' => 'ONCLICK',
			'LINK' => 'BX.UI.InfoHelper.show(\''.self::$bitrix24helpCodes[$featureId].'\');'
		];
	}

	/**
	 * Return true if Bitrix24 is exists.
	 *
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	private static function isBitrix24(): bool
	{
		if (self::$bitrix24Included === null)
		{
			self::$bitrix24Included = Loader::includeModule('bitrix24');
		}

		return self::$bitrix24Included;
	}
}
