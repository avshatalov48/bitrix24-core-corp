<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Timeline;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Timeline\SignDocument\DocumentData;
use Bitrix\Crm\Timeline\SignDocument\MessageData;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Service\Container;

class SignDocument extends LogMessage
{
	protected ?DocumentData $documentData = null;
	protected ?MessageData $messageData = null;
	protected ?\Bitrix\Sign\Item\Document $signDocument = null;

	public function getType(): string
	{
		return 'SignDocumentLog';
	}

	public static function isActive(): bool
	{
		return \Bitrix\Crm\Settings\Crm::isDocumentSigningEnabled();
	}

	public function getIconCode(): ?string
	{
		
		$messageData = $this->loadMessageData();
		$isMail = true;
		if ($messageData)
		{
			$isMail = $messageData->getChannel()->getType() === Timeline\SignDocument\Channel::TYPE_EMAIL;
		}
		
		$titlesMap = [
			Timeline\SignDocument\Entry::TYPE_CATEGORY_CREATED => Icon::DOCUMENT,
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT => Icon::MAIL_OUTCOME,
			Timeline\SignDocument\Entry::TYPE_CATEGORY_VIEWED => Icon::VIEW,
			Timeline\SignDocument\Entry::TYPE_CATEGORY_PREPARED_TO_FILL => Icon::DOCUMENT,
			Timeline\SignDocument\Entry::TYPE_CATEGORY_FILLED => Icon::DOCUMENT,
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SIGNED => Icon::DOCUMENT,
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SIGN_COMPLETED => Icon::DOCUMENT,
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_REPEATEDLY => Icon::MAIL_OUTCOME,
			Timeline\SignDocument\Entry::TYPE_CATEGORY_INTEGRITY_SUCCESS => Icon::DOCUMENT,
			Timeline\SignDocument\Entry::TYPE_CATEGORY_INTEGRITY_FAILURE => Icon::DOCUMENT,
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_INTEGRITY_FAILURE => Icon::DOCUMENT,
			Timeline\SignDocument\Entry::TYPE_CATEGORY_PIN_SEND_LIMIT_REACHED => Icon::DOCUMENT,
			Timeline\SignDocument\Entry::TYPE_CATEGORY_NOTIFICATION_READ => Icon::VIEW,
			Timeline\SignDocument\Entry::TYPE_CATEGORY_NOTIFICATION_ERROR => $isMail ? Icon::MAIL_OUTCOME : Icon::IM,
			Timeline\SignDocument\Entry::TYPE_CATEGORY_NOTIFICATION_DELIVERED => $isMail ? Icon::MAIL_OUTCOME : Icon::IM,
		];
		return $titlesMap[$this->model->getTypeCategoryId()] ?? Icon::INFO;
	}

	public function getTitle(): ?string
	{
		$titlesMap = [
			Timeline\SignDocument\Entry::TYPE_CATEGORY_CREATED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_CREATE_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_VIEWED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_VIEW_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_PREPARED_TO_FILL => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_READY_TO_FILL_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_FILLED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_FILLED_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SIGN_COMPLETED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SIGNED_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_REPEATEDLY => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SENT_REPEATEDLY_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_INTEGRITY_SUCCESS => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_INTEGRITY_CHECK_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_INTEGRITY_FAILURE => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_INTEGRITY_CHECK_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_INTEGRITY_FAILURE => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SENT_INTEGRITY_FAILURE_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_PIN_SEND_LIMIT_REACHED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_PIN_SEND_LIMIT_REACHED_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_NOTIFICATION_ERROR => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_NOTIFICATION_ERROR'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_NOTIFICATION_DELIVERED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_NOTIFICATION_DELIVERED'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SIGN_CONFIGURATION_ERROR => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_CONFIGURE_ERROR_TITLE'),
		];
		$messageData = $this->loadMessageData();

		if ($messageData)
		{
			$type = $messageData->getChannel()->getType() === Timeline\SignDocument\Channel::TYPE_EMAIL
				? Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_EMAIL')
				: Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_PHONE');

			$titlesMap[Timeline\SignDocument\Entry::TYPE_CATEGORY_NOTIFICATION_READ] = Loc::getMessage(
				'CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_NOTIFICATION_READ',
				['%TYPE%' => $type,]
			);

			$titlesMap[Timeline\SignDocument\Entry::TYPE_CATEGORY_NOTIFICATION_ERROR] = Loc::getMessage(
				'CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_NOTIFICATION_ERROR',
				['%TYPE%' => $type,]
			);

			$titlesMap[Timeline\SignDocument\Entry::TYPE_CATEGORY_NOTIFICATION_DELIVERED] = Loc::getMessage(
				'CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_NOTIFICATION_DELIVERED',
				['%TYPE%' => $type,]
			);

			$titlesMap[Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT] = $messageData->getChannel()->getType() === Timeline\SignDocument\Channel::TYPE_EMAIL
				? Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_MAIL_SEND_TITLE')
				: Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SMS_SEND_TITLE');

			$member = Container::instance()->getMemberRepository()->getByUid($messageData->getRecipient()->getHash());

			$titlesMap[Timeline\SignDocument\Entry::TYPE_CATEGORY_SIGNED] = $member->party !== 1
				? Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SIGNED_BY_SIDE_TITLE')
				: Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SIGNED_BY_INITIATOR_TITLE');
		}

		return $titlesMap[$this->model->getTypeCategoryId()] ?? null;
	}

	public function getContentBlocks(): ?array
	{
		$blocks = [];
		$blocks['document'] = $this->getDocumentBlock();

		if (
			($this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT
			|| $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_REPEATEDLY
			)
			&& $this->getChannelContentBlock()
		)
		{
			$blocks[] = $this->getChannelContentBlock();
		}

		if ($this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_SIGN_CONFIGURATION_ERROR)
		{
			$blocks[] = $this->getErrorContentBlock();
		}

		if ($this->loadMessageData())
		{
			$blocks[] = $this->getSignerContentBlock();
		}

		if ($this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_PIN_SEND_LIMIT_REACHED)
		{
			$blocks[] = $this->getPinSendLimitContentBlock();
		}

		return $blocks;
	}

	private function getChannelContentBlock(): ?Layout\Body\ContentBlock
	{
		$messageData = $this->loadMessageData();
		if (!$messageData)
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_BLOCK_CHANNEL_TITLE'))
			->setContentBlock((new Layout\Body\ContentBlock\Text())
				->setValue($messageData->getChannel()->getType())
				->setColor('whatsapp')
				->setIsBold(true)
			)
		;
	}

	private function getErrorContentBlock(): ?Layout\Body\ContentBlock
	{
		$messageData = $this->loadMessageData();
		if (!$messageData)
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline()
			->setWordWrap()
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_CONFIGURE_ERROR'))
			->setContentBlock((new Layout\Body\ContentBlock\Text())
				->setTitle($messageData->getError()?->getMessage())
				->setValue($messageData->getError()?->getMessage())
				->setIsMultiline()
			)
		;
	}

	private function getPinSendLimitContentBlock(): Layout\Body\ContentBlock\Text
	{
		return (new Layout\Body\ContentBlock\Text)
			->setValue(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_BLOCK_TEXT_PIN_SEND_LIMIT_REACHED_CONTENT'))
			->setColor(Layout\Body\ContentBlock\Text::COLOR_BASE_70)
		;
	}

	private function getSignerContentBlock()
	{
		$messageData = $this->loadMessageData();
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_MESSAGE_SIGNER'))
			->setContentBlock((new Layout\Body\ContentBlock\Text())
				->setValue(
					implode(', ',
						[
							$messageData->getRecipient()->getTitle(),
							$messageData->getChannel()->getIdentifier(),
						]
					)
				));
	}

	private function getDocumentBlock()
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline()
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT'))
			->setContentBlock((new Layout\Body\ContentBlock\Text())
				->setValue(!empty($this->loadSignDocument()) ? $this->loadSignDocument()->title : ''));
	}

	protected function loadDocumentData(): DocumentData
	{
		if (!$this->documentData)
		{
			$this->documentData = DocumentData::createFromArray(
				$this->getHistoryItemModel()->get(
					Timeline\HistoryDataModel\Presenter\SignDocument::DOCUMENT_DATA_KEY
				)
			);
		}

		return $this->documentData;
	}

	protected function loadSignDocument(): ?\Bitrix\Sign\Item\Document
	{
		if (!$this->documentData)
		{
			$this->loadDocumentData();
		}

		if (!$this->signDocument)
		{
			$this->signDocument = Container::instance()->getDocumentRepository()->getById($this->documentData->getDocumentId());
		}

		return $this->signDocument;
	}

	protected function loadMessageData(): ?MessageData
	{
		if (!$this->messageData)
		{
			$data = $this->getHistoryItemModel()->get(
				Timeline\HistoryDataModel\Presenter\SignDocument::MESSAGE_DATA_KEY
			);

			if (!empty($data))
			{
				$this->messageData = MessageData::createFromArray($data);
			}
		}

		return $this->messageData;
	}
}
