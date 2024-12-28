<?php

namespace Bitrix\Mobile\Collab\Dto;

use Bitrix\Main\DI\ServiceLocator;

class CollabSecuritySettingsDto
{
	public function __construct(
		public bool $prohibitScreenshotForGuests = false,
		public bool $prohibitCopyTextForGuests = false,
		public bool $bitrixSpProtection = false,
		public bool $prohibitDownloadFilesForGuests = false,
	)
	{
	}
}