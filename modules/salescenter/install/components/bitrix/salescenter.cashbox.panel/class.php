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

		if(!SaleManager::getInstance()->isManagerAccess(true))
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

		$activeCashboxHandlersByCountry = $this->getActiveCashboxHandlersByCountry();
		$this->arResult['activeCashboxHandlersByCountry'] = $activeCashboxHandlersByCountry;
		$this->arResult['isCashboxCountryConflict'] = !(empty($activeCashboxHandlersByCountry['RU']) || empty($activeCashboxHandlersByCountry['UA']));

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
		$zone = '';
		$isCloud = Main\Loader::includeModule("bitrix24");
		if ($isCloud)
		{
			$zone = \CBitrix24::getLicensePrefix();
		}
		elseif (Main\Loader::includeModule('intranet'))
		{
			$zone = \CIntranetUtils::getPortalZone();
		}

		$cashboxDescriptions = [];
		if ($zone === 'ru') {
			$cashboxDescriptions = array_merge($cashboxDescriptions, [
				[
					'id' => 'atol',
					'title' => Loc::getMessage('SCP_CASHBOX_ATOL'),
					'image' => $this->getImagePath() . 'atol.svg',
					'itemSelectedColor' => '#ED1B2F',
					'itemSelectedImage' => $this->getImagePath() . 'atol_s.svg',
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
					'id' => Sale\Cashbox\CashboxAtolFarmV5::getCode(),
					'title' => Loc::getMessage('SCP_CASHBOX_ATOL_FFD_12'),
					'image' => $this->getImagePath() . 'atol.svg',
					'itemSelectedColor' => '#ED1B2F',
					'itemSelectedImage' => $this->getImagePath() . 'atol_s.svg',
					'itemSelected' => false,
					'data' => [
						'type' => 'cashbox',
						'handler' => '\\Bitrix\\Sale\\Cashbox\\CashboxAtolFarmV5',
						'connectPath' => $this->getCashboxEditUrl([
							'handler' => '\\Bitrix\\Sale\\Cashbox\\CashboxAtolFarmV5',
							'preview' => 'y',
						]),
						'showMenu' => false,
					],
				],
				[
					'id' => 'orangedata',
					'title' => Loc::getMessage('SCP_CASHBOX_ORANGE_DATA'),
					'image' => $this->getImagePath() . 'orangedata.svg',
					'itemSelectedColor' => '#FF9A01',
					'itemSelectedImage' => $this->getImagePath() . 'orangedata_s.svg',
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
				[
					'id' => Sale\Cashbox\CashboxOrangeDataFfd12::getCode(),
					'title' => Sale\Cashbox\CashboxOrangeDataFfd12::getName(),
					'image' => $this->getImagePath() . 'orangedata.svg',
					'itemSelectedColor' => '#FF9A01',
					'itemSelectedImage' => $this->getImagePath() . 'orangedata_s.svg',
					'itemSelected' => false,
					'data' => [
						'type' => 'cashbox',
						'handler' => '\\Bitrix\\Sale\\Cashbox\\CashboxOrangeDataFfd12',
						'connectPath' => $this->getCashboxEditUrl([
							'handler' => '\\Bitrix\\Sale\\Cashbox\\CashboxOrangeDataFfd12',
							'preview' => 'y',
						]),
						'showMenu' => false,
						'recommendation' => true,
					],
				],
				[
					'id' => 'businessru-atol',
					'title' => Loc::getMessage('SCP_CASHBOX_BUSINESS_RU_ATOL'),
					'image' => $this->getImagePath() . 'businessru_atol.svg',
					'itemSelectedColor' => '#FF142C',
					'itemSelectedImage' => $this->getImagePath() . 'businessru_atol_s.svg',
					'itemSelected' => false,
					'data' => [
						'type' => 'cashbox',
						'kkm-id' => Sale\Cashbox\KkmRepository::ATOL,
						'handler' => '\\'.Sale\Cashbox\CashboxBusinessRu::class,
						'connectPath' => $this->getCashboxEditUrl([
							'handler' => '\\'.Sale\Cashbox\CashboxBusinessRu::class,
							'kkm-id' => Sale\Cashbox\KkmRepository::ATOL,
							'preview' => 'y',
						]),
						'showMenu' => false,
						'recommendation' => true,
					],
				],
				[
					'id' => Sale\Cashbox\CashboxBusinessRuV5::getCode() . '-atol',
					'title' => Loc::getMessage('SCP_CASHBOX_BUSINESS_RU_ATOL') . ' (' . Loc::getMessage('SCP_CASHBOX_FFD_12') . ')',
					'image' => $this->getImagePath() . 'businessru_atol.svg',
					'itemSelectedColor' => '#FF142C',
					'itemSelectedImage' => $this->getImagePath() . 'businessru_atol_s.svg',
					'itemSelected' => false,
					'data' => [
						'type' => 'cashbox',
						'kkm-id' => Sale\Cashbox\KkmRepository::ATOL,
						'handler' => '\\'.Sale\Cashbox\CashboxBusinessRuV5::class,
						'connectPath' => $this->getCashboxEditUrl([
							'handler' => '\\'.Sale\Cashbox\CashboxBusinessRuV5::class,
							'kkm-id' => Sale\Cashbox\KkmRepository::ATOL,
							'preview' => 'y',
						]),
						'showMenu' => false,
						'recommendation' => true,
					],
				],
				[
					'id' => 'businessru-shtrihm',
					'title' => Loc::getMessage('SCP_CASHBOX_BUSINESS_RU_SHTRIHM'),
					'image' => $this->getImagePath() . 'businessru_shtrihm.svg',
					'itemSelectedColor' => '#8F7B66',
					'itemSelectedImage' => $this->getImagePath() . 'businessru_shtrihm_s.svg',
					'itemSelected' => false,
					'data' => [
						'type' => 'cashbox',
						'kkm-id' => Sale\Cashbox\KkmRepository::SHTRIHM,
						'handler' => '\\'.Sale\Cashbox\CashboxBusinessRu::class,
						'connectPath' => $this->getCashboxEditUrl([
							'handler' => '\\'.Sale\Cashbox\CashboxBusinessRu::class,
							'kkm-id' => Sale\Cashbox\KkmRepository::SHTRIHM,
							'preview' => 'y',
						]),
						'showMenu' => false,
						'recommendation' => true,
					],
				],
				[
					'id' => Sale\Cashbox\CashboxBusinessRuV5::getCode() . '-shtrihm',
					'title' => Loc::getMessage('SCP_CASHBOX_BUSINESS_RU_SHTRIHM') . ' (' . Loc::getMessage('SCP_CASHBOX_FFD_12') . ')',
					'image' => $this->getImagePath() . 'businessru_shtrihm.svg',
					'itemSelectedColor' => '#8F7B66',
					'itemSelectedImage' => $this->getImagePath() . 'businessru_shtrihm_s.svg',
					'itemSelected' => false,
					'data' => [
						'type' => 'cashbox',
						'kkm-id' => Sale\Cashbox\KkmRepository::SHTRIHM,
						'handler' => '\\'.Sale\Cashbox\CashboxBusinessRuV5::class,
						'connectPath' => $this->getCashboxEditUrl([
							'handler' => '\\'.Sale\Cashbox\CashboxBusinessRuV5::class,
							'kkm-id' => Sale\Cashbox\KkmRepository::SHTRIHM,
							'preview' => 'y',
						]),
						'showMenu' => false,
						'recommendation' => true,
					],
				],
				[
					'id' => 'businessru-evotor',
					'title' => Loc::getMessage('SCP_CASHBOX_BUSINESS_RU_EVOTOR'),
					'image' => $this->getImagePath() . 'businessru_evotor.svg',
					'itemSelectedColor' => '#E44A21',
					'itemSelectedImage' => $this->getImagePath() . 'businessru_evotor_s.svg',
					'itemSelected' => false,
					'data' => [
						'type' => 'cashbox',
						'kkm-id' => Sale\Cashbox\KkmRepository::EVOTOR,
						'handler' => '\\'.Sale\Cashbox\CashboxBusinessRu::class,
						'connectPath' => $this->getCashboxEditUrl([
							'handler' => '\\'.Sale\Cashbox\CashboxBusinessRu::class,
							'kkm-id' => Sale\Cashbox\KkmRepository::EVOTOR,
							'preview' => 'y',
						]),
						'showMenu' => false,
						'recommendation' => true,
					],
				],
				[
					'id' => Sale\Cashbox\CashboxBusinessRuV5::getCode() . '-evotor',
					'title' => Loc::getMessage('SCP_CASHBOX_BUSINESS_RU_EVOTOR') . ' (' . Loc::getMessage('SCP_CASHBOX_FFD_12') . ')',
					'image' => $this->getImagePath() . 'businessru_evotor.svg',
					'itemSelectedColor' => '#E44A21',
					'itemSelectedImage' => $this->getImagePath() . 'businessru_evotor_s.svg',
					'itemSelected' => false,
					'data' => [
						'type' => 'cashbox',
						'kkm-id' => Sale\Cashbox\KkmRepository::EVOTOR,
						'handler' => '\\'.Sale\Cashbox\CashboxBusinessRuV5::class,
						'connectPath' => $this->getCashboxEditUrl([
							'handler' => '\\'.Sale\Cashbox\CashboxBusinessRuV5::class,
							'kkm-id' => Sale\Cashbox\KkmRepository::EVOTOR,
							'preview' => 'y',
						]),
						'showMenu' => false,
						'recommendation' => true,
					],
				],
			]);
		}
		if ($zone === 'ua' || ($zone === 'ru' && !$isCloud))
		{
			$cashboxDescriptions[] = [
				'id' => 'checkbox',
				'title' => Loc::getMessage('SCP_CASHBOX_CHECKBOX'),
				'image' => $this->getImagePath() . 'checkbox.svg',
				'itemSelectedColor' => '#272BED',
				'itemSelectedImage' => $this->getImagePath() . 'checkbox_s.svg',
				'itemSelected' => false,
				'data' => [
					'type' => 'cashbox',
					'handler' => '\\Bitrix\\Sale\\Cashbox\\CashboxCheckbox',
					'connectPath' => $this->getCashboxEditUrl([
						'handler' => '\\Bitrix\\Sale\\Cashbox\\CashboxCheckbox',
						'preview' => 'y',
					]),
					'showMenu' => false,
				],
			];
		}

		$paySystemCashboxList = $this->getPaySystemCashboxItems();
		if ($paySystemCashboxList)
		{
			$cashboxDescriptions = array_merge($cashboxDescriptions, $paySystemCashboxList);
		}

		$restHandlers = Sale\Cashbox\Manager::getRestHandlersList();
		foreach ($restHandlers as $restHandlerCode => $restHandlerConfig)
		{
			$restHandlerDescription = [
				'id' => $restHandlerCode,
				'title' => $restHandlerConfig['NAME'],
				'image' => $this->getImagePath().'offline.svg',
				'itemSelectedColor' => '#359FD0',
				'itemSelectedImage' => $this->getImagePath().'offline_s.svg',
				'itemSelected' => false,
				'data' => [
					'type' => 'cashbox',
					'handler' => '\\Bitrix\\Sale\\Cashbox\\CashboxRest',
					'connectPath' => $this->getCashboxEditUrl([
						'handler' => '\\Bitrix\\Sale\\Cashbox\\CashboxRest',
						'preview' => 'y',
						'restHandler' => $restHandlerCode,
					]),
					'showMenu' => false,
				]
			];

			$cashboxDescriptions[] = $restHandlerDescription;
		}

		$filter = SaleManager::getInstance()->getCashboxFilter(false);
		$dbRes = Sale\Cashbox\Internals\CashboxTable::getList([
			'select' => ['ID', 'ACTIVE', 'NAME', 'KKM_ID', 'HANDLER', 'SETTINGS'],
			'filter' => $filter,
		]);

		while($cashbox = $dbRes->fetch())
		{
			$code = $cashbox['HANDLER']::getCode();
			if ($cashbox['KKM_ID'] && !Sale\Cashbox\Manager::isPaySystemCashbox($cashbox['HANDLER']))
			{
				$code .= '_'.$cashbox['KKM_ID'];
			}

			if(!isset($cashboxes[$code]))
			{
				$cashboxes[$code] = [];
			}

			$isRestCashbox = $cashbox['HANDLER'] === '\\'.Sale\Cashbox\CashboxRest::class;
			if ($isRestCashbox)
			{
				$restHandlerCode = $cashbox['SETTINGS']['REST']['REST_CODE'];
				if(!isset($cashboxes[$code][$restHandlerCode]))
				{
					$cashboxes[$code][$restHandlerCode] = [];
				}

				$cashboxes[$code][$restHandlerCode][] = $cashbox;
			}
			else
			{
				$cashboxes[$code][] = $cashbox;
			}
		}

		foreach($cashboxDescriptions as &$cashboxDescription)
		{
			$isRestCashbox = $cashboxDescription['data']['handler'] === '\\'.Sale\Cashbox\CashboxRest::class;

			$code = $cashboxDescription['data']['handler']::getCode();
			if (isset($cashboxDescription['data']['kkm-id']))
			{
				$code .= '_'.$cashboxDescription['data']['kkm-id'];
			}

			if ($isRestCashbox)
			{
				$restHandlerCode = $cashboxDescription['id'];
				$handlerCashboxes = $cashboxes[$code][$restHandlerCode];
			}
			else
			{
				$handlerCashboxes = $cashboxes[$code];
			}

			if (isset($handlerCashboxes) && is_array($handlerCashboxes))
			{
				if (Sale\Cashbox\Manager::isPaySystemCashbox($cashboxDescription['data']['handler']))
				{
					$cashboxDescription['data']['menuItems'] = $this->getPaySystemCashboxMenu($handlerCashboxes);
				}
				else
				{
					$cashboxDescription['data']['menuItems'] = $this->getCashboxMenu($cashboxDescription['data'], $handlerCashboxes);
				}

				foreach($handlerCashboxes as $handlerCashbox)
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

	private function getPaySystemCashboxItems(): array
	{
		$result = [];
		$paySystemCashbox = [];

		$cashboxList = Sale\Cashbox\Manager::getListFromCache();
		foreach ($cashboxList as $cashbox)
		{
			if ($cashbox['ACTIVE'] === 'N')
			{
				continue;
			}

			if ($cashbox['HANDLER'] === '\\' . Sale\Cashbox\CashboxRobokassa::class)
			{
				$paySystemCashbox = $cashbox;
				break;
			}
		}

		if ($paySystemCashbox)
		{
			$code = $paySystemCashbox['HANDLER']::getCode();

			$result[] = [
				'id' => $code,
				'title' => Sale\Cashbox\CashboxRobokassa::getName(),
				'image' => $this->getImagePath().$code.'.svg',
				'itemSelectedColor' => '#FF5722',
				'itemSelectedImage' => $this->getImagePath().$code.'_s.svg',
				'itemSelected' => true,
				'data' => [
					'paySystem' => true,
					'type' => 'cashbox',
					'handler' => $paySystemCashbox['HANDLER'],
					'connectPath' => $this->getCashboxEditUrl([
						'handler' => $paySystemCashbox['HANDLER'],
					]),
					'showMenu' => false,
				]
			];
		}

		return $result;
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
	private function getCashboxMenu(array $cashboxData, array $cashboxes): array
	{
		$result = [];

		foreach($cashboxes as $cashbox)
		{
			$isRestHandler = $cashbox['HANDLER'] === '\Bitrix\Sale\Cashbox\CashboxRest';
			if(empty($result))
			{
				$addUrlParams = [
					'handler' => $cashboxData['handler'],
				];
				if (isset($cashboxData['kkm-id']))
				{
					$addUrlParams['kkm-id'] = $cashboxData['kkm-id'];
				}
				if ($isRestHandler)
				{
					$addUrlParams['restHandler'] = $cashbox['SETTINGS']['REST']['REST_CODE'];
				}

				$result = [
					[
						'NAME' => Loc::getMessage('SCP_CASHBOX_ADD'),
						'LINK' => $this->getCashboxEditUrl($addUrlParams),
					],
					[
						'DELIMITER' => true,
					],
				];
			}
			$editUrlParams = ['id' => $cashbox['ID'], 'handler' => $cashbox['HANDLER']];
			if ($isRestHandler)
			{
				$editUrlParams['restHandler'] = $cashbox['SETTINGS']['REST']['REST_CODE'];
			}
			$result[] = [
				'NAME' => Loc::getMessage('SCP_CASHBOX_SETTINGS', [
					'#CASHBOX_NAME#' => htmlspecialcharsbx($cashbox['NAME'])
				]),
				'LINK' => $this->getCashboxEditUrl($editUrlParams),
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

	private function getPaySystemCashboxMenu(array $cashboxes): array
	{
		$result = [];

		foreach($cashboxes as $cashbox)
		{
			$result[] = [
				'NAME' => Loc::getMessage('SCP_CASHBOX_SETTINGS', [
					'#CASHBOX_NAME#' => htmlspecialcharsbx($cashbox['NAME'])
				]),
				'LINK' => $this->getCashboxEditUrl([
					'id' => $cashbox['ID'],
					'handler' => $cashbox['HANDLER'],
				]),
			];
		}

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
	public function reloadCashboxItemAction($handler = null, $cashboxId = null): array
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
			if ($cashboxItem["id"] === $cashboxId)
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
	 * @return array[]
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getActiveCashboxHandlersByCountry()
	{
		$cashboxesByCountry = [
			'RU' => [],
			'UA' => [],
		];

		$filter = SaleManager::getInstance()->getCashboxFilter();
		$cashboxList = Sale\Cashbox\Internals\CashboxTable::getList([
			'select' => ['HANDLER', 'NAME'],
			'filter' => $filter,
		]);
		while($cashbox = $cashboxList->fetch())
		{
			$handler = $cashbox['HANDLER'];
			if ($cashbox['ACTIVE'] === 'N' || $handler === '\Bitrix\Sale\Cashbox\CashboxRest')
			{
				continue;
			}

			$handler = $cashbox['HANDLER'];

			if ($handler === '\Bitrix\Sale\Cashbox\CashboxCheckbox')
			{
				$country = 'UA';
			}
			else
			{
				$country = 'RU';
			}

			$cashboxesByCountry[$country][] = htmlspecialcharsbx($cashbox['NAME']);
		}

		return $cashboxesByCountry;
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