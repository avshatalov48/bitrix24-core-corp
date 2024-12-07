<?php

namespace Bitrix\Crm\Kanban\Entity\Dto\Sign\B2e;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Caster\CollectionCaster;
use Bitrix\Crm\Dto\Caster\IntCaster;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\IntegerField;
use Bitrix\Crm\Dto\Validator\RequiredField;

final class UserListDto extends Dto
{
	public int $total = 0;
	public array $userIdList = [];

	protected function getValidators(array $fields): array
	{
		return [
			new RequiredField($this, 'total'),
			new IntegerField($this, 'total', 0),
			new RequiredField($this, 'userIdList'),
		];
	}

	public function getCastByPropertyName(string $propertyName): ?Caster
	{
		return match ($propertyName)
		{
			'userIdList' => new CollectionCaster(new IntCaster()),
			default => null,
		};
	}
}
