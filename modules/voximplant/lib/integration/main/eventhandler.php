<?php

namespace Bitrix\Voximplant\Integration\Main;

use Bitrix\Voximplant\StatisticTable;

class EventHandler
{
	/**
	 * Cleans up irrelevant links after deleting a file.
	 *
	 * @param array $params
	 */
	public static function onFileDelete(array $params): void
	{
		$fileId = (int)$params['ID'];
		if ($fileId < 1)
		{
			return;
		}

		StatisticTable::updateBatch(
			[
				'CALL_RECORD_ID' => null,
			],
			[
				'=CALL_RECORD_ID' => $fileId,
			],
		);
	}
}