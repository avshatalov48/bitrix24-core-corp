<?php
declare(strict_types=1);

namespace Bitrix\AI\Synchronization\Enum;

/**
 * Enum SyncMode
 * SyncModes.
 */
enum SyncMode: int
{
	case Standard = 0;
	case Partitional = 1;
}
