<?php

namespace Bitrix\Crm\Search;

use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
use Bitrix\Main\Application;
use Bitrix\Main\DB;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Data;
use CCrmFieldMulti;
use CCrmOwnerType;

abstract class SearchContentBuilder
{
	/**
	 * CRM entity class name
	 *
	 * @var string
	 */
	protected string $entityClassName = '';

	/**
	 * CRM entity index table class name
	 *
	 * @var string
	 */
	protected string $entityIndexTableClassName = '';

	/**
	 * CRM entity indexes table name (b_crm_company_index|b_crm_contact_index|b_crm_deal_index|b_crm_lead_index)
	 *
	* @var string
	 */
	protected string $entityIndexTable = '';

	/**
	 * CRM entity indexes table column name (COMPANY_ID|CONTACT_ID|DEAL_ID|LEAD_ID)
	 *
	 * @var string
	 */
	protected string $entityIndexTablePrimaryColumn = '';


	/**
	 * Method prepare Search Map. Map is source of text for fulltext search index
	 *
	 * @param array 	 $fields	Entity Fields
	 * @param array|null $options	Options
	 *
	 * @return SearchMap
	 */
	abstract protected function prepareSearchMap(array $fields, array $options = null): SearchMap;

	/**
	 * Method save content index
	 *
	* @param int 		$entityId
	* @param SearchMap	$map
	 *
	* @return void
	 */
	abstract protected function save(int $entityId, SearchMap $map): void;

	/**
	 * Method return entity type ID
	 *
	 * @return int
	 */
	public function getEntityTypeId(): int
	{
		return CCrmOwnerType::Undefined;
	}

	/**
	 * Method prepare filter for full text search
	 *
	* @param array $params
	 *
	* @return array
	 */
	public function prepareEntityFilter(array $params): array
	{
		$value = $params['SEARCH_CONTENT'] ?? '';
		if (!is_string($value) || $value === '')
		{
			return [];
		}

		return [
			'*SEARCH_CONTENT' => SearchEnvironment::prepareToken($value),
		];
	}

	public function removeShortIndex(int $entityId): Data\Result
	{
		if (
			!empty($this->entityIndexTablePrimaryColumn)
			&& class_exists($this->entityIndexTableClassName)
			&& method_exists($this->entityIndexTableClassName, 'delete')
		)
		{
			return $this->entityIndexTableClassName::delete([$this->entityIndexTablePrimaryColumn => $entityId]);
		}

		return new Data\Result(); // not implemented by default
	}

	public function convertEntityFilterValues(array &$filter): void
	{
		$this->transferEntityFilterKeys(['FIND'], $filter);
	}

	public function getSearchContent(int $entityId, array $options = null): string
	{
		$fields = $this->prepareEntityFields($entityId);
		if (!is_array($fields))
		{
			return '';
		}

		return $this->prepareSearchMap($fields, $options)->getString();
	}

	public function build(int $entityId, array $options = null): void
	{
		$fields = $this->prepareEntityFields($entityId);
		if (is_array($fields))
		{
			if (!isset($options['onlyShortIndex']) || !$options['onlyShortIndex'])
			{
				$this->save($entityId, $this->prepareSearchMap($fields, $options));
			}

			$options['isShortIndex'] = true;
			$this->saveShortIndex(
				$entityId,
				$this->prepareSearchMap($fields, $options),
			);
		}
	}

	public function bulkBuild(array $entityIds): void
	{
		$this->prepareForBulkBuild($entityIds);

		foreach ($entityIds as $entityId)
		{
			$fields = $this->prepareEntityFields($entityId);
			if (is_array($fields))
			{
				$this->save($entityId, $this->prepareSearchMap($fields));
				$this->saveShortIndex(
					(int)$entityId,
					$this->prepareSearchMap($fields, ['isShortIndex' => true]),
				);
			}
		}
	}

	/**
	 * Method prepare entity fields
	 *
	 * @param int $entityId
	 *
	 * @return array|null
	 */
	protected function prepareEntityFields(int $entityId): ?array
	{
		if (class_exists($this->entityClassName))
		{
			if (method_exists($this->entityClassName, 'GetListEx'))
			{
				$dbResult = $this->entityClassName::GetListEx(
					[],
					['=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N'],
					false,
					false,
					['*'/*, 'UF_*'*/]
				);
			}
			elseif (method_exists($this->entityClassName, 'GetList'))
			{
				$dbResult = $this->entityClassName::GetList(
					[],
					['=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N'],
					false,
					false,
					['*'/*, 'UF_*'*/]
				);
			}

			$fields = isset($dbResult) ? $dbResult->Fetch() : null;

			return is_array($fields) ? $fields : null;
		}

		return null;
	}

	/**
	 * Prepare data for bulk indexes build
	 *
	* @param int[] $entityIds
	 *
	* @return void
	 */
	protected function prepareForBulkBuild(array $entityIds): void
	{
		if (class_exists($this->entityClassName))
		{
			if (method_exists($this->entityClassName, 'GetListEx'))
			{
				$dbResult = $this->entityClassName::GetListEx(
					[],
					['@ID' => $entityIds, 'CHECK_PERMISSIONS' => 'N'],
					['ASSIGNED_BY_ID'],
					false,
					['ASSIGNED_BY_ID']
				);
			}
			elseif (method_exists($this->entityClassName, 'GetList'))
			{
				$dbResult = $this->entityClassName::GetList(
					[],
					['@ID' => $entityIds, 'CHECK_PERMISSIONS' => 'N'],
					['ASSIGNED_BY_ID'],
					false,
					['ASSIGNED_BY_ID']
				);
			}

			if (!isset($dbResult))
			{
				return;
			}

			$userIds = [];
			while ($fields = $dbResult->Fetch())
			{
				$userIds[] = (int)$fields['ASSIGNED_BY_ID'];
			}

			if (!empty($userIds))
			{
				SearchMap::cacheUsers($userIds);
			}
		}
	}

	protected function saveShortIndex(int $entityId, SearchMap $map): ?DB\Result
	{
		if (
			empty($this->entityIndexTable)
			|| empty($this->entityIndexTablePrimaryColumn)
		)
		{
			return null;
		}

		return $this->saveSearchContent($this->entityIndexTable, $this->entityIndexTablePrimaryColumn, $entityId, $map);
	}

	protected function prepareUpdateData(string $tableName, string $searchContent): array
	{
		$helper = Application::getConnection()->getSqlHelper();
		$preparedSearchContent = $helper->forSql($searchContent);
		$encryptedSearchContent = sha1($searchContent);

		return [
			$this->getSearchFieldName() => new SqlExpression(
				"CASE WHEN " . $helper->getSha1Function('?#.?#') . " = '{$encryptedSearchContent}' THEN ?#.?# ELSE '{$preparedSearchContent}' END",
				$tableName,
				$this->getSearchFieldName(),
				$tableName,
				$this->getSearchFieldName()
			),
		];
	}

	protected function parseCustomerNumber(string $str, string $template): string
	{
		$regex = $template !== ''
			? '/^'.str_replace(['%NUMBER%', ' '], ['\s*(.+)\s*', '\s*'], $template).'$/'
			: '/(?:#|Nr.?)\s*(.+)/';

		if (preg_match($regex, $str, $m) === 1 && isset($m[1]))
		{
			return $m[1];
		}
		
		return '';
	}

	protected function getSearchFieldName(): string
	{
		return 'SEARCH_CONTENT';
	}

	/**
	 * Transfer specified filter keys to search content
	 * 
	 * @param array $sourceKeys	Filter keys for transfer to search
	 * @param array $filter		List Filter
	 */
	protected function transferEntityFilterKeys(array $sourceKeys, array &$filter): void
	{
		$searchFieldName = $this->getSearchFieldName();
		
		foreach ($sourceKeys as $key)
		{
			if (!isset($filter[$key]))
			{
				continue;
			}

			if (is_string($filter[$key]))
			{
				$find = trim($filter[$key]);
				if ($find !== '')
				{
					$find = SearchEnvironment::prepareSearchContent($find);
					if (!isset($filter[$searchFieldName]))
					{
						$filter[$searchFieldName] = $find;
					}
					else
					{
						$filter[$searchFieldName] .= ' '.$find;
					}
				}
			}

			unset($filter[$key]);
		}
	}

	protected function getUserFieldEntityId(): string
	{
		if (
			class_exists($this->entityClassName)
			&& method_exists($this->entityClassName, 'GetUserFieldEntityID')
		)
		{
			return $this->entityClassName::GetUserFieldEntityID();
		}

		return '';
	}

	// region SearchMap wrappers
	protected function addPhonesAndEmailsToSearchMap(int $entityId, SearchMap $map): SearchMap
	{
		$multiFields = $this->getEntityMultiFields($entityId);
		if (empty($multiFields))
		{
			return $map;
		}

		if (isset($multiFields[CCrmFieldMulti::PHONE]))
		{
			foreach ($multiFields[CCrmFieldMulti::PHONE] as $multiField)
			{
				if (isset($multiField['VALUE']))
				{
					$map->addPhone($multiField['VALUE']);
				}
			}
		}

		if (isset($multiFields[CCrmFieldMulti::EMAIL]))
		{
			foreach ($multiFields[CCrmFieldMulti::EMAIL] as $multiField)
			{
				if (isset($multiField['VALUE']))
				{
					$map->addEmail($multiField['VALUE']);
				}
			}
		}

		return $map;
	}

	protected function addTitleToSearchMap(int $entityId, string $title, string $template, SearchMap $map): SearchMap
	{
		if (empty($title))
		{
			return $map;
		}

		$map->addText($title);
		$map->addText(SearchEnvironment::prepareSearchContent($title));

		$customerNumber = $this->parseCustomerNumber($title, $template);
		if ($customerNumber !== (string)$entityId)
		{
			$map->addTextFragments($customerNumber);
		}

		return $map;
	}
	// endregion

	private function getEntityMultiFields(int $entityId): array
	{
		if ($entityId <= 0)
		{
			return [];
		}

		return DuplicateCommunicationCriterion::prepareEntityMultifieldValues(
			$this->getEntityTypeId(),
			$entityId
		);
	}

	private function saveSearchContent(string $tableName, string $columnName, int $entityId, SearchMap $map): ?DB\Result
	{
		$connection = Application::getConnection();

		$merge = $connection->getSqlHelper()->prepareMerge(
			$tableName,
			[$columnName],
			[
				$this->getSearchFieldName() => $map->getString(),
				$columnName => $entityId,
			],
			$this->prepareUpdateData($tableName, $map->getString())
		);

		if ($merge[0] !== '')
		{
			return $connection->query($merge[0]);
		}

		return null;
	}
}
