<?php

namespace Bitrix\Call\DTO;

class TrackErrorRequest
{
	public string $callUuid = '';
	public string $errorCode = '';

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
		if (isset($fields['errorCode']))
		{
			$this->errorCode = $fields['errorCode'];
		}
		return $this;
	}
}