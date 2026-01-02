<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service;

use Bitrix\Disk\Document\DocumentSource;
use Bitrix\Disk\Document\SessionManager;
use Bitrix\Disk\Internal\Command\CreateInternalSessionCommand;

class SessionCommandFactory
{
	public function __construct(
		private readonly DocumentSource $documentSource,
		private readonly SessionManager $sessionManager,
	) {
	}

	public function createCreateInternalSessionCommand(int $type, bool $exactUser = false): CreateInternalSessionCommand
	{
		return new CreateInternalSessionCommand(
			$this->documentSource,
			$this->sessionManager,
			$type,
			$exactUser,
		);
	}
}
