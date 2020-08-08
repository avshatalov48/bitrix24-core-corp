<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

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

	private const SMSPROVIDER_TITLE_LENGTH_LIMIT = 50;

	public function executeComponent()
	{
		if (!Loader::includeModule('salescenter'))
		{
			$this->showError(Loc::getMessage('SCP_SALESCENTER_MODULE_ERROR'));
			return;
		}

		if (!Loader::includeModule('sale'))
		{
			$this->showError(Loc::getMessage('SCP_SALE_MODULE_ERROR'));
			return;
		}

		if(!SaleManager::getInstance()->isManagerAccess())
		{
			$this->showError(Loc::getMessage('SCP_ACCESS_DENIED'));
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
		$partnerItemList = [];

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

				$partnerItemList[] = [
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

		return $partnerItemList;
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
		// sms-provider
		$this->arResult['smsProviderPanelParams'] = [
			'id' => $this->smsProviderPanelId,
			'items' => $this->getMenuItems(),
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

		foreach ($smsSenders as $sender)
		{
			if($sender['isConfigurable'])
			{
				if($sender['id'] == 'smsastby')
				{
					$itemSelectedColor = '#188A98';
				}
				elseif ($sender['id'] == 'smsru')
				{
					$itemSelectedColor = '#8EB807';
				}
				elseif ($sender['id'] == 'twilio')
				{
					$itemSelectedColor = '#F12E45';
				}
				else
				{
					$itemSelectedColor = '';
				}

				$result[] = [
					'id' => $sender['id'],
					'title' => $sender['name'],
					'image' => '/bitrix/components/bitrix/salescenter.smsprovider.panel/templates/.default/images/'.$sender['id'].'.svg',
					'itemSelectedColor' => $itemSelectedColor,
					'itemSelectedImage' => '/bitrix/components/bitrix/salescenter.smsprovider.panel/templates/.default/images/'.$sender['id'].'-active.svg',
					'itemSelected' => $sender['canUse'],
					'data' => [
						'type' => 'smsprovider',
						'connectPath' => '/bitrix/components/bitrix/salescenter.smsprovider.panel/slider.php?senderId='.$sender['id'],
					]
				];
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
		$result = array_filter(
			$this->getMarketplaceItems($this->getMarketPlacePartnerItemCodeList()),
			static function ($item) {
				return ($item['itemSelected']) === false;
			}
		);

		$result[] = $this->getSmsProviderAppsCount();

		if (Bitrix24Manager::getInstance()->isEnabled())
		{
			$result[] = $this->getRecommendItem();
		}

		foreach ($this->getActionboxItems() as $actionboxItem)
		{
			$result[] = $actionboxItem;
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