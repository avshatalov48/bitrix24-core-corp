<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document\OnlyOffice\Clients\CommandService\Commands;

abstract class BaseCommand
{
	public readonly string $c;

	public function __construct(CommandType $command)
	{
		$this->c = $command->value;
	}
}