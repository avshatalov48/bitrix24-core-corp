<?php

namespace Bitrix\Sale\Compatible;

use Bitrix\Main;
use Bitrix\Sale\Compatible\Internals;
use Bitrix\Sale\Internals\OrderTable;
use Bitrix\Sale;
use Bitrix\Sale\Delivery\Services;

Main\Localization\Loc::loadMessages(__FILE__);

/**
 * Class OrderCompatibility
 * @package Bitrix\Sale\Compatible
 */
class OrderCompatibility extends Internals\EntityCompatibility
{
	/** @var null|Sale\Order */
	protected $order = null;

	/** @var array  */
	protected $requestFields = null;

	/** @var null|BasketCompatibility */
	protected $basket = null;

	protected $externalPrice = null;


	const ORDER_COMPAT_ACTION_ADD = 'ADD';
	const ORDER_COMPAT_ACTION_UPDATE = 'UPDATE';
	const ORDER_COMPAT_ACTION_SAVE = 'SAVE';

	protected $runtimeFields = array();
	protected $propertyRuntimeList = array();

	/**
	 * @return string
	 */
	protected static function getRegistryType()
	{
		return Sale\Registry::REGISTRY_TYPE_ORDER;
	}

	/**
	 * @return Main\Entity\Base
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	protected static function getEntity()
	{
		return OrderTable::getEntity();
	}

	/**
	 * @return string
	 */
	protected static function getBasketCompatibilityClassName()
	{
		return BasketCompatibility::class;
	}

	/**
	 * OrderCompatibility constructor.
	 * @param array $fields
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	protected function __construct(array $fields = array())
	{
		/** @var OrderQuery query */
		$this->query = new OrderQuery(static::getEntity());
		$this->fields = new Sale\Internals\Fields($fields);
	}

	/**
	 * @return Sale\Order|null
	 */
	public function getOrder()
	{
		return $this->order;
	}

	/**
	 * @return array
	 */
	public function getRequestFields()
	{
		return $this->requestFields;
	}

	/**
	 * @param BasketCompatibility $basketCompatibility
	 */
	public function setBasketCompatibility(BasketCompatibility $basketCompatibility)
	{
		$this->basket = $basketCompatibility;
	}

	/**
	 * @param array $fields
	 * @return static
	 */
	public static function create(array $fields)
	{
		$adminSection = (defined('ADMIN_SECTION') && ADMIN_SECTION === true);

		$orderCompatibility = new static();

		$lid = $fields['LID'];
		$userId = $fields['USER_ID'];
		$currency = $fields['CURRENCY'];

		$registry = Sale\Registry::getInstance(static::getRegistryType());
		/** @var Sale\Order $orderClassName */
		$orderClassName = $registry->getOrderClassName();

		if (isset($fields['ID']) && intval($fields['ID']) > 0)
		{
			if (!$order = $orderClassName::load($fields['ID']))
			{
				throw new Sale\UserMessageException('Order not found');
			}
		}
		else
		{
			if (!$order = $orderClassName::create($lid, $userId, $currency))
			{
				throw new Sale\UserMessageException('Order not create');
			}
		}

		if (isset($fields['PERSON_TYPE_ID']) && intval($fields['PERSON_TYPE_ID']) > 0)
		{
			$order->setPersonTypeId($fields['PERSON_TYPE_ID']);
		}

		$orderFields = static::replaceFields($fields, static::getOrderReplaceFields());

		$orderFields = $orderCompatibility->parseRawFields(static::ENTITY_ORDER, $orderFields);

		$orderFields = static::clearFields($orderFields);
		foreach (static::getFieldsFromOtherEntities() as $wrongField)
		{
			if (array_key_exists($wrongField, $fields))
				unset($orderFields[$wrongField]);
		}

		$orderFields = static::convertDateFields($orderFields, static::getOrderDateFields());

		unset($orderFields['MARKED']);
		unset($orderFields['CANCELED']);

		if (array_key_exists('PRICE', $orderFields))
		{
			$orderCompatibility->externalPrice = $orderFields['PRICE'];
		}

		if ($order->getId() > 0)
		{
			if ($adminSection)
			{
				unset($orderFields['PRICE']);
			}

			unset($orderFields['PRICE_DELIVERY']);
			unset($orderFields['DISCOUNT_VALUE']);
			unset($orderFields['TAX_VALUE']);
			$order->setField('DATE_UPDATE', new Main\Type\DateTime());
		}
		else
		{
			if (!$adminSection)
				unset($orderFields['SUM_PAID']);

			$order->setField('DATE_INSERT', new Main\Type\DateTime());
			$order->setField('DATE_UPDATE', new Main\Type\DateTime());
		}

		unset($orderFields['TAX_PRICE']);

		if (array_key_exists('STATUS_ID', $orderFields) && $order->getId() > 0)
		{
			$order->setField('STATUS_ID', $orderFields['STATUS_ID']);
			unset($orderFields['STATUS_ID']);
		}

		if (isset($orderFields['USE_VAT']) && $orderFields['USE_VAT'] === true)
		{
			$orderFields['USE_VAT'] = 'Y';
		}

		$order->setFieldsNoDemand($orderFields);

		$orderCompatibility->order = $order;

		$orderCompatibility->requestFields = $fields;

		$order->getDiscount();

		return $orderCompatibility;
	}

	/**
	 * Filling the order data from request
	 *
	 * @internal
	 *
	 * @param Sale\Order $order		Entity order.
	 * @param array $fields			An array of request data.
	 *
	 * @return Sale\Result
	 */
	public static function fillOrderFromRequest(Sale\Order $order, array $fields)
	{
		global $USER;
		$result = new Sale\Result();
		if (isset($fields['CANCELED']))
		{
			if ($order->getId() > 0 && $order->getField('CANCELED') != $fields['CANCELED'])
			{
				if (!(\CSaleOrder::CanUserCancelOrder($order->getId(), $USER->GetUserGroupArray(), $USER->GetID())))
				{
					$result->addError( new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_COMPATIBLE_ORDER_CANCEL_NO_PERMISSION'), 'SALE_COMPATIBLE_ORDER_CANCEL_NO_PERMISSION') );
					return $result;
				}

				/** @var Sale\Result $r */
				$r = $order->setField('CANCELED', $fields['CANCELED']);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
					return $result;
				}

				if (array_key_exists("REASON_CANCELED", $fields))
				{
					/** @var Sale\Result $r */
					$r = $order->setField('REASON_CANCELED', $fields['REASON_CANCELED']);
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
						return $result;
					}
				}

			}
		}


		if (isset($fields['MARKED']))
		{
			if ($order->getId() > 0)
			{
				if ($fields['MARKED'] == 'Y')
				{
					$reasonMarked = '';
					if (!empty($fields['REASON_MARKED']))
					{
						$reasonMarked = trim($fields['REASON_MARKED']);
					}

					$r = new Sale\Result();
					$r->addError(new Sale\ResultWarning($reasonMarked, 'SALE_ORDER_MARKER_ERROR'));

					$registry = Sale\Registry::getInstance(static::getRegistryType());

					/** @var Sale\EntityMarker $entityMarkerClassName */
					$entityMarkerClassName = $registry->getEntityMarkerClassName();
					$entityMarkerClassName::addMarker($order, $order, $r);
				}

				if ($order->getField('MARKED') != $fields['MARKED'])
				{
					/** @var Sale\Result $r */
					$r = $order->setField('MARKED', $fields['MARKED']);
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
					}
				}
			}
		}

		if ($order->getId() > 0 && !empty($fields['ACCOUNT_NUMBER']) && !empty($fields['SITE_ID']))
		{
			$filter = array(
				'filter' => array(
					'ACCOUNT_NUMBER' => $fields['ACCOUNT_NUMBER'],
					'!ID' => $order->getId()
				),
				'select' => array('ID')
			);

			$registry = Sale\Registry::getInstance(static::getRegistryType());

			/** @var Sale\Order $orderClassName */
			$orderClassName = $registry->getOrderClassName();
			if (($res = $orderClassName::getList($filter)) && ($res->fetch()))
			{
				$result->addError(new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_COMPATIBLE_ORDER_ACCOUNT_NUMBER_ALREADY_EXISTS'), 'SALE_COMPATIBLE_ORDER_ACCOUNT_NUMBER_ALREADY_EXISTS'));
			}
		}


		return $result;
	}


	/**
	 * Filling the shipment collection  data from request
	 *
	 * @internal
	 *
	 * @param Sale\ShipmentCollection $shipmentCollection	Entity shipment collection.
	 * @param array $fields									An array of request data.
	 * @param Sale\ShipmentCollection $shipmentCollection
	 * @param array $fields
	 *
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectNotFoundException
	 */
	public function fillShipmentCollectionFromRequest(Sale\ShipmentCollection $shipmentCollection, array $fields)
	{
		$result = new Sale\Result();

		/** @var Sale\Order $order */
		if (!$order = $shipmentCollection->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}

		$shipment = null;
		$deliveryId = null;
		$deliveryCode = isset($fields['DELIVERY_ID']) && strval(trim($fields['DELIVERY_ID'])) != '' ? trim($fields['DELIVERY_ID']) : null;

		if (strval(trim($deliveryCode)) != '')
		{
			$deliveryId = \CSaleDelivery::getIdByCode($deliveryCode);
		}

		if ($order->getId() > 0)
		{
			//todo: check $deliveryId

			if (count($shipmentCollection) == 2 && $shipmentCollection->isExistsSystemShipment())
			{
				/** @var Sale\Shipment $shipment */
				foreach($shipmentCollection as $shipment)
				{
					if ($shipment->isSystem())
						continue;

					if ($deliveryId > 0 && $deliveryId != $shipment->getDeliveryId())
					{
						/** @var Sale\Result $r */
						$r = $shipment->setField('DELIVERY_ID', $deliveryId);
						if ($r->isSuccess())
						{
							/** @var Services\Base $deliveryService */
							$deliveryService = Sale\Delivery\Services\Manager::getObjectById($deliveryId);
							if ($deliveryService->isProfile())
								$fields['DELIVERY_NAME'] = $deliveryService->getNameWithParent();
							else
								$fields['DELIVERY_NAME'] = $deliveryService->getName();
						}
						else
						{
							$result->addErrors($r->getErrors());
						}
					}
					elseif (intval($deliveryId) == 0 && array_key_exists('DELIVERY_ID', $fields) || (intval($deliveryId) !== intval($deliveryCode)))
					{
						unset($fields['DELIVERY_ID']);
					}

					if (array_key_exists('PRICE_DELIVERY', $fields) && (float)$fields['PRICE_DELIVERY'] != $shipment->getField('PRICE_DELIVERY'))
					{
						$fields['BASE_PRICE_DELIVERY'] = (float)$fields['PRICE_DELIVERY'];
						$fields['CUSTOM_PRICE_DELIVERY'] = "Y";

						unset($fields['PRICE_DELIVERY']);
					}

					$shipmentFields = static::convertDateFields($fields, static::getEntityDateFields($shipment));

					unset($shipmentFields['ALLOW_DELIVERY']);
					unset($shipmentFields['DEDUCTED']);

					if ($fields['CURRENCY'] != $shipmentFields['CURRENCY'])
					{
						$shipmentFields['CURRENCY'] = $fields['CURRENCY'];
					}

					/** @var Sale\Result $r */
					$r = $shipment->setFields(static::clearFields($shipmentFields, static::getShipmentAvailableFields()));
					if ($r->isSuccess())
					{
						static::fillOrderFieldsFromEntity($order, $shipment, $fields, static::getShipmentFieldsToConvert());
					}
					else
					{
						$result->addErrors($r->getErrors());
					}

					if ($shipment !== null)
					{
						DiscountCompatibility::setShipment($order->getId(), $shipment->getId());
					}

					unset($fields['DELIVERY_ID']);
				}
			}

		}
		else
		{
			if (intval($deliveryId) == 0)
			{
				$deliveryId = \Bitrix\Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
			}

			if (intval($deliveryId) > 0)
			{
				/** @var Sale\Shipment $shipment */
				if ($shipment = static::createShipmentFromRequest($shipmentCollection, $deliveryId, $fields))
				{
					if (isset($fields['TRACKING_NUMBER']) && strval($fields['TRACKING_NUMBER']) != '')
					{
						$shipment->setField('TRACKING_NUMBER', $fields['TRACKING_NUMBER']);
					}

					if (isset($fields['DELIVERY_EXTRA_SERVICES']) && is_array($fields['DELIVERY_EXTRA_SERVICES']))
					{
						$shipment->setExtraServices($fields['DELIVERY_EXTRA_SERVICES']);
					}

					if (isset($fields['STORE_ID']) && intval($fields['STORE_ID']) > 0)
					{
						$shipment->setStoreId($fields['STORE_ID']);
					}

					if ($shipment !== null)
					{
						DiscountCompatibility::setShipment($order->getId(), $shipment->getId());
					}

					static::fillOrderFieldsFromEntity($order, $shipment, $fields, static::getShipmentFieldsToConvert());
				}
			}
		}

		if ($basket = $order->getBasket())
		{
			/** @var BasketCompatibility $basketCompatibilityClassName */
			$basketCompatibilityClassName = static::getBasketCompatibilityClassName();

			/** @var Sale\Result $r */
			$r = $basketCompatibilityClassName::syncShipmentCollectionAndBasket($shipmentCollection, $basket);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
				return $result;
			}

		}

		/** @var Sale\Result $r */
		$r = static::syncShipmentCollectionFromRequest($shipmentCollection, $fields);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
			return $result;
		}
		if ($basket)
		{
			/** @var Sale\Shipment $shipment */
			foreach ($shipmentCollection as $shipment)
			{
				if ($shipment->isSystem())
					continue;

				/** @var Sale\ShipmentItemCollection $shipmentItemCollection */
				if (!$shipmentItemCollection = $shipment->getShipmentItemCollection())
				{
					throw new Main\ObjectNotFoundException('Entity "ShipmentItemCollection" not found');
				}

				if (!empty($fields['BARCODE_LIST']) && is_array($fields['BARCODE_LIST']))
				{
					/** @var Sale\Result $r */
					$r = static::fillShipmentItemCollectionFromRequest($shipmentItemCollection, $fields['BARCODE_LIST'], $basket);
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
						return $result;
					}
				}

			}
		}
		return $result;
	}

	/**
	 * @param Sale\Order $order
	 * @param Sale\Internals\CollectableEntity $entity
	 * @param array $requestFields
	 * @param array $allowFields
	 */
	private static function fillOrderFieldsFromEntity(Sale\Order $order, Sale\Internals\CollectableEntity $entity, array $requestFields, array $allowFields)
	{
		$dateFields = static::getEntityDateFields($entity);
		foreach ($allowFields as $checkField)
		{
			$checkOrderField = $order->getField($checkField);

			$isDate = false;

			if (array_key_exists($checkField, $dateFields))
			{
				$isDate = true;
				$checkOrderField = static::convertDateFieldToOldFormat($order->getField($checkField));
			}

			if (!empty($requestFields[$checkField]) && $checkOrderField != trim($requestFields[$checkField]))
			{
				$setValue = $entity->getField($checkField);
				if ($isDate)
				{
					$setValue = static::convertDateField($checkField, $requestFields[$checkField], static::getEntityDateFields($entity));
				}

				if (in_array($checkField, static::getAvailableFields()))
				{
					$order->setFieldNoDemand($checkField, $setValue);
				}
			}
		}
	}

	/**
	 * @internal
	 * @param Sale\ShipmentCollection $shipmentCollection
	 * @param int $deliveryId
	 * @param array $requestFields
	 * @return null|Sale\Shipment
	 */
	public static function createShipmentFromRequest(Sale\ShipmentCollection $shipmentCollection, $deliveryId, array $requestFields)
	{

		$shipment = null;

		if (intval($deliveryId) > 0 && $service = Sale\Delivery\Services\Manager::getObjectById($deliveryId))
		{

			$shipment = $shipmentCollection->createItem($service);

			if ($service->isProfile())
				$serviceName = $service->getNameWithParent();
			else
				$serviceName = $service->getName();
			$shipment->setField('DELIVERY_NAME', $serviceName);


			if (isset($requestFields['DELIVERY_PRICE']) && floatval($requestFields['DELIVERY_PRICE']) > 0)
			{
				$basePriceDelivery = $requestFields['DELIVERY_PRICE'];
				$priceDelivery = $requestFields['PRICE_DELIVERY'];

				if (!empty($requestFields['PRICE_DELIVERY_DIFF']))
				{
					$basePriceDelivery = $priceDelivery + floatval($requestFields['PRICE_DELIVERY_DIFF']);
				}

				$shipment->setFieldNoDemand('BASE_PRICE_DELIVERY', $basePriceDelivery);
				$shipment->setFieldNoDemand('CURRENCY', $requestFields['CURRENCY']);

				$shipment->setFieldNoDemand('PRICE_DELIVERY', $priceDelivery);

				if (isset($requestFields['PRICE_DELIVERY']) && $requestFields['PRICE_DELIVERY'] < $requestFields['DELIVERY_PRICE'])
					$shipment->setFieldNoDemand('PRICE_DELIVERY', $requestFields['PRICE_DELIVERY']);
			}
			elseif (array_key_exists("PRICE_DELIVERY", $requestFields) && floatval($requestFields['PRICE_DELIVERY']) >= 0)
			{
				$shipment->setFieldNoDemand('PRICE_DELIVERY', floatval($requestFields['PRICE_DELIVERY']));
				$shipment->setFieldNoDemand('BASE_PRICE_DELIVERY', floatval($requestFields['PRICE_DELIVERY']));
				$shipment->setFieldNoDemand('CURRENCY', $requestFields['CURRENCY']);
				$shipment->setFieldNoDemand('CUSTOM_PRICE_DELIVERY', "Y");
			}
		}

		return $shipment;
	}

	/**
	 * Request processing for shipments
	 *
	 * @internal
	 *
	 * @param Sale\ShipmentCollection $shipmentCollection		Entity shipment collection.
	 * @param array $fields										An array of request data.
	 *
	 * @return Sale\Result
	 * @throws Main\ObjectNotFoundException
	 */
	public static function syncShipmentCollectionFromRequest(Sale\ShipmentCollection $shipmentCollection, array $fields)
	{
		$result = new Sale\Result();

		$countShipments = count($shipmentCollection);
		$baseShipment = null;

		if ($countShipments <= 2 && $shipmentCollection->isExistsSystemShipment())
		{
			/** @var Sale\Shipment $shipment */
			foreach ($shipmentCollection as $shipment)
			{
				if ($shipment->isSystem())
					continue;

				$baseShipment = $shipment;
			}
		}
		else
		{
			return $result;
		}

		/** @var Sale\Order $order */
		if (!$order = $shipmentCollection->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}


		if ($baseShipment === null)
		{
			return $result;
		}

		if (isset($fields['ALLOW_DELIVERY']) && strval($fields['ALLOW_DELIVERY']) != '')
		{
			if ($baseShipment->getField('ALLOW_DELIVERY') != $fields['ALLOW_DELIVERY'])
			{
				if ($fields['ALLOW_DELIVERY'] == "Y")
				{
					/** @var Sale\Result $r */
					$r = $baseShipment->allowDelivery();
				}
				else
				{
					/** @var Sale\Result $r */
					$r = $baseShipment->disallowDelivery();
				}

				if ($r->isSuccess())
				{
					$order->setFieldNoDemand('ALLOW_DELIVERY', $fields['ALLOW_DELIVERY']);
				}
				else
				{
					$result->addErrors($r->getErrors());
				}
			}
		}


		if (isset($fields['DEDUCTED']) && strval($fields['DEDUCTED']) != '')
		{
			if ($baseShipment->getField('DEDUCTED') != $fields['DEDUCTED'])
			{
				if ($fields['DEDUCTED'] == "Y")
				{
					/** @var Sale\Result $r */
					$r = $baseShipment->tryShip();
				}
				else
				{
					/** @var Sale\Result $r */
					$r = $baseShipment->tryUnship();
				}

				if ($r->isSuccess())
				{
					$order->setFieldNoDemand('DEDUCTED', $fields['DEDUCTED']);
				}
				else
				{
					$result->addErrors($r->getErrors());
				}
			}
		}

		return $result;
	}
	/**
	 * @internal
	 * @param array $fields
	 *
	 * @return Sale\Result
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectNotFoundException
	 */
	public function fillPaymentCollectionFromRequest(array $fields)
	{
		/** @var Sale\Order $order */
		if (!$order = $this->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}

		/** @var Sale\PaymentCollection $paymentCollection */
		if (!$paymentCollection = $order->getPaymentCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "PaymentCollection" not found');
		}

		$result = new Sale\Result();
		$sum = floatval($fields['PRICE']);

		if (isset($fields['SUM_PAID'])
			&& floatval($fields['SUM_PAID']) >= floatval($fields['PRICE']))
		{
			$sum = floatval($fields['SUM_PAID']);
		}

		$isPayFromUserBudget = null;
		$backToUserBudget = null;

		if (array_key_exists('ONLY_FULL_PAY_FROM_ACCOUNT', $fields))
		{
			$isPayFromUserBudget = $fields['ONLY_FULL_PAY_FROM_ACCOUNT'];
		}

		if ($isPayFromUserBudget === null && array_key_exists('PAY_CURRENT_ACCOUNT', $fields) && $fields['PAY_CURRENT_ACCOUNT'] !== null)
		{
			$isPayFromUserBudget = ($fields['PAY_CURRENT_ACCOUNT'] != "Y");
		}

		if (array_key_exists('PAY_FROM_ACCOUNT_BACK', $fields))
		{
			$backToUserBudget = ($fields['PAY_FROM_ACCOUNT_BACK'] == "Y");
		}

		$paySystemId = null;
		$paySystemName = null;

		$userId = $order->getUserId();
		$currency = $order->getCurrency();

		$rawFields = array();
		$paymentInner = null;
		$paymentOuter = null;
		$countPayments = count($paymentCollection);

		$orderPaid = false;

		if ((($countPayments == 0 && $order->getId() == 0)
			|| ($countPayments == 2 && $paymentCollection->isExistsInnerPayment())
			|| ($countPayments == 1 && !$paymentCollection->isExistsInnerPayment())))
		{

			$needSum = $order->getPrice() - $order->getSumPaid();

			if ($countPayments <= 1)
			{

				if ($order->getId() == 0)
				{
					if (!isset($fields["PAY_SYSTEM_ID"]))
						$fields["PAY_SYSTEM_ID"] = static::getDefaultPaySystemId($order->getPersonTypeId());


					/** @var Sale\PaySystem\Service $service */
					if ($service = Sale\PaySystem\Manager::getObjectById($fields["PAY_SYSTEM_ID"]))
					{
						/** @var Sale\Payment $paymentOuter */
						$paymentOuter = $paymentCollection->createItem($service);
						$paymentOuter->setField('DATE_BILL', new Main\Type\DateTime());
						$paymentOuter->setField('SUM', $needSum);
						$paymentOuter->setField('PAY_SYSTEM_NAME', $service->getField('NAME'));
						$order->setFieldNoDemand('PAY_SYSTEM_ID', $fields["PAY_SYSTEM_ID"]);
						$countPayments = 1;
					}
				}
				else
				{
					$paymentOuter = null;

					/** @var Sale\Payment $payment */
					foreach ($paymentCollection as $payment)
					{
						if ($payment->isInner())
							continue;

						$paymentOuter = $payment;
					}

					if ($paymentOuter !== null
						&& ($paymentOuter->getPaymentSystemId() != intval($fields["PAY_SYSTEM_ID"]))
					)
					{
						/** @var Sale\PaySystem\Service $service */
						if ($service = Sale\PaySystem\Manager::getObjectById($fields["PAY_SYSTEM_ID"]))
						{
							/** @var Sale\Payment $paymentOuter */
							$paymentOuter->setField('PAY_SYSTEM_NAME', $service->getField('NAME'));
							$paymentOuter->setField('PAY_SYSTEM_ID', intval($fields["PAY_SYSTEM_ID"]));
							$order->setFieldNoDemand('PAY_SYSTEM_ID', intval($fields["PAY_SYSTEM_ID"]));
						}
					}

				}


			}

			if (isset($fields['PAYED']))
			{
				$paidFlag = null;


				if ($countPayments > 0)
				{
					/** @var Sale\Payment $payment */
					foreach($paymentCollection as $payment)
					{
						if ($paidFlag === null && $payment->isPaid() && $needSum == 0)
						{
							$paidFlag = 'Y';
						}

						if ($payment->isInner())
							continue;

						$paymentOuter = $payment;
					}
				}

				if ($paidFlag === null)
				{
					$paidFlag = 'N';
				}


				if ($paidFlag != $fields['PAYED'])
				{
					if ($fields['PAYED'] == "Y")
					{
						$pay = true;
						$orderPaid = true;

						if ($isPayFromUserBudget !== null)
						{
							if (static::canPayWithUserBudget($needSum, $userId, $currency, $isPayFromUserBudget))
							{
								$userBudget = Sale\Internals\UserBudgetPool::getUserBudget($userId, $currency);

								if ($userBudget >= $needSum)
								{
									$pay = false;
								}
							}
						}

						/** @var Sale\Result $r */
						$r = static::payFromBudget($order, $pay, $isPayFromUserBudget);
						if ($r->isSuccess())
						{
							$needSum = $order->getPrice() - $order->getSumPaid();

							if (!$pay)
							{
								/** @var Sale\Result $r */
								$r = $paymentOuter->setField('SUM', $needSum);
							}

							if (!$r->isSuccess())
							{
								$result->addErrors($r->getErrors());
							}
						}
						else
						{
							$result->addErrors($r->getErrors());
						}
					}
					else
					{
						//
						/** @var Sale\Payment $payment */
						foreach($paymentCollection as $payment)
						{
							if ($payment->isPaid())
							{
								if ($backToUserBudget && $payment->isInner())
								{
									$payment->setReturn('Y');
								}
								else
								{
									$payment->setPaid('N');
								}
							}

							if ($payment->isInner())
							{
								$payment->delete();
							}
							else
							{
								$payment->setField('SUM', $order->getPrice());
							}
						}


					}

					unset($fields['PAYED']);
				}
				elseif ($order->getId() == 0)
				{
					if ($isPayFromUserBudget !== null)
					{
						if (static::canPayWithUserBudget($needSum, $userId, $currency, $isPayFromUserBudget))
						{
							$userBudget = Sale\Internals\UserBudgetPool::getUserBudget($userId, $currency);

							$setSum = $userBudget;

							/** @var Sale\Result $r */
							$r = static::payFromBudget($order, false);
							if ($r->isSuccess())
							{
								$sum -= $setSum;
							}
							else
							{
								$result->addErrors($r->getErrors());
							}
						}
					}
				}

				if ($order->getId() > 0)
				{
					$payment = null;

					/** @var Sale\Payment $paymentItem */
					foreach($paymentCollection as $paymentItem)
					{
						if ($paymentItem->isInner())
						{
							$paymentInner = $paymentItem;
							if ($payment === null && $paymentItem->isPaid())
							{
								$payment = $paymentItem;
							}
						}
						else
						{
							$paymentOuter = $paymentItem;
							if ($payment === null && $paymentItem->isPaid())
							{
								$payment = $paymentItem;
							}
						}
					}

					if ($payment === null)
					{
						if ($paymentOuter !== null)
							$payment = $paymentOuter;
						else
							$payment = $paymentInner;
					}

					if ($payment === null)
					{
						return $result;
					}

					$paymentFields = static::convertDateFields($fields, static::getPaymentDateFields());


					if (!empty($paymentFields['PAY_SYSTEM_ID']) && $paymentFields['PAY_SYSTEM_ID'] != $payment->getPaymentSystemId())
					{
						if ($payment->isInner())
						{
							unset($paymentFields['PAY_SYSTEM_ID']);
						}
						else
						{
							$paySystemId = (int)$paymentFields['PAY_SYSTEM_ID'];

							/** @var Sale\PaySystem\Service $paysystem */
							if ($paysystem = Sale\PaySystem\Manager::getObjectById($paySystemId))
							{
								$paymentFields['PAY_SYSTEM_NAME'] = $paysystem->getField('NAME');
							}
						}
					}


					$paymentFields = static::replaceFields($paymentFields, static::getPaymentReplaceFields());
					$paymentFields = static::clearFields($paymentFields, static::getPaymentAvailableFields());

					/** @var Sale\Result $r */
					$r = $payment->setFields($paymentFields);
					if ($r->isSuccess())
					{

						static::fillOrderFieldsFromEntity($order, $payment, $fields, static::getPaymentFieldsToConvert());
					}
					else
					{
						$result->addErrors($r->getErrors());
					}


					if ($result->isSuccess() && intval($paySystemId) > 0)
					{
						$order->setFieldNoDemand('PAY_SYSTEM_ID', $paySystemId);
					}
				}
			}

			$paymentOuter = null;
			$calcSum = 0;
			/** @var Sale\Payment $payment */
			foreach($paymentCollection as $payment)
			{
				$calcSum += $payment->getSum();
				if ($payment->isInner())
					continue;

				$paymentOuter = $payment;
			}

			if ($paymentOuter && !$paymentOuter->isPaid())
			{
				if ($order->getPrice() != $calcSum)
				{
					$paymentOuter->setField('SUM', $paymentOuter->getSum() + ($order->getPrice() - $calcSum));
				}
			}

			if (!$paymentOuter)
				return $result;

			$fieldsFromOrder = array(
				'PS_STATUS', 'PS_STATUS_CODE', 'PS_STATUS_DESCRIPTION',
				'PS_STATUS_MESSAGE', 'PS_SUM', 'PS_CURRENCY', 'PS_RESPONSE_DATE',
				'PAY_VOUCHER_NUM', 'PAY_VOUCHER_DATE', 'DATE_PAY_BEFORE',
				'DATE_BILL', 'PAY_SYSTEM_NAME', 'PAY_SYSTEM_ID',
				'DATE_PAYED', 'EMP_PAYED_ID', 'CURRENCY'
			);

			foreach ($fieldsFromOrder as $fieldName)
			{
				if (isset($fields[$fieldName]))
				{
					switch ($fieldName)
					{
						case 'DATE_BILL':
						case 'DATE_PAY_BEFORE':
						case 'PS_RESPONSE_DATE':
						if (!isset($fields[$fieldName]) || strval($fields[$fieldName]) == '')
							continue 2;
							$value = new Main\Type\DateTime($fields[$fieldName]);
						break;
						case 'PAY_VOUCHER_DATE':
							if (!isset($fields[$fieldName]) ||  strval($fields[$fieldName]) == '')
								continue 2;
							$value = new Main\Type\Date($fields[$fieldName]);
							break;
						case 'DATE_PAYED':
							if (!isset($fields[$fieldName]) || strval($fields[$fieldName]) == '')
								continue 2;
							$fieldName = 'DATE_PAID';
							$value = new Main\Type\DateTime($fields['DATE_PAYED']);
							break;
						case 'EMP_PAYED_ID':
							$fieldName = 'EMP_PAID_ID';
							$value = $fields['EMP_PAYED_ID'];
							break;
						default:
							$value = $fields[$fieldName];
						break;
					}
					$paymentOuter->setFieldNoDemand($fieldName, $value);
					if ($fieldName === 'PAY_SYSTEM_ID')
					{
						$order->setFieldNoDemand('PAY_SYSTEM_ID', $value);

						/** @var Sale\PaySystem\Service $paysystem */
						if ($paysystem = Sale\PaySystem\Manager::getObjectById($value))
							$paymentOuter->setFieldNoDemand('PAY_SYSTEM_NAME', $paysystem->getField('NAME'));
					}

					if ($fieldName == "DATE_PAID")
						$fieldName = 'DATE_PAYED';

					if (in_array($fieldName, $this->getAvailableFields()))
					{
						$order->setFieldNoDemand($fieldName, $value);
					};
				}
				elseif (isset($fields['~'.$fieldName]))
				{
					$rawFields['~'.$fieldName] = $fields['~'.$fieldName];
				}
			}

			if (isset($fields['PAY_SYSTEM_PRICE']))
				$paymentOuter->setField('PRICE_COD', $fields['PAY_SYSTEM_PRICE']);

			if (!empty($rawFields))
			{
				$this->parseRawFields(static::ENTITY_PAYMENT, $rawFields);
			}
		}


		if (array_key_exists('SUM_PAID', $fields))
		{
			if ($orderPaid)
			{
				if ($fields['SUM_PAID'] == 0)
				{
					$fields['SUM_PAID'] = $order->getPrice();
				}
			}

			if ($fields['SUM_PAID'] >= 0)
			{
				$oldSumPaid = $order->getSumPaid();

				$deltaSumPaid = floatval($fields['SUM_PAID']) - $oldSumPaid;

				if ($deltaSumPaid > 0)
				{
					$paidPayment = false;

					/** @var Sale\Payment $payment */
					foreach ($paymentCollection as $payment)
					{
						if ($payment->isPaid() || $payment->isInner())
							continue;

						if (Sale\PriceMaths::roundPrecision($payment->getSum()) === Sale\PriceMaths::roundPrecision($deltaSumPaid))
						{
							$paidPayment = true;
							/** @var Sale\Result $r */
							$r = $payment->setPaid("Y");
							if (!$r->isSuccess())
							{
								$result->addErrors($r->getErrors());
							}
							break;
						}
					}

					if (!$paidPayment)
					{
						$service = null;
						$paymentSystemId = null;

						if (count($paymentCollection) > 0)
						{
							/** @var Sale\Payment $firstPayment */
							if ($firstPayment = $paymentCollection->rewind())
							{
								$paymentSystemId = $firstPayment->getPaymentSystemId();
								if ($paymentSystemId > 0)
								{
									$service = Sale\PaySystem\Manager::getObjectById($paymentSystemId);
								}
							}
						}

						if (!$service)
						{
							$paymentSystemId = static::getDefaultPaySystemId($order->getPersonTypeId());
							$service = Sale\PaySystem\Manager::getObjectById($paymentSystemId);
						}

						/** @var Sale\PaySystem\Service $service */
						if ($service)
						{
							/** @var Sale\Payment $paymentOuter */
							$payment = $paymentCollection->createItem($service);
							$payment->setField('DATE_BILL', new Main\Type\DateTime());
							$payment->setField('SUM', $deltaSumPaid);
							$payment->setField('PAY_SYSTEM_NAME', $service->getField('NAME'));
							$order->setFieldNoDemand('PAY_SYSTEM_ID', $paymentSystemId);

							/** @var Sale\Result $r */
							$r = $payment->setPaid("Y");
							if (!$r->isSuccess())
							{
								$result->addErrors($r->getErrors());
							}
						}
					}
				}
			}
		}

		return $result;
	}

	private static function getDefaultPaySystemId($personTypeId)
	{
		$personTypeId = intval($personTypeId);

		static $defaultPaySystemId = array();
		if (isset($defaultPaySystemId[$personTypeId]))
			return $defaultPaySystemId[$personTypeId];

		$defaultPaySystemId[$personTypeId] = intval(Main\Config\Option::get('sale', '1C_IMPORT_DEFAULT_PS', 0));
		if (isset($defaultPaySystemId[$personTypeId]) && ($defaultPaySystemId[$personTypeId] > 0))
			return $defaultPaySystemId[$personTypeId];

		if ($personTypeId > 0)
		{
			$dbPaySystem = Sale\PaySystem\Manager::getList(
				array(
					'select' => array("ID"),
					'filter' => array(
						'=ACTIVE' => 'Y',
						'=PERSON_TYPE_ID' => $personTypeId,
						'=ENTITY_REGISTRY_TYPE' => static::getRegistryType()
					),
					'order' => array('SORT'),
					'limit' => 1
				)
			);
			if ($paySystem = $dbPaySystem->fetch())
				$defaultPaySystemId[$personTypeId] = intval($paySystem['ID']);

			if (isset($defaultPaySystemId[$personTypeId]) && ($defaultPaySystemId[$personTypeId] > 0))
				return $defaultPaySystemId[$personTypeId];
		}

		$dbPaySystem = Sale\PaySystem\Manager::getList(
			array(
				'select' => array("ID"),
				'filter' => array(
					'=ACTIVE' => 'Y',
					'=ENTITY_REGISTRY_TYPE' => static::getRegistryType()
				),
				'order' => array('SORT'),
				'limit' => 1
			)
		);
		if ($paySystem = $dbPaySystem->fetch())
			$defaultPaySystemId[$personTypeId] = intval($paySystem['ID']);

		return $defaultPaySystemId[$personTypeId];
	}

	/**
	 * @internal
	 *
	 * @param Sale\Tax $tax
	 * @param array $fields
	 * @return Sale\Result
	 */
	public function fillTaxFromRequest(Sale\Tax $tax, array $fields)
	{
		if (!empty($fields['COUNT_DELIVERY_TAX']))
		{
			$tax->setDeliveryCalculate(($fields['COUNT_DELIVERY_TAX'] == "Y"));
		}

		if (!empty($fields['TAX_LIST']) && is_array($fields['TAX_LIST']))
		{
			$tax->initTaxList($fields['TAX_LIST']);
		}
		elseif (!empty($tax->getTaxList()))
		{
			/** @var Sale\Order $order */
			if ($order = $this->getOrder())
			{
				$order->refreshVat();
				if ($tax = $order->getTax())
				{
					$tax->resetTaxList();
				}
			}
		}

		if (array_key_exists('TAX_VALUE', $fields))
		{
			$order = $this->getOrder();
			$order->setFieldNoDemand('TAX_VALUE', floatval($fields['TAX_VALUE']));
		}

		return new Sale\Result();
	}

	/**
	 * @param $id
	 * @param bool $value
	 * @param array $storeData
	 * @return Sale\Result
	 * @throws Main\ObjectNotFoundException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	public static function shipment($id, $value, array $storeData = array() )
	{
		global $USER;

		$result = new Sale\Result();

		$registry = Sale\Registry::getInstance(static::getRegistryType());

		/** @var Sale\Order $orderClassName */
		$orderClassName = $registry->getOrderClassName();
		if ($order = $orderClassName::load($id))
		{
			/** @var Sale\Basket $basket */
			if (!$basket = $order->getBasket())
			{
				throw new Main\ObjectNotFoundException('Entity "Basket" not found');
			}

			/** @var Sale\ShipmentCollection $shipmentCollection */
			if(!$shipmentCollection = $order->getShipmentCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}

			/** @var Sale\Shipment $shipment */
			foreach ($shipmentCollection as $shipment)
			{
				if ($shipment->isSystem())
					continue;

				/** @var Sale\ShipmentItemCollection $shipmentItemCollection */
				if (!$shipmentItemCollection = $shipment->getShipmentItemCollection())
				{
					throw new Main\ObjectNotFoundException('Entity "ShipmentItemCollection" not found');
				}

				/** @var Sale\Result $r */
				$r = static::fillShipmentItemCollectionFromRequest($shipmentItemCollection, $storeData, $basket);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
					return $result;
				}

				/** @var Sale\Result $r */
				$r = $shipment->setField('DEDUCTED', $value === true ? 'Y' : 'N');
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
					continue;
				}
			}
		}


		/** @var Sale\Result $r */
		$r = $order->save();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @internal
	 *
	 * @param Sale\ShipmentItemCollection $shipmentItemCollection
	 * @param array $storeData
	 * @param Sale\Basket $basket
	 *
	 * @return Sale\Result
	 * @throws Main\ObjectNotFoundException
	 */
	public static function fillShipmentItemCollectionFromRequest(Sale\ShipmentItemCollection $shipmentItemCollection, array $storeData, Sale\Basket $basket = null)
	{
		$result = new Sale\Result();


		if ($basket === null)
		{

			/** @var Sale\Shipment $shipment */
			if (!$shipment = $shipmentItemCollection->getShipment())
			{
				throw new Main\ObjectNotFoundException('Entity "Shipment" not found');
			}


			/** @var Sale\ShipmentCollection $shipmentCollection */
			if(!$shipmentCollection = $shipment->getCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}

			/** @var Sale\Order $order */
			if (!$order = $shipmentCollection->getOrder())
			{
				throw new Main\ObjectNotFoundException('Entity "Order" not found');
			}

			/** @var Sale\Basket $basket */
			if (!$basket = $order->getBasket())
			{
				throw new Main\ObjectNotFoundException('Entity "Basket" not found');
			}
		}

		/** @var Sale\ShipmentItem $shipmentItem */
		foreach ($shipmentItemCollection as $shipmentItem)
		{
			/** @var Sale\BasketItem $basketItem */
			if (($basketItem = $shipmentItem->getBasketItem()) && $basketItem->getId() == 0)
			{
				continue;
			}
			/** @var Sale\ShipmentItemStoreCollection $shipmentItemStoreCollection */
			if (!$shipmentItemStoreCollection = $shipmentItem->getShipmentItemStoreCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentItemStoreCollection" not found');
			}

			$barcodeList = array();
			/** @var Sale\ShipmentItemStore $shipmentItemStore */
			foreach ($shipmentItemStoreCollection as $shipmentItemStore)
			{
				$storeId = $shipmentItemStore->getField('STORE_ID');
				$basketId = $shipmentItemStore->getField('BASKET_ID');
				$barcodeList[$basketId][$storeId][] = array(
					'ID' => $shipmentItemStore->getId(),
					'QUANTITY' => $shipmentItemStore->getQuantity(),
					'BARCODE' => $shipmentItemStore->getBarcode(),
				);
			}

			$baseBarcode = null;

			foreach($storeData as $basketId => $barcodeDataList)
			{
				if ((intval($basketId) != $basketId)
					|| ($basketItem->getId() != $basketId))
					continue;

				foreach ($barcodeDataList as $barcodeData)
				{

					/** @var Sale\BasketItem $basketItem */
					if (!$basketItem = $basket->getItemById($basketId))
					{
						throw new Main\ObjectNotFoundException('Entity "BasketItem" not found');
					}


					$saveBarcodeList = array();

					if ($basketItem->isBarcodeMulti() && is_array($barcodeData['BARCODE']))
					{
						$barcodeQuantity = $barcodeData['QUANTITY'] / count($barcodeData['BARCODE']);

						foreach ($barcodeData['BARCODE'] as $barcodeId => $barcodeValue)
						{
							$barcodeFields = array(
								'QUANTITY' => $barcodeQuantity,
								'BARCODE' => $barcodeValue,
							);

							if (intval($barcodeId) > 0)
							{
								$barcodeFields['ID'] = intval($barcodeId);
							}

							$saveBarcodeList[] = $barcodeFields;
						}
					}
					else
					{
						if (strval($barcodeData['BARCODE']) != '')
						{
							$baseBarcode = trim($barcodeData['BARCODE']);
						}
						elseif (!empty($baseBarcode))
						{
							$barcodeData['BARCODE'] = $baseBarcode;
						}

						$barcodeFields = array(
							'QUANTITY' => $barcodeData['QUANTITY'],
							'BARCODE' => $barcodeData['BARCODE'],
						);

						if (!empty($barcodeList[$basketId]) && !empty($barcodeList[$basketId][$barcodeData['STORE_ID']]))
						{

							foreach ($barcodeList[$basketId][$barcodeData['STORE_ID']] as $existBarcodeData)
							{
								if ($existBarcodeData['BARCODE'] == $barcodeData['BARCODE'] && !empty($existBarcodeData['ID']))
								{
									if ($shipmentItemStoreCollection->getItemById($existBarcodeData['ID']))
									{
										$barcodeFields['ID'] = $existBarcodeData['ID'];
									}
								}
							}
						}

						$saveBarcodeList = array(
							$barcodeFields
						);
					}

					foreach ($saveBarcodeList as $saveBarcodeData)
					{
						$barcodeFields = array(
							'QUANTITY' => $saveBarcodeData['QUANTITY'],
							'BARCODE' => $saveBarcodeData['BARCODE'],
						);

						/** @var Sale\ShipmentItemStore $shipmentItemStore */
						$shipmentItemStore = $shipmentItemStoreCollection->getItemByBarcode($saveBarcodeData['BARCODE']);

						if (!$shipmentItemStore)
						{
							$barcodeFields['STORE_ID'] = intval($barcodeData['STORE_ID']);
							$shipmentItemStore = $shipmentItemStoreCollection->createItem($basketItem);
						}

						/** @var Sale\Result $r */
						$r = $shipmentItemStore->setFields($barcodeFields);
						if (!$r->isSuccess())
						{
							$result->addErrors($r->getErrors());
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param bool $value
	 * @return Sale\Result
	 * @throws Main\ObjectNotFoundException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	public static function allowDelivery($id, $value)
	{
		$result = new Sale\Result();

		$registry = Sale\Registry::getInstance(static::getRegistryType());

		/** @var Sale\Order $orderClassName */
		$orderClassName = $registry->getOrderClassName();
		if ($order = $orderClassName::load($id))
		{
			/** @var Sale\ShipmentCollection $shipmentCollection */
			if(!$shipmentCollection = $order->getShipmentCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}

			/** @var Sale\Shipment $shipment */
			foreach ($shipmentCollection as $shipment)
			{
				if ($shipment->isSystem())
					continue;

				/** @var Sale\Result $r */
				$r = $shipment->setField('ALLOW_DELIVERY', $value === true ? 'Y' : 'N');
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
					return $result;
				}
			}
		}


		/** @var Sale\Result $r */
		$r = $order->save();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @param array $fields
	 *
	 * @return Sale\Result
	 */
	public static function add(array $fields)
	{
		return static::modifyOrder(static::ORDER_COMPAT_ACTION_ADD, $fields);
	}

	/**
	 * @param $id
	 * @param array $fields
	 * @param bool $dateUpdate
	 *
	 * @return Sale\Result
	 */
	public static function update($id, array $fields, $dateUpdate = false)
	{
		$result = new Sale\Result();

		$id = (int)$id;

		if ($id <= 0)
		{
			$result->addError(new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_COMPATIBLE_ORDER_ID_NOT_FOUND'), 'SALE_COMPATIBLE_ORDER_ID_NOT_FOUND'));
			return $result;
		}

		$fields['ID'] = $id;

		if (!$dateUpdate)
		{
			$fields['DATE_UPDATE'] = null;
		}

		/** @var Sale\Result $r */
		return static::modifyOrder(static::ORDER_COMPAT_ACTION_UPDATE, $fields);
	}

	/**
	 * @internal
	 * @param string $action
	 * @param array $fields
	 *
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ObjectNotFoundException
	 */
	public static function modifyOrder($action, array $fields)
	{
		$result = new Sale\Result();

		try
		{
			$adminSection = (defined('ADMIN_SECTION') && ADMIN_SECTION === true);

			/** @var Sale\Compatible\OrderCompatibility $orderCompatibility */
			$orderCompatibility = static::create($fields);

			/** @var Sale\Order $order */
			$order = $orderCompatibility->getOrder();

			/** @var Sale\PropertyValueCollection $propCollection */
			$propCollection = $order->getPropertyCollection();

			if (!empty($fields['ORDER_PROP']) && is_array($fields['ORDER_PROP']))
			{
				$fields['PROPERTIES'] = $fields['ORDER_PROP'];
			}

			if (!isset($fields['PROPERTIES']) || !is_array($fields['PROPERTIES']))
			{
				$fields['PROPERTIES'] = array();
			}

			// compatibility to prevent setting default values for empty properties
			/** @var Sale\PropertyValue $propertyValue */
			foreach ($propCollection as $propertyValue)
			{
				$propertyFields = $propertyValue->getProperty();
				$key = isset($propertyFields['ID']) ? $propertyFields['ID'] : 'n'.$propertyValue->getId();

				if ($propertyValue->getId() <=0
					&& !array_key_exists($key, $fields['PROPERTIES'])
				)
				{
					$propertyValue->delete();
				}
			}

			/** @var Sale\Result $r */
			$r = $propCollection->setValuesFromPost($fields, $_FILES);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
				return $result;
			}


			$oldPrice = $order->getPrice();

//			$isStartField = $order->isStartField();


			/** @var Sale\Basket $basket */
			$basket = $order->getBasket();

			if (!$basket && $action == static::ORDER_COMPAT_ACTION_SAVE)
			{
				$fUserId = null;


				if (!empty($fields['BASKET_ITEMS']) && is_array($fields['BASKET_ITEMS']))
				{
					foreach ($fields['BASKET_ITEMS'] as $basketItemData)
					{
						if (!empty($basketItemData['FUSER_ID']) && intval($basketItemData['FUSER_ID']) > 0)
						{
							$fUserId = intval($basketItemData['FUSER_ID']);
							break;
						}
					}
				}


				if (intval($fUserId) <= 0 && !$adminSection)
				{
					$fUserId = static::getDefaultFuserId();
				}

				$userId = $order->getUserId();
				if ($userId > 0)
				{
					$fUserIdByUserId = Sale\Fuser::getIdByUserId($userId);
					if (intval($fUserId) > 0 && intval($fUserIdByUserId) > 0
						&& intval($fUserId) != intval($fUserIdByUserId))
					{
						// TODO: ... [SALE_BASKET_001] - the call of old method of the basket
						\CSaleBasket::TransferBasket($fUserId, $fUserIdByUserId);
					}

					$fUserId = $fUserIdByUserId;
				}

				if (intval($fUserId) <= 0)
				{
					$result->addError(new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_COMPATIBLE_ORDER_FUSERID_NOT_FOUND'), "SALE_COMPATIBLE_ORDER_FUSERID_NOT_FOUND"));
					return $result;
				}


				$registry = Sale\Registry::getInstance(static::getRegistryType());
				/** @var Sale\Basket $basketClassName */
				$basketClassName = $registry->getBasketClassName();

				if (!$adminSection)
				{
					$siteId = !empty($fields["SITE_ID"]) ? $fields["SITE_ID"] : (!empty($fields["LID"]) ? $fields['LID']: null);
					$allBasket = $basketClassName::loadItemsForFUser($fUserId, $siteId);

					if ($allBasket)
					{
						$basket = $allBasket->getOrderableItems();
					}
				}


				if (!$basket)
				{
					$basket = $basketClassName::create($order->getSiteId());
					$basket->setFUserId($fUserId);
				}
			}

			$isStartField = $order->isStartField(true);


			if ($basket)
			{
				/** @var BasketCompatibility $basketCompatibilityClassName */
				$basketCompatibilityClassName = static::getBasketCompatibilityClassName();
				$basketCompatibility = $basketCompatibilityClassName::create($orderCompatibility);

				/** @var Sale\Result $r */
				$r = $basketCompatibility->fillBasket($basket, $fields);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
					return $result;
				}

				if ($action == static::ORDER_COMPAT_ACTION_SAVE && $order->getId() == 0 && count($basket) > 0)
				{
					$order->setMathActionOnly(true);
					$order->setBasket($basket);
					$order->setMathActionOnly(false);
				}
				
				if ($orderCompatibility->isExistPrice() && $oldPrice == $order->getPrice())
				{
					$order->setFieldNoDemand('PRICE', $orderCompatibility->externalPrice);
				}

			}

			/** @var Sale\Result $r */
			$r = $orderCompatibility->fillTaxFromRequest($order->getTax(), $fields);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
				return $result;
			}

			/** @var Sale\Result $r */
			$r = $orderCompatibility->fillShipmentCollectionFromRequest( $order->getShipmentCollection(), $fields);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
				return $result;
			}

			if ($isStartField)
			{
				$hasMeaningfulFields = $order->hasMeaningfulField();

				/** @var Sale\Result $r */
				$r = $order->doFinalAction($hasMeaningfulFields);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
					return $result;
				}
			}

			$order->setMathActionOnly(false);

			/** @var Sale\Result $r */
			$r = $orderCompatibility->fillPaymentCollectionFromRequest($fields);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
				return $result;
			}

			/** @var Sale\Result $r */
			$r = static::fillOrderFromRequest($order, $fields);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
				return $result;
			}

		}
		catch(Sale\UserMessageException $e)
		{
			$result->addError(new Sale\ResultError($e->getMessage(), $e->getCode()));
			return $result;
		}

		static::transformationLocation($order);

		/** @var Sale\Result $r */
		$r = $order->save();
		if ($r->isSuccess())
		{
			if ($orderData = $r->getData())
				$result->setData($orderData);

			if ($orderId = $r->getId())
				$result->setId($orderId);

			/** @var Sale\Result $r */
			$r = $orderCompatibility->saveRawFields($order, static::ENTITY_ORDER);
		}

		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}
		else
		{
			$oldFields = static::convertDateFieldsToOldFormat($order->getFieldValues());
			$oldFields = $oldFields + $orderCompatibility->rawFields;

			/** @var Sale\PaymentCollection $paymentCollection */
			if ($paymentCollection = $order->getPaymentCollection())
			{
				/** @var Sale\Payment $payment */
				foreach ($paymentCollection as $payment)
				{
					if ($payment->getId() <= 0)
					{
						continue;
					}

					/** @var Sale\Result $r */
					$r = $orderCompatibility->saveRawFields($payment, static::ENTITY_PAYMENT);
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
					}
				}
			}
			
			$result->setData(array(
								'OLD_FIELDS' => $oldFields
							));
		}

		return $result;
	}

	/**
	 * @param $orderId
	 * @param $value
	 * @return Sale\Result
	 * @throws Main\ObjectNotFoundException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function reserve($orderId, $value)
	{
		$result = new Sale\Result();

		$registry = Sale\Registry::getInstance(static::getRegistryType());

		/** @var Sale\Order $orderClassName */
		$orderClassName = $registry->getOrderClassName();
		if (!$order = $orderClassName::load($orderId))
		{
			$result->addError( new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_COMPATIBLE_ORDER_NOT_FOUND'), 'SALE_COMPATIBLE_ORDER_NOT_FOUND') );
			return $result;
		}

		/** @var Sale\ShipmentCollection $shipmentCollection */
		if (!$shipmentCollection = $order->getShipmentCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
		}

		/** @var Sale\Shipment $shipment */
		foreach ($shipmentCollection as $shipment)
		{
			if ($shipment->isSystem())
				continue;

			if ($value == "Y")
			{
				/** @var Sale\Result $r */
				$r = $shipment->tryReserve();
				if (!$r->isSuccess())
				{
					$registry = Sale\Registry::getInstance(static::getRegistryType());

					/** @var Sale\EntityMarker $entityMarkerClassName */
					$entityMarkerClassName = $registry->getEntityMarkerClassName();
					$entityMarkerClassName::addMarker($order, $shipment, $r);
					if (!$shipment->isSystem())
					{
						$shipment->setField('MARKED', 'Y');
					}

					$result->addErrors($r->getErrors());
				}
			}
			else
			{
				if (!$shipment->isShipped())
				{
					/** @var Sale\Result $r */
					$r = $shipment->tryUnreserve();
					if (!$r->isSuccess())
					{
						$registry = Sale\Registry::getInstance(static::getRegistryType());

						/** @var Sale\EntityMarker $entityMarkerClassName */
						$entityMarkerClassName = $registry->getEntityMarkerClassName();
						$entityMarkerClassName::addMarker($order, $shipment, $r);
						if (!$shipment->isSystem())
						{
							$shipment->setField('MARKED', 'Y');
						}
						$result->addErrors($r->getErrors());
					}
				}
			}
		}

		/** @var Sale\Result $r */
		$r = $order->save();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @param $orderId
	 * @param array $values
	 * @param bool $withdraw
	 * @param bool $pay
	 * @return Sale\Result
	 * @throws Main\ObjectNotFoundException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function pay($orderId, array $values, $withdraw = false, $pay = false)
	{
		$result = new Sale\Result();

		$paid = null;
		if (isset($values['PAYED']) && strval($values['PAYED']) != '')
		{
			$values['PAID'] = $values['PAYED'];
		}

		if (intval($orderId) <= 0)
		{
			$result->addError( new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_COMPATIBLE_ORDER_ID_NOT_FOUND'), 'SALE_COMPATIBLE_ORDER_ID_NOT_FOUND') );
			return $result;
		}

		$registry = Sale\Registry::getInstance(static::getRegistryType());
		/** @var Sale\Order $orderClassName */
		$orderClassName = $registry->getOrderClassName();
		if (!$order = $orderClassName::load($orderId))
		{
			$result->addError( new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_COMPATIBLE_ORDER_NOT_FOUND'), 'SALE_COMPATIBLE_ORDER_NOT_FOUND') );
			return $result;
		}

		if ($order->isCanceled())
		{
			/** @var Sale\Result $r */
			$r = $order->setField('CANCELED', 'N');
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
				return $result;
			}
		}

		/** @var Sale\PaymentCollection $paymentCollection */
		if (!$paymentCollection = $order->getPaymentCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "PaymentCollection" not found');
		}

		$paidFormUserBudget = false;

		if ($withdraw)
		{
			/** @var Sale\Result $r */
			$r = static::payFromBudget($order, $pay);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
			else
			{
				$payBudgetData = $r->getData();
				if (array_key_exists('PAID_FROM_BUDGET', $payBudgetData))
				{
					$paidFormUserBudget = $payBudgetData['PAID_FROM_BUDGET'];
				}
			}

		}


		if (!$paidFormUserBudget)
		{
			/** @var Sale\Payment $payment */
			foreach ($paymentCollection as $payment)
			{
				if (empty($fields))
				{
					if (isset($values['=DATE_PAYED']))
					{
						$values['DATE_PAID'] = $values['=DATE_PAYED'];
						unset($values['=DATE_PAYED']);
					}

					$values = static::convertDateFields($values, static::getPaymentDateFields());
					$fields = static::clearFields($values, $payment->getAvailableFields());

				}

				if ($values['PAID'] == "N" && !$payment->isPaid())
					continue;

				if ($withdraw && $values['PAID'] == "N" && $payment->isInner())
				{
					/** @var Sale\Result $r */
					$r = $payment->setReturn('Y');
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
					}
				}
				else
				{
					$oldPaid = $payment->isPaid();
					/** @var Sale\Result $r */
					$r = $payment->setPaid($values['PAID']);
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
					}

					if ($payment->isInner() && !$oldPaid && $payment->isPaid())
					{
						Sale\Internals\UserBudgetPool::addPoolItem($order, ( $payment->getSum() * -1 ), Sale\Internals\UserBudgetPool::BUDGET_TYPE_ORDER_UNPAY, $payment);
						if (!$r->isSuccess())
						{
							$result->addErrors($r->getErrors());
						}
					}

				}

				if (isset($fields['PAID']))
				{
					unset($fields['PAID']);
				}

				$r = $payment->setFields($fields);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}
		}

		if (!$result->isSuccess())
		{
			return $result;
		}

		/** @var Sale\Result $r */
		$r = $order->save();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}
		return $result;
	}

	/**
	 * Paid from internal account
	 *
	 * @param Sale\Order $order 	Entity of the order.
	 * @param bool $pay 			Flag making donations to internal account.
	 * @param null $paidFormUserBudget
	 * @return Sale\Result
	 * @throws Main\ObjectNotFoundException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\InvalidOperationException
	 */
	public static function payFromBudget(Sale\Order $order, $pay, $paidFormUserBudget = null)
	{
		$result = new Sale\Result();

		/** @var Sale\Payment|null $paymentOuter */
		$paymentInner = null;

		/** @var Sale\Payment|null $paymentOuter */
		$paymentOuter = null;

		/** @var Sale\PaymentCollection $paymentCollection */
		if (!$paymentCollection = $order->getPaymentCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "PaymentCollection" not found');
		}

		if (count($paymentCollection) > 2)
			return $result;

		$needSum = $order->getPrice() - $order->getSumPaid();

		if ($needSum > 0)
		{

			/** @var Sale\Payment $payment */
			foreach ($paymentCollection as $payment)
			{
				if (!$payment->isInner())
				{
					$paymentOuter = $payment;
					break;
				}
			}

			if (!$pay || ($pay && $paidFormUserBudget === false))
			{
				/** @var Sale\Payment $paymentInner */
				$paymentInner = $paymentCollection->getInnerPayment();
				if (!$paymentInner)
				{
					$paymentInner = $paymentCollection->createInnerPayment();
				}

				if (!$paymentInner)
				{
					throw new Main\ObjectNotFoundException('Entity inner "Payment" not found');
				}

				$userBudget = Sale\Internals\UserBudgetPool::getUserBudget($order->getUserId(), $order->getCurrency());

				$setSum = $userBudget;
				if ($userBudget >= $needSum)
				{
					$setSum = $needSum;
				}

				if ($paymentInner->getId() == 0)
				{
					$paymentInnerFields = array(
						'SUM' => $setSum,
						'CURRENCY' => $order->getCurrency(),
						'DATE_BILL' => new Main\Type\DateTime(),
					);

					$r = $paymentInner->setFields($paymentInnerFields);
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
					}
				}
				else
				{
					if ($paymentInner->getSum() < $needSum)
					{
						$paymentInner->setField('SUM', $needSum - $paymentInner->getSum());
					}
				}

				if ($pay && $paidFormUserBudget === false)
				{
					$paymentOuter->setField('SUM', $needSum - $setSum);
				}

				$payment = $paymentInner;

			}
			else
			{
				$payment = $paymentOuter;
			}

			if ($pay)
			{

				if ($payment === null)
				{
					$paySystemId = static::getDefaultPaySystemId($order->getPersonTypeId());

					/** @var Sale\PaySystem\Service $paySystem */
					if ($paySystem = Sale\PaySystem\Manager::getObjectById($paySystemId))
					{
						$registry = Sale\Registry::getInstance(static::getRegistryType());
						/** @var Sale\Payment $paymentClassName */
						$paymentClassName = $registry->getPaymentClassName();

						$payment = $paymentClassName::create($paymentCollection, $paySystem);
						$payment->setField('SUM', $needSum);
						$payment->setField('DATE_BILL', new Main\Type\DateTime());
						$paymentCollection->addItem($payment);
					}
				}

				$operationPayment = $payment;
				if ($paidFormUserBudget === false)
				{
					$operationPayment = $paymentOuter;
				}

				$service = Sale\PaySystem\Manager::getObjectById($operationPayment->getPaymentSystemId());
				if ($service)
				{
					$r = $service->creditNoDemand($operationPayment);
					if (!$r->isSuccess())
						$result->addErrors($r->getErrors());
				}
				else
				{
					$result->addError(new Main\Entity\EntityError(Main\Localization\Loc::getMessage('SALE_COMPATIBLE_PAYSYSTEM_NOT_FOUND')));
					return $result;
				}
			}

			if ($payment->isReturn() && $payment->isInner())
			{
				$r = $payment->setPaid('Y');
			}
			else
			{
				/** @var Sale\Result $r */
				$r = $payment->setPaid('Y');

				if ($r->isSuccess())
				{
					if ($pay)
					{
						$operationPayment = $payment;
						if ($paidFormUserBudget === false)
						{
							$operationPayment = $paymentOuter;

							/** @var Sale\Result $resultPayment */
							$resultPayment = $paymentOuter->setPaid('Y');
							if (!$resultPayment->isSuccess())
							{
								$result->addErrors($resultPayment->getErrors());
							}
						}

						$service = Sale\PaySystem\Manager::getObjectById($operationPayment->getPaymentSystemId());
						if ($service)
						{
							$r = $service->creditNoDemand($operationPayment);
							if (!$r->isSuccess())
								$result->addErrors($r->getErrors());
						}
						else
						{
							$result->addError(new Main\Entity\EntityError(Main\Localization\Loc::getMessage('SALE_COMPATIBLE_PAYSYSTEM_NOT_FOUND')));
							return $result;
						}
					}

				}
				else
				{
					$result->addErrors($r->getErrors());
				}
			}

			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}

		}

		$result->setData(array('PAID_FROM_BUDGET' => $paidFormUserBudget));

		return $result;
	}

	/**
	 * Cancel order
	 *
	 * @param int $orderId		Order ID.
	 * @param string $value		The cancel key(Y/N).
	 * @param bool|string $comment	cancel reason
	 * @return Sale\Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function cancel($orderId, $value, $comment = false)
	{
		$result = new Sale\Result();

		if (intval($orderId) <= 0)
		{
			$result->addError( new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_COMPATIBLE_ORDER_ID_NOT_FOUND'), 'SALE_COMPATIBLE_ORDER_ID_NOT_FOUND') );
			return $result;
		}

		$registry = Sale\Registry::getInstance(static::getRegistryType());
		/** @var Sale\Order $orderClassName */
		$orderClassName = $registry->getOrderClassName();
		if (!$order = $orderClassName::load($orderId))
		{
			$result->addError( new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_COMPATIBLE_ORDER_NOT_FOUND'), 'SALE_COMPATIBLE_ORDER_NOT_FOUND') );
			return $result;
		}

		if ($value === 'N')
		{
			if ($order->isCanceled())
			{
				$r = $order->setField('CANCELED', 'N');
				if (!$r->isSuccess())
				{
					return $result->addErrors($r->getErrors());
				}

				$r = $order->save();
				if (!$r->isSuccess())
				{
					return $result->addErrors($r->getErrors());
				}
			}

			return $result;
		}

		if ($order->isCanceled())
		{
			return $result;
		}

		$paymentCollection = $order->getPaymentCollection();
		/** @var Sale\Payment $payment */
		foreach ($paymentCollection as $payment)
		{
			if ($payment->isPaid())
				$payment->setReturn('Y');
		}

		$shipmentCollection = $order->getShipmentCollection();
		/** @var Sale\Shipment $shipment */
		foreach ($shipmentCollection as $shipment)
		{
			if ($shipment->isSystem())
				continue;

			if ($shipment->isShipped())
				$shipment->setField('DEDUCTED', 'N');

			if ($shipment->isAllowDelivery())
				$shipment->disallowDelivery();
		}

		$r = $order->setField('CANCELED', 'Y');
		if (!$r->isSuccess())
		{
			return $result->addErrors($r->getErrors());
		}

		if (!empty($comment) && strval($comment) != '')
		{
			$r = $order->setField('REASON_CANCELED', $comment);
			if (!$r->isSuccess())
			{
				return $result->addErrors($r->getErrors());
			}
		}

		return $order->save();
	}

	/**
	 * Delete the order
	 * @param int $id		Order ID.
	 * @return Sale\Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function delete($id)
	{
		$result = new Sale\Result();

		if (intval($id) <= 0)
		{
			$result->addError( new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_COMPATIBLE_ORDER_ID_NOT_FOUND'), 'SALE_COMPATIBLE_ORDER_ID_NOT_FOUND') );
			return $result;
		}

		$registry = Sale\Registry::getInstance(static::getRegistryType());
		/** @var Sale\Order $orderClassName */
		$orderClassName = $registry->getOrderClassName();
		if (!$order = $orderClassName::load($id))
		{
			$result->addError(new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_ORDER_ENTITY_NOT_FOUND'), 'SALE_ORDER_ENTITY_NOT_FOUND'));
			return $result;
		}

		/** @var Sale\Payment $payment */
		foreach ($order->getPaymentCollection() as $payment)
		{
			if ($payment->isPaid())
			{
				$payment->setPaid('N');
			}
		}

		/** @var Sale\Shipment $shipment */
		foreach ($order->getShipmentCollection() as $shipment)
		{
			if ($shipment->isShipped())
			{
				$shipment->setField('DEDUCTED', 'N');
			}
		}

		$r = $order->save();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
			return $result;
		}

		try
		{
			$r = $orderClassName::delete($id);
		}
		catch (\Exception $exception)
		{
			$r = $orderClassName::deleteNoDemand($id);
		}

		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @param float $needSum
	 * @param int $userId
	 * @param string $currency
	 * @param bool $fullPay
	 * @return bool
	 */
	public static function canPayWithUserBudget($needSum, $userId, $currency, $fullPay = true)
	{
		$budget = Sale\Internals\UserBudgetPool::getUserBudget($userId, $currency);
		if ($fullPay === false && $budget > 0)
			return true;

		if ($fullPay === true && $budget >= $needSum)
			return true;

		return false;
	}


	/**
	 * @param Sale\ShipmentCollection $shipmentCollection
	 *
	 * @return Sale\Shipment
	 * @throws Main\ArgumentNullException
	 */
	public static function createShipmentFromShipmentSystem(Sale\ShipmentCollection $shipmentCollection)
	{

		$shipment = null;

		/** @var Sale\Shipment $systemShipment */
		$systemShipment = $shipmentCollection->getSystemShipment();

		if ($systemShipment->getDeliveryId() > 0)
		{
			/** @var Sale\Shipment $shipment */
			$shipment = static::getShipmentByDeliveryId($shipmentCollection, $systemShipment->getDeliveryId());

			if (!$shipment)
			{
				if ($service = Sale\Delivery\Services\Manager::getObjectById($systemShipment->getDeliveryId()))
				{
					/** @var Sale\Shipment $shipment */
					$shipment = $shipmentCollection->createItem($service);
					$shipment->setField('DELIVERY_NAME', $service->getName());
				}
			}
		}

		return $shipment;
	}


	/**
	 * @internal
	 *
	 * @param Sale\ShipmentCollection $shipmentCollection
	 * @param $deliveryId
	 * @return Sale\Shipment|bool
	 */
	public static function getShipmentByDeliveryId(Sale\ShipmentCollection $shipmentCollection, $deliveryId)
	{
		/** @var Sale\Shipment $shipment */
		foreach($shipmentCollection as $shipment)
		{
			if ($shipment->isSystem())
				continue;

			if ($shipment->getDeliveryId() == $deliveryId)
			{
				return $shipment;
			}
		}

		return false;
	}

	/**
	 * @param Sale\Order $order
	 */
	protected static function transformationLocation(Sale\Order $order)
	{
		/** @var Sale\PropertyValueCollection $propertyCollection */
		if ($propertyCollection = $order->getPropertyCollection())
		{
			/** @var Sale\PropertyValue $valueItem */
			foreach ($propertyCollection as $valueItem)
			{
				if ($valueItem->getValue() != '')
				{
					$setValue = $valueItem->getValue();

					$prop = $valueItem->getPropertyObject();
					if ($prop->getType() == 'LOCATION')
					{
						$setValue = \CSaleLocation::tryTranslateIDToCode($setValue);
					}

					$valueItem->setField('VALUE', $setValue);
				}

			}
		}

	}

	/**
	 * @param $id
	 *
	 * @return CDBResult|int
	 * @throws Main\ArgumentNullException
	 */
	public static function getById($id)
	{
		$compatibility = new static();

		$select = array('*');

		$registry = Sale\Registry::getInstance(static::getRegistryType());
		/** @var Sale\Order $orderClassName */
		$orderClassName = $registry->getOrderClassName();

		if ($order = $orderClassName::load($id))
		{
			/** @var Sale\PaymentCollection $paymentCollection */
			if ($paymentCollection = $order->getPaymentCollection())
			{
				if (count($paymentCollection) == 1)
				{
					$select = array_merge($select, array_keys(static::getAliasPaymentFields()));
				}
			}

			/** @var Sale\ShipmentCollection $shipmentCollection */
			if ($shipmentCollection = $order->getShipmentCollection())
			{
				if (count($shipmentCollection) == 1
					|| (count($shipmentCollection) == 2 && $shipmentCollection->isExistsSystemShipment()))
				{
					$select = array_merge($select, array_keys(static::getAliasShipmentFields()));
				}
			}
		}

		return static::setGetListParameters($compatibility, array(), array("ID" => $id), null, array(), $select);
	}

	/**
	 * @internal 
	 * @return array
	 */
	public static function getAliasFields()
	{
		$fields = array(
			'NAME_SEARCH' => array(
				'NAME' => 'USER.NAME',
				'LAST_NAME' => 'USER.LAST_NAME',
				'SECOND_NAME' => 'USER.SECOND_NAME',
				'EMAIL' => 'USER.EMAIL',
				'LOGIN' => 'USER.LOGIN',
				'NAME_SEARCH' => 'USER.ID',
			),
			'USER_ID' => 'USER.ID',
			'USER_LOGIN' => 'USER.LOGIN',
			'USER_NAME' => 'USER.NAME',
			'USER_LAST_NAME' => 'USER.LAST_NAME',
			'USER_EMAIL' => 'USER.EMAIL',
			'RESPONSIBLE_ID' => 'RESPONSIBLE.ID',
			'RESPONSIBLE_LOGIN' => 'RESPONSIBLE.LOGIN',
			'RESPONSIBLE_NAME' => 'RESPONSIBLE.NAME',
			'RESPONSIBLE_LAST_NAME' => 'RESPONSIBLE.LAST_NAME',
			'RESPONSIBLE_SECOND_NAME' => 'RESPONSIBLE.SECOND_NAME',
			'RESPONSIBLE_EMAIL' => 'RESPONSIBLE.EMAIL',
			'RESPONSIBLE_WORK_POSITION' => 'RESPONSIBLE.WORK_POSITION',
			'RESPONSIBLE_PERSONAL_PHOTO' => 'RESPONSIBLE.PERSONAL_PHOTO',

			'PROPERTY_ID' => 'PROPERTY.ID',
			'PROPERTY_ORDER_PROPS_ID' => 'PROPERTY.ORDER_PROPS_ID',
			'PROPERTY_NAME' => 'PROPERTY.NAME',
			'PROPERTY_VALUE' => 'PROPERTY.VALUE',
			'PROPERTY_CODE' => 'PROPERTY.CODE',
			'PROPERTY_VAL_BY_CODE' => 'PROPERTY.VALUE',
//			'COMPLETE_ORDERS' => 'PROPERTY.ORDER_PROPS_ID',

		);
		return array_merge($fields,
						   static::getAliasPaymentFields(),
						   static::getAliasShipmentFields(),
						   static::getAliasBasketFields()
		);
	}

	/**
	 * @return array
	 */
	protected static function getAliasPaymentFields()
	{
		return array(
			'PAY_SYSTEM_ID' => 'PAYMENT.PAY_SYSTEM_ID',
			'PAYED' => 'PAYMENT.PAID',

			'DATE_PAYED' => 'PAYMENT.DATE_PAID',
			'EMP_PAYED_ID' => 'PAYMENT.EMP_PAID_ID',

			'PS_STATUS' => 'PAYMENT.PS_STATUS',
			'PS_STATUS_CODE' => 'PAYMENT.PS_STATUS_CODE',
			'PS_STATUS_DESCRIPTION' => 'PAYMENT.PS_STATUS_DESCRIPTION',
			'PS_STATUS_MESSAGE' => 'PAYMENT.PS_STATUS_MESSAGE',
			'PS_SUM' => 'PAYMENT.PS_SUM',
			'PS_CURRENCY' => 'PAYMENT.PS_CURRENCY',
			'PS_RESPONSE_DATE' => 'PAYMENT.PS_RESPONSE_DATE',
		);
	}

	/**
	 * @return array
	 */
	protected static function getAliasShipmentFields()
	{
		return array(
			'DELIVERY_ID' => 'SHIPMENT.DELIVERY.CODE',
			//'DELIVERY_ID' => 'SHIPMENT.DELIVERY_ID',
			'PRICE_DELIVERY' => 'SHIPMENT.PRICE_DELIVERY',
			'ALLOW_DELIVERY' => 'SHIPMENT.ALLOW_DELIVERY',
			'DATE_ALLOW_DELIVERY' => 'SHIPMENT.DATE_ALLOW_DELIVERY',
			'EMP_ALLOW_DELIVERY_ID' => 'SHIPMENT.EMP_ALLOW_DELIVERY_ID',

			'DATE_DEDUCTED' => 'SHIPMENT.DATE_DEDUCTED',
			'EMP_DEDUCTED_ID' => 'SHIPMENT.EMP_DEDUCTED_ID',
			'REASON_UNDO_DEDUCTED' => 'SHIPMENT.REASON_UNDO_DEDUCTED',

			'TRACKING_NUMBER' => 'SHIPMENT.TRACKING_NUMBER',
			'DELIVERY_DOC_NUM' => 'SHIPMENT.DELIVERY_DOC_NUM',
			'DELIVERY_DOC_DATE' => 'SHIPMENT.DELIVERY_DOC_DATE',
		);
	}

	/**
	 * @return array
	 */
	protected static function getAliasBasketFields()
	{
		return array(
			'BASKET_ID' => 'BASKET.ID',
			'BASKET_PRODUCT_ID' => 'BASKET.PRODUCT_ID',
			'BASKET_PRODUCT_XML_ID' => 'BASKET.PRODUCT_XML_ID',
			'BASKET_MODULE' => 'BASKET.MODULE',
			'BASKET_NAME' => 'BASKET.NAME',
			'BASKET_QUANTITY' => 'BASKET.QUANTITY',
			'BASKET_PRICE' => 'BASKET.PRICE',
			'BASKET_CURRENCY' => 'BASKET.CURRENCY',
			'BASKET_VAT_RATE' => 'BASKET.VAT_RATE',
			'BASKET_RECOMMENDATION' => 'BASKET.RECOMMENDATION',
            'BASKET_DISCOUNT_PRICE' => 'BASKET.DISCOUNT_PRICE',
            'BASKET_DISCOUNT_NAME' => 'BASKET.DISCOUNT_NAME',
            'BASKET_DISCOUNT_VALUE' => 'BASKET.DISCOUNT_VALUE',
		);
	}

	/**
	 * @return array
	 */
	protected static function getSelectFields()
	{
		$fields = array_keys(static::getEntity()->getScalarFields());

		return array_merge($fields, array(
			'DATE_INSERT_FORMAT',
			'DATE_UPDATE_SHORT',
			'DATE_STATUS_SHORT',
			'DATE_CANCELED_SHORT',
			'BY_RECOMMENDATION',

			'LOCK_STATUS',
			'LOCK_USER_NAME',
			'DATE_INSERT_FORMAT',

			"RESPONSIBLE_ID",
			"RESPONSIBLE_LOGIN",
			"RESPONSIBLE_NAME",
			"RESPONSIBLE_LAST_NAME",
			"RESPONSIBLE_SECOND_NAME",
			"RESPONSIBLE_EMAIL",
			"RESPONSIBLE_WORK_POSITION",
			"RESPONSIBLE_PERSONAL_PHOTO",
			"RESPONSIBLE_GROUP_ID",

			"PAY_SYSTEM_ID",
			"DELIVERY_ID",
			"DEDUCTED",
			"RESERVED",
			"PRICE_DELIVERY",
			"ALLOW_DELIVERY",
			"DATE_ALLOW_DELIVERY",
			"EMP_ALLOW_DELIVERY_ID",
			"DELIVERY_DOC_NUM",
			"DELIVERY_DOC_DATE",
			"PAYED",
			"DATE_PAYED",
			"EMP_PAYED_ID",
			"STATUS_ID",
			"DATE_STATUS",
			"EMP_STATUS_ID",
			"DATE_INSERT_FORMAT",
			"USER_LOGIN",
			"USER_NAME",
			"USER_LAST_NAME",
			"USER_EMAIL",
			"DATE_PAY_BEFORE",
			"DATE_BILL",
			"ACCOUNT_NUMBER",
			"TRACKING_NUMBER",


		));

	}

	/**
	 * @internal
	 * @return array
	 */
	public static function getAvailableFields()
	{
		$registry = Sale\Registry::getInstance(static::getRegistryType());
		/** @var Sale\Order $orderClassName */
		$orderClassName = $registry->getOrderClassName();

		return array_merge($orderClassName::getAvailableFields(),
							array('PRICE_DELIVERY', "PAY_VOUCHER_DATE", "PAY_VOUCHER_NUM", "DATE_ALLOW_DELIVERY", "DATE_PAYED")
		);
	}


	/**
	 * @return array
	 */
	protected static function getShipmentClearFields()
	{
		return array(
			'STATUS_ID',
			'ACCOUNT_NUMBER',
			'DATE_INSERT',
			'MARKED',
			'EMP_MARKED_ID',
			'DATE_MARKED',
			'REASON_MARKED',
			'DATE_CANCELED',
			'EMP_CANCELED_ID',
		);
	}


	/**
	 * @return array
	 */
	protected static function getPaymentClearFields()
	{
		return array(
			'ACCOUNT_NUMBER',
			'MARKED',
			'EMP_MARKED_ID',
			'DATE_MARKED',
			'REASON_MARKED',
		);
	}
	
	/**
	 * @return array
	 */
	protected static function getPaymentAvailableFields()
	{
		$registry = Sale\Registry::getInstance(static::getRegistryType());
		/** @var Sale\Payment $paymentClassName */
		$paymentClassName = $registry->getPaymentClassName();

		return static::clearAvailableFields($paymentClassName::getAvailableFields(), static::getPaymentClearFields());
	}

	/**
	 * @return array
	 */
	protected static function getShipmentAvailableFields()
	{
		$registry = Sale\Registry::getInstance(static::getRegistryType());
		/** @var Sale\Shipment $shipmentClassName */
		$shipmentClassName = $registry->getShipmentClassName();

		return static::clearAvailableFields($shipmentClassName::getAvailableFields(), static::getShipmentClearFields());
	}


	protected function getWhiteListFields()
	{
		return array_merge(parent::getWhiteListFields(), array_keys(static::getAliasFields()));
	}


	/**
	 * @param array $fields
	 * @param array $clearFields
	 *
	 * @return array
	 */
	protected static function clearAvailableFields(array $fields, array $clearFields = array())
	{
		$result = array();
		if (!empty($clearFields))
		{
			foreach ($fields as $field)
			{
				if (!in_array($field, $clearFields))
				{
					$result[] = $field;
				}
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	protected static function getFieldsFromOtherEntities()
	{
		return array_merge(
			static::getShipmentFieldsToConvert(),
			static::getPaymentFieldsToConvert()
		);
	}

	/**
	 * @internal
	 * @return array
	 */
	public static function getOrderDateFields()
	{
		return array(
			'DATE_INSERT' => 'datetime',
			'DATE_UPDATE' => 'datetime',
			'DATE_PAYED' => 'datetime',
			'DATE_STATUS' => 'datetime',
			'DATE_LOCK' => 'datetime',
			'DATE_PAY_BEFORE' => 'date',
			'DATE_BILL' => 'date',
			'DATE_MARKED' => 'datetime',
			'DATE_CANCELED' => 'datetime',
		);
	}

	/**
	 * @return array
	 */
	protected static function getShipmentFieldsToConvert()
	{
		return array(
//			'ALLOW_DELIVERY',
			'DATE_ALLOW_DELIVERY',
			'EMP_ALLOW_DELIVERY_ID',

			'DELIVERY_DOC_NUM',
			'DELIVERY_DOC_DATE',
			'TRACKING_NUMBER',

//			'DEDUCTED',
			'DATE_DEDUCTED',
			'EMP_DEDUCTED_ID',
			'REASON_UNDO_DEDUCTED',

			'RESERVED',
		);
	}

	/**
	 * @return array
	 */
	protected static function getPaymentFieldsToConvert()
	{
		return array(
//			'PAYED',
			'DATE_PAYED',
			'EMP_PAYED_ID',
			'PAY_VOUCHER_NUM',
			'PAY_VOUCHER_DATE',
			'PAY_SYSTEM_ID',
		);
	}

	/**
	 * @return array
	 */
	protected static function getEntityDateFields(Sale\Internals\CollectableEntity $entity)
	{
		if ($entity instanceof Sale\Shipment)
		{
			return array(
				'DATE_ALLOW_DELIVERY' => 'datetime',
				'DELIVERY_DOC_DATE' => 'date',
				'DATE_DEDUCTED' => 'datetime',
				'DATE_MARKED' => 'datetime',
				'DATE_RESPONSIBLE_ID' => 'date',
				'DATE_INSERT' => 'datetime',
				'TRACKING_LAST_CHECK' => 'datetime',
				'TRACKING_LAST_CHANGE' => 'datetime',
			);
		}
		elseif ($entity instanceof Sale\Payment)
		{
			return array(
				'DATE_PAYED' => 'datetime',
				'DATE_PAID' => 'datetime',
				'PAY_VOUCHER_DATE' => 'date',
				'PS_RESPONSE_DATE' => 'datetime',
			);
		}
	}

	/**
	 * @return array
	 */
	protected static function getPaymentDateFields()
	{
		return array(
			'DATE_PAYED' => 'datetime',
			'DATE_PAID' => 'datetime',
			'PAY_VOUCHER_DATE' => 'date',
			'PS_RESPONSE_DATE' => 'datetime',
		);
	}


	/**
	 * @return array
	 */
	protected static function getPaymentReplaceFields()
	{
		return array(
			'PAYED' => 'PAID',
			'DATE_PAYED' => 'DATE_PAID',
			'EMP_PAYED_ID' => 'EMP_PAID_ID',
		);
	}

	/**
	 * @return array
	 */
	protected static function getOrderReplaceFields()
	{
		return array();
	}


	/**
	 * @internal
	 *
	 * @param Sale\Basket $basket
	 * @param array $requestFields
	 *
	 * @return bool
	 */
	public function resetOrderPrice(Sale\Basket $basket, array $requestFields)
	{

		if (empty($requestFields['BASKET_ITEMS']))
			return false;

		$resetPrice = false;
		$resetPriceDelivery = false;

		/** @var Sale\Order $order */
		$order = $this->getOrder();

		if ($order->getId() == 0)
		{
			$order->resetData(array('PRICE_DELIVERY'));
			$resetPriceDelivery = true;
		}

		foreach ($requestFields['BASKET_ITEMS'] as $basketData)
		{
			if (!isset($basketData['ID']) || intval($basketData['ID']) <= 0)
				continue;

			/** @var Sale\BasketItem $basketItem */
			if (!$basketItem = $basket->getItemById($basketData['ID']))
				continue;

			if ($resetPriceDelivery === false)
			{
				if ($order->getId() == 0 || isset($basketData['PRICE'])
					&& floatval($basketData['PRICE']) != $basketItem->getPrice())
				{

					$order->resetData(array('PRICE'));
					$resetPrice = true;
				}
			}


//			if ($resetPriceDelivery === false)
//			{
//				if ($order->getId() == 0 || isset($basketData['QUANTITY'])
//					&& floatval($basketData['QUANTITY']) != $basketItem->getQuantity())
//				{
//					$order->resetData(array('PRICE_DELIVERY'));
//					$resetPriceDelivery = true;
//				}
//			}

			if ($resetPriceDelivery && $resetPrice)
				return true;
		}


		//
		return false;
	}

	/**
	 * @param Sale\Order $order
	 *
	 * @return array
	 */
	public static function getOrderFields(Sale\Order $order)
	{
		$result = new Sale\Result();

		$fields = array(
			"SITE_ID" => $order->getSiteId(),
			"LID" => $order->getSiteId(),
			"PERSON_TYPE_ID" => $order->getPersonTypeId(),
			"PRICE" => $order->getPrice(),
			"CURRENCY" => $order->getCurrency(),
			"USER_ID" => $order->getUserId(),
			"PAY_SYSTEM_ID" => (int)$order->getField('PAY_SYSTEM_ID'),
			"PRICE_DELIVERY" => $order->getDeliveryPrice(),
			"DELIVERY_ID" => (int)$order->getField('DELIVERY_ID'),
			"DISCOUNT_VALUE" => $order->getDiscountPrice(),
			"TAX_VALUE" => $order->getTaxValue(),
			"TRACKING_NUMBER" => $order->getField('TRACKING_NUMBER'),
			"PAYED" => $order->getField('PAYED'),
			"CANCELED" => $order->getField('CANCELED'),
			"STATUS_ID" => $order->getField('STATUS_ID'),
			"RESERVED" => $order->getField('RESERVED'),
		);

		$orderFields = static::convertOrderToArray($order);
		if (is_array($orderFields))
		{
			$orderFields = $fields + $orderFields;
			$orderFields = static::convertDateFieldsToOldFormat($orderFields);
		}

		$result->setData(array(
							 'FIELDS' => $fields,
							 'ORDER_FIELDS' => $orderFields,
						 ));

		return $result;
	}

	/**
	 * @internal
	 * @param Sale\Order $order
	 * @return array
	 */
	public static function convertOrderToArray(Sale\Order $order)
	{
		$fields = $order->getFieldValues();

		//getWeight
		$fields = array_merge($fields,
							  array(
								  'ORDER_WEIGHT' => 0,
								  'BASKET_ITEMS' => array(),
								  'ORDER_PROP' => array(),
								  'DISCOUNT_LIST' => array(),
								  'TAX_LIST' => array(),
								  'VAT_RATE' => $order->getVatRate(),
								  'VAT_SUM' => $order->getVatSum(),
							  ));

		/** @var Sale\Basket $basket */
		if ($basket = $order->getBasket())
		{
			/** @var Sale\BasketItem $basketItem */
			foreach ($basket as $basketItem)
			{
				/** @var BasketCompatibility $basketCompatibilityClassName */
				$basketCompatibilityClassName = static::getBasketCompatibilityClassName();

				$fields['BASKET_ITEMS'][] = $basketCompatibilityClassName::convertBasketItemToArray($basketItem);
			}

			$fields['ORDER_WEIGHT'] = $basket->getWeight();
		}

		/** @var Sale\PropertyValueCollection $basket */
		if ($propertyCollection = $order->getPropertyCollection())
		{
			/** @var Sale\PropertyValue $property */
			foreach ($propertyCollection as $property)
			{
//				$propertyValue = $property->getValue();
				$fields['ORDER_PROP'][$property->getPropertyId()] = $property->getValue();
			}
		}


		if ($propProfileName = $propertyCollection->getProfileName())
			$fields['PROFILE_NAME'] = $propProfileName->getValue();

		if ($propPayerName = $propertyCollection->getPayerName())
			$fields['PAYER_NAME'] = $propPayerName->getValue();

		if ($propUserEmail = $propertyCollection->getUserEmail())
			$fields['USER_EMAIL'] = $propUserEmail->getValue();

		if ($propDeliveryLocationZip = $propertyCollection->getDeliveryLocationZip())
			$fields['DELIVERY_LOCATION_ZIP'] = $propDeliveryLocationZip->getValue();

		if ($propDeliveryLocation = $propertyCollection->getDeliveryLocation())
			$fields['DELIVERY_LOCATION'] = $propDeliveryLocation->getValue();

		if ($propTaxLocation = $propertyCollection->getTaxLocation())
			$fields['TAX_LOCATION'] = $propTaxLocation->getValue();

		$fields['DISCOUNT_LIST'] = DiscountCompatibility::getOldDiscountResult();

		/** @var Sale\Tax $tax */
		if ($tax = $order->getTax())
		{
			$fields['TAX_LIST'] = $tax->getTaxList();
		}

		return $fields;
	}

	public function isExistPrice()
	{
		return ($this->externalPrice !== null);
	}

	/**
	 * @param $key
	 *
	 * @return null|string
	 */
	public function parseField($key)
	{
		$output = null;
		$locationPropInfo = \CSaleOrder::getLocationPropertyInfo();

		static $propIndex = 0;

		$propIDTmp = false;
		if (mb_strpos($key, "PROPERTY_ID_") === 0)
		{
			$propIndex++;
			$this->addPropertyRuntime($propIndex);
			if (!($propRuntimeName = $this->getPropertyRuntimeName($propIndex)))
			{
				return null;
			}

			$propIDTmp = intval(mb_substr($key, mb_strlen("PROPERTY_ID_")));

			$this->query->addFilter('='.$propRuntimeName.'.ORDER_PROPS_ID', $propIDTmp);
			if(isset($locationPropInfo['ID'][$propIDTmp]))
			{
				$this->addQueryAlias('PROPERTY_ID_'.$propIDTmp, 'LOCATION.ID');
			}
			else
			{
				$this->addQueryAlias('PROPERTY_ID_'.$propIDTmp, $propRuntimeName.'.ID');
			}

			$output = 'PROPERTY_ID_'.$propIDTmp;

		}
		elseif (mb_strpos($key, "PROPERTY_ORDER_PROPS_ID_") === 0)
		{
			$propIndex++;
			$this->addPropertyRuntime($propIndex);
			if (!($propRuntimeName = $this->getPropertyRuntimeName($propIndex)))
			{
				return null;
			}

			$propIDTmp = intval(mb_substr($key, mb_strlen("PROPERTY_ORDER_PROPS_ID_")));

			$this->query->addFilter('='.$propRuntimeName.'.ORDER_PROPS_ID', $propIDTmp);
			if(isset($locationPropInfo['ID'][$propIDTmp]))
			{
				$this->addQueryAlias('PROPERTY_ORDER_PROPS_ID_'.$propIDTmp, 'LOCATION.ID');
			}
			else
			{
				$this->addQueryAlias('PROPERTY_ORDER_PROPS_ID_'.$propIDTmp, $propRuntimeName.'.ORDER_PROPS_ID');
			}

			$output = 'PROPERTY_ORDER_PROPS_ID_'.$propIDTmp;
		}
		elseif (mb_strpos($key, "PROPERTY_NAME_") === 0)
		{
			$propIndex++;
			$this->addPropertyRuntime($propIndex);
			if (!($propRuntimeName = $this->getPropertyRuntimeName($propIndex)))
			{
				return null;
			}

			$propIDTmp = intval(mb_substr($key, mb_strlen("PROPERTY_NAME_")));

			$this->addQueryAlias('PROPERTY_NAME_'.$propIDTmp, $propRuntimeName.'.NAME');
			$this->query->addFilter('='.$propRuntimeName.'.ORDER_PROPS_ID', $propIDTmp);

			$output = 'PROPERTY_NAME_'.$propIDTmp;
		}
		elseif (mb_strpos($key, "PROPERTY_VALUE_") === 0)
		{
			$propIndex++;
			$this->addPropertyRuntime($propIndex);
			if (!($propRuntimeName = $this->getPropertyRuntimeName($propIndex)))
			{
				return null;
			}

			$propIDTmp = intval(mb_substr($key, mb_strlen("PROPERTY_VALUE_")));

			if(isset($locationPropInfo['ID'][$propIDTmp]))
			{
				$this->addQueryAlias('PROPERTY_ID_'.$propIDTmp, 'LOCATION.ID');
			}
			else
			{
				$this->addQueryAlias('PROPERTY_ID_'.$propIDTmp, $propRuntimeName.'.VALUE');
			}

			$output = 'PROPERTY_ID_'.$propIDTmp;
		}
		elseif (mb_strpos($key, "PROPERTY_CODE_") === 0)
		{
			$propIndex++;
			$this->addPropertyRuntime($propIndex);
			if (!($propRuntimeName = $this->getPropertyRuntimeName($propIndex)))
			{
				return null;
			}

			$propIDTmp = intval(mb_substr($key, mb_strlen("PROPERTY_CODE_")));
			$this->addQueryAlias('PROPERTY_CODE_'.$propIDTmp, $propRuntimeName.'.CODE');
			$this->query->addFilter('='.$propRuntimeName.'.ORDER_PROPS_ID', $propIDTmp);

			$output = 'PROPERTY_CODE_'.$propIDTmp;
		}
		elseif (mb_strpos($key, "PROPERTY_VAL_BY_CODE_") === 0)
		{
			$propIndex++;
			$this->addPropertyRuntime($propIndex);
			if (!($propRuntimeName = $this->getPropertyRuntimeName($propIndex)))
			{
				return null;
			}

			$propIDTmp = preg_replace("/[^a-zA-Z0-9_-]/is", "", trim(mb_substr($key, mb_strlen("PROPERTY_VAL_BY_CODE_"))));

			$this->addQueryAlias('PROPERTY_VAL_BY_CODE_'.$propIDTmp, $propRuntimeName.'.VALUE');
			if(isset($locationPropInfo['CODE'][$propIDTmp]))
			{
				$this->addQueryAlias('PROPERTY_VAL_BY_CODE_'.$propIDTmp, 'LOCATION.ID');
			}
			else
			{
				$this->addQueryAlias('PROPERTY_VAL_BY_CODE_'.$propIDTmp, $propRuntimeName.'.VALUE');
			}

			$this->query->addFilter('='.$propRuntimeName.'.CODE', $propIDTmp);

			$output = 'PROPERTY_VAL_BY_CODE_'.$propIDTmp;
		}
		elseif (mb_strpos($key, "BASKET_") === 0)
		{
			$output = static::addBasketRuntime($key);
		}

		if(isset($locationPropInfo['ID'][$propIDTmp]))
		{
			$this->query->registerRuntimeField(
				'LOCATION',
				array(
					'data_type' => '\Bitrix\Sale\Location\LocationTable',
					'reference' => array(
						'=this.PROPERTY.VALUE' => 'ref.CODE'
					),
					'join_type' => 'inner'
				)
			);
		}

		return $output;
	}


	/**
	 * @param int $index
	 */
	protected function addPropertyRuntime($index)
	{
		if ($this->getPropertyRuntimeName($index))
			return;

		$this->query->registerRuntimeField(
			'PROPERTY_'.$index,
			array(
				'data_type' => '\Bitrix\Sale\Internals\OrderPropsValueTable',
				'reference' => array(
					'ref.ORDER_ID' => 'this.ID',
				),
				'join_type' => 'inner'
			)
		);

		$this->runtimeFields[] = 'PROPERTY_'.$index;
		$this->propertyRuntimeList[$index] = 'PROPERTY_'.$index;
	}


	protected function getPropertyRuntimeName($index)
	{
		return (!empty($this->propertyRuntimeList[$index]) ? $this->propertyRuntimeList[$index] : null);
	}


	/**
	 * @param $key
	 *
	 * @return null|string
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	protected function addBasketRuntime($key)
	{
		$output = null;

		if ($key == "BASKET_DISCOUNT_COUPON")
		{
			if (!in_array('COUPONS', $this->runtimeFields))
			{
				$this->query->registerRuntimeField(
					'COUPONS',
					array(
						'data_type' => '\Bitrix\Sale\Internals\OrderCouponsTable',
						'reference' => array(
							'=ref.ORDER_ID' => 'this.ID'
						),
					)
				);
				$this->runtimeFields[] = "COUPONS";
			}

			$this->addQueryAlias('BASKET_DISCOUNT_COUPON', 'COUPONS.COUPON');
			$output = 'BASKET_DISCOUNT_COUPON';

		}
		elseif ($key == "BASKET_DISCOUNT_NAME")
		{
			if (!in_array('DISCOUNT_ORDER_RULES', $this->runtimeFields))
			{
				$this->query->registerRuntimeField(
					'DISCOUNT_ORDER_RULES',
					array(
						'data_type' => '\Bitrix\Sale\Internals\OrderRulesTable',
						'reference' => array(
							'=ref.ORDER_ID' => 'this.ID',
						),
					)
				);
				$this->runtimeFields[] = "DISCOUNT_ORDER_RULES";
			}

			if (!in_array('DISCOUNT', $this->runtimeFields))
			{
				$this->query->registerRuntimeField(
					'DISCOUNT',
					array(
						'data_type' => '\Bitrix\Sale\Internals\OrderDiscountTable',
						'reference' => array(
							'=ref.ID' => 'this.DISCOUNT_ORDER_RULES.ORDER_DISCOUNT_ID'
						),
					)
				);

				$this->runtimeFields[] = "DISCOUNT";
			}

			$this->addQueryAlias('BASKET_DISCOUNT_NAME', 'DISCOUNT.NAME');
			$output = 'BASKET_DISCOUNT_NAME';
		}

		return $output;
	}

	protected static function getDefaultFuserId()
	{
		return Sale\Fuser::getId();
	}
}

class OrderFetchAdapter implements FetchAdapter
{

	protected static function getMoneyFields()
	{
		return array(
			"PRICE_DELIVERY",
			"PRICE",
			"DISCOUNT_VALUE",
			"DISCOUNT_ALL",
			"BASKET_PRICE_TOTAL",
			"PS_SUM",
			"PRICE_DELIVERY",
		);
	}
	/**
	 * @param array $row
	 *
	 * @return array
	 */
	public function adapt(array $row)
	{
		$data = Internals\EntityCompatibility::convertDateFieldsToOldFormat($row);
		return static::convertRowData($data);
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public static function convertRowData(array $data)
	{
		if (isset($data['DELIVERY_ID']) && intval($data['DELIVERY_ID']) > 0)
		{
			$data['DELIVERY_ID'] = \CSaleDelivery::getCodeById($data['DELIVERY_ID']);
		}

		if (isset($data['CURRENCY']) && !empty($data['CURRENCY']))
		{
			foreach (static::getMoneyFields() as $field)
			{
				if (array_key_exists($field, $data))
				{
					$data[$field] = SaleFormatCurrency($data[$field], $data["CURRENCY"], false, true);
				}
			}
		}

		return $data;
	}
}