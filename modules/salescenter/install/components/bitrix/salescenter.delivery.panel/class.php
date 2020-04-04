<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale,
	Bitrix\Sale\Delivery,
	Bitrix\Main\Engine\Contract\Controllerable;

/**
 * Class SalesCenterDeliveryPanel
 */
class SalesCenterDeliveryPanel extends CBitrixComponent
{
	protected $deliveryPanelId = 'salescenter-extra-delivery';

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
			ShowError(Loc::getMessage('SDP_SALESCENTER_MODULE_ERROR'));
			return;
		}
		Loader::includeModule('sale');

		$this->arResult['deliveryPanelParams'] = [
			'id' => $this->deliveryPanelId,
			'items' => $this->getDeliveryPanelItems(),
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
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getDeliveryPanelItems()
	{
		$deliveryPanelItems = [];
		$imagePath = $this->__path.'/templates/.default/images/';

		$currentDeliveries = $this->getCurrentDeliveries();
		$deliveryTypes = $this->getDeliveryTypes();
		foreach ($deliveryTypes as $deliveryType)
		{
			$isSelected = false;
			if (!isset($currentDeliveries[$deliveryType['TYPE']]['TYPE'])
				|| empty($currentDeliveries[$deliveryType['TYPE']]['TYPE'])
			)
			{
				$currentDeliveries[$deliveryType['TYPE']]['TYPE'] = [];
			}

			if (!isset($currentDeliveries[$deliveryType['TYPE']]['ITEMS'])
				|| empty($currentDeliveries[$deliveryType['TYPE']]['ITEMS'])
			)
			{
				$currentDeliveries[$deliveryType['TYPE']]['ITEMS'] = [];
			}

			switch ($deliveryType['TYPE'])
			{
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

					$deliveryPanelItems[] = [
						'id' => 'spsr',
						'title' => $deliveryType['NAME'],
						'image' => $imagePath.'spsr_express.svg',
						'itemSelected' => $isSelected,
						'itemSelectedColor' => "#013E57",
						'itemSelectedImage' => $imagePath.'spsr_express_s.svg',
						'data' => [
							'type' => 'delivery',
							'connectPath' => $deliveryType['LINK'],
							'menuItems' => $this->getMenuItems($deliveryType['TYPE'], 'SPSR'),
							'showMenu' => (isset($currentDeliveries[$deliveryType['TYPE']]['ITEMS'])),
						],
					];
					break;
				case '\Sale\Handlers\Delivery\AdditionalHandler':
					if ($deliveryType['SERVICE_TYPE'] == 'DPD')
					{
						if (isset($currentDeliveries[$deliveryType['TYPE']])
							&& in_array(
								$deliveryType['SERVICE_TYPE'],
								$currentDeliveries[$deliveryType['TYPE']]['TYPE']
							)
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

						$deliveryPanelItems[] = [
							'id' => 'dpd',
							'title' => $deliveryType['NAME'],
							'image' => $imagePath.'dpd.svg',
							'itemSelected' => $isSelected,
							'itemSelectedColor' => "#DC0032",
							'itemSelectedImage' => $imagePath.'dpd_s.svg',
							'data' => [
								'type' => 'delivery',
								'connectPath' => $deliveryType['LINK'],
								'menuItems' => $this->getMenuItems($deliveryType['TYPE'], 'DPD'),
								'showMenu' => (in_array('DPD', $currentDeliveries[$deliveryType['TYPE']]['TYPE']))
							],
						];
					}
					elseif ($deliveryType['SERVICE_TYPE'] == 'CDEK')
					{
						if (isset($currentDeliveries[$deliveryType['TYPE']])
							&& in_array(
								$deliveryType['SERVICE_TYPE'],
								$currentDeliveries[$deliveryType['TYPE']]['TYPE']
							)
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

						$deliveryPanelItems[] = [
							'id' => 'cdek',
							'title' => $deliveryType['NAME'],
							'image' => $imagePath.'cdek.svg',
							'itemSelected' => $isSelected,
							'itemSelectedColor' => "#57A52C",
							'itemSelectedImage' => $imagePath.'cdek_s.svg',
							'data' => [
								'type' => 'delivery',
								'connectPath' => $deliveryType['LINK'],
								'menuItems' => $this->getMenuItems($deliveryType['TYPE'], 'CDEK'),
								'showMenu' => (in_array('CDEK', $currentDeliveries[$deliveryType['TYPE']]['TYPE']))
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

					$deliveryPanelItems[] = [
						'id' => 'simple',
						'title' => $deliveryType['NAME'],
						'image' => $imagePath.'by_location.svg',
						'itemSelected' => $isSelected,
						'itemSelectedColor' => "#177CE2",
						'itemSelectedImage' => $imagePath.'by_location_s.svg',
						'data' => [
							'type' => 'delivery',
							'connectPath' => $deliveryType['LINK'],
							'menuItems' => $this->getMenuItems($deliveryType['TYPE'], 'SIMPLE'),
							'showMenu' => (isset($currentDeliveries[$deliveryType['TYPE']]['ITEMS'])),
						],
					];
					break;
			}
		}

		return $deliveryPanelItems;
	}

	/**
	 * @return array
	 */
	protected function getDeliveryTypesToExclude()
	{
		return [
			'\Bitrix\Sale\Delivery\Services\Automatic',
			'\Bitrix\Sale\Delivery\Services\Configurable',
			'\Bitrix\Sale\Delivery\Services\EmptyDeliveryService',
			'\Bitrix\Sale\Delivery\Services\Group'
		];
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getDeliveryTypes()
	{
		$classNamesList = Delivery\Services\Manager::getHandlersList();
		$classesToExclude = $this->getDeliveryTypesToExclude();

		$deliveryTypes = [];

		/** @var Delivery\Services\Base $class */
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
						if ($srvType == 'RUSPOST')
						{
							continue;
						}

						if(!empty($srvParams["NAME"]))
						{
							$queryParams = [
								'lang' => LANGUAGE_ID,
								'PARENT_ID' => 0,
								'CLASS_NAME' => $class,
								'SERVICE_TYPE' => $srvType,
								'publicSidePanel' => 'Y'
							];

							$editUrl = $this->arParams['SEF_FOLDER']."sale_delivery_service_edit/?".http_build_query($queryParams);
							$deliveryTypes[] = [
								"TYPE" => $class,
								"SERVICE_TYPE" => $srvType,
								"NAME" => $srvParams["NAME"],
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
					'CLASS_NAME' => $class,
					'publicSidePanel' => 'Y'
				];

				$editUrl = $this->arParams['SEF_FOLDER']."sale_delivery_service_edit/?".http_build_query($queryParams);
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
	protected function getMenuItems($class, $type)
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
				'NAME' => Loc::getMessage('SDP_SALESCENTER_DELIVERY_ADD'),
				'LINK' => $deliveryPathAdd.http_build_query($queryEdit)
			],
			[
				'DELIMITER' => true
			],
			[
				'NAME' => Loc::getMessage('SDP_SALESCENTER_DELIVERY_LIST'),
				'LINK' => $deliveryPath."?".http_build_query($queryList),
				'FILTER' => [
					'CLASS_NAME' => $class,
				],
			]
		];

		return $menuItems;
	}
}