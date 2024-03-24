<?php

namespace Bitrix\Crm\Timeline\Rest\HistoryItem\ListParams;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Crm\Service\Converter;

final class Builder
{
	use Singleton;

	private Converter\OrmObject $converter;

	public function __construct()
	{
		$this->converter = Container::getInstance()->getOrmObjectConverter();
	}

	public static function build(
		?array $rawSelect = ['*'],
		?array $rawOrder = null,
		?array $rawFilter = null,
		PageNavigation $pageNavigation,
	): Params
	{
		return self::getInstance()->execute($rawSelect, $rawOrder, $rawFilter, $pageNavigation);
	}

	public function execute(
		?array $rawSelect = ['*'],
		?array $rawOrder = null,
		?array $rawFilter = null,
		PageNavigation $pageNavigation,
	): Params
	{
		return new Params(
			select: $this->prepareSelect($rawSelect ?? ['*']),
			filter: $this->prepareFilter($rawFilter ?? []),
			order: $this->prepareOrder($rawOrder ?? []),
			pageNavigation: $pageNavigation,
		);
	}

	private function prepareSelect(array $select): Select
	{
		$select = array_map(fn ($fName) => $this->converter->convertFieldNameFromCamelCaseToUpperCase($fName), $select);
		$allSelectable = [];
		foreach (Params::AVAILABLE_FIELDS as $field => $config)
		{
			if (in_array('select', $config))
			{
				$allSelectable[] = $field;
			}
		}

		if (in_array('*', $select, true))
		{
			return new Select($allSelectable);
		}
		if (!in_array('ID', $select, true))
		{
			$select[] = 'ID';
		}

		return new Select(
			array_filter($select, fn($fName) => in_array($fName, $allSelectable))
		);
	}

	private function prepareFilter(array $filter): Filter
	{
		$filter = $this->converter->convertKeysToUpperCase($filter);
		$allFilterable = [];
		foreach (Params::AVAILABLE_FIELDS as $field => $config)
		{
			if (in_array('filter', $config))
			{
				$allFilterable[] = $field;
			}
		}
		$prefixes = ['=', '%', '>', '<', '@', '!=', '!%', '><', '>=', '<=', '=%', '%=',	'!><', '!=%', '!%='];

		$result = [];
		foreach ($filter as $field => $value)
		{
			$cleanField = str_replace($prefixes, '', $field);
			if (in_array($cleanField, $allFilterable, true))
			{
				$result[$field] = $value;
			}
		}

		return new Filter($result);
	}

	private function prepareOrder(array $order): array
	{
		$order = $this->converter->convertKeysToUpperCase($order);
		$allOrderable = [];
		foreach (Params::AVAILABLE_FIELDS as $field => $config)
		{
			if (in_array('order', $config))
			{
				$allOrderable[] = $field;
			}
		}

		return array_filter($order, fn($fName) => in_array($fName, $allOrderable), ARRAY_FILTER_USE_KEY);
	}
}