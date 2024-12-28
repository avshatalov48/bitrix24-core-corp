<?php

namespace Bitrix\DiskMobile;

enum SearchEntity: string
{
	case User = 'user';
	case Group = 'group';
	case Common = 'common';

	public function entityType(): string
	{
		return match ($this)
		{
			self::User => \Bitrix\Disk\ProxyType\User::class,
			self::Group => \Bitrix\Disk\ProxyType\Group::class,
			self::Common => \Bitrix\Disk\ProxyType\Common::class,
		};
	}
}
