<?php

namespace Bitrix\Sign\Item;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Helper\CloneHelper;
use Bitrix\Sign\Item\Member\Reminder;
use Bitrix\Sign\Type\Member\Notification\ReminderType;
use Bitrix\Sign\Type\MemberStatus;
use Bitrix\Sign\Type\ProcessingStatus;

class Member implements Contract\Item, Contract\Item\TrackableItem
{
	use TrackableItemTrait;

	public Reminder $reminder;

	public function __construct(
		public ?int $documentId = null,
		public ?int $party = null,
		public ?int $id = null,
		public ?string $uid = null,
		public string $status = MemberStatus::WAIT,
		public string $processingStatus = ProcessingStatus::WAIT,
		public ?string $name = null,
		public ?string $companyName = null,
		public ?string $channelType = null,
		public ?string $channelValue = null,
		public ?int $signedFileId = null,
		public ?DateTime $dateSigned = null,
		public ?DateTime $dateCreated = null,
		public ?string $entityType = null,
		public ?int $entityId = null,
		public ?int $presetId = null,
		public ?int $signatureFileId = null,
		public ?int $stampFileId = null,
		public ?string $role = null,
		public ?int $configured = null,
		?Reminder $reminder = null,
		public ?DateTime $dateSend = null,
		public ?int $employeeId = null,
		public ?int $hcmLinkJobId = null,
		public ?DateTime $dateStatusChanged = null,
	)
	{
		$this->reminder = $reminder ?? new Reminder(
			lastSendDate: null,
			plannedNextSendDate: null,
			completed: false,
			type: ReminderType::NONE,
			startDate: null,
		);
		$this->initOriginal();
	}

	public function __clone()
	{
		$this->dateSigned = CloneHelper::cloneIfNotNull($this->dateSigned);
		$this->dateCreated = CloneHelper::cloneIfNotNull($this->dateCreated);
		$this->dateSend = CloneHelper::cloneIfNotNull($this->dateSend);
		$this->dateStatusChanged = CloneHelper::cloneIfNotNull($this->dateStatusChanged);
		$this->reminder = clone $this->reminder;
		$this->reminder->lastSendDate = CloneHelper::cloneIfNotNull($this->reminder->lastSendDate);
		$this->reminder->plannedNextSendDate = CloneHelper::cloneIfNotNull($this->reminder->plannedNextSendDate);
		$this->reminder->startDate = CloneHelper::cloneIfNotNull($this->reminder->startDate);
	}

	protected function getExcludedFromCopyProperties(): array
	{
		return [
			'id',
			'dateModify',
			'processingStatus',
			'name',
			'companyName',
			'signedFileId',
			'dateCreated',
			'signatureFileId',
			'role',
		];
	}
}
