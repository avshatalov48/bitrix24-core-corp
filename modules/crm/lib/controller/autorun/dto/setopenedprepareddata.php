<?php

namespace Bitrix\Crm\Controller\Autorun\Dto;

use Bitrix\Crm\Dto\Validator\RequiredField;

final class SetOpenedPreparedData extends PreparedData
{
	public bool $isOpened;

	protected function getValidators(array $fields): array
	{
		$validators = parent::getValidators($fields);

		$validators[] = new RequiredField($this, 'isOpened');

		return $validators;
	}
}
