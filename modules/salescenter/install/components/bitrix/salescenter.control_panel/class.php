<?php

use Bitrix\Main;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\SalesCenter\Driver;
use Bitrix\Sale;
use Bitrix\SalesCenter\Integration\CrmManager;
use Bitrix\SalesCenter\Integration\PullManager;
use Bitrix\SalesCenter\Integration\SaleManager;
use Bitrix\SalesCenter\Integration\RestManager;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;
use Bitrix\Rest;
use Bitrix\SalesCenter;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class SalesCenterControlPanelComponent extends CBitrixComponent implements Controllerable
{
	protected const PANEL_ID_PAYMENTS = 'salescenter-payments-panel';
	protected const PANEL_ID_SERVICES = 'salescenter-services-panel';
	protected const PANEL_ID_PAYMENT_SYSTEMS = 'salescenter-paymentSystems-panel';

	private const LABEL_NEW = 'new';

	protected $pages;

	public function executeComponent(): void
	{
		if(!Loader::includeModule('salescenter'))
		{
			ShowError(Loc::getMessage('SALESCENTER_CONTROL_PANEL_MODULE_ERROR'));
			return;
		}

		if(!Driver::getInstance()->isEnabled())
		{
			$this->arResult['isShowFeature'] = true;
			$this->includeComponentTemplate('limit');
			return;
		}

		if(!SaleManager::getInstance()->isManagerAccess())
		{
			ShowError(Loc::getMessage('SALESCENTER_ACCESS_DENIED'));
			return;
		}

		PullManager::getInstance()->subscribeOnConnect();
		$this->arResult['managerParams'] = Driver::getInstance()->getManagerParams();

		$paymentItemTiles[] = $this->getCrmStoreTile();

		foreach ($this->getMarketplaceItemsTile($this->getMarketplaceSalescenterItemCodeList()) as $marketplaceItem)
		{
			$paymentItemTiles[] = $marketplaceItem;
		}

		$paymentItemTiles = array_merge($paymentItemTiles, [
			$this->getPaymentsInChatTile(),
			$this->getPaymentsInSmsTile(),
			$this->getServicesInChatTile(),
			$this->getServicesInSmsTile(),
			$this->getConsultationTile(),
		]);

		if (Bitrix24Manager::getInstance()->isEnabled())
		{
			$paymentItemTiles[] = $this->getRecommendationItemTile();
		}

		$this->arResult['panels'] = [
			[
				'id' => static::PANEL_ID_PAYMENTS,
				'items' => $paymentItemTiles,
				'itemType' => 'BX.Salescenter.PaymentItem',
			],
			[
				'id' => static::PANEL_ID_PAYMENT_SYSTEMS,
				'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_SETTINGS_TITLE'),
				'items' => $this->getPanelItems(),
				'itemType' => 'BX.Salescenter.PaymentSystemItem',
			],
		];

		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('SALESCENTER_CONTROL_PANEL_TITLE'));

		$this->includeComponentTemplate();
	}

	/**
	 * @return array
	 */
	protected function getPanelItems() : array
	{
		$items = [
			$this->getSmsProviderTile(),
			$this->getPaymentSystemsTile(),
		];

		if (RestManager::getInstance()->isEnabled() && $this->hasDeliveryMarketplaceApp())
		{
			$items[] = $this->getDeliveryTile();
		}

		if (Driver::getInstance()->isCashboxEnabled())
		{
			$items[] = $this->getCashboxesTile();
		}

		$items[] = $this->getUserConsentTile();

		return $items;
	}

	protected function getCrmStoreTile(): array
	{
		$userConsentSettingPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.crmstore');
		$userConsentSettingPath = getLocalPath('components'.$userConsentSettingPath.'/slider.php');

		return [
			'id' => 'crmstore',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CRM_STORE_TILE'),
			'image' => $this->getImagePath().'crm-store-active.svg',
			'data' => [
				'isDependsOnConnection' => true,
				'url' => $userConsentSettingPath,
				'active' => \Bitrix\SalesCenter\Integration\LandingManager::getInstance()->isSiteExists(),
				'activeColor' => '#00B4AC',
				'activeImage' => $this->getImagePath().'crm-store.svg',
				'label' => self::LABEL_NEW,
			],
		];
	}

	/**
	 * @return array
	 */
	protected function getPaymentsInChatTile(): array
	{
		$menu = $this->getPaymentsMenu();
		$menu[] = [
			'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_HOW'),
			'onclick' => 'BX.Salescenter.Manager.openHowItWorks(arguments[0])',
		];

		return [
			'id' => 'payments-chat',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYMENTS_CHAT_TITLE'),
			'image' => $this->getImagePath().'payments-chat.svg',
			'data' => [
				'isDependsOnConnection' => true,
				'menu' => $menu,
				'active' => \Bitrix\SalesCenter\Integration\LandingManager::getInstance()->isSiteExists(),
				'activeColor' => '#FF5752',
				'activeImage' => $this->getImagePath().'payments-chat-active.svg',
			],
		];
	}

	protected function getPaymentsInSmsTile(): array
	{
		$menu = $this->getPaymentsMenu();
		$menu[] = [
			'delimiter' => true,
		];
		$menu[] = [
			'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_HOW'),
			'onclick' => 'BX.Salescenter.Manager.openHowSmsWorks(arguments[0])',
		];

		return [
			'id' => 'payments-sms',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYMENTS_SMS_TITLE'),
			'image' => $this->getImagePath().'payments-sms.svg',
			'data' => [
				'isDependsOnConnection' => true,
				'menu' => $menu,
				'active' => \Bitrix\SalesCenter\Integration\LandingManager::getInstance()->isSiteExists(),
				'activeColor' => '#EF678B',
				'activeImage' => $this->getImagePath().'payments-sms-active.svg',
			],
		];
	}

	protected function getPaymentsMenu(): array
	{
		return [
			[
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYMENT_SYSTEMS_MENU'),
				'onclick' => 'BX.Salescenter.ControlPanel.paymentSystemsTileClick();'
			],
			[
				'delimiter' => true,
			],
			[
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_ORDERS_MENU'),
				'onclick' => 'BX.Salescenter.Manager.openSlider(\'/shop/orders/list/\');',
			],
			[
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_DEALS_MENU'),
				'onclick' => 'BX.Salescenter.Manager.openSlider(\''.CrmManager::getInstance()->getDealsLink().'\');',
			],
		];
	}

	protected function getServicesInChatTile(): array
	{
		return [
			'id' => 'services-chat',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_SERVICES_CHAT_TITLE'),
			'image' => $this->getImagePath().'services-chat.svg',
			'data' => [
				'isDependsOnConnection' => true,
				'hasPagesMenu' => true,
				'menu' => $this->getServicesMenu('services-chat'),
				'active' => Driver::getInstance()->isSalesInChatActive(),
				'activeColor' => '#FEA800',
				'activeImage' => $this->getImagePath().'services-chat-active.svg',
				'reloadAction' => 'getServicesTile',
			],
		];
	}

	protected function getServicesInSmsTile(): array
	{
		return [
			'id' => 'services-sms',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_SERVICES_SMS_TITLE'),
			'image' => $this->getImagePath().'services-sms.svg',
			'data' => [
				'isDependsOnConnection' => true,
				'hasPagesMenu' => true,
				'menu' => $this->getServicesMenu('services-sms'),
				'active' => Driver::getInstance()->isSalesInChatActive(),
				'activeColor' => '#2DC5F5',
				'activeImage' => $this->getImagePath().'services-sms-active.svg',
				'reloadAction' => 'getServicesTile',
			],
		];
	}

	public function getServicesTileAction(string $id): array
	{
		return [
			'menu' => $this->getServicesMenu($id),
			'active' => Driver::getInstance()->isSalesInChatActive(),
		];
	}

	protected function getServicesMenu(string $id): array
	{
		if(!Loader::includeModule('salescenter'))
		{
			return [];
		}

		static $menu;
		if($menu === null)
		{
			$forms = CrmManager::getInstance()->getWebForms();
			$connectedWebFormIds = \Bitrix\SalesCenter\Integration\LandingManager::getInstance()->getConnectedWebFormIds();
			$forms = array_filter($forms, function($form) use ($connectedWebFormIds)
			{
				return !in_array($form['ID'], $connectedWebFormIds);
			});
			$menu = [
				[
					'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_FORM_PAGES_ADD'),
					'items' => $this->getAddNewPageMenu(true),
				],
			];
			if(\Bitrix\SalesCenter\Integration\LandingManager::getInstance()->isSiteExists())
			{
				$menu[] = [
					'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_FORM_PAGE_ADD'),
					'items' => $this->getAddNewWebformPageMenu($forms),
				];
			}
			$menu[] = [
				'delimiter' => true,
			];
			$formPages = $this->getPages(true);
			foreach($formPages as $page)
			{
				$menu[] = $this->getPageMenu($page);
			}
			if(!empty($formPages))
			{
				$menu[] = [
					'delimiter' => true,
				];
			}
			if(!\Bitrix\SalesCenter\Integration\LandingManager::getInstance()->isSiteExists())
			{
				$menu[] = [
					'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_CONNECT'),
					'onclick' => 'BX.Salescenter.ControlPanel.closeMenu();BX.Salescenter.ControlPanel.connectShop(\''.$id.'\');',
				];
				$menu[] = [
					'delimiter' => true,
				];
			}
			$menu[] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_HOW'),
				'onclick' => 'BX.Salescenter.Manager.openFormPagesHelp(arguments[0])',
			];
		}

		return $menu;
	}

	protected function getConsultationTile(): array
	{
		return [
			'id' => 'consultations',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CONSULTATIONS_TILE'),
			'image' => $this->getImagePath().'consultations.svg',
			'data' => [
				'isDependsOnConnection' => true,
				'hasPagesMenu' => true,
				'menu' => $this->getConsultationTileActionAction()['menu'],
				'active' => Driver::getInstance()->isSalesInChatActive(),
				'activeColor' => '#9BCD00',
				'activeImage' => $this->getImagePath().'consultations-active.svg',
				'reloadAction' => 'getConsultationTileAction',
			],
		];
	}

	public function getConsultationTileActionAction(): array
	{
		return [
			'menu' => $this->getConsultationTileMenu(),
			'active' => Driver::getInstance()->isSalesInChatActive(),
		];
	}

	protected function getConsultationTileMenu(): array
	{
		$menu = [];

		if(!Loader::includeModule('salescenter'))
		{
			return $menu;
		}
		if(!Driver::getInstance()->isSalesInChatActive())
		{
			return $menu;
		}

		$menu = [
			[
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_ADD'),
				'items' => $this->getAddNewPageMenu(),
			],
		];
		$pages = $this->getPages();
		if(!empty($pages))
		{
			$menu[] = [
				'delimiter' => true,
			];
			foreach($pages as $page)
			{
				$menu[] = $this->getPageMenu($page);
			}
		}
		if(!\Bitrix\SalesCenter\Integration\LandingManager::getInstance()->isSiteExists())
		{
			$menu[] = [
				'delimiter' => true,
			];
			$menu[] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_CONNECT'),
				'onclick' => 'BX.Salescenter.ControlPanel.closeMenu();BX.Salescenter.ControlPanel.connectShop(\'consultations\');',
			];
		}
		$menu[] = [
			'delimiter' => true,
		];
		$menu[] = [
			'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_HOW'),
			'onclick' => 'BX.Salescenter.Manager.openCommonPagesHelp(arguments[0])',
		];

		return $menu;
	}

	protected function getPaymentSystemsTile(): array
	{
		$paySystemPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem.panel');
		$paySystemPath = getLocalPath('components'.$paySystemPath.'/slider.php');
		$paySystemPath = new \Bitrix\Main\Web\Uri($paySystemPath);
		$paySystemPath->addParams([
			'analyticsLabel' => 'salescenterClickPaymentTile',
			'type' => 'main',
			'mode' => 'main'
		]);

		$filter = SaleManager::getInstance()->getPaySystemFilter();
		$isActive = (Sale\Internals\PaySystemActionTable::getCount($filter) > 0);

		return [
			'id' => 'payment-systems',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYMENT_SYSTEMS_TILE'),
			'image' => $this->getImagePath().'payment-systems.svg',
			'data' => [
				'url' => $paySystemPath->getLocator(),
				'active' => $isActive,
				'activeColor' => '#458CE4',
				'activeImage' => $this->getImagePath().'payment-systems-active.svg',
				'reloadAction' => 'getPaymentSystemsTile',
			],
		];
	}

	public function getPaymentSystemsTileAction(): array
	{
		Bitrix\Main\Loader::includeModule("sale");
		Bitrix\Main\Loader::includeModule("salescenter");

		$tile = $this->getPaymentSystemsTile();
		return [
			'active' => $tile['data']['active'],
		];
	}

	protected function getDeliveryTile(): array
	{
		$deliveryPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.delivery.panel');
		$deliveryPath = getLocalPath('components'.$deliveryPath.'/slider.php');
		$deliveryPath = new \Bitrix\Main\Web\Uri($deliveryPath);
		$deliveryPath->addParams([
			'analyticsLabel' => 'salescenterClickDeliveryTile',
			'type' => 'main',
			'mode' => 'main'
		]);

		$isActive = $this->hasDeliveryInstalledApp();

		return [
			'id' => 'delivery',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_DELIVERY_TILE'),
			'image' => $this->getImagePath().'delivery.svg',
			'data' => [
				'url' => $deliveryPath->getLocator(),
				'active' => $isActive,
				'activeColor' => '#80A802',
				'activeImage' => $this->getImagePath().'delivery-active.svg',
				'reloadAction' => 'getDeliveryTile',
			],
		];
	}

	private function hasDeliveryInstalledApp(): bool
	{
		$handlers = (new SalesCenter\Delivery\Handlers\HandlersRepository())
			->getCollection()
			->getInstallableInstalledItems();

		if (count($handlers) > 0)
		{
			return true;
		}

		$marketplaceAppCodeList = RestManager::getInstance()->getMarketplaceAppCodeList('delivery');
		$count = (int)Rest\AppTable::getCount([
			'=CODE' => $marketplaceAppCodeList,
			'SCOPE' => '%sale%',
			'=ACTIVE' => 'Y',
		]);

		return $count > 0;
	}

	private function hasDeliveryMarketplaceApp(): bool
	{
		$zone = $this->getZone();
		$partnerItems = RestManager::getInstance()->getByTag([
			RestManager::TAG_DELIVERY,
			RestManager::TAG_DELIVERY_RECOMMENDED,
			$zone
		]);

		return !empty($partnerItems['ITEMS']);
	}

	public function getDeliveryTileAction(): array
	{
		Bitrix\Main\Loader::includeModule("sale");
		Bitrix\Main\Loader::includeModule("salescenter");

		$tile = $this->getDeliveryTile();
		return [
			'active' => $tile['data']['active'],
		];
	}

	protected function getSmsProviderTile(): array
	{
		$path = \CComponentEngine::makeComponentPath('bitrix:salescenter.smsprovider.panel');
		$path = getLocalPath('components'.$path.'/slider.php');
		$path = new \Bitrix\Main\Web\Uri($path);
		$path->addParams([
			'analyticsLabel' => 'salescenterClickSmsProviderTile',
		]);

		return [
			'id' => 'smsprovider',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_SMS_PROVIDER_TILE'),
			'image' => $this->getImagePath().'sms-provider.svg',
			'data' => [
				'url' => $path->getLocator(),
				'active' => Bitrix\Crm\Integration\SmsManager::canSendMessage(),
				'activeColor' => '#00BEFA',
				'activeImage' => $this->getImagePath().'sms-provider-active.svg',
				'reloadAction' => 'getSmsProviderTile',
			],
		];
	}

	protected function getCashboxesTile(): array
	{
		$cashboxPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.cashbox.panel');
		$cashboxPath = getLocalPath('components'.$cashboxPath.'/slider.php');
		$cashboxPath = new \Bitrix\Main\Web\Uri($cashboxPath);
		$cashboxPath->addParams([
			'analyticsLabel' => 'salescenterClickCashboxTile',
		]);

		$filter = SaleManager::getInstance()->getCashboxFilter();
		$isActive = (Sale\Cashbox\Internals\CashboxTable::getCount($filter) > 0);

		return [
			'id' => 'cashboxes',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CASHBOXES_TILE_2'),
			'image' => $this->getImagePath().'cashboxes.svg',
			'data' => [
				'url' => $cashboxPath->getLocator(),
				'active' => $isActive,
				'activeColor' => '#A763E4',
				'activeImage' => $this->getImagePath().'cashboxes-active.svg',
				'reloadAction' => 'getCashboxesTile',
			],
		];
	}

	/**
	 * @param array $marketplaceCodeList
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getMarketplaceItemsTile(array $marketplaceCodeList): array
	{
		$marketplaceItems = [];

		$zone = $this->getZone();
		$installedMarketplaceItems = $this->getInstalledMarketplaceItemsByCodes($marketplaceCodeList);
		foreach ($marketplaceCodeList as $marketplaceCode)
		{
			if ($marketplaceApp = RestManager::getInstance()->getMarketplaceAppByCode($marketplaceCode))
			{
				$title = $marketplaceApp['LANG'][$zone]['NAME']
					?? $marketplaceApp['NAME']
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

				$marketplaceItems[$marketplaceApp['CODE']] = [
					'id' => $marketplaceApp['CODE'],
					'title' => $title,
					'image' => $img,
					'data' => [
						'appId' => isset($installedMarketplaceItems[$marketplaceCode])
							? $installedMarketplaceItems[$marketplaceCode]['ID']
							: $marketplaceApp['ID'],
						'code' => $marketplaceApp['CODE'],
						'itemSubType' => 'marketplaceApp',
						'active' => isset($installedMarketplaceItems[$marketplaceCode]),
						'hasOwnIcon' => $hasOwnIcon,
						'reloadAction' => 'getMarketplaceItemsTile',
						'label' => self::LABEL_NEW,
					],
				];
			}
		}

		return $marketplaceItems;
	}

	/**
	 * @param $id
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getMarketplaceItemsTileAction($id): array
	{
		$installedMarketplaceItems = $this->getInstalledMarketplaceItemsByCodes([$id]);
		return [
			'active' => isset($installedMarketplaceItems[$id]),
		];
	}

	/**
	 * @param $marketplaceCodeList
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getInstalledMarketplaceItemsByCodes($marketplaceCodeList): array
	{
		$result = [];

		$installedMarketplaceItems = Rest\AppTable::getList([
			'select' => ['*'],
			'filter' => [
				'=CODE' => $marketplaceCodeList,
				'=ACTIVE' => 'Y',
			],
		])->fetchAll();

		foreach ($installedMarketplaceItems as $installedMarketplaceItem)
		{
			$result[$installedMarketplaceItem['CODE']] = $installedMarketplaceItem;
		}

		return $result;
	}

	/**
	 * @return array|string[]
	 * @throws Main\SystemException
	 */
	private function getMarketplaceSalescenterItemCodeList(): array
	{
		$result = [];

		$partnerItems = RestManager::getInstance()->getByTag([
			RestManager::TAG_SALESCENTER,
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

	private function getRecommendationItemTile(): array
	{
		return [
			'id' => 'recommendation',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_RECOMMEND'),
			'image' => $this->getImagePath().'recommend.svg',
			'data' => [
				'active' => false,
			],
		];
	}

	protected function getPages(bool $isWebForm = false): array
	{
		$result = [];

		if($this->pages === null)
		{
			$pageController = new \Bitrix\SalesCenter\Controller\Page();
			$this->pages = $pageController->getList();
		}

		foreach($this->pages as $page)
		{
			if($isWebForm)
			{
				if($page->isWebform())
				{
					$result[] = $page;
				}
			}
			else
			{
				if(!$page->isWebform())
				{
					$result[] = $page;
				}
			}
		}

		return $result;
	}

	protected function getAddNewWebformPageMenu(array $forms): array
	{
		$list = [
			[
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_FORMS_ADD'),
				'onclick' => 'BX.Salescenter.ControlPanel.closeMenu();BX.Salescenter.Manager.addNewForm().then(BX.Salescenter.ControlPanel.dropPageMenus);'
			],
		];
		if(!empty($forms))
		{
			$list[] = [
				'delimiter' => true,
			];
			foreach($forms as $form)
			{
				$list[] = [
					'text' => htmlspecialcharsbx($form['NAME']),
					'onclick' => 'BX.Salescenter.ControlPanel.closeMenu();BX.Salescenter.Manager.addNewFormPage('.$form['ID'].').then(BX.Salescenter.ControlPanel.dropPageMenus);',
				];
			}
		}

		return $list;
	}

	protected function getAddNewPageMenu(bool $isWebform = false): array
	{
		$pageStubObject = [];
		if($isWebform)
		{
			$pageStubObject['isWebform'] = 'Y';
		}
		return [
			[
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_ADD_SITE'),
				'onclick' => 'BX.Salescenter.ControlPanel.closeMenu();BX.Salescenter.Manager.addSitePage('.$isWebform.').then(BX.Salescenter.ControlPanel.dropPageMenus);',
			],
			[
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_ADD_CUSTOM'),
				'onclick' => 'BX.Salescenter.ControlPanel.closeMenu();BX.Salescenter.Manager.addCustomPage('.CUtil::PhpToJSObject($pageStubObject).').then(BX.Salescenter.ControlPanel.dropPageMenus);',
			],
		];
	}

	protected function getPageMenu(\Bitrix\SalesCenter\Model\Page $page): array
	{
		$list = [];
		$controller = new \Bitrix\SalesCenter\Controller\Page();
		$pageData = $controller->getAction($page)['page'];
		if($page->getLandingId() > 0)
		{
			$list[] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_EDIT'),
				'onclick' => 'BX.Salescenter.ControlPanel.closeMenu();BX.Salescenter.Manager.editLandingPage(\''.$page->getLandingId().'\')',
			];
		}
		else
		{
			$list[] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_EDIT'),
				'onclick' => 'BX.Salescenter.ControlPanel.closeMenu();BX.Salescenter.Manager.addCustomPage('.CUtil::PhpToJSObject($pageData).').then(BX.Salescenter.ControlPanel.dropPageMenus);',
			];
		}
		$list[] = [
			'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_COPY'),
			'onclick' => 'BX.Salescenter.Manager.copyUrl(\''.$page->getUrl().'\', arguments[0])',
		];
		if($page->getLandingId() > 0)
		{
			$list[] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_HIDE'),
				'onclick' => 'BX.Salescenter.ControlPanel.closeMenu();BX.Salescenter.Manager.hidePage('.CUtil::PhpToJSObject($pageData).').then(BX.Salescenter.ControlPanel.dropPageMenus);',
			];
		}
		else
		{
			$list[] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_DELETE'),
				'onclick' => 'BX.Salescenter.ControlPanel.closeMenu();BX.Salescenter.Manager.deleteUrl('.CUtil::PhpToJSObject($pageData).').then(BX.Salescenter.ControlPanel.dropPageMenus);',
			];
		}

		return [
			'text' => htmlspecialcharsbx($page->getName()),
			'items' => $list,
		];
	}

	public function getCashboxesTileAction(): array
	{
		Bitrix\Main\Loader::includeModule("sale");
		Bitrix\Main\Loader::includeModule("salescenter");

		$tile = $this->getCashboxesTile();

		return [
			'active' => $tile['data']['active'],
		];
	}

	protected function getUserConsentTile(): array
	{
		$userConsentSettingPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.userconsent');
		$userConsentSettingPath = getLocalPath('components'.$userConsentSettingPath.'/slider.php');

		$menu = $this->getUserConsentMenu();

		return [
			'id' => 'userconsent',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_USERCONSENT_TILE'),
			'image' => $this->getImagePath().'userconsent.svg',
			'data' => [
				'url' => $userConsentSettingPath,
				'menu' => $menu,
				'active' => !empty($menu),
				'activeColor' => '#2C7AB2',
				'activeImage' => $this->getImagePath().'userconsent-active.svg',
				'reloadAction' => 'getUserConsentTile',
			],
		];
	}

	public function getUserConsentTileAction(): array
	{
		$menu = $this->getUserConsentMenu();

		return [
			'active' => !empty($menu),
			'menu' => $menu,
		];
	}

	protected function getUserConsentMenu(): array
	{
		$menu = [];

		$userConsentSettingPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.userconsent');
		$userConsentSettingPath = getLocalPath('components'.$userConsentSettingPath.'/slider.php');

		if ($this->isUserConsentActive())
		{
			$userConsentId = $this->getUserConsentId();
			if(!$userConsentId)
			{
				$userConsentId = $this->getDefaultUserConsentId();
			}
			if($userConsentId)
			{
				$userConsentUrl = "/settings/configs/userconsent/consents/{$userConsentId}/?AGREEMENT_ID={$userConsentId}&apply_filter=Y";

				$menu = [
					[
						'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_USERCONSENT_EDIT'),
						'onclick' => 'BX.Salescenter.ControlPanel.closeMenu();BX.Salescenter.Manager.openSlider(\''.$userConsentSettingPath.'\').then(BX.Salescenter.ControlPanel.reloadUserConsentTile);',
					],
					[
						'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_USERCONSENT_LIST'),
						'onclick' => 'BX.Salescenter.ControlPanel.closeMenu();BX.Salescenter.Manager.openSlider(\''.$userConsentUrl.'\');',
					]
				];
			}
		}

		return $menu;
	}

	private function isUserConsentActive(): bool
	{
		return (Bitrix\Main\Config\Option::get('salescenter', '~SALESCENTER_USER_CONSENT_ACTIVE', 'Y') === 'Y');
	}

	private function getUserConsentId(): ?int
	{
		$consent = Bitrix\Main\Config\Option::get('salescenter', '~SALESCENTER_USER_CONSENT_ID');
		if(!$consent)
		{
			return null;
		}

		return (int)$consent;
	}

	private function getDefaultUserConsentId(): int
	{
		$agreementId = 0;
		if (Loader::includeModule('imopenlines'))
		{
			$configManager = new Bitrix\ImOpenLines\Config();
			$result = $configManager->getList(
				[
					'select' => ['AGREEMENT_ID'],
					'filter' => ['>AGREEMENT_ID' => 0],
					'order' => ['ID'],
					'limit' => 1
				]
			);
			foreach ($result as $id => $config)
			{
				$agreementId = (int)$config['AGREEMENT_ID'];
			}

			if ($agreementId)
			{
				Bitrix\Main\Config\Option::set('salescenter', '~SALESCENTER_USER_CONSENT_ID', $agreementId);
				Bitrix\Main\Config\Option::set('salescenter', '~SALESCENTER_USER_CONSENT_CHECKED', 'Y');
			}
		}

		return $agreementId;
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [];
	}

	/**
	 * @return string
	 */
	protected function getImagePath()
	{
		return $this->__path.'/templates/.default/images/';
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