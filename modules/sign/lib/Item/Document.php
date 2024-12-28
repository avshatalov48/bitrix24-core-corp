<?php

namespace Bitrix\Sign\Item;

use Bitrix\Main;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\B2e\RequiredFieldsCollection;
use Bitrix\Sign\Type\Document\InitiatedByType;

class Document implements Contract\Item, Contract\Item\ItemWithOwner, Contract\Item\ItemWithCrmId
{
	public function __construct(
		public ?string $scenario = null,
		public ?int $parties = null,
		public ?int $id = null,
		public ?string $title = null,
		public ?string $uid = null,
		public ?int $blankId = null,
		public ?string $langId = null,
		public ?string $status = null,
		public ?string $initiator = null,
		public ?string $entityType = null,
		public ?int $entityTypeId = null,
		public ?int $entityId = null,
		public ?int $resultFileId = null,
		public ?int $version = null,
		public ?int $createdById = null,
		public ?string $companyUid = null,
		public ?int $representativeId = null,
		public ?string $scheme = null,
		public ?Main\Type\DateTime $dateCreate = null,
		public ?Main\Type\DateTime $dateSign = null,
		public ?string $regionDocumentType = null,
		public ?string $externalId = null,
		public ?int $stoppedById = null,
		public ?Main\Type\DateTime $externalDateCreate = null,
		public ?string $providerCode = null,
		public ?int $templateId = null,
		public ?int $chatId = null,
		public ?int $createdFromDocumentId = null,
		public InitiatedByType $initiatedByType = InitiatedByType::COMPANY,
		public ?int $hcmLinkCompanyId = null,
		public ?Main\Type\DateTime $dateStatusChanged = null,
	) {}

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
}
