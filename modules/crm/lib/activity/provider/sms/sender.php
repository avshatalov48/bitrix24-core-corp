<?php

namespace Bitrix\Crm\Activity\Provider\Sms;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\BindingsMaker\ActivityBindingsMaker;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Sale\Repository\PaymentRepository;
use Bitrix\Sale\Repository\ShipmentRepository;

final class Sender
{
	private ItemIdentifier $owner;
	private MessageDto $message;
	private ?ItemIdentifier $toEntity = null;
	private int $responsibleId;
	private ?string $source = null;
	private ?int $paymentId = null;
	private ?int $shipmentId = null;
	private array $compilationProductIds = [];

	public function __construct(ItemIdentifier $owner, MessageDto $message)
	{
		$this->owner = $owner;
		$this->message = $message;
		$this->responsibleId = Container::getInstance()->getContext()->getUserId(); // @todo check user id
	}

	public function send(): Result
	{
		$result = new Result();

		$ownerTypeId = $this->owner->getEntityTypeId();
		$ownerId = $this->owner->getEntityId();

		if(!Container::getInstance()->getUserPermissions()->checkUpdatePermissions($ownerTypeId, $ownerId))
		{
			$result->addError(new Error('CRM_PERMISSION_DENIED'));

			return $result;
		}

		$bindings = $this->getBindings();
		$additionalFields = $this->getAdditionalFields($bindings);

		$message = $this->message;
		$sendResult = \Bitrix\Crm\Integration\SmsManager::sendMessage([
			'SENDER_ID' => $message->senderId,
			'AUTHOR_ID' => $this->responsibleId,
			'MESSAGE_FROM' => $message->from,
			'MESSAGE_TO' => $message->to,
			'MESSAGE_BODY' => $message->body,
			'MESSAGE_TEMPLATE' => $message->template,
			'MESSAGE_HEADERS' => [
				'module_id' => 'crm',
				'bindings' => $bindings,
			],
			'ADDITIONAL_FIELDS' => $additionalFields
		]);

		if (!$sendResult->isSuccess())
		{
			$result->addErrors($sendResult->getErrors());
		}

		return $result;
	}

	private function getBindings(): array
	{
		$ownerTypeId = $this->owner->getEntityTypeId();
		$ownerId = $this->owner->getEntityId();

		$bindings = [
			[
				'OWNER_TYPE_ID' => $ownerTypeId,
				'OWNER_ID' => $ownerId,
			],
		];

		$comEntityId = $this->getComEntityItemIdentifier()->getEntityId();
		$comEntityTypeId = $this->getComEntityItemIdentifier()->getEntityTypeId();

		if ($comEntityTypeId !== $ownerTypeId || $comEntityId !== $ownerId)
		{
			$bindings[] = [
				'OWNER_TYPE_ID' => $comEntityTypeId,
				'OWNER_ID' => $comEntityId
			];
		}

		return $bindings;
	}

	private function getAdditionalFields(array &$bindings): array
	{
		$message = $this->message;

		$comEntityId = $this->getComEntityItemIdentifier()->getEntityId();
		$comEntityTypeId = $this->getComEntityItemIdentifier()->getEntityTypeId();

		$additionalFields = [
			'ACTIVITY_PROVIDER_TYPE_ID' => \Bitrix\Crm\Activity\Provider\Sms::PROVIDER_TYPE_SMS,
			'ENTITY_TYPE' => \CCrmOwnerType::ResolveName($comEntityTypeId),
			'ENTITY_TYPE_ID' => $comEntityTypeId,
			'ENTITY_ID' => $comEntityId,
			'BINDINGS' => $bindings,
			'ACTIVITY_AUTHOR_ID' => $this->responsibleId,
			'ACTIVITY_DESCRIPTION' => $message->body,
			'MESSAGE_TO' => $message->to,
		];

		$this->prepareOrderAdditionalFields($additionalFields, $bindings);
		$this->prepareDealAdditionalFields($additionalFields);

		return $additionalFields;
	}

	private function prepareOrderAdditionalFields(&$additionalFields, &$bindings): void
	{
		if (
			$this->paymentId
			&& $this->source === mb_strtolower(\CCrmOwnerType::OrderName)
			&& preg_match('/(?:https?):\/\//', $this->message->body)
			&& Loader::includeModule('sale')
		)
		{
			$payment = PaymentRepository::getInstance()->getById($this->paymentId);
			if (!$payment)
			{
				return;
			}

			$bindings = array_merge($bindings, ActivityBindingsMaker::makeByPayment($payment));

			$additionalFields['ENTITIES'] = [
				'ORDER' => $payment->getOrder(),
				'PAYMENT' => $payment,
				'SHIPMENT' => $this->shipmentId ? ShipmentRepository::getInstance()->getById($this->shipmentId) : null,
			];
		}
	}

	private function prepareDealAdditionalFields(&$additionalFields): void
	{
		if (empty($this->compilationProductIds) || $this->source !== mb_strtolower(\CCrmOwnerType::DealName))
		{
			return;
		}

		$deal = (
		$this->owner->getEntityTypeId() === \CCrmOwnerType::Deal
			? \CCrmDeal::GetByID($this->owner->getEntityId())
			: null
		);

		$additionalFields['PRODUCT_IDS'] = $this->compilationProductIds;
		$additionalFields['ENTITIES'] = [
			'DEAL' => $deal,
		];
	}

	public function setEntityIdentifier(ItemIdentifier $entity): self
	{
		$this->toEntity = $entity;

		return $this;
	}

	public function setSource(?string $source): self
	{
		$this->source = $source;

		return $this;
	}

	public function setPaymentId(?int $paymentId): self
	{
		$this->paymentId = $paymentId;

		return $this;
	}

	public function setShipmentId(?int $shipmentId): self
	{
		$this->shipmentId = $shipmentId;

		return $this;
	}

	public function setCompilationProductIds(array $ids): self
	{
		$this->compilationProductIds = $ids;

		return $this;
	}

	private function getComEntityItemIdentifier(): ItemIdentifier
	{
		return ($this->toEntity ?: $this->owner);
	}
}
