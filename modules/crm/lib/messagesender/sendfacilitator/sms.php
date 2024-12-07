<?php

namespace Bitrix\Crm\MessageSender\SendFacilitator;

use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\MessageSender\Channel;
use Bitrix\Crm\MessageSender\SendFacilitator;
use Bitrix\Main\ArgumentException;

final class Sms extends SendFacilitator
{
	private ?string $messageBody = null;
	private ?string $messageTemplate = null;

	public function __construct(Channel $channel)
	{
		if ($channel->getSender()::getSenderCode() !== SmsManager::getSenderCode())
		{
			throw new ArgumentException('Channel should be from SMS sender');
		}

		parent::__construct($channel);
	}

	protected function getActivityProviderTypeId(): string
	{
		return SmsManager::isEdnaWhatsAppSendingEnabled($this->channel->getId())
			? \Bitrix\Crm\Activity\Provider\WhatsApp::PROVIDER_TYPE_WHATSAPP
			: \Bitrix\Crm\Activity\Provider\Sms::PROVIDER_TYPE_SMS
		;
	}

	public function setMessageBody(string $body): self
	{
		$this->messageBody = $body;

		return $this;
	}

	public function setMessageTemplate(string $template): self
	{
		$this->messageTemplate = $template;

		return $this;
	}

	protected function prepareMessageOptions(): array
	{
		$options = [
			'SENDER_ID' => $this->channel->getId(),
			'MESSAGE_FROM' => $this->getFrom()->getId(),
			'MESSAGE_BODY' => $this->messageBody,
		];

		if (!empty($this->messageTemplate))
		{
			$options['MESSAGE_TEMPLATE'] = $this->messageTemplate;
		}

		return $options;
	}
}
