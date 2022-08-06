<?php

namespace Bitrix\Crm\Integrity\CriterionRegistrar\Decorator;

use Bitrix\Crm\Integrity\CriterionRegistrar;
use Bitrix\Crm\Integrity\DuplicateVolatileCriterion;
use Bitrix\Crm\Integrity\Volatile;
use Bitrix\Main\Result;

final class VolatileCriterionReindex extends VolatileCriterion
{
	protected function wrapRegister(CriterionRegistrar\Data $data): Result
	{
		//region Register volatile duplicate criterion fields
		DuplicateVolatileCriterion::register(
			$data->getEntityTypeId(),
			$data->getEntityId()
		);
		//endregion Register volatile duplicate criterion fields

		return new Result();
	}
}
