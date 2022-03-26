<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\SalesCenter\Integration\RestManager,
	Bitrix\SalesCenter\Integration\Bitrix24Manager,
	Bitrix\Rest,
	Bitrix\SalesCenter;
use Bitrix\Sale\Delivery\Services\Table;
use Bitrix\SalesCenter\Integration\SaleManager;

/**
 * Class SalesCenterDeliveryPanel
 */
class SalesCenterDeliveryPanel extends CBitrixComponent implements Main\Engine\Contract\Controllerable
{
	private const MARKETPLACE_CATEGORY_DELIVERY = 'delivery';
	private const TITLE_LENGTH_LIMIT = 50;

	protected $deliveryPanelId = 'salescenter-extra-delivery';

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [];
	}

	/**
	 * @param $arParams
	 * @return array
	 */
	public function onPrepareComponentParams($arParams)
	{
		return parent::onPrepareComponentParams($arParams);
	}

	protected function listKeysSignedParameters()
	{
		return [
			'SEF_FOLDER',
		];
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
			$this->showError(Loc::getMessage('SDP_SALESCENTER_MODULE_ERROR'));
			return;
		}
		Loader::includeModule('sale');

		if(!SaleManager::getInstance()->isManagerAccess(true))
		{
			$this->showError(Loc::getMessage('SDP_ACCESS_DENIED'));
			return;
		}

		$this->prepareResult();

		$this->includeComponentTemplate();
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function prepareResult(): array
	{
		$internalItems = $this->getDeliveryPanelItems();

		if (RestManager::getInstance()->isEnabled())
		{
			$recommendedItemCodeList = $this->getMarketplaceRecommendedItemCodeList();
			$marketplaceItems = $this->getMarketplaceItems($recommendedItemCodeList);
		}

		$items = array_merge($internalItems, $marketplaceItems);

		if (Bitrix24Manager::getInstance()->isEnabled())
		{
			$items[] = $this->getRecommendItem();
		}

		if (RestManager::getInstance()->isEnabled())
		{
			foreach ($this->getActionboxItems() as $actionboxItem)
			{
				$items[] = $actionboxItem;
			}
		}

		$this->arResult['deliveryPanelParams'] = [
			'id' => $this->deliveryPanelId,
			'items' => $items,
		];

		return $this->arResult;
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
	 * @throws Main\SystemException
	 */
	private function getDeliveryPanelItems(): array
	{
		$result = [];

		$handlers = (new SalesCenter\Delivery\Handlers\HandlersRepository())
			->getCollection()
			->getInstallableItems();

		foreach ($handlers as $handler)
		{
			$menuItems = [
				[
					'NAME' => Loc::getMessage('SDP_SALESCENTER_DELIVERY_ADD'),
					'LINK' => $handler->getInstallationLink()
				],
				[
					'DELIMITER' => true
				],
			];

			$filter = [
				'=CLASS_NAME' => $handler->getHandlerClass()
			];
			if ($handler instanceof SalesCenter\Delivery\Handlers\IRestHandler)
			{
				$filter['%CONFIG'] = $handler->getRestHandlerCode();
			}

			$existingItems = Table::getList(['filter' => $filter]);

			foreach ($existingItems as $existingItem)
			{
				$menuItems[] = [
					'NAME' => sprintf(
						'%s %s',
						Loc::getMessage('SDP_SALESCENTER_DELIVERY_EDIT'),
						$existingItem['NAME']
					),
					'LINK' => $handler->getEditLink($existingItem['ID'])
				];
			}

			$result[] = [
				'title' => $handler->getName(),
				'image' => $handler->getImagePath(),
				'itemSelectedImage' => $handler->getInstalledImagePath(),
				'itemSelected' => $handler->isInstalled(),
				'itemSelectedColor' => $handler->getInstalledColor(),
				'data' => [
					'type' => 'delivery',
					'connectPath' => $handler->getInstallationLink(),
					'menuItems' => $menuItems,
					'showMenu' => ($existingItems->getSelectedRowsCount() > 0),
					'hasOwnIcon' => true,
				],
			];
		}

		return $result;
	}

	/**
	 * @param $class
	 * @return array
	 */
	private function buildServiceListAdminLink($class)
	{
		$deliveryPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.delivery.panel');
		$deliveryPath = getLocalPath('components'.$deliveryPath.'/slider.php');

		$queryList = [
			'show_delivery_list' => 'Y',
			'CLASS_NAME' => $class,
		];

		return [
			'NAME' => Loc::getMessage('SDP_SALESCENTER_DELIVERY_LIST'),
			'LINK' => $deliveryPath."?".http_build_query($queryList),
			'FILTER' => [
				'CLASS_NAME' => $class,
			],
		];
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

		$filterOption = new \Bitrix\Main\UI\Filter\Options('tbl_sale_delivery_list');
		$filterData = $filterOption->getPresets();

		$filterData[$filterId] = $filterData['default_filter'];
		$filterData[$filterId]['filter_rows'] = implode(',', array_keys($filter));
		$filterData[$filterId]['fields'] = $filter;

		$filterOption->setDefaultPreset($filterId);
		$filterOption->setPresets($filterData);
		$filterOption->save();
	}

	/**
	 * @param array $marketplaceItemCodeList
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getMarketplaceItems(array $marketplaceItemCodeList): array
	{
		$installedApps = $this->getMarketplaceInstalledApps();
		$marketplaceItemCodeList = array_unique(array_merge(array_keys($installedApps), $marketplaceItemCodeList));
		$zone = $this->getZone();
		$partnerItemList = [];

		foreach($marketplaceItemCodeList as $marketplaceItemCode)
		{
			if ($marketplaceApp = RestManager::getInstance()->getMarketplaceAppByCode($marketplaceItemCode))
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
					'sort' => $marketplaceApp['ID'],
				];
			}
		}

		if ($partnerItemList)
		{
			Main\Type\Collection::sortByColumn($partnerItemList, ['sort' => SORT_ASC]);
		}

		return $partnerItemList;
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getMarketplaceInstalledApps(): array
	{
		static $marketplaceInstalledApps = [];
		if(!empty($marketplaceInstalledApps))
		{
			return $marketplaceInstalledApps;
		}

		$marketplaceAppCodeList = RestManager::getInstance()->getMarketplaceAppCodeList(self::MARKETPLACE_CATEGORY_DELIVERY);
		$appIterator = Rest\AppTable::getList([
			'select' => [
				'ID',
				'CODE',
			],
			'filter' => [
				'=CODE' => $marketplaceAppCodeList,
				'SCOPE' => '%sale%',
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
	 * @return array|string[]
	 * @throws Main\SystemException
	 */
	private function getMarketplaceRecommendedItemCodeList(): array
	{
		$result = [];

		$zone = $this->getZone();
		$partnerItems = RestManager::getInstance()->getByTag([
			RestManager::TAG_DELIVERY,
			RestManager::TAG_DELIVERY_RECOMMENDED,
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

	private function getRecommendItem(): array
	{
		$feedbackPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.feedback');
		$feedbackPath = getLocalPath('components'.$feedbackPath.'/slider.php');
		$feedbackPath = new Main\Web\Uri($feedbackPath);

		$queryParams = [
			'lang' => LANGUAGE_ID,
			'feedback_type' => 'delivery_offer',
		];
		$feedbackPath->addParams($queryParams);

		return [
			'id' => 'recommend',
			'title' => Loc::getMessage('SDP_SALESCENTER_DELIVERY_RECOMMEND'),
			'image' => $this->getImagePath().'recommend.svg',
			'data' => [
				'type' => 'recommend',
				'connectPath' => $feedbackPath->getLocator(),
			]
		];
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
	 * @return string
	 */
	private function getImagePath(): string
	{
		static $imagePath = '';
		if ($imagePath)
		{
			return $imagePath;
		}

		$componentPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.delivery.panel');
		$componentPath = getLocalPath('components'.$componentPath);

		$imagePath = $componentPath.'/templates/.default/images/';
		return $imagePath;
	}

	/**
	 * @param string $title
	 * @return string
	 */
	private function getFormattedTitle(string $title): string
	{
		if (mb_strlen($title) > self::TITLE_LENGTH_LIMIT)
		{
			$title = mb_substr($title, 0, self::TITLE_LENGTH_LIMIT - 3).'...';
		}

		return $title;
	}

	/**
	 * @return array
	 */
	private function getActionboxItems(): array
	{
		$dynamicItems = [];

		$restItems = RestManager::getInstance()->getActionboxItems(RestManager::ACTIONBOX_PLACEMENT_DELIVERY);
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