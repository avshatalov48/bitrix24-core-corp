<?php

namespace Bitrix\Crm\Service\Communication\Result;

use Bitrix\Crm\ItemIdentifierCollection;
use Bitrix\Main\Result;

class RegisterTouchResult extends Result
{
	private int $index = 0;

	public function setTouchedItemsCollection(ItemIdentifierCollection $itemIdentifierCollection): self
	{
		$this->data['touchedItemsCollection'] = $itemIdentifierCollection;

		return $this;
	}

	public function getTouchedItemsCollection(): ?ItemIdentifierCollection
	{
		return $this->data['touchedItemsCollection'] ?? null;
	}
}
