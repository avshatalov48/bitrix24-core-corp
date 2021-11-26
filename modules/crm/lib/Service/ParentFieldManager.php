<?php


namespace Bitrix\Crm\Service;

use Bitrix\Crm\Field;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Result;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Crm\Item;
use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\Relation\EntityRelationTable;

class ParentFieldManager
{
	public const FIELD_PARENT_PREFIX = 'PARENT_ID';

	protected $parents = [];

	/**
	 * Return an array of items of a parents entities with binding to the elements of the current entity
	 *
	 * @param array $itemIds Array of items IDs
	 * @param array $entityFields Array of parent fields names
	 * @param int $entityTypeId ID current (children) entity
	 * @return array Array of items of a parents entities
	 */
	public function getParentFields(array $itemIds, array $entityFields, int $entityTypeId): array
	{
		$parentRelationElements = $this->getParentRelationElements($itemIds, $entityFields, $entityTypeId);

		if (count($parentRelationElements))
		{
			$elementRelationsIds = $this->getElementRelationIds($parentRelationElements);
			$this->loadParentElements($elementRelationsIds);
			$this->prepareParentElements($parentRelationElements);
		}

		return $this->parents;
	}

	/**
	 * Return all binding parent elements for all $childrenIds
	 *
	 * @param array $childrenIds
	 * @param array $fields
	 * @param int $entityTypeId
	 * @return array
	 */
	protected function getParentRelationElements(
		array $childrenIds,
		array $fields,
		int $entityTypeId
	): array
	{
		$parentIds = [];
		foreach ($fields as $field)
		{
			if (self::isParentFieldName($field))
			{
				$parentIds[] = (int)str_replace(self::FIELD_PARENT_PREFIX . '_', '', $field);
			}
		}

		$result = [];
		if (count($parentIds))
		{
			$result = EntityRelationTable::getList([
				'select' => [
					'SRC_ENTITY_TYPE_ID',
					'SRC_ENTITY_ID',
					'DST_ENTITY_ID'
				],
				'filter' => [
					'DST_ENTITY_TYPE_ID' => $entityTypeId,
					'DST_ENTITY_ID' => $childrenIds,
					'SRC_ENTITY_TYPE_ID' => $parentIds,
				]
			])->fetchAll();
		}

		return $result;
	}

	/**
	 * Exclude duplication and create an array of IDs of all related parent elements
	 *
	 * @param array $parentRelationElements
	 * @return array
	 */
	protected function getElementRelationIds(array $parentRelationElements): array
	{
		$elementRelationIds = [];
		foreach ($parentRelationElements as $parentRelationsElement)
		{
			$parentEntityId = (int)$parentRelationsElement['SRC_ENTITY_ID'];
			$parentEntityTypeId = (int)$parentRelationsElement['SRC_ENTITY_TYPE_ID'];

			if (
				!isset($elementRelationIds[$parentEntityTypeId])
				|| !in_array($parentEntityId, $elementRelationIds[$parentEntityTypeId], true)
			)
			{
				$elementRelationIds[$parentEntityTypeId][] = $parentEntityId;
			}
		}
		return $elementRelationIds;
	}

	/**
	 * Get items of bound parent entities
	 *
	 * @param array $elementRelationsIds
	 */
	protected function loadParentElements(array $elementRelationsIds): void
	{
		foreach ($elementRelationsIds as $parentElementTypeId => $parentElementIds)
		{
			if (\CCrmOwnerType::isPossibleDynamicTypeId($parentElementTypeId))
			{
				$factory = Container::getInstance()->getFactory($parentElementTypeId);
				if ($factory)
				{
					$parentElements = $factory->getItemsFilteredByPermissions([
						'select' => [
							Item::FIELD_NAME_TITLE,
						],
						'filter' => [
							'@'.\Bitrix\Crm\Item::FIELD_NAME_ID => $parentElementIds,
						]
					]);

					$router = Container::getInstance()->getRouter();
					$items = [];
					foreach ($parentElements as $parent)
					{
						$parentId = $parent->getId();
						$url = $router->getItemDetailUrl(
							$parent->getEntityTypeId(),
							$parentId,
							$parent->getCategoryId()
						);
						$title = HtmlFilter::encode($parent->getTitle());
						$items[$parentId] = [
							'id' => $parentId,
							'code' => self::FIELD_PARENT_PREFIX . '_' . $parent->getEntityTypeId(),
							'entityDescription' => HtmlFilter::encode($factory->getEntityDescription()),
							'title' => $title,
							'url' => $url,
							'value' => '<a href="' . $url . '">' . $title . '</a>',
						];
					}
					$this->parents[$parentElementTypeId] = $items;
				}
			}
			else
			{
				$provider = $this->getItemsProvider($parentElementTypeId);
				$methodName = 'getListEx';
				$filter = [
					'@ID' => $parentElementIds,
				];

				if (mb_strtolower($provider) === '\\' . mb_strtolower(\CCrmQuote::class))
				{
					$methodName = 'getList';
					$filter['CHECK_PERMISSIONS'] = 'Y';
				}

				if (class_exists($provider) && method_exists($provider, $methodName))
				{
					$res = $provider::$methodName(
						[],
						$filter,
						false,
						[],
						[
							'ID',
							'TITLE',
						]
					);

					$items = [];
					while ($parent = $res->fetch())
					{
						$parentId = $parent['ID'];
						$url = $this->getUrl($parentId, \CCrmOwnerType::ResolveName($parentElementTypeId));
						$title = HtmlFilter::encode($parent['TITLE']);
						$entityDescription = HtmlFilter::encode(\CCrmOwnerType::GetDescription($parentElementTypeId));
						$items[$parentId] = [
							'id' => $parentId,
							'code' => self::FIELD_PARENT_PREFIX . '_' . $parentElementTypeId,
							'entityDescription' => $entityDescription,
							'title' => $title,
							'url' => $url,
							'value' => '<a href="' . $url . '">' . $title . '</a>',
						];
					}
					$this->parents[$parentElementTypeId] = $items;
				}
			}
		}
	}

	/**
	 * Create an array from an array of relations with parent elements
	 * and the elements from loadParentElements function
	 *
	 * @param array $parentRelationElements
	 */
	protected function prepareParentElements(array $parentRelationElements): void
	{
		$parents = [];
		foreach($parentRelationElements as $relation)
		{
			$parentEntityId = (int)$relation['SRC_ENTITY_ID'];
			$parentEntityTypeId = (int)$relation['SRC_ENTITY_TYPE_ID'];
			$childrenEntityId = (int)$relation['DST_ENTITY_ID'];

			$element = $this->parents[$parentEntityTypeId][$parentEntityId];
			$parents[$childrenEntityId][$parentEntityTypeId] = $element;
		}
		$this->parents = $parents;
	}

	/**
	 * Check if a field name is a parent field name,
	 * e.g. PARENT_ID_128 - this is a field that refers to the parent entity with id = 128
	 *
	 * @param string $fieldName
	 * @return bool
	 */
	public static function isParentFieldName(string $fieldName): bool
	{
		return (mb_strpos($fieldName, self::FIELD_PARENT_PREFIX) === 0);
	}

	/**
	 * Create a field name for the parent entity
	 *
	 * @param $entityTypeId
	 * @return string
	 */
	public static function getParentFieldName($entityTypeId): string
	{
		return (self::FIELD_PARENT_PREFIX . '_' . $entityTypeId);
	}

	/**
	 * Get id of a parent entity from field name
	 * e.g. PARENT_ID_128 - return 128
	 *
	 * @param string $fieldName
	 * @return int
	 */
	public static function getEntityTypeIdFromFieldName(string $fieldName): int
	{
		return (int)str_replace(self::FIELD_PARENT_PREFIX . '_', '', $fieldName);
	}

	/**
	 * @param int $entityTypeId
	 * @return string \CCrmLead|\CCrmDeal|\CCrmInvoice|\CCrmQuote
	 */
	protected function getItemsProvider(int $entityTypeId): string
	{
		return '\CCrm' . \CCrmOwnerType::ResolveName($entityTypeId);
	}

	/**
	 * @param int $entityId
	 * @param string $entityTypeName
	 * @return string
	 */
	protected function getUrl(int $entityId, string $entityTypeName): string
	{
		return str_replace(
			Entity::getPathMarkers(),
			$entityId,
			\CrmCheckPath('PATH_TO_'.mb_strtoupper($entityTypeName).'_DETAILS', '', '')
		);
	}

	/**
	 * Return parent fields description for entity $entityTypeId in the same format as Factory::getFieldsInfo()
	 *
	 * @param int $entityTypeId
	 * @return array
	 */
	public function getParentFieldsInfo(int $entityTypeId): array
	{
		$fields = [];

		$relationManager = Container::getInstance()->getRelationManager();
		$parentRelations = $relationManager->getParentRelations($entityTypeId);
		foreach ($parentRelations as $relation)
		{
			if ($relation->isPredefined())
			{
				continue;
			}
			$parentEntityTypeId = $relation->getParentEntityTypeId();
			$fieldName = static::getParentFieldName($parentEntityTypeId);
			$fields[$fieldName] = [
				'TYPE' => Field::TYPE_CRM_ENTITY,
				'SETTINGS' => [
					'parentEntityTypeId' => $parentEntityTypeId,
				],
				'TITLE' => \CCrmOwnerType::GetDescription($parentEntityTypeId),
			];
		}

		return $fields;
	}

	/**
	 * Save relations of the $item passed in $data.
	 *
	 * @param Item $item
	 * @param array $data
	 * @return Result
	 */
	public function saveItemRelations(Item $item, array $data, array $additionalParents = []): Result
	{
		$result = new Result();

		$child = ItemIdentifier::createByItem($item);
		$relationManager = Container::getInstance()->getRelationManager();

		$oldParentElements = $this->sortIdentifiersByEntityTypeId($relationManager->getParentElements($child));
		$newParentElements = $this->sortIdentifiersByEntityTypeId(array_merge(
			$this->getParentIdentifiersFromData($data),
			$additionalParents
		));

		// delete removed relations
		foreach ($data as $name => $value)
		{
			if (empty($value) && static::isParentFieldName($name))
			{
				/** @var string $entityTypeId */
				$entityTypeId = static::getEntityTypeIdFromFieldName($name);
				if (isset($oldParentElements[$entityTypeId]))
				{
					$unBindResult = $relationManager->unbindItems($oldParentElements[$entityTypeId], $child);
					if (!$unBindResult->isSuccess())
					{
						$result->addErrors($unBindResult->getErrors());
					}
				}
			}
		}

		// bind new items and change existing (not deleted) relations
		foreach (array_diff($newParentElements, $oldParentElements) as $bindItem)
		{
			$oldParent = ($oldParentElements[$bindItem->getEntityTypeId()] ?? null);
			if ($oldParent && $bindItem->getEntityId() !== $oldParent->getEntityId())
			{
				$unBindResult = $relationManager->unbindItems($oldParentElements[$bindItem->getEntityTypeId()], $child);
				if (!$unBindResult->isSuccess())
				{
					$result->addErrors($unBindResult->getErrors());
				}
			}
			$bindResult = $relationManager->bindItems($bindItem, $child);
			if (!$bindResult->isSuccess())
			{
				$result->addErrors($bindResult->getErrors());
			}
		}

		return $result;
	}

	/**
	 * @param ItemIdentifier[] $parentElements
	 * @return ItemIdentifier[]
	 */
	protected function sortIdentifiersByEntityTypeId(array $parentElements): array
	{
		$results = [];
		foreach ($parentElements as $element)
		{
			$results[$element->getEntityTypeId()] = $element;
		}

		return $results;
	}

	protected function getParentIdentifiersFromData(array $data): array
	{
		$newParentElements = [];
		foreach($data as $name => $value)
		{
			if (($value > 0) && (mb_strpos($name, self::FIELD_PARENT_PREFIX) === 0))
			{
				$parentEntityTypeId = (int)str_replace(self::FIELD_PARENT_PREFIX . '_', '', $name);
				$parentEntityId = (int)$value;
				if (\CCrmOwnerType::IsDefined($parentEntityTypeId) && $parentEntityId > 0)
				{
					$newParentElements[] = new ItemIdentifier(
						$parentEntityTypeId,
						$parentEntityId,
					);
				}
			}
		}

		return $newParentElements;
	}
}
