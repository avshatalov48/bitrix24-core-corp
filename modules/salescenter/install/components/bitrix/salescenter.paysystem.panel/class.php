<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale;

/**
 * Class SalesCenterPaySystemPanel
 */
class SalesCenterPaySystemPanel extends CBitrixComponent
{
	protected $paySystemPanelId = 'salescenter-paysystem';

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
	 * @throws \Bitrix\Main\SystemException
	 */
	public function executeComponent()
	{
		if(!Loader::includeModule('salescenter'))
		{
			ShowError(Loc::getMessage('SPP_SALESCENTER_MODULE_ERROR'));
			return;
		}
		Loader::includeModule('sale');

		$this->arResult['paySystemPanelParams'] = [
			'id' => $this->paySystemPanelId,
			'items' => $this->getPaySystemItems(),
		];

		$this->includeComponentTemplate();
	}

	/**
	 * @param $error
	 */
	protected function showError($error)
	{
		ShowError($error);
	}

	/**
	 * @return array
	 */
	protected function getPaySystemHandlers()
	{
		$fullList = [
			'paypal' => [],
			'sberbankonline' => [],
			'yandexcheckout' => [
				'bank_card',
				'sberbank',
				'sberbank_sms',
				'alfabank',
				'yandex_money',
				'webmoney',
				'qiwi'
			],
			'liqpay' => [],
		];

		if(!\Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->isEnabled())
		{
			return $fullList;
		}

		if (\Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->isCurrentZone('ru'))
		{
			return [
				'sberbankonline' => [],
				'yandexcheckout' => [
					'bank_card',
					'sberbank',
					'sberbank_sms',
					'alfabank',
					'yandex_money',
					'webmoney',
					'qiwi'
				],
				'paypal' => [],
			];
		}
		elseif (\Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->isCurrentZone('ua'))
		{
			return [
				'liqpay' => [],
				'paypal' => [],
			];
		}
		else
		{
			return [
				'paypal' => [],
			];
		}
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getPaySystemItems()
	{
		$paySystemPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem');
		$paySystemPath = getLocalPath('components'.$paySystemPath.'/slider.php')."?";

		$paySystemHandlerList = $this->getPaySystemHandlers();

		$paySystemPanel = [
			'sberbankonline',
			'yandexcheckout' => [
				'bank_card',
				'alfabank',
				'yandex_money',
				'qiwi',
				'webmoney'
			],
			'paypal',
			'liqpay',
		];

		$paySystemColorList = [
			'sberbankonline' => '#2C9B47',
			'qiwi' => '#E9832C',
			'paypal' => '#243B80',
			'webmoney' => '#006FA8',
			'yandexcheckout' => [
				'alfabank' => '#EE2A23',
				'bank_card' => '#19D0C8',
				'yandex_money' => '#E10505',
				'qiwi' => '#E9832C',
				'webmoney' => '#006FA8'
			],
			'liqpay' => '#7AB72B',
		];

		$paySystemSortList = [
			'sberbankonline' => 200,
			'paypal' => 2000,
			'yandexcheckout' => [
				'alfabank' => 1200,
				'bank_card' => 500,
				'yandex_money' => 1300,
				'sberbank' => 600,
				'sberbank_sms' => 700,
				'qiwi' => 1600,
				'webmoney' => 1700
			],
			'liqpay' => 2100
		];

		$filter = \Bitrix\SalesCenter\Integration\SaleManager::getInstance()->getPaySystemFilter();
		unset($filter['ACTIVE']);
		$paySystemIterator = Sale\PaySystem\Manager::getList([
			'select' => ['ID', 'ACTIVE', 'NAME', 'ACTION_FILE', 'PS_MODE'],
			'filter' => $filter,
		]);

		$paySystemActions = $paySystemList = [];
		foreach ($paySystemIterator as $paySystem)
		{
			$paySystemList[$paySystem['ACTION_FILE']][] = $paySystem;
		}

		$queryParams = [
			'lang' => LANGUAGE_ID,
			'publicSidePanel' => 'Y',
		];

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

								$queryParams['ID'] = $paySystemItem['ID'];
								$link = $paySystemPath.http_build_query($queryParams);
								$paySystemActions[$paySystemItem['ACTION_FILE']]['ITEMS'][$paySystemItem['PS_MODE']][] = [
									'NAME' => Loc::getMessage('SPP_PAYSYSTEM_SETTINGS', [
										'#PAYSYSTEM_NAME#' => $paySystemItem['NAME']
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

						$queryParams['ID'] = $paySystemItem['ID'];
						$link = $paySystemPath.http_build_query($queryParams);
						$paySystemActions[$paySystemItem['ACTION_FILE']]['ITEMS'][] = [
							'NAME' => Loc::getMessage('SPP_PAYSYSTEM_SETTINGS', [
								'#PAYSYSTEM_NAME#' => $paySystemItem['NAME']
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

		$imagePath = $this->__path.'/templates/.default/images/';
		$paySystemItems = [];
		foreach ($paySystemActions as $handler => $paySystem)
		{
			if (!in_array($handler, $paySystemPanel) && (!isset($paySystemPanel[$handler])))
			{
				continue;
			}

			$items = [];
			$isActive = false;
			$title = Loc::getMessage('SPP_PAYSYSTEM_'.strtoupper($handler).'_TITLE');

			if ($paySystem)
			{
				$isPsMode = $paySystem['PS_MODE'];
				if ($isPsMode)
				{
					foreach ($paySystem['ITEMS'] as $psMode => $paySystemItem)
					{
						if (!in_array($psMode, $paySystemPanel[$handler]))
						{
							continue;
						}

						$type = $psMode;

						$title = Loc::getMessage('SPP_PAYSYSTEM_'.strtoupper($handler).'_'.strtoupper($psMode).'_TITLE');

						$items = $paySystemItem;
						$isActive = $paySystemActions[$handler]['ACTIVE'][$psMode];

						$paySystemItems[] = [
							'id' => $handler.'_'.$psMode,
							'sort' => (isset($paySystemSortList[$handler][$psMode]) ? $paySystemSortList[$handler][$psMode] : 100),
							'title' => $title,
							'image' => $imagePath.$handler.'_'.$psMode.'.svg',
							'itemSelectedColor' => $paySystemColorList[$handler][$psMode],
							'itemSelected' => $isActive,
							'itemSelectedImage' => $imagePath.$handler.'_'.$psMode.'_s.svg',
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
								'menuItems' => $items,
								'showMenu' => !empty($items),
								'paySystemType' => $type,
							],
						];
					}
				}
				else
				{
					$items = $paySystem['ITEMS'];
					$isActive = $paySystemActions[$handler]['ACTIVE'];
					$type = $handler;

					$paySystemItems[] = [
						'id' => $handler,
						'sort' => (isset($paySystemSortList[$handler]) ? $paySystemSortList[$handler] : 100),
						'title' => $title,
						'image' => $imagePath.$handler.'.svg',
						'itemSelectedColor' => $paySystemColorList[$handler],
						'itemSelected' => $isActive,
						'itemSelectedImage' => $imagePath.$handler.'_s.svg',
						'data' => [
							'type' => 'paysystem',
							'connectPath' => $paySystemPath.http_build_query(array_merge($queryParams, ['ACTION_FILE' => $handler])),
							'menuItems' => $items,
							'showMenu' => !empty($items),
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
					'image' => $imagePath.$handler.'.svg',
					'itemSelectedColor' => $paySystemColorList[$handler],
					'itemSelected' => $isActive,
					'itemSelectedImage' => $imagePath.$handler.'_s.svg',
					'data' => [
						'type' => 'paysystem',
						'connectPath' => $paySystemPath.http_build_query(array_merge($queryParams, ['ACTION_FILE' => $handler])),
						'menuItems' => $items,
						'showMenu' => !empty($items),
						'paySystemType' => $type,
					],
				];
			}
		}

		sortByColumn($paySystemItems, ["sort" => SORT_ASC]);

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
	 * @param $paySystemActions
	 * @return array
	 */
	private function getPaySystemMenu(array $paySystemActions)
	{
		$paySystemPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem');
		$paySystemPath = getLocalPath('components'.$paySystemPath.'/slider.php')."?";

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
				'ACTION_FILE' => strtolower($handler)
			];

			if ($paySystems['PS_MODE'])
			{
				foreach ($paySystems['ITEMS'] as $psMode => $paySystem)
				{
					if (!$paySystem)
					{
						continue;
					}

					$queryParams['ACTION_FILE'] = $handler;
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
}