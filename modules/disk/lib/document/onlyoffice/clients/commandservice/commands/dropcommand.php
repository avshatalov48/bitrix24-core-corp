<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document\OnlyOffice\Clients\CommandService\Commands;

class DropCommand extends BaseCommand
{
	public function __construct(
		public readonly string $key,
		public readonly array $users
	)
	{
		parent::__construct(CommandType::Drop);
	}
}