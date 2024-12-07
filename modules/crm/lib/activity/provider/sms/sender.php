<?php

namespace Bitrix\Crm\Activity\Provider\Sms;

use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MessageSender\Channel;
use Bitrix\Crm\MessageSender\Channel\ChannelRepository;
use Bitrix\Crm\MessageSender\SendFacilitator;
use Bitrix\Crm\MessageSender\SendFacilitator\Notifications;
use Bitrix\Crm\MessageSender\SendFacilitator\Sms;
use Bitrix\Crm\Order\BindingsMaker\ActivityBindingsMaker;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
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
	private SenderExtra $senderExtra;

	public function __construct(
		ItemIdentifier $owner,
		MessageDto $message,
		?SenderExtra $senderExtra = null,
	)
	{
		$this->owner = $owner;
		$this->message = $message;
		$this->responsibleId = Container::getInstance()->getContext()->getUserId();
		$this->senderExtra = $senderExtra ?? new SenderExtra();
	}

	public function send(bool $checkUserPermissions = true): Result
	{
		$ownerTypeId = $this->owner->getEntityTypeId();
		$ownerId = $this->owner->getEntityId();
		$message = $this->message;

		$senderCode = ($message->senderId === NotificationsManager::getSenderCode()
			? NotificationsManager::getSenderCode()
			: SmsManager::getSenderCode()
		);
		$senderChannelId = $message->senderId;
		$channel =
			ChannelRepository::create(new ItemIdentifier($ownerTypeId, $ownerId))
				->getById($senderCode, $senderChannelId)
		;

		$result = new Result();

		if (!$channel)
		{
			$result->addError(new Error(Loc::getMessage('CRM_ACTIVITY_PROVIDER_SMS_CHANNEL_NOT_FOUND')));

			return $result;
		}

		if (
			$checkUserPermissions
			&& !Container::getInstance()->getUserPermissions()->checkUpdatePermissions($ownerTypeId, $ownerId)
		)
		{
			$result->addError(new Error('CRM_PERMISSION_DENIED'));

			return $result;
		}

		$fromCorrespondent = $this->getFromCorrespondent($channel, $message);
		if (!$fromCorrespondent)
		{
			$result->addError(new Error(Loc::getMessage('CRM_ACTIVITY_PROVIDER_SMS_WRONG_FROM')));

			return $result;
		}

		$additionalFields = $this->getAdditionalFields();
		$toCorrespondent = $this->getToCorrespondent($channel, $message, $additionalFields);
		if (!$toCorrespondent)
		{
			$result->addError(new Error(Loc::getMessage('CRM_ACTIVITY_PROVIDER_SMS_WRONG_TO')));

			return $result;
		}

		$facilitator = $this->createFacilitator($channel, $message);

		if (!$facilitator)
		{
			$result->addError(new Error(Loc::getMessage('CRM_ACTIVITY_PROVIDER_SMS_WRONG_CHANNEL')));

			return $result;
		}

		$sendResult = $facilitator
			->setFrom($fromCorrespondent)
			->setTo($toCorrespondent)
			->setAdditionalFields($additionalFields)
			->send()
		;

		if (!$sendResult->isSuccess())
		{
			$result->addErrors($sendResult->getErrors());
		}

		return $result;
	}

	private function getFromCorrespondent(Channel $channel, MessageDto $message): ?Channel\Correspondents\From
	{
		foreach ($channel->getFromList() as $fromListItem)
		{
			if ($fromListItem->getId() === $message->from)
			{
				return $fromListItem;
			}
		}

		return null;
	}

	private function getToCorrespondent(
		Channel $channel,
		MessageDto $message,
		array $additionalFields
	): ?Channel\Correspondents\To
	{
		foreach ($channel->getToList() as $toListItem)
		{
			$addressSource = $toListItem->getAddressSource();
			if (
				$addressSource->getEntityTypeId() !== $additionalFields['ENTITY_TYPE_ID']
				|| $addressSource->getEntityId() !== $additionalFields['ENTITY_ID']
			)
			{
				continue;
			}

			if ($toListItem->getAddress()->getValue() === $message->to)
			{
				return $toListItem;
			}
		}

		return null;
	}

	private function createFacilitator(Channel $channel, MessageDto $message): ?SendFacilitator
	{
		$senderCode = $channel->getSender()::getSenderCode();

		if ($senderCode === SmsManager::getSenderCode())
		{
			$facilitator = (new Sms($channel))->setMessageBody($message->body);

			if (!empty($message->template))
			{
				$facilitator->setMessageTemplate($message->template);
			}

			return $facilitator;
		}

		if ($senderCode === NotificationsManager::getSenderCode())
		{
			$facilitator = (new Notifications($channel))->setTemplateCode($message->template);

			if (!empty($message->placeholders))
			{
				$placeholders = [];
				foreach($message->placeholders as $placeholder)
				{
					$placeholders[$placeholder->name] = $placeholder->value;
				}
				$facilitator->setPlaceholders($placeholders);
			}

			return $facilitator;
		}

		return null;
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

	private function getAdditionalFields(): array
	{
		$message = $this->message;

		$comEntityId = $this->getComEntityItemIdentifier()->getEntityId();
		$comEntityTypeId = $this->getComEntityItemIdentifier()->getEntityTypeId();
		$bindings = $this->getBindings();

		$additionalFields = [
			'ACTIVITY_PROVIDER_TYPE_ID' => $this->getActivityProviderTypeId($message->senderId),
			'ENTITY_TYPE' => \CCrmOwnerType::ResolveName($comEntityTypeId),
			'ENTITY_TYPE_ID' => $comEntityTypeId,
			'ENTITY_ID' => $comEntityId,
			'BINDINGS' => $bindings,
			'ACTIVITY_AUTHOR_ID' => $this->responsibleId,
			'ACTIVITY_DESCRIPTION' => $message->body,
			'MESSAGE_TO' => $message->to,
			'ORIGINAL_TEMPLATE_ID' => $message->templateOriginalId,
		];

		$this->prepareOrderAdditionalFields($additionalFields, $bindings);
		$this->prepareDealAdditionalFields($additionalFields);

		if ($this->senderExtra->sentMessageTag)
		{
			$additionalFields['ASSOCIATED_MESSAGE_TAG'] = $this->senderExtra->sentMessageTag;
		}

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
			$additionalFields['CREATE_VIEWED_TIMELINE_ITEM'] = true;
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

	private function getActivityProviderTypeId(string $senderId): string
	{
		return SmsManager::isEdnaWhatsAppSendingEnabled($senderId)
			? \Bitrix\Crm\Activity\Provider\WhatsApp::PROVIDER_TYPE_WHATSAPP
			: \Bitrix\Crm\Activity\Provider\Sms::PROVIDER_TYPE_SMS;
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
