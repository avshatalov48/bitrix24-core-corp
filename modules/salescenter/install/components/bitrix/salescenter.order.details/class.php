<?php

use Bitrix\Main;
use Bitrix\Main\Localization;
use Bitrix\Crm\Order;
use Bitrix\Sale\Delivery;
use Bitrix\Crm\CompanyTable;
use Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

Main\Loader::includeModule('sale');

CBitrixComponent::includeComponentClass("bitrix:sale.personal.order.detail");

class SalesCenterOrderDetails extends CBitrixPersonalOrderDetailComponent
{
	public function onPrepareComponentParams($params)
	{
		self::tryParseInt($params["CACHE_TIME"], 3600, true);

		$params['CACHE_GROUPS'] = (isset($params['CACHE_GROUPS']) && $params['CACHE_GROUPS'] == 'N' ? 'N' : 'Y');

		$params['ID'] = (int)$params['ID'];

		$params['ALLOW_INNER'] = 'N';

		if (empty($params["ACTIVE_DATE_FORMAT"]))
		{
			$params["ACTIVE_DATE_FORMAT"] = Main\Type\Date::getFormat();
		}

		if (!is_array($params["CUSTOM_SELECT_PROPS"]))
		{
			$params["CUSTOM_SELECT_PROPS"] = [];
		}
		if (!in_array('PROPERTY_MORE_PHOTO', $params['CUSTOM_SELECT_PROPS']))
		{
			$params['CUSTOM_SELECT_PROPS'][] = 'PROPERTY_MORE_PHOTO';
		}

		// resample sizes
		self::tryParseInt($params["PICTURE_WIDTH"], 110);
		self::tryParseInt($params["PICTURE_HEIGHT"], 110);

		// resample type for images
		if(!in_array($params['RESAMPLE_TYPE'], array(BX_RESIZE_IMAGE_EXACT, BX_RESIZE_IMAGE_PROPORTIONAL, BX_RESIZE_IMAGE_PROPORTIONAL_ALT)))
			$params['RESAMPLE_TYPE'] = BX_RESIZE_IMAGE_PROPORTIONAL;

		if(!$params['HEADER_TITLE'])
		{
			if (Loader::includeModule('crm'))
			{
				$res = CompanyTable::getList(
					[
						'select' => [
							'ID', 'TITLE'
						],
						'filter' => [
							'=IS_MY_COMPANY' => 'Y'
						],
						'order' => [
							'DATE_MODIFY' => 'desc'
						]
					]
				);
				if ($row = $res->fetch())
				{
					$title = $row['TITLE'];
				}
			}
			$params['HEADER_TITLE'] = $title ?? 'Company 24';
		}

		return $params;
	}

	/**
	 * @return void
	 */
	protected function checkOrder()
	{
		if (!($this->order))
		{
			$this->doCaseOrderIdNotSet();
		}
	}

	/**
	 * Function could describe what to do when order ID not set. By default, component will redirect to list page.
	 *
	 * @throws Main\SystemException
	 * @return void
	 */
	protected function doCaseOrderIdNotSet()
	{
		throw new Main\SystemException(
			Localization\Loc::getMessage("SPOD_NO_ORDER", array("#ID#" => $this->arParams["ID"])),
			self::E_ORDER_NOT_FOUND
		);
	}

	protected function checkAuthorized()
	{
		$context = Main\Context::getCurrent();
		$request = $context->getRequest();

		$this->loadOrder(urldecode(urldecode($this->arParams["ID"])));
		$this->checkOrder();

		if ($request->get('access') !== $this->order->getHash())
		{
			$msg = Localization\Loc::getMessage("SPOD_ACCESS_DENIED");
			throw new Main\SystemException($msg, self::E_NOT_AUTHORIZED);
		}
	}

	protected function obtainDataPaySystem()
	{
		return;
	}

	/**
	 * @return array
	 */
	protected function createCacheId()
	{
		global $APPLICATION;

		return array(
			$APPLICATION->GetCurPage(),
			$this->dbResult["ID"],
			$this->dbResult["PERSON_TYPE_ID"],
			$this->dbResult["DATE_UPDATE"]->toString(),
			$this->useCatalog,
			false
		);
	}

	protected function obtainData()
	{
		parent::obtainData();

		if (Main\Loader::includeModule('crm'))
		{
			if ($this->needAddTimelineEntityOnOpen())
			{
				$this->addTimelineEntityOnView();

				/** @var Order\DealBinding $dealBinding */
				$dealBinding = $this->order->getDealBinding();
				if ($dealBinding)
				{
					$this->changeOrderStageDealOnViewedNoPaid(
						$dealBinding->getDealId()
					);
				}
			}
		}
	}

	protected function obtainDataOrder()
	{
		parent::obtainDataOrder();

		$this->dbResult['SHIPMENT'] = array_values(
			array_filter(
				$this->dbResult['SHIPMENT'],
				function ($item)
				{
					return (int)$item['DELIVERY_ID'] !== (int)Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
				}
			)
		);
	}

	protected function obtainBasket(&$cached)
	{
		parent::obtainBasket($cached);

		foreach ($cached['BASKET'] as &$basketItem)
		{
			$basketItem['FORMATED_PRICE'] = SaleFormatCurrency($basketItem["PRICE"], $basketItem["CURRENCY"]);
			$basketItem['FORMATED_BASE_PRICE'] = SaleFormatCurrency($basketItem["BASE_PRICE"], $basketItem["CURRENCY"]);
		}
	}

	/**
	 * @param $item
	 * @return int
	 */
	protected function getPictureId($item): int
	{
		$result = 0;

		if ((int)$item['PROPERTY_MORE_PHOTO_VALUE'] > 0)
		{
			$result = $item['PROPERTY_MORE_PHOTO_VALUE'];
		}
		elseif ((int)$item['DETAIL_PICTURE'] > 0)
		{
			$result = $item['DETAIL_PICTURE'];
		}
		elseif ((int)$item['PREVIEW_PICTURE'] > 0)
		{
			$result = $item['PREVIEW_PICTURE'];
		}

		return (int)$result;
	}

	private function changeOrderStageDealOnViewedNoPaid($dealId)
	{
		$fields = ['ORDER_STAGE' => Order\OrderStage::VIEWED_NO_PAID];

		$deal = new \CCrmDeal(false);
		$deal->Update($dealId, $fields);
	}

	/**
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function needAddTimelineEntityOnOpen()
	{
		$dbRes = \Bitrix\Crm\Timeline\Entity\TimelineTable::getList([
			'filter' => [
				'TYPE_ID' => \Bitrix\Crm\Timeline\TimelineType::ORDER,
				'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ASSOCIATED_ENTITY_ID' => $this->order->getId()
			]
		]);

		while ($item = $dbRes->fetch())
		{
			if (isset($item['SETTINGS']['FIELDS']['VIEWED']))
			{
				return false;
			}
		}

		global $USER;

		return
			is_object($USER)
			&& (int)$USER->GetID() !== (int)$this->order->getField('RESPONSIBLE_ID')
		;
	}

	/**
	 * @throws Main\ArgumentException
	 */
	protected function addTimelineEntityOnView()
	{
		$order = $this->order;

		$bindings = [
			[
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $order->getId()
			]
		];

		if ($order->getDealBinding())
		{
			$bindings[] = [
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ENTITY_ID' => $order->getDealBinding()->getDealId()
			];
		}

		$params = [
			'ORDER_FIELDS' => $order->getFieldValues(),
			'SETTINGS' => [
				'CHANGED_ENTITY' => \CCrmOwnerType::OrderName,
				'FIELDS' => [
					'ORDER_ID' => $order->getId(),
					'VIEWED' => 'Y',
				]
			],
			'BINDINGS' => $bindings
		];

		\Bitrix\Crm\Timeline\OrderController::getInstance()->onView($order->getId(), $params);
	}
}