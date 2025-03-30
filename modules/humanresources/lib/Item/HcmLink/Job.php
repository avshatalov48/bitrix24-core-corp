<?php

namespace Bitrix\HumanResources\Item\HcmLink;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\HumanResources\Type\HcmLink\JobStatus;
use Bitrix\HumanResources\Type\HcmLink\JobType;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Type\DateTime;

class Job implements Item, Arrayable, \JsonSerializable
{
	public function __construct(
		public int       $companyId,
		public JobType   $type,
		public JobStatus $status = JobStatus::STARTED,
		public int       $done = 0,
		public int       $total = 0,
		public int       $eventCount = 0,
		public ?DateTime $createdAt = null,
		public ?DateTime $updatedAt = null,
		public ?DateTime $finishedAt = null,
		public array     $inputData = [],
		public array     $outputData = [],
		public ?int      $id = null,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'companyId' => $this->companyId,
			'type' => $this->type->name,
			'status' => $this->status->name,
			'done' => $this->done,
			'total' => $this->total,
			'inputData' => $this->inputData,
			'outputData' => $this->outputData,
			'createdAt' => ($this->createdAt ?? new DateTime())->format(\DateTimeInterface::ATOM),
			'updatedAt' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
			'finishedAt' => $this->finishedAt?->format(\DateTimeInterface::ATOM),
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}