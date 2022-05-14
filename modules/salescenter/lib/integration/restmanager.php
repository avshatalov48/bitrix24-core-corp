<?php

namespace Bitrix\SalesCenter\Integration;

use Bitrix\Main,
	Bitrix\Rest;

class RestManager extends Base
{
	private const DEFAULT_CACHE_TTL = 43200;
	private const TAG_CACHE_TTL = 3600;
	private const ACTION_BOX_CACHE_TTL = 3600;

	public const TAG_SMSPROVIDER_SMS = 'sms';
	public const TAG_SMSPROVIDER_PARTNERS = 'partners';
	public const TAG_SMSPROVIDER_RECOMMENDED = 'recommended';

	public const TAG_PAYSYSTEM_PAYMENT = 'payment';
	public const TAG_PAYSYSTEM_MAKE_PAYMENT = 'make_payment';
	public const TAG_PAYSYSTEM_RECOMMENDED = 'recommended';
	public const TAG_PAYSYSTEM_PARTNERS = 'partners';

	public const TAG_DELIVERY = 'delivery';
	public const TAG_DELIVERY_MAKE_DELIVERY = 'make_delivery';
	public const TAG_DELIVERY_RECOMMENDED = 'recommended';

	public const TAG_SALESCENTER = 'salescenter';
	public const TAG_SALES_CENTER = 'sales_center';
	public const TAG_PARTNERS = 'partners';

	public const ACTIONBOX_PLACEMENT_PAYMENT = 'payment';
	public const ACTIONBOX_PLACEMENT_DELIVERY = 'delivery';
	public const ACTIONBOX_PLACEMENT_SMS = 'sms';

	protected function getModuleName()
	{
		return 'rest';
	}

	/**
	 * @param array $tag
	 * @param integer|bool $page
	 * @param integer|bool $pageSize
	 * @return array|bool|mixed|null
	 * @throws Main\SystemException
	 */
	public function getByTag(array $tag, $page = false, $pageSize = false)
	{
		$cacheId = md5(serialize([$tag, $page, $pageSize]));
		$cachePath = '/salescenter/saleshub/tag/';
		$cache = Main\Application::getInstance()->getCache();
		if($cache->initCache(self::TAG_CACHE_TTL, $cacheId, $cachePath))
		{
			$marketplaceApps = $cache->getVars();
		}
		else
		{
			$marketplaceApps = Rest\Marketplace\Client::getByTag($tag, $page, $pageSize);
			if(!empty($marketplaceApps['ITEMS']))
			{
				$cache->startDataCache();
				$cache->endDataCache($marketplaceApps);
			}
		}

		return $marketplaceApps;
	}

	/**
	 * @param string $code
	 * @return array
	 * @throws Main\SystemException
	 */
	public function getMarketplaceAppByCode(string $code): array
	{
		$cacheId = "salescenter_app_{$code}";
		$cachePath = "/salescenter/saleshub/app/{$code}/";
		$cache = Main\Application::getInstance()->getCache();
		if($cache->initCache(self::DEFAULT_CACHE_TTL, $cacheId, $cachePath))
		{
			$marketplaceApp = $cache->getVars();
		}
		else
		{
			$marketplaceApp = Rest\Marketplace\Client::getApp($code);
			if (isset($marketplaceApp['ITEMS']))
			{
				$marketplaceApp = $marketplaceApp['ITEMS'];
			}

			if(isset($marketplaceApp['NAME']))
			{
				$cache->startDataCache();
				$cache->endDataCache($marketplaceApp);
			}
		}

		return (is_array($marketplaceApp) && isset($marketplaceApp['NAME'])) ? $marketplaceApp : [];
	}

	/**
	 * @param string $category
	 * @return mixed
	 * @throws Main\SystemException
	 */
	public function getMarketplaceAppsCount(string $category)
	{
		$cacheId = "salescenter_categoty_{$category}_count";
		$cachePath = "/salescenter/saleshub/count/{$category}/";
		$cache = Main\Application::getInstance()->getCache();

		if ($cache->initCache(self::DEFAULT_CACHE_TTL, $cacheId, $cachePath))
		{
			$categoryItems = $cache->getVars();
		}
		else
		{
			$categoryItems = Rest\Marketplace\Client::getCategory($category, 0, 1);
			if (is_array($categoryItems))
			{
				$cache->startDataCache();
				$cache->endDataCache($categoryItems);
			}
		}

		return $categoryItems['PAGES'] ?? 0;
	}

	/**
	 * @param string $category
	 * @return array
	 * @throws Main\SystemException
	 */
	public function getMarketplaceAppCodeList(string $category): array
	{
		$cacheId = "salescenter_category_{$category}_codes";
		$cachePath = "/salescenter/saleshub/codes/{$category}/";
		$cache = Main\Application::getInstance()->getCache();

		$appCodeList = [];
		if ($cache->initCache(self::DEFAULT_CACHE_TTL, $cacheId, $cachePath))
		{
			$appCodeList = $cache->getVars();
		}
		else
		{
			$page = 1;
			do
			{
				$categoryItems = Rest\Marketplace\Client::getCategory($category, $page, 100);
				if (!is_array($categoryItems)
					|| isset($categoryItems['ERROR'])
					|| empty($categoryItems['ITEMS'])
				)
				{
					break;
				}

				foreach ($categoryItems['ITEMS'] as $item)
				{
					$appCodeList[] = $item['CODE'];
				}
				$page++;
			}
			while((int)$categoryItems['PAGES'] !== (int)$categoryItems['CUR_PAGE']);

			if ($appCodeList)
			{
				$cache->startDataCache();
				$cache->endDataCache($appCodeList);
			}
		}

		return $appCodeList;
	}

	/**
	 * @param $placement
	 * @param null $userLang
	 * @return array
	 */
	public function getActionboxItems($placement, $userLang = null): array
	{
		$userLang = $userLang ?? LANGUAGE_ID;

		$cacheId = "actionbox_items_{$placement}_{$userLang}";
		$cachePath = "/salescenter/saleshub/actionboxitems/{$placement}/";
		$cache = Main\Data\Cache::createInstance();

		if ($cache->initCache(self::ACTION_BOX_CACHE_TTL, $cacheId, $cachePath))
		{
			$actionboxItems = $cache->GetVars();
		}
		else
		{
			$actionboxItems = Rest\Marketplace\MarketplaceActions::getItems($placement, $userLang);
			$cache->startDataCache();
			$cache->endDataCache($actionboxItems);
		}

		return $actionboxItems;
	}

	public function hasDeliveryMarketplaceApp(): bool
	{
		if (!$this->isEnabled())
		{
			return false;
		}

		$zone = $this->getZone();
		$partnerItems = $this->getByTag([
			self::TAG_DELIVERY,
			self::TAG_DELIVERY_RECOMMENDED,
			$zone
		]);

		return !empty($partnerItems['ITEMS']);
	}

	private function getZone()
	{
		if (Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			$zone = \CBitrix24::getPortalZone();
		}
		else
		{
			$iterator = Main\Localization\LanguageTable::getList([
				'select' => ['ID'],
				'filter' => ['=DEF' => 'Y', '=ACTIVE' => 'Y']
			]);
			$row = $iterator->fetch();
			$zone = $row['ID'];
		}

		return $zone;
	}
}
