<?php

namespace Bitrix\Tasks\Grid\Scope\Types;
use Bitrix\Tasks\Grid;
use Bitrix\Tasks\Grid\Scope\Scope;
use Bitrix\Tasks\Grid\ScopeInterface;

class SpacesContext implements ScopeInterface
{
	private Grid $grid;

	public function __construct(Grid $grid)
	{
		$this->grid = $grid;
	}

	public function getHeaders(): array
	{
		return [
			'TITLE',
			'ACTIVITY_DATE',
			'DEADLINE',
			'RESPONSIBLE_NAME',
			'ORIGINATOR_NAME',
		];
	}

	public function getScope(): string
	{
		return Scope::SPACES;
	}

	public function apply(): array
	{
		$headers = $this->grid->getHeaders();

		array_map(function (array $item, string $key) use (&$headers): void {
			$headers[$key]['default'] = in_array($key, $this->getHeaders(), true);
		}, $headers, array_keys($headers));

		usort($headers, $this->getComparator());

		return $headers;
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
}
