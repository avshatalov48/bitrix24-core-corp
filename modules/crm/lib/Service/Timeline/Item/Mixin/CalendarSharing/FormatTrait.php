<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;

use Bitrix\Main;

trait FormatTrait
{
	public function formatTime(int $minutes): string
	{
		$now = time();
		$dayStart = $now - $now % 86400 - FormatDate('Z');

		$culture = Main\Application::getInstance()->getContext()->getCulture();
		$timeFormat = $culture->get('SHORT_TIME_FORMAT');

		return FormatDate($timeFormat, $dayStart + $minutes * 60);
	}

	public function formatDuration(int $diffMinutes): string
	{
		$now = time();
		$hours = (int)($diffMinutes / 60);
		$minutes = $diffMinutes % 60;

		$hint = FormatDate('idiff', $now - $minutes * 60);
		if ($hours > 0)
		{
			$hint = FormatDate('Hdiff', $now - $hours * 60 * 60);
			if ($minutes > 0)
			{
				$hint .= ' ' . FormatDate('idiff', $now - $minutes * 60);
			}
		}

		return $hint;
	}
}