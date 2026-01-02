<?php

namespace Bitrix\HumanResources\Result\Integration\Im;

use Bitrix\HumanResources\Result\PropertyResult;

/**
 * Successful or unsuccessful result of filterByPermissions method in ChatService
 */
class FilterChatResult extends PropertyResult
{
	protected array $chatIds = [];

	public function setChatIds(array $chatIds): static
	{
		$this->chatIds = $chatIds;

		return $this;
	}

	public function getChatIds(): array
	{
		return $this->chatIds;
	}
}
