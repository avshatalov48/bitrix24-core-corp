<?php

namespace Bitrix\Sign\Item;

use Bitrix\Main;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Attribute\Copyable;
use Bitrix\Sign\Type\Document\InitiatedByType;

class Document implements Contract\Item, Contract\Item\ItemWithOwner, Contract\Item\ItemWithCrmId, Contract\Item\TrackableItem
{
	use TrackableItemTrait;

	public function __construct(
		#[Copyable]
		public ?string $scenario = null,
		#[Copyable]
		public ?int $parties = null,
		public ?int $id = null,
		public ?string $title = null,
		public ?string $uid = null,
		public ?int $blankId = null,
		#[Copyable]
		public ?string $langId = null,
		public ?string $status = null,
		public ?string $initiator = null,
		#[Copyable]
		public ?string $entityType = null,
		public ?int $entityTypeId = null,
		public ?int $entityId = null,
		public ?int $resultFileId = null,
		#[Copyable]
		public ?int $version = null,
		public ?int $createdById = null,
		public ?int $groupId = null,
		#[Copyable]
		public ?string $companyUid = null,
		#[Copyable]
		public ?int $representativeId = null,
		#[Copyable]
		public ?string $scheme = null,
		public ?Main\Type\DateTime $dateCreate = null,
		public ?Main\Type\DateTime $dateSign = null,
		#[Copyable]
		public ?string $regionDocumentType = null,
		#[Copyable]
		public ?string $externalId = null,
		public ?int $stoppedById = null,
		public ?Main\Type\DateTime $externalDateCreate = null,
		#[Copyable]
		public ?string $providerCode = null,
		public ?int $templateId = null,
		public ?int $chatId = null,
		public ?int $createdFromDocumentId = null,
		public InitiatedByType $initiatedByType = InitiatedByType::COMPANY,
		#[Copyable]
		public ?int $hcmLinkCompanyId = null,
		public ?Main\Type\DateTime $dateStatusChanged = null,
	)
	{
		$this->initOriginal();
	}

	public function getCrmId(): int
	{
		return (int)$this->entityId;
	}

	public function getId(): int
	{
		return (int)$this->id;
	}

	public function getOwnerId(): int
	{
		return (int)$this->createdById;
	}

	public function isTemplated(): bool
	{
		return $this->templateId !== null;
	}

	public function isInitiatedByEmployee(): bool
	{
		return $this->initiatedByType === InitiatedByType::EMPLOYEE;
	}

	protected function getExcludedFromCopyProperties(): array
	{
		return [
			'id',
			'entityTypeId',
			'version',
			'createdById',
		];
	}
}
