<?php

namespace Bitrix\Call\DTO;

class ControllerRequest extends Hydrator
{
	public string $callUuid = '';
	public int $userId = 0;
}
