<?php

namespace Bitrix\Crm\Controller\Autorun\Dto;

use Bitrix\Crm\Dto\Validator\RequiredField;

final class SetExportPreparedData extends PreparedData
{
	public bool $export;

	protected function getValidators(array $fields): array
	{
		$validators = parent::getValidators($fields);

		$validators[] = new RequiredField($this, 'export');

		return $validators;
	}
}
