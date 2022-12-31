<?php

namespace Bitrix\Crm\Config;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Crm;
use Bitrix\Iblock;

class State
{
	/** @var int */
	private static $elementCount;
	/** @var array */
	private static $iblockList = [];

	/**
	 * Returns information about exceeding the number of goods in the landing for the information block.
	 *
	 * @param int|null $iblockId		Iblock Id.
	 * @return array|null
	 */
	public static function getExceedingProductLimit(?int $iblockId = null): ?array
	{
		if ($iblockId === null)
		{
			$iblockId = Crm\Product\Catalog::getDefaultId();
		}

		if ($iblockId === null || $iblockId <= 0)
		{
			return null;
		}

		if (!ModuleManager::isModuleInstalled('bitrix24'))
		{
			return null;
		}

		if (!self::checkIblockId($iblockId))
		{
			return null;
		}

		return self::checkIblockLimit($iblockId);
	}

	/**
	 * OnIBlockElementAdd event handler. Do not use directly.
	 *
	 * @param array &$fields
	 * @return bool
	 */
	public static function handleBeforeIblockElementAdd(array &$fields): bool
	{
		if (!self::checkElementIblockId($fields))
		{
			return true;
		}

		$limit = self::checkIblockLimit((int)$fields['IBLOCK_ID']);
		if (empty($limit))
		{
			return true;
		}

		self::setProductLimitError($limit['MESSAGE']);

		return false;
	}

	/**
	 * OnAfterIBlockElementAdd event handler. Do not use directly.
	 *
	 * @param array &$fields
	 * @return void
	 */
	public static function handleAfterIblockElementAdd(array &$fields): void
	{
		if ($fields['RESULT'] === false)
			return;

		if (!self::checkElementIblockId($fields))
			return;

		self::$elementCount = null;
	}

	/**
	 * OnAfterIBlockElementDelete event handler. Do not use directly.
	 *
	 * @param array $fields
	 * @return void
	 */
	public static function handleAfterIblockElementDelete(array $fields): void
	{
		if (!self::checkElementIblockId($fields))
			return;

		self::$elementCount = null;
	}

	/**
	 * @param array $fields
	 * @return bool
	 */
	private static function checkElementIblockId(array $fields): bool
	{
		if (!isset($fields['IBLOCK_ID']))
		{
			return false;
		}
		return self::checkIblockId((int)$fields['IBLOCK_ID']);
	}

	/**
	 * @param int $iblockId
	 * @return bool
	 */
	private static function checkIblockId(int $iblockId): bool
	{
		if ($iblockId <= 0)
		{
			return false;
		}
		if (!isset(self::$iblockList[$iblockId]))
		{
			$result = true;
			if (!ModuleManager::isModuleInstalled('bitrix24'))
			{
				$result = false;
			}
			if ($iblockId !== (int)Crm\Product\Catalog::getDefaultId())
			{
				$result = false;
			}
			self::$iblockList[$iblockId] = $result;
		}

		return self::$iblockList[$iblockId];
	}

	/**
	 * Returns products limit.
	 *
	 * @param int $iblockId
	 * @return array|null
	 * 	keys are case sensitive:
	 * 		<ul>
	 * 		<li>int COUNT
	 * 		<li>int LIMIT
	 * 		<li>string MESSAGE_ID
	 * 		</ul>
	 */
	private static function checkIblockLimit(int $iblockId): ?array
	{
		$result = self::getIblockLimit($iblockId);
		if (
			$result['LIMIT'] === 0
			|| $result['COUNT'] < $result['LIMIT']
		)
		{
			return null;
		}
		$result['MESSAGE'] = self::getProductLimitError($result);
		unset($result['MESSAGE_ID']);
		$result['HELP_MESSAGE'] = Feature::getProductLimitHelpLink();

		return $result;
	}

	/**
	 * @param int $iblockId
	 * @return array
	 * 	keys are case sensitive:
	 * 		<ul>
	 * 		<li>int COUNT
	 * 		<li>int LIMIT
	 * 		<li>string MESSAGE_ID
	 *		</ul>
	 */
	private static function getIblockLimit(int $iblockId): array
	{
		$result = [
			'COUNT' => 0,
			'LIMIT' => Feature::getProductLimit(),
			'MESSAGE_ID' => 'CRM_STATE_ERR_CATALOG_PRODUCT_LIMIT'
		];
		if ($result['LIMIT'] === 0)
		{
			return $result;
		}
		$result['COUNT'] = self::getElementCount($iblockId);

		return $result;
	}

	/**
	 * @param int $iblockId
	 * @return int
	 */
	private static function getElementCount(int $iblockId): int
	{
		if (self::$elementCount === null)
		{
			self::$elementCount = 0;
			if (Loader::includeModule('iblock'))
			{
				self::$elementCount = Iblock\ElementTable::getCount(
					[
						'=IBLOCK_ID' => $iblockId,
						'=WF_STATUS_ID' => 1,
						'==WF_PARENT_ELEMENT_ID' => null,
					],
					[
						'ttl' => 86400,
					]
				);
			}
		}
		return self::$elementCount;
	}

	/**
	 * @param array $limit
	 * @return string|null
	 */
	private static function getProductLimitError(array $limit): ?string
	{
		if (!isset($limit['COUNT']) || !isset($limit['LIMIT']) || !isset($limit['MESSAGE_ID']))
		{
			return null;
		}

		return Loc::getMessage(
			$limit['MESSAGE_ID'],
			[
				'#COUNT#' => $limit['COUNT'],
				'#LIMIT#' => $limit['LIMIT']
			]
		);
	}

	/**
	 * Send error.
	 *
	 * @param string $errorMessage
	 * @return void
	 */
	private static function setProductLimitError(string $errorMessage): void
	{
		global $APPLICATION;

		$error = new \CAdminException([
			[
				'text' => $errorMessage,
			]
		]);
		$APPLICATION->ThrowException($error);
	}

	public static function getProductPriceChangingNotification(): ?array
	{
		return [
			'MESSAGE' => Loc::getMessage('CRM_STATE_CATALOG_PRODUCT_PRICE_CHANGING_BLOCKED'),
			'ARTICLE_CODE' => '14842358'
		];
	}
}
