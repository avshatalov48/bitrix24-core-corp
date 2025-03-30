<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Rest,
	Bitrix\SalesCenter\Integration\SaleManager,
	Bitrix\Main\Engine\Contract\Controllerable,
	Bitrix\SalesCenter\Integration\RestManager,
	Bitrix\SalesCenter\Integration\Bitrix24Manager;

Loc::loadMessages(__FILE__);
class SalesCenterSmsProviderPanel extends CBitrixComponent implements Controllerable
{
	private const MARKETPLACE_CATEGORY_SMS = 'crm_robot_sms';
	private $smsProviderPanelId = 'salescenter-smsprovider';
	private $smsProviderAppPanelId = 'salescenter-smsprovider-app';
	private $smsProviderColors = [];

	private const SMSPROVIDER_TITLE_LENGTH_LIMIT = 50;

	public function executeComponent()
	{
		if (!Loader::includeModule('salescenter'))
		{
			showError(Loc::getMessage('SCP_SALESCENTER_MODULE_ERROR'));
			return;
		}

		if (!Loader::includeModule('sale'))
		{
			showError(Loc::getMessage('SCP_SALE_MODULE_ERROR'));
			return;
		}

		if (!SaleManager::getInstance()->isManagerAccess())
		{
			showError(Loc::getMessage('SCP_ACCESS_DENIED'));
			return;
		}

		$this->prepareResult();

		$this->includeComponentTemplate();
	}

	/**
	 * @return string
	 */
	private function getImagePath(): string
	{
		static $imagePath = '';
		if ($imagePath)
		{
			return $imagePath;
		}

		$componentPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.smsprovider.panel');
		$componentPath = getLocalPath('components'.$componentPath);

		$imagePath = $componentPath.'/templates/.default/images/';
		return $imagePath;
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
	/**
	 * @param string $title
	 * @return string
	 */
	private function getFormattedTitle(string $title): string
	{
		if (mb_strlen($title) > self::SMSPROVIDER_TITLE_LENGTH_LIMIT)
		{
			$title = mb_substr($title, 0, self::SMSPROVIDER_TITLE_LENGTH_LIMIT - 3).'...';
		}

		return $title;
	}

	/**
	 * @param array $marketplaceItemCodeList
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getMarketplaceItems(array $marketplaceItemCodeList): array
	{
		$installedApps = $this->getMarketplaceInstalledApps();
		$zone = $this->getZone();
		$itemList = [];

		foreach($marketplaceItemCodeList as $marketplaceItemCode)
		{
			if ($marketplaceApp = RestManager::getInstance()->getMarketplaceAppByCode($marketplaceItemCode))
			{
				$title = $marketplaceApp['NAME']
					?? $marketplaceApp['LANG'][$zone]['NAME']
					?? current($marketplaceApp['LANG'])['NAME']
					?? '';

				if (!empty($marketplaceApp['ICON_PRIORITY']) || !empty($marketplaceApp['ICON']))
				{
					$hasOwnIcon = true;
					$img = $marketplaceApp['ICON_PRIORITY'] ?: $marketplaceApp['ICON'];
				}
				else
				{
					$hasOwnIcon = false;
					$img = $this->getImagePath().'marketplace_default.svg';
				}

				$itemList[] = [
					'id' => (array_key_exists($marketplaceItemCode, $installedApps)
						? $installedApps[$marketplaceItemCode]['ID']
						: $marketplaceApp['ID']
					),
					'title' => $this->getFormattedTitle($title),
					'image' => $img,
					'itemSelected' => array_key_exists($marketplaceItemCode, $installedApps),
					'data' => [
						'type' => 'marketplaceApp',
						'code' => $marketplaceApp['CODE'],
						'hasOwnIcon' => $hasOwnIcon,
					],
				];
			}
		}

		return $itemList;
	}

	/**
	 * @return array|string[]
	 * @throws Main\SystemException
	 */
	private function getMarketPlacePartnerItemCodeList(): array
	{
		$result = [];

		$zone = $this->getZone();

		$partnerItems = RestManager::getInstance()->getByTag([
			RestManager::TAG_SMSPROVIDER_SMS,
			RestManager::TAG_SMSPROVIDER_PARTNERS,
			$zone
		]);
		if (!empty($partnerItems['ITEMS']))
		{
			foreach ($partnerItems['ITEMS'] as $partnerItem)
			{
				$result[] = $partnerItem['CODE'];
			}
		}

		return $result;
	}

	/**
	 * @return array|string[]
	 * @throws Main\SystemException
	 */
	private function getMarketPlaceRecommendedItemCodeList(): array
	{
		$result = [];

		$zone = $this->getZone();

		$recommendedItems = RestManager::getInstance()->getByTag([
			RestManager::TAG_SMSPROVIDER_SMS,
			RestManager::TAG_SMSPROVIDER_RECOMMENDED,
			$zone
		]);
		if (!empty($recommendedItems['ITEMS']))
		{
			foreach ($recommendedItems['ITEMS'] as $recommendedItem)
			{
				$result[] = $recommendedItem['CODE'];
			}
		}

		return $result;
	}

	/**
	* @return array
	* @throws Main\ArgumentException
	* @throws Main\LoaderException
	* @throws Main\ObjectPropertyException
	* @throws Main\SystemException
	*/
	private function getMarketplaceInstalledApps(): array
	{
		if(!Main\Loader::includeModule('rest'))
		{
			return [];
		}

		static $marketplaceInstalledApps = [];
		if(!empty($marketplaceInstalledApps))
		{
			return $marketplaceInstalledApps;
		}

		$marketplaceAppCodeList = RestManager::getInstance()->getMarketplaceAppCodeList(self::MARKETPLACE_CATEGORY_SMS);

		$appIterator = Rest\AppTable::getList([
			'select' => [
				'ID',
				'CODE',
			],
			'filter' => [
				'=CODE' => $marketplaceAppCodeList,
				'SCOPE' => '%messageservice%',
				'=ACTIVE' => 'Y',
			]
		]);
		while ($row = $appIterator->fetch())
		{
			$marketplaceInstalledApps[$row['CODE']] = $row;
		}

		return $marketplaceInstalledApps;
	}

	public function prepareResult()
	{
		$this->smsProviderColors = $this->getSmsProviderColors();
		$menuItems = $this->getMenuItems();
		$recommendedItems = $this->getRecommendedItems();
		// sms-provider
		$this->arResult['smsProviderPanelParams'] = [
			'id' => $this->smsProviderPanelId,
			'items' => array_merge($menuItems, $recommendedItems),
		];
		// marketplace
		$this->arResult['smsProviderAppPanelParams'] = [
			'id' => $this->smsProviderAppPanelId,
			'items' => $this->getPartnerItems(),
		];

		return $this->arResult;
	}

	protected function getSmsProviderItems()
	{
		$result = [];
		$smsSenders = \Bitrix\Crm\Integration\SmsManager::getSenderInfoList();
		$saleshubItems = SaleManager::getSaleshubSmsProviderItems();

		$disabledSmsProviders = [];
		$zone = '';
		if (Main\Loader::includeModule('intranet'))
		{
			$zone = \CIntranetUtils::getPortalZone();
		}
		if ($zone === 'ua')
		{
			$disabledSmsProviders = ['smsru', 'smsastby'];
		}

		foreach ($smsSenders as $sender)
		{
			$senderId = $sender['id'];
			if (in_array($senderId, $disabledSmsProviders))
			{
				continue;
			}
			if(array_key_exists($senderId, $saleshubItems) && $sender['isConfigurable'])
			{
				$saleshubItem = $saleshubItems[$senderId];
				if (!$saleshubItem['main'])
				{
					continue;
				}

				$itemSelectedColor = $this->smsProviderColors[$sender['id']] ?? '';

				$result[] = [
					'id' => $senderId,
					'sort' => $saleshubItem['sort'],
					'title' => $sender['name'],
					'image' => '/bitrix/components/bitrix/salescenter.smsprovider.panel/templates/.default/images/'.$senderId.'.svg',
					'itemSelectedColor' => $itemSelectedColor,
					'itemSelectedImage' => '/bitrix/components/bitrix/salescenter.smsprovider.panel/templates/.default/images/'.$senderId.'-active.svg',
					'itemSelected' => $sender['canUse'],
					'data' => [
						'type' => 'smsprovider',
						'connectPath' => '/bitrix/components/bitrix/salescenter.smsprovider.panel/slider.php?senderId='.$senderId,
						'recommendation' => $saleshubItem['recommendation'],
						'isSelectedItemTitleDark' => in_array($senderId, $this->getDarkSelectedTitleProviders()),
					]
				];
			}
		}
		Main\Type\Collection::sortByColumn($result, ['sort' => SORT_ASC]);

		if (NotificationsManager::isAvailable())
		{
			array_unshift(
				$result,
				[
					'id' => 'bitrix24',
					'title' => Loc::getMessage('SPP_SMSPROVIDER_SMS_AND_WHATS_APP') . "\n" . Loc::getMessage('SPP_SMSPROVIDER_APP_BITRIX24'),
					'image' => '/bitrix/components/bitrix/salescenter.smsprovider.panel/templates/.default/images/bitrix24.svg',
					'itemSelectedColor' => '#1EC6FA',
					'itemSelectedImage' => '/bitrix/components/bitrix/salescenter.smsprovider.panel/templates/.default/images/bitrix24-active.svg',
					'itemSelected' => NotificationsManager::isConnected(),
					'data' => [
						'type' => 'bitrix24',
						'connectPath' => NotificationsManager::getConnectUrl(),
						'recommendation' => false,
						'isSelectedItemTitleDark' => in_array($senderId, $this->getDarkSelectedTitleProviders()),
					]
				]
			);
		}

		return $result;
	}

	private function getSmsProviderColors() : array
	{
		return [
			'smsastby' => '#188A98',
			'smsru' => '#8EB807',
			'twilio' => '#F12E45',
			'smsednaru' => '#1AEA76',
		];
	}

	private function getDarkSelectedTitleProviders() : array
	{
		return ['smsednaru'];
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getRecommendedItems(): array
	{
		$result = [];

		if (RestManager::getInstance()->isEnabled())
		{
			$result = array_filter(
				$this->getMarketplaceItems($this->getMarketPlaceRecommendedItemCodeList()),
				static function ($item) {
					return ($item['itemSelected']) === false;
				}
			);
		}

		if (RestManager::getInstance()->isEnabled())
		{
			foreach ($this->getActionboxItems() as $actionboxItem)
			{
				$result[] = $actionboxItem;
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getPartnerItems(): array
	{
		$result = [];

		if (RestManager::getInstance()->isEnabled())
		{
			$result = array_filter(
				$this->getMarketplaceItems($this->getMarketPlacePartnerItemCodeList()),
				static function ($item) {
					return ($item['itemSelected']) === false;
				}
			);
			// reset the keys, otherwise there might be js errors somewhere in tiles
			$result = array_values($result);

			$result[] = $this->getSmsProviderAppsCount();
		}

		if (Bitrix24Manager::getInstance()->isEnabled())
		{
			$result[] = $this->getRecommendItem();
		}

		if (RestManager::getInstance()->isEnabled())
		{
			foreach ($this->getActionboxItems() as $actionboxItem)
			{
				$result[] = $actionboxItem;
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Main\SystemException
	 */
	private function getSmsProviderAppsCount(): array
	{
		$appsCount = $this->getMarketplaceAppsCount();
		return [
			'id' => 'counter',
			'title' => Loc::getMessage('SPP_SMSPROVIDER_APP_TOTAL_APPLICATIONS'),
			'data' => [
				'type' => 'counter',
				'connectPath' => '/marketplace/?category='.self::MARKETPLACE_CATEGORY_SMS,
				'count' => $appsCount,
				'description' => Loc::getMessage('SPP_SMSPROVIDER_APP_SEE_ALL'),
			],
		];
	}

	private function getRecommendItem(): array
	{
		return [
			'id' => 'recommend',
			'title' => Loc::getMessage('SPP_SMSPROVIDER_APP_RECOMMEND'),
			'image' => '/bitrix/components/bitrix/salescenter.smsprovider.panel/templates/.default/images/recommend.svg',
			'data' => [
				'type' => 'recommend'
			]
		];
	}

	/**
	 * @return mixed
	 * @throws Main\SystemException
	 */
	private function getMarketplaceAppsCount()
	{
		$cacheTTL = 43200;
		$cacheId = 'salescenter_smsprovider_app_rest_app_count';
		$cachePath = '/salescenter/smsprovider/app/rest_partners/';
		$cache = Main\Application::getInstance()->getCache();

		if ($cache->initCache($cacheTTL, $cacheId, $cachePath))
		{
			$categoryItems = $cache->getVars();
		}
		else
		{
			$categoryItems = Rest\Marketplace\Client::getCategory(self::MARKETPLACE_CATEGORY_SMS, 0, 1);
			if (is_array($categoryItems))
			{
				$cache->startDataCache();
				$cache->endDataCache($categoryItems);
			}
		}

		return $categoryItems['PAGES'] ?? 0;
	}

	/**
	 * @inheritDoc
	 */
	public function configureActions()
	{
		return [];
	}

	protected function getMenuItems()
	{
		$result = $this->getSmsProviderItems();

		if (RestManager::getInstance()->isEnabled())
		{
			$installedApps = $this->getMarketplaceInstalledApps();
			$result = array_merge($result, $this->getMarketplaceItems(array_keys($installedApps)));
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getActionboxItems(): array
	{
		$dynamicItems = [];

		$restItems = RestManager::getInstance()->getActionboxItems(RestManager::ACTIONBOX_PLACEMENT_SMS);
		if ($restItems)
		{
			$dynamicItems = $this->prepareActionboxItems($restItems);
		}

		return $dynamicItems;
	}

	/**
	 * @param array $items
	 * @return array
	 */
	private function prepareActionboxItems(array $items): array
	{
		$result = [];

		foreach ($items as $item)
		{
			if ($item['SLIDER'] === 'Y')
			{
				preg_match("/^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)/i", $item['HANDLER'])
					? $handler = 'landing'
					: $handler = 'marketplace';
			}
			else
			{
				$handler = 'anchor';
			}

			$result[] = [
				'title' => $item['NAME'],
				'image' => $item['IMAGE'],
				'outerImage' => true,
				'itemSelectedColor' => $item['COLOR'],
				'data' => [
					'type' => 'actionbox',
					'showMenu' => false,
					'move' => $item['HANDLER'],
					'handler' => $handler,
				],
			];
		}

		return $result;
	}
}