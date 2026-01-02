<?php

namespace Bitrix\Call\DTO;

class CallUserRequest extends Hydrator
{
	public int $userId = 0;
	public int $callId = 0;
	public string $callUuid = '';
	public string $roomId = '';
	public string $userState = '';
	public string $legacyMobile = 'N';
	public string $callInstanceId = '';
	public int $code = 0;
	public array $connectedUsers = [];
	public array $disconnectedUsers = [];
}
