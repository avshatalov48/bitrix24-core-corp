<?php

namespace Bitrix\Crm\Integration\Main\UISelector\EntitySelection;

abstract class Strategy
{
	public function __construct(protected Entity $entity)
	{
	}

	abstract public function getEntities(array $items): array;
	abstract public function getEntitiesIDs(array $items): array;
}
