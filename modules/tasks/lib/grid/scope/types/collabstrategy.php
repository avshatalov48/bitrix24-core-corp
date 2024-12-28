<?php

namespace Bitrix\Tasks\Grid\Scope\Types;
use Bitrix\Tasks\Grid\ScopeStrategyInterface;

class CollabStrategy implements ScopeStrategyInterface
{
	public function apply(array &$gridHeaders, array $parameters = []): void
	{
		foreach ($gridHeaders as $key => $header)
		{
			$gridHeaders[$key]['default'] = in_array($key, $this->getDefaultHeaders(), true);
		}

		usort($gridHeaders, $this->getComparator());
	}

	private function getComparator(): callable
	{
		return function (array $first, array $second) {
			$firstKey = array_search($first['id'], $this->getDefaultHeaders(), true);
			$secondKey = array_search($second['id'], $this->getDefaultHeaders(), true);
			if ($firstKey !== false && $secondKey !== false)
			{
				return $firstKey <=> $secondKey;
			}

			return 0;
		};
	}

	private function getDefaultHeaders(): array
	{
		return [
			'TITLE',
			'ACTIVITY_DATE',
			'DEADLINE',
			'ORIGINATOR_NAME',
			'RESPONSIBLE_NAME',
			'REAL_STATUS',
		];
	}
}
