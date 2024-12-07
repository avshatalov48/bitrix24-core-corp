<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Activity\Provider\BaseMessage;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Item\Model;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Client;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

class SmsStatus extends LogMessage
{
	private const WHATSAPP_PROVIDER_ID = 'ednaru';

	private bool $isSmsChannel;
	private array $messageInfo;

	public function __construct(Context $context, Model $model)
	{
		$this->isSmsChannel = !empty($model->getAssociatedEntityModel()?->get('SMS_INFO'));
		if ($this->isSmsChannel)
		{
			$this->messageInfo = $model->getAssociatedEntityModel()?->get('SMS_INFO') ?? [];
		}
		else
		{
			$messageInfo = $model->getAssociatedEntityModel()?->get('MESSAGE_INFO') ?? [];
			$this->messageInfo = $messageInfo['HISTORY_ITEMS'][0] ?? [];
		}

		parent::__construct($context, $model);
	}

	public function getType(): string
	{
		return 'SmsStatus';
	}

	public function getIconCode(): ?string
	{
		return match ($this->getStatus())
		{
			BaseMessage::MESSAGE_FAILURE => 'info',
			BaseMessage::MESSAGE_SUCCESS => 'comment',
			BaseMessage::MESSAGE_READ => 'view',
			default => parent::getIconCode(),
		};

	}

	public function getTitle(): ?string
	{
		$messenger = $this->messageInfo['PROVIDER_DATA']['DESCRIPTION'] ?? '';
		$isSmsChannel = $this->isSmsChannel;
		$isWhatsApp = $this->isSmsChannel && $this->messageInfo['senderId'] === self::WHATSAPP_PROVIDER_ID;

		return match ($this->getStatus())
		{
			BaseMessage::MESSAGE_FAILURE => $isSmsChannel
				? Loc::getMessage($isWhatsApp
					? 'CRM_TIMELINE_LOG_WHATSAPP_STATUS_TITLE_FAILURE'
					: 'CRM_TIMELINE_LOG_SMS_STATUS_TITLE_FAILURE'
				)
				: Loc::getMessage(
					'CRM_TIMELINE_LOG_MSG_STATUS_TITLE_FAILURE',
					['#MESSENGER#' => $messenger]
				),
			BaseMessage::MESSAGE_SUCCESS => $isSmsChannel
				? Loc::getMessage($isWhatsApp
					? 'CRM_TIMELINE_LOG_WHATSAPP_STATUS_TITLE_SUCCESS'
					: 'CRM_TIMELINE_LOG_SMS_STATUS_TITLE_SUCCESS'
				)
				: Loc::getMessage(
					'CRM_TIMELINE_LOG_MSG_STATUS_TITLE_SUCCESS',
					['#MESSENGER#' => $messenger]
				),
			BaseMessage::MESSAGE_READ => $isSmsChannel
				? Loc::getMessage($isWhatsApp
					? 'CRM_TIMELINE_LOG_WHATSAPP_STATUS_TITLE_READ'
					: 'CRM_TIMELINE_LOG_SMS_STATUS_TITLE_READ'
				)
				: Loc::getMessage(
					'CRM_TIMELINE_LOG_MSG_STATUS_TITLE_READ',
					['#MESSENGER#' => $messenger]
				),
			default => Loc::getMessage('CRM_TIMELINE_LOG_SMS_STATUS_TITLE_UNKNOWN'),
		};

	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$client = $this->buildClientBlock(Client::BLOCK_WITH_FORMATTED_VALUE, Loc::getMessage('CRM_TIMELINE_LOG_SMS_STATUS_RECIPIENT'));
		if ($client)
		{
			if ($client instanceof LineOfTextBlocks && $this->isSmsChannel)
			{
				$client->addContentBlock(
					'provider',
					(new Text())
						->setValue($this->messageInfo['senderShortName'] ?? '')
						->setColor(Text::COLOR_BASE_70)
				);
			}

			$result['recipient'] = $client;
		}

		return $result;
	}

	public function getTags(): ?array
	{
		$status = $this->getStatus();
		// render tags to FAILURE codes only
		if ($status !== BaseMessage::MESSAGE_FAILURE)
		{
			return null;
		}

		$statusTag = new Tag(Loc::getMessage('CRM_TIMELINE_LOG_TAG_SENDING_ERROR'), Tag::TYPE_FAILURE);

		$errorText = $this->isSmsChannel
			? ($this->messageInfo['errorText'] ?? '')
			: ($this->messageInfo['ERROR_MESSAGE'] ?? '');

		if (!empty($errorText))
		{
			$statusTag->setHint($errorText);
		}

		return [
			'status' => $statusTag,
		];
	}

	private function getStatus(): ?int
	{
		$activityData = $this->getModel()->getSettings()['ACTIVITY_DATA'];
		if (isset($activityData))
		{
			return $activityData['STATUS'] ?? null;
		}

		return null;
	}
}
