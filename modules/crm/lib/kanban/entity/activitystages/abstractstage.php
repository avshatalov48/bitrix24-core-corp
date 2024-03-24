<?php

namespace Bitrix\Crm\Kanban\Entity\ActivityStages;

use Bitrix\Crm\Filter\FieldsTransform\UserBasedField;
use Bitrix\Main\Engine\CurrentUser;

abstract class AbstractStage
{
	protected const RESPONSIBLE_ID_FIELD_NAME = 'RESPONSIBLE_ID';

	abstract public function getFilterParams(array $filter = []): array;

	protected function transformFilter(array &$filter): void
	{
		if (!isset($filter[self::RESPONSIBLE_ID_FIELD_NAME]))
		{
			$filter[self::RESPONSIBLE_ID_FIELD_NAME] = CurrentUser::get()->getId();
		}

		UserBasedField::applyTransformWrapper($filter, [self::RESPONSIBLE_ID_FIELD_NAME]);
	}
}
