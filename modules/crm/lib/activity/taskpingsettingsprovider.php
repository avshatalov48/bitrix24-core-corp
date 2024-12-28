<?php

namespace Bitrix\Crm\Activity;

use Bitrix\Crm\Activity\Ping\PingSettingsProvider;

final class TaskPingSettingsProvider extends PingSettingsProvider
{
	public const DEFAULT_OFFSETS = [15, 60];
	public static function getDefaultOffsetList(): array
	{
		$defaultOffsetList = parent::getDefaultOffsetList();
		unset($defaultOffsetList[0]);

		return $defaultOffsetList;
	}

	public function getCurrentOffsets(): array
	{
		return self::DEFAULT_OFFSETS;
	}
}
