<?php

namespace Bitrix\Crm\Integration\AI\Function\Deal\Dto\Category;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\StringField;
use Bitrix\Crm\Dto\Validator\StringStartsWith;
use Bitrix\Crm\EO_Status;
use Bitrix\Crm\StatusTable;

final class Stage extends Dto
{
	public string $name;
	public ?string $color = null;

	protected function getValidators(array $fields): array
	{
		return [
			new StringField($this, 'name'),
			new NotEmptyField($this, 'name'),

			new StringField($this, 'color'),
			new StringStartsWith($this, 'color', '#'),
		];
	}

	public function toOrmObject(): EO_Status
	{
		return (new EO_Status())
			->setName($this->name)
			->setColor($this->color);
	}
}
