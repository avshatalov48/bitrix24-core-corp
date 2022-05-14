<?php

namespace Bitrix\Voximplant\Integration\Disk;

use Bitrix\Main\Event;
use Bitrix\Voximplant\StatisticTable;

class EventHandler
{
	/**
	 * Cleans up irrelevant links after deleting a file.
	 *
	 * @param Event $event
	 */
	public static function onAfterDeleteFile(Event $event): void
	{
		$fileId = (int)$event->getParameter(0);
		if ($fileId < 1)
		{
			return;
		}

		StatisticTable::updateBatch(
			[
				'CALL_WEBDAV_ID' => null,
			],
			[
				'=CALL_WEBDAV_ID' => $fileId,
			],
		);
	}
}