<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Timeline;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Timeline\HistoryDataModel\Presenter\SignDocumentLog;
use Bitrix\Crm\Timeline\SignDocument\DocumentData;
use Bitrix\Crm\Timeline\SignDocument\MessageData;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Document;

class SignDocument extends LogMessage
{
	protected ?DocumentData $documentData = null;
	protected ?MessageData $messageData = null;
	protected ?Document $signDocument = null;



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
		$titlesMap = [
			Timeline\SignDocument\Entry::TYPE_CATEGORY_CREATED => 'document',
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT => 'mail-outcome',
			Timeline\SignDocument\Entry::TYPE_CATEGORY_VIEWED => 'document',
			Timeline\SignDocument\Entry::TYPE_CATEGORY_PREPARED_TO_FILL => 'document',
			Timeline\SignDocument\Entry::TYPE_CATEGORY_FILLED => 'document',
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SIGNED => 'document',
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SIGN_COMPLETED => 'document',
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_REPEATEDLY => 'mail-outcome',
			Timeline\SignDocument\Entry::TYPE_CATEGORY_INTEGRITY_SUCCESS => 'document',
			Timeline\SignDocument\Entry::TYPE_CATEGORY_INTEGRITY_FAILURE => 'document',
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_INTEGRITY_FAILURE => 'document',
		];

		return $titlesMap[$this->model->getTypeCategoryId()] ?? 'info';
	}

	public function getTitle(): ?string
	{
		$titlesMap = [
			Timeline\SignDocument\Entry::TYPE_CATEGORY_CREATED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_CREATE_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SEND_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_VIEWED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_VIEW_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_PREPARED_TO_FILL => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_READY_TO_FILL_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_FILLED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_FILLED_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SIGNED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SIGNED_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SIGN_COMPLETED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SIGNED_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_REPEATEDLY => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SENT_REPEATEDLY_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_INTEGRITY_SUCCESS => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_INTEGRITY_CHECK_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_INTEGRITY_FAILURE => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_INTEGRITY_CHECK_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_INTEGRITY_FAILURE => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SENT_INTEGRITY_FAILURE_TITLE'),
		];

		return $titlesMap[$this->model->getTypeCategoryId()] ?? null;
	}

	public function getContentBlocks(): ?array
	{
		$blocks = [];
		$blocks[] = $this->getDocumentBlock();

		if (
			$this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT
			|| $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_REPEATEDLY
		)
		{
			$blocks[] = $this->getMailSubjectContentBlock();
			$blocks[] = $this->getChannelContentBlock();
		}

		if ($this->loadMessageData())
		{
			$blocks[] = $this->getAuthorContentBlock();
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

	private function getMailSubjectContentBlock()
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_MESSAGE_THEME'))
			->setContentBlock((new Layout\Body\ContentBlock\Text())
				->setValue($this->loadMessageData()->getSubject()));
	}

	private function getIntegrityCheckedBlock()
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_MESSAGE_INTEGRITY_CHECKED'))
			->setContentBlock((new Layout\Body\ContentBlock\Text())
				->setValue(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_MESSAGE_INTEGRITY_STATE_'
					. $this->loadMessageData()->getIntegrityState())));
	}

	private function getAuthorContentBlock()
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_MESSAGE_AUTHOR'))
			->setContentBlock((new Layout\Body\ContentBlock\Text())
				->setValue($this->loadMessageData()->getRecipient()->getTitle()));
	}

	private function getDocumentBlock()
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT'))
			->setContentBlock((new Layout\Body\ContentBlock\Text())
				->setValue(!empty($this->loadSignDocument()) ? $this->loadSignDocument()->getTitle() : ''));
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

	protected function loadSignDocument(): ?Document
	{
		if (!$this->documentData)
		{
			$this->loadDocumentData();
		}

		if (!$this->signDocument)
		{
			$this->signDocument = Document::getById($this->documentData->getDocumentId());
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
