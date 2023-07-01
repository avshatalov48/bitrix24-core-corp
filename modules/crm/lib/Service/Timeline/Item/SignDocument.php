<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Common\Logo;
use Bitrix\Crm\Timeline;
use Bitrix\Crm\Timeline\HistoryDataModel\Presenter;
use Bitrix\Crm\Timeline\SignDocument\DocumentData;
use Bitrix\Crm\Timeline\SignDocument\MessageData;
use Bitrix\Crm\Timeline\SignDocument\Signer;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Document;

class SignDocument extends Configurable
{
	protected const BLOCK_DOCUMENT = 'document';
	protected const BLOCK_MY_SIGNER = 'mySigner';
	protected const BLOCK_SIGNERS = 'signers';
	protected const BLOCK_MAIL_SUBJECT = 'mailSubject';
	protected const BLOCK_AUTHOR= 'author';
	protected const BLOCK_DATE= 'date';
	protected const BLOCK_RECIPIENT = 'recipient';
	protected const BLOCK_CHANNEL = 'channel';
	protected const BLOCK_FIELDS_COUNT = 'fieldsCount';
	protected const BLOCK_REQUEST = 'request';

	protected ?DocumentData $documentData = null;
	protected ?MessageData $messageData = null;
	protected ?Document $signDocument = null;

	public function getType(): string
	{
		return 'SignDocument';
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	public static function isActive(): bool
	{
		return \Bitrix\Crm\Settings\Crm::isDocumentSigningEnabled();
	}

	public function getTitle(): ?string
	{
		$titlesMap = [
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SEND_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SIGNED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SIGNED_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SIGN_COMPLETED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SIGNED_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_FINAL => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SENT_FINAL_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_COMPLETED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_COMPLETED_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_REQUESTED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_REQUESTED_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_REPEATEDLY => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SENT_REPEATEDLY_TITLE'),
			Timeline\SignDocument\Entry::TYPE_CATEGORY_PRINTED_FORM => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_PRINTED_FORM_TITLE_MSGVER_1'),
		];

		$messageData = $this->getMessageData();

		if ($messageData)
		{
			$titlesMap[Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT] =
				$messageData->getChannel()->getType() === Timeline\SignDocument\Channel::TYPE_EMAIL
				? Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_MAIL_SEND_TITLE')
				: Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_SMS_SEND_TITLE');
		}

		return $titlesMap[$this->model->getTypeCategoryId()] ?? null;
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		$channel = $this->getMessageData() && $this->messageData->getChannel() === 'phone'
			? Logo::CHANNEL_WHATSAPP
			: Logo::MAIL_OUTCOME
		;

		$logosMap = [
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT =>
				[ 'type' => $channel, 'subType' => 'arrow-outgoing',],
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SIGNED =>
				['type' => Logo::DOCUMENT, 'subType' => 'sign',],
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SIGN_COMPLETED =>
				['type' => Logo::DOCUMENT, 'subType' => 'sign',],
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_FINAL => $channel,
			Timeline\SignDocument\Entry::TYPE_CATEGORY_COMPLETED =>
				['type' => Logo::DOCUMENT, 'subType' => 'double-check',],
			Timeline\SignDocument\Entry::TYPE_CATEGORY_PRINTED_FORM =>
				['type' => Logo::DOCUMENT, 'subType' => 'search',],
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_REPEATEDLY => $channel,
		];

		$code = $logosMap[$this->model->getTypeCategoryId()] ?? Logo::DOCUMENT;
		$logo = Logo::getInstance($code['type'] ?? $code)->createLogo();

		if ($code['subType'] ?? false)
		{
			$logo->setAdditionalIconCode($code['subType']);
		}

		if (!$this->getSignDocument())
		{
			return $logo;
		}
		return $logo->setAction(
			(new Layout\Action\JsEvent('SignDocument:Open'))
			->addActionParamString('documentId', $this->getDocumentData()->getDocumentId())
			->addActionParamString('memberHash', $this->getDocumentData()->getMemberHash())
		)
		;
	}

	public function getIconCode(): string
	{
		$channel = (
			$this->getMessageData() && $this->messageData->getChannel() === 'whatsapp'
				? Icon::IM
				: Icon::MAIL_OUTCOME
		);

		$itemsMap = [
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT => $channel,
			Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_REPEATEDLY => $channel,
		];

		return $itemsMap[$this->model->getTypeCategoryId()] ?? Icon::DOCUMENT;

	}

	public function getTags(): ?array
	{
		$tags = null;

		if ($this->isCategoryCreated())
		{
			$tags = [
				new Layout\Header\Tag(
					Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_TAG_CREATED') ?? '',
					Layout\Header\Tag::TYPE_PRIMARY,
				),
			];
		}
		elseif (
			$this->isCategorySent()
			|| $this->isCategorySentFinal()
			|| $this->isCategorySentRepeatedly()
			|| $this->isCategorySentIntegrityFailure()
		)
		{
			$tag = $this->getMessageStatusTag();
			if ($tag)
			{
				$tags = [$tag];
			}
		}
		elseif ($this->isCategoryViewed())
		{
			$tags = [
				new Layout\Header\Tag(
					Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_TAG_VIEWED') ?? '',
					Layout\Header\Tag::TYPE_SUCCESS,
				),
			];
		}
		elseif ($this->isCategoryPreparedToFill())
		{
			$tags = [
				new Layout\Header\Tag(
					Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_TAG_PREPARED_TO_FILL') ?? '',
					Layout\Header\Tag::TYPE_SECONDARY,
				),
			];
		}
		elseif ($this->isCategoryFilled())
		{
			$tags = [
				new Layout\Header\Tag(
					Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_TAG_FILLED') ?? '',
					Layout\Header\Tag::TYPE_SUCCESS,
				),
			];
		}
		elseif ($this->isCategorySigned())
		{
			$messageData = $this->getMessageData();
			$signDocument = $this->getSignDocument();
			if ($messageData && $signDocument)
			{
				$member = $signDocument->getMemberByHash($messageData->getRecipient()->getHash());

				$title = $member->isInitiator()
					? Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_TAG_INITIATOR_SIGNED')
					: Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_TAG_SIDE_SIGNED');
			}
			$title = $title ?? Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_TAG_SIGNED');
			$tags = [
				new Layout\Header\Tag(
					$title ?? '',
					Layout\Header\Tag::TYPE_PRIMARY,
				),
			];
		}
		elseif ($this->isCategorySignCompleted())
		{
			$tags = [
				new Layout\Header\Tag(
					Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_TAG_SIGN_COMPLETED') ?? '',
					Layout\Header\Tag::TYPE_SUCCESS,
				),
			];
		}
		elseif ($this->isCategoryIntegritySuccess())
		{
			$tags = [
				new Layout\Header\Tag(
					Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_TAG_INTEGRITY_SUCCESS') ?? '',
					Layout\Header\Tag::TYPE_SUCCESS,
				),
			];
		}
		elseif ($this->isCategoryIntegrityFailure())
		{
			$tags = [
				new Layout\Header\Tag(
					Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_TAG_INTEGRITY_FAILURE') ?? '',
					Layout\Header\Tag::TYPE_FAILURE,
				),
			];
		}

		return $tags;
	}

	private function getMessageStatusTag(): ?Layout\Header\Tag
	{
		$messageData = $this->getMessageData();
		if (!$messageData)
		{
			return null;
		}
		if ($messageData->isStatusSent())
		{
			return new Layout\Header\Tag(
				Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_TAG_SENT') ?? '',
				Layout\Header\Tag::TYPE_SECONDARY,
			);
		}
		if ($messageData->isStatusDelivered())
		{
			return new Layout\Header\Tag(
				Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_TAG_DELIVERED') ?? '',
				Layout\Header\Tag::TYPE_SUCCESS,
			);
		}

		return new Layout\Header\Tag(
			Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_TAG_ERROR') ?? '',
			Layout\Header\Tag::TYPE_FAILURE,
		);
	}

	public function getContentBlocks(): ?array
	{
		$blocks = [];

		foreach ($this->getBlockIdentifiers() as $blockIdentifier)
		{
			if ($blockIdentifier === static::BLOCK_SIGNERS)
			{
				$signerBlocks = $this->getSignersContentBlocks();
				if (is_array($signerBlocks))
				{
					$blocks += $signerBlocks;
				}
				continue;
			}
			$block = $this->getContentBlock($blockIdentifier);
			if ($block)
			{
				$blocks[$blockIdentifier] = $block;
			}
		}

		return $blocks;
	}

	public function getButtons(): ?array
	{
		$messageData = $this->getMessageData();
		$recipientHash = $this->getMessageData() ?
			($this->getMessageData()->getRecipient()->getHash() ?? '')
			: '';

		$openButton = $this->getSignDocument() ? (new Layout\Footer\Button(
			Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_BUTTON_OPEN') ?? '',
			Layout\Footer\Button::TYPE_PRIMARY,
		))->setAction((new Layout\Action\JsEvent('SignDocument:Open'))
			->addActionParamInt('documentId', $this->getDocumentData()->getDocumentId())
			->addActionParamString('memberHash', $recipientHash))
		: null;

		$buttons = [];


		if ($this->isCategoryPrintedForm() && $openButton)
		{
			$buttons['open'] = $openButton;

		}
		elseif ($this->isCategoryCreated()
			&& !$this->isCategorySent())
		{
			if ($openButton)
			{
				$buttons['open'] = $openButton;
			}

			$buttons['modify'] = (new Layout\Footer\Button(
				Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_BUTTON_MODIFY') ?? '',
				Layout\Footer\Button::TYPE_SECONDARY,
			))
				->setAction((new Layout\Action\JsEvent('SignDocument:Modify'))
					->addActionParamInt('documentId', $this->getDocumentData()->getDocumentId()))
				->setHideIfReadonly(true)
			;
		}
		elseif (
			($this->isCategorySent()
			|| $this->isCategorySentRepeatedly()
			|| $this->isCategorySentIntegrityFailure())
			&& (
				!$this->isCategorySentFinal()
				&& $messageData
				&& $messageData->getRecipient()
				&& $this->getSignDocument()
				&& !$this->getSignDocument()->canBeChanged()
				&& !$this->getSignDocument()->isSignedByMember($messageData->getRecipient()->getHash())
			)
		)
		{
			$buttons['resend'] = (new Layout\Footer\Button(
				Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_BUTTON_RESEND') ?? '',
				Layout\Footer\Button::TYPE_SECONDARY,
			))->setAction(
				(new Layout\Action\JsEvent('SignDocument:Resend'))
					->addActionParamString('buttonId', 'resend')
					->addActionParamInt('documentId', $this->getDocumentData()->getDocumentId())
					->addActionParamString('recipientHash', $recipientHash)
					->setAnimation(Layout\Action\Animation::showLoaderForBlock())
			);
		}
		elseif (
			$this->isCategoryViewed()
			|| $this->isCategoryPreparedToFill()
			|| $this->isCategoryFilled()
			|| $this->isCategorySigned()
			|| $this->isCategoryRequested()
			|| $this->isCategoryIntegritySuccess()
			|| $this->isCategoryIntegrityFailure()
			|| $this->isCategorySignCompleted()
			|| $this->isCategoryCompleted()
		)
		{
			if ($openButton)
			{
				$buttons['open'] = $openButton;
			}

			$buttons['download'] = (new Layout\Footer\Button(
				Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_BUTTON_DOWNLOAD') ?? '',
				Layout\Footer\Button::TYPE_SECONDARY,
			))->setAction(
				(new Layout\Action\JsEvent('SignDocument:Download'))
					->addActionParamString('documentHash', $this->getDocumentData()->getDocumentHash())
					->addActionParamString('memberHash', $recipientHash)
					->setAnimation(Layout\Action\Animation::showLoaderForBlock())
			);
		}

		return $buttons;
	}

	protected function getBlockIdentifiers(): array
	{
		if ($this->isCategoryCreated() || $this->isCategorySignCompleted() || $this->isCategoryCompleted())
		{
			return [
				static::BLOCK_DOCUMENT,
				static::BLOCK_MY_SIGNER,
				static::BLOCK_SIGNERS,
			];
		}
		if ($this->isCategorySent())
		{
			return [
				static::BLOCK_DOCUMENT,
				static::BLOCK_RECIPIENT,
				static::BLOCK_MY_SIGNER,
			];
		}
		if ($this->isCategoryViewed())
		{
			return [
				static::BLOCK_DOCUMENT,
				static::BLOCK_MY_SIGNER,
				static::BLOCK_RECIPIENT,
			];
		}
		if ($this->isCategoryPreparedToFill() || $this->isCategoryFilled())
		{
			return [
				static::BLOCK_DOCUMENT,
				static::BLOCK_RECIPIENT,
				static::BLOCK_FIELDS_COUNT,
				static::BLOCK_MY_SIGNER,
			];
		}
		if ($this->isCategorySigned())
		{
			return [
				static::BLOCK_DOCUMENT,
				static::BLOCK_RECIPIENT,
				static::BLOCK_MY_SIGNER,
			];
		}
		if (
			$this->isCategorySentFinal()
			|| $this->isCategoryIntegritySuccess()
			|| $this->isCategoryIntegrityFailure()
			|| $this->isCategorySentIntegrityFailure()
		)
		{
			return [
				static::BLOCK_DOCUMENT,
				static::BLOCK_CHANNEL,
				static::BLOCK_RECIPIENT,
			];
		}
		if ($this->isCategoryRequested())
		{
			return [
				static::BLOCK_DOCUMENT,
				static::BLOCK_CHANNEL,
				static::BLOCK_REQUEST,
			];
		}
		if ($this->isCategoryPrintedForm())
		{
			return [
				static::BLOCK_DOCUMENT,
				static::BLOCK_DATE,
				static::BLOCK_MY_SIGNER,
			];
		}

		return [];
	}

	protected function getContentBlock(string $identifier): ?Layout\Body\ContentBlock
	{
		if ($identifier === static::BLOCK_DOCUMENT)
		{
			return $this->getDocumentBlock();
		}
		if ($identifier === static::BLOCK_MY_SIGNER)
		{
			return $this->getMySignerContentBlock();
		}
		if ($identifier === static::BLOCK_RECIPIENT)
		{
			return $this->getRecipientContentBlock();
		}
		if ($identifier === static::BLOCK_DATE)
		{
			return $this->getDateContentBlock();
		}
		if ($identifier === static::BLOCK_CHANNEL)
		{
			return $this->getChannelContentBlock();
		}
		if ($identifier === static::BLOCK_FIELDS_COUNT)
		{
			return $this->getFieldsCountContentBlock();
		}
		if ($identifier === static::BLOCK_REQUEST)
		{
			return $this->getRequestContentBlock();
		}
		if ($identifier === static::BLOCK_AUTHOR)
		{
			return $this->getAuthorContentBlock();
		}
		if ($identifier === static::BLOCK_MAIL_SUBJECT)
		{
			return $this->getMailSubjectContentBlock();
		}

		return null;
	}

	private function getMailSubjectContentBlock()
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_MESSAGE_MAIL_SUBJECT'))
			->setContentBlock((new Layout\Body\ContentBlock\Text())
				->setValue($this->getMessageData()->getSubject()));
	}

	private function getAuthorContentBlock()
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_MESSAGE_AUTHOR'))
			->setContentBlock((new Layout\Body\ContentBlock\Text())
				->setValue($this->getMessageData()->getRecipient()->getTitle()));
	}

	protected function getMySignerContentBlock(): ?Layout\Body\ContentBlock
	{
		$mySigner = $this->getDocumentData()->getMySigner();
		if ($mySigner)
		{
			return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
				->setInline(false)
				->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_BLOCK_MY_SIGNER_TITLE'))
				->setContentBlock($this->getSignerContentBlock($mySigner))
			;
		}

		return null;
	}

	protected function getDocumentBlock()
	{
		if (!$this->getAssociatedEntityModel())
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline()
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_TITLE'))
			->setContentBlock((new Layout\Body\ContentBlock\Text())
				->setValue($this->getAssociatedEntityModel()->get('TITLE')));
	}

	protected function getSignerContentBlock(Signer $signer, string $value = ''): Layout\Body\ContentBlock
	{
		return (new Layout\Body\ContentBlock\Text())
			->setValue(
				!empty($value) ? implode(', ',
					[
						$signer->getTitle(),
						$value,
					]
				) : $signer->getTitle()
			)
		;
	}

	protected function getSignersContentBlocks(): ?array
	{
		$signers = $this->getDocumentData()->getSigners();
		if (empty($signers))
		{
			return null;
		}

		$line = [];

		foreach ($signers as $index => $signer)
		{
			$line[static::BLOCK_SIGNERS . '_' . $index] = (new Layout\Body\ContentBlock\ContentBlockWithTitle())
				->setInline(false)
				->setTitle(
					Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_BLOCK_SIGNER_TITLE',
					[
						'#INDEX#' => $index + 1,
					]
				))
				->setContentBlock($this->getSignerContentBlock($signer))
			;
		}

		return $line;
	}

	protected function getRecipientContentBlock(): ?Layout\Body\ContentBlock
	{
		$messageData = $this->getMessageData();
		if (!$messageData)
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_BLOCK_RECIPIENT_TITLE'))
			->setContentBlock($this->getSignerContentBlock(
				$messageData->getRecipient(),
				$messageData->getChannel()->getIdentifier()
			))
		;
	}

	protected function getDateContentBlock(): ?Layout\Body\ContentBlock
	{
		if (!$this->getSignDocument())
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_BLOCK_DATE'))
			->setContentBlock((new Layout\Body\ContentBlock\Date())
				->setDate($this->getSignDocument()
					->getDateCreate()
					->toUserTime())
			)
		;
	}

	protected function getChannelContentBlock(): ?Layout\Body\ContentBlock
	{
		$messageData = $this->getMessageData();
		if (!$messageData)
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_BLOCK_CHANNEL_TITLE'))
			->setContentBlock((new Layout\Body\ContentBlock\Text())
				->setValue($messageData->getChannel()->getType())
				->setColor('green')
				->setIsBold(true)
			)
		;
	}

	protected function getFieldsCountContentBlock(): ?Layout\Body\ContentBlock
	{
		$fieldsToCount = $this->getDocumentData()->getFieldsCount();
		if ($fieldsToCount > 0)
		{
			return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
				->setInline(true)
				->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_BLOCK_FIELDS_COUNT_TITLE'))
				->setContentBlock((new Layout\Body\ContentBlock\Text())
					->setValue($fieldsToCount)
				)
			;
		}

		return null;
	}

	protected function getRequestContentBlock(): ?Layout\Body\ContentBlock
	{
		$messageData = $this->getMessageData();
		if ($messageData)
		{
			return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
				->setInline(false)
				->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_BLOCK_REQUEST_TITLE'))
				->setContentBlock(
					$this->getSignerContentBlock($messageData->getRecipient())
				)
			;
		}

		return null;
	}

	protected function getDocumentData(): DocumentData
	{
		if (!$this->documentData)
		{
			$this->documentData = DocumentData::createFromArray(
				$this->getHistoryItemModel()->get(
					Presenter\SignDocument::DOCUMENT_DATA_KEY
				)
			);
		}

		return $this->documentData;
	}

	protected function getMessageData(): ?MessageData
	{
		if (!$this->messageData)
		{
			$data = $this->getHistoryItemModel()->get(
				Presenter\SignDocument::MESSAGE_DATA_KEY
			);
			if (!empty($data))
			{
				$this->messageData = MessageData::createFromArray($data);
			}
		}

		return $this->messageData;
	}

	private function getSignDocument(): ?Document
	{
		if (!$this->signDocument)
		{
			$this->signDocument = Document::getById($this->getDocumentData()->getDocumentId());
		}

		return $this->signDocument;
	}

	protected function getDeleteConfirmationText(): string
	{
		return Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_DELETE_CONFIRM');
	}
	protected function isCategoryCreated(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_CREATED;
	}

	protected function isCategorySent(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT;
	}

	protected function isCategoryViewed(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_VIEWED;
	}

	protected function isCategoryPreparedToFill(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_PREPARED_TO_FILL;
	}

	protected function isCategoryFilled(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_FILLED;
	}

	protected function isCategorySigned(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_SIGNED;
	}

	protected function isCategorySignCompleted(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_SIGN_COMPLETED;
	}

	protected function isCategorySentFinal(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_FINAL;
	}

	protected function isCategoryCompleted(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_COMPLETED;
	}

	protected function isCategoryRequested(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_REQUESTED;
	}

	protected function isCategorySentRepeatedly(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_REPEATEDLY;
	}

	protected function isCategoryPrintedForm(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_PRINTED_FORM;
	}

	protected function isCategoryIntegritySuccess(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_INTEGRITY_SUCCESS;
	}

	protected function isCategoryIntegrityFailure(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_INTEGRITY_FAILURE;
	}

	protected function isCategorySentIntegrityFailure(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignDocument\Entry::TYPE_CATEGORY_SENT_INTEGRITY_FAILURE;
	}
}
