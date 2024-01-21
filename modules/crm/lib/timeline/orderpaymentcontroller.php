<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Order;
use Bitrix\Crm\Order\Payment;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Registry;
use Bitrix\Sale\Cashbox;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Sale\Repository\PaymentRepository;

Loc::loadMessages(__FILE__);

/**
 * Class OrderPaymentController
 * @package Bitrix\Crm\Timeline
 */
class OrderPaymentController extends EntityController
{
	public const SENT_TO_TERMINAL = 'SENT_TO_TERMINAL';
	public const PAY_SYSTEM_CLICK = 'PAY_SYSTEM_CLICK';
	public const VIEWED_WAY_CUSTOMER_PAYMENT_PAY = 'VIEWED';

	/**
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::OrderPayment;
	}

	/**
	 * @param $ownerID
	 * @param array $params
	 * @throws Main\ArgumentException
	 */
	public function onCreate($ownerID, array $params)
	{
		if (!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}
		if ($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$fields = isset($params['FIELDS']) && is_array($params['FIELDS']) ? $params['FIELDS'] : null;
		if (!is_array($fields))
		{
			$fields = self::getEntity($ownerID);
		}
		if (!is_array($fields))
		{
			return;
		}

		$settingFields = [
			'SUM' => $fields['SUM'],
			'CURRENCY' => $fields['CURRENCY']
		];

		if ($fields['DATE_BILL'] instanceof Main\Type\Date)
		{
			$settingFields['DATE_BILL_TIMESTAMP'] = $fields['DATE_BILL']->getTimestamp();
		}

		$settings = ['FIELDS' => $settingFields];

		$orderId = (isset($fields['ORDER_ID']) && (int)$fields['ORDER_ID'] > 0) ? (int)$fields['ORDER_ID'] : 0;
		if ($orderId > 0)
		{
			$settings['BASE'] = [
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => (int)$fields['ORDER_ID'],
			];
		}
		self::enrichSettingFields((int)$ownerID, $settings);

		$authorID = self::resolveCreatorID($fields);
		$bindings = [
			[
				'ENTITY_TYPE_ID' => \CCrmOwnerType::OrderPayment,
				'ENTITY_ID' => $ownerID,
			]
		];

		if ($orderId > 0)
		{
			$bindings[] = array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $orderId
			);
		}

		$timelineEntryId = CreationEntry::create([
			'ENTITY_TYPE_ID' => \CCrmOwnerType::OrderPayment,
			'ENTITY_ID' => $ownerID,
			'AUTHOR_ID' => $authorID,
			'SETTINGS' => $settings,
			'BINDINGS' => $bindings,
		]);

		$enableHistoryPush = $timelineEntryId > 0;
		if ($enableHistoryPush && Main\Loader::includeModule('pull'))
		{
			$pushParams = array();
			if ($enableHistoryPush)
			{
				$historyFields = TimelineEntry::getByID($timelineEntryId);
				if(is_array($historyFields))
				{
					$pushParams['HISTORY_ITEM'] = $this->prepareHistoryDataModel(
						$historyFields,
						array('ENABLE_USER_INFO' => true)
					);
				}
			}

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::OrderPayment, $ownerID);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => 'timeline_order_payment_add',
					'params' => $pushParams,
				)
			);
		}
	}

	public function onPaid(int $ownerId, array $params): void
	{
		$params['SETTINGS']['CHANGED_ENTITY'] = \CCrmOwnerType::OrderPaymentName;
		$this->notifyOrderPaymentEntry($ownerId, $params);

		if (isset($params['ENTITY']) && $params['ENTITY'] instanceof Payment)
		{
			$payment = $params['ENTITY'];

			$isWithOrdersMode = \CCrmSaleHelper::isWithOrdersMode();

			$paySystem = $payment->getPaySystem();
			if (!$paySystem)
			{
				return;
			}

			if ($isWithOrdersMode && $paySystem->canPrintCheck())
			{
				$documents = Cashbox\CheckManager::collateDocuments([$payment]);
				if (empty($documents))
				{
					$cashboxList = Cashbox\Manager::getListFromCache();
					if ($cashboxList)
					{
						$this->notifyOrderPaymentEntry(
							$ownerId,
							[
								'SETTINGS' => [
									'CHANGED_ENTITY' => \CCrmOwnerType::OrderPaymentName,
									'FIELDS' => [
										'NEED_MANUAL_ADD_CHECK' => 'Y',
										'ORDER_ID' => $payment->getOrderId(),
										'PAYMENT_ID' => $payment->getId(),
									],
								],
								'FIELDS' => $payment->getFieldValues(),
								'BINDINGS' => Order\BindingsMaker\TimelineBindingsMaker::makeByPayment($payment),
							]
						);
					}
				}
			}
		}
	}

	public function onClick(int $ownerId, array $params): void
	{
		$params['SETTINGS']['CHANGED_ENTITY'] = \CCrmOwnerType::OrderPaymentName;
		$params['SETTINGS']['FIELDS'][self::PAY_SYSTEM_CLICK] = 'Y';
		$this->notifyOrderPaymentEntry($ownerId, $params);
	}

	/**
	 * @param $ownerId
	 * @param array $params
	 * @throws Main\ArgumentException
	 */
	private function notifyOrderPaymentEntry($ownerId, array $params)
	{
		if (!is_int($ownerId))
		{
			$ownerId = (int)$ownerId;
		}
		if ($ownerId <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$settings = is_array($params['SETTINGS']) ? $params['SETTINGS'] : [];
		self::enrichSettingFields((int)$ownerId, $settings);

		$paymentFields = is_array($params['FIELDS']) ? $params['FIELDS'] : [];
		$bindings = $params['BINDINGS'] ?? [];

		$authorId = self::resolveCreatorID($paymentFields);
		if (!empty($settings))
		{
			$timelineEntryId = OrderEntry::create([
				'ENTITY_ID' => $ownerId,
				'TYPE_CATEGORY_ID' => TimelineType::MODIFICATION,
				'ENTITY_TYPE_ID' => \CCrmOwnerType::OrderPayment,
				'AUTHOR_ID' => $authorId,
				'BINDINGS' => $bindings,
				'SETTINGS' => $settings
			]);

			foreach ($bindings as $binding)
			{
				$this->sendPullEventOnAdd(
					new ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
					$timelineEntryId
				);
			}
		}
	}

	/**
	 * @param $ownerID
	 * @param $entryTypeID
	 * @param array $fields
	 * @return Main\Result
	 * @throws Main\ArgumentException
	 */
	public function updateSettingFields($ownerID, $entryTypeID, array $fields)
	{
		$result = new Main\Result();
		$ownerID = (int)$ownerID;
		$entryTypeID = (int)$entryTypeID;
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$timelineData = Entity\TimelineTable::getList([
			'filter' => [
				'ASSOCIATED_ENTITY_ID' => $ownerID,
				'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::OrderPayment,
				'TYPE_ID' => $entryTypeID,
			]
		]);
		while ($row = $timelineData->fetch())
		{
			$settings = $row['SETTINGS'];
			$settings['FIELDS'] = $fields;
			$r = Entity\TimelineTable::update($row['ID'], ['SETTINGS' => $settings]);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
			elseif (is_array($settings['BASE']))
			{
				$baseOwnerId = (int)$settings['BASE']['ENTITY_ID'];
				$baseOwnerTypeId = (int)$settings['BASE']['ENTITY_TYPE_ID'];
				if ($baseOwnerId > 0 && \CCrmOwnerType::IsDefined($baseOwnerTypeId))
				{
					$row['SETTINGS'] = $settings;
					$items = array($row['ID'] => $row);
					TimelineManager::prepareDisplayData($items);
					if(Main\Loader::includeModule('pull') && \CPullOptions::GetQueueServerStatus())
					{
						$tag = TimelineEntry::prepareEntityPushTag($baseOwnerTypeId, $baseOwnerId);
						\CPullWatch::AddToStack(
							$tag,
							array(
								'module_id' => 'crm',
								'command' => 'timeline_item_update',
								'params' => array('ENTITY_ID' => $row['ID'], 'TAG' => $tag, 'HISTORY_ITEM' => $items[$row['ID']]),
							)
						);
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param $ID
	 * @return Main\ORM\Fields\ScalarField[]|null
	 */
	protected static function getEntity($ID)
	{
		$payment = Payment::getList([
			'filter' => [
				'ID' => $ID,
			],
			'select' => [
				'ORDER_CREATED_BY' => 'ORDER.CREATE_BY',
				'ORDER_ACCOUNT_NUMBER' => 'ORDER.ACCOUNT_NUMBER',
				'RESPONSIBLE_ID',
				'ACCOUNT_NUMBER',
				'DATE_BILL',
				'ORDER_ID',
			],
		]);

		return is_object($payment) ? $payment->getFields() : null;
	}

	/**
	 * @param array $fields
	 * @return int
	 */
	protected static function resolveCreatorID(array $fields)
	{
		$authorId = 0;

		if (isset($fields['RESPONSIBLE_ID']))
		{
			$authorId = (int)$fields['RESPONSIBLE_ID'];
		}

		if ($authorId === 0 && isset($fields['ORDER_CREATED_BY']))
		{
			$authorId = (int)$fields['ORDER_CREATED_BY'];
		}

		if ($authorId <= 0)
		{
			$authorId = self::getDefaultAuthorId();
		}

		return $authorId;
	}

	public function onView(int $ownerId, array $params, string $viewedWay): void
	{
		$isValidViewedWay = in_array(
			$viewedWay,
			[
				self::VIEWED_WAY_CUSTOMER_PAYMENT_PAY,
			],
			true
		);
		if (!$isValidViewedWay)
		{
			return;
		}

		$params['SETTINGS']['CHANGED_ENTITY'] = \CCrmOwnerType::OrderPaymentName;
		$params['SETTINGS']['FIELDS'][$viewedWay] = 'Y';
		$this->notifyOrderPaymentEntry($ownerId, $params);
	}

	public function onSentToTerminal(int $ownerId, array $params): void
	{
		$params['SETTINGS']['CHANGED_ENTITY'] = \CCrmOwnerType::OrderPaymentName;
		$params['SETTINGS']['FIELDS'][self::SENT_TO_TERMINAL] = 'Y';
		$this->notifyOrderPaymentEntry($ownerId, $params);
	}

	/**
	 * @param $ownerId
	 * @param $params
	 */
	public function onSend($ownerId, $params)
	{
		if ((int)$ownerId === 0)
		{
			return;
		}

		$params['SETTINGS']['CHANGED_ENTITY'] = \CCrmOwnerType::OrderPaymentName;
		$this->notifyOrderPaymentEntry($ownerId, $params);

		$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
		/** @var \Bitrix\Sale\Payment $paymentClassName */
		$paymentClassName = $registry->getPaymentClassName();
		$orderData = $paymentClassName::getList([
			'select' => ['ORDER_ID'],
			'filter' => ['ID' => $ownerId],
			'limit' => 1,
		])->fetch();

		if ($orderData)
		{
			$order = Order\Order::load($orderData['ORDER_ID']);
			if ($order)
			{
				/** @var Order\EntityBinding $binding */
				$binding = $order->getEntityBinding();
				if (
					$binding
					&& $binding->getOwnerTypeId() === \CCrmOwnerType::Deal
				)
				{
					$this->changeOrderStageDealOnSentNoViewed(
						$binding->getOwnerId()
					);
				}
			}
		}
	}

	private function changeOrderStageDealOnSentNoViewed($dealId)
	{
		$fields = ['ORDER_STAGE' => Order\OrderStage::SENT_NO_VIEWED];

		$deal = new \CCrmDeal(false);
		$deal->Update($dealId, $fields);
	}

	/**
	 * @param array $data
	 * @param array|null $options
	 * @return array
	 */
	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$data = array_merge($data, is_array($data['SETTINGS']) ? $data['SETTINGS'] : []);

		return parent::prepareHistoryDataModel($data, $options);
	}

	private static function enrichSettingFields(int $paymentId, array &$settings): void
	{
		$payment = PaymentRepository::getInstance()->getById($paymentId);
		if (!$payment)
		{
			return;
		}

		$settingsFields = [
			'IS_TERMINAL_PAYMENT' =>
				Container::getInstance()->getTerminalPaymentService()->isTerminalPayment($payment->getId())
					? 'Y'
					: 'N'
			,
		];
		if (isset($settings['FIELDS']) && is_array($settings['FIELDS']))
		{
			$settings['FIELDS'] = array_merge(
				$settings['FIELDS'],
				$settingsFields
			);
		}
		else
		{
			$settings['FIELDS'] = $settingsFields;
		}
	}
}
