<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 *
 * This class should be used in components, inside agent functions, in rest, ajax and more, bringing unification to all places and processes
 */

namespace Bitrix\Tasks\Manager\Task;

use Bitrix\Main\ArgumentException;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Provider\Exception\Log\TaskLogProviderException;
use CTaskLog;
use CTimeZone;

final class Log extends Manager
{
	public static function getIsMultiple()
	{
		return true;
	}

	/**
	 * @throws TaskLogProviderException
	 * @throws ArgumentException
	 */
	public static function getListByParentEntity($userId, $taskId, array $parameters = []): array
	{
		$data = [];

		$task = self::getTask($userId, $taskId);

		if ($task !== null && $task->checkCanRead())
		{
			$res = CTaskLog::GetList(
				['CREATED_DATE' => 'DESC'],
				['TASK_ID' => $taskId],
			);

			$tzDisabled = !CTimeZone::enabled();

			if ($tzDisabled)
			{
				CTimeZone::enable();
			}

			$tzOffset = CTimeZone::getOffset();

			if ($tzDisabled)
			{
				CTimeZone::disable();
			}

			while (true)
			{
				if ($parameters['ESCAPE_DATA'])
				{
					$item = $res->GetNext();
				}
				else
				{
					$item = $res->fetch();
				}

				if (!$item)
				{
					break;
				}

				// Adjust unix timestamps to "bitrix timestamps"
				if (
					isset(CTaskLog::$arComparedFields[$item['FIELD']])
					&& (CTaskLog::$arComparedFields[$item['FIELD']] === 'date')
				)
				{
					if (intval($item['TO_VALUE']))
					{
						$item['TO_VALUE'] = $item['TO_VALUE'] + $tzOffset;
					}

					if (intval($item['FROM_VALUE']))
					{
						$item['FROM_VALUE'] = $item['FROM_VALUE'] + $tzOffset;
					}
				}

				$data[] = $item;
			}
		}

		return ['DATA' => $data, 'CAN' => []];
	}
}