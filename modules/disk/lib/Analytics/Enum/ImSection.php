<?php

declare(strict_types=1);

namespace Bitrix\Disk\Analytics\Enum;

enum ImSection: string
{
	case Chat = 'chat';
	case Channel = 'channel';
	case Openline = 'openline';
}
