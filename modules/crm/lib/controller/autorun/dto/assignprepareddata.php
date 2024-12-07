<?php

namespace Bitrix\Crm\Controller\Autorun\Dto;

use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\RequiredField;

final class AssignPreparedData extends PreparedData
{
	public int $assignedById;

	protected function getValidators(array $fields): array
	{
		$validators = parent::getValidators($fields);

		$validators[] = new RequiredField($this, 'assignedById');
		$validators[] = new NotEmptyField($this, 'assignedById');

		return $validators;
	}
}
