<?php

namespace Bitrix\Tasks\Copy\Implement;

use Bitrix\Main\Copy\Container;
use Bitrix\Main\Copy\CopyImplementer;
use Bitrix\Tasks\Internals\Task\ParameterTable;

class TaskParams extends CopyImplementer
{
	public function add(Container $container, array $fields)
	{
		$queryObject = ParameterTable::getList([
			'select' => ['ID'],
			'filter' => [
				'TASK_ID' => $fields['TASK_ID'],
				'CODE' => $fields['CODE'],
			],
		]);
		if ($data = $queryObject->fetch())
		{
			$result = ParameterTable::update(
				$data['ID'],
				[
					'VALUE' => $fields['VALUE'],
				]
			);
		}
		else
		{
			$result = ParameterTable::add($fields);
		}

		if ($result->isSuccess())
		{
			return $result->getId();
		}
		else
		{
			$this->result->addErrors($result->getErrors());

			return false;
		}
	}

	public function getFields(Container $container, $entityId)
	{
		$queryObject = ParameterTable::getById($entityId);

		return (($fields = $queryObject->fetch()) ? $fields : []);
	}

	public function prepareFieldsToCopy(Container $container, array $fields)
	{
		unset($fields['ID']);

		$dictionary = $container->getDictionary();

		if ($taskId = $dictionary->get('TASK_ID'))
		{
			$fields['TASK_ID'] = $taskId;
		}

		return $fields;
	}

	public function copyChildren(Container $container, $entityId, $copiedEntityId)
	{
		return $this->getResult();
	}
}