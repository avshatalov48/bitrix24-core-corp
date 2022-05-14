<?php

namespace Bitrix\Crm\Integrity\CriterionRegistrar\Decorator;

use Bitrix\Crm\Integrity\CriterionRegistrar;
use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
use Bitrix\Crm\Item;
use Bitrix\Main\Result;

final class CommunicationCriterion extends CriterionRegistrar\Decorator
{
	protected function wrapRegister(CriterionRegistrar\Data $data): Result
	{
		$fields = $data->getCurrentFields();

		$multifields = $fields[Item::FIELD_NAME_FM] ?? null;

		if (is_array($multifields))
		{
			$duplicateCommData = DuplicateCommunicationCriterion::prepareBulkData($multifields);
			if (!empty($duplicateCommData))
			{
				DuplicateCommunicationCriterion::bulkRegister(
					$data->getEntityTypeId(),
					$data->getEntityId(),
					$duplicateCommData,
				);
			}
		}

		return new Result();
	}

	protected function wrapUpdate(CriterionRegistrar\Data $data): Result
	{
		$fields = $data->getCurrentFields();

		$multifields = $fields[Item::FIELD_NAME_FM] ?? null;

		if (is_array($multifields))
		{
			// we can't simply use FM from current fields since they may be incomplete
			// e.g., we don't change phones and therefore don't provide them in FM at all
			$multifields = DuplicateCommunicationCriterion::prepareEntityMultifieldValues(
				$data->getEntityTypeId(),
				$data->getEntityId(),
			);

			DuplicateCommunicationCriterion::bulkRegister(
				$data->getEntityTypeId(),
				$data->getEntityId(),
				DuplicateCommunicationCriterion::prepareBulkData($multifields),
			);
		}

		return new Result();
	}

	protected function wrapUnregister(CriterionRegistrar\Data $data): Result
	{
		DuplicateCommunicationCriterion::unregister(
			$data->getEntityTypeId(),
			$data->getEntityId(),
		);

		return new Result();
	}
}
