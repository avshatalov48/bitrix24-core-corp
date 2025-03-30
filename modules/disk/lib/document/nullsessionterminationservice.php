<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document;

class NullSessionTerminationService implements SessionTerminationService
{

	public function terminateAllSessions(): void
	{
	}
}