<?php

namespace Bitrix\Crm\Search;

use Bitrix\Crm\Field;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Search\Content;

final class DynamicTypeSearchContentBuilder extends SearchContentBuilder
{
	protected int $entityTypeId;

	public function __construct(int $entityTypeId)
	{
		if ($entityTypeId > 0)
		{
			$this->entityTypeId = $entityTypeId;
		}
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function prepareEntityFilter(array $params): array
	{
		$value = $params['SEARCH_CONTENT'] ?? '';
		if (!is_string($value) || $value === '')
		{
			return [];
		}
		
		// Reference to a separate FULL_TEXT table used for fulltext indexes in the Dynamic Type
		return [
			"*FULL_TEXT.SEARCH_CONTENT" => SearchEnvironment::prepareToken($this->prepareTokenCompatible($value))
		];
	}

	public function delete(int $entityId): void
	{
		$this->getIndexDataClass()::delete($entityId);
	}
	
	protected function getUserFieldEntityId(): string
	{
		return $this->getFactory()?->getUserFieldEntityId() ?? '';
	}

	protected function prepareEntityFields(int $entityId): ?array
	{
		return $this->getFactory()?->getItem($entityId)?->getData();
	}

	protected function prepareSearchMap(array $fields, array $options = null): SearchMap
	{
		$map = new SearchMap();
		$entityId = (int)($fields['ID'] ?? 0);
		if ($entityId <= 0)
		{
			return $map;
		}

		$map->add($this->prepareTokenCompatible($entityId));

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
			elseif ($assigned > 0)
			{
				$map->addUserByID($assigned);
			}
		}

		$userFields = SearchEnvironment::getUserFields($entityId, $this->getUserFieldEntityId());
		foreach ($userFields as $userField)
		{
			if (!empty($userField['VALUE']))
			{
				$userField['VALUE'] = $this->prepareTokenCompatible($userField['VALUE']);
			}

			$map->addUserField($userField);
		}

		return $map;
	}

	protected function prepareForBulkBuild(array $entityIds): void
	{
		$items = $this->getFactory()?->getItems([
			'select' => $this->getFieldsWithUsers(),
			'filter' => [
				'@ID' => $entityIds,
			]
		]);

		$userIds = $this->extractUserIdsFromItems($items);
		if (!empty($userIds))
		{
			SearchMap::cacheUsers($userIds);
		}
	}

	protected function save(int $entityId, SearchMap $map): void
	{
		$this->getIndexDataClass()::merge($entityId, $map->getString());
	}

	/**
	 * Prepares search token before SearchEnvironment::prepareToken for compatibility
	 * @see SearchEnvironment::prepareToken()
	 *
	 * @param $query
	 *
	 * @return string|mixed
	 */
	private function prepareTokenCompatible($query)
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

	private function extractUserIdsFromItems(array $items): array
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

		return array_unique($userIds);
	}

	private function getFactory(): ?Factory
	{
		return Container::getInstance()->getFactory($this->entityTypeId);
	}

	private function getIndexDataClass()
	{
		return $this->getFactory()?->getFulltextDataClass();
	}

	private function getFieldsWithUsers(): array
	{
		$fieldsCollection = $this->getFactory()?->getFieldsCollection();
		if (!isset($fieldsCollection))
		{
			return [];
		}

		$fieldsWithUsers = [];
		foreach ($fieldsCollection as $field)
		{
			if ($field->getType() === Field::TYPE_USER && !$field->isMultiple())
			{
				$fieldsWithUsers[] = $field->getName();
			}
		}

		return $fieldsWithUsers;
	}

	private function addMultipleUsersToSearchMap(array $userIds, SearchMap $map): SearchMap
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
}
