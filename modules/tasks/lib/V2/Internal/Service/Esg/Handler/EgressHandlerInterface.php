<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Esg\Handler;

use Bitrix\Tasks\V2\Public\Command\AbstractCommand;

interface EgressHandlerInterface
{
	public function handle(AbstractCommand $command): void;
}
