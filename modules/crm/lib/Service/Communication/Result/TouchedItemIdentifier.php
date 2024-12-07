<?php

namespace Bitrix\Crm\Service\Communication\Result;

use Bitrix\Crm\ItemIdentifier;

final class TouchedItemIdentifier
{
	public function __construct(private ItemIdentifier $itemIdentifier, private bool $isCreated)
	{

	}

	public function getItemIdentifier(): ItemIdentifier
	{
		return $this->itemIdentifier;
	}

	public function isCreated(): bool
	{
		return $this->isCreated;
	}

	public function toArray(): array
	{
		return [
			'itemIdentifier' => $this->itemIdentifier->toArray(),
			'isCreated' => $this->isCreated,
		];
	}
}
