<?php

namespace Bitrix\Call\DTO;

class TrackErrorRequest extends Hydrator
{
	public string $callUuid = '';
	public string $errorCode = '';
}
