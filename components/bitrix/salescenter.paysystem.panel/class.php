<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale,
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

		$paySystemItems = array_merge(
			$this->getPaySystemItems(),
			$marketplaceRecommendedItems
		);

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
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\IO\FileNotFoundException
	 */
	private function getPaySystemItems(): array
	{
		$systemHandlerList = $this->getSystemPaySystemHandlersList();
		$paySystemPanel = $this->getPaySystemPanel();
		$paySystemPath = $this->getPaySystemComponentPath();
		$paySystemIterator = Sale\PaySystem\Manager::getList([
			'select' => ['ID', 'ACTIVE', 'NAME', 'ACTION_FILE', 'PS_MODE'],
			'filter' => [
				'=ACTION_FILE' => array_keys($systemHandlerList),
				'=ENTITY_REGISTRY_TYPE' => 'ORDER',
			],
		]);

		$yandexHandler = \Sale\Handlers\PaySystem\YandexCheckoutHandler::class;
		$yandexHandler = Sale\PaySystem\Manager::getFolderFromClassName($yandexHandler);

		$paySystemActions = [];
		foreach ($paySystemIterator as $paySystem)
		{
			if (!$paySystem['PS_MODE'] && $paySystem['ACTION_FILE'] !== $yandexHandler)
			{
				$paySystem['PS_MODE'] = null;
			}

			$paySystemHandler = $paySystem['ACTION_FILE'];
			$inPanel = array_key_exists($paySystem['ACTION_FILE'], $paySystemPanel);
			$psMode = $paySystem['PS_MODE'];
			$isActive = $paySystem['ACTIVE'] === 'Y';
			if ($psMode !== null)
			{
				$inPanel = in_array($psMode, $paySystemPanel[$paySystem['ACTION_FILE']] ?? [], true);
			}

			if (!$isActive && !$inPanel)
			{
				continue;
			}

			$queryParams = [
				'lang' => LANGUAGE_ID,
				'publicSidePanel' => 'Y',
				'ID' => $paySystem['ID'],
				'ACTION_FILE' => $paySystem['ACTION_FILE'],
			];

			if ($psMode !== null)
			{
				$queryParams['PS_MODE'] = $psMode;

				if (!isset($paySystemActions[$paySystemHandler]['ACTIVE'][$psMode])
					|| $paySystemActions[$paySystemHandler]['ACTIVE'][$psMode] === false
				)
				{
					$paySystemActions[$paySystemHandler]['ACTIVE'][$psMode] = $isActive;
				}
				$paySystemActions[$paySystemHandler]['PS_MODE'] = true;
				$paySystemActions[$paySystemHandler]['HANDLER_NAME'] = $systemHandlerList[$paySystemHandler]['name'];
				$paySystemActions[$paySystemHandler]['ITEMS'][$psMode]['HANDLER_NAME'] = $systemHandlerList[$paySystemHandler]['psMode'][$psMode];
				$paySystemActions[$paySystemHandler]['ITEMS'][$psMode]['ITEMS'][] = [
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
				$paySystemActions[$paySystemHandler]['HANDLER_NAME'] = $systemHandlerList[$paySystemHandler]['name'];
				$paySystemActions[$paySystemHandler]['ITEMS'][] = [
					'NAME' => Loc::getMessage('SPP_PAYSYSTEM_SETTINGS', [
						'#PAYSYSTEM_NAME#' => htmlspecialcharsbx($paySystem['NAME'])
					]),
					'LINK' => $paySystemPath->addParams($queryParams)->getLocator(),
				];
			}
		}

		foreach ($paySystemPanel as $handler => $psModeList)
		{
			if (!$this->isPaySystemAvailable($handler))
			{
				continue;
			}

			if ($psModeList)
			{
				foreach ($psModeList as $psMode)
				{
					if (!$this->isPaySystemAvailable($handler, $psMode))
					{
						continue;
					}

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
			$title = Loc::getMessage('SPP_PAYSYSTEM_'.mb_strtoupper($handler).'_TITLE');
			if (!$title)
			{
				$title = Loc::getMessage('SPP_PAYSYSTEM_DEFAULT_TITLE', [
					'#PAYSYSTEM_NAME#' => $paySystem['HANDLER_NAME']
				]);
			}

			$handlerTitle = $title;

			$image = $this->getImagePath().'marketplace_default.svg';
			$itemSelectedImage = $this->getImagePath().'marketplace_default_s.svg';

			$imagePath = $this->getImagePath().$handler.'.svg';
			$itemSelectedImagePath = $this->getImagePath().$handler.'_s.svg';
			if (Main\IO\File::isFileExists(Main\Application::getDocumentRoot().$imagePath))
			{
				$image = $imagePath;
				$itemSelectedImage = $itemSelectedImagePath;
			}

			if (!empty($paySystem['ITEMS']))
			{
				if ($paySystem['PS_MODE'])
				{
					foreach ($paySystem['ITEMS'] as $psMode => $paySystemItem)
					{
						$title = $handlerTitle;
						$type = $psMode;
						$isActive = $paySystemActions[$handler]['ACTIVE'][$psMode];
						if (!$isActive
							&& (
								isset($paySystemPanel[$handler])
								&& !in_array($psMode, $paySystemPanel[$handler], true)
							)
						)
						{
							continue;
						}

						if (empty($paySystemItem)
							&& (
								isset($paySystemPanel[$handler])
								&& !in_array($psMode, $paySystemPanel[$handler], true)
							)
						)
						{
							continue;
						}

						if (Loc::getMessage('SPP_PAYSYSTEM_'.mb_strtoupper($handler).'_'.mb_strtoupper($psMode).'_TITLE'))
						{
							$title = Loc::getMessage('SPP_PAYSYSTEM_'.mb_strtoupper($handler).'_'.mb_strtoupper($psMode).'_TITLE');
						}

						$queryParams['ACTION_FILE'] = $handler;
						$queryParams['PS_MODE'] = $psMode;
						$paySystemPath = $this->getPaySystemComponentPath();
						$paySystemPath->addParams($queryParams);

						$imagePath = $this->getImagePath().$handler.'_'.$psMode.'.svg';
						$itemSelectedImagePath = $this->getImagePath().$handler.'_'.$psMode.'_s.svg';
						if (Main\IO\File::isFileExists(Main\Application::getDocumentRoot().$imagePath))
						{
							$image = $imagePath;
							$itemSelectedImage = $itemSelectedImagePath;
						}

						if (is_array($this->paySystemColor[$handler]))
						{
							$itemSelectedColor = $this->paySystemColor[$handler][$psMode];
						}
						else
						{
							$itemSelectedColor = $this->paySystemColor[$handler];
						}

						if (!$itemSelectedColor)
						{
							$itemSelectedColor = '#56C472';
						}

						$paySystemItems[] = [
							'id' => $handler.'_'.$psMode,
							'sort' => $this->getPaySystemSort($handler, $psMode),
							'title' => $this->getFormattedTitle($title),
							'image' => $image,
							'itemSelectedColor' => $itemSelectedColor,
							'itemSelected' => $isActive,
							'itemSelectedImage' => $itemSelectedImage,
							'data' => [
								'type' => 'paysystem',
								'connectPath' => $paySystemPath->getLocator(),
								'menuItems' => $paySystemItem['ITEMS'] ?? $paySystemItem,
								'showMenu' => !empty($paySystemItem),
								'paySystemType' => $type,
								'recommendation' => $this->isPaySystemRecommendation($handler, $psMode),
							],
						];
					}
				}
				else
				{
					$isActive = $paySystemActions[$handler]['ACTIVE'];

					if (!$isActive && (!array_key_exists($handler, $paySystemPanel)))
					{
						continue;
					}
					$type = $handler;

					$queryParams['ACTION_FILE'] = $handler;
					$paySystemPath = $this->getPaySystemComponentPath();
					$paySystemPath->addParams($queryParams);

					$imagePath = $this->getImagePath().$handler.'.svg';
					$itemSelectedImagePath = $this->getImagePath().$handler.'_s.svg';
					if (Main\IO\File::isFileExists(Main\Application::getDocumentRoot().$imagePath))
					{
						$image = $imagePath;
						$itemSelectedImage = $itemSelectedImagePath;
					}

					$paySystemItems[] = [
						'id' => $handler,
						'sort' => $this->getPaySystemSort($handler),
						'title' => $this->getFormattedTitle($title),
						'image' => $image,
						'itemSelectedColor' => $this->paySystemColor[$handler] ?? '#56C472',
						'itemSelected' => $isActive,
						'itemSelectedImage' => $itemSelectedImage,
						'data' => [
							'type' => 'paysystem',
							'connectPath' => $paySystemPath->getLocator(),
							'menuItems' => $paySystem['ITEMS'],
							'showMenu' => !empty($paySystem['ITEMS']),
							'paySystemType' => $type,
							'recommendation' => $this->isPaySystemRecommendation($handler),
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
					'sort' => $this->getPaySystemSort($handler),
					'title' => $this->getFormattedTitle($title),
					'image' => $image,
					'itemSelectedColor' => $this->paySystemColor[$handler] ?? '#56C472',
					'itemSelected' => $isActive,
					'itemSelectedImage' => $itemSelectedImage,
					'data' => [
						'type' => 'paysystem',
						'connectPath' => $paySystemPath->getLocator(),
						'menuItems' => [],
						'showMenu' => false,
						'paySystemType' => $type,
						'recommendation' => $this->isPaySystemRecommendation($handler),
					],
				];
			}
		}

		Main\Type\Collection::sortByColumn($paySystemItems, ['sort' => SORT_ASC]);

		return $paySystemItems;
	}

	/**
	 * @return array
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\IO\FileNotFoundException
	 */
	private function getSystemPaySystemHandlersList(): array
	{
		$systemHandlerList = [];

		$handlerList = Sale\PaySystem\Manager::getHandlerList();
		if (isset($handlerList['SYSTEM']))
		{
			$systemHandlers = array_keys($handlerList['SYSTEM']);
			foreach ($systemHandlers as $key => $systemHandler)
			{
				if ($systemHandler === 'inner')
				{
					continue;
				}

				$handlerDescription = Sale\PaySystem\Manager::getHandlerDescription($systemHandler);
				if (empty($handlerDescription))
				{
					continue;
				}

				$systemHandlerList[$systemHandler] = [
					'name' => $handlerDescription['NAME'] ?? $handlerList['SYSTEM'][$systemHandler],
				];

				/** @var Sale\PaySystem\BaseServiceHandler $handlerClass */
				$handlerClass = Sale\PaySystem\Manager::getClassNameFromPath($systemHandler);
				if (!class_exists($handlerClass))
				{
					$documentRoot = Main\Application::getDocumentRoot();
					$path = Sale\PaySystem\Manager::getPathToHandlerFolder($systemHandler);
					$fullPath = $documentRoot.$path.'/handler.php';
					if ($path && Main\IO\File::isFileExists($fullPath))
					{
						require_once $fullPath;
					}
				}

				if (class_exists($handlerClass) && ($psMode = $handlerClass::getHandlerModeList()))
				{
					$systemHandlerList[$systemHandler]['psMode'] = $psMode;
				}
			}
		}

		return $systemHandlerList;
	}

	/**
	 * @param $handler
	 * @param null $psMode
	 * @return bool
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	private function isPaySystemAvailable($handler, $psMode = null): bool
	{
		$description = Sale\PaySystem\Manager::getHandlerDescription($handler);
		$isAvailable = $description && !(isset($description['IS_AVAILABLE']) && !$description['IS_AVAILABLE']);
		if (!$psMode)
		{
			return $isAvailable;
		}

		$psModeList = [];
		/** @var Sale\PaySystem\BaseServiceHandler $handlerClass */
		[$handlerClass] = Sale\PaySystem\Manager::includeHandler($handler);
		if (class_exists($handlerClass))
		{
			$psModeList = $handlerClass::getHandlerModeList();
		}

		return isset($psModeList[$psMode]);
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
				'alfabank' => '#EE2A23',
				'yoo_money' => '#FFA900',
				'qiwi' => '#E9832C',
				'webmoney' => '#006FA8',
				'embedded' => '#0697F2',
				'tinkoff_bank' => '#FFE52B',
			],
			'skb' => [
				'skb' => '#F65E64',
				'delobank' => '#F65E64',
				'gazenergobank' => '#F65E64',
			],
			'bepaid' => [
				'widget' => '#E36F10',
				'checkout' => '#E36F10',
			],
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
	 * @return array
	 */
	private function getPaySystemPanel(): array
	{
		$zone = $this->getZone();
		$paySystemPanel = [];
		if ($this->isMainMode())
		{
			foreach ($this->paySystemList as $handler => $handlerItem)
			{
				if (!empty($handlerItem['psMode']))
				{
					foreach ($handlerItem['psMode'] as $psMode => $psModeItem)
					{
						if ($psModeItem['main'])
						{
							$paySystemPanel[$handler][] = $psMode;
						}
					}
				}
				elseif ($handlerItem['main']
					|| ($zone !== 'ru' && $handler === 'paypal')
				)
				{
					$paySystemPanel[$handler] = [];
				}
			}
		}
		else
		{
			foreach ($this->paySystemList as $handler => $handlerItem)
			{
				if (!empty($handlerItem['psMode']))
				{
					foreach ($handlerItem['psMode'] as $psMode => $psModeItem)
					{
						$paySystemPanel[$handler][] = $psMode;
					}
				}
				else
				{
					$paySystemPanel[$handler] = [];
				}
			}
		}

		return $paySystemPanel;
	}

	/**
	 * @param $handler
	 * @param bool $psMode
	 * @return int|mixed
	 */
	private function getPaySystemSort($handler, $psMode = false)
	{
		$defaultSort = 10000;
		if ($psMode)
		{
			return $this->paySystemList[$handler]['psMode'][$psMode]['sort'] ?? $defaultSort;
		}

		return $this->paySystemList[$handler]['sort'] ?? $defaultSort;
	}

	private function isPaySystemRecommendation($handler, $psMode = false)
	{
		$isRecommendation = false;
		if ($psMode)
		{
			return $this->paySystemList[$handler]['psMode'][$psMode]['recommendation'] ?? $isRecommendation;
		}

		return $this->paySystemList[$handler]['recommendation'] ?? $isRecommendation;
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
		$userHandlerList = $this->getUserPaySystemHandlersList();
		if (empty($userHandlerList))
		{
			return [];
		}

		$paySystemPath = $this->getPaySystemComponentPath();
		$filter = $this->getFilterForPaySystem($userHandlerList);
		$paySystemIterator = Sale\PaySystem\Manager::getList([
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
		$userHandlerList = [];

		$handlerList = Sale\PaySystem\Manager::getHandlerList();
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

				$handlerDescription = Sale\PaySystem\Manager::getHandlerDescription($userHandler);
				if (empty($handlerDescription))
				{
					continue;
				}

				$userHandlerList[$userHandler] = [
					'name' => $handlerDescription['NAME'] ?? $handlerList['USER'][$userHandler],
				];

				/** @var Sale\PaySystem\BaseServiceHandler $handlerClass */
				$handlerClass = Sale\PaySystem\Manager::getClassNameFromPath($userHandler);
				if (!class_exists($handlerClass))
				{
					$documentRoot = Main\Application::getDocumentRoot();
					$path = Sale\PaySystem\Manager::getPathToHandlerFolder($userHandler);
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
	 * @inheritDoc
	 */
	public function configureActions()
	{
		return [];
	}
}