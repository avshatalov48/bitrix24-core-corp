<?php

namespace Bitrix\Crm\Integration\AI\Function\Deal\Dto;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\DefinedCategory;
use Bitrix\Crm\Dto\Validator\IntegerField;
use Bitrix\Crm\Dto\Validator\RequiredField;
use Bitrix\Main\ArgumentException;
use CCrmOwnerType;

final class MoveBetweenCategoryParameters extends Dto
{
	public int $from;
	public int $to;

	/**
	 * @throws ArgumentException
	 */
	protected function getValidators(array $fields): array
	{
		return [
			new RequiredField($this, 'from'),
			new DefinedCategory($this, CCrmOwnerType::Deal, 'from'),

			new RequiredField($this, 'to'),
			new DefinedCategory($this, CCrmOwnerType::Deal, 'to'),
		];
	}
}
