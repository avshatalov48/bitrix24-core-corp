<?php

namespace Bitrix\Rpa\Scenario;

use Bitrix\Main\Result;
use Bitrix\Rpa\Model\FieldTable;
use Bitrix\Rpa\Model\Type;
use Bitrix\Rpa\Scenario;

class TypeFieldSettings extends Scenario
{
	protected $type;

	public function __construct(Type $type)
	{
		$this->type = $type;
	}

	public function play(): Result
	{
		$result = new Result();

		$visibilityResult = $this->addKanbanVisibilityToAllUserFields();
		if(!$visibilityResult->isSuccess())
		{
			$result->addErrors($visibilityResult->getErrors());
		}

		return $result;
	}

	protected function addKanbanVisibilityToAllUserFields(): Result
	{
		$result = new Result();

		$userFields = $this->type->getUserFieldCollection();
		foreach($userFields as $userField)
		{
			$addResult = FieldTable::add([
				'TYPE_ID' => $this->type->getId(),
				'STAGE_ID' => 0,
				'FIELD' => $userField->getName(),
				'VISIBILITY' => FieldTable::VISIBILITY_KANBAN,
			]);

			if(!$addResult->isSuccess())
			{
				$result->addErrors($addResult->getErrors());
			}
		}

		return $result;
	}
}