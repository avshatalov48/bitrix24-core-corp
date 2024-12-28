<?php

namespace Bitrix\HumanResources\Access\Role\System;

abstract class Base
{
	abstract public function getPermissions(): array;

	public function getMap(): array
	{
		$result = [];
		foreach ($this->getPermissions() as $permissionId => $value)
		{
			$result[] = [
				'id' => $permissionId,
				'value' => $value,
			];
		}

		return $result;
	}
}