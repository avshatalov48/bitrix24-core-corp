<?php

namespace Bitrix\Crm\Integrity\CriterionRegistrar\Decorator;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Integrity\CriterionRegistrar;
use Bitrix\Crm\Integrity\DuplicatePersonCriterion;
use Bitrix\Crm\Item;
use Bitrix\Main\Result;

final class PersonCriterion extends CriterionRegistrar\Decorator
{
	protected function wrapRegister(CriterionRegistrar\Data $data): Result
	{
		$fields = $data->getCurrentFields();

		$lastName = (string)($fields[Item::FIELD_NAME_LAST_NAME] ?? '');
		if ($lastName !== '')
		{
			DuplicatePersonCriterion::register(
				$data->getEntityTypeId(),
				$data->getEntityId(),
				$lastName,
				(string)($fields[Item::FIELD_NAME_NAME] ?? ''),
				(string)($fields[Item::FIELD_NAME_SECOND_NAME] ?? '')
			);
		}

		return new Result();
	}

	protected function wrapUpdate(CriterionRegistrar\Data $data): Result
	{
		$previousFields = $data->getPreviousFields();
		$currentFields = $data->getCurrentFields();

		$difference = ComparerBase::compareEntityFields($previousFields, $currentFields);

		if (
			$difference->isChanged(Item::FIELD_NAME_LAST_NAME)
			|| $difference->isChanged(Item::FIELD_NAME_NAME)
			|| $difference->isChanged(Item::FIELD_NAME_SECOND_NAME)
		)
		{
			$lastName =
				$difference->getCurrentValue(Item::FIELD_NAME_LAST_NAME)
				?? $difference->getPreviousValue(Item::FIELD_NAME_LAST_NAME)
			;

			$name =
				$difference->getCurrentValue(Item::FIELD_NAME_NAME)
				?? $difference->getPreviousValue(Item::FIELD_NAME_NAME)
			;

			$secondName =
				$difference->getCurrentValue(Item::FIELD_NAME_SECOND_NAME)
				?? $difference->getPreviousValue(Item::FIELD_NAME_SECOND_NAME)
			;

			DuplicatePersonCriterion::register(
				$data->getEntityTypeId(),
				$data->getEntityId(),
				(string)$lastName,
				(string)$name,
				(string)$secondName,
			);
		}

		return new Result();
	}

	protected function wrapUnregister(CriterionRegistrar\Data $data): Result
	{
		DuplicatePersonCriterion::unregister($data->getEntityTypeId(), $data->getEntityId());

		return new Result();
	}
}
