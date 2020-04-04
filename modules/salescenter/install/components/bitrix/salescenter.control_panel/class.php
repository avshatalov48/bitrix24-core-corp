<?php

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\SalesCenter\Driver;
use Bitrix\Sale;
use Bitrix\Salescenter;
use Bitrix\SalesCenter\Integration\CrmManager;
use Bitrix\SalesCenter\Integration\PullManager;
use Bitrix\SalesCenter\Integration\SaleManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class SalesCenterControlPanelComponent extends CBitrixComponent implements Controllerable
{
	protected $panelId = 'salescenter-control-panel';
	protected $deliveryId = 'salescenter-control-panel-delivery';
	protected $cashboxId = 'salescenter-control-panel-cashbox';

	protected $deliveryListTableId = 'tbl_sale_delivery_list';
	protected $sefFolder = '/shop/settings/';

	/**
	 * @return mixed|void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function executeComponent()
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

		$this->arResult['panelParams'] = [
			'id' => $this->panelId,
			'items' => $this->getPanelItems(),
		];

//		$this->arResult['deliveryParams'] = [
//			'id' => $this->deliveryId,
//			'items' => $this->getDeliveryPanelItems(),
//		];

		$this->arResult['cashboxParams'] = [
			'id' => $this->cashboxId,
			'items' => array_merge($this->getCashboxItems(), $this->getUserConsentItems()),
		];

		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('SALESCENTER_CONTROL_PANEL_TITLE'));

		$this->includeComponentTemplate();
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getPanelItems()
	{
		$items = [];
		$items[] = $this->getStoreChatTile();

		if(CrmManager::getInstance()->isShowSmsTile())
		{
			$items[] = $this->getStoreSmsTile();
		}

		$paySystemList = $this->getPaySystemItems();
		foreach ($paySystemList as $paySystem)
		{
			$items[] = $paySystem;
		}

		$paySystemExtraList = $this->getPaySystemExtraItems();
		foreach ($paySystemExtraList as $paySystemExtra)
		{
			$items[] = $paySystemExtra;
		}

		return $items;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getDeliveryPanelItems()
	{
		$items = [];

		$deliveryList = $this->getDeliveryItems();
		foreach ($deliveryList as $delivery)
		{
			$items[] = $delivery;
		}

		$deliveryExtraList = $this->getDeliveryExtraItems();
		foreach ($deliveryExtraList as $deliveryExtra)
		{
			$items[] = $deliveryExtra;
		}

		return $items;
	}

	/**
	 * @return array
	 */
	protected function getStoreChatTile()
	{
		return [
			'id' => 'store-chats',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_STORE_CHAT_TILE'),
			'image' => $this->getImagePath().'chat.svg',
			'itemSelected' => Driver::getInstance()->isSalesInChatActive(),
			'itemSelectedColor' => '#FF5752',
			'itemSelectedImage' => $this->getImagePath().'chat_s.svg',
		];
	}

	protected function getStoreSmsTile()
	{
		return [
			'id' => 'store-sms',
			'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_STORE_SMS_TILE'),
			'image' => $this->getImagePath().'sms.svg',
			'itemSelected' => Driver::getInstance()->isSalesInChatActive(),
			'itemSelectedColor' => '#EF678B',
			'itemSelectedImage' => $this->getImagePath().'sms_s.svg',
		];
	}

	/**
	 * @return array
	 */
	public function getChatTileMenuAction()
	{
		$result = $this->getPagesMenu();

		if(!empty($result))
		{
			$result['id'] = 'store-chat-menu';

			$result['items'][] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_HOW'),
				'onclick' => 'BX.Salescenter.Manager.openHowItWorks(arguments[0])',
			];
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getSmsTileMenuAction()
	{
		$result = $this->getPagesMenu();

		if(!empty($result))
		{
			$result['id'] = 'store-sms-menu';

			$result['items'][] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_SMS_SETTINGS'),
				'onclick' => 'window.open(\'/crm/configs/sms/\', \'_blank\');',
			];

			$result['items'][] = [
				'delimiter' => true,
			];

			$result['items'][] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_SMS_HOW'),
				'onclick' => 'BX.Salescenter.Manager.openHowSmsWorks(arguments[0])',
			];
		}

		return $result;
	}

	/**
	 * @return array
	 */
	protected function getPagesMenu()
	{
		if(!Loader::includeModule('salescenter'))
		{
			return [];
		}
		if(!Driver::getInstance()->isSalesInChatActive())
		{
			return [];
		}
		$pageList = [
			[
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_ADD'),
				'items' => $this->getAddNewPageMenu(),
			],
		];
		$pageController = new \Bitrix\SalesCenter\Controller\Page();
		$allPages = $pageController->getList();
		$pages = $formPages = [];
		foreach($allPages as $page)
		{
			if($page->isWebform())
			{
				$formPages[] = $page;
			}
			else
			{
				$pages[] = $page;
			}
		}
		if(!empty($pages))
		{
			$pageList [] = [
				'delimiter' => true,
			];
		}
		foreach($pages as $page)
		{
			$pageList[] = $this->getPageMenu($page);
		}
		$forms = CrmManager::getInstance()->getWebForms();
		$connectedWebFormIds = \Bitrix\SalesCenter\Integration\LandingManager::getInstance()->getConnectedWebFormIds();
		$forms = array_filter($forms, function($form) use ($connectedWebFormIds)
		{
			return !in_array($form['ID'], $connectedWebFormIds);
		}
		);
		$formList = [
			[
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_FORM_PAGES_ADD'),
				'items' => $this->getAddNewPageMenu(true),
			],
		];
		if(\Bitrix\SalesCenter\Integration\LandingManager::getInstance()->isSiteExists())
		{
			$formList[] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_FORM_PAGE_ADD'),
				'items' => $this->getAddNewWebformPageMenu($forms),
			];
		}
		if(!empty($formPages))
		{
			$formList[] = [
				'delimiter' => true,
			];
			foreach($formPages as $page)
			{
				$formList[] = $this->getPageMenu($page);
			}
		}
		$result = [
			'items' => [
				[
					'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES'),
					'items' => $pageList,
				],
				[
					'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_FORMS'),
					'items' => $formList,
				],
				[
					'delimiter' => true,
				],
			],
		];

		if(!\Bitrix\SalesCenter\Integration\LandingManager::getInstance()->isSiteExists())
		{
			$result['items'][] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_CONNECT'),
				'onclick' => 'BX.Salescenter.ControlPanel.connectShop();',
			];
			$result['items'][] = [
				'delimiter' => true,
			];
		}

		return $result;
	}

	/**
	 * @param array $forms
	 * @return array
	 */
	protected function getAddNewWebformPageMenu(array $forms)
	{
		$list = [
			[
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_FORMS_ADD'),
				'onclick' => 'BX.Salescenter.ControlPanel.hideMenu(\'store-chat-menu\');BX.Salescenter.Manager.addNewForm().then(BX.Salescenter.ControlPanel.reloadStoreChatsMenu);'
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
					'onclick' => 'BX.Salescenter.ControlPanel.hideMenu(\'store-chat-menu\');BX.Salescenter.Manager.addNewFormPage('.$form['ID'].').then(BX.Salescenter.ControlPanel.reloadStoreChatsMenu);',
				];
			}
		}

		return $list;
	}

	/**
	 * @param bool $isWebform
	 * @return array
	 */
	protected function getAddNewPageMenu($isWebform = false)
	{
		$pageStubObject = [];
		if($isWebform)
		{
			$pageStubObject['isWebform'] = 'Y';
		}
		return [
			[
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_ADD_SITE'),
				'onclick' => 'BX.Salescenter.ControlPanel.hideMenu(\'store-chat-menu\');BX.Salescenter.Manager.addSitePage('.$isWebform.').then(BX.Salescenter.ControlPanel.reloadStoreChatsMenu);',
			],
			[
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_ADD_CUSTOM'),
				'onclick' => 'BX.Salescenter.ControlPanel.hideMenu(\'store-chat-menu\');BX.Salescenter.Manager.addCustomPage('.CUtil::PhpToJSObject($pageStubObject).').then(BX.Salescenter.ControlPanel.reloadStoreChatsMenu);',
			],
		];
	}

	/**
	 * @param \Bitrix\SalesCenter\Model\Page $page
	 * @return array
	 */
	protected function getPageMenu(\Bitrix\SalesCenter\Model\Page $page)
	{
		$list = [];
		$controller = new \Bitrix\SalesCenter\Controller\Page();
		$pageData = $controller->getAction($page)['page'];
		if($page->getLandingId() > 0)
		{
			$list[] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_EDIT'),
				'onclick' => 'BX.Salescenter.ControlPanel.hideMenu(\'store-chat-menu\');BX.Salescenter.Manager.editLandingPage(\''.$page->getLandingId().'\')',
			];
		}
		else
		{
			$list[] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_EDIT'),
				'onclick' => 'BX.Salescenter.ControlPanel.hideMenu(\'store-chat-menu\');BX.Salescenter.Manager.addCustomPage('.CUtil::PhpToJSObject($pageData).').then(BX.Salescenter.ControlPanel.reloadStoreChatsMenu);',
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
				'onclick' => 'BX.Salescenter.ControlPanel.hideMenu(\'store-chat-menu\');BX.Salescenter.Manager.hidePage('.CUtil::PhpToJSObject($pageData).').then(BX.Salescenter.ControlPanel.reloadStoreChatsMenu);',
			];
		}
		else
		{
			$list[] = [
				'text' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CHAT_PAGES_DELETE'),
				'onclick' => 'BX.Salescenter.Manager.deleteUrl('.CUtil::PhpToJSObject($pageData).').then(BX.Salescenter.ControlPanel.reloadStoreChatsMenu);',
			];
		}

		return [
			'text' => htmlspecialcharsbx($page->getName()),
			'items' => $list,
		];
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getCashboxItems()
	{
		if(!Driver::getInstance()->isCashboxEnabled())
		{
			$this->arResult['cashboxTitleCode'] = 'SALESCENTER_CONTROL_PANEL_CONSENT_TITLE';
			return [];
		}

		$cashboxes = [];
		$cashboxDescriptions = [
			[
				'id' => 'atol',
				'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CASHBOX_ATOL'),
				'image' => $this->getImagePath().'atol.svg',
				'itemSelectedColor' => '#ED1B2F',
				'itemSelectedImage' => $this->getImagePath().'atol_s.svg',
				'itemSelected' => false,
				'data' => [
					'type' => 'cashbox',
					'handler' => '\\Bitrix\\Sale\\Cashbox\\CashboxAtolFarmV4',
					'connectPath' => $this->getCashboxEditUrl([
						'handler' => '\\Bitrix\\Sale\\Cashbox\\CashboxAtolFarmV4',
						'preview' => 'y',
					]),
					'showMenu' => false,
				],
			],
			[
				'id' => 'orangedata',
				'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CASHBOX_ORANGE_DATA'),
				'image' => $this->getImagePath().'orangedata.svg',
				'itemSelectedColor' => '#FF9A01',
				'itemSelectedImage' => $this->getImagePath().'orangedata_s.svg',
				'itemSelected' => false,
				'data' => [
					'type' => 'cashbox',
					'handler' => '\\Bitrix\\Sale\\Cashbox\\CashboxOrangeData',
					'connectPath' => $this->getCashboxEditUrl([
						'handler' => '\\Bitrix\\Sale\\Cashbox\\CashboxOrangeData',
						'preview' => 'y',
					]),
					'showMenu' => false,
				],
			],
		];

		$this->arResult['cashboxTitleCode'] = 'SALESCENTER_CONTROL_PANEL_CASHBOX_CONSENT_TITLE';

		$filter = SaleManager::getInstance()->getCashboxFilter(false);
		$cashboxList = Sale\Cashbox\Internals\CashboxTable::getList([
			'select' => ['ID', 'ACTIVE', 'NAME', 'HANDLER'],
			'filter' => $filter,
		]);
		while($cashbox = $cashboxList->fetch())
		{
			if(!isset($cashboxes[$cashbox['HANDLER']]))
			{
				$cashboxes[$cashbox['HANDLER']] = [];
			}
			$cashboxes[$cashbox['HANDLER']][] = $cashbox;
		}

		foreach($cashboxDescriptions as &$cashboxDescription)
		{
			if(isset($cashboxes[$cashboxDescription['data']['handler']]) && is_array($cashboxes[$cashboxDescription['data']['handler']]))
			{
				$cashboxDescription['data']['menuItems'] = $this->getCashboxMenu($cashboxes[$cashboxDescription['data']['handler']]);
				foreach($cashboxes[$cashboxDescription['data']['handler']] as $handlerCashbox)
				{
					if($handlerCashbox['ACTIVE'] === 'Y')
					{
						$cashboxDescription['itemSelected'] = true;
					}
					$cashboxDescription['data']['showMenu'] = true;
				}
			}
		}

		return $cashboxDescriptions;
	}

	/**
	 * @param array $cashboxes
	 * @return array
	 */
	protected function getCashboxMenu(array $cashboxes)
	{
		$result = [];

		foreach($cashboxes as $cashbox)
		{
			if(empty($result))
			{
				$result = [
					[
						'NAME' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CASHBOX_ADD'),
						'LINK' => $this->getCashboxEditUrl([
							'handler' => $cashbox['HANDLER'],
						]),
					],
					[
						'DELIMITER' => true,
					],
				];
			}
			$result[] = [
				'NAME' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CASHBOX_SETTINGS', [
					'#CASHBOX_NAME#' => htmlspecialcharsbx($cashbox['NAME'])
				]),
				'LINK' => $this->getCashboxEditUrl(['id' => $cashbox['ID'], 'handler' => $cashbox['HANDLER']]),
			];
		}

		$result[] = [
			'DELIMITER' => true,
		];

		$result[] = [
			'NAME' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_CASHBOX_CHECKS'),
			'LINK' => $this->getCashboxEditUrl([
				'show_checks' => 'y',
				'current_date' => 'y',
			])
		];

		return $result;
	}

	/**
	 * @param array $params
	 * @return \Bitrix\Main\Web\Uri|false
	 */
	protected function getCashboxEditUrl(array $params = [])
	{
		static $cashboxPath = null;
		if($cashboxPath === null)
		{
			$cashboxPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.cashbox');
			$cashboxPath = getLocalPath('components'.$cashboxPath.'/slider.php');
		}

		if(!$cashboxPath)
		{
			return false;
		}

		$uri = new \Bitrix\Main\Web\Uri($cashboxPath);
		$uri->addParams($params);
		return $uri;
	}

	/**
	* @param $handler
	* @return array
	* @throws \Bitrix\Main\ArgumentException
	* @throws \Bitrix\Main\ObjectPropertyException
	* @throws \Bitrix\Main\SystemException
	*/
	public function reloadCashboxItemAction($handler = null)
	{
		$result = [
			'menuItems' => []
		];

		if(!$handler || !Loader::includeModule('salescenter') || !SaleManager::getInstance()->isFullAccess())
		{
			return $result;
		}

		$cashboxItems = $this->getCashboxItems();

		foreach($cashboxItems as $cashboxItem)
		{
			if($cashboxItem['data']['handler'] == $handler)
			{
				$result['itemSelected'] = $cashboxItem['itemSelected'];
				$result['menuItems'] = $cashboxItem['data']['menuItems'];
				$result['showMenu'] = $cashboxItem['data']['showMenu'];
				break;
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function getUserConsentItems()
	{
		$userConsentSettingPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.userconsent');
		$userConsentSettingPath = getLocalPath('components'.$userConsentSettingPath.'/slider.php');

		$menuItems = [];
		$userConsent = 0;
		if ($this->isUserConsentActive() === 'Y')
		{
			$userConsent = $this->getUserConsent();
			if ($userConsent === false)
			{
				$userConsent = $this->getDefaultUserConsent();
			}

			if ($userConsent)
			{
				$userConsentUrl = "/settings/configs/userconsent/consents/{$userConsent}/?AGREEMENT_ID={$userConsent}&apply_filter=Y";

				$menuItems = [
					[
						'NAME' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_USERCONSENT_EDIT'),
						'LINK' => $userConsentSettingPath,
					],
					[
						'NAME' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_USERCONSENT_LIST'),
						'LINK' => $userConsentUrl,
					]
				];
			}
		}

		return [
			[
				'id' => 'userconsent',
				'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_USERCONSENT_RULES'),
				'image' => $this->getImagePath().'userconsent.svg',
				'itemSelectedColor' => '#A061D1',
				'itemSelected' => (bool)$userConsent,
				'itemSelectedImage' => $this->getImagePath().'userconsent.svg',
				'data' => [
					'type' => 'userconsent',
					'connectPath' => $userConsentSettingPath,
					'menuItems' => $menuItems,
				],
			],
		];
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function isUserConsentActive()
	{
		return Bitrix\Main\Config\Option::get('salescenter', '~SALESCENTER_USER_CONSENT_ACTIVE', 'Y');
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function getUserConsent()
	{
		return Bitrix\Main\Config\Option::get('salescenter', '~SALESCENTER_USER_CONSENT_ID', false);
	}

	/**
	 * @return int
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function getDefaultUserConsent()
	{
		$agreementId = false;
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
				$agreementId = $config['AGREEMENT_ID'];
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
	protected function getPaySystemHandlers()
	{
		$fullList = [
			'cash' => [],
			'paypal' => [],
			'sberbankonline' => [],
			'qiwi' => [],
			'webmoney' => [],
			'yandexcheckout' => [
				'bank_card',
				'sberbank',
				'sberbank_sms',
				'alfabank',
				'yandex_money',
				'webmoney',
				'qiwi'
			],
			'uapay' => [],
			'liqpay' => [],
		];

		if(!\Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->isEnabled())
		{
			return $fullList;
		}

		if (\Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->isCurrentZone('ru'))
		{
			return [
				'cash' => [],
				'paypal' => [],
				'sberbankonline' => [],
				'qiwi' => [],
				'webmoney' => [],
				'yandexcheckout' => [
					'bank_card',
					'sberbank',
					'sberbank_sms',
					'alfabank',
					'yandex_money',
					'webmoney',
					'qiwi'
				],
			];
		}
		elseif (\Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->isCurrentZone('ua'))
		{
			return [
				'cash' => [],
				'paypal' => [],
				'liqpay' => [],
				'uapay' => [],
			];
		}
		else
		{
			return [
				'cash' => [],
				'paypal' => [],
			];
		}
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getPaySystemItems()
	{
		$paySystemPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem');
		$paySystemPath = getLocalPath('components'.$paySystemPath.'/slider.php')."?";

		$paySystemHandlerList = $this->getPaySystemHandlers();

		if (
			SalesCenter\Integration\Bitrix24Manager::getInstance()->isCurrentZone('ua')
			|| SalesCenter\Integration\IntranetManager::getInstance()->isCurrentZone('ua')
		)
		{
			$paySystemPanel = [
				'cash',
				'uapay'
			];
		}
		else
		{
			$paySystemPanel = [
				'cash',
				'yandexcheckout' => [
					'sberbank',
					'sberbank_sms',
				],
				'uapay'
			];
		}

		$paySystemColorList = [
			'cash' => '#8EB927',
			'paypal' => '#243B80',
			'sberbankonline' => '#2C9B47',
			'qiwi' => '#E9832C',
			'webmoney' => '#006FA8',
			'yandexcheckout' => [
				'alfabank' => '#EE2A23',
				'bank_card' => '#19D0C8',
				'yandex_money' => '#E10505',
				'sberbank' => '#327D36',
				'sberbank_sms' => '#327D36',
				'qiwi' => '#E9832C',
				'webmoney' => '#006FA8'
			],
			'liqpay' => '#7AB72B',
			'uapay' => '#E41F18',
		];

		$paySystemSortList = [
			'cash' => 100,
			'paypal' => 900,
			'sberbankonline' => 200,
			'qiwi' => 1400,
			'webmoney' => 1500,
			'yandexcheckout' => [
				'alfabank' => 1200,
				'bank_card' => 500,
				'yandex_money' => 1300,
				'sberbank' => 600,
				'sberbank_sms' => 700,
				'qiwi' => 1600,
				'webmoney' => 1700
			],
			'liqpay' => 1800,
			'uapay' => 2000
		];

		$filter = SaleManager::getInstance()->getPaySystemFilter();
		unset($filter['ACTIVE']);
		$paySystemIterator = Sale\PaySystem\Manager::getList([
			'select' => ['ID', 'ACTIVE', 'NAME', 'ACTION_FILE', 'PS_MODE'],
			'filter' => $filter,
			'order' => ['ACTIVE' => 'DESC', 'ID' => 'ASC'],
		]);

		$paySystemActions = $paySystemList = [];
		foreach ($paySystemIterator as $paySystem)
		{
			$paySystemList[$paySystem['ACTION_FILE']][] = $paySystem;
		}

		foreach ($paySystemHandlerList as $paySystemHandler => $paySystemMode)
		{
			if (!$this->isPaySystemHandlerExist($paySystemHandler))
			{
				continue;
			}

			$paySystemItems = $paySystemList[$paySystemHandler];
			if ($paySystemItems)
			{
				foreach ($paySystemItems as $paySystemItem)
				{
					$queryParams = [
						'lang' => LANGUAGE_ID,
						'publicSidePanel' => 'Y',
						'ID' => $paySystemItem['ID'],
						'ACTION_FILE' => $paySystemItem['ACTION_FILE'],
						'PS_MODE' => $paySystemItem['PS_MODE'],
					];

					$isPsMode = !empty($paySystemItem['PS_MODE']);
					if($isPsMode)
					{
						foreach ($paySystemMode as $psMode)
						{
							if ($psMode === $paySystemItem['PS_MODE'])
							{
								if (!isset($paySystemActions[$paySystemItem['ACTION_FILE']]['ACTIVE'][$paySystemItem['PS_MODE']]))
								{
									$paySystemActions[$paySystemItem['ACTION_FILE']]['ACTIVE'][$paySystemItem['PS_MODE']] = false;
									$paySystemActions[$paySystemItem['ACTION_FILE']]['DELIMITER'][$paySystemItem['PS_MODE']] = false;
								}
								if ($paySystemItem['ACTIVE'] === 'Y')
								{
									$paySystemActions[$paySystemItem['ACTION_FILE']]['ACTIVE'][$paySystemItem['PS_MODE']] = true;
								}
								elseif($paySystemActions[$paySystemItem['ACTION_FILE']]['ACTIVE'][$paySystemItem['PS_MODE']] === true && !$paySystemActions[$paySystemItem['ACTION_FILE']]['DELIMITER'][$paySystemItem['PS_MODE']])
								{
									$paySystemActions[$paySystemItem['ACTION_FILE']]['ITEMS'][$paySystemItem['PS_MODE']][] = [
										'DELIMITER' => true,
									];
									$paySystemActions[$paySystemItem['ACTION_FILE']]['DELIMITER'][$paySystemItem['PS_MODE']] = true;
								}

								$paySystemActions[$paySystemItem['ACTION_FILE']]['PS_MODE'] = true;

								$link = $paySystemPath.http_build_query($queryParams);
								$paySystemActions[$paySystemItem['ACTION_FILE']]['ITEMS'][$paySystemItem['PS_MODE']][] = [
									'NAME' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYSYSTEM_SETTINGS', [
										'#PAYSYSTEM_NAME#' => htmlspecialcharsbx($paySystemItem['NAME'])
									]),
									'LINK' => $link
								];
							}
							else
							{
								if (!isset($paySystemActions[$paySystemHandler]['ITEMS'][$psMode]))
								{
									$paySystemActions[$paySystemHandler]['ITEMS'][$psMode] = [];
								}
							}
						}
					}
					else
					{
						if (!isset($paySystemActions[$paySystemItem['ACTION_FILE']]['ACTIVE']))
						{
							$paySystemActions[$paySystemItem['ACTION_FILE']]['ACTIVE'] = false;
							$paySystemActions[$paySystemItem['ACTION_FILE']]['DELIMITER'] = false;
						}
						if ($paySystemItem['ACTIVE'] === 'Y')
						{
							$paySystemActions[$paySystemItem['ACTION_FILE']]['ACTIVE'] = true;
						}
						elseif($paySystemActions[$paySystemItem['ACTION_FILE']]['ACTIVE'] === true && !$paySystemActions[$paySystemItem['ACTION_FILE']]['DELIMITER'])
						{
							$paySystemActions[$paySystemItem['ACTION_FILE']]['ITEMS'][] = [
								'DELIMITER' => true,
							];
							$paySystemActions[$paySystemItem['ACTION_FILE']]['DELIMITER'] = true;
						}

						$paySystemActions[$paySystemItem['ACTION_FILE']]['PS_MODE'] = false;

						$link = $paySystemPath.http_build_query($queryParams);
						$paySystemActions[$paySystemItem['ACTION_FILE']]['ITEMS'][] = [
							'NAME' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYSYSTEM_SETTINGS', [
								'#PAYSYSTEM_NAME#' => htmlspecialcharsbx($paySystemItem['NAME'])
							]),
							'LINK' => $link
						];
					}
				}
			}
			else
			{
				$handlerModeList = $this->getHandlerModeList($paySystemHandler);
				if ($handlerModeList)
				{
					foreach ($paySystemMode as $psMode)
					{
						if (in_array($psMode, $handlerModeList))
						{
							$paySystemActions[$paySystemHandler]['PS_MODE'] = true;
							$paySystemActions[$paySystemHandler]['ACTIVE'][$psMode] = false;
							$paySystemActions[$paySystemHandler]['ITEMS'][$psMode] = [];
						}
					}
				}
				else
				{
					$paySystemActions[$paySystemHandler] = [
						'ACTIVE' => false,
						'PS_MODE' => false,
					];
				}
			}
		}

		if ($paySystemActions)
		{
			$paySystemActions = $this->getPaySystemMenu($paySystemActions);
		}

		$queryParams = [
			'lang' => LANGUAGE_ID,
			'publicSidePanel' => 'Y',
			'CREATE' => 'Y',
		];

		$paySystemItems = [];
		foreach ($paySystemActions as $handler => $paySystem)
		{
			if (empty($paySystem) && (!in_array($handler, $paySystemPanel)))
			{
				continue;
			}

			$isActive = false;
			$title = Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYSYSTEM_'.strtoupper($handler).'_TITLE');

			if ($paySystem)
			{
				$isPsMode = $paySystem['PS_MODE'];
				if ($isPsMode)
				{
					foreach ($paySystem['ITEMS'] as $psMode => $paySystemItem)
					{
						if (!isset($paySystemPanel[$handler]))
						{
							continue;
						}

						$type = $psMode;
						$isActive = $paySystemActions[$handler]['ACTIVE'][$psMode];
						if (!$isActive && (!in_array($psMode, $paySystemPanel[$handler])))
						{
							continue;
						}

						if (empty($paySystemItem) && (!in_array($psMode, $paySystemPanel[$handler])))
						{
							continue;
						}

						$title = Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYSYSTEM_'.strtoupper($handler).'_'.strtoupper($psMode).'_TITLE');

						$paySystemItems[] = [
							'id' => $handler.'_'.$psMode,
							'sort' => (isset($paySystemSortList[$handler][$psMode]) ? $paySystemSortList[$handler][$psMode] : 100),
							'title' => $title,
							'image' => $this->getImagePath().$handler.'_'.$psMode.'.svg',
							'itemSelectedColor' => $paySystemColorList[$handler][$psMode],
							'itemSelected' => $isActive,
							'itemSelectedImage' => $this->getImagePath().$handler.'_'.$psMode.'_s.svg',
							'data' => [
								'type' => 'paysystem',
								'connectPath' => $paySystemPath.http_build_query(
									array_merge(
										$queryParams,
										[
											'ACTION_FILE' => $handler,
											'PS_MODE' => $psMode,
										]
									)
								),
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

					if (!$isActive && (!in_array($handler, $paySystemPanel)))
					{
						continue;
					}
					$type = $handler;

					$paySystemItems[] = [
						'id' => $handler,
						'sort' => (isset($paySystemSortList[$handler]) ? $paySystemSortList[$handler] : 100),
						'title' => $title,
						'image' => $this->getImagePath().$handler.'.svg',
						'itemSelectedColor' => $paySystemColorList[$handler],
						'itemSelected' => $isActive,
						'itemSelectedImage' => $this->getImagePath().$handler.'_s.svg',
						'data' => [
							'type' => 'paysystem',
							'connectPath' => $paySystemPath.http_build_query(array_merge($queryParams, ['ACTION_FILE' => $handler])),
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
				$paySystemItems[] = [
					'id' => $handler,
					'sort' => (isset($paySystemSortList[$handler]) ? $paySystemSortList[$handler] : 100),
					'title' => $title,
					'image' => $this->getImagePath().$handler.'.svg',
					'itemSelectedColor' => $paySystemColorList[$handler],
					'itemSelected' => $isActive,
					'itemSelectedImage' => $this->getImagePath().$handler.'_s.svg',
					'data' => [
						'type' => 'paysystem',
						'connectPath' => $paySystemPath.http_build_query(array_merge($queryParams, ['ACTION_FILE' => $handler])),
						'menuItems' => [],
						'showMenu' => false,
						'paySystemType' => $type,
					],
				];
			}
		}

		sortByColumn($paySystemItems, ["sort" => SORT_ASC]);

		return $paySystemItems;
	}

	/**
	 * @return array|mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function getYandexCheckoutEmbeddedPaySystem()
	{
		$paySystemPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem');
		$paySystemPath = getLocalPath('components'.$paySystemPath.'/slider.php')."?";

		$handler = "yandexcheckout";
		$psMode = "embedded";

		$handlerModeList = $this->getHandlerModeList($handler);
		if (!in_array($psMode, $handlerModeList))
		{
			return [];
		}

		$paySystemIterator = Sale\PaySystem\Manager::getList([
			'select' => ['ID', 'ACTIVE', 'NAME', 'ACTION_FILE', 'PS_MODE'],
			'filter' => [
				'=ACTION_FILE' => $handler,
				'=PS_MODE' => $psMode,
			],
			'order' => ['ACTIVE' => 'DESC', 'ID' => 'ASC'],
		]);

		$paySystemActions = $paySystemList = [];
		foreach ($paySystemIterator as $paySystem)
		{
			$paySystemList[$paySystem['ACTION_FILE']][] = $paySystem;
		}


		if (!$this->isPaySystemHandlerExist($handler))
		{
			return [];
		}

		$paySystemItems = $paySystemList[$handler];
		if ($paySystemItems)
		{
			foreach ($paySystemItems as $paySystemItem)
			{
				$queryParams = [
					'lang' => LANGUAGE_ID,
					'publicSidePanel' => 'Y',
					'ID' => $paySystemItem['ID'],
					'ACTION_FILE' => $paySystemItem['ACTION_FILE'],
					'PS_MODE' => $paySystemItem['PS_MODE'],
				];

				if(!empty($paySystemItem['PS_MODE']))
				{
					if (!isset($paySystemActions[$paySystemItem['ACTION_FILE']]['ACTIVE'][$paySystemItem['PS_MODE']]))
					{
						$paySystemActions[$paySystemItem['ACTION_FILE']]['ACTIVE'][$paySystemItem['PS_MODE']] = false;
						$paySystemActions[$paySystemItem['ACTION_FILE']]['DELIMITER'][$paySystemItem['PS_MODE']] = false;
					}
					if ($paySystemItem['ACTIVE'] === 'Y')
					{
						$paySystemActions[$paySystemItem['ACTION_FILE']]['ACTIVE'][$paySystemItem['PS_MODE']] = true;
					}
					elseif($paySystemActions[$paySystemItem['ACTION_FILE']]['ACTIVE'][$paySystemItem['PS_MODE']] === true && !$paySystemActions[$paySystemItem['ACTION_FILE']]['DELIMITER'][$paySystemItem['PS_MODE']])
					{
						$paySystemActions[$paySystemItem['ACTION_FILE']]['ITEMS'][$paySystemItem['PS_MODE']][] = [
							'DELIMITER' => true,
						];
						$paySystemActions[$paySystemItem['ACTION_FILE']]['DELIMITER'][$paySystemItem['PS_MODE']] = true;
					}

					$paySystemActions[$paySystemItem['ACTION_FILE']]['PS_MODE'] = true;

					$link = $paySystemPath.http_build_query($queryParams);
					$paySystemActions[$paySystemItem['ACTION_FILE']]['ITEMS'][$paySystemItem['PS_MODE']][] = [
						'NAME' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYSYSTEM_SETTINGS', [
							'#PAYSYSTEM_NAME#' => htmlspecialcharsbx($paySystemItem['NAME'])
						]),
						'LINK' => $link
					];
				}
			}
		}
		else
		{
			$handlerModeList = $this->getHandlerModeList($handler);
			if ($handlerModeList)
			{
				if (in_array($psMode, $handlerModeList))
				{
					$paySystemActions[$handler]['PS_MODE'] = true;
					$paySystemActions[$handler]['ACTIVE'][$psMode] = false;
					$paySystemActions[$handler]['ITEMS'][$psMode] = [];
				}
			}
			else
			{
				$paySystemActions[$handler] = [
					'ACTIVE' => false,
					'PS_MODE' => false,
				];
			}
		}

		$paySystemFinalActions = [];
		if ($paySystemActions)
		{
			$paySystemFinalActions = [
				"applepay" => $this->getPaySystemMenu($paySystemActions, ['EMBEDDED_TYPE' => 'applepay']),
				"googlepay" => $this->getPaySystemMenu($paySystemActions, ['EMBEDDED_TYPE' => 'googlepay']),
			];
		}

		$queryParams = [
			'lang' => LANGUAGE_ID,
			'publicSidePanel' => 'Y',
			'CREATE' => 'Y',
		];

		$isActive = $paySystemActions[$handler]['ACTIVE'][$psMode] ?? false;
		$menuItems = $paySystemActions[$handler]['ITEMS'][$psMode] ?? [];
		$showMenu = $menuItems ? true : false;

		$paySystemItems = [
			[
				'id' => $handler,
				'sort' => 2000,
				'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYSYSTEM_YANDEXCHECKOUT_EMBEDDED_APPLEPAY_TITLE'),
				'image' => $this->getImagePath().'yandexcheckout_embedded_applepay.svg',
				'itemSelectedColor' => "#69809F",
				'itemSelected' => $isActive,
				'itemSelectedImage' => $this->getImagePath().'yandexcheckout_embedded_applepay_s.svg',
				'data' => [
					'type' => 'paysystem',
					'connectPath' => $paySystemPath.http_build_query(
						array_merge(
							$queryParams,
							[
								'ACTION_FILE' => $handler,
								'PS_MODE' => $psMode,
								'EMBEDDED_TYPE' => 'applepay',
							]
						)
					),
					'menuItems' => $paySystemFinalActions['applepay'][$handler]['ITEMS'][$psMode] ?? [],
					'showMenu' => $showMenu,
					'paySystemType' => $psMode,
					'additionalParams' => ['EMBEDDED_TYPE' => 'applepay'],
				],
			],
			[
				'id' => $handler,
				'sort' => 2100,
				'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYSYSTEM_YANDEXCHECKOUT_EMBEDDED_GOOGLEPAY_TITLE'),
				'image' => $this->getImagePath().'yandexcheckout_embedded_googlepay.svg',
				'itemSelectedColor' => "#397CED",
				'itemSelected' => $isActive,
				'itemSelectedImage' => $this->getImagePath().'yandexcheckout_embedded_googlepay_s.svg',
				'data' => [
					'type' => 'paysystem',
					'connectPath' => $paySystemPath.http_build_query(
						array_merge(
							$queryParams,
							[
								'ACTION_FILE' => $handler,
								'PS_MODE' => $psMode,
								'EMBEDDED_TYPE' => 'googlepay',
							]
						)
					),
					'menuItems' => $paySystemFinalActions['googlepay'][$handler]['ITEMS'][$psMode] ?? [],
					'showMenu' => $showMenu,
					'paySystemType' => $psMode,
					'additionalParams' => ['EMBEDDED_TYPE' => 'googlepay'],
				],
			]
		];

		return $paySystemItems;
	}

	/**
	 * @param $handler
	 * @return array
	 */
	private function getHandlerModeList($handler)
	{
		/** @var Sale\PaySystem\BaseServiceHandler $className */
		$className = Sale\PaySystem\Manager::getClassNameFromPath($handler);
		if (!class_exists($className))
		{
			$documentRoot = \Bitrix\Main\Application::getDocumentRoot();
			$path = Sale\PaySystem\Manager::getPathToHandlerFolder($handler);
			$fullPath = $documentRoot.$path.'/handler.php';
			if ($path && \Bitrix\Main\IO\File::isFileExists($fullPath))
			{
				require_once $fullPath;
			}
		}

		$handlerModeList = [];
		if (class_exists($className))
		{
			$handlerModeList = $className::getHandlerModeList();
			if ($handlerModeList)
			{
				$handlerModeList = array_keys($handlerModeList);
			}
		}

		return $handlerModeList;
	}

	/**
	 * @return array
	 */
	private function getPaySystemExtraItems()
	{
		$paySystemPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem.panel');
		$paySystemPath = getLocalPath('components'.$paySystemPath.'/slider.php');
		$paySystemPath = new \Bitrix\Main\Web\Uri($paySystemPath);
		$paySystemPath->addParams([
			'analyticsLabel' => 'salescenterClickPaymentTile',
			'type' => 'extra',
		]);

		return [
			[
				'id' => 'paysystem',
				'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYSYSTEM_SELECT'),
				'image' => $this->getImagePath().'paysystem.svg',
				'selectedColor' => "#E8A312",
				'selected' => false,
				'selectedImage' => $this->getImagePath().'paysystem_s.svg',
				'data' => [
					'type' => 'paysystem_extra',
					'connectPath' => $paySystemPath->getLocator(),
				]
			]
		];
	}

	/**
	 * @param array $paySystemActions
	 * @param array $additionalQueryParams
	 * @return array
	 */
	private function getPaySystemMenu(array $paySystemActions, $additionalQueryParams = [])
	{
		$paySystemPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem');
		$paySystemPath = getLocalPath('components'.$paySystemPath.'/slider.php')."?";

		$name = Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYSYSTEM_ADD');
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
				'ACTION_FILE' => strtolower($handler)
			];
			if ($additionalQueryParams)
			{
				$queryParams = array_merge($queryParams, $additionalQueryParams);
			}

			if ($paySystems['PS_MODE'])
			{
				foreach ($paySystems['ITEMS'] as $psMode => $paySystem)
				{
					if (!$paySystem)
					{
						continue;
					}

					$queryParams['PS_MODE'] = $psMode;
					$link = $paySystemPath.http_build_query($queryParams);
					array_unshift($paySystemActions[$handler]['ITEMS'][$psMode],
						[
							'NAME' => $name,
							'LINK' => $link
						],
						[
							'DELIMITER' => true
						]
					);
				}
			}
			else
			{
				$link = $paySystemPath.http_build_query($queryParams);
				array_unshift($paySystemActions[$handler]['ITEMS'],
					[
						'NAME' => $name,
						'LINK' => $link
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
	 * @param $handler
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	protected function isPaySystemHandlerExist($handler)
	{
		$handlerDirectories = Sale\PaySystem\Manager::getHandlerDirectories();
		if (Bitrix\Main\IO\File::isFileExists($_SERVER['DOCUMENT_ROOT'].$handlerDirectories['SYSTEM'].$handler.'/handler.php'))
		{
			return true;
		}

		return false;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getDeliveryItems()
	{
		$deliveryPanelItems = [];

		$currentDeliveries = $this->getCurrentDeliveries();
		$deliveryTypes = $this->getDeliveryTypes();
		foreach ($deliveryTypes as $deliveryType)
		{
			$isSelected = false;
			switch ($deliveryType['TYPE'])
			{
				case '\Bitrix\Sale\Delivery\Services\Configurable':
					$isSelectedConfigurable = [
						'pickup' => false,
						'courier' => false,
					];
					$configurableItems = [];
					foreach ($currentDeliveries[$deliveryType['TYPE']]['ITEMS'] as $item)
					{
						$stores = Sale\Delivery\ExtraServices\Manager::getStoresFields($item["ID"], false);
						if ($stores)
						{
							if ($item['ACTIVE'] === 'Y')
							{
								$isSelectedConfigurable['pickup'] = true;
							}
							$configurableItems['pickup'][] = $item;
						}
						else
						{
							if ($item['ACTIVE'] === 'Y')
							{
								$isSelectedConfigurable['courier'] = true;
							}
							$configurableItems['courier'][] = $item;
						}
					}

					$deliveryPanelItems[] = [
						'id' => 'pickup',
						'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_DELIVERY_PICKUP'),
						'image' => $this->getImagePath().'pickup.svg',
						'itemSelectedColor' => '#0F629B',
						'itemSelected' => $isSelectedConfigurable['pickup'],
						'itemSelectedImage' => $this->getImagePath().'pickup_s.svg',
						'data' => [
							'type' => 'delivery',
							'connectPath' => $deliveryType['LINK'],
							'menuItems' => $this->getDeliveryMenu($deliveryType['TYPE'], 'PICKUP'),
							'showMenu' => (isset($configurableItems['pickup'])),
						],
					];

					$deliveryPanelItems[] = [
						'id' => 'courier',
						'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_DELIVERY_COURIER'),
						'image' => $this->getImagePath().'courier.svg',
						'itemSelectedColor' => '#10629B',
						'itemSelected' => $isSelectedConfigurable['courier'],
						'itemSelectedImage' => $this->getImagePath().'courier_s.svg',
						'data' => [
							'type' => 'delivery',
							'connectPath' => $deliveryType['LINK'],
							'menuItems' => $this->getDeliveryMenu($deliveryType['TYPE'], 'COURIER'),
							'showMenu' => (isset($configurableItems['courier'])),
						],
					];

					break;
				case '\Sale\Handlers\Delivery\AdditionalHandler':
					if ($deliveryType['SERVICE_TYPE'] == 'RUSPOST')
					{
						if (isset($currentDeliveries[$deliveryType['TYPE']])
							&& in_array($deliveryType['SERVICE_TYPE'], $currentDeliveries[$deliveryType['TYPE']]['TYPE'])
						)
						{
							foreach ($currentDeliveries[$deliveryType['TYPE']]['ITEMS'] as $item)
							{
								if ($item['CONFIG']['MAIN']['SERVICE_TYPE'] !== 'RUSPOST')
								{
									continue;
								}

								if ($item['ACTIVE'] === 'Y')
								{
									$isSelected = true;
								}
							}
						}

						$deliveryPanelItems[] = [
							'id' => 'ruspost',
							'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_DELIVERY_RUS_POST'),
							'image' => $this->getImagePath().'pochta.svg',
							'itemSelectedColor' => '#0055A5',
							'itemSelected' => $isSelected,
							'itemSelectedImage' => $this->getImagePath().'pochta_s.svg',
							'data' => [
								'type' => 'delivery',
								'connectPath' => $deliveryType['LINK'],
								'menuItems' => $this->getDeliveryMenu($deliveryType['TYPE'], 'RUSPOST'),
								'showMenu' => (isset($currentDeliveries[$deliveryType['TYPE']]['ITEMS'])),
							],
						];
					}
					elseif ($deliveryType['SERVICE_TYPE'] == 'DPD')
					{
						if (isset($currentDeliveries[$deliveryType['TYPE']])
							&& in_array($deliveryType['SERVICE_TYPE'], $currentDeliveries[$deliveryType['TYPE']]['TYPE'])
						)
						{
							foreach ($currentDeliveries[$deliveryType['TYPE']]['ITEMS'] as $item)
							{
								if ($item['CONFIG']['MAIN']['SERVICE_TYPE'] !== 'DPD')
								{
									continue;
								}

								if ($item['ACTIVE'] === 'Y')
								{
									$isSelected = true;
								}
							}
						}

						if ($isSelected)
						{
							$deliveryPanelItems[] = [
								'id' => 'dpd',
								'title' => htmlspecialcharsbx($deliveryType['NAME']),
								'image' => $this->getImagePath().'dpd.svg',
								'itemSelected' => $isSelected,
								'itemSelectedColor' => "#DC0032",
								'itemSelectedImage' => $this->getImagePath().'dpd_s.svg',
								'data' => [
									'type' => 'delivery',
									'connectPath' => $deliveryType['LINK'],
									'menuItems' => $this->getDeliveryMenu($deliveryType['TYPE'], 'DPD'),
									'showMenu' => (isset($currentDeliveries[$deliveryType['TYPE']]['ITEMS'])),
								],
							];
						}
					}
					elseif ($deliveryType['SERVICE_TYPE'] == 'CDEK')
					{
						if (isset($currentDeliveries[$deliveryType['TYPE']])
							&& in_array($deliveryType['SERVICE_TYPE'], $currentDeliveries[$deliveryType['TYPE']]['TYPE'])
						)
						{
							foreach ($currentDeliveries[$deliveryType['TYPE']]['ITEMS'] as $item)
							{
								if ($item['CONFIG']['MAIN']['SERVICE_TYPE'] !== 'CDEK')
								{
									continue;
								}

								if ($item['ACTIVE'] === 'Y')
								{
									$isSelected = true;
								}
							}
						}

						if ($isSelected)
						{
							$deliveryPanelItems[] = [
								'id' => 'cdek',
								'title' => htmlspecialcharsbx($deliveryType['NAME']),
								'image' => $this->getImagePath().'cdek.svg',
								'itemSelected' => $isSelected,
								'itemSelectedColor' => "#57A52C",
								'itemSelectedImage' => $this->getImagePath().'cdek_s.svg',
								'data' => [
									'type' => 'delivery',
									'connectPath' => $deliveryType['LINK'],
									'menuItems' => $this->getDeliveryMenu($deliveryType['TYPE'], 'CDEK'),
									'showMenu' => (isset($currentDeliveries[$deliveryType['TYPE']]['ITEMS'])),
								],
							];
						}
					}

					break;
				case '\Sale\Handlers\Delivery\SpsrHandler':
					if (isset($currentDeliveries[$deliveryType['TYPE']]))
					{
						foreach ($currentDeliveries[$deliveryType['TYPE']]['ITEMS'] as $item)
						{
							if ($item['ACTIVE'] === 'Y')
							{
								$isSelected = true;
							}
						}
					}

					if ($isSelected)
					{
						$deliveryPanelItems[] = [
							'id' => 'spsr',
							'title' => htmlspecialcharsbx($deliveryType['NAME']),
							'image' => $this->getImagePath().'spsr_express.svg',
							'itemSelected' => $isSelected,
							'itemSelectedColor' => "#013E57",
							'itemSelectedImage' => $this->getImagePath().'spsr_express_s.svg',
							'data' => [
								'type' => 'delivery',
								'connectPath' => $deliveryType['LINK'],
								'menuItems' => $this->getDeliveryMenu($deliveryType['TYPE'], 'SPSR'),
								'showMenu' => (isset($currentDeliveries[$deliveryType['TYPE']]['ITEMS'])),
							],
						];
					}

					break;
				case '\Sale\Handlers\Delivery\SimpleHandler':
					if (isset($currentDeliveries[$deliveryType['TYPE']]))
					{
						foreach ($currentDeliveries[$deliveryType['TYPE']]['ITEMS'] as $item)
						{
							if ($item['ACTIVE'] === 'Y')
							{
								$isSelected = true;
							}
						}
					}

					if ($isSelected)
					{
						$deliveryPanelItems[] = [
							'id' => 'simple',
							'title' => htmlspecialcharsbx($deliveryType['NAME']),
							'image' => $this->getImagePath().'by_location.svg',
							'itemSelected' => $isSelected,
							'itemSelectedColor' => "#177CE2",
							'itemSelectedImage' => $this->getImagePath().'by_location_s.svg',
							'data' => [
								'type' => 'delivery',
								'connectPath' => $deliveryType['LINK'],
								'menuItems' => $this->getDeliveryMenu($deliveryType['TYPE'], 'SIMPLE'),
								'showMenu' => (isset($currentDeliveries[$deliveryType['TYPE']]['ITEMS'])),
							],
						];
					}
					break;
			}
		}

		return $deliveryPanelItems;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getDeliveryTypes()
	{
		$classNamesList = \Bitrix\Sale\Delivery\Services\Manager::getHandlersList();
		$classesToExclude = $this->getDeliveryTypesToExclude();

		$deliveryTypes = [];

		/** @var Bitrix\Sale\Delivery\Services\Base $class */
		foreach($classNamesList as $class)
		{
			if(in_array($class, $classesToExclude) || $class::isProfile())
				continue;

			$supportedServices = $class::getSupportedServicesList();
			if(is_array($supportedServices) && !empty($supportedServices))
			{
				if (
					(empty($supportedServices['ERRORS']) || empty($supportedServices['NOTES']))
					&& is_array($supportedServices)
				)
				{
					foreach($supportedServices as $srvType => $srvParams)
					{
						if(!empty($srvParams["NAME"]))
						{
							$queryParams = [
								'lang' => LANGUAGE_ID,
								'PARENT_ID' => 0,
								'CREATE' => 'Y',
								'CLASS_NAME' => $class,
								'SERVICE_TYPE' => $srvType,
								'publicSidePanel' => 'Y'
							];

							$editUrl = $this->sefFolder."sale_delivery_service_edit/?".http_build_query($queryParams);
							$deliveryTypes[] = [
								"TYPE" => $class,
								"SERVICE_TYPE" => $srvType,
								"NAME" => htmlspecialcharsbx($srvParams["NAME"]),
								"LINK" => $editUrl
							];
						}
					}
				}
			}
			else
			{
				$queryParams = [
					'lang' => LANGUAGE_ID,
					'PARENT_ID' => 0,
					'CREATE' => 'Y',
					'CLASS_NAME' => $class,
					'publicSidePanel' => 'Y'
				];

				$editUrl = $this->sefFolder."sale_delivery_service_edit/?".http_build_query($queryParams);
				$deliveryTypes[] = [
					"TYPE" => $class,
					"NAME" => $class::getClassTitle(),
					"LINK" => $editUrl
				];
			}
		}

		sortByColumn($deliveryTypes, array("NAME" => SORT_ASC));

		return $deliveryTypes;
	}

	/**
	 * @return array
	 */
	protected function getDeliveryTypesToExclude()
	{
		return [
			'\Bitrix\Sale\Delivery\Services\Automatic',
			'\Bitrix\Sale\Delivery\Services\EmptyDeliveryService',
			'\Bitrix\Sale\Delivery\Services\Group'
		];
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getCurrentDeliveries()
	{
		$currentDeliveries = [];

		\Bitrix\Sale\Delivery\Services\Manager::getHandlersList();
		$deliveryList = Sale\Delivery\Services\Table::getList([
			"select" => ['ID', 'ACTIVE', 'CONFIG', 'CLASS_NAME']
		])->fetchAll();

		foreach ($deliveryList as $delivery)
		{
			/** @var \Bitrix\Sale\Delivery\Services\Base $class */
			$class = $delivery['CLASS_NAME'];
			if($class::isProfile())
			{
				continue;
			}

			$supportedServices = $class::getSupportedServicesList();
			if(is_array($supportedServices) && !empty($supportedServices))
			{
				if (
					(empty($supportedServices['ERRORS']) || empty($supportedServices['NOTES']))
					&& is_array($supportedServices)
				)
				{
					foreach ($supportedServices as $srvType => $srvParams)
					{
						if ($srvType === $delivery['CONFIG']['MAIN']['SERVICE_TYPE'])
						{
							$currentDeliveries[$delivery['CLASS_NAME']]['TYPE'][] = $srvType;
							$currentDeliveries[$delivery['CLASS_NAME']]['ITEMS'][] = $delivery;
						}
					}
				}
			}
			else
			{
				$currentDeliveries[$delivery['CLASS_NAME']]['ITEMS'][] = $delivery;
			}
		}

		return $currentDeliveries;
	}

	/**
	 * @param $class
	 * @param $type
	 * @return array
	 */
	protected function getDeliveryMenu($class, $type)
	{
		$deliveryPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.delivery.panel');
		$deliveryPath = getLocalPath('components'.$deliveryPath.'/slider.php');

		$deliveryPathAdd = '/shop/settings/sale_delivery_service_edit/?';
		$queryEdit = [
			'lang' => LANGUAGE_ID,
			'PARENT_ID' => 0,
			'CREATE' => 'Y',
			'publicSidePanel' => 'Y',
			'CLASS_NAME' => $class,
			'SERVICE_TYPE' => $type,
		];

		$queryList = [
			'show_delivery_list' => 'Y',
			'CLASS_NAME' => $class,
			'SERVICE_TYPE' => $type,
		];

		$menuItems = [
			[
				'NAME' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_DELIVERY_ADD'),
				'LINK' => $deliveryPathAdd.http_build_query($queryEdit)
			],
			[
				'DELIMITER' => true
			],
			[
				'NAME' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_DELIVERY_LIST'),
				'LINK' => $deliveryPath."?".http_build_query($queryList),
				'FILTER' => [
					'CLASS_NAME' => $class,
				],
			]
		];

		return $menuItems;
	}

	/**
	 * @param array $filter
	 */
	public function setDeliveryListFilterAction(array $filter)
	{
		if (empty($filter))
		{
			return;
		}

		$filterId = 'tmp_filter';

		$filterOption = new \Bitrix\Main\UI\Filter\Options($this->deliveryListTableId);
		$filterData = $filterOption->getPresets();

		$filterData[$filterId] = $filterData['default_filter'];
		$filterData[$filterId]['filter_rows'] = implode(',', array_keys($filter));
		$filterData[$filterId]['fields'] = $filter;

		$filterOption->setDefaultPreset($filterId);
		$filterOption->setPresets($filterData);
		$filterOption->save();
	}

	/**
	 * @return array
	 */
	protected function getDeliveryExtraItems()
	{
		$deliveryPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.delivery.panel');
		$deliveryPath = getLocalPath('components'.$deliveryPath.'/slider.php');
		$deliveryPathEdit = '/shop/settings/sale_delivery_service_edit/?';

		$queryEdit = [
			'lang' => LANGUAGE_ID,
			'PARENT_ID' => 0,
			'CLASS_NAME' => '\Bitrix\Sale\Delivery\Services\Configurable',
			'CREATE' => 'Y',
			'publicSidePanel' => 'Y'
		];

		$link = $deliveryPathEdit.http_build_query($queryEdit);

		return [
			[
				'id' => 'delivery',
				'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_DELIVERY_SELECT'),
				'image' => $this->getImagePath().'delivery.svg',
				'selectedColor' => "#f00",
				'selected' => false,
				'selectedImage' => $this->getImagePath().'delivery_s.svg',
				'data' => [
					'type' => 'delivery_extra',
					'connectPath' => $deliveryPath
				]
			],
			[
				'id' => 'create',
				'title' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_DELIVERY_CREATE'),
				'image' => $this->getImagePath().'create.svg',
				'selectedColor' => "#f00",
				'selected' => false,
				'selectedImage' => $this->getImagePath().'create_s.svg',
				'data' => [
					'type' => 'delivery_extra',
					'connectPath' => $link,
				]
			]
		];
	}

	/**
	 * @param $paySystemId
	 * @param $actionFile
	 * @param $psMode
	 * @param array $additionalParams
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function reloadPaySystemItemAction($paySystemId = null, $actionFile = null, $psMode = null, $additionalParams = [])
	{
		Loader::includeModule('sale');

		if ($paySystemId)
		{
			$paySystem = Sale\PaySystem\Manager::getById($paySystemId);
			if ($paySystem)
			{
				$actionFile = $paySystem['ACTION_FILE'];
				$psMode = $paySystem['PS_MODE'];
			}
		}

		if (!$actionFile)
		{
			return [
				'menuItems' => [],
				'itemSelected' => false,
				'showMenu' => false
			];
		}

		$paySystemPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem');
		$paySystemPath = getLocalPath('components'.$paySystemPath.'/slider.php')."?";
		// $paySystemPath = '/shop/settings/sale_pay_system_edit/?';

		$filter = [
			'=ACTION_FILE' => $actionFile
		];
		if ($psMode)
		{
			$filter['=PS_MODE'] = $psMode;
		}

		$paySystemList = Sale\PaySystem\Manager::getList([
			'select' => ['ID', 'ACTIVE', 'NAME', 'ACTION_FILE', 'PS_MODE'],
			'filter' => $filter,
			'order' => ['ACTIVE' => 'DESC', 'ID' => 'ASC'],
		])->fetchAll();

		$menuItems = [];
		$isDelimiterAdded = $isActive = false;
		foreach ($paySystemList as $paySystem)
		{
			$queryParams = [
				'lang' => LANGUAGE_ID,
				'publicSidePanel' => 'Y',
				'ID' => $paySystem['ID'],
			];

			$link = $paySystemPath.http_build_query($queryParams);
			$isPsMode = (!empty($paySystem['PS_MODE']));

			if ($paySystem['ACTIVE'] === 'Y')
			{
				$isActive = true;
			}
			elseif($isActive && !$isDelimiterAdded)
			{
				$isDelimiterAdded = true;
				$menuItems['PAY_SYSTEM'][] = [
					'DELIMITER' => true,
				];
			}

			$menuItems['PS_MODE'] = $isPsMode;
			$menuItems['PAY_SYSTEM'][] = [
				'NAME' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYSYSTEM_SETTINGS', [
					'#PAYSYSTEM_NAME#' => htmlspecialcharsbx($paySystem['NAME'])
				]),
				'LINK' => $link
			];
		}

		if ($menuItems)
		{
			$queryParams = [
				'lang' => LANGUAGE_ID,
				'publicSidePanel' => 'Y',
				'CREATE' => 'Y',
				'ACTION_FILE' => strtolower($actionFile),
			];
			if ($additionalParams)
			{
				$queryParams = array_merge($queryParams, $additionalParams);
			}

			$name = Loc::getMessage('SALESCENTER_CONTROL_PANEL_PAYSYSTEM_ADD');
			if ($menuItems['PS_MODE'])
			{
				$queryParams['PS_MODE'] = strtolower($psMode);
			}
			$link = $paySystemPath.http_build_query($queryParams);
			array_unshift($menuItems['PAY_SYSTEM'],
				[
					'NAME' => $name,
					'LINK' => $link
				],
				[
					'DELIMITER' => true
				]
			);
		}

		return [
			'itemSelected' => $isActive,
			'menuItems' => (isset($menuItems['PAY_SYSTEM']) ? $menuItems['PAY_SYSTEM'] : []),
			'showMenu' => isset($menuItems['PAY_SYSTEM']),
		];
	}

	/**
	 * @param $className
	 * @param null $serviceType
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function reloadDeliveryItemAction($className, $serviceType = null)
	{
		Loader::includeModule('sale');

		if (!$className)
		{
			return [
				'menuItems' => []
			];
		}

		\Bitrix\Sale\Delivery\Services\Manager::getHandlersList();
		$deliveryList = Sale\Delivery\Services\Table::getList([
			'select' => ['ID', 'ACTIVE', 'CONFIG', 'CLASS_NAME'],
			'filter' => ['=CLASS_NAME' => $className],
		])->fetchAll();

		$menuItems = [];
		$isActive = false;
		foreach ($deliveryList as $delivery)
		{
			if (isset($delivery['CONFIG']['MAIN']['SERVICE_TYPE']) && $serviceType)
			{
				if ($delivery['CONFIG']['MAIN']['SERVICE_TYPE'] !== $serviceType)
				{
					continue;
				}
			}

			if ($delivery['CLASS_NAME'] === '\Bitrix\Sale\Delivery\Services\Configurable')
			{
				if ($serviceType === 'PICKUP')
				{
					$stores = Sale\Delivery\ExtraServices\Manager::getStoresFields($delivery["ID"], false);
					if (!$stores)
					{
						continue;
					}
				}
			}

			if ($delivery['ACTIVE'] === 'Y')
			{
				$isActive = true;
			}

			$menuItems = $this->getDeliveryMenu($delivery['CLASS_NAME'], $serviceType);
		}

		return [
			'itemSelected' => $isActive,
			'menuItems' => $menuItems,
			'showMenu' => isset($menuItems),
		];
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function reloadUserConsentAction()
	{
		$userConsentSettingPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.userconsent');
		$userConsentSettingPath = getLocalPath('components'.$userConsentSettingPath.'/slider.php');

		$menuItems = [];
		$userConsent = 0;
		if ($this->isUserConsentActive() === 'Y')
		{
			$userConsent = $this->getUserConsent();
			if ($userConsent === false)
			{
				$userConsent = $this->getDefaultUserConsent();
			}

			if ($userConsent)
			{
				$userConsentUrl = "/settings/configs/userconsent/consents/{$userConsent}/?AGREEMENT_ID={$userConsent}&apply_filter=Y";

				$menuItems = [
					[
						'NAME' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_USERCONSENT_EDIT'),
						'LINK' => $userConsentSettingPath,
					],
					[
						'NAME' => Loc::getMessage('SALESCENTER_CONTROL_PANEL_USERCONSENT_LIST'),
						'LINK' => $userConsentUrl,
					]
				];
			}
		}

		return [
			'itemSelected' => (bool)$userConsent,
			'menuItems' => $menuItems
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
}