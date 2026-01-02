<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Control\Command\Access;

use Bitrix\Tasks\Internals\Attribute\PositiveNumber;
use Bitrix\Tasks\Internals\Attribute\Min;
use Bitrix\Tasks\Flow\AbstractCommand;

class GiveAccessCommand extends AbstractCommand
{
	public function __construct(
		#[Min(0)]
		public readonly int $startFromId,
		#[PositiveNumber]
		public readonly int $limit,
	)
	{
	}
}
