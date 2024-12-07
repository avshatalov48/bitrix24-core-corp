<?php

namespace Bitrix\Crm\Controller\Autorun\Dto;

use Bitrix\Crm\Dto\Validator\RequiredField;

final class SetCategoryPreparedData extends PreparedData
{
	public int $categoryId;

	protected function getValidators(array $fields): array
	{
		$validators = parent::getValidators($fields);

		$validators[] = new RequiredField($this, 'categoryId');

		return $validators;
	}
}
