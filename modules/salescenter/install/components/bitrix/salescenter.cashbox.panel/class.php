<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Engine\Contract\Controllerable,
	Bitrix\Sale,
	Bitrix\SalesCenter\Integration\SaleManager,
	Bitrix\SalesCenter\Integration\Bitrix24Manager;

Loc::loadMessages(__FILE__);

/**
 * Class SalesCenterCashboxPanel
 */
class SalesCenterCashboxPanel extends CBitrixComponent implements Controllerable
{
	private $cashboxPanelId = 'salescenter-cashbox';
	private $offlineCashboxPanelId = 'salescenter-offline-cashbox';

	/**
	 * @param $arParams
	 * @return array
	 */
	public function onPrepareComponentParams($arParams)
	{
		return parent::onPrepareComponentParams($arParams);
	}

	/**
	 * @return mixed|void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
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

		// online cashbox
		$cashboxItems = $this->getCashboxItems();
		if (Bitrix24Manager::getInstance()->isEnabled())
		{
			$cashboxItems[] = $this->getRecommendItem();
		}

		$this->arResult['cashboxPanelParams'] = [
			'id' => $this->cashboxPanelId,
			'items' => $cashboxItems,
		];

		// offline cashbox
		$offlineCashboxItems = $this->getOfflineCashboxItems();
		$this->arResult['offlineCashboxPanelParams'] = [
			'id' => $this->offlineCashboxPanelId,
			'items' => $offlineCashboxItems,
		];

		$this->includeComponentTemplate();
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getCashboxItems(): array
	{
		$cashboxes = [];
		$cashboxDescriptions = [
			[
				'id' => 'atol',
				'title' => Loc::getMessage('SCP_CASHBOX_ATOL'),
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
				'title' => Loc::getMessage('SCP_CASHBOX_ORANGE_DATA'),
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
					'recommendation' => true,
				],
			],
		];

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
	 * @return mixed
	 */
	private function getOfflineCashboxItems()
	{
		$cashboxDescriptions[] = [
			'id' => 'offline',
			'title' => Loc::getMessage('SCP_CASHBOX_OFFLINE'),
			'image' => $this->getImagePath().'offline.svg',
			'itemSelectedColor' => '#359FD0',
			'itemSelectedImage' => $this->getImagePath().'offline_s.svg',
			'itemSelected' => false,
			'data' => [
				'type' => 'cashbox',
				'handler' => 'offline',
				'connectPath' => $this->getCashboxEditUrl([
					'handler' => 'offline',
					'preview' => 'y',
				]),
				'showMenu' => false,
			],
		];

		return $cashboxDescriptions;
	}

	/**
	 * @param array $cashboxes
	 * @return array
	 */
	private function getCashboxMenu(array $cashboxes): array
	{
		$result = [];

		foreach($cashboxes as $cashbox)
		{
			if(empty($result))
			{
				$result = [
					[
						'NAME' => Loc::getMessage('SCP_CASHBOX_ADD'),
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
				'NAME' => Loc::getMessage('SCP_CASHBOX_SETTINGS', [
					'#CASHBOX_NAME#' => htmlspecialcharsbx($cashbox['NAME'])
				]),
				'LINK' => $this->getCashboxEditUrl(['id' => $cashbox['ID'], 'handler' => $cashbox['HANDLER']]),
			];
		}

		$result[] = [
			'DELIMITER' => true,
		];

		$result[] = [
			'NAME' => Loc::getMessage('SCP_CASHBOX_CHECKS'),
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
	private function getCashboxEditUrl(array $params = [])
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
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function reloadCashboxItemAction($handler = null): array
	{
		Loader::includeModule('sale');

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
			'title' => Loc::getMessage('SCP_CASHBOX_APP_RECOMMEND'),
			'image' => $this->getImagePath().'recommend.svg',
			'data' => [
				'type' => 'recommend',
				'connectPath' => $feedbackPath->getLocator(),
			]
		];
	}

	/**
	 * @param $error
	 */
	private function showError($error): void
	{
		ShowError($error);
	}

	/**
	 * @return string
	 */
	protected function getImagePath(): string
	{
		return $this->__path.'/templates/.default/images/';
	}

	/**
	 * @inheritDoc
	 */
	public function configureActions()
	{
		return [];
	}
}