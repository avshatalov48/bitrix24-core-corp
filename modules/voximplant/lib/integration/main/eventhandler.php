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
		if (!in_array($params['MODULE_ID'], ['voximplant']))
		{
			return;
		}

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