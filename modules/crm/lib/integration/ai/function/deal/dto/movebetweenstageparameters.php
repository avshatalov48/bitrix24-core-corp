<?php

namespace Bitrix\Crm\Integration\AI\Function\Deal\Dto;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\DefinedCategory;
use Bitrix\Crm\Dto\Validator\IntegerField;
use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\RequiredField;
use Bitrix\Main\ArgumentException;
use CCrmOwnerType;

final class MoveBetweenStageParameters extends Dto
{
	public int $categoryId;
	public string $from;
	public string $to;

	/**
	 * @throws ArgumentException
	 */
	protected function getValidators(array $fields): array
	{
		return [
			new RequiredField($this, 'categoryId'),
			new DefinedCategory($this, CCrmOwnerType::Deal, 'categoryId'),

			new NotEmptyField($this, 'from'),

			new NotEmptyField($this, 'to'),
		];
	}
}
