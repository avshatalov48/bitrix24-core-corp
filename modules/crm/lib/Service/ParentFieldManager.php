<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Field;
use Bitrix\Crm\Integration\Main\UISelector;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\Relation\EntityRelationTable;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Request;
use Bitrix\Main\Result;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Web\Uri;

class ParentFieldManager
{
	public const FIELD_PARENT_PREFIX = 'PARENT_ID';
	public const URL_PARAM_PARENT_TYPE_ID = 'parentTypeId';
	public const URL_PARAM_PARENT_ID = 'parentId';

	protected $parents = [];

	/**
	 * Return an array of items of a parents entities with binding to the elements of the current entity.
	 * Load parent fields values from database.
	 * Return array of the structure:
	 * 	[childEntityTypeId] =>
	 *		[parentEntityTypeId] =>
	 *			[parentEntityId] => [
	 * 				'id' => parentEntityId,
	 *				'code' => PARENT_ID_{parentEntityTypeId},
	 *				'entityDescription' => parentEntityDescription (Lead / Deal etc.),
	 *				'title' => parentEntityTitle,
	 *				'url' => parentEntityDetailUrl,
	 *				'value' => '<a href="' . url . '">' . title . '</a>',
	 * 			]
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
	 * Method return data in the same format as getParentFields.
	 * This method does not load parent field values from database, only description of parent items.
	 *
	 * @param int $childEntityTypeId - identifier of the $items.
	 * @param array $items - flat array where each element - item's data.
	 * @return array
	 */
	public function loadParentElementsByChildren(int $childEntityTypeId, array $items): array
	{
		$parentRelationElements = [];

		foreach ($items as $item)
		{
			foreach ($item as $fieldName => $value)
			{
				if (static::isParentFieldName($fieldName))
				{
					$parentEntityTypeId = static::getEntityTypeIdFromFieldName($fieldName);
					$parentRelationElements[] = [
						'SRC_ENTITY_TYPE_ID' => $parentEntityTypeId,
						'SRC_ENTITY_ID' => (int)$value,
						'DST_ENTITY_ID' => $item['ID'],
						'DST_ENTITY_TYPE_ID' => $childEntityTypeId,
					];
				}
			}
		}

		if (empty($parentRelationElements))
		{
			return [];
		}

		$elementRelationsIds = $this->getElementRelationIds($parentRelationElements);
		$this->loadParentElements($elementRelationsIds);
		$this->prepareParentElements($parentRelationElements);

		return $this->parents;
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
			if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($parentElementTypeId))
			{
				$factory = Container::getInstance()->getFactory($parentElementTypeId);
				if ($factory)
				{
					$parentElements = $factory->getItemsFilteredByPermissions([
						'select' => [
							Item::FIELD_NAME_TITLE,
						],
						'filter' => [
							'@' . Item::FIELD_NAME_ID => $parentElementIds,
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
						$title = HtmlFilter::encode($parent->getHeading());
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
	 * Return parent fields description with info about how database should obtain their values.
	 * @see \CCrmLead::GetFields() and similar methods.
	 *
	 * @param int $entityTypeId
	 * @param string $entitySqlTableAlias
	 * @return array
	 */
	public function getParentFieldsSqlInfo(int $entityTypeId, string $entitySqlTableAlias): array
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
			$tableAlias = 'ERT_' . $fieldName;
			$fields[$fieldName] = [
				'FIELD' => "{$tableAlias}.SRC_ENTITY_ID",
				'TYPE' => 'int',
				'FROM' => 'LEFT JOIN ' . EntityRelationTable::getTableName() . " {$tableAlias} ON"
					. " {$tableAlias}.SRC_ENTITY_TYPE_ID = " . $parentEntityTypeId
					. " AND {$tableAlias}.DST_ENTITY_TYPE_ID = " . $entityTypeId
					. " AND {$tableAlias}.DST_ENTITY_ID = " . $entitySqlTableAlias . ".ID"
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
	public function saveItemRelations(Item $item, array $data): Result
	{
		$childIdentifier = ItemIdentifier::createByItem($item);

		return $this->saveParentRelationsForIdentifier($childIdentifier, $data);
	}

	/**
	 * Save parent relations passed in $data for identifier $childIdentifier.
	 *
	 * @param ItemIdentifier $childIdentifier
	 * @param array $data
	 * @return Result
	 */
	public function saveParentRelationsForIdentifier(ItemIdentifier $childIdentifier, array $data): Result
	{
		$result = new Result();

		$relationManager = Container::getInstance()->getRelationManager();

		$oldParentElements = $this->sortIdentifiersByEntityTypeId($relationManager->getParentElements($childIdentifier));
		$newParentElements = $this->sortIdentifiersByEntityTypeId($this->getParentIdentifiersFromData($data));

		// delete removed relations
		foreach ($data as $name => $value)
		{
			if (empty($value) && static::isParentFieldName($name))
			{
				/** @var string $entityTypeId */
				$entityTypeId = static::getEntityTypeIdFromFieldName($name);
				if (isset($oldParentElements[$entityTypeId]))
				{
					$unBindResult = $relationManager->unbindItems($oldParentElements[$entityTypeId], $childIdentifier);
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
				$unBindResult = $relationManager->unbindItems($oldParentElements[$bindItem->getEntityTypeId()], $childIdentifier);
				if (!$unBindResult->isSuccess())
				{
					$result->addErrors($unBindResult->getErrors());
				}
			}
			$bindResult = $relationManager->bindItems($bindItem, $childIdentifier);
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
			if (($value > 0) && (mb_strpos($name, self::FIELD_PARENT_PREFIX . '_') === 0))
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

	/**
	 * If $componentParams has data about parent item, then checks permission to it.
	 * If user has permissions then into $componentParameters adds info about inner filter by this item.
	 *
	 * Return true if $componentParams has been modified successfully.
	 *
	 * @param int $entityTypeId
	 * @param array $componentParams
	 * @return bool
	 */
	public function tryPrepareListComponentParametersWithParentItem(int $entityTypeId, array &$componentParams): bool
	{
		if (
			!isset(
				$componentParams['PARENT_ENTITY_TYPE_ID'],
				$componentParams['PARENT_ENTITY_ID']
			)
		)
		{
			return false;
		}

		$parentEntityTypeId = (int)$componentParams['PARENT_ENTITY_TYPE_ID'];
		$parentEntityId = (int)$componentParams['PARENT_ENTITY_ID'];
		if ($parentEntityTypeId && $parentEntityId)
		{
			$isPermitted = Container::getInstance()->getUserPermissions()->checkReadPermissions(
				$parentEntityTypeId,
				$parentEntityId
			);
			if (!$isPermitted)
			{
				return false;
			}

			$relation = Container::getInstance()->getRelationManager()->getRelation(new RelationIdentifier(
				$parentEntityTypeId,
				$entityTypeId
			));
			if (!$relation || $relation->isPredefined())
			{
				return false;
			}

			$filter = [
				static::getParentFieldName($parentEntityTypeId) => $parentEntityId,
			];
			if (!isset($componentParams['INTERNAL_FILTER']) || !is_array($componentParams['INTERNAL_FILTER']))
			{
				$componentParams['INTERNAL_FILTER'] = [];
			}
			$componentParams['INTERNAL_FILTER'] = array_merge($componentParams['INTERNAL_FILTER'], $filter);

			return true;
		}

		return false;
	}

	/**
	 * Adds information about parent fields into $fields in filter data provider format.
	 *
	 * @param int $entityTypeId
	 * @return array
	 */
	public function getParentFieldsOptionsForFilterProvider(int $entityTypeId): array
	{
		$fields = [];

		$relationManager = Container::getInstance()->getRelationManager();
		$parentRelations = $relationManager->getParentRelations($entityTypeId);
		foreach ($parentRelations as $relation)
		{
			if (!$relation->isPredefined())
			{
				$parentEntityTypeId = $relation->getParentEntityTypeId();

				$fields[static::getParentFieldName($parentEntityTypeId)] = [
					'type' => 'dest_selector',
					'default' => false,
					'partial' => true,
				];
			}
		}

		return $fields;
	}

	/**
	 * Return field description with code $fieldID for $entityTypeId in filter data provider format.
	 *
	 * @param int $entityTypeId
	 * @param string $fieldId
	 * @return array[]
	 */
	public function prepareParentFieldDataForFilterProvider(int $entityTypeId, string $fieldId): array
	{
		$result = [
			'params' => [
				'apiVersion' => 3,
				'context' => EntitySelector::CONTEXT,
				'multiple' => 'N',
				'contextCode' => 'CRM',
				'useClientDatabase' => 'N',
				'enableAll' => 'N',
				'enableUsers' => 'N',
				'enableSonetgroups' => 'N',
				'enableDepartments' => 'N',
				'allowEmailInvitation' => 'N',
				'allowSearchEmailUsers' => 'N',
				'departmentSelectDisable' => 'Y',
				'isNumeric' => 'Y',
				'enableCrm' => 'Y',
			]
		];

		$parentId = static::getEntityTypeIdFromFieldName($fieldId);

		if (\CCrmOwnerType::isPossibleDynamicTypeId($parentId))
		{
			$key = UISelector\Handler::ENTITY_TYPE_CRMDYNAMICS . '_'. $parentId;
			$crmDynamicTitles = [
				$key => HtmlFilter::encode(\CCrmOwnerType::GetDescription($parentId)),
			];
		}

		$result['params'] = array_merge_recursive(
			$result['params'],
			ElementType::getEnableEntityTypesForSelectorOptions(
				[\CCrmOwnerType::ResolveName($parentId)],
				$crmDynamicTitles ?? []
			)
		);

		return $result;
	}

	/**
	 * Adds grid headers description with parent fields of entity with $entityTypeId to $headers.
	 *
	 * @param int $entityTypeId
	 * @param array $headers
	 */
	public function prepareGridHeaders(int $entityTypeId, array &$headers): void
	{
		$relationManager = Container::getInstance()->getRelationManager();
		$parentRelations = $relationManager->getParentRelations($entityTypeId);
		foreach ($parentRelations as $relation)
		{
			if (!$relation->isPredefined())
			{
				$parentEntityTypeId = $relation->getParentEntityTypeId();
				$headers[] = [
					'id' => static::getParentFieldName($parentEntityTypeId),
					'name' => \CCrmOwnerType::GetDescription($parentEntityTypeId),
					'sort' => false,
					'default' => false,
					'editable' => false
				];
			}
		}
	}

	/**
	 * Add references to EntityRelationTable for all custom relations with $entity.
	 *
	 * @param \Bitrix\Main\ORM\Entity $entity
	 * @param int $entityTypeId
	 */
	public function addParentFieldsReferences(\Bitrix\Main\ORM\Entity $entity, int $entityTypeId): void
	{
		$relations = Container::getInstance()->getRelationManager()->getParentRelations($entityTypeId);
		foreach ($relations as $relation)
		{
			if ($relation->isPredefined())
			{
				continue;
			}
			$referenceName = 'PARENT_REFERENCE_' . $relation->getParentEntityTypeId();
			if ($entity->hasField($referenceName))
			{
				continue;
			}
			$entity->addField(new Reference(
				$referenceName,
				EntityRelationTable::class,
				Join::on('this.ID', 'ref.DST_ENTITY_ID')
					->where('ref.DST_ENTITY_TYPE_ID', $entityTypeId)
					->where('ref.SRC_ENTITY_TYPE_ID', $relation->getParentEntityTypeId())
			));

			$entity->addField(new ExpressionField(
				static::getParentFieldName($relation->getParentEntityTypeId()),
				'%s',
				$referenceName . '.SRC_ENTITY_ID'
			))->configureValueType(IntegerField::class);
		}
	}

	/**
	 * Extract ItemIdentifier from filter $value.
	 * If $entityTypeId is not passed - it will be got from encoded string $value.
	 *
	 * @param $value
	 * @param int|null $entityTypeId
	 * @return ItemIdentifier|null
	 */
	public static function getItemIdentifierFromFilterValue($value, int $entityTypeId = null): ?ItemIdentifier
	{
		$entityId = 0;
		if (\CCrmOwnerType::IsDefined($entityTypeId))
		{
			if (is_int($value) || is_numeric($value))
			{
				$entityId = (int)$value;
			}
			elseif (is_string($value))
			{
				$prefix = static::getParentFieldFilterValuePrefix($entityTypeId);
				$entityId = (int)str_replace($prefix, '', $value);
			}
			if ($entityId > 0)
			{
				return new ItemIdentifier($entityTypeId, $entityId);
			}

			return null;
		}

		if (!is_string($value))
		{
			return null;
		}

		if (preg_match('#^CRM([A-Z]+)-?(\d+)_?(\d+)?$#', $value, $matches))
		{
			if ($matches[1] === 'DYNAMIC' && count($matches) === 4)
			{
				$entityTypeId = (int)$matches[2];
				$entityId = (int)$matches[3];
			}
			else
			{
				$entityTypeId = \CCrmOwnerType::ResolveID($matches[1]);
				$entityId = (int)($matches[2] ?? 0);
			}
			if (\CCrmOwnerType::IsDefined($entityTypeId) && $entityId > 0)
			{
				return new ItemIdentifier($entityTypeId, $entityId);
			}
		}

		return null;
	}

	/**
	 * Return prefix for value to encode value in filter.
	 *
	 * @param int $entityTypeId
	 * @return string
	 */
	public static function getParentFieldFilterValuePrefix(int $entityTypeId): string
	{
		if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			return UISelector\CrmDynamics::PREFIX_FULL . $entityTypeId . '_';
		}

		return 'CRM' . \CCrmOwnerType::ResolveName($entityTypeId);
	}

	/**
	 * Transforms string value (like "CRMLEAD_1") into entity identifier if possible.
	 * If value is not correct or empty returns null.
	 *
	 * @param string $fieldName
	 * @param $value
	 * @return int|null
	 */
	public static function transformEncodedFilterValueIntoInteger(string $fieldName, $value): ?int
	{
		$parentEntityTypeId = static::getEntityTypeIdFromFieldName($fieldName);
		if (!\CCrmOwnerType::IsDefined($parentEntityTypeId))
		{
			return null;
		}
		$identifier = static::getItemIdentifierFromFilterValue($value, $parentEntityTypeId);
		if ($identifier)
		{
			return $identifier->getEntityId();
		}

		return null;
	}

	/**
	 * Parses data about parent item from request.
	 * If item is found - return identifier.
	 *
	 * @param Request|null $request
	 * @return ItemIdentifier|null
	 */
	public static function tryParseParentItemFromRequest(?Request $request = null): ?ItemIdentifier
	{
		if (!$request)
		{
			$request = Application::getInstance()->getContext()->getRequest();
		}

		$parentEntityTypeId = (int)$request->get(static::URL_PARAM_PARENT_TYPE_ID);
		$parentEntityId = (int)$request->get(static::URL_PARAM_PARENT_ID);
		if (\CCrmOwnerType::IsDefined($parentEntityTypeId) && $parentEntityId > 0)
		{
			return new ItemIdentifier($parentEntityTypeId, $parentEntityId);
		}

		return null;
	}

	/**
	 * Add data about parent item to url.
	 *
	 * @param int $childEntityTypeId
	 * @param ItemIdentifier $parentIdentifier
	 * @param Uri $url
	 */
	public static function addParentItemToUrl(int $childEntityTypeId, ItemIdentifier $parentIdentifier, Uri $url): void
	{
		$availableAsCustomParents =
			Container::getInstance()->getRelationManager()->getAvailableForParentBindingEntityTypes($childEntityTypeId)
		;
		if (isset($availableAsCustomParents[$parentIdentifier->getEntityTypeId()]))
		{
			$url->addParams([
				static::URL_PARAM_PARENT_TYPE_ID => $parentIdentifier->getEntityTypeId(),
				static::URL_PARAM_PARENT_ID => $parentIdentifier->getEntityId(),
			]);
		}
		else
		{
			$fieldName = mb_strtolower(\CCrmOwnerType::ResolveName($parentIdentifier->getEntityTypeId())) . '_id';
			$url->addParams([
				$fieldName => $parentIdentifier->getEntityId(),
			]);
		}
	}
}
