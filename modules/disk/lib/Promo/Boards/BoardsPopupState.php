<?php

declare(strict_types=1);

namespace Bitrix\Disk\Promo\Boards;

enum BoardsPopupState: string
{
	case New = 'new';
	case Acknowledged = 'acknowledged';
	case Viewed = 'viewed';
	case Completed = 'completed';
}