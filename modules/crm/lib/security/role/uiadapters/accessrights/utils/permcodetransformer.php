<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Utils;

use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\PermIdentifier;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\ArgumentException;

class PermCodeTransformer
{
	use Singleton;

	private const CS = '~~~';

	public function makeAccessRightPermCode(PermIdentifier $perm): string
	{
		$field = $perm->field;
		$fieldValue = $perm->fieldValue;
		$entityCode = $perm->entityCode;
		$permCode = $perm->permCode;

		if ($field === '-')
		{
			$field = null;
		}

		if ($field === null || $fieldValue === null)
		{
			return $entityCode . self::CS . $permCode;
		}

		return $entityCode . self::CS . $permCode . self::CS . $field . self::CS . $fieldValue;
	}

	public function decodeAccessRightCode(string $code): PermIdentifier
	{
		$parts = explode(self::CS, $code);

		if (count($parts) < 2 || count($parts) > 4)
		{
			throw new ArgumentException();
		}

		$entityCode = $parts[0];
		$permCode = $parts[1];

		$fieldValue = null;
		if (count($parts) == 4)
		{
			$field = $parts[2];
			$fieldValue = $parts[3];
		}

		if (empty($field))
		{
			$field = '-';
		}

		return new PermIdentifier($entityCode, $permCode, $field, $fieldValue);
	}
}