<?php

namespace Bitrix\Crm\Timeline\Rest\HistoryItem\ListParams;

final class Filter
{
	private const EXCLUDE_FROM_TIMELINE_FILTER = ['BINDINGS'];

	public function __construct(private array $filter)
	{
	}

	public function getFilter(): array
	{
		return $this->filter;
	}

	public function getOnlyTimelineTableFilterFields(): array
	{
		return array_filter(
			$this->filter,
			fn($fName) => !in_array($fName, self::EXCLUDE_FROM_TIMELINE_FILTER, true),
			ARRAY_FILTER_USE_KEY
		);
	}

	public function hasBindingsFilter(): bool
	{
		return array_key_exists('BINDINGS', $this->filter) && !empty($this->filter['BINDINGS']);
	}

	public function getBindingsFilter(): ?array
	{
		$bindingsFilter = $this->filter['BINDINGS'] ?? null;

		if (!is_array($bindingsFilter))
		{
			return [];
		}

		if (isset($bindingsFilter['ENTITY_TYPE_ID']) && isset($bindingsFilter['ENTITY_ID']))
		{
			return [
				[
					'ENTITY_TYPE_ID' => $bindingsFilter['ENTITY_TYPE_ID'],
					'ENTITY_ID' => $bindingsFilter['ENTITY_ID'],
				]
			];
		}

		return $bindingsFilter;
	}
}