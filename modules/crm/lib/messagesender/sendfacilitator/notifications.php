<?php

namespace Bitrix\Crm\MessageSender\SendFacilitator;

use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\MessageSender\Channel;
use Bitrix\Crm\MessageSender\SendFacilitator;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;

final class Notifications extends SendFacilitator
{
	private ?string $templateCode = null;
	private ?string $messageTemplate = null;
	/** @var Array<string, mixed>  */
	private array $placeholders = [];
	private $languageId;

	public function __construct(Channel $channel)
	{
		if ($channel->getSender()::getSenderCode() !== NotificationsManager::getSenderCode())
		{
			throw new ArgumentException('Channel should be from Notifications sender');
		}

        $this->languageId = Application::getInstance()->getLicense()->getRegion() ?? LANGUAGE_ID;

		parent::__construct($channel);
	}

	protected function getActivityProviderTypeId(): string
	{
		return \Bitrix\Crm\Activity\Provider\Notification::PROVIDER_TYPE_NOTIFICATION;
	}

	public function setTemplateCode(string $templateCode): self
	{
		$this->templateCode = $templateCode;

		return $this;
	}

	public function setPlaceholders(array $placeholders): self
	{
		$this->placeholders = $placeholders;

		return $this;
	}

	public function setLanguageId(string $languageId): self
	{
		$this->languageId = $languageId;

		return $this;
	}

	protected function prepareMessageOptions(): array
	{
		return [
			'TEMPLATE_CODE' => $this->templateCode,
			'PLACEHOLDERS' => $this->placeholders,
			'LANGUAGE_ID' => $this->languageId,
		];
	}
}
