<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document;

interface SessionTerminationService
{
	public function terminateAllSessions(): void;
}