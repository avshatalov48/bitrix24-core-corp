<?php

namespace Bitrix\Crm\Order;

use Bitrix\Catalog\v2\Sku\BaseSku;
use Bitrix\Crm\Activity\Provider\BaseMessage;
use Bitrix\Crm\Relation\EntityRelationTable;
use Bitrix\Crm\Service\Timeline\Item\DealProductList\SkuConverter;
use Bitrix\Main\EventResult;
use Bitrix\Sale;
use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Crm\Requisite;
use Bitrix\Sale\TradingPlatform\Landing\Landing;
use Bitrix\Catalog\v2\Integration\UI\ViewedProducts;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Crm\Order\BindingsMaker\TimelineBindingsMaker;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

Main\Localization\Loc::loadMessages(__FILE__);

/**
 * Class Order
 * @package Bitrix\Crm\Order
 */
class Order extends Sale\Order
{
	protected $contactCompanyCollection = null;

	/** @var EntityBinding|null $entityBinding */
	protected $entityBinding = null;

	private $requisiteList = [];

	/**
	 * @param $siteId
	 * @param null $userId
	 * @param null $currency
	 * @return static
	 */
	public static function create($siteId, $userId = null, $currency = null)
	{
		$order = parent::create($siteId, $userId, $currency);

		$order->setFieldNoDemand(
			'RESPONSIBLE_ID',
			Crm\Settings\OrderSettings::getCurrent()->getDefaultResponsibleId()
		);

		return $order;
	}

	/**
	 * @return Sale\Result
	 */
	protected function add()
	{
		$result = parent::add();
		if (!$result->isSuccess())
		{
			return $result;
		}

		if ($this->needCreateContactCompany())
		{
			$r = $this->addContactCompany();
			if (!$r->isSuccess())
			{
				return $r;
			}

			$this->setContactCompanyRequisites();
		}

		$this->addTimelineEntryOnCreate();

		return $result;
	}

	protected function enableAutomaticDealCreation()
	{
		if (!Configuration::isEnabledEntitySynchronization())
		{
			return false;
		}

		if ($this->isSetNotAvailableTradePlatformForDeal())
		{
			return false;
		}

		if (!\CCrmSaleHelper::isWithOrdersMode())
		{
			return true;
		}
		else
		{
			$collection = $this->getTradeBindingCollection();
			/** @var TradeBindingEntity $binding */
			foreach ($collection as $binding)
			{
				$platform = $binding->getTradePlatform();
				if ($platform instanceof Landing)
				{
					return $platform->isOfType(Landing::LANDING_STORE_CHATS);
				}
			}
		}

		return false;
	}

	private function isSetNotAvailableTradePlatformForDeal(): bool
	{
		$notAvailableTradePlatform = [
			TradingPlatform\RealizationDocument::TRADING_PLATFORM_CODE,
		];

		$setTradePlatform = [];

		$collection = $this->getTradeBindingCollection();
		/** @var TradeBindingEntity $binding */
		foreach ($collection as $binding)
		{
			$platform = $binding->getTradePlatform();
			if ($platform)
			{
				$setTradePlatform[] = $platform->getCode();
			}
		}

		return (bool)(array_intersect($notAvailableTradePlatform, $setTradePlatform));
	}

	private function needCreateContactCompany(): bool
	{
		$contactCompanyCollection = $this->getContactCompanyCollection();
		if (
			$contactCompanyCollection->isEmpty()
			&& $contactCompanyCollection->isAutoCreationModeEnabled()
		)
		{
			return true;
		}

		return false;
	}

	/**
	 * @param string $name
	 * @param float|int|mixed|string $oldValue
	 * @param float|int|mixed|string $value
	 * @return Sale\Result
	 */
	protected function onFieldModify($name, $oldValue, $value)
	{
		$result = parent::onFieldModify($name, $oldValue, $value);
		if (!$result->isSuccess())
		{
			return $result;
		}

		if ($name === 'STATUS_ID')
		{
			$canceled = (OrderStatus::getSemanticID($value) === Crm\PhaseSemantics::FAILURE) ? 'Y' : 'N';

			$r = $this->setField('CANCELED', $canceled);
			if (!$r->isSuccess())
			{
				return $result->addErrors($r->getErrors());
			}
		}

		return $result;
	}

	private function setContactCompanyRequisites()
	{
		$collection = $this->getContactCompanyCollection();

		$entity = $collection->getPrimaryCompany();
		if ($entity === null)
		{
			$entity = $collection->getPrimaryContact();
		}

		if ($entity === null)
		{
			return;
		}

		$result = [
			'MC_REQUISITE_ID' => 0,
			'MC_BANK_DETAIL_ID' => 0
		];

		$matcher = Matcher\EntityMatchManager::getInstance();
		$entityId = $entity->getField('ENTITY_ID');
		$entityTypeId = $entity->getField('ENTITY_TYPE_ID');
		$requisiteMatch = $matcher->matchEntityRequisites($this, $entityTypeId, $entityId);
		if (!empty($requisiteMatch))
		{
			[$selectedRequisites, $selectedBankRequisites] = $requisiteMatch;
		}

		if (!empty($selectedRequisites))
		{
			$result['REQUISITE_ID'] = current($selectedRequisites)['ID'];
		}
		else
		{
			$requisiteList = $entity->getRequisiteList();
			if ($requisiteList)
			{
				$result['REQUISITE_ID'] = current($requisiteList)['ID'];
			}
		}

		if (!empty($selectedBankRequisites))
		{
			$result['BANK_DETAIL_ID'] = current($selectedBankRequisites)['ID'];
		}
		else
		{
			$bankRequisiteList = $entity->getBankRequisiteList();
			if ($bankRequisiteList)
			{
				$result['BANK_DETAIL_ID'] = current($bankRequisiteList)['ID'];
			}
		}

		$this->setRequisiteLink($result);
	}

	/**
	 * @return Sale\Result
	 */
	protected function onAfterSave()
	{
		$result = parent::onAfterSave();

		$binding = $this->getEntityBinding();

		if (
			(
				!$binding
				|| $binding->getOwnerTypeId() === \CCrmOwnerType::Deal
			)
			&& $this->enableAutomaticDealCreation()
		)
		{
			if ($this->isNew())
			{
				(new OrderDealSynchronizer)->createDealFromOrder($this);
			}
			elseif ($this->getBasket()->isChanged())
			{
				(new OrderDealSynchronizer)->updateDealFromOrder($this);
			}
		}

		if ($this->isNew())
		{
			$this->processOnStoreV3OrderCreate();
		}

		if (
			$this->fields->isChanged('CANCELED')
			&& $this->isCanceled()
			&& Crm\Automation\Factory::isAutomationAvailable(\CCrmOwnerType::Order)
		)
		{
			Crm\Automation\Trigger\OrderCanceledTrigger::execute(
				[['OWNER_TYPE_ID' => \CCrmOwnerType::Order, 'OWNER_ID' => $this->getId()]],
				['ORDER' => $this]
			);
		}

		if ($binding?->getOwnerTypeId() === \CCrmOwnerType::Deal)
		{
			if ($binding->isChanged() && !$this->enableAutomaticDealCreation())
			{
				$this->addTimelineEntryOnBindingDealChanged();
			}
		}

		if($this->fields->isChanged('RESPONSIBLE_ID'))
		{
			Permissions\Order::updatePermission($this->getId(), $this->getField('RESPONSIBLE_ID'));
		}

		if ($this->isNew())
		{
			$this->runAutomationOnAdd();
		}
		else
		{
			if ($this->fields->isChanged('STATUS_ID'))
			{
				$this->addTimelineEntryOnStatusModify();

				if (
					$this->getField('STATUS_ID') === OrderStatus::getFinalStatus()
					&& $binding?->getOwnerTypeId() === \CCrmOwnerType::Deal
				)
				{
					$this->addTimelineEntryNotifyBindingDeal([
						'ORDER_FIELDS' => $this->getFieldValues(),
						'SETTINGS' => [
							'CHANGED_ENTITY' => \CCrmOwnerType::OrderName,
							'FIELDS' => [
								'ORDER_DONE' => 'Y',
							],
						]
					]);
				}
			}

			if ($this->fields->isChanged('PRICE') || $this->fields->isChanged('CURRENCY'))
			{
				$this->updateTimelineCreationEntity();
			}
		}

		if ($this->fields->isChanged('CANCELED'))
		{
			if (!($this->isNew() && $this->getField('CANCELED') === 'N'))
			{
				$this->addTimelineEntryOnCancel();
			}
		}

		$this->saveRequisiteLink();

		if ($this->fields->isChanged('RESPONSIBLE_ID') || $this->fields->isChanged('STATUS_ID'))
		{
			if(
				$this->fields->isChanged('RESPONSIBLE_ID')
				&& Crm\Automation\Factory::isAutomationAvailable(\CCrmOwnerType::Order)
				&& $this->isNew()
			)
			{
				Crm\Automation\Trigger\ResponsibleChangedTrigger::execute(
					[['OWNER_TYPE_ID' => \CCrmOwnerType::Order, 'OWNER_ID' => $this->getId()]],
					['ORDER' => $this]
				);
			}

			static::resetCounters($this->getField('RESPONSIBLE_ID'));
		}

		if ($this->getFields()->isChanged('PAYED') && $this->isPaid())
		{
			$binding = $this->getEntityBinding();
			if (
				$binding
				&& Crm\Automation\Factory::canUseAutomation()
				&& Crm\Automation\Trigger\OrderPaidTrigger::isSupported($binding->getOwnerTypeId())
			)
			{
				Crm\Automation\Trigger\OrderPaidTrigger::execute(
					[['OWNER_TYPE_ID' => $binding->getOwnerTypeId(), 'OWNER_ID' => $binding->getOwnerId()]],
					['ORDER' => $this]
				);
			}

			Crm\Timeline\OrderController::getInstance()->onPay(
				$this->getId(),
				$this->getTimelineEntryParamsOnPaid()
			);
		}

		$this->updateDealOrderStage();

		if ($this->getFields()->isChanged('DEDUCTED') && $this->isDeducted())
		{
			Crm\Timeline\OrderController::getInstance()->onDeduct(
				$this->getId(),
				$this->getTimelineEntryParamsOnDeducted()
			);

			$binding = $this->getEntityBinding();
			if (
				$binding
				&& $binding->getOwnerTypeId() === \CCrmOwnerType::Deal
			)
			{
				if (Crm\Automation\Factory::isAutomationAvailable(\CCrmOwnerType::Deal))
				{
					Crm\Automation\Trigger\DeliveryFinishedTrigger::execute(
						[['OWNER_TYPE_ID' => \CCrmOwnerType::Deal, 'OWNER_ID' => $binding->getOwnerId()]],
						['ORDER' => $this]
					);
				}
			}
		}

		$this->appendBuyerGroups();

		if (Main\Loader::includeModule('pull'))
		{
			\CPullWatch::AddToStack(
				'CRM_ENTITY_ORDER',
				[
					'module_id' => 'crm',
					'command' => 'onOrderSave',
					'params' => [
						'FIELDS' => $this->getFieldValues()
					]
				]
			);
		}

		Crm\Search\SearchContentBuilderFactory::create(\CCrmOwnerType::Order)->build($this->getId());

		if (!$this->isNew() && Crm\Automation\Factory::isAutomationAvailable(\CCrmOwnerType::Order))
		{
			$this->runAutomationOnUpdate($this->fields->getChangedValues(), $this->fields->getOriginalValues());
		}

		return $result;
	}

	/**
	 * @return void
	 */
	private function updateDealOrderStage()
	{
		if (
			!$this->getEntityBinding()
			|| $this->getEntityBinding()->getOwnerTypeId() !== \CCrmOwnerType::Deal
		)
		{
			return;
		}

		$nextStage = false;

		if ($this->getFields()->isChanged('PAYED') && $this->isPaid())
		{
			$nextStage = OrderStage::PAID;
		}
		else
		{
			$latestPayment = $this->getLatestPayment();
			if ($latestPayment && $latestPayment->getFields()->isChanged('PAID'))
			{
				if ($latestPayment->isPaid())
				{
					$nextStage = OrderStage::PAID;
				}
				elseif ($latestPayment->isReturn())
				{
					$nextStage = OrderStage::REFUND;
				}
				else
				{
					$originalFields = $latestPayment->getFields()->getOriginalValues();

					// @todo: implement Payment::isNew() method
					if ($originalFields['PAID'] !== null)
					{
						$nextStage = OrderStage::PAYMENT_CANCEL;
					}
				}
			}
		}

		if ($nextStage)
		{
			$fields = ['ORDER_STAGE' => $nextStage];
			$deal = new \CCrmDeal(false);
			$deal->Update($this->getEntityBinding()->getOwnerId(), $fields);
		}
	}

	/**
	 * @return \Bitrix\Sale\Payment|null
	 */
	private function getLatestPayment()
	{
		$paymentCollection = $this->getPaymentCollection();
		if ($paymentCollection && count($paymentCollection) > 0)
		{
			$maxId = 0;

			/** @var \Bitrix\Sale\Payment $payment */
			foreach ($paymentCollection as $payment)
			{
				$maxId = max($maxId, $payment->getId());
			}

			if ($maxId > 0)
			{
				return $paymentCollection->getItemById($maxId);
			}
		}

		return null;
	}

	/**
	 * @return array
	 */
	protected function getTimelineEntryParamsOnPaid() : array
	{
		return [
			'ORDER_FIELDS' => $this->getFieldValues(),
			'SETTINGS' => [
				'CHANGED_ENTITY' => \CCrmOwnerType::OrderPaymentName,
				'FIELDS' => [
					'ORDER_PAID' => $this->getField('PAYED'),
					'ORDER_DONE' => ($this->getField('STATUS_ID') === OrderStatus::getFinalStatus()) ? 'Y' : 'N',
				]
			],
			'BINDINGS' => TimelineBindingsMaker::makeByOrder($this, ['withDeal' => false])
		];
	}

	/**
	 * @return array
	 */
	protected function getTimelineEntryParamsOnCreate() : array
	{
		return [
			'ORDER_FIELDS' => $this->getFieldValues(),
			'BINDINGS' => TimelineBindingsMaker::makeByOrder($this, ['withDeal' => false])
		];
	}

	/**
	 * @return array
	 */
	protected function getTimelineEntryParamsOnDeducted() : array
	{
		return [
			'ORDER_FIELDS' => $this->getFieldValues(),
			'SETTINGS' => [
				'CHANGED_ENTITY' => \CCrmOwnerType::OrderShipmentName,
				'FIELDS' => [
					'ORDER_DEDUCTED' => $this->getField('DEDUCTED')
				]
			],
			'BINDINGS' => TimelineBindingsMaker::makeByOrder($this, ['withDeal' => false])
		];
	}

	protected function addTimelineEntryOnBindingDealChanged()
	{
		Crm\Timeline\OrderController::getInstance()->onBindingDealCreation(
			$this->getId(),
			$this->getEntityBinding()->getOwnerId(),
			[
				'ORDER_FIELDS' => $this->getFieldValues(),
				'IS_NEW_ORDER' => $this->isNew() ? 'Y' : 'N',
				'IS_NEW_DEAL' => $this->getEntityBinding()->isNewEntity() ? 'Y' : 'N'
			]
		);
	}

	/**
	 * @param array $params
	 */
	protected function addTimelineEntryNotifyBindingDeal(array $params) : void
	{
		Crm\Timeline\OrderController::getInstance()->notifyBindingDeal(
			$this->getId(),
			$this->getEntityBinding()->getOwnerId(),
			$params
		);
	}


	/**
	 * return void
	 */
	protected function appendBuyerGroups()
	{
		$userId = $this->getField('USER_ID');

		if (!empty($userId))
		{
			\CUser::AppendUserGroup($userId, BuyerGroup::getDefaultGroups());
		}
	}

	/**
	 * @return void;
	 */
	private function runAutomationOnAdd()
	{
		$starter = new Crm\Automation\Starter(\CCrmOwnerType::Order, $this->getId());
		$starter->runOnAdd();
	}

	/**
	 * @return void;
	 */
	private function runAutomationOnUpdate(array $fields, array $prevFields)
	{
		$starter = new Crm\Automation\Starter(\CCrmOwnerType::Order, $this->getId());
		$starter->runOnUpdate($fields, $prevFields);
	}

	private function addTimelineEntryOnCreate(): void
	{
		Crm\Timeline\OrderController::getInstance()->onCreate(
			$this->getId(),
			$this->getTimelineEntryParamsOnCreate()
		);
	}

	/**
	 * Processing the creation of an order for the store.
	 *
	 * @return void
	 */
	private function processOnStoreV3OrderCreate(): void
	{
		if (!Main\Loader::includeModule('landing'))
		{
			return;
		}

		$platform = $this->getTradeBindingCollection()->getTradingPlatform(
			Landing::TRADING_PLATFORM_CODE,
			Landing::LANDING_STORE_STORE_V3
		);
		if ($platform)
		{
			$this->addTimelineEntryOnStoreV3OrderCreate();
			$this->sendSmsToClientOnStoreV3OrderCreate($platform);
		}
	}

	private function addTimelineEntryOnStoreV3OrderCreate(): void
	{
		$viewedProducts = array_map(
			static function (BaseSku $sku)
			{
				return SkuConverter::convertToProductModel($sku)->toArray();
			},
			ViewedProducts\Repository::getInstance()->getList()
		);

		if (
			$viewedProducts
			&& $this->getEntityBinding()
			&& $this->getEntityBinding()->getOwnerTypeId() === \CCrmOwnerType::Deal
			&& $this->getTradeBindingCollection()->hasTradingPlatform(Landing::TRADING_PLATFORM_CODE)
		)
		{
			Crm\Timeline\OrderController::getInstance()->onLandingOrderCreate(
				$this->getId(),
				$this->getEntityBinding()->getOwnerId(),
				[
					'ORDER_FIELDS' => $this->getFieldValues(),
					'SETTINGS' => [
						'DEAL_ID' => $this->getEntityBinding()->getOwnerId(),
						'VIEWED_PRODUCTS' => $viewedProducts,
					],
				]
			);
		}
	}

	/**
	 * @param Sale\TradingPlatform\Platform $platform
	 */
	private function sendSmsToClientOnStoreV3OrderCreate(Sale\TradingPlatform\Platform $platform): void
	{
		/** @var Contact|Company|null $entityCommunication */
		$entityCommunication = $this->getContactCompanyCollection()->getEntityCommunication();
		$phoneTo = $this->getContactCompanyCollection()->getEntityCommunicationPhone();

		if ($entityCommunication && $phoneTo)
		{
			$feedbackPage = $platform->getExternalLink(
				Landing::LINK_TYPE_PUBLIC_FEEDBACK,
				$this
			);

			Crm\MessageSender\MessageSender::send(
				[
					Crm\Integration\NotificationsManager::getSenderCode() => [
						'ACTIVITY_PROVIDER_TYPE_ID' => BaseMessage::PROVIDER_TYPE_CRM_ORDER_COMPLETED,
						'TEMPLATE_CODE' => 'ORDER_COMPLETED',
						'PLACEHOLDERS' => [
							'NAME' => $entityCommunication->getCustomerName(),
						],
					],
					Crm\Integration\SmsManager::getSenderCode() => [
						'ACTIVITY_PROVIDER_TYPE_ID' => BaseMessage::PROVIDER_TYPE_CRM_ORDER_COMPLETED,
						'MESSAGE_BODY' => !empty($feedbackPage)
							? Main\Localization\Loc::getMessage(
								'CRM_ORDER_ORDER_CREATED_WITH_FEEDBACK_LINK',
								[
									'#FEEDBACK_LINK#' => UrlManager::getInstance()->getHostUrl() . \CBXShortUri::GetShortUri($feedbackPage),
								]
							)
							: Main\Localization\Loc::getMessage('CRM_ORDER_ORDER_CREATED_WITHOUT_FEEDBACK_LINK'),
					]
				],
				[
					'COMMON_OPTIONS' => [
						'PHONE_NUMBER' => $phoneTo,
						'USER_ID' => $this->getField('RESPONSIBLE_ID'),
						'ADDITIONAL_FIELDS' => [
							'ENTITY_TYPE' => $entityCommunication::getEntityTypeName(),
							'ENTITY_TYPE_ID' => $entityCommunication::getEntityType(),
							'ENTITY_ID' => $entityCommunication->getField('ENTITY_ID'),
							'BINDINGS' => Crm\Order\BindingsMaker\ActivityBindingsMaker::makeByOrder(
								$this,
								[
									'extraBindings' => [
										[
											'TYPE_ID' => $entityCommunication::getEntityType(),
											'ID' => $entityCommunication->getField('ENTITY_ID'),
										]
									]
								]
							),
						]
					]
				]
			);
		}
	}

	private function addTimelineEntryOnCancel()
	{
		$fields = [
			'ID' => $this->getId(),
			'CANCELED' => $this->getField('CANCELED'),
		];

		if ($this->getField('CANCELED') === 'Y')
		{
			$fields['REASON_CANCELED'] = $this->getField('REASON_CANCELED');
			$fields['EMP_CANCELED_ID'] = $this->getField('EMP_CANCELED_ID');
		}

		Crm\Timeline\OrderController::getInstance()->onCancel(
			$this->getId(),
			[
				'FIELDS' => $fields,
				'BINDINGS' => TimelineBindingsMaker::makeByOrder($this, ['withDeal' => false])
			]
		);
	}

	private function addTimelineEntryOnStatusModify()
	{
		$originalValues = $this->getFields()->getOriginalValues();

		$params = [
			'PREVIOUS_FIELDS' => ['STATUS_ID' => $originalValues['STATUS_ID']],
			'CURRENT_FIELDS' => [
				'STATUS_ID' => $this->getField('STATUS_ID'),
				'EMP_STATUS_ID' => $this->getField('EMP_STATUS_ID')
			],
			'BINDINGS' => TimelineBindingsMaker::makeByOrder($this, ['withDeal' => false])
		];
		Crm\Timeline\OrderController::getInstance()->onModify($this->getId(), $params);
	}

	private function updateTimelineCreationEntity()
	{
		$fields = $this->getFields();
		$selectedFields = [
			'DATE_INSERT_TIMESTAMP' => $fields['DATE_INSERT']->getTimestamp(),
			'PRICE' => $fields['PRICE'],
			'CURRENCY' => $fields['CURRENCY']
		];

		Crm\Timeline\OrderController::getInstance()->updateSettingFields(
			$this->getId(),
			Crm\Timeline\TimelineType::CREATION,
			$selectedFields
		);
	}

	/**
	 * @return Sale\Result
	 */
	private function addContactCompany()
	{
		$result = new Sale\Result();

		$matches = Matcher\EntityMatchManager::getInstance()->match($this);
		if ($matches)
		{
			/** @var ContactCompanyCollection $communication */
			$communication = $this->getContactCompanyCollection();
			if (isset($matches[\CCrmOwnerType::Contact]))
			{
				/** @var Contact $contact */
				$contact = Contact::create($communication);
				$contact->setField('ENTITY_ID', $matches[\CCrmOwnerType::Contact]);
				$contact->setField('IS_PRIMARY', 'Y');

				$communication->addItem($contact);
			}

			if (isset($matches[\CCrmOwnerType::Company]))
			{
				/** @var Company $company */
				$company = Company::create($communication);
				$company->setField('ENTITY_ID', $matches[\CCrmOwnerType::Company]);
				$company->setField('IS_PRIMARY', 'Y');

				$communication->addItem($company);
			}
		}

		return $result;
	}

	/**
	 * @return Sale\Result
	 */
	protected function saveEntities()
	{
		$result = parent::saveEntities();

		$r = $this->getContactCompanyCollection()->save();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		$binding = $this->entityBinding;
		if ($binding)
		{
			$r = $binding->save();
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}

			if ($binding->isDeleted())
			{
				$this->entityBinding = null;
			}
		}

		return $result;
	}

	/**
	 * @param $orderId
	 * @return Sale\Result
	 */
	protected static function deleteEntitiesNoDemand($orderId)
	{
		$result = parent::deleteEntitiesNoDemand($orderId);

		$registry = Sale\Registry::getInstance(static::getRegistryType());

		/** @var ContactCompanyCollection $contactCompanyCollection */
		$contactCompanyCollection = $registry->get(ENTITY_CRM_CONTACT_COMPANY_COLLECTION);

		$r = $contactCompanyCollection::deleteNoDemand($orderId);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		/** @var EntityBinding $binding */
		$binding = $registry->get(ENTITY_CRM_ORDER_ENTITY_BINDING);

		$r = $binding::deleteNoDemand($orderId);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @param Sale\OrderBase $order
	 */
	protected static function deleteEntities(Sale\OrderBase $order)
	{
		parent::deleteEntities($order);

		if ($order instanceof Order)
		{
			/** @var ContactCompanyCollection $contactCompanyCollection */
			if ($contactCompanyCollection = $order->getContactCompanyCollection())
			{
				/** @var ContactCompanyEntity $entity */
				foreach ($contactCompanyCollection as $entity)
				{
					$entity->delete();
				}
			}

			$binding = $order->getEntityBinding();
			if ($binding)
			{
				$binding->delete();
			}
		}
	}

	/**
	 * @return ContactCompanyCollection|null
	 */
	public function getContactCompanyCollection()
	{
		if (!$this->contactCompanyCollection)
		{
			$this->contactCompanyCollection = $this->loadContactCompanyCollection();
		}

		return $this->contactCompanyCollection;
	}

	/**
	 * @return ContactCompanyCollection
	 */
	public function loadContactCompanyCollection()
	{
		$registry = Sale\Registry::getInstance(static::getRegistryType());

		/** @var ContactCompanyCollection $contactCompanyClassName */
		$contactCompanyClassName = $registry->get(ENTITY_CRM_CONTACT_COMPANY_COLLECTION);
		return $contactCompanyClassName::load($this);
	}

	/**
	 * @return bool
	 */
	public function isChanged()
	{
		if (parent::isChanged())
		{
			return true;
		}

		/** @var ContactCompanyCollection $contactCompanyCollection */
		if ($contactCompanyCollection = $this->getContactCompanyCollection())
		{
			if ($contactCompanyCollection->isChanged())
			{
				return true;
			}
		}

		return false;
	}

	public function clearChanged()
	{
		parent::clearChanged();

		if ($contactCompanyCollection = $this->getContactCompanyCollection())
		{
			$contactCompanyCollection->clearChanged();
		}

		if ($binding = $this->getEntityBinding())
		{
			$binding->clearChanged();
		}
	}

	/**
	 * Delete order.
	 *
	 * @param int $id Order id.
	 * @return Sale\Result
	 */
	public static function delete($id)
	{
		$result = parent::delete($id);

		if ($result->isSuccess())
		{
			$data = $result->getData();

			/** @var Sale\OrderBase $order */
			$order = $data['ORDER'];
			$responsibleId = $order->getField('RESPONSIBLE_ID');
			if ($responsibleId > 0)
			{
				static::resetCounters($responsibleId);
			}

			Crm\Timeline\TimelineEntry::deleteByOwner(\CCrmOwnerType::Order, $id);
			EntityRelationTable::deleteByItem(\CCrmOwnerType::Order, $id);
		}

		return $result;
	}

	/**
	 * @param int $responsibleId
	 */
	private static function resetCounters($responsibleId)
	{
		$respIds = intval($responsibleId) > 0 ? array($responsibleId) : array();

		$counters = Crm\Settings\Crm::isUniversalActivityScenarioEnabled()
			? [
				Crm\Counter\EntityCounterType::CURRENT,
				Crm\Counter\EntityCounterType::INCOMING_CHANNEL,
				Crm\Counter\EntityCounterType::ALL
			]
			: [
				Crm\Counter\EntityCounterType::PENDING,
				Crm\Counter\EntityCounterType::IDLE,
				Crm\Counter\EntityCounterType::ALL
			]
		;
		Crm\Counter\EntityCounterManager::reset(
			Crm\Counter\EntityCounterManager::prepareCodes(
				\CCrmOwnerType::Order,
				$counters,
				array('EXTENDED_MODE' => true)
			),
			$respIds
		);
	}

	/**
	 * @param array $requisiteList
	 */
	public function setRequisiteLink(array $requisiteList)
	{
		foreach ($requisiteList as $name => $value)
		{
			$this->requisiteList[$name] = $value;
		}
	}

	/**
	 * @return array|false
	 */
	public function getRequisiteLink()
	{
		if (!$this->requisiteList)
		{
			if ($this->getId() > 0)
			{
				$this->requisiteList = $this->loadRequisiteLink();
			}
		}

		return $this->requisiteList;
	}

	/**
	 * @return array|false
	 */
	protected function loadRequisiteLink()
	{
		$dbRes = Requisite\EntityLink::getList([
			'select' => [
				'REQUISITE_ID',
				'BANK_DETAIL_ID',
				'MC_REQUISITE_ID',
				'MC_BANK_DETAIL_ID',
			],
			'filter' => [
				'=ENTITY_ID' => $this->getId(),
				'=ENTITY_TYPE_ID' => \CCrmOwnerType::Order
			]
		]);

		if ($data = $dbRes->fetch())
		{
			return $data;
		}

		return [];
	}

	protected function saveRequisiteLink()
	{
		$requisiteLink = $this->getRequisiteLink();
		if ($requisiteLink)
		{
			Requisite\EntityLink::register(
				\CCrmOwnerType::Order,
				$this->getId(),
				$requisiteLink['REQUISITE_ID'] ?: 0,
				$requisiteLink['BANK_DETAIL_ID'] ?: 0,
				$requisiteLink['MC_REQUISITE_ID'] ?: 0,
				$requisiteLink['MC_BANK_DETAIL_ID'] ?: 0
			);
		}
	}

	/**
	 * @param \SplObjectStorage $cloneEntity
	 */
	protected function cloneEntities(\SplObjectStorage $cloneEntity)
	{
		parent::cloneEntities($cloneEntity);

		/** @var Order $orderClone */
		$orderClone = $cloneEntity[$this];

		/** @var ContactCompanyCollection $contactCompanyCollection */
		if ($contactCompanyCollection = $this->getContactCompanyCollection())
		{
			$orderClone->contactCompanyCollection = $contactCompanyCollection->createClone($cloneEntity);
		}
	}

	/**
	 * @return EventResult
	 */
	public static function OnInitRegistryList()
	{
		$registry = array(
			Sale\Registry::REGISTRY_TYPE_ORDER => array(
				Sale\Registry::ENTITY_ORDER => '\Bitrix\Crm\Order\Order',
				Sale\Registry::ENTITY_PROPERTY => '\Bitrix\Crm\Order\Property',
				Sale\Registry::ENTITY_SHIPMENT_PROPERTY => '\Bitrix\Crm\Order\ShipmentProperty',
				Sale\Registry::ENTITY_PROPERTY_VALUE => '\Bitrix\Crm\Order\PropertyValue',
				Sale\Registry::ENTITY_PROPERTY_VALUE_COLLECTION => '\Bitrix\Crm\Order\PropertyValueCollection',
				Sale\Registry::ENTITY_SHIPMENT_PROPERTY_VALUE_COLLECTION => '\Bitrix\Crm\Order\ShipmentPropertyValueCollection',
				Sale\Registry::ENTITY_SHIPMENT_PROPERTY_VALUE => '\Bitrix\Crm\Order\ShipmentPropertyValue',
				Sale\Registry::ENTITY_TAX => '\Bitrix\Crm\Order\Tax',
				Sale\Registry::ENTITY_DISCOUNT => '\Bitrix\Crm\Order\Discount',
				Sale\Registry::ENTITY_DISCOUNT_COUPON => '\Bitrix\Crm\Order\DiscountCoupon',
				Sale\Registry::ENTITY_ORDER_DISCOUNT => '\Bitrix\Crm\Order\OrderDiscount',
				Sale\Registry::ENTITY_BASKET => '\Bitrix\Crm\Order\Basket',
				Sale\Registry::ENTITY_BASKET_ITEM => '\Bitrix\Crm\Order\BasketItem',
				Sale\Registry::ENTITY_BUNDLE_COLLECTION => '\Bitrix\Crm\Order\BundleCollection',
				Sale\Registry::ENTITY_BASKET_PROPERTIES_COLLECTION => '\Bitrix\Crm\Order\BasketPropertiesCollection',
				Sale\Registry::ENTITY_BASKET_PROPERTY_ITEM => '\Bitrix\Crm\Order\BasketPropertyItem',
				Sale\Registry::ENTITY_BASKET_RESERVE_COLLECTION => '\Bitrix\Crm\Order\ReserveQuantityCollection',
				Sale\Registry::ENTITY_BASKET_RESERVE_COLLECTION_ITEM => '\Bitrix\Crm\Order\ReserveQuantity',
				Sale\Registry::ENTITY_PAYMENT => '\Bitrix\Crm\Order\Payment',
				Sale\Registry::ENTITY_PAYMENT_COLLECTION => '\Bitrix\Crm\Order\PaymentCollection',
				Sale\Registry::ENTITY_PAYABLE_BASKET_ITEM => '\Bitrix\Crm\Order\PayableBasketItem',
				Sale\Registry::ENTITY_PAYABLE_SHIPMENT => '\Bitrix\Crm\Order\PayableShipmentItem',
				Sale\Registry::ENTITY_PAYABLE_ITEM_COLLECTION => '\Bitrix\Crm\Order\PayableItemCollection',
				Sale\Registry::ENTITY_SHIPMENT => '\Bitrix\Crm\Order\Shipment',
				Sale\Registry::ENTITY_SHIPMENT_COLLECTION => '\Bitrix\Crm\Order\ShipmentCollection',
				Sale\Registry::ENTITY_SHIPMENT_ITEM => '\Bitrix\Crm\Order\ShipmentItem',
				Sale\Registry::ENTITY_SHIPMENT_ITEM_COLLECTION => '\Bitrix\Crm\Order\ShipmentItemCollection',
				Sale\Registry::ENTITY_SHIPMENT_ITEM_STORE => '\Bitrix\Crm\Order\ShipmentItemStore',
				Sale\Registry::ENTITY_SHIPMENT_ITEM_STORE_COLLECTION => '\Bitrix\Crm\Order\ShipmentItemStoreCollection',
				Sale\Registry::ENTITY_OPTIONS => 'Bitrix\Main\Config\Option',
				Sale\Registry::ENTITY_ORDER_STATUS => 'Bitrix\Crm\Order\OrderStatus',
				Sale\Registry::ENTITY_DELIVERY_STATUS => 'Bitrix\Crm\Order\DeliveryStatus',
				Sale\Registry::ENTITY_PERSON_TYPE => 'Bitrix\Crm\Order\PersonType',
				Sale\Registry::ENTITY_ENTITY_MARKER => 'Bitrix\Crm\Order\EntityMarker',
				Sale\Registry::ENTITY_ORDER_HISTORY => 'Bitrix\Crm\Order\OrderHistory',
				Sale\Registry::ENTITY_NOTIFY => 'Bitrix\Crm\Order\Notify',
				ENTITY_CRM_COMPANY => 'Bitrix\Crm\Order\Company',
				ENTITY_CRM_CONTACT => 'Bitrix\Crm\Order\Contact',
				ENTITY_CRM_CONTACT_COMPANY_COLLECTION => 'Bitrix\Crm\Order\ContactCompanyCollection',
				ENTITY_CRM_ORDER_ENTITY_BINDING => 'Bitrix\Crm\Order\EntityBinding',
				Sale\Registry::ENTITY_TRADE_BINDING_COLLECTION => 'Bitrix\Crm\Order\TradeBindingCollection',
				Sale\Registry::ENTITY_TRADE_BINDING_ENTITY => 'Bitrix\Crm\Order\TradeBindingEntity',
			)
		);

		return new EventResult(EventResult::SUCCESS, $registry);
	}

	/**
	 * @return EntityBinding|null
	 */
	public function getEntityBinding()
	{
		if (!$this->entityBinding)
		{
			$this->entityBinding = $this->loadEntityBinding();
		}

		if ($this->entityBinding && $this->entityBinding->isDeleted())
		{
			return null;
		}

		return $this->entityBinding;
	}

	/**
	 * @return EntityBinding|null
	 */
	public function loadEntityBinding()
	{
		$registry = Sale\Registry::getInstance(static::getRegistryType());

		$bindingClassName = $registry->get(ENTITY_CRM_ORDER_ENTITY_BINDING);
		return $bindingClassName::load($this);
	}

	/**
	 * @return EntityBinding
	 */
	public function createEntityBinding()
	{
		$registry = Sale\Registry::getInstance(static::getRegistryType());

		/** @var EntityBinding $bindingClassName */
		$bindingClassName = $registry->get(ENTITY_CRM_ORDER_ENTITY_BINDING);

		$this->entityBinding = $bindingClassName::create($this);

		return $this->entityBinding;
	}

	/**
	 * @return array
	 */
	public function toArray() : array
	{
		$result = parent::toArray();

		$result['CONTACTS_COMPANIES'] = $this->getContactCompanyCollection()->toArray();

		if ($this->getEntityBinding())
		{
			$result['ENTITY_BINDING'] = $this->getEntityBinding()->toArray();
		}

		return $result;
	}
}
