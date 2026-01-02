<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Access\UnifiedLink;

use Bitrix\Main\Localization\Loc;

enum UnifiedLinkAccessLevel: string
{
	case Denied = 'D';
	case Read = 'R';
	case Edit = 'W';

	public function isMax(): bool
	{
		return $this === self::Edit;
	}

	public function canRead(): bool
	{
		return $this->value >= self::Read->value;
	}

	public function canEdit(): bool
	{
		return $this === self::Edit;
	}

	public function getTitle(): string
	{
		return match ($this)
		{
			self::Denied => Loc::getMessage('DISK_UNIFIED_LINK_ACCESS_LEVEL_DENIED'),
			self::Read => Loc::getMessage('DISK_UNIFIED_LINK_ACCESS_LEVEL_READ'),
			self::Edit => Loc::getMessage('DISK_UNIFIED_LINK_ACCESS_LEVEL_EDIT'),
		};
	}
}
