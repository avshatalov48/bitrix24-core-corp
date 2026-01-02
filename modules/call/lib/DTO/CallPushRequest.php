<?php
namespace Bitrix\Call\DTO;

class CallPushRequest extends Hydrator
{
	public int $initiatorUserId = 0;
	public string $callUuid = '';
	public string $roomId = '';
	public string $requestId = '';
	public array $users = [];
	public array $usersIds = [];
	public string $legacyMobile = 'N';
	public string $video = 'N';
}
