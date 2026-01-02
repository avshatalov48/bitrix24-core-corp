<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Command\UnifiedLink;

use Bitrix\Disk\Internal\Repository\UnifiedLinkAccessRepository;
use Bitrix\Main\ORM\Data\AddResult;

class SetAccessCommandHandler
{
	public function __invoke(SetAccessCommand $command): AddResult
	{
		return UnifiedLinkAccessRepository::set(
			$command->objectId,
			$command->level,
		);
	}
}
