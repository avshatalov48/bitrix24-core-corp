<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking;

use Bitrix\Main\Loader;
use Bitrix\Intranet;
use Bitrix\Landing;
use Bitrix\Crm\Communication;
use Bitrix\Crm\Integration\Bitrix24\Product;

/**
 * Class Provider
 *
 * @package Bitrix\Crm\Tracking
 */
class Provider
{
	/**
	 * Get feedback parameters.
	 *
	 * @return array
	 */
	public static function getFeedbackParameters()
	{
		return [
			'ID' => 'crm-tracking',
			'FORMS' => [
				['zones' => ['com.br'], 'id' => '70','lang' => 'br', 'sec' => 'ro93se'],
				['zones' => ['es'], 'id' => '72','lang' => 'la', 'sec' => 'p94c2f'],
				['zones' => ['de'], 'id' => '74','lang' => 'de', 'sec' => 'nb1umg'],
				['zones' => ['ua'], 'id' => '78','lang' => 'ua', 'sec' => 'ga5hxb'],
				['zones' => ['ru', 'by', 'kz'], 'id' => '80','lang' => 'ru', 'sec' => 'cw9dbl'],
				['zones' => ['en'], 'id' => '76','lang' => 'en', 'sec' => 'stvrqm'],
			],
			'PRESETS' => []
		];
	}

	/**
	 * Get channels.
	 *
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getChannels()
	{
		$list = [
			[
				'CODE' => Channel\Base::Site24,
				'ICON_CLASS' => 'ui-icon ui-icon-service-site-b24',
				'CONFIGURED' => true,
				'CONFIGURABLE' => false,
			],
			[
				'CODE' => Channel\Base::Shop24,
				'ICON_CLASS' => 'ui-icon ui-icon-service-estore',
				'CONFIGURED' => true,
				'CONFIGURABLE' => false,
			],
			[
				'CODE' => Channel\Base::Site,
				'ICON_CLASS' => 'ui-icon ui-icon-service-site',
				'CONFIGURABLE' => true,
				'CONFIGURED' => !empty(self::getReadySites()),
			],
			[
				'CODE' => Channel\Base::Call,
				'ICON_CLASS' => 'ui-icon ui-icon-service-calltracking',
				'CONFIGURED' => self::hasSourcesWithFilledPool(Communication\Type::PHONE),
				'CONFIGURABLE' => true,
			],
			[
				'CODE' => Channel\Base::Mail,
				'ICON_CLASS' => 'ui-icon ui-icon-service-envelope',
				'CONFIGURED' => self::hasSourcesWithFilledPool(Communication\Type::EMAIL),
				'CONFIGURABLE' => true,
			],
			[
				'CODE' => Channel\Base::Order,
				'ICON_CLASS' => 'ui-icon ui-icon-service-estore',
				'CONFIGURED' => Channel\Order::isConfigured(),
				'CONFIGURABLE' => true,
			],
		];

		foreach ($list as $index => $item)
		{
			$channel = Channel\Factory::create($item['CODE']);
			$item['NAME'] = $channel->getGridName();
			$item['SHORT_NAME'] = $channel->getName();
			$item['ITEMS'] = $channel->getItems();
			$list[$index] = $item;
		}

		if (!Loader::includeModule('intranet'))
		{
			return $list;
		}

		$existedCodes = array_column($list, 'CODE');
		$contactCenter = new Intranet\ContactCenter();
		$itemList = $contactCenter->getItems([
			'MODULES' => ['imopenlines', 'crm'],
			'ACTIVE' => 'Y', 'IS_LOAD_INNER_ITEMS' => 'N',
		]);
		foreach ($itemList as $moduleId => $items)
		{
			foreach ($items as $itemId => $item)
			{
				if ($itemId === 'calltracking')
				{
					continue;
				}

				$itemId = $itemId === 'widget' ? Channel\Base::Button : $itemId;
				if (!$item['SELECTED'] || in_array($itemId, $existedCodes))
				{
					continue;
				}
				$list[] = [
					'CODE' => $itemId,
					'NAME' => $item['NAME'],
					'SHORT_NAME' => $item['NAME'],
					'ITEMS' => [],
					'ICON_CLASS' => $item['LOGO_CLASS'],
					'CONFIGURED' => true,
					'CONFIGURABLE' => false,
				];
			}
		}

		return $list;
	}

	/**
	 * Get sources.
	 *
	 * @return array
	 */
	public static function getAvailableSources()
	{
		$adsSources = self::getStaticSources();
		$adsSources = array_combine(array_column($adsSources, 'CODE'), $adsSources);

		$list = self::getActualSources();
		foreach ($list as $index => $item)
		{
			if ($item['CODE'] && isset($adsSources[$item['CODE']]))
			{
				unset($adsSources[$item['CODE']]);
			}
		}

		foreach ($adsSources as $index => $item)
		{
			$list[] = $item + [
				'ID' => null,
				'UTM_SOURCE' => null,
				'CONFIGURED' => false,
				'ICON_COLOR' => '',
			];
		}

		usort($list, [__CLASS__, 'sortSourcesByCode']);

		return $list;
	}

	/**
	 * Get static sources.
	 *
	 * @return array
	 */
	public static function getStaticSources()
	{
		$list = [
			[
				'CODE' => 'google',
				'ICON_CLASS' => 'ui-icon ui-icon-service-google-ads',
				'ICON_COLOR' => '#3889db',
				'CONFIGURABLE' => true,
				'HAS_PATH_TO_LIST' => true,
				'REF_DOMAIN' => [
					/*
					['regexp' => 'www\.google\.[A-Za-z]{2,3}'],
					['regexp' => 'www\.google\.com\.[A-Za-z]{2,3}'],
					'www.g.cn',
					*/
				],
			],
			[
				'CODE' => 'fb',
				'ICON_CLASS' => 'ui-icon ui-icon-service-fb',
				'ICON_COLOR' => '#38659f',
				'CONFIGURABLE' => true,
				'HAS_PATH_TO_LIST' => true,
				'REF_DOMAIN' => Settings::isSocialRefDomainUsed()
					? ['www.facebook.com', 'facebook.com']
					: [],
			],
			[
				'CODE' => 'instagram',
				'ICON_CLASS' => 'ui-icon ui-icon-service-instagram',
				'ICON_COLOR' => '#d56c9a',
				'CONFIGURABLE' => true,
				'HAS_PATH_TO_LIST' => true,
				'REF_DOMAIN' => Settings::isSocialRefDomainUsed()
					? ['www.instagram.com', 'instagram.com']
					: [],
			],
		];

		if (Product::isRegionRussian())
		{
			$list[] = [
				'CODE' => 'vk',
				'ICON_CLASS' => 'ui-icon ui-icon-service-vk',
				'ICON_COLOR' => '#3871ba',
				'CONFIGURABLE' => true,
				'HAS_PATH_TO_LIST' => true,
				'REF_DOMAIN' => Settings::isSocialRefDomainUsed()
					? ['www.vk.com', 'vk.com']
					: [],
			];
			$list[] = [
				'CODE' => 'yandex',
				'ICON_CLASS' => 'ui-icon ui-icon-service-ya-direct',
				'ICON_COLOR' => '#ffce00',
				'CONFIGURABLE' => true,
				'HAS_PATH_TO_LIST' => true,
				'REF_DOMAIN' => [
					/*
					'ya.ru', 'yandex.asia', 'yandex.mobi',
					['regexp' => 'yandex\.[A-Za-z]{2,6}'],
					*/
				],
			];
		}

		$list[] = [
			'ID' => 0,
			'CODE' => 'organic',
			'DESCRIPTION' => Source\Base::getDescriptionByCode('organic'),
			'ICON_CLASS' => 'ui-icon ui-icon-service-organic',
			'ICON_COLOR' => '',
			'CONFIGURED' => true,
			'CONFIGURABLE' => false,
			'HAS_PATH_TO_LIST' => true,
		];

		foreach ($list as $index => $item)
		{
			$item['NAME'] = Source\Base::getNameByCode($item['CODE']);
			$item['SHORT_NAME'] = Source\Base::getShortNameByCode($item['CODE']);
			$list[$index] = $item;
		}

		return $list;
	}

	/**
	 * Return true if it has sources with filled pool.
	 *
	 * @param int $typeId Communication type ID.
	 * @return bool
	 */
	public static function hasSourcesWithFilledPool($typeId)
	{
		$typeName = Communication\Type::resolveName($typeId);
		foreach (self::getActualSources() as $source)
		{
			if (empty($source[$typeName]))
			{
				continue;
			}

			return true;
		}

		return false;
	}

	/**
	 * Return true if it has ready ad sources.
	 *
	 * @return bool
	 */
	public static function hasReadyAdSources()
	{
		if (!Manager::isAdAccessible())
		{
			return false;
		}

		foreach (self::getActualAdSources() as $source)
		{
			$ad = new Analytics\Ad($source);
			if ($ad->isConnected())
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Get actual ad sources.
	 *
	 * @return array
	 */
	public static function getActualAdSources()
	{
		$list = [];
		foreach (self::getActualSources() as $source)
		{
			if (!Analytics\Ad::isSupported($source['CODE']))
			{
				continue;
			}

			$list[] = $source;
		}

		return $list;
	}

	/**
	 * Get actual sources.
	 *
	 * @return array
	 */
	public static function getActualSources()
	{
		$adsSources = self::getStaticSources();
		$adsSources = array_combine(array_column($adsSources, 'CODE'), $adsSources);
		$sourceFields = Internals\SourceFieldTable::getSourceFields();

		$list = Internals\SourceTable::getList([
			'select' => ['ID', 'CODE', 'NAME', 'ICON_COLOR', 'AD_CLIENT_ID', 'AD_ACCOUNT_ID'],
			'filter' => ['=ACTIVE' => 'Y'],
			'order' => ['ID' => 'ASC'],
			'cache' => ['ttl' => 3600]
		])->fetchAll();
		foreach ($list as $index => $item)
		{
			if ($item['CODE'] && isset($adsSources[$item['CODE']]))
			{
				$item = ['NAME' => $item['NAME']] + $adsSources[$item['CODE']] + $item;
			}

			if (!$item['CODE'])
			{
				$userSources[] = $item['ID'];
				$item['ICON_COLOR'] = $item['ICON_COLOR'] ?: '#55d0e0';
			}

			if (isset($sourceFields[$item['ID']]))
			{
				$item = $item + $sourceFields[$item['ID']];
			}

			$list[$index] = $item + [
				'DESCRIPTION' => Source\Base::getDescriptionByCode($item['CODE'], $item['NAME']),
				'ICON_CLASS' => 'ui-icon ui-icon-service-universal',
				'CONFIGURED' => !empty($item['UTM_SOURCE']),
				'HAS_PATH_TO_LIST' => true,
			];

			$item['ID'] = (int) $item['ID'];
		}

		foreach ($adsSources as $sourceCode => $source)
		{
			if (!$source['CONFIGURED'] || $source['CONFIGURABLE'])
			{
				continue;
			}

			$list[] = $source;
		}

		usort($list, [__CLASS__, 'sortSourcesByCode']);

		return $list;
	}

	/**
	 * Get ready sources.
	 *
	 * @return array
	 */
	public static function getReadySources()
	{
		$list = [];
		foreach (self::getActualSources() as $source)
		{
			if (empty($source['UTM_SOURCE']))
			{
				continue;
			}
			
			/*
			if (empty($source['EMAIL']) && empty($source['PHONE']))
			{
				continue;
			}
			*/

			$list[] = $source;
		}

		return $list;
	}

	/**
	 * Get ready sites.
	 *
	 * @return array
	 */
	public static function getReadySites()
	{
		return Internals\SiteTable::getList([
			'filter' => [
				'=IS_INSTALLED' => 'Y',
				'=ACTIVE' => 'Y'
			]
		])->fetchAll();
	}

	/**
	 * Sort sources.
	 *
	 * @param array $sourceA Source A.
	 * @param array $sourceB Source B.
	 * @return int
	 */
	public static function sortSourcesByCode(array $sourceA, array $sourceB)
	{
		$weights = array_flip(array_column(self::getStaticSources(), 'CODE'));
		$weightA = ($sourceA['CODE'] && isset($weights[$sourceA['CODE']])) ?
			$weights[$sourceA['CODE']]
			:
			100;
		$weightB = ($sourceB['CODE'] && isset($weights[$sourceB['CODE']])) ?
			$weights[$sourceB['CODE']]
			:
			100;

		return $weightA > $weightB ? 1 : 0;
	}

	/**
	 * Get b24 sites.
	 *
	 * @param bool $isStore Return b24 e-stores.
	 * @return array
	 */
	public static function getB24Sites($isStore = null)
	{
		if (!Loader::includeModule('landing'))
		{
			return [];
		}

		$filter = [
			'=ACTIVE' => 'Y'
		];
		if (is_bool($isStore))
		{
			$filter['=TYPE'] = $isStore ?  'STORE' : 'PAGE';
		}

		$list = Landing\Site::getList([
			'select' => [
				'ID', 'TITLE', 'DOMAIN_NAME' => 'DOMAIN.DOMAIN',
				'DOMAIN_PROTOCOL' => 'DOMAIN.PROTOCOL'
			],
			'filter' => $filter
		])->fetchAll();

		$list = array_filter(
			$list,
			function ($item)
			{
				return !empty($item['DOMAIN_NAME']);
			}
		);
		sort($list);

		$disabledList = array_column(Internals\SiteB24Table::getList()->fetchAll(), 'LANDING_SITE_ID');
		foreach ($list as $index => $site)
		{
			$list[$index]['EXCLUDED'] = in_array($site['ID'], $disabledList);
		}

		return $list;
	}

	/**
	 * Get ready b24 site domains.
	 *
	 * @return array
	 */
	public static function getReadyB24SiteDomains()
	{
		return array_keys(self::getReadyB24SiteIds());
	}

	/**
	 * Get ready b24 site domains.
	 *
	 * @return array
	 */
	public static function getReadyB24SiteIds()
	{
		$result = [];
		foreach (self::getB24Sites() as $site)
		{
			if ($site['EXCLUDED'])
			{
				continue;
			}

			$host = strtolower(trim($site['DOMAIN_NAME']));
			$result[$host] = $site['ID'];
		}

		return $result;
	}
}