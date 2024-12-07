<?php

declare(strict_types = 1);

namespace Bitrix\Crm\Service\Sale\Terminal;

use Bitrix\Crm;
use Bitrix\Crm\Binding\OrderEntityTable;
use Bitrix\Crm\ClientInfo;
use Bitrix\Crm\Order\Configuration;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\Payment;
use Bitrix\Crm\Terminal\OrderProperty;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\Result;
use Bitrix\Sale;
use Bitrix\Sale\Internals\Entity;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Repository\PaymentRepository;
use Bitrix\Sale\Tax\VatCalculator;
use Bitrix\Crm\Order\TradingPlatform\DynamicEntity;

final class PaymentService
{
	public function createByProducts(array $products, CreatePaymentOptions $createOptions): Result
	{
		$result = new Result();

		if (empty($products))
		{
			$result->addError(new Error('No products specified'));

			return $result;
		}

		if (!$this->validateCurrency($createOptions->getCurrency()))
		{
			$result->addError(new Error('The specified currency does not exist'));

			return $result;
		}

		return $this->createPayment($products, $createOptions);
	}

	public function createByAmount(float $amount, CreatePaymentOptions $createOptions): Result
	{
		$result = new Result();

		if ($amount <= 0)
		{
			$result->addError(new Error('The amount is not specified'));

			return $result;
		}

		if (!$this->validateCurrency($createOptions->getCurrency()))
		{
			$result->addError(new Error('The specified currency does not exist'));

			return $result;
		}

		$products = [
			$this->createPaymentProduct($amount, $createOptions),
		];

		$isEnabledEntitySynchronization = Configuration::isEnabledEntitySynchronization();
		Configuration::setEnabledEntitySynchronization(false);

		$result = $this->createPayment($products, $createOptions);

		Configuration::setEnabledEntitySynchronization($isEnabledEntitySynchronization);

		return $result;
	}

	/**
	 * @param int $id
	 * @param UpdatePaymentOptions $updateOptions
	 * @return Result
	 */
	public function update(int $id, UpdatePaymentOptions $updateOptions): Result
	{
		$result = new Result();

		if (!$id)
		{
			$result->addError(new Error('The payment ID is not specified'));

			return $result;
		}

		$payment = PaymentRepository::getInstance()->getById($id);
		if (!$payment)
		{
			$result->addError(new Error('The specified payment does not exist'));

			return $result;
		}

		$order = $payment->getOrder();

		if ($updateOptions->getResponsibleId())
		{
			$payment->setField('RESPONSIBLE_ID', $updateOptions->getResponsibleId());
		}

		if ($payment->isChanged())
		{
			$orderSaveResult = $order->save();
			if (!$orderSaveResult->isSuccess())
			{
				$result->addErrors($orderSaveResult->getErrors());

				return $result;
			}
		}

		$result->setData([
			'payment' => $payment,
		]);

		return $result;
	}

	private function createPayment(array $products, CreatePaymentOptions $createOptions): Result
	{
		$result = new Result();

		$order = $this->findOrder($createOptions);

		$builderDataResult = $this->getDataForBuilder($products, $createOptions, $order);
		if (!$builderDataResult->isSuccess())
		{
			$result->addErrors($builderDataResult->getErrors());

			return $result;
		}
		$builderData = $builderDataResult->getData();

		$orderBuilder = Crm\Order\Builder\Factory::createBuilderForPayment();
		try
		{
			$orderBuilder->build($builderData);
		}
		catch (Sale\Helpers\Order\Builder\BuildingException)
		{
		}

		$order = $orderBuilder->getOrder();
		if (!$order)
		{
			$errors = $orderBuilder->getErrorsContainer()?->getErrors();
			if (!empty($errors))
			{
				$result->addErrors($errors);
			}
			else
			{
				$result->addError(new Error('Error creating order'));
			}

			return $result;
		}

		$payment = $this->findNewPayment($order);
		if (!$payment)
		{
			$result->addError(new Error('Payment not found'));

			return $result;
		}

		if ($createOptions->getPhoneNumber())
		{
			$this->setPhoneNumberProperty($order, $createOptions->getPhoneNumber());
		}

		$order->getContactCompanyCollection()?->disableAutoCreationMode();

		$orderSaveResult = $order->save();
		if (!$orderSaveResult->isSuccess())
		{
			$result->addErrors($orderSaveResult->getErrors());

			return $result;
		}

		$this->markPayment($payment->getId());
		Crm\Terminal\PullManager::add([$payment->getId()]);

		$this->addTimelineEntryOnCreate($payment);

		$result->setData([
			'payment' => $payment,
		]);

		return $result;
	}

	private function validateCurrency(string $currency): bool
	{
		if (!Loader::includeModule('currency'))
		{
			return false;
		}

		return CurrencyManager::isCurrencyExist($currency);
	}

	private function findOrder(CreatePaymentOptions $createOptions): ?Order
	{
		$boundEntity = $createOptions->getEntity();
		if ($boundEntity)
		{
			$orderRow = OrderEntityTable::getRow([
				'select' => [
					'ORDER_ID',
				],
				'filter' => [
					'=OWNER_ID' => $boundEntity->getEntityId(),
					'=OWNER_TYPE_ID' => $boundEntity->getEntityTypeId(),
				],
			]);
			if ($orderRow)
			{
				return Order::load((int)$orderRow['ORDER_ID']);
			}
		}

		return null;
	}

	private function createPaymentProduct(float $paymentAmount, CreatePaymentOptions $createOptions): array
	{
		$basketProductFields = [
			'PRODUCT_ID' => 0,
			'NAME' => Loc::getMessage('CRM_TL_BASKET_ITEM_NAME'),
			'CUSTOM_PRICE' => 'Y',
			'PRICE' => $paymentAmount,
			'CURRENCY' => $createOptions->getCurrency(),
			'QUANTITY' => 1,
		];

		if (Loader::includeModule('catalog'))
		{
			$measure = [];
			$measureResult = \CCatalogMeasure::getList(
				[],
				['CODE' => \CCatalogMeasure::DEFAULT_MEASURE_CODE],
				false,
				false,
				['CODE', 'SYMBOL_RUS']
			);
			if ($measureResult->SelectedRowsCount())
			{
				$measure = $measureResult->Fetch();
			}
			else
			{
				$measure = \CCatalogMeasure::getDefaultMeasure(true);
			}

			if ($measure)
			{
				$basketProductFields['MEASURE_CODE'] = $measure['CODE'];
				$basketProductFields['MEASURE_NAME'] = $measure['SYMBOL_RUS'];
			}
		}

		return $basketProductFields;
	}

	public function isPaymentWithoutProducts(Payment $payment): bool
	{
		$payableItemCollection = $payment->getPayableItemCollection();

		if ($payableItemCollection->isEmpty())
		{
			return true;
		}

		if ($payableItemCollection->count() !== 1)
		{
			return false;
		}

		/** @var \Bitrix\Sale\PayableItem $payableItem */
		$payableItem = $payableItemCollection->current();

		/** @var Entity $entityObject */
		$entityObject = $payableItem->getEntityObject();

		$fieldsValues = $entityObject->getFieldValues();

		if (
			empty($fieldsValues['MODULE'])
			&& empty($fieldsValues['PRODUCT_ID'])
			&& (int)$fieldsValues['QUANTITY'] === 1
		)
		{
			return true;
		}

		return false;
	}

	private function setPhoneNumberProperty(Order $order, string $phoneNumber): void
	{
		$propertyValue = $order->getPropertyCollection()->createItem(
			OrderProperty::getTerminalProperty()
		);
		$propertyValue->setValue($phoneNumber);
	}

	private function createContactCompany(?string $phone, ?string $name): Sale\Result
	{
		$result = new Sale\Result();

		$userId = (int)CurrentUser::get()->getId();
		$fields = [
			'NAME' => $name ?? '',
			'ASSIGNED_BY_ID' => $userId,
			'TYPE_ID' => 'CLIENT',
			'SOURCE_ID' => 'STORE',
			'FM' => [
				'PHONE' => [
					'n1' => [
						'VALUE_TYPE' => 'MOBILE',
						'VALUE' => $phone ?? '',
					],
				],
			],
		];

		$options = [
			'DISABLE_REQUIRED_USER_FIELD_CHECK' => true,
			'REGISTER_SONET_EVENT' => true,
		];

		$contact = new \CCrmContact(false);
		$id = (int)$contact->Add($fields, true, $options);

		if ($id > 0)
		{
			$result->setId($id);
		}
		else
		{
			$result->addError(new Error($contact->LAST_ERROR));
		}

		return $result;
	}

	private function getDataForBuilder(array $products, CreatePaymentOptions $createOptions, ?Order $order): Result
	{
		$result = new Result();

		$orderId = $order ? $order->getId() : 0;

		$data = [
			'ID' => $orderId,
			'PRODUCT' => $products,
			'PAYMENT' => [],
			'CURRENCY' => $createOptions->getCurrency(),
		];

		$setClientBuilderDataResult = $this->setClientBuilderData($createOptions, $data);
		if (!$setClientBuilderDataResult->isSuccess())
		{
			return $result->addErrors($setClientBuilderDataResult->getErrors());
		}

		$payment = [
			'PRODUCT' => [],
			'PAY_SYSTEM_ID' => $this->getDefaultPaySystem()['ID'],
			'PAY_SYSTEM_NAME' => $this->getDefaultPaySystem()['NAME'],
			'RESPONSIBLE_ID' => $createOptions->getResponsibleId(),
		];

		$paymentSum = 0;
		foreach ($products as $index => $product)
		{
			// probably a temporary solution since taxes are a mess, as usual
			$price = $this->getProductPrice($product);
			$paymentSum += Sale\PriceMaths::roundPrecision($product['QUANTITY'] * $price);

			$payment['PRODUCT'][] = [
				'BASKET_CODE' => $index,
				'QUANTITY' => $product['QUANTITY']
			];
		}

		$payment['SUM'] = $paymentSum;
		$data['PAYMENT'][] = $payment;

		if ($createOptions->getEntity() && !$orderId)
		{
			$data['TRADING_PLATFORM'] = DynamicEntity::getInstanceByCode(
				DynamicEntity::getCodeByEntityTypeId(
					$createOptions->getEntity()->getEntityTypeId()
				)
			)->getId();
		}

		$result->setData($data);

		return $result;
	}

	private function setClientBuilderData(CreatePaymentOptions $createOptions, &$data): Result
	{
		$result = new Result();

		$client = [];

		if ($createOptions->getEntity())
		{
			$client = ClientInfo::createFromOwner(
				$createOptions->getEntity()->getEntityTypeId(),
				$createOptions->getEntity()->getEntityId()
			)->toArray();
		}
		else
		{
			if ($createOptions->getClient())
			{
				if ($createOptions->getClient()->getEntityTypeId() === \CCrmOwnerType::Contact)
				{
					$client['CONTACT_IDS'] = [$createOptions->getClient()->getEntityId()];
				}
				elseif ($createOptions->getClient()->getEntityTypeId() === \CCrmOwnerType::Company)
				{
					$client['COMPANY_ID'] = $createOptions->getClient()->getEntityId();
				}
			}
			elseif ($createOptions->getClientName() || $createOptions->getPhoneNumber())
			{
				$contactCompanyResult = $this->createContactCompany(
					$createOptions->getPhoneNumber(),
					$createOptions->getClientName()
				);
				if ($contactCompanyResult->isSuccess())
				{
					$client['CONTACT_IDS'] = [$contactCompanyResult->getId()];
				}
				else
				{
					$result->addErrors($contactCompanyResult->getErrors());

					return $result;
				}
			}
		}

		if(
			!empty($client['OWNER_ID'])
			&& !empty($client['OWNER_TYPE_ID'])
		)
		{
			$data['OWNER_ID'] = $client['OWNER_ID'];
			$data['OWNER_TYPE_ID'] = $client['OWNER_TYPE_ID'];
			unset($client['OWNER_ID'], $client['OWNER_TYPE_ID']);
		}

		$data['CLIENT'] = $client;

		if (!empty($data['CLIENT']['COMPANY_ID']))
		{
			$data['PERSON_TYPE_ID'] = Crm\Order\PersonType::getCompanyPersonTypeId();
		}
		else
		{
			$data['PERSON_TYPE_ID'] = Crm\Order\PersonType::getContactPersonTypeId();
		}

		return $result;
	}

	private function getDefaultPaySystem(): array
	{
		$paySystemList = PaySystem\Manager::getList([
			'filter' => [
				'=ACTIVE' => 'Y',
			],
		]);

		foreach ($paySystemList as $item)
		{
			if ($item['ACTION_FILE'] === 'cash')
			{
				return $item;
			}
		}

		return PaySystem\Manager::getById(PaySystem\Manager::getInnerPaySystemId());
	}

	private function findNewPayment(Order $order): ?Payment
	{
		foreach ($order->getPaymentCollection() as $payment)
		{
			if ($payment->getId() === 0)
			{
				return $payment;
			}
		}

		return null;
	}
	
	private function getProductPrice(array $product): float
	{
		$price =  (float)($product['PRICE'] ?? 0.0);
		$vatIncluded = ($product['VAT_INCLUDED'] ?? 'Y') === 'Y';
		if (!$vatIncluded)
		{
			$vatRate = (float)($product['VAT_RATE'] ?? 0.0);
			if ($vatRate > 0)
			{
				// price with tax
				$price = (new VatCalculator($vatRate))->accrue($price);
			}
		}
		
		return $price;
	}

	private function addTimelineEntryOnCreate(Payment $payment): void
	{
		Crm\Timeline\OrderPaymentController::getInstance()->onSentToTerminal(
			$payment->getId(),
			[
				'ORDER_FIELDS' => $payment->getOrder()->getFieldValues(),
				'SETTINGS' => [
					'FIELDS' => [
						'ORDER_ID' => $payment->getOrderId(),
						'PAYMENT_ID' => $payment->getId(),
					]
				],
				'BINDINGS' => Crm\Order\BindingsMaker\TimelineBindingsMaker::makeByPayment($payment),
				'FIELDS' => $payment->getFieldValues(),
			]
		);
	}

	public function isTerminalPayment(int $id): bool
	{
		if (!$id)
		{
			return false;
		}

		$terminalPayment = Crm\Terminal\TerminalPaymentTable::getById($id)->fetch();

		return (bool)$terminalPayment;
	}

	private function markPayment(int $id): AddResult
	{
		return Crm\Terminal\TerminalPaymentTable::add(['PAYMENT_ID' => $id]);
	}

	public function unmarkPayment(int $id): DeleteResult
	{
		return Crm\Terminal\TerminalPaymentTable::delete($id);
	}

	public function getRuntimeReferenceField(
		string $joinType = 'inner',
		string $name = 'TERMINAL_PAYMENT'
	): ReferenceField
	{
		return new ReferenceField(
			$name,
			Crm\Terminal\TerminalPaymentTable::class,
			[
				'=this.ID' => 'ref.PAYMENT_ID',
			],
			['join_type' => $joinType]
		);
	}
}
