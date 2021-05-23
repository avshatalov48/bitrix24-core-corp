<?php
namespace Bitrix\Sale;

use Bitrix\Sale\Internals\CollectableEntity;

class EventActions
{
	const ADD = "ADD";
	const UPDATE = "UPDATE";
	const DELETE = "DELETE";

	// Events new kernel
	const EVENT_ON_ORDER_PAID = "OnSaleOrderPaid";
	const EVENT_ON_BEFORE_ORDER_DELETE = "OnSaleBeforeOrderDelete";
	const EVENT_ON_ORDER_DELETED = "OnSaleOrderDeleted";
	const EVENT_ON_ORDER_BEFORE_SAVED = "OnSaleOrderBeforeSaved";
	const EVENT_ON_ORDER_SAVED = "OnSaleOrderSaved";
	const EVENT_ON_SHIPMENT_DELIVER = "OnSaleShipmentDelivery";

	const EVENT_ON_BEFORE_ORDER_CANCELED = "OnSaleBeforeOrderCanceled";
	const EVENT_ON_ORDER_CANCELED = "OnSaleOrderCanceled";

	const EVENT_ON_ORDER_PAID_SEND_MAIL = "OnSaleOrderPaidSendMail";
	const EVENT_ON_ORDER_CANCELED_SEND_MAIL = "OnSaleOrderCancelSendEmail";

	const EVENT_ON_BASKET_BEFORE_SAVED = "OnSaleBasketBeforeSaved";
	const EVENT_ON_BASKET_ITEM_BEFORE_SAVED = "OnSaleBasketItemBeforeSaved";
	const EVENT_ON_BASKET_ITEM_SAVED = "OnSaleBasketItemSaved";
	const EVENT_ON_BASKET_SAVED = "OnSaleBasketSaved";

	const EVENT_ON_SHIPMENT_TRACKING_NUMBER_CHANGE = "OnShipmentTrackingNumberChange";
	const EVENT_ON_SHIPMENT_ALLOW_DELIVERY = "OnShipmentAllowDelivery";
	const EVENT_ON_SHIPMENT_DEDUCTED = "OnShipmentDeducted";

	const EVENT_ON_BEFORE_SHIPMENT_RESERVE = "OnSaleBeforeShipmentReserve";
	const EVENT_ON_SHIPMENT_RESERVED = "OnSaleShipmentReserved";

	const EVENT_ON_PAYMENT_PAID = "OnPaymentPaid";

	const EVENT_ON_BEFORE_ORDER_STATUS_CHANGE = "OnSaleBeforeStatusOrderChange";
	const EVENT_ON_ORDER_STATUS_CHANGE = "OnSaleStatusOrderChange";
	const EVENT_ON_ORDER_STATUS_CHANGE_SEND_MAIL = "OnSaleOrderStatusChangeSendEmail";

	const EVENT_ON_BEFORE_SHIPMENT_STATUS_CHANGE = "OnSaleBeforeStatusShipmentChange";
	const EVENT_ON_SHIPMENT_STATUS_CHANGE = "OnSaleStatusShipmentChange";
	const EVENT_ON_SHIPMENT_STATUS_CHANGE_SEND_MAIL = "OnSaleShipmentStatusChangeSendEmail";

	const EVENT_ON_ORDER_STATUS_ALLOW_PAY_CHANGE = "OnSaleStatusAllowPayChange";
	const EVENT_ON_ORDER_STATUS_ALLOW_PAY_CHANGE_SEND_MAIL = "onSaleOrderStatusAllowPaySendEmail";

	const EVENT_ON_ADMIN_ORDER_LIST = "OnSaleAdminOrderList";

	const EVENT_ON_BASKET_ITEM_REFRESH_DATA = "OnSaleBasketItemRefreshData";

	const EVENT_ON_CHECK_PRINT = "OnSalePaymentCheckPrint";

	const EVENT_ON_CHECK_PRINT_ERROR = "OnSalePaymentCheckPrintError";

	const EVENT_ON_CHECK_VALIDATION_ERROR = "OnSalePaymentCheckValidationError";

	const EVENT_ON_TAX_GET_LIST = "OnSaleTaxGetList";

	const ENTITY_COLLECTABLE_ENTITY = CollectableEntity::class;

	const EVENT_ON_ORDER_BEFORE_ARCHIVED = "OnSaleOrderBeforeArchived";

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getEventNotifyMap()
	{
		$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);

		/** @var Order $orderEntity */
		$orderEntity = $registry->getOrderClassName();

		/** @var Shipment $shipmentEntity */
		$shipmentEntity = $registry->getShipmentClassName();

		/** @var Notify $notifyEntity */
		$notifyEntity = $registry->getNotifyClassName();

		return array(
			static::EVENT_ON_ORDER_SAVED => array(
				"ENTITY" => $orderEntity,
				"METHOD" => array($notifyEntity, "sendOrderNew"),
			),
			static::EVENT_ON_ORDER_CANCELED => array(
				"ENTITY" => $orderEntity,
				"METHOD" => array($notifyEntity, "sendOrderCancel"),
			),
			static::EVENT_ON_ORDER_PAID => array(
				"ENTITY" => $orderEntity,
				"METHOD" => array($notifyEntity, "sendOrderPaid"),
			),

			static::EVENT_ON_ORDER_STATUS_CHANGE => array(
				"ENTITY" => $orderEntity,
				"METHOD" => array($notifyEntity, "sendOrderStatusChange"),
			),
			static::EVENT_ON_SHIPMENT_TRACKING_NUMBER_CHANGE => array(
				"ENTITY" => $shipmentEntity,
				"METHOD" => array($notifyEntity, "sendShipmentTrackingNumberChange"),
			),
			static::EVENT_ON_SHIPMENT_ALLOW_DELIVERY => array(
				"ENTITY" => $shipmentEntity,
				"METHOD" => array($notifyEntity, "sendShipmentAllowDelivery"),
			),
			static::EVENT_ON_SHIPMENT_STATUS_CHANGE => array(
				"ENTITY" => $shipmentEntity,
				"METHOD" => array($notifyEntity, "sendShipmentStatusChange"),
			),

			static::EVENT_ON_ORDER_STATUS_ALLOW_PAY_CHANGE => array(
				"ENTITY" => $orderEntity,
				"METHOD" => array($notifyEntity, "sendOrderAllowPayStatusChange"),
			),

			static::EVENT_ON_CHECK_PRINT => array(
				"ENTITY" => static::ENTITY_COLLECTABLE_ENTITY,
				"METHOD" => array($notifyEntity, "sendPrintableCheck"),
			),

			static::EVENT_ON_CHECK_PRINT_ERROR => array(
				"ENTITY" => static::ENTITY_COLLECTABLE_ENTITY,
				"METHOD" => array($notifyEntity, "sendCheckError"),
			),

			static::EVENT_ON_CHECK_VALIDATION_ERROR => array(
				"ENTITY" => $orderEntity,
				"METHOD" => array($notifyEntity, "sendCheckValidationError"),
			),

		);
	}

}
