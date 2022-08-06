<?php

namespace Bitrix\Crm\Integrity\CriterionRegistrar\Decorator;

use Bitrix\Crm\Integrity\CriterionRegistrar;
use Bitrix\Crm\Integrity\DuplicateVolatileCriterion;
use Bitrix\Crm\Integrity\Volatile;
use Bitrix\Main\Result;

class VolatileCriterion extends CriterionRegistrar\Decorator
{
	protected function wrapRegister(CriterionRegistrar\Data $data): Result
	{
		//region Register volatile duplicate criterion fields
		DuplicateVolatileCriterion::register(
			$data->getEntityTypeId(),
			$data->getEntityId(),
			[
				Volatile\FieldCategory::ENTITY,
				Volatile\FieldCategory::MULTI,
			]
		);
		//endregion Register volatile duplicate criterion fields

		return new Result();
	}

	protected function wrapUpdate(CriterionRegistrar\Data $data): Result
	{
		return $this->wrapRegister($data);
	}

	protected function wrapUnregister(CriterionRegistrar\Data $data): Result
	{
		//region Unregister volatile duplicate criterion fields
		DuplicateVolatileCriterion::unregister(
			$data->getEntityTypeId(),
			$data->getEntityId()
		);
		//endregion Unregister volatile duplicate criterion fields

		return new Result();
	}
}
