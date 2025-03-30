<?php

declare(strict_types=1);

namespace Bitrix\Disk\Analytics\Enum;

enum DocumentHandlerType: string
{
	case Bitrix24 = 'b24_docs';
	case Desktop = 'desk_app';
	case Board = 'board';
}
