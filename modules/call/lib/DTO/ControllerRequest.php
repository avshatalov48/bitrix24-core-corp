<?php

namespace Bitrix\Call\DTO;


class ControllerRequest
{
	public string $callUuid = '';
	public int $userId = 0;

	public function __construct(?array $fields = null)
	{
		if ($fields !== null)
		{
			$this->hydrate($fields);
		}
	}

	public function hydrate(array $fields): self
	{
		if (isset($fields['callUuid']))
		{
			$this->callUuid = $fields['callUuid'];
		}
		if (isset($fields['userId']))
		{
			$this->userId = (int)$fields['userId'];
		}
		return $this;
	}
}