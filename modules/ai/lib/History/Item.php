<?php

namespace Bitrix\AI\History;

use Bitrix\Main\Type\DateTime;

class Item
{
	private ?array $groupData = null;

	public function __construct(
		private ?int $id,
		private DateTime $createdDate,
		private ?string $data,
		private ?string $engineCode,
		private mixed $payloadRawData,
	) {}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getCreatedDate(): DateTime
	{
		return $this->createdDate;
	}

	public function getData(): ?string
	{
		return $this->data;
	}

	public function getEngineCode(): ?string
	{
		return $this->engineCode;
	}

	public function getPayloadRawData(): mixed
	{
		return $this->payloadRawData;
	}

	public function isGrouped(): bool
	{
		return $this->groupData !== null;
	}

	public function addGroupData(string $data): void
	{
		if (!$this->isGrouped())
		{
			$this->groupData = [$this->data];
		}
		$this->groupData[] = $data;
	}

	public function getGroupData(): array
	{
		return $this->groupData;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'date' => (string)$this->createdDate,
			'data' => $this->data,
			'engineCode' => $this->engineCode,
			'payload' => $this->payloadRawData,
		];
	}
}
