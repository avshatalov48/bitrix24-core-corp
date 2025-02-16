<?php

namespace Bitrix\Crm\Controller\Autorun\Dto;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Caster\CollectionCaster;
use Bitrix\Crm\Dto\Caster\IntCaster;
use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\RequiredField;

final class ObserversPreparedData extends PreparedData
{
	public array $observerIdList = [];

	protected function getValidators(array $fields): array
	{
		$validators = parent::getValidators($fields);

		$validators[] = new RequiredField($this, 'observerIdList');
		$validators[] = new NotEmptyField($this, 'observerIdList');

		return $validators;
	}

	public function getCastByPropertyName(string $propertyName): ?Caster
	{
		return match ($propertyName)
		{
			'observerIdList' => new CollectionCaster(new IntCaster()),
			default => null,
		};
	}
}