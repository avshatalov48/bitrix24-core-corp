<?php

namespace Bitrix\Mobile\Collab\Dto;

class CollabPermissionSettingsDto
{
	public function __construct(
		public CollabSettingsUserDto $owner,
		public array $moderators,
		public string $inviters = 'K',
		public string $messageWriters = 'K',
		public string $showHistory = 'Y',
		public string $allowGuestsInvitation = 'Y',
	)
	{
	}
}