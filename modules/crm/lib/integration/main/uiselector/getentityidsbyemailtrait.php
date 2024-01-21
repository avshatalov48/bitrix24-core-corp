<?php

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Crm\FieldMultiTable;
use CCrmFieldMulti;

trait GetEntityIdsByEmailTrait
{
	protected function getEntityIdsByEmail(string $email): array
	{
		$query = FieldMultiTable::query()
			->where('ENTITY_ID', static::getOwnerTypeName())
			->where('TYPE_ID', CCrmFieldMulti::EMAIL)
			->setSelect(['ELEMENT_ID'])
		;

		if (mb_substr($email, -1) === '%')
		{
			$query->whereLike('VALUE', $email);
		}
		else
		{
			$query->where('VALUE', $email);
		}

		return $query->fetchCollection()->getElementIdList();
	}
}
