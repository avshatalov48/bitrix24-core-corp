<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\AutoWire\Parameter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Rest\Controllers\Base;
use CTaskItem;

Loc::loadMessages(__FILE__);

/**
 * Class View
 *
 * @package Bitrix\Tasks\Rest\Controllers\Task
 */
class View extends Base
{
	/**
	 * @return ExactParameter|Parameter|null
	 */
	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(CTaskItem::class, 'task', static function($className, $id) {
			return new $className($id, CurrentUser::get()->getId());
		});
	}

	/**
	 * Updates task's last view date with given value (timestamp) or current time.
	 *
	 * @param CTaskItem $task
	 * @param null|string $viewedDate
	 * @param array $parameters
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function updateAction(CTaskItem $task, $viewedDate = null, $parameters = []): void
	{
		if (!$task->checkCanRead())
		{
			return;
		}

		$taskId = $task->getId();
		$userId = CurrentUser::get()->getId();

		$viewedDateToSave = null;
		if ($viewedDate && ($timestamp = strtotime($viewedDate)))
		{
			$viewedDateToSave = DateTime::createFromTimestamp($timestamp);
		}

		ViewedTable::set($taskId, $userId, $viewedDateToSave, $parameters);
	}
}