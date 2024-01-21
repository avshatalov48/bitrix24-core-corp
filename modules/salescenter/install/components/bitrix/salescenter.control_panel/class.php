<?php

use Bitrix\Crm\Service\Container;
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
use Bitrix\SalesCenter\Integration\CatalogManager;
use Bitrix\Rest;
use Bitrix\SalesCenter;
use Bitrix\Catalog;
use Bitrix\Salescenter\Restriction\ToolAvailabilityManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class SalesCenterControlPanelComponent extends CBitrixComponent implements Controllerable
{
	protected const PANEL_ID_PAYMENTS = 'salescenter-payments-panel';
	protected const PANEL_ID_SERVICES = 'salescenter-services-panel';
	protected const PANEL_ID_PAYMENT_SYSTEMS = 'salescenter-paymentSystems-panel';
	private const TITLE_LENGTH_LIMIT = 40;
	private const MARKETPLACE_APP_LIMIT = 15;

	private const LABEL_NEW = 'new';

	protected $pages;

	public function executeComponent(): void
	{
		if(!Loader::includeModule('salescenter'))
		{
			ShowError(Loc::getMessage('SALESCENTER_CONTROL_PANEL_MODULE_ERROR'));
			return;
		}

		if (Loader::includeModule('crm'))
		{
			CAllCrmInvoice::installExternalEntities();
		}

		if(!Driver::getInstance()->isEnabled())
		{
			$this->arResult['isShowFeature'] = true;
			$this->includeComponentTemplate('limit');
			return;
		}

		if (!ToolAvailabilityManager::getInstance()->checkSalescenterAvailability())
		{
			$this->includeComponentTemplate('tool_disabled');

			return;
		}

		if(!SaleManager::getInstance()->isManagerAccess())
		{
			ShowError(Loc::getMessage('SALESCENTER_ACCESS_DENIED'));
			return;
		}

		PullManager::getInstance()->subscribeOnConnect();
		$this->arResult['managerParams'] = Driver::getInstance()->getManagerParams();

		$this->arResult['panels'] = [
			[
				'id' => static::PANEL_ID_PAYMENTS,
				'items' => $this->getPaymentsPanelItems(),
				'itemType' => 'BX.Salescenter.PaymentItem',
			],
			[
				'id' => static::PANEL_ID_PAYMENT_SYSTEMS,
				'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_SETTINGS_TITLE'),
				'items' => $this->getSettingsPanelItems(),
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
	protected function getPaymentsPanelItems(): array
	{
		$tiles = [
			$this->getCrmWithEshopTile(),
			$this->getCrmStoreTile(),
		];

		if (
			CrmManager::getInstance()->isEnabled()
			&& CrmManager::getInstance()->isTerminalAvailable()
			&& Container::getInstance()->getIntranetToolsManager()->checkTerminalAvailability()
		)
		{
			$tiles[] = $this->getTerminalTile();
		}

		$tiles[] = $this->getCrmFormTile();

		if (RestManager::getInstance()->isEnabled())
		{
			foreach ($this->getMarketplaceItemsTile($this->getMarketplaceSalescenterItemCodeList()) as $marketplaceItem)
			{
				$tiles[] = $marketplaceItem;
			}
		}

		$tiles = array_merge($tiles, [
			$this->getPaymentsInChatTile(),
			$this->getPaymentsInSmsTile(),
			$this->getServicesInChatTile(),
			$this->getServicesInSmsTile(),
			$this->getConsultationTile(),
		]);

		if (RestManager::getInstance()->isEnabled())
		{
			foreach ($this->getMarketplaceItemsTile($this->getMarketplaceSalesCenterItemCodeListAfter()) as $marketplaceItem)
			{
				$tiles[] = $marketplaceItem;
			}
		}

		if (Bitrix24Manager::getInstance()->isEnabled())
		{
			$tiles[] = $this->getRecommendationItemTile();
		}

		return $tiles;
	}

	/**
	 * @return array
	 */
	protected function getSettingsPanelItems(): array
	{
		$items = [
			$this->getSmsProviderTile(),
		];

		if (SaleManager::getInstance()->isManagerAccess(true))
		{
			$items[] = $this->getPaymentSystemsTile();

			if (Driver::getInstance()->hasDeliveryServices())
			{
				$items[] = $this->getDeliveryTile();
			}

			if (Driver::getInstance()->isCashboxEnabled())
			{
				$items[] = $this->getCashboxesTile();
			}

			if (CatalogManager::getInstance()->isEnabled() && Driver::getInstance()->isCashboxEnabled())
			{
				$items[] = $this->getCatalogAgentContractTile();
			}
		}

		$items[] = $this->getUserConsentTile();

		return $items;
	}

	protected function getCrmWithEshopTile(): array
	{
		$site = \Bitrix\SalesCenter\Integration\LandingManager::getInstance()->getCrmStoreSite();
		$isActive = ($site !== null);

		$tile = [
			'id' => 'crm-with-eshop',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CRM_WITH_ESHOP_TILE'),
			'image' => $this->getImagePath().'crm-with-eshop.svg',
			'data' => [
				'isDependsOnConnection' => false,
				'active' => $isActive,
				'activeColor' => '#DC3F49',
				'activeImage' => $this->getImagePath().'crm-with-eshop-active.svg',
				'reloadAction' => 'getCrmWithEshopTile',
				'sliderOptions' => [
					'width' => 1200,
				],
			],
		];

		if ($isActive)
		{
			$menu = [];

			if ($dealsLink = CrmManager::getInstance()->getDealsLink())
			{
				$menu[] = [
					'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_DEALS_MENU'),
					'onclick' => "BX.Salescenter.Manager.openSlider('" . \CUtil::JSEscape($dealsLink) . "');"
				];
			}

			$menu[] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYMENT_SYSTEMS_MENU'),
				'onclick' => 'BX.Salescenter.ControlPanel.paymentSystemsTileClick();'
			];

			$publicUrl = (string)$site['PUBLIC_URL'];
			if ($publicUrl !== '')
			{
				$menu[] = [
					'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CRM_WITH_ESHOP_OPEN_SITE'),
					'onclick' => "window.open('" . \CUtil::JSEscape($publicUrl) . "', '_blank');"
				];
			}

			$menu[] = [
				'delimiter' => true,
			];

			$menu[] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CRM_WITH_ESHOP_HOW_WORKS'),
				'onclick' => 'BX.Salescenter.Manager.openHowCrmStoreWorks(arguments[0])',
			];

			$tile['data']['menu'] = $menu;
		}
		else
		{
			$tile['data']['url'] = '/shop/stores/site/edit/0/?super=Y';
		}

		return $tile;
	}

	public function getCrmWithEshopTileAction(): array
	{
		if (!Loader::includeModule('salescenter'))
		{
			return [];
		}

		$tile = $this->getCrmWithEshopTile();

		return [
			'menu' => $tile['data']['menu'],
			'active' => $tile['data']['active'],
		];
	}

	protected function getCrmStoreTile(): array
	{
		$userConsentSettingPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.crmstore');
		$userConsentSettingPath = getLocalPath('components'.$userConsentSettingPath.'/slider.php');

		return [
			'id' => 'crmstore',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CRM_STORE_TILE_2_MSGVER_1'),
			'image' => $this->getImagePath().'crm-store-active.svg',
			'data' => [
				'isDependsOnConnection' => true,
				'url' => $userConsentSettingPath,
				'active' => \Bitrix\SalesCenter\Integration\LandingManager::getInstance()->isSiteExists(),
				'activeColor' => '#00B4AC',
				'activeImage' => $this->getImagePath().'crm-store.svg',
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

	/**
	 * @return array
	 */
	protected function getPaymentsMenu(): array
	{
		$result = [];

		$result[] = [
			'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYMENT_SYSTEMS_MENU'),
			'onclick' => 'BX.Salescenter.ControlPanel.paymentSystemsTileClick();'
		];

		$result[] = ['delimiter' => true];

		if (CCrmSaleHelper::isWithOrdersMode())
		{
			$result[] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_ORDERS_MENU'),
				'onclick' => 'BX.Salescenter.Manager.openSlider(\'/shop/orders/list/\');',
			];
		}

		$result[] = [
			'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_DEALS_MENU'),
			'onclick' => 'BX.Salescenter.Manager.openSlider(\''.CrmManager::getInstance()->getDealsLink().'\');',
		];

		return $result;
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

	protected function getCrmFormTile(): array
	{
		$path = \CComponentEngine::makeComponentPath('bitrix:salescenter.crmform.panel');
		$path = getLocalPath('components'.$path.'/slider.php');
		$path = new \Bitrix\Main\Web\Uri($path);
		$path->addParams([
			'analyticsLabel' => 'salescenterClickCrmFormTile',
		]);

		\CBitrixComponent::includeComponentClass('bitrix:salescenter.crmform.panel');
		$isTileActive = \SalesCenterCrmFormPanel::hasFormsWithTemplates();

		return [
			'id' => 'crmform',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CRM_FORM'),
			'image' => $this->getImagePath().'crm-form.svg',
			'data' => [
				'url' => (string)$path,
				'active' => $isTileActive,
				'activeColor' => '#2FC6F6',
				'activeImage' => $this->getImagePath().'crm-form-active.svg',
				'isDependsOnConnection' => false,
			],
		];
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

	public function getSmsProviderTileAction(): array
	{
		Bitrix\Main\Loader::includeModule("crm");
		Bitrix\Main\Loader::includeModule("sale");
		Bitrix\Main\Loader::includeModule("salescenter");

		$tile = $this->getSmsProviderTile();

		return [
			'active' => $tile['data']['active'],
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
					$img = $marketplaceApp['ICON_PRIORITY'] ?? $marketplaceApp['ICON'];
				}
				else
				{
					$hasOwnIcon = false;
					$img = $this->getImagePath().'marketplace_default.svg';
				}

				$marketplaceItems[$marketplaceApp['CODE']] = [
					'id' => $marketplaceApp['CODE'],
					'title' => $this->getFormattedTitle($title),
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
					],
				];
			}
		}

		return $marketplaceItems;
	}

	/**
	 * @param string $title
	 * @return string
	 */
	private function getFormattedTitle(string $title): string
	{
		if (mb_strlen($title) > self::TITLE_LENGTH_LIMIT)
		{
			$title = mb_substr($title, 0, self::TITLE_LENGTH_LIMIT - 3) . '...';
		}

		return $title;
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
		return $this->getMarketplaceItemCodeList(
			[
				RestManager::TAG_SALESCENTER,
			]
		);
	}

	/**
	 * @return array|string[]
	 * @throws Main\SystemException
	 */
	private function getMarketplaceSalesCenterItemCodeListAfter(): array
	{
		return $this->getMarketplaceItemCodeList(
			[
				RestManager::TAG_SALES_CENTER,
				RestManager::TAG_PARTNERS,
				$this->getZone(),
			],
			self::MARKETPLACE_APP_LIMIT
		);
	}

	/**
	 * @param array $tags
	 * @param int|bool $pageSize
	 *
	 * @return array|string[]
	 * @throws Main\SystemException
	 */
	private function getMarketplaceItemCodeList(array $tags, $pageSize = false): array
	{
		$result = [];

		$partnerItems = RestManager::getInstance()->getByTag($tags, false, $pageSize);
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
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_USERCONSENT_TILE_2'),
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

	protected function getTerminalTile(): array
	{
		$terminalPath = '/terminal/';
		$terminalPaymentReferenceField = CrmManager::getInstance()->getTerminalPaymentRuntimeReferenceField();

		$runtime = [];
		if ($terminalPaymentReferenceField)
		{
			$runtime[] = $terminalPaymentReferenceField;
		}

		$terminalPayment = Sale\Payment::getList([
			'select' => ['ID'],
			'runtime' => $runtime,
			'limit' => 1,
		])->fetch();

		return [
			'id' => 'terminal',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_TERMINAL_PAYMENT_TILE'),
			'image' => $this->getImagePath() . 'terminal.svg',
			'data' => [
				'isDependsOnConnection' => true,
				'url' => $terminalPath,
				'active' => (bool)$terminalPayment,
				'activeColor' => '#0B66C3',
				'activeImage' => $this->getImagePath() . 'terminal-active.svg',
				'label' => self::LABEL_NEW,
			],
		];
	}

	private function getCatalogAgentContractTile(): array
	{
		$catalogAgentContractPath = '/agent_contract/';

		return [
			'id' => 'catalog-agent-contract',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_AGENT_CONTRACT_TILE'),
			'image' => $this->getImagePath() . 'catalog-agent-contract.svg',
			'data' => [
				'url' => $catalogAgentContractPath,
				'active' => $this->isAgentContractExist(),
				'activeColor' => '#2C7AB2',
				'activeImage' => $this->getImagePath() . 'catalog-agent-contract-active.svg',
				'reloadAction' => 'isAgentContractExistTile',
			],
		];
	}

	private function isAgentContractExist(): bool
	{
		return CatalogManager::getInstance()->isEnabled() && Catalog\AgentContractTable::getCount();
	}

	public function isAgentContractExistTileAction(): array
	{
		if (!Loader::includeModule("salescenter"))
		{
			return [];
		}

		return [
			'active' => $this->isAgentContractExist(),
		];
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
