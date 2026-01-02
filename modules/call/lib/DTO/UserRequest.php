<?php

namespace Bitrix\Call\DTO;

class UserRequest extends Hydrator
{
	public string $callUuid = '';
	public string $roomId = '';
	public int $callId = 0;
	public string $video = 'N';
	public string $show = 'N';
	public string $legacyMobile = 'N';
	public string $repeated = 'N';
	public array $users = [];
	public string $callInstanceId = '';
	public int $code = 0;
	public string $provider = '';
	public int $callType = 0;
	public string $entityType = '';
	public string $entityId = '';
}
