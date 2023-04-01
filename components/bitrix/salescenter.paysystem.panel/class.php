<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Rest,
	Bitrix\SalesCenter\Integration\SaleManager,
	Bitrix\SalesCenter\Integration\RestManager,
	Bitrix\SalesCenter\Integration\Bitrix24Manager,
	Bitrix\Main\Engine\Contract\Controllerable;

Loc::loadMessages(__FILE__);

/**
 * Class SalesCenterPaySystemPanel
 */
class SalesCenterPaySystemPanel extends CBitrixComponent implements Controllerable
{
	private const PAYSYSTEM_TITLE_LENGTH_LIMIT = 50;

	private const MARKETPLACE_CATEGORY_PAYMENT = 'payment';

	private const RUSSIAN_PORTAL_ZONE_CODE = 'ru';

	private $paySystemPanelId = 'salescenter-paysystem';
	private $paySystemAppPanelId = 'salescenter-paysystem-app';

	private $mode;
	private $paySystemList;
	private $paySystemColor;

	/**
	 * @param $arParams
	 * @return array
	 */
	public function onPrepareComponentParams($arParams)
	{
		$arParams['MODE'] = $arParams['MODE'] ?? 'main';
		$this->mode = $arParams['MODE'];

		return parent::onPrepareComponentParams($arParams);
	}

	protected function listKeysSignedParameters()
	{
		return [
			'PAYSYSTEM_COLOR',
		];
	}

	/**
	 * @return bool
	 */
	private function isMainMode(): bool
	{
		return $this->mode === 'main';
	}

	/**
	 * @return mixed|void
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 */
	public function executeComponent()
	{
		if (!Loader::includeModule('salescenter'))
		{
			$this->showError(Loc::getMessage('SPP_SALESCENTER_MODULE_ERROR'));
			return;
		}

		if (!Loader::includeModule('sale'))
		{
			$this->showError(Loc::getMessage('SPP_SALE_MODULE_ERROR'));
			return;
		}

		if(!SaleManager::getInstance()->isManagerAccess(true))
		{
			$this->showError(Loc::getMessage('SPP_ACCESS_DENIED'));
			return;
		}

		$this->prepareResult();

		$this->includeComponentTemplate();
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\IO\FileNotFoundException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function prepareResult(): array
	{
		$this->paySystemList = SaleManager::getSaleshubPaySystemItems();
		$this->paySystemColor = $this->getPaySystemColor();

		$this->arResult['mode'] = $this->mode;
		$this->arResult['isMainMode'] = $this->isMainMode();

		$marketplaceRecommendedItems = [];
		if (RestManager::getInstance()->isEnabled())
		{
			$recommendedItemCodeList = $this->getMarketPlaceRecommendedItemCodeList();
			$marketplaceRecommendedItems = $this->getMarketplaceItems($recommendedItemCodeList, true);
		}

		$paySystemItemsResult = (new Bitrix\SalesCenter\Component\PaySystem())->getRecommendedItems(
			$this->paySystemList,
			$this->isMainMode(),
			self::PAYSYSTEM_TITLE_LENGTH_LIMIT,
			$this->paySystemColor,
		);

		$paySystemItems = $paySystemItemsResult->isSuccess()
			? array_merge(
				$paySystemItemsResult->getData(),
				$marketplaceRecommendedItems
			)
			: $marketplaceRecommendedItems;

		// paysystem
		$this->arResult['paySystemPanelParams'] = [
			'id' => $this->paySystemPanelId,
			'items' => $paySystemItems,
		];

		$paySystemExtraItems = [];
		if ($this->isMainMode())
		{
			// marketplace
			$paySystemExtraItems[] = $this->getPaySystemExtraItem();

			foreach ($this->getUserPaySystemItems() as $userPaySystemItem)
			{
				$paySystemExtraItems[] = $userPaySystemItem;
			}

			if (RestManager::getInstance()->isEnabled())
			{
				$partnerItemCodeList = $this->getMarketPlacePartnerItemCodeList();
				foreach ($this->getMarketplaceItems($partnerItemCodeList) as $marketplaceItem)
				{
					$paySystemExtraItems[] = $marketplaceItem;
				}

				$paySystemExtraItems[] = $this->getShowAllItem();
			}

			if (Bitrix24Manager::getInstance()->isEnabled())
			{
				$paySystemExtraItems[] = $this->getRecommendItem();

				if ($this->getZone() === self::RUSSIAN_PORTAL_ZONE_CODE)
				{
					$paySystemExtraItems[] = $this->getSbpRecommendItem();
				}
			}

			if (RestManager::getInstance()->isEnabled())
			{
				foreach ($this->getActionboxItems() as $actionboxItem)
				{
					$paySystemExtraItems[] = $actionboxItem;
				}
			}
		}

		$this->arResult['paySystemAppPanelParams'] = [
			'id' => $this->paySystemAppPanelId,
			'items' => $paySystemExtraItems,
		];

		return $this->arResult;
	}

	/**
	 * @param $error
	 */
	private function showError($error)
	{
		ShowError($error);
	}

	/**
	 * @param $paySystemList
	 * @return array
	 */
	private function getFilterForPaySystem($paySystemList): array
	{
		$filter = [
			'ENTITY_REGISTRY_TYPE' => 'ORDER',
		];
		$subFilter = [];
		foreach ($paySystemList as $handler => $handlerItem)
		{
			$psMode = empty($handlerItem['psMode']) ? [] : array_keys($handlerItem['psMode']);
			if ($psMode)
			{
				$subFilter[] = [
					'=ACTION_FILE' => $handler,
					'=PS_MODE' => $psMode,
				];
			}
			else
			{
				$subFilter[] = [
					'=ACTION_FILE' => $handler,
				];
			}
		}

		if ($subFilter)
		{
			$filter[] = array_merge(['LOGIC' => 'OR'], $subFilter);
		}

		return $filter;
	}

	/**
	 * @return Main\Web\Uri
	 */
	private function getPaySystemComponentPath(): Main\Web\Uri
	{
		$paySystemPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem');
		$paySystemPath = getLocalPath('components'.$paySystemPath.'/slider.php');
		$paySystemPath = new Main\Web\Uri($paySystemPath);

		return $paySystemPath;
	}

	/**
	 * @return array
	 */
	private function getPaySystemColor(): array
	{
		return [
			'yandexcheckout' => [
				'bank_card' => '#19D0C8',
				'sberbank' => '#2C9B47',
				'sberbank_sms' => '#289D37',
				'sberbank_qr' => '#289D37',
				'sbp' => '#1487C9',
				'alfabank' => '#EE2A23',
				'yoo_money' => '#FFA900',
				'qiwi' => '#E9832C',
				'webmoney' => '#006FA8',
				'embedded' => '#0697F2',
				'tinkoff_bank' => '#FFE52B',
				'installments' => '#00EEBC',
			],
			'skb' => [
				'skb' => '#DF1D40',
				'delobank' => '#DF1D40',
				'gazenergobank' => '#DF1D40',
			],
			'bepaid' => [
				'widget' => '#E36F10',
				'checkout' => '#E36F10',
			],
			'bepaiderip' => '#E36F10',
			'uapay' => '#E41F18',
			'cash' => '#8EB927',
			'sberbankonline' => '#2C9B47',
			'webmoney' => '#006FA8',
			'qiwi' => '#E9832C',
			'paypal' => '#243B80',
			'liqpay' => '#7AB72B',
			'orderdocument' => '#2FA8CD',
			'cashondelivery' => '#39A68E',
			'cashondeliverycalc' => '#39A68E',
			'paymaster' => '#1B7195',
			'wooppay' => [
				'iframe' => '#2F80ED',
				'checkout' => '#2F80ED',
			],
			'alfabank' => '#EF3124',
			'roboxchange' => [
				'bank_card' => '#19D0C8',
				'apple_pay' => '#8F8F8F',
				'google_pay' => '#4285F4',
				'samsung_pay' => '#1429A1',
			],
			'platon' => '#EC6125',
		];
	}

	/**
	 * @param array $paySystemActions
	 * @return array
	 */
	private function getPaySystemMenu(array $paySystemActions): array
	{
		$name = Loc::getMessage('SPP_PAYSYSTEM_ADD');

		foreach ($paySystemActions as $handler => $paySystems)
		{
			if (!$paySystems || empty($paySystems['ITEMS']))
			{
				continue;
			}

			$queryParams = [
				'lang' => LANGUAGE_ID,
				'publicSidePanel' => 'Y',
				'CREATE' => 'Y',
				'ACTION_FILE' => mb_strtolower($handler)
			];

			if ($paySystems['PS_MODE'])
			{
				foreach ($paySystems['ITEMS'] as $psMode => $paySystem)
				{
					if (!$paySystem)
					{
						continue;
					}

					$queryParams['PS_MODE'] = $psMode;
					$paySystemPath = $this->getPaySystemComponentPath();
					$paySystemPath->addParams($queryParams);


					$items = $paySystemActions[$handler]['ITEMS'][$psMode]['ITEMS']
						?? $paySystemActions[$handler]['ITEMS'][$psMode];
					array_unshift($items,
						[
							'NAME' => $name,
							'LINK' => $paySystemPath->getLocator(),
						],
						[
							'DELIMITER' => true
						]
					);
					if (isset($paySystemActions[$handler]['ITEMS'][$psMode]['ITEMS']))
					{
						$paySystemActions[$handler]['ITEMS'][$psMode]['ITEMS'] = $items;
					}
					else
					{
						$paySystemActions[$handler]['ITEMS'][$psMode] = $items;
					}
				}
			}
			else
			{
				$paySystemPath = $this->getPaySystemComponentPath();
				$paySystemPath->addParams($queryParams);

				array_unshift($paySystemActions[$handler]['ITEMS'],
					[
						'NAME' => $name,
						'LINK' => $paySystemPath->getLocator(),
					],
					[
						'DELIMITER' => true
					]
				);
			}
		}

		return $paySystemActions;
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

		$componentPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem.panel');
		$componentPath = getLocalPath('components'.$componentPath);

		$imagePath = $componentPath.'/templates/.default/images/';
		return $imagePath;
	}

	/**
	 * @return array
	 */
	private function getPaySystemExtraItem(): array
	{
		$paySystemPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem.panel');
		$paySystemPath = getLocalPath('components'.$paySystemPath.'/slider.php');
		$paySystemPath = new Main\Web\Uri($paySystemPath);
		$paySystemPath->addParams([
			'analyticsLabel' => 'salescenterClickPaymentTile',
			'type' => 'extra',
			'mode' => 'extra'
		]);

		return [
			'id' => 'paysystem',
			'title' => Loc::getMessage('SPP_PAYSYSTEM_ITEM_EXTRA'),
			'image' => $this->getImagePath().'paysystem.svg',
			'selectedColor' => '#E8A312',
			'selected' => false,
			'selectedImage' => $this->getImagePath().'paysystem_s.svg',
			'data' => [
				'type' => 'paysystem_extra',
				'connectPath' => $paySystemPath->getLocator(),
			]
		];
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\IO\FileNotFoundException
	 */
	private function getUserPaySystemItems(): array
	{
		if (!Loader::includeModule('sale'))
		{
			return [];
		}
		$paySystemManager = Main\DI\ServiceLocator::getInstance()->get('sale.paysystem.manager');


		$userHandlerList = $this->getUserPaySystemHandlersList();
		if (empty($userHandlerList))
		{
			return [];
		}

		$paySystemPath = $this->getPaySystemComponentPath();
		$filter = $this->getFilterForPaySystem($userHandlerList);
		$paySystemIterator = $paySystemManager::getList([
			'select' => ['ID', 'ACTIVE', 'NAME', 'ACTION_FILE', 'PS_MODE'],
			'filter' => $filter,
		]);

		$paySystemActions = [];
		foreach ($paySystemIterator as $paySystem)
		{
			$paySystemHandler = $paySystem['ACTION_FILE'];
			$psMode = $paySystem['PS_MODE'];
			$isActive = $paySystem['ACTIVE'] === 'Y';

			$queryParams = [
				'lang' => LANGUAGE_ID,
				'publicSidePanel' => 'Y',
				'ID' => $paySystem['ID'],
				'ACTION_FILE' => $paySystem['ACTION_FILE'],
			];

			if ($psMode)
			{
				$queryParams['PS_MODE'] = $psMode;

				if (!isset($paySystemActions[$paySystemHandler]['ACTIVE'][$psMode])
					|| $paySystemActions[$paySystemHandler]['ACTIVE'][$psMode] === false
				)
				{
					$paySystemActions[$paySystemHandler]['ACTIVE'][$psMode] = $isActive;
				}
				$paySystemActions[$paySystemHandler]['PS_MODE'] = true;
				$paySystemActions[$paySystemHandler]['ITEMS'][$psMode][] = [
					'NAME' => Loc::getMessage('SPP_PAYSYSTEM_SETTINGS', [
						'#PAYSYSTEM_NAME#' => htmlspecialcharsbx($paySystem['NAME'])
					]),
					'LINK' => $paySystemPath->addParams($queryParams)->getLocator(),
				];
			}
			else
			{
				$paySystemPath->addParams($queryParams)->getLocator();

				if (!isset($paySystemActions[$paySystemHandler]['ACTIVE'])
					|| $paySystemActions[$paySystemHandler]['ACTIVE'] === false
				)
				{
					$paySystemActions[$paySystemHandler]['ACTIVE'] = $isActive;
				}
				$paySystemActions[$paySystemHandler]['PS_MODE'] = false;
				$paySystemActions[$paySystemHandler]['ITEMS'][] = [
					'NAME' => Loc::getMessage('SPP_PAYSYSTEM_SETTINGS', [
						'#PAYSYSTEM_NAME#' => htmlspecialcharsbx($paySystem['NAME'])
					]),
					'LINK' => $paySystemPath->addParams($queryParams)->getLocator(),
				];
			}
		}

		foreach ($userHandlerList as $handler => $psModeList)
		{
			$psModeList = $psModeList['psMode'] ?? [];
			if ($psModeList)
			{
				foreach (array_keys($psModeList) as $psMode)
				{
					if (empty($paySystemActions[$handler]['ITEMS'][$psMode]))
					{
						$paySystemActions[$handler]['PS_MODE'] = true;
						$paySystemActions[$handler]['ACTIVE'][$psMode] = false;
						$paySystemActions[$handler]['ITEMS'][$psMode] = [];
					}
				}
			}
			elseif (empty($paySystemActions[$handler]))
			{
				$paySystemActions[$handler] = [
					'ACTIVE' => false,
					'PS_MODE' => false,
				];
			}
		}

		if ($paySystemActions)
		{
			$paySystemActions = $this->getPaySystemMenu($paySystemActions);
		}

		$paySystemItems = [];
		foreach ($paySystemActions as $handler => $paySystem)
		{
			$queryParams = [
				'lang' => LANGUAGE_ID,
				'publicSidePanel' => 'Y',
				'CREATE' => 'Y',
			];

			$isActive = false;
			$title = $userHandlerList[$handler]['name'];
			$title = $this->getFormattedTitle($title);

			$image = $this->getImagePath().'marketplace_default.svg';
			$selectedImage = $this->getImagePath().'marketplace_default_s.svg';

			if (isset($paySystem['ITEMS']))
			{
				$isPsMode = $paySystem['PS_MODE'];
				if ($isPsMode)
				{
					foreach ($paySystem['ITEMS'] as $psMode => $paySystemItem)
					{
						$type = $psMode;
						$isActive = $paySystemActions[$handler]['ACTIVE'][$psMode];

						$title = "{$userHandlerList[$handler]['name']}({$userHandlerList[$handler]['psMode'][$psMode]})";
						$title = $this->getFormattedTitle($title);

						$queryParams['ACTION_FILE'] = $handler;
						$queryParams['PS_MODE'] = $psMode;
						$paySystemPath = $this->getPaySystemComponentPath();
						$paySystemPath->addParams($queryParams);

						$paySystemItems[] = [
							'id' => $handler.'_'.$psMode,
							'title' => $this->getFormattedTitle($title),
							'image' => $image,
							'itemSelectedColor' => '#56C472',
							'itemSelected' => $isActive,
							'itemSelectedImage' => $selectedImage,
							'data' => [
								'type' => 'paysystem',
								'connectPath' => $paySystemPath->getLocator(),
								'menuItems' => $paySystemItem,
								'showMenu' => !empty($paySystemItem),
								'paySystemType' => $type,
							],
						];
					}
				}
				else
				{
					$isActive = $paySystemActions[$handler]['ACTIVE'];
					$type = $handler;

					$queryParams['ACTION_FILE'] = $handler;
					$paySystemPath = $this->getPaySystemComponentPath();
					$paySystemPath->addParams($queryParams);

					$paySystemItems[] = [
						'id' => $handler,
						'title' => $this->getFormattedTitle($title),
						'image' => $image,
						'itemSelectedColor' => '#56C472',
						'itemSelected' => $isActive,
						'itemSelectedImage' => $selectedImage,
						'data' => [
							'type' => 'paysystem',
							'connectPath' => $paySystemPath->getLocator(),
							'menuItems' => $paySystem['ITEMS'],
							'showMenu' => !empty($paySystem['ITEMS']),
							'paySystemType' => $type,
						],
					];
				}
			}
			else
			{
				$type = $handler;
				$queryParams['ACTION_FILE'] = $handler;
				$paySystemPath = $this->getPaySystemComponentPath();
				$paySystemPath->addParams($queryParams);

				$paySystemItems[] = [
					'id' => $handler,
					'title' => $this->getFormattedTitle($title),
					'image' => $image,
					'itemSelectedColor' => '#56C472',
					'itemSelected' => $isActive,
					'itemSelectedImage' => $selectedImage,
					'data' => [
						'type' => 'paysystem',
						'connectPath' => $paySystemPath->getLocator(),
						'menuItems' => [],
						'showMenu' => false,
						'paySystemType' => $type,
					],
				];
			}
		}

		return $paySystemItems;
	}

	/**
	 * @return array
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\IO\FileNotFoundException
	 */
	private function getUserPaySystemHandlersList(): array
	{
		if (!Loader::includeModule('sale'))
		{
			return [];
		}
		$paySystemManager = Main\DI\ServiceLocator::getInstance()->get('sale.paysystem.manager');

		$userHandlerList = [];

		$handlerList = $paySystemManager::getHandlerList();
		if (isset($handlerList['USER']))
		{
			$userHandlers = array_keys($handlerList['USER']);
			foreach ($userHandlers as $key => $userHandler)
			{
				if (mb_strpos($userHandler, '/') !== false)
				{
					unset($userHandlers[$key]);
					continue;
				}

				$handlerDescription = $paySystemManager::getHandlerDescription($userHandler);
				if (empty($handlerDescription))
				{
					continue;
				}

				$userHandlerList[$userHandler] = [
					'name' => $handlerDescription['NAME'] ?? $handlerList['USER'][$userHandler],
				];

				/** @var \Bitrix\Sale\PaySystem\BaseServiceHandler $handlerClass */
				$handlerClass = $paySystemManager::getClassNameFromPath($userHandler);
				if (!class_exists($handlerClass))
				{
					$documentRoot = Main\Application::getDocumentRoot();
					$path = $paySystemManager::getPathToHandlerFolder($userHandler);
					$fullPath = $documentRoot.$path.'/handler.php';
					if ($path && Main\IO\File::isFileExists($fullPath))
					{
						require_once $fullPath;
					}
				}

				if (class_exists($handlerClass) && ($psMode = $handlerClass::getHandlerModeList()))
				{
					$userHandlerList[$userHandler]['psMode'] = $psMode;
				}
			}
		}

		return $userHandlerList;
	}

	/**
	 * @param array $marketplaceItemCodeList
	 * @param bool $filter
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getMarketplaceItems(array $marketplaceItemCodeList, $filter = false): array
	{
		if ($filter)
		{
			$installedApps = [];
			if ($marketplaceItemCodeList)
			{
				$installedApps = $this->getMarketplaceInstalledApps($marketplaceItemCodeList);
			}
		}
		else
		{
			$installedApps = $this->getMarketplaceInstalledApps();
		}

		$marketplaceItemCodeList = array_unique(array_merge(array_keys($installedApps), $marketplaceItemCodeList));
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
					$img = $marketplaceApp['ICON_PRIORITY'] ?? $marketplaceApp['ICON'];
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
	private function getMarketPlaceRecommendedItemCodeList(): array
	{
		$result = [];

		$zone = $this->getZone();
		$partnerItems = RestManager::getInstance()->getByTag([
			RestManager::TAG_PAYSYSTEM_PAYMENT,
			RestManager::TAG_PAYSYSTEM_RECOMMENDED,
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
	private function getMarketPlacePartnerItemCodeList(): array
	{
		$result = [];

		$partnerItems = RestManager::getInstance()->getByTag([
			RestManager::TAG_PAYSYSTEM_PAYMENT,
			RestManager::TAG_PAYSYSTEM_PARTNERS,
			$this->getZone(),
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
	 * @param array $marketplaceAppCodeList
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getMarketplaceInstalledApps(array $marketplaceAppCodeList = []): array
	{
		static $marketplaceInstalledApps = [];
		if(!empty($marketplaceInstalledApps))
		{
			return $marketplaceInstalledApps;
		}

		if (!$marketplaceAppCodeList)
		{
			$marketplaceAppCodeList = RestManager::getInstance()->getMarketplaceAppCodeList(self::MARKETPLACE_CATEGORY_PAYMENT);
		}

		$appIterator = Rest\AppTable::getList([
			'select' => [
				'ID',
				'CODE',
			],
			'filter' => [
				'=CODE' => $marketplaceAppCodeList,
				'SCOPE' => '%pay_system%',
				'=ACTIVE' => 'Y',
			]
		]);
		while ($row = $appIterator->fetch())
		{
			$marketplaceInstalledApps[$row['CODE']] = $row;
		}

		return $marketplaceInstalledApps;
	}

	/**
	 * @return array
	 * @throws Main\SystemException
	 */
	private function getShowAllItem(): array
	{
		$paySystemAppsCount = RestManager::getInstance()->getMarketplaceAppsCount(self::MARKETPLACE_CATEGORY_PAYMENT);
		return [
			'id' => 'counter',
			'title' => Loc::getMessage('SPP_PAYSYSTEM_APP_TOTAL_APPLICATIONS'),
			'data' => [
				'type' => 'counter',
				'connectPath' => '/marketplace/?category='.self::MARKETPLACE_CATEGORY_PAYMENT,
				'count' => $paySystemAppsCount,
				'description' => Loc::getMessage('SPP_PAYSYSTEM_APP_SEE_ALL'),
			],
		];
	}

	/**
	 * @return array
	 */
	private function getActionboxItems(): array
	{
		$dynamicItems = [];

		$restItems = RestManager::getInstance()->getActionboxItems(RestManager::ACTIONBOX_PLACEMENT_PAYMENT);
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

	private function getRecommendItem(): array
	{
		$feedbackPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.feedback');
		$feedbackPath = getLocalPath('components'.$feedbackPath.'/slider.php');
		$feedbackPath = new Main\Web\Uri($feedbackPath);

		$queryParams = [
			'lang' => LANGUAGE_ID,
			'feedback_type' => 'paysystem_offer',
		];
		$feedbackPath->addParams($queryParams);

		return [
			'id' => 'recommend',
			'title' => Loc::getMessage('SPP_PAYSYSTEM_APP_RECOMMEND'),
			'image' => $this->getImagePath().'recommend.svg',
			'data' => [
				'type' => 'recommend',
				'connectPath' => $feedbackPath->getLocator(),
			]
		];
	}

	private function getSbpRecommendItem(): array
	{
		$feedbackPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.feedback');
		$feedbackPath = getLocalPath('components'.$feedbackPath.'/slider.php');
		$feedbackPath = new Main\Web\Uri($feedbackPath);

		$queryParams = [
			'lang' => LANGUAGE_ID,
			'feedback_type' => 'paysystem_sbp_offer',
		];
		$feedbackPath->addParams($queryParams);

		return [
			'id' => 'sbp_recommend',
			'title' => Loc::getMessage('SPP_PAYSYSTEM_SBP_RECOMMEND'),
			'image' => $this->getImagePath().'sbp_recommend.svg',
			'data' => [
				'type' => 'recommend',
				'connectPath' => $feedbackPath->getLocator(),
			]
		];
	}

	/**
	 * @param string $title
	 * @return string
	 */
	private function getFormattedTitle(string $title): string
	{
		if (mb_strlen($title) > self::PAYSYSTEM_TITLE_LENGTH_LIMIT)
		{
			$title = mb_substr($title, 0, self::PAYSYSTEM_TITLE_LENGTH_LIMIT - 3).'...';
		}

		return $title;
	}

	private function getZone()
	{
		return (new \Bitrix\SalesCenter\Component\PaySystem())->getZone();
	}

	/**
	 * @inheritDoc
	 */
	public function configureActions()
	{
		return [];
	}
}