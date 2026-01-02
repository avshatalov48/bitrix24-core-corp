<?php

namespace Bitrix\Call\DTO;

class CallRequest extends Hydrator
{
	public int $initiatorUserId = 0;
	public int $userId = 0;
	public int $chatId = 0;
	public int $callId = 0;
	public string $requestId = '';
	public string $callUuid = '';
	public string $roomId = '';
	public string $parentCallUuid = '';
	public string $provider = '';
	public int $callType = 0;
	public array $users = [];
	public string $video = 'N';
	public string $show = 'Y';
	public string $legacyMobile = 'N';
	public string $repeated = 'N';
	public int $tokenVersion = 1;
	public bool $isAudioRecord = false;
	public int $audioRecordingInitiatorId = 0;
	public string $audioRecordingErrCode = '';
}
