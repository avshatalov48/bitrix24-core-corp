<?php

namespace Bitrix\Crm\Controller\Autorun\Dto;

use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\RequiredField;

final class SetStagePreparedData extends PreparedData
{
	public string $stageId;

	protected function getValidators(array $fields): array
	{
		$validators = parent::getValidators($fields);

		$validators[] = new RequiredField($this, 'stageId');
		$validators[] = new NotEmptyField($this, 'stageId');

		return $validators;
	}
}
