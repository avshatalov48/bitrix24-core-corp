<?php

namespace Bitrix\Crm\Integration\Main\UISelector\EntitySelection\Strategy;

use Bitrix\Crm\Integration\Main\UISelector\EntitySelection\Strategy;

class HiddenEntities extends Strategy
{
	public function getEntities(array $items): array
	{
		return $items;
	}

	public function getEntitiesIDs(array $items): array
	{
		$hiddenEntitiesIDs = [];

		$prefix = $this->entity->getPrefix();
		$itemCodePattern = '/^' . $prefix . '(\d+)$/';

		foreach ($items as $hiddenItem)
		{
			$hiddenEntitiesIDs[] = preg_replace(
				$itemCodePattern,
				'$1',
				$hiddenItem,
			);
		}

		return $hiddenEntitiesIDs;
	}
}
