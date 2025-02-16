<?php

namespace Bitrix\Sign\Item\MyDocumentsGrid;

use Bitrix\Sign\Contract;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\DocumentStatus;

class Document implements Contract\Item
{
	public function __construct(
		public int $id,
		public string $title,
		public ?string $providerCode,
		public ?DateTime $signDate,
		public ?DateTime $sendDate,
		public ?DateTime $editDate,
		public ?DateTime $approvedDate,
		public ?DateTime $cancelledDate,
		public string $status,
		public Member $initiator,
		public InitiatedByType $initiatedByType,
		public ?int $stoppedById,
		public ?bool $someoneSigned = null,
	)
	{}

	public function isInitiatedByCompany(): bool
	{
		return $this->initiatedByType === InitiatedByType::COMPANY;
	}

	public function isInitiatedByEmployee(): bool
	{
		return $this->initiatedByType === InitiatedByType::EMPLOYEE;
	}

	public function isDocumentStopped(): bool
	{
		return $this->status === DocumentStatus::STOPPED;
	}
}