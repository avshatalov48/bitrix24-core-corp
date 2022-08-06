<?php

namespace Bitrix\Crm\Search;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Model\Dynamic\PrototypeItemIndex;
use Bitrix\Main\Search\Content;

class DynamicTypeSearchContentBuilder extends SearchContentBuilder
{
	protected $entityTypeId;

	public function __construct(int $entityTypeId)
	{
		if ($entityTypeId > 0)
		{
			$this->entityTypeId = $entityTypeId;
		}
	}

	public function getEntityTypeID(): int
	{
		return $this->entityTypeId;
	}

	public function isFullTextSearchEnabled(): bool
	{
		return true;
	}

	protected function getUserFieldEntityID(): string
	{
		return Container::getInstance()->getFactory($this->entityTypeId)->getUserFieldEntityId();
	}

	protected function getFactory(): Factory
	{
		return Container::getInstance()->getFactory($this->entityTypeId);
	}

	/**
	 * @return PrototypeItemIndex
	 */
	protected function getIndexDataClass(): string
	{
		return $this->getFactory()->getFulltextDataClass();
	}

	/**
	 * Get names of fields containing user IDs
	 * @return string[]
	 */
	protected function getFieldsWithUsers(): array
	{
		$fieldsWithUsers = [];
		foreach ($this->getFactory()->getFieldsCollection() as $field)
		{
			if ($field->getType() === Field::TYPE_USER && !$field->isMultiple())
			{
				$fieldsWithUsers[] = $field->getName();
			}
		}

		return $fieldsWithUsers;
	}

	protected function prepareEntityFields($entityID): ?array
	{
		return $this->getFactory()->getItem($entityID)->getData();
	}

	protected function prepareForBulkBuild(array $entityIDs)
	{
		$items = $this->getFactory()->getItems([
			'select' => $this->getFieldsWithUsers(),
			'filter' => ['@ID' => $entityIDs]
		]);

		$userIds = static::extractUserIdsFromItems($items);

		if (!empty($userIds))
		{
			SearchMap::cacheUsers($userIds);
		}
	}

	/**
	 * @param Item[] $items
	 *
	 * @return array
	 */
	protected function extractUserIdsFromItems(array $items): array
	{
		$fieldsWithUsers = $this->getFieldsWithUsers();
		$userIds = [];
		foreach ($items as $item)
		{
			foreach ($fieldsWithUsers as $fieldName)
			{
				$userIds = $item->get($fieldName);
			}
		}

		$userIds = array_filter($userIds, function($id)
		{
			return !empty($id);
		});
		$userIds = array_unique($userIds);

		return $userIds;
	}

	/**
	 * Prepares search token before SearchEnvironment::prepareToken for compatibility
	 * @see SearchEnvironment::prepareToken()
	 * @param $query
	 *
	 * @return string|mixed
	 */
	protected function prepareTokenCompatible($query)
	{
		if (!is_string($query) && !is_int($query))
		{
			return $query;
		}

		$query = trim($query);
		// Old API (SearchEnvironment::prepareToken) doesn't process integer tokens
		if (Content::isIntegerToken($query))
		{
			$query = Content::prepareIntegerToken($query);
		}

		return $query;
	}

	public function prepareEntityFilter(array $params): array
	{
		$value = $params['SEARCH_CONTENT'] ?? '';
		if (!is_string($value) || $value === '')
		{
			return [];
		}

		$operation = $this->isFullTextSearchEnabled() ? '*' : '*%';

		// Reference to a separate FULL_TEXT table used for fulltext indexes in the Dynamic Type
		return [
			"{$operation}FULL_TEXT.SEARCH_CONTENT" => SearchEnvironment::prepareToken($this->prepareTokenCompatible($value))
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function prepareSearchMap(array $fields, array $options = null): SearchMap
	{
		$map = new SearchMap();

		$entityID = $fields['ID'] ?? 0;
		if ($entityID <= 0)
		{
			return $map;
		}

		$map->add($this->prepareTokenCompatible($entityID));

		if (isset($fields['TITLE']))
		{
			$map->addText($this->prepareTokenCompatible($fields['TITLE']));
		}

		if (isset($fields['ACCOUNT_NUMBER']))
		{
			$map->addText($this->prepareTokenCompatible($fields['ACCOUNT_NUMBER']));
		}

		foreach ($this->getFieldsWithUsers() as $fieldName)
		{
			if (isset($fields[$fieldName]) && $fields[$fieldName] > 0)
			{
				$map->addUserByID($fields[$fieldName]);
			}
		}

		if (isset($fields['ASSIGNED_BY_ID']))
		{
			$assigned = $fields['ASSIGNED_BY_ID'];
			if (is_array($assigned))
			{
				$map = $this->addMultipleUsersToSearchMap($assigned, $map);
			}
			else
			{
				if ($assigned > 0)
				{
					$map->addUserByID($assigned);
				}
			}
		}

		foreach($this->getUserFields($entityID) as $userField)
		{
			if (!empty($userField['VALUE']))
			{
				$userField['VALUE'] = $this->prepareTokenCompatible($userField['VALUE']);
			}
			$map->addUserField($userField);
		}

		return $map;
	}

	protected function addMultipleUsersToSearchMap(array $userIds, SearchMap $map): SearchMap
	{
		foreach ($userIds as $userId)
		{
			if ($userId > 0)
			{
				$map->addUserByID($userId);
			}
		}

		return $map;
	}

	protected function save($entityID, SearchMap $map): void
	{
		$this->getIndexDataClass()::merge($entityID, $map->getString());
	}

	public function delete($entityID): void
	{
		$this->getIndexDataClass()::delete($entityID);
	}
}