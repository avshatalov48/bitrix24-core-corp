<?php

namespace Bitrix\Crm\Timeline\Rest\HistoryItem\ListParams;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\UI\PageNavigation;

final class Params
{
	public const AVAILABLE_FIELDS = [
		'ID' => ['select', 'filter', 'order'],
		'TYPE_ID' => ['select', 'filter', 'order'],
		'TYPE_CATEGORY_ID' => ['select', 'filter', 'order'],
		'CREATED' => ['select', 'filter', 'order'],
		'AUTHOR_ID' => ['select', 'filter', 'order'],
		'ASSOCIATED_ENTITY_ID' => ['select', 'filter', 'order'],
		'ASSOCIATED_ENTITY_TYPE_ID' => ['select', 'filter', 'order'],
		'ASSOCIATED_ENTITY_CLASS_NAME' => ['select', 'filter', 'order'],
		'BINDINGS' => ['select', 'filter'],
		'LAYOUT' => ['select'],
	];


	public function __construct(
		private Select $select,
		private Filter $filter,
		private array $order,
		private PageNavigation $pageNavigation
	)
	{

	}

	public function getSelect(): Select
	{
		return $this->select;
	}

	public function getFilter(): Filter
	{
		return $this->filter;
	}

	public function getOrder(): array
	{
		return $this->order;
	}

	public function getLimit(): int
	{
		return $this->pageNavigation->getLimit();
	}

	public function getOffset(): int
	{
		return $this->pageNavigation->getOffset();
	}
}