<?php

namespace Bitrix\Crm\Integrity\CriterionRegistrar;

use Bitrix\Crm\Integrity\CriterionRegistrar;
use Bitrix\Crm\Integrity\DuplicateEntityRanking;
use Bitrix\Main\Result;

final class EntityRanking extends CriterionRegistrar
{
	public function register(Data $data): Result
	{
		DuplicateEntityRanking::registerEntityStatistics(
			$data->getEntityTypeId(),
			$data->getEntityId(),
			$data->getCurrentFields(),
		);

		return new Result();
	}

	public function update(Data $data): Result
	{
		$fields = $data->getCurrentFields() + $data->getPreviousFields();

		DuplicateEntityRanking::registerEntityStatistics(
			$data->getEntityTypeId(),
			$data->getEntityId(),
			$fields,
		);

		return new Result();
	}

	public function unregister(Data $data): Result
	{
		DuplicateEntityRanking::unregisterEntityStatistics($data->getEntityTypeId(), $data->getEntityId());

		return new Result();
	}
}
