<?php

namespace Bitrix\Crm\MessageSender;

use Bitrix\Crm\MessageSender\Channel\Correspondents\From;
use Bitrix\Crm\MessageSender\Channel\Correspondents\To;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

abstract class SendFacilitator
{
	protected Channel $channel;

	protected ?From $from = null;
	protected ?To $to = null;
	protected array $additionalFields = [];

	public function __construct(Channel $channel)
	{
		$this->channel = $channel;
	}

	public function setFrom(From $from): self
	{
		$this->from = $from;

		return $this;
	}

	public function setTo(To $to): self
	{
		$this->to = $to;

		return $this;
	}

	final public function send(): Result
	{
		$channelCheckResult = $this->channel->checkChannel();
		if (!$channelCheckResult->isSuccess())
		{
			return $channelCheckResult;
		}

		$checkCommunicationsResult = $this->channel->checkCommunications();
		if (!$checkCommunicationsResult->isSuccess())
		{
			return $checkCommunicationsResult;
		}

		$fields = $this->channel->getSender()::makeMessageFields(
			array_merge(
				$this->prepareMessageOptions(),
				[
					'ACTIVITY_PROVIDER_TYPE_ID' => $this->getActivityProviderTypeId(),
				]
			),
			$this->prepareMessageCommonOptions(),
		);

		$result = $this->channel->getSender()::sendMessage($fields);
		if (!($result instanceof Result))
		{
			return (new Result())->addError(new Error(Loc::getMessage('CRM_MSSF_ERROR')));
		}

		$result->setData(
			array_merge(
				[
					'FROM' => $this->getFrom(),
					'TO' => $this->getTo(),
				],
				$result->getData(),
			),
		);

		return $result;
	}

	/**
	 * Set additional fields that will be available in the message (even after its send and delivery).
	 * Use it if you want to get some additional info in 'message sent' and 'message delivered' events.
	 *
	 * @param array $additionalFields
	 * @return SendFacilitator
	 */
	public function setAdditionalFields(array $additionalFields): self
	{
		$this->additionalFields = $additionalFields;

		return $this;
	}

	abstract protected function getActivityProviderTypeId(): string;

	abstract protected function prepareMessageOptions(): array;

	protected function prepareMessageCommonOptions(): array
	{
		return [
			'PHONE_NUMBER' => $this->getTo()->getAddress()->getValue(),
			'USER_ID' => $this->channel->getUserId(),
			'ADDITIONAL_FIELDS' => array_merge(
				$this->additionalFields,
				[
					'ROOT_SOURCE' => $this->getTo()->getRootSource()->toArray(),
					'ADDRESS_SOURCE' => $this->getTo()->getAddressSource()->toArray(),
					// to create activities on these items
					'BINDINGS' => [
						[
							'OWNER_TYPE_ID' => $this->getTo()->getRootSource()->getEntityTypeId(),
							'OWNER_ID' => $this->getTo()->getRootSource()->getEntityId(),
						],
						[
							'OWNER_TYPE_ID' => $this->getTo()->getAddressSource()->getEntityTypeId(),
							'OWNER_ID' => $this->getTo()->getAddressSource()->getEntityId(),
						],
					],
					// it's passed to an activity's COMMUNICATION field
					'ENTITY_TYPE' => \CCrmOwnerType::ResolveName($this->getTo()->getAddressSource()->getEntityTypeId()),
					'ENTITY_TYPE_ID' => $this->getTo()->getAddressSource()->getEntityTypeId(),
					'ENTITY_ID' => $this->getTo()->getAddressSource()->getEntityId(),
				],
			),
		];
	}

	final protected function getFrom(): ?From
	{
		if ($this->from)
		{
			return $this->from;
		}

		$firstAvailable = null;
		foreach ($this->channel->getFromList() as $from)
		{
			if (!$firstAvailable && $from->isAvailable())
			{
				$firstAvailable = $from;
			}

			if ($from->isDefault() && $from->isAvailable())
			{
				return $from;
			}
		}

		return $firstAvailable;
	}

	final protected function getTo(): ?To
	{
		if ($this->to)
		{
			return $this->to;
		}

		$toList = $this->channel->getToList();

		return array_shift($toList);
	}
}
