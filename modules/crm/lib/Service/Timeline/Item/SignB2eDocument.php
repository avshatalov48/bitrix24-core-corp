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
use Bitrix\Main\Web\Uri;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\DocumentStatus;

class SignB2eDocument extends Configurable
{
	protected const BLOCK_DOCUMENT = 'document';
	protected const BLOCK_MY_SIGNER = 'mySigner';
	protected const BLOCK_SIGNERS = 'signers';
	protected const BLOCK_AUTHOR = 'author';
	protected const BLOCK_DATE = 'date';
	protected const BLOCK_FIELDS_COUNT = 'fieldsCount';
	protected const BLOCK_REQUEST = 'request';

	protected ?DocumentData $documentData = null;
	protected ?MessageData $messageData = null;
	protected ?\Bitrix\Sign\Item\Document $signDocument = null;

	public function getType(): string
	{
		return 'SignB2eDocument';
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
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_STARTED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_START_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CREATED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_CREATE_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_STOPPED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_STOPPED_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_DONE => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DONE_TITLE'),
		];

		return $titlesMap[$this->model->getTypeCategoryId()] ?? null;
	}

	public function getLogo(): ?Layout\Body\Logo
	{
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
			(new Layout\Action\JsEvent('SignB2eDocument:Open'))
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

		return $itemsMap[$this->model->getTypeCategoryId()] ?? Icon::DOCUMENT;
	}

	public function getTags(): ?array
	{
		$tags = null;

		if ($this->isCategoryCreated())
		{
			$tags = [
				new Layout\Header\Tag(
					Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_TAG_CREATED') ?? '',
					Layout\Header\Tag::TYPE_PRIMARY,
				),
			];
		}

		return $tags;
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
		$signDocument = $this->getSignDocument();

		$buttons = [];

		$inProcess = in_array($signDocument->status, [DocumentStatus::NEW, DocumentStatus::UPLOADED, DocumentStatus::READY,]);

		if ($signDocument)
		{
			$buttons['signingProcess'] = (new Layout\Footer\Button(
				Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_SIGNING_PROCESS') ?? '',
				!$inProcess ? Layout\Footer\Button::TYPE_PRIMARY : Layout\Footer\Button::TYPE_SECONDARY))
				->setAction($this->getShowSigningProcessAction());
		}

		return $buttons;
	}

	private function getShowSigningProcessAction(): ?Layout\Action
	{
		if (!\Bitrix\Crm\Activity\Provider\SignB2eDocument::isActive())
		{
			return null;
		}

		$signDocument = $this->getSignDocument();
		if (!$signDocument)
		{
			return null;
		}

		$uri = new Uri('/bitrix/components/bitrix/sign.document.list/slider.php');
		$uri->addParams([
			'site_id' => SITE_ID,
			'sessid' => bitrix_sessid_get(),
			'type' => 'document',
			'entity_id' => $signDocument->entityId,
		]);

		return
			(new Layout\Action\JsEvent($this->getType() . ':ShowSigningProcess'))
				->addActionParamString('processUri', $uri->getUri())
			;
	}

	protected function getBlockIdentifiers(): array
	{
		if ($this->isCategoryCreated())
		{
			return [
				static::BLOCK_DOCUMENT,
				static::BLOCK_MY_SIGNER,
				static::BLOCK_SIGNERS,
			];
		}

		if ($this->isCategoryStarted())
		{
			return [
				static::BLOCK_DOCUMENT,
				static::BLOCK_MY_SIGNER,
				static::BLOCK_SIGNERS,
			];
		}

		if ($this->isCategoryStopped())
		{
			return [
				static::BLOCK_DOCUMENT,
				static::BLOCK_MY_SIGNER,
				static::BLOCK_SIGNERS,
			];
		}

		if ($this->isCategoryDone())
		{
			return [
				static::BLOCK_DOCUMENT,
				static::BLOCK_MY_SIGNER,
				static::BLOCK_SIGNERS,
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
		if ($identifier === static::BLOCK_DATE)
		{
			return $this->getDateContentBlock();
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

		return null;
	}

	private function getAuthorContentBlock()
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_MESSAGE_AUTHOR'))
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
				->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_BLOCK_MY_SIGNER_TITLE'))
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
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_TITLE'))
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
					Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_BLOCK_SIGNER_TITLE',
					[
						'#INDEX#' => $index + 1,
					]
				))
				->setContentBlock($this->getSignerContentBlock($signer))
			;
		}

		return $line;
	}

	protected function getDateContentBlock(): ?Layout\Body\ContentBlock
	{
		if (!$this->getSignDocument())
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_BLOCK_DATE'))
			->setContentBlock((new Layout\Body\ContentBlock\Date())
				->setDate($this->getSignDocument()
					->getDateCreate()
					->toUserTime())
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
				->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_BLOCK_FIELDS_COUNT_TITLE'))
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
				->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_BLOCK_REQUEST_TITLE'))
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

	private function getSignDocument(): ?\Bitrix\Sign\Item\Document
	{
		if (!$this->signDocument)
		{
			$this->signDocument = Container::instance()->getDocumentRepository()->getById($this->getDocumentData()->getDocumentId());
		}

		return $this->signDocument;
	}

	protected function getDeleteConfirmationText(): string
	{
		return Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DELETE_CONFIRM');
	}

	protected function isCategoryCreated(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CREATED;
	}

	protected function isCategoryStarted(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_STARTED;
	}

	protected function isCategoryDone(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_DONE;
	}

	protected function isCategoryStopped(): bool
	{
		return $this->model->getTypeCategoryId() === Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_STOPPED;
	}
}
