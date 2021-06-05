<?php
namespace Bitrix\Timeman\Monitor\History;

use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Model\Monitor\MonitorUserLogTable;
use Bitrix\Timeman\Monitor\Utils\User;

class UserLog
{
	public static function record($history): array
	{
		$date = new Date($history['dateLog'], 'Y-m-j');

		foreach ($history['historyPackage'] as $index => $entry)
		{
			$result = MonitorUserLogTable::add([
				'DATE_LOG' => $date,
				'USER_ID' => User::getCurrentUserId(),
				'PRIVATE_CODE' => $entry['privateCode'],
				'ENTITY_ID' => $entry['ENTITY_ID'],
				'TIME_SPEND' => $entry['time'],
				'DESKTOP_CODE' => $history['desktopCode'],
				'COMMENT' => $entry['comment'],
			]);

			$history['historyPackage'][$index]['USER_LOG_ID'] = $result->getId();
		}

		return $history;
	}
}