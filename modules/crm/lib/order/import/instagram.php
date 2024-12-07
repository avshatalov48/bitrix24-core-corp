<?php

namespace Bitrix\Crm\Order\Import;

use Bitrix\Crm\Order\Import\Internals\ProductTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Entity\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;

/** @internal */
class Instagram
{
	const AUTH_CACHE_DIR = '/store/import/instagram/auth/';
	const AUTH_CACHE_TIME = 3600 * 24;

	const MEDIA_CACHE_DIR = '/store/import/instagram/media/';
	const MEDIA_CACHE_TIME = 60;

	const ENABLED_OPTION = 'import_instagram_enabled';
	const STATUS_OPTION = 'import_instagram_status';
	const LAST_IMPORT_OPTION = 'import_instagram_last_time';
	const NEW_MEDIA_OPTION = 'import_instagram_new_media';
	const LAST_VIEWED_TIMESTAMP_OPTION = 'last_instagram_load_timestamp';

	const MEDIA_TYPE_IMAGE = 'IMAGE';
	const MEDIA_TYPE_CAROUSEL_ALBUM = 'CAROUSEL_ALBUM';
	const MEDIA_TYPE_VIDEO = 'VIDEO';

	const ERROR_CONNECTOR_MESSENGER_INVALID_OAUTH_ACCESS_TOKEN = "CONNECTOR_MESSENGER_INVALID_OAUTH_ACCESS_TOKEN";

	protected static $connectorName = 'fbinstagramstore';
	/** @var Provider */
	private static $provider;

	protected static $iblockId;
	protected static $sectionId;

	protected static $source = 'instagram';
	protected static $resizeImages = true;

	public static function getConnectorName()
	{
		return static::$connectorName;
	}

	public static function getStatus()
	{
		$option = Option::get('crm', self::STATUS_OPTION, '');

		if (!empty($option) && CheckSerializedData($option))
		{
			$status = @unserialize($option, ['allowed_classes' => false]);
		}
		else
		{
			$status = [
				'ACTIVE' => static::isAvailable(),
			];
		}

		return [
			'STATUS' => !empty($status['ACTIVE']) && !empty($status['CONNECTION']) && !empty($status['REGISTER']),
			'ACTIVE' => !empty($status['ACTIVE']),
			'CONNECTION' => !empty($status['CONNECTION']),
			'REGISTER' => !empty($status['REGISTER']),
		];
	}

	public static function isActiveStatus()
	{
		$status = static::getStatus();

		return !empty($status['STATUS']);
	}

	public static function setStatus(array $status)
	{
		$status = [
			'ACTIVE' => !empty($status['ACTIVE']),
			'CONNECTION' => !empty($status['CONNECTION']),
			'REGISTER' => !empty($status['REGISTER']),
		];

		Option::set('crm', self::STATUS_OPTION, serialize($status));
	}

	/**
	 * @return Provider
	 */
	private static function getProvider()
	{
		if (static::$provider === null)
		{
			static::$provider = new Provider(static::$connectorName);
		}

		return static::$provider;
	}

	/**
	 * @param string $url
	 * @return array
	 */
	public static function getConnection()
	{
		$connection = [];

		$cache = Cache::createInstance();

		if ($cache->initCache(self::AUTH_CACHE_TIME, static::$source, self::AUTH_CACHE_DIR))
		{
			$connection = $cache->getVars();
		}
		elseif ($cache->startDataCache())
		{
			/** @var \Bitrix\Main\Result $infoOAuth */
			$infoOAuth = static::getProvider()->getAuthorizationInformation('');

			if ($infoOAuth->isSuccess())
			{
				$connection = $infoOAuth->getData();
				$cache->endDataCache($connection);
			}
			else
			{
				/** @var Error $error */
				foreach ($infoOAuth->getErrorCollection() as $error)
				{
					if ($error->getCode() == self::ERROR_CONNECTOR_MESSENGER_INVALID_OAUTH_ACCESS_TOKEN)
					{
						$InvalidOauthAccessToken = true;
					}
				}

				if (!empty($InvalidOauthAccessToken))
				{
					$connection = $infoOAuth->getData();
					$connection['ERRORS'][] = new Error(
						Loc::getMessage('CRM_ORDER_IMPORT_FACEBOOK_INVALID_OAUTH_ACCESS_TOKEN'),
						self::ERROR_CONNECTOR_MESSENGER_INVALID_OAUTH_ACCESS_TOKEN
					);
				}
				else
				{
					$connection = [];
					$connection['ERRORS'][] = new Error(
						Loc::getMessage('CRM_ORDER_IMPORT_FACEBOOK_ERROR_REQUEST_INFORMATION_FROM_SERVER')
						.'<br>'.Loc::getMessage('CRM_ORDER_IMPORT_FACEBOOK_REPEATING_ERROR')
					);
				}

				$cache->abortDataCache();
				static::cleanAuthCache();
			}
		}

		return $connection;
	}

	/**
	 * @param $userId
	 * @return \Bitrix\Main\Result
	 */
	public static function deleteActiveUser($userId)
	{
		/** @var \Bitrix\Main\Result $result */
		$result = static::getProvider()->delUserActive($userId);

		if ($result->isSuccess())
		{
			static::cleanCache();
		}

		return $result;
	}

	/**
	 * @param $pageId
	 * @return \Bitrix\Main\Result
	 */
	public static function deleteActivePage($pageId)
	{
		/** @var \Bitrix\Main\Result $result */
		$result = static::getProvider()->delPageActive($pageId);

		if ($result->isSuccess())
		{
			static::cleanCache();
		}

		return $result;
	}

	/**
	 * @param $pageId
	 * @return \Bitrix\Main\Result
	 */
	public static function bindAuthorizationPage($pageId)
	{
		/** @var \Bitrix\Main\Result $result */
		$result = static::getProvider()->authorizationPage($pageId);

		if ($result->isSuccess())
		{
			static::cleanCache();
		}

		return $result;
	}

	/**
	 * @return \Bitrix\Main\Result
	 */
	public static function deleteConnector()
	{
		/** @var \Bitrix\Main\Result $result */
		$result = static::getProvider()->deleteConnector();

		static::cleanCache();

		return $result;
	}

	/**
	 * @return array
	 */
	public static function getMedia()
	{
		if (!static::isAvailable() || !static::isActiveStatus())
		{
			return [];
		}

		$cache = Cache::createInstance();

		if ($cache->initCache(self::MEDIA_CACHE_TIME, static::$source, self::MEDIA_CACHE_DIR))
		{
			return $cache->getVars();
		}

		$media = [];
		$connection = static::getConnection();

		if (!empty($connection['PAGE']['INSTAGRAM']['ID']))
		{
			if ($cache->startDataCache())
			{
				/** @var \Bitrix\Main\Result $mediaResult */
				$mediaResult = static::getProvider()->getMedia($connection['PAGE']['INSTAGRAM']['ID']);

				if ($mediaResult->isSuccess())
				{
					$mediaRaw = $mediaResult->getData();

					if (!empty($mediaRaw['MEDIA']['data']))
					{
						$media = array_filter($mediaRaw['MEDIA']['data']);
						$mostRecentMedia = reset($media);

						if (!empty($mostRecentMedia['timestamp']))
						{
							Option::set('crm', self::LAST_IMPORT_OPTION, strtotime($mostRecentMedia['timestamp']));
						}
					}
				}
			}

			$cache->endDataCache($media);
		}

		return $media;
	}

	public static function cleanCache()
	{
		static::cleanAuthCache();
		static::cleanMediaCache();
	}

	public static function cleanAuthCache()
	{
		$cache = Cache::createInstance();
		$cache->cleanDir(self::AUTH_CACHE_DIR);
	}

	public static function cleanMediaCache()
	{
		$cache = Cache::createInstance();
		$cache->cleanDir(self::MEDIA_CACHE_DIR);
	}

	public static function checkNewMedia()
	{
		if (static::checkNewMediaOption())
		{
			return true;
		}

		if (static::isAvailable() && static::isActiveStatus())
		{
			$beforeImportTimestamp = (int)Option::get('crm', self::LAST_IMPORT_OPTION, 0);

			static::getMedia();

			$afterImportTimestamp = (int)Option::get('crm', self::LAST_IMPORT_OPTION, 0);

			if ($afterImportTimestamp > $beforeImportTimestamp)
			{
				Option::set('crm', self::NEW_MEDIA_OPTION, 'Y');

				return true;
			}
		}

		return false;
	}

	public static function checkNewMediaOption()
	{
		if (!static::isAvailable() || !static::isActiveStatus())
		{
			return false;
		}

		return Option::get('crm', self::NEW_MEDIA_OPTION, 'N') === 'Y';
	}

	public static function clearNewMediaOption()
	{
		Option::set('crm', self::NEW_MEDIA_OPTION, 'N');
	}

	public static function isAvailable()
	{
		return false;
		// return Option::get('crm', self::ENABLED_OPTION, 'Y') === 'Y';
	}

	public static function isSiteTemplateImportable($siteTemplate)
	{
		$templatesWithImport = ['store-instagram/mainpage'];

		return in_array($siteTemplate, $templatesWithImport, true);
	}

	public static function getIblockId()
	{
		if (static::$iblockId === null)
		{
			static::$iblockId = \CCrmCatalog::EnsureDefaultExists();
		}

		return static::$iblockId;
	}

	public static function getSectionId()
	{
		if (static::$sectionId === null)
		{
			static::$sectionId = static::loadSectionId();
		}

		return static::$sectionId;
	}

	public static function getSectionCode()
	{
		return 'IMPORT_'.mb_strtoupper(static::$source);
	}

	public static function getProductsCount()
	{
		$products = ElementTable::getList([
			'filter' => [
				'IBLOCK_ID' => static::getIblockId(),
				'IBLOCK_SECTION_ID' => static::getSectionId(),
			],
			'count_total' => true,
		]);

		return $products->getCount();
	}

	public static function getImportedMedias($mediaIds)
	{
		$productIterator = ProductTable::getList([
			'select' => ['SOURCE_ID'],
			'filter' => [
				'=SOURCE_NAME' => static::$source,
				'@SOURCE_ID' => $mediaIds,
			],
		]);

		$products = $productIterator->fetchAll();

		return array_column($products, 'SOURCE_ID');
	}

	protected static function loadSectionId()
	{
		$filter = [
			'IBLOCK_ID' => static::getIblockId(),
			'=CODE' => static::getSectionCode(),
			'CHECK_PERMISSIONS' => 'N',
		];

		$res = \CIBlockSection::GetList([], $filter, false, ['ID']);
		if ($arr = $res->Fetch())
		{
			$sectionId = $arr['ID'];
		}
		else
		{
			$sectionId = static::createSection();
		}

		return $sectionId;
	}

	protected static function createSection()
	{
		$section = new \CIBlockSection;

		$fields = [
			'IBLOCK_ID' => static::getIblockId(),
			'ACTIVE' => 'Y',
			'NAME' => Loc::getMessage('CRM_ORDER_IMPORT_INSTAGRAM_SECTION_NAME'),
			'IBLOCK_SECTION_ID' => 0,
			'CODE' => static::getSectionCode(),
			'XML_ID' => static::$source,
			'CHECK_PERMISSIONS' => 'N',
		];

		return $section->Add($fields, true, true, static::$resizeImages);
	}

	private static function convertMoney($price, $from, $to)
	{
		if ($from === $to)
		{
			return (float)$price;
		}

		if (Loader::includeModule('currency'))
		{
			return \CCrmCurrency::ConvertMoney($price, $from, $to);
		}

		$exchangeRate = 1.0;
		// Using hardcoded exchange rates for Rub
		if ($from === 'RUB')
		{
			if ($to === 'EUR')
			{
				$exchangeRate = 78.8;
			}
			elseif ($to === 'USD')
			{
				$exchangeRate = 67.7;
			}
			elseif ($to === 'UAH')
			{
				$exchangeRate = 2.4;
			}
		}

		return round($price / $exchangeRate, 2);
	}

	public static function onAfterIblockElementDelete($fields)
	{
		ProductTable::deleteByProductId($fields['ID']);
	}

	protected static function getLastError()
	{
		return \CCrmProduct::GetLastError();
	}

	protected static function generateProductCode(string $name): string
	{
		return
			(new \CIBlockElement())
				->generateMnemonicCode(uniqid($name), static::getIblockId())
		;
	}

	protected static function addProduct($fields)
	{
		$currencyId = \CCrmCurrency::GetBaseCurrencyID();

		$addFields = [
			'CATALOG_ID' => static::getIblockId(),
			'SECTION_ID' => static::getSectionId(),
			'NAME' => $fields['NAME'],
			'CODE' => static::generateProductCode($fields['NAME']),
			'DESCRIPTION' => $fields['DESCRIPTION'],
			'CURRENCY_ID' => $currencyId,
			'ACTIVE' => 'Y',
			'SORT' => 100,
			'XML_ID' => static::$source.'_'.$fields['ID'],
		];

		if (isset($fields['PRICE']))
		{
			$addFields['PRICE'] = static::convertMoney($fields['PRICE'], 'RUB', $currencyId);
		}

		$productId = \CCrmProduct::Add($addFields);

		if ($productId)
		{
			$settings = $fields['SOURCE_DATA'];

			$connection = static::getConnection();

			if (!empty($connection['PAGE']['INSTAGRAM']['USERNAME']))
			{
				$settings['account_id'] = $connection['PAGE']['INSTAGRAM']['ID'];
				$settings['account_name'] = $connection['PAGE']['INSTAGRAM']['NAME'];
				$settings['account_username'] = $connection['PAGE']['INSTAGRAM']['USERNAME'];
			}

			ProductTable::add([
				'PRODUCT_ID' => $productId,
				'SOURCE_NAME' => static::$source,
				'SOURCE_ID' => $fields['ID'],
				'SETTINGS' => $settings,
			]);
		}

		return $productId;
	}

	public static function import($products)
	{
		$result = new Result();

		if (!empty($products) && is_array($products))
		{
			$added = [];
			$errors = [];

			foreach ($products as $product)
			{
				$productId = static::addProduct($product);

				if ($productId === false)
				{
					$error = static::getLastError();
					$errors[] = new Error(Loc::getMessage('CRM_ORDER_IMPORT_INSTAGRAM_ERROR_ADD', [
						'#NAME#' => !empty($product['NAME']) ? ' "'.$product['NAME'].'"' : '',
						'#ERROR#' => !empty($error) ? ': '.lcfirst($error) : '',
					]));
				}
				else
				{
					$added[$product['ID']] = $productId;
				}
			}

			$result->setData([
				'addedItems' => $added,
				'iblockId' => static::getIblockId(),
				'sectionId' => static::getSectionId(),
			]);

			if (!empty($errors))
			{
				$result->addErrors($errors);
			}
		}

		return $result;
	}

	public static function getCurrentUri()
	{
		$request = Context::getCurrent()->getRequest();
		$server = Context::getCurrent()->getServer();

		$uri = ($request->isHttps() ? 'https://' : 'http://').$server->getServerName();
		$uri .= ':'.$server->getServerPort();
		$uri .= $server->getRequestUri();

		return $uri;
	}
}