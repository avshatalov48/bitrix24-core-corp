<?php

namespace Bitrix\Crm\Security\QueryBuilder\Result;

use Bitrix\Crm\Security\Controller\QueryBuilder\Conditions\RestrictedConditionsList;
use Bitrix\Main\ORM\Entity;

interface ResultOption
{
	public function getIdentityColumnName(): string;

	public function make(Entity $entity, RestrictedConditionsList $conditions, string $prefix = ''): string;

	public function makeCompatible(string $querySql, string $prefix = ''): string;
}
