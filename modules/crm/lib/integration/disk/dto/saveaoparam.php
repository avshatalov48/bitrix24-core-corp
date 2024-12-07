<?php

namespace Bitrix\Crm\Integration\Disk\Dto;

class SaveAOParam
{
	public function __construct(
		private int $quoteId,
		private array $prevStorageIds,
		private array $storageIds,
		private int $userId,
	)
	{
	}

	public function quoteId(): int
	{
		return $this->quoteId;
	}

	public function prevStorageIds(): array
	{
		return $this->prevStorageIds;
	}

	public function storageIds(): array
	{
		return $this->storageIds;
	}

	public function userId(): int
	{
		return $this->userId;
	}

}