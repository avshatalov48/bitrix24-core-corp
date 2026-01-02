<?php
declare(strict_types=1);

namespace Bitrix\Disk\Internal\Command;

use Bitrix\Disk\Document\DocumentSource;
use Bitrix\Disk\Document\SessionManager;
use Bitrix\Main\Command\AbstractCommand;
use Bitrix\Main\Result;

class CreateInternalSessionCommand extends AbstractCommand
{
	public function __construct(
		public readonly DocumentSource $documentSource,
		public readonly SessionManager $sessionManager,
		public readonly int $type,
		public readonly bool $exactUser = false,
	) {
	}

	protected function execute(): Result
	{
		return (new CreateInternalSessionCommandHandler())($this);
	}
}