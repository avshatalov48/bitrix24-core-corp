<?php

namespace Bitrix\Tasks\Grid\Scope\Types;
use Bitrix\Tasks\Grid\ScopeStrategyInterface;

class SpaceStrategy implements ScopeStrategyInterface
{
	public function apply(array $gridHeaders): array
	{
		array_map(function (array $item, string $key) use (&$gridHeaders): void {
			$gridHeaders[$key]['default'] = in_array($key, $this->getHeaders(), true);
		}, $gridHeaders, array_keys($gridHeaders));

		usort($gridHeaders, $this->getComparator());

		return $gridHeaders;
	}

	private function getComparator(): callable
	{
		return function (array $first, array $second) {
			$firstKey = array_search($first['id'], $this->getHeaders());
			$secondKey = array_search($second['id'], $this->getHeaders());
			if ($firstKey !== false && $secondKey !== false)
			{
				return $firstKey <=> $secondKey;
			}

			return 0;
		};
	}

	private function getHeaders(): array
	{
		return [
			'TITLE',
			'ACTIVITY_DATE',
			'DEADLINE',
			'RESPONSIBLE_NAME',
			'ORIGINATOR_NAME',
		];
	}
}
