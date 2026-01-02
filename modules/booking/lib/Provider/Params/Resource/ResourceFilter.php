<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params\Resource;

use Bitrix\Booking\Internals\Model\ResourceLinkedEntityTable;
use Bitrix\Booking\Provider\Params\Filter;
use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;

class ResourceFilter extends Filter
{
	private array $filter;
	private string $initAlias;
	private Connection $connection;

	public const LINKED_ENTITY_CONDITION_ALL = 'all';
	public const LINKED_ENTITY_CONDITION_ANY = 'any';

	public function __construct(array $filter = [])
	{
		$this->filter = $filter;
		$this->connection = Application::getInstance()->getConnection();
	}

	public function prepareQuery(Query $query): void
	{
		$this->initAlias = $query->getInitAlias();

		parent::prepareQuery($query);
	}

	public function prepareFilter(): ConditionTree
	{
		$result = new ConditionTree();

		if (isset($this->filter['ID']))
		{
			if (is_array($this->filter['ID']))
			{
				$result->whereIn('ID', array_map('intval', $this->filter['ID']));
			}
			else
			{
				$result->where('ID', '=', (int)$this->filter['ID']);
			}
		}

		if (isset($this->filter['EXTERNAL_ID']))
		{
			if (is_array($this->filter['EXTERNAL_ID']))
			{
				$result->whereIn('EXTERNAL_ID', array_map('intval', $this->filter['EXTERNAL_ID']));
			}
			else
			{
				$result->where('EXTERNAL_ID', '=', (int)$this->filter['EXTERNAL_ID']);
			}
		}

		if (isset($this->filter['IS_MAIN']))
		{
			$result->where('IS_MAIN', '=', (bool)$this->filter['IS_MAIN']);
		}

		$includeDeleted = (
			isset($this->filter['INCLUDE_DELETED'])
			&& $this->filter['INCLUDE_DELETED'] === true
		);

		if (!$includeDeleted)
		{
			$result->where('DATA.IS_DELETED', '=', false);
		}

		if (isset($this->filter['TYPE_ID']))
		{
			if (is_array($this->filter['TYPE_ID']))
			{
				$result->whereIn('TYPE_ID', array_map('intval', $this->filter['TYPE_ID']));
			}
			else
			{
				$result->where('TYPE_ID', '=', (int)$this->filter['TYPE_ID']);
			}
		}

		if (isset($this->filter['NAME']))
		{
			$result->where('DATA.NAME', '=', (string)$this->filter['NAME']);
		}

		if (isset($this->filter['SEARCH_QUERY']))
		{
			$result->whereLike('DATA.NAME', '%' . $this->filter['SEARCH_QUERY'] . '%');
		}

		if (isset($this->filter['DESCRIPTION']))
		{
			$result->where('DATA.DESCRIPTION', '=', (string)$this->filter['DESCRIPTION']);
		}

		if (
			isset($this->filter['LINKED_ENTITY']['TYPE'])
			&& isset($this->filter['LINKED_ENTITY']['ID'])
			&& is_array($this->filter['LINKED_ENTITY']['ID'])
			&& !empty($this->filter['LINKED_ENTITY']['ID'])
		)
		{
			$condition =
				(
					isset($this->filter['LINKED_ENTITY']['CONDITION'])
					&& in_array(
						$this->filter['LINKED_ENTITY']['CONDITION'],
						[
							self::LINKED_ENTITY_CONDITION_ALL,
							self::LINKED_ENTITY_CONDITION_ANY,
						],
						true
					)
				)
					? $this->filter['LINKED_ENTITY']['CONDITION']
					: self::LINKED_ENTITY_CONDITION_ALL
			;

			$entityIds = array_unique(array_map('intval', $this->filter['LINKED_ENTITY']['ID']));

			if ($condition === self::LINKED_ENTITY_CONDITION_ALL)
			{
				$resourceIds = array_column(
					Application::getConnection()->query("
						SELECT RESOURCE_ID
						FROM " . ResourceLinkedEntityTable::getTableName() . "
						WHERE
							ENTITY_TYPE = 'sku'
						  	AND ENTITY_ID IN (" . implode(', ', $entityIds) . ")
						GROUP BY RESOURCE_ID
						HAVING COUNT(1) = " . count($entityIds) . "
					")->fetchAll(),
					'RESOURCE_ID'
				);

				$result->whereIn('ID', empty($resourceIds) ? [0] : $resourceIds);
			}
			else
			{
				//@todo if/when needed
			}
		}

		if (isset($this->filter['HAS_LINKED_ENTITIES_OF_TYPE']))
		{
			$result->where(
				Query::filter()->whereExists(
					new SqlExpression("
					SELECT 1
					FROM " . ResourceLinkedEntityTable::getTableName() . "
					WHERE
						RESOURCE_ID = " . $this->initAlias . ".ID
						AND ENTITY_TYPE = '" . $this->connection->getSqlHelper()->forSql(
							$this->filter['HAS_LINKED_ENTITIES_OF_TYPE']
						) . "'
				")
				)
			);
		}

		return $result;
	}
}
