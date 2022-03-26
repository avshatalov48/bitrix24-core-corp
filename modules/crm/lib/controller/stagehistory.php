<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Engine\Response\DataType\Page;

class StageHistory extends Controller
{
	public function listAction(
		int $entityTypeId,
		array $order = [],
		array $filter = [],
		array $select = [],
		PageNavigation $pageNavigation = null
	): ?Page
	{
		switch ($entityTypeId)
		{
			case \CCrmOwnerType::Lead:
				$dataSource = \Bitrix\Crm\History\LeadStatusHistoryEntry::class;
				break;

			case \CCrmOwnerType::Deal:
				$dataSource = \Bitrix\Crm\History\DealStageHistoryEntry::class;
				break;

			case \CCrmOwnerType::Invoice:
				$dataSource = \Bitrix\Crm\History\InvoiceStatusHistoryEntry::class;
				break;

			default:
				$this->addError(new Error(\CCrmOwnerType::ResolveName($entityTypeId) . ' entity is not supported'));

				return null;
		}
		$fields = $this->getFields($entityTypeId);

		$preparedFilter = $this->prepareFilter($filter, $fields);

		return new Page(
			'items',
			$dataSource::getListFilteredByPermissions([
				'order' => $this->prepareOrder($order, $fields),
				'filter' => $preparedFilter,
				'select' => $this->prepareSelect($select, $fields),
				'offset' => $pageNavigation->getOffset(),
				'limit' => $pageNavigation->getLimit(),
			]),
			function() use ($preparedFilter, $dataSource) {
				return $dataSource::getItemsCountFilteredByPermissions($preparedFilter);
			}
		);
	}

	private function prepareOrder(array $order, array $fields): array
	{
		$result = [];
		foreach ($order as $sortField => $sortOrder)
		{
			if (in_array($sortField, $fields))
			{
				$result[$sortField] =
					mb_strtolower($sortOrder) === 'desc'
						? 'desc'
						: 'asc';
			}
		}

		return $result;
	}

	private function prepareFilter(array $filter, array $fields): array
	{
		$result = [];

		$sqlWhere = new \CSQLWhere();
		foreach ($filter as $filterKey => $filterValue)
		{
			$filterCondition = $sqlWhere->MakeOperation($filterKey);
			if (!in_array($filterCondition['FIELD'], $fields))
			{
				continue;
			}
			if (
			!in_array(
				$filterCondition['OPERATION'],
				[
					'NB', //not between (!><)
					'NI', //not Identical (!=)
					'B',  //between (><)
					'GE', //greater or equal (>=)
					'LE', //less or equal (<=)
					'NIN', //not in (!@)
					'I', //Identical (=)
					'G', //greater (>)
					'L', //less (<)
					'IN', // IN (@)
					'E' // no operation
				],
				true
			)
			)
			{
				continue;
			}
			if ($filterCondition['OPERATION'] === 'E') // if no operation, change to strong equality
			{
				$filterKey = '=' . $filterKey;
			}
			if ($filterCondition['FIELD'] === 'CREATED_TIME')
			{
				$filterValue = \CRestUtil::unConvertDateTime($filterValue);
			}

			$result[$filterKey] = $filterValue;
		}

		return $result;
	}

	private function prepareSelect(array $select, array $fields): array
	{
		$result = [];
		foreach ($select as $field)
		{
			if (in_array($field, $fields))
			{
				$result[] = $field;
			}
		}
		if (empty($result))
		{
			$result = $fields;
		}
		if (!in_array('ID', $result))
		{
			$result[] = 'ID';
		}

		return $result;
	}

	private function getFields(int $entityTypeId): array
	{
		$fields = [
			'ID',
			'TYPE_ID',
			'OWNER_ID',
			'CREATED_TIME',
		];

		switch ($entityTypeId)
		{
			case \CCrmOwnerType::Lead:
				$fields[] = 'STATUS_SEMANTIC_ID';
				$fields[] = 'STATUS_ID';
				break;

			case \CCrmOwnerType::Deal:
				$fields[] = 'CATEGORY_ID';
				$fields[] = 'STAGE_SEMANTIC_ID';
				$fields[] = 'STAGE_ID';
				break;

			case \CCrmOwnerType::Invoice:
				$fields[] = 'STATUS_SEMANTIC_ID';
				$fields[] = 'STATUS_ID';
				break;
		}

		return $fields;
	}
}
