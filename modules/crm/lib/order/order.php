<?php

namespace Bitrix\Crm\Order;

use Bitrix\Crm\Activity\Provider\Sms;
use Bitrix\Main\EventResult;
use Bitrix\Sale;
use Bitrix\Crm;
use Bitrix\Catalog;
use Bitrix\Main;
use Bitrix\Crm\Requisite;
use Bitrix\Sale\TradingPlatform\Landing\Landing;
use Bitrix\Iblock;
use Bitrix\Catalog\v2\Integration\UI\ViewedProducts;
use Bitrix\Main\Engine\UrlManager;
use \Bitrix\Crm\Order\BindingsMaker\TimelineBindingsMaker;

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

	/** @var DealBinding|null $dealBinding */
	protected $dealBinding = null;

	private $requisiteList = [];

	/**
	 * @param $siteId
	 * @param null $userId
	 * @param null $currency
	 * @return Sale\Order
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

		$contactCompanyCollection = $this->getContactCompanyCollection();
		if ($contactCompanyCollection->isEmpty())
		{
			$r = $this->addContactCompany();
			if (!$r->isSuccess())
			{
				return $r;
			}

			$this->setContactCompanyRequisites();
		}

		if ($this->enableAutomaticDealCreation())
		{
			$dealBinding = $this->getDealBinding();
			if (!$dealBinding)
			{
				$dealCreator = new DealCreator($this);
				$dealId = $dealCreator->create();
				if ($dealId)
				{
					$dealBinding = $this->createDealBinding();
					$dealBinding->setField('DEAL_ID', $dealId);
					$dealBinding->markCrmDealAsNew();
				}
			}
		}

		if (
			Main\Loader::includeModule('landing')
			&& $platform = $this->getTradeBindingCollection()->getTradingPlatform(
				Landing::TRADING_PLATFORM_CODE,
				Landing::LANDING_STORE_STORE_V3
			)
		)
		{
			$this->addTimelineEntryOnStoreV3OrderCreate();
			$this->sendSmsToClientOnStoreV3OrderCreate($platform);
		}

		$this->addTimelineEntryOnCreate();

		return $result;
	}

	protected function enableAutomaticDealCreation()
	{
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

	/**
	 * @param $checkId
	 * @param array $settings
	 */
	public function addTimelineCheckEntryOnCreate($checkId, array $settings = []) : void
	{
		if ((int)$checkId <= 0)
		{
			return;
		}

		Crm\Timeline\OrderCheckController::getInstance()->onPrintCheck(
			$checkId,
			$this->getTimelineEntryParamsOnCheckPrint($settings)
		);
	}

	public function addTimelineCheckEntryOnFailure(array $settings = []) : void
	{
		Crm\Timeline\OrderCheckController::getInstance()->onCheckFailure(
			$this->getTimelineEntryParamsOnCheckFailure($settings)
		);
	}

	/**
	 * @param array $settings
	 * @return array
	 */
	private function getTimelineEntryParamsOnCheckPrint(array $settings)
	{
		return [
			'ORDER_FIELDS' => $this->getFieldValues(),
			'SETTINGS' => $settings,
			'BINDINGS' => TimelineBindingsMaker::makeByOrder($this),
		];
	}

	private function getTimelineEntryParamsOnCheckFailure(array $settings)
	{
		$resultSettings = $settings;
		$resultSettings['FAILURE'] = 'Y';
		$resultSettings['PRINTED'] = 'N';
		return [
			'ORDER_FIELDS' => $this->getFieldValues(),
			'SETTINGS' => $resultSettings,
			'BINDINGS' => TimelineBindingsMaker::makeByOrder($this),
		];
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

		$requisiteList = $entity->getRequisiteList();
		if ($requisiteList)
		{
			$result['REQUISITE_ID'] = current($requisiteList)['ID'];
		}

		$bankRequisiteList = $entity->getBankRequisiteList();
		if ($bankRequisiteList)
		{
			$result['BANK_DETAIL_ID'] = current($bankRequisiteList)['ID'];
		}

		$this->setRequisiteLink($result);
	}

	/**
	 * @return Sale\Result
	 */
	protected function onAfterSave()
	{
		$result = parent::onAfterSave();

		if ($this->fields->isChanged('CANCELED') && $this->isCanceled() && Crm\Automation\Factory::canUseAutomation())
		{
			Crm\Automation\Trigger\OrderCanceledTrigger::execute(
				[['OWNER_TYPE_ID' => \CCrmOwnerType::Order, 'OWNER_ID' => $this->getId()]],
				['ORDER' => $this]
			);
		}

		$dealBinding = $this->getDealBinding();
		if ($dealBinding && $dealBinding->isChanged() && !$this->enableAutomaticDealCreation())
		{
			$this->addTimelineEntryOnBindingDealChanged();
		}

		if ($this->isNew())
		{
			$this->runAutomationOnAdd();
		}
		else
		{
			if ($this->fields->isChanged('STATUS_ID'))
			{
				$this->runAutomationOnStatusChanged();
				$this->addTimelineEntryOnStatusModify();

				if (
					$this->getField('STATUS_ID') === OrderStatus::getFinalStatus()
					&& $this->getDealBinding()
				)
				{
					$params = $this->getTimelineEntryParamsOnSetFinalStatus();

					$this->addTimelineEntryNotifyBindingDeal($params);
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
			if($this->fields->isChanged('RESPONSIBLE_ID'))
			{
				Permissions\Order::updatePermission($this->getId(), $this->getField('RESPONSIBLE_ID'));

				if (Crm\Automation\Factory::canUseAutomation())
				{
					Crm\Automation\Trigger\ResponsibleChangedTrigger::execute(
						[['OWNER_TYPE_ID' => \CCrmOwnerType::Order, 'OWNER_ID' => $this->getId()]],
						['ORDER' => $this]
					);
				}
			}

			static::resetCounters($this->getField('RESPONSIBLE_ID'));
		}

		if ($this->getFields()->isChanged('PAYED') && $this->isPaid())
		{
			if ($this->getDealBinding())
			{
				if (Crm\Automation\Factory::canUseAutomation())
				{
					Crm\Automation\Trigger\OrderPaidTrigger::execute(
						[['OWNER_TYPE_ID' => \CCrmOwnerType::Deal, 'OWNER_ID' => $this->getDealBinding()->getDealId()]],
						['ORDER' => $this]
					);
				}
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

			if ($this->getDealBinding())
			{
				if (Crm\Automation\Factory::canUseAutomation())
				{
					Crm\Automation\Trigger\DeliveryFinishedTrigger::execute(
						[['OWNER_TYPE_ID' => \CCrmOwnerType::Deal, 'OWNER_ID' => $this->getDealBinding()->getDealId()]],
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

		return $result;
	}

	/**
	 * @return void
	 */
	private function updateDealOrderStage()
	{
		if (!$this->getDealBinding())
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
			$deal->Update($this->getDealBinding()->getDealId(), $fields);
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

			if ($latestPayment = $paymentCollection->getItemById($maxId))
			{
				return $latestPayment;
			}
		}

		return null;
	}

	/**
	 * @return array
	 */
	protected function getTimelineEntryParamsOnSetFinalStatus() : array
	{
		return [
			'ORDER_FIELDS' => $this->getFieldValues(),
			'SETTINGS' => [
				'CHANGED_ENTITY' => \CCrmOwnerType::OrderName,
				'FIELDS' => [
					'ORDER_DONE' => 'Y',
				],
			]
		];
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
			$this->getDealBinding()->getDealId(),
			[
				'ORDER_FIELDS' => $this->getFieldValues(),
				'IS_NEW_ORDER' => $this->isNew() ? 'Y' : 'N',
				'IS_NEW_DEAL' => $this->getDealBinding()->isNewCrmDeal() ? 'Y' : 'N'
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
			$this->getDealBinding()->getDealId(),
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
	private function runAutomationOnStatusChanged()
	{
		//TODO: use Crm\Automation\Starter
		Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Order, $this->getId());
	}

	private function addTimelineEntryOnCreate(): void
	{
		Crm\Timeline\OrderController::getInstance()->onCreate(
			$this->getId(),
			$this->getTimelineEntryParamsOnCreate()
		);
	}

	/**
	 * @return ?array
	 */
	private function getViewedProducts(): ?array
	{
		if (!Main\Loader::includeModule('catalog') || !Main\Loader::includeModule('iblock'))
		{
			return null;
		}

		$basePriceGroupId = \CCatalogGroup::GetBaseGroupId();
		/** @var Crm\Product\Url\ProductBuilder $adminLinkBuilder */
		$adminLinkBuilder = Iblock\Url\AdminPage\BuilderManager::getInstance()->getBuilder(
			Crm\Product\Url\ProductBuilder::TYPE_ID
		);

		$skus = ViewedProducts\Repository::getInstance()->getList();

		$newCard = Catalog\Config\State::isProductCardSliderEnabled();

		$result  = [];
		foreach ($skus as $sku)
		{
			$price = $basePriceGroupId ? $sku->getPriceCollection()->findByGroupId($basePriceGroupId) : null;
			$image = $sku->getFrontImageCollection()->getFrontImage();

			if ($adminLinkBuilder)
			{
				$adminLinkBuilder->setIblockId($sku->getIblockId());
			}

			$result[] = [
				'slider' => $newCard ? 'Y' : 'N',
				'offerId' => $sku->getId(),
				'adminLink' => $adminLinkBuilder ? $adminLinkBuilder->getProductDetailUrl($sku->getId()) : null,
				'name' => $sku->getName(),
				'image' => $image ? $image->getSource() : null,
				'variationInfo' => Catalog\v2\Helpers\PropertyValue::getSkuPropertyDisplayValues($sku),
				'price' => $price ? $price->getPrice() : null,
				'currency' => $price ? $price->getCurrency() : null,
			];
		}

		return $result;
	}

	private function addTimelineEntryOnStoreV3OrderCreate(): void
	{
		$viewedProducts = $this->getViewedProducts();

		if (
			$viewedProducts
			&& $this->getDealBinding()
			&& $this->getTradeBindingCollection()->hasTradingPlatform(Landing::TRADING_PLATFORM_CODE)
		)
		{
			Crm\Timeline\OrderController::getInstance()->onLandingOrderCreate(
				$this->getId(),
				$this->getDealBinding()->getDealId(),
				[
					'ORDER_FIELDS' => $this->getFieldValues(),
					'SETTINGS' => [
						'DEAL_ID' => $this->getDealBinding()->getDealId(),
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
		$entityCommunication = $this->getEntityCommunication();
		$phoneTo = $this->getEntityCommunicationPhone();

		if ($entityCommunication && $phoneTo)
		{
			$feedbackPage = $platform->getExternalLink(
				Landing::LINK_TYPE_PUBLIC_FEEDBACK,
				$this
			);

			Crm\MessageSender\MessageSender::send(
				[
					Crm\Integration\NotificationsManager::getSenderCode() => [
						'ACTIVITY_PROVIDER_TYPE_ID' => Crm\Activity\Provider\Notification::PROVIDER_TYPE_NOTIFICATION,
						'TEMPLATE_CODE' => 'ORDER_COMPLETED',
						'PLACEHOLDERS' => [
							'NAME' => $entityCommunication->getCustomerName(),
						],
					],
					Crm\Integration\SmsManager::getSenderCode() => [
						'ACTIVITY_PROVIDER_TYPE_ID' => Sms::PROVIDER_TYPE_SALESCENTER_DELIVERY,
						'MESSAGE_BODY' => Main\Localization\Loc::getMessage('CRM_ORDER_ORDER_CREATED')
							. (
							$feedbackPage
								? (
								' ' . Main\Localization\Loc::getMessage(
									'CRM_ORDER_ORDER_CREATED_QUESTIONS_LEFT',
									[
										'#FEEDBACK_LINK#' => UrlManager::getInstance()->getHostUrl() . \CBXShortUri::GetShortUri($feedbackPage),
									]
								)
							)
								: ''
							),
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

		$dealBinding = $this->dealBinding;
		if ($dealBinding)
		{
			$r = $dealBinding->save();
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}

			if ($dealBinding->isDeleted())
			{
				$this->dealBinding = null;
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

		/** @var DealBinding $dealBinding */
		$dealBinding = $registry->get(ENTITY_CRM_ORDER_DEAL_BINDING);

		$r = $dealBinding::deleteNoDemand($orderId);
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

			$dealBinding = $order->getDealBinding();
			if ($dealBinding)
			{
				$dealBinding->delete();
			}
		}
	}

	/**
	 * @return Company|Contact|null
	 */
	public function getEntityCommunication()
	{
		$contact = $this->getContactCompanyCollection()->getPrimaryContact();
		if ($contact)
		{
			return $contact;
		}

		$company = $this->getContactCompanyCollection()->getPrimaryCompany();
		if ($company)
		{
			return $company;
		}

		return null;
	}

	/**
	 * @return string|null
	 */
	public function getEntityCommunicationPhone(): ?string
	{
		$entityCommunication = $this->getEntityCommunication();
		if (!$entityCommunication)
		{
			return null;
		}

		$phone = \CCrmFieldMulti::GetEntityFirstPhone(
			$entityCommunication::getEntityTypeName(),
			$entityCommunication->getField('ENTITY_ID'),
			true,
			false
		);

		if (!$phone)
		{
			return null;
		}

		return (string)$phone->format();
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

		if ($dealBinding = $this->getDealBinding())
		{
			$dealBinding->clearChanged();
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
		}

		return $result;
	}

	/**
	 * @param int $responsibleId
	 */
	private static function resetCounters($responsibleId)
	{
		$respIds = intval($responsibleId) > 0 ? array($responsibleId) : array();

		Crm\Counter\EntityCounterManager::reset(
			Crm\Counter\EntityCounterManager::prepareCodes(
				\CCrmOwnerType::Order,
				array(
					Crm\Counter\EntityCounterType::PENDING,
					Crm\Counter\EntityCounterType::IDLE,
					Crm\Counter\EntityCounterType::ALL
				),
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
				ENTITY_CRM_ORDER_DEAL_BINDING => 'Bitrix\Crm\Order\DealBinding',
				Sale\Registry::ENTITY_TRADE_BINDING_COLLECTION => 'Bitrix\Crm\Order\TradeBindingCollection',
				Sale\Registry::ENTITY_TRADE_BINDING_ENTITY => 'Bitrix\Crm\Order\TradeBindingEntity',
			)
		);

		return new EventResult(EventResult::SUCCESS, $registry);
	}

	/**
	 * @return DealBinding
	 */
	public function getDealBinding()
	{
		if (!$this->dealBinding)
		{
			$this->dealBinding = $this->loadDealBinding();
		}

		if ($this->dealBinding && $this->dealBinding->isDeleted())
		{
			return null;
		}

		return $this->dealBinding;
	}

	/**
	 * @return DealBinding|null
	 */
	public function loadDealBinding()
	{
		$registry = Sale\Registry::getInstance(static::getRegistryType());

		$dealBindingClassName = $registry->get(ENTITY_CRM_ORDER_DEAL_BINDING);
		return $dealBindingClassName::load($this);
	}

	/**
	 * @return DealBinding
	 */
	public function createDealBinding()
	{
		$registry = Sale\Registry::getInstance(static::getRegistryType());

		/** @var DealBinding $dealBindingClassName */
		$dealBindingClassName = $registry->get(ENTITY_CRM_ORDER_DEAL_BINDING);

		$this->dealBinding = $dealBindingClassName::create($this);

		return $this->dealBinding;
	}

	/**
	 * @return array
	 */
	public function toArray() : array
	{
		$result = parent::toArray();

		$result['CONTACTS_COMPANIES'] = $this->getContactCompanyCollection()->toArray();

		if ($this->getDealBinding())
		{
			$result['DEAL_BINDING'] = $this->getDealBinding()->toArray();
		}

		return $result;
	}
}