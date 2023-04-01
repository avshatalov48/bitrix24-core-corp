<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class EcommerceDocumentsList extends ContentBlock
{
	protected ?int $ownerId = null;
	protected ?int $ownerTypeId = null;
	protected bool $isWithOrdersMode = false;
	protected array $summaryOptions = [];

	public function getRendererName(): string
	{
		return 'EcommerceDocumentsList';
	}

	public function getSummaryOptions(): array
	{
		return $this->summaryOptions;
	}

	public function setSummaryOptions(array $summaryOptions): self
	{
		$this->summaryOptions = $summaryOptions;

		return $this;
	}

	public function getOwnerId(): ?int
	{
		return $this->ownerId;
	}

	public function setOwnerId(?int $ownerId): self
	{
		$this->ownerId = $ownerId;

		return $this;
	}

	public function getOwnerTypeId(): ?int
	{
		return $this->ownerTypeId;
	}

	public function setOwnerTypeId(?int $ownerTypeId): self
	{
		$this->ownerTypeId = $ownerTypeId;

		return $this;
	}

	public function isWithOrdersMode(): bool
	{
		return $this->isWithOrdersMode;
	}

	public function setIsWithOrdersMode(bool $isWithOrdersMode): self
	{
		$this->isWithOrdersMode = $isWithOrdersMode;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'summaryOptions' => $this->getSummaryOptions(),
			'ownerId' => $this->getOwnerId(),
			'ownerTypeId' => $this->getOwnerTypeId(),
			'isWithOrdersMode' => $this->isWithOrdersMode(),
		];
	}
}
