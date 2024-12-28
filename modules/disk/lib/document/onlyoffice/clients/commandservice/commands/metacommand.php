<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document\OnlyOffice\Clients\CommandService\Commands;

class MetaCommand extends BaseCommand
{
	public function __construct(
		public readonly string $key,
		public readonly array $meta
	)
	{
		parent::__construct(CommandType::Meta);
	}
}