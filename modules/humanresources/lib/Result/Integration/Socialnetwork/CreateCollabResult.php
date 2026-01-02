<?php

namespace Bitrix\HumanResources\Result\Integration\Socialnetwork;

use Bitrix\HumanResources\Result\PropertyResult;

class CreateCollabResult extends PropertyResult
{
	protected ?int $collabId = null;

	public function setCollabId(?int $collabId): static
	{
		$this->collabId = $collabId;

		return $this;
	}

	public function getCollabId(): ?int
	{
		return $this->collabId;
	}
}
