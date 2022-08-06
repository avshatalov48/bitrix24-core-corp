<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Tasks\Rest\Controllers\Base;

class History extends Base
{

	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(
			\CTaskItem::class, 'task', function ($className, $id) {
			$userId = CurrentUser::get()->getId();

			return new $className($id, $userId);
		}
		);
	}

    /**
     * @param \CTaskItem $task
     * @param array $filter CREATED_DATE USER_ID FIELD
     *
     * @param array $order
     *
     * @return array
     * @throws \TasksException
     */
	public function listAction(\CTaskItem $task, array $filter = [], array $order = [])
	{
		$filter['TASK_ID'] = $task->getId();
		if(!$task->checkCanRead())
        {
			$this->errorCollection->add([new Error('Access denied.')]);
			return null;
        }

		$res = \CTaskLog::GetList($order, $filter);
		$list= [];
		while($row = $res->Fetch())
		{
			$list[] = [
				'ID'=>$row['ID'],
				'CREATED_DATE'=>$row['CREATED_DATE'],
				'FIELD'=>$row['FIELD'],
				'VALUE' => [
					'FROM'=>$row['FROM_VALUE'],
					'TO'=>$row['TO_VALUE']
				],
				'USER' => [
					'ID'=>$row['USER_ID'],
					'NAME'=>$row['USER_NAME'],
					'LAST_NAME'=>$row['USER_LAST_NAME'],
					'SECOND_NAME'=>$row['USER_SECOND_NAME'],
					'LOGIN'=>$row['USER_LOGIN'],
				]
			];
		}

		return ['list'=>$this->convertKeysToCamelCase($list)];
	}
}