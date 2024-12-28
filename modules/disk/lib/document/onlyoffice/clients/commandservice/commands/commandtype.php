<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document\OnlyOffice\Clients\CommandService\Commands;

enum CommandType: string
{
	case Meta = 'meta';
	case Drop = 'drop';
}
