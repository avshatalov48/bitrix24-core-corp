<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder\Conditions;

class UseJoin
{
	public function __construct(
		private string $identityColumnName
	)
	{
	}

	public function getIdentityColumnName(): string
	{
		return $this->identityColumnName;
	}
}