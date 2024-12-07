<?php

namespace Bitrix\Crm\MessageSender\SendFacilitator;

use Bitrix\Crm\Integration\MailManager;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\MessageSender\Channel;
use Bitrix\Crm\MessageSender\SendFacilitator;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;

final class Mail extends SendFacilitator
{
	private int $storageTypeId = StorageType::File;

	private array $attachmentIds = [];

	private ?string $messageSubject = null;
	private ?string $messageBody = null;
	private int $messageBodyContentType = \CCrmContentType::Html;

	private bool $addEmailSignature = true;

	public function __construct(Channel $channel)
	{
		if ($channel->getSender()::getSenderCode() !== MailManager::getSenderCode())
		{
			throw new ArgumentException('Channel should be from Mail sender');
		}

		parent::__construct($channel);
	}

	protected function getActivityProviderTypeId(): string
	{
		return \Bitrix\Crm\Activity\Provider\Email::TYPE_EMAIL_COMPRESSED;
	}

	protected function prepareMessageOptions(): array
	{
		return [
			'ATTACHMENTS_STORAGE_TYPE_ID' => $this->storageTypeId,
			'ATTACHMENTS_IDS' => array_unique($this->attachmentIds),
			'MESSAGE_SUBJECT' => $this->messageSubject,
			'MESSAGE_BODY' => $this->messageBody,
			'MESSAGE_BODY_CONTENT_TYPE' => $this->messageBodyContentType,
			'MESSAGE_FROM' => $this->getFrom()->getName(),
			'MAILBOX_ID' => $this->getFrom()->getId(),
			'ADD_EMAIL_SIGNATURE' => $this->addEmailSignature,
		];
	}

	protected function prepareMessageCommonOptions(): array
	{
		$options = parent::prepareMessageCommonOptions();

		unset($options['PHONE_NUMBER']);

		$options['EMAIL'] = $this->getTo()->getAddress()->getValue();

		return $options;
	}

	public function setAttachmentStorageType(int $storageTypeId): self
	{
		static $supportedStorageTypes = [
			StorageType::File,
			// StorageType::Disk, //todo disk support requires more work
		];

		if (!in_array($storageTypeId, $supportedStorageTypes, true))
		{
			throw new ArgumentOutOfRangeException('storageTypeId', $supportedStorageTypes);
		}

		$this->storageTypeId = $storageTypeId;

		return $this;
	}

	public function addAttachment(int $fileId): self
	{
		if ($fileId <= 0)
		{
			throw new ArgumentNullException('fileId');
		}

		$this->attachmentIds[] = $fileId;

		return $this;
	}

	public function setMessageSubject(string $subject): self
	{
		$this->messageSubject = $subject;

		return $this;
	}

	public function setAddEmailSignature(bool $addEmailSignature): self
	{
		$this->addEmailSignature = $addEmailSignature;

		return $this;
	}

	/**
	 * Attention! You may need to specify body content type - html, bb code or plain text.
	 * By default, body is considered html.
	 *
	 * @param string $body
	 * @return $this
	 */
	public function setMessageBody(string $body): self
	{
		$this->messageBody = $body;

		return $this;
	}

	/**
	 * @param int $messageBodyContentType - constant of \CCrmContentType
	 * @see \CCrmContentType
	 *
	 * @return $this
	 */
	public function setMessageBodyContentType(int $messageBodyContentType): self
	{
		if (!\CCrmContentType::IsDefined($messageBodyContentType))
		{
			throw new ArgumentOutOfRangeException('messageBodyContentType', \CCrmContentType::getAll());
		}

		$this->messageBodyContentType = $messageBodyContentType;

		return $this;
	}
}
