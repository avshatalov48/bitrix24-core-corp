<?php

namespace Bitrix\CrmMobile\Kanban;

use Bitrix\Crm\Component\EntityList\GridId;
use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Kanban\EntityActivityCounter;
use Bitrix\Crm\Security\Manager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\Crm;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Result;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\CrmMobile\Kanban\Dto\Field;
use Bitrix\CrmMobile\Kanban\Dto\Item;
use Bitrix\CrmMobile\Kanban\Dto\ItemData;
use Bitrix\CrmMobile\Kanban\Entity\EntityNotFoundException;
use CCrmOwnerType;

/**
 * Class Entity
 *
 * @package Bitrix\CrmMobile\Kanban
 */
abstract class Entity
{
	protected $params = [];
	protected $pageNavigation = null;

	protected const CRM_STATUS_FIELD_TYPE = 'crm_status';
	protected const CRM_FIELD_TYPE = 'crm';
	protected const IBLOCK_ELEMENT_FIELD_TYPE = 'iblock_element';
	protected const IBLOCK_SECTION_FIELD_TYPE = 'iblock_section';
	protected const TMP_FILTER_PRESET_ID = 'tmp_filter';

	protected const EXCLUDED_FIELDS = [
		'TITLE',
		'OPPORTUNITY',
		'DATE_CREATE',
	];

	protected const DEFAULT_COUNT_WITH_RECKON_ACTIVITY = 1;

	/**
	 * @param string $entityType
	 * @return Entity
	 * @throws EntityNotFoundException
	 */
	public static function getInstance(string $entityType): Entity
	{
		if ($entityType === \CCrmOwnerType::LeadName)
		{
			return ServiceLocator::getInstance()->get('crmmobile.kanban.entity.lead');
		}

		if ($entityType === \CCrmOwnerType::DealName)
		{
			return ServiceLocator::getInstance()->get('crmmobile.kanban.entity.deal');
		}

		if ($entityType === \CCrmOwnerType::QuoteName)
		{
			return ServiceLocator::getInstance()->get('crmmobile.kanban.entity.quote');
		}

		if ($entityType === \CCrmOwnerType::ContactName)
		{
			return ServiceLocator::getInstance()->get('crmmobile.kanban.entity.contact');
		}

		if ($entityType === \CCrmOwnerType::CompanyName)
		{
			return ServiceLocator::getInstance()->get('crmmobile.kanban.entity.company');
		}

		if ($entityType === \CCrmOwnerType::SmartInvoiceName)
		{
			return ServiceLocator::getInstance()->get('crmmobile.kanban.entity.smartInvoice');
		}

		$entityTypeId = \CCrmOwnerType::ResolveID($entityType);

		if (\CCrmOwnerType::isDynamicTypeBasedStaticEntity($entityTypeId))
		{
			$instance = ServiceLocator::getInstance()->get('crmmobile.kanban.entity.dynamicTypeBasedStatic');
			$instance->setEntityType($entityType);
			return $instance;
		}

		if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			$instance = ServiceLocator::getInstance()->get('crmmobile.kanban.entity.dynamic');
			$instance->setEntityType($entityType);
			return $instance;
		}

		throw new EntityNotFoundException('EntityType: ' . $entityType . ' unknown');
	}

	/**
	 * @return int
	 */
	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::ResolveID($this->getEntityType());
	}

	/**
	 * @return string
	 */
	abstract public function getEntityType(): string;

	/**
	 * @return bool
	 */
	public function isUseColumns(): bool
	{
		return false;
	}

	/**
	 * @param array $params
	 * @return $this
	 */
	public function prepare(array $params): Entity
	{
		$this->params = $params;
		return $this;
	}

	/**
	 * @return array
	 */
	abstract public function getList(): array;

	/**
	 * @return array
	 */
	public function getColumns(): array
	{
		return [];
	}

	/**
	 * @return string
	 */
	protected function getGridId(): string
	{
		return (new GridId($this->getEntityTypeId()))->getValue();
	}

	/**
	 * @param array $presets
	 * @param string|null $defaultPresetName
	 * @return array
	 */
	protected function prepareFilterPresets(array $presets, ?string $defaultPresetName): array
	{
		$results = [];

		foreach ($presets as $id => $preset)
		{
			$name = html_entity_decode($preset['name'] ?? '', ENT_QUOTES);

			if ($id === null || $id === 'default_filter' || $id === 'tmp_filter')
			{
				continue;
			}

			$default = ($id === $defaultPresetName);

			$results[] = compact('id', 'name', 'default');
		}

		return $results;
	}

	/**
	 * @param int $id
	 * @param int $stageId
	 * @return Result
	 */
	abstract public function updateItemStage(int $id, int $stageId): Result;

	/**
	 * @param int $id
	 * @param array $params
	 * @return Result
	 */
	abstract public function deleteItem(int $id, array $params = []): Result;

	/**
	 * @param PageNavigation $pageNavigation
	 * @return $this
	 */
	public function setPageNavigation(PageNavigation $pageNavigation): Entity
	{
		$this->pageNavigation = $pageNavigation;
		return $this;
	}

	/**
	 * @return array
	 */
	protected function getFilterParams(): array
	{
		return ($this->params['filterParams'] ?? []);
	}

	protected function getEntityAttributes(array $items, string $columnIdName = 'id'): ?array
	{
		if (empty($items))
		{
			return null;
		}

		$ids = array_column($items, $columnIdName);
		$entityTypeName = $this->getEntityType();

		return Manager::resolveController($entityTypeName)->getPermissionAttributes($entityTypeName, $ids);
	}

	/**
	 * @param array $data
	 * @return Item
	 */
	protected function buildItemDto(array $data): Item
	{
		$item = new Item([
			'id' => $data['id'],
		]);
		$item->data = new ItemData($data['data']);
		return $item;
	}

	/**
	 * @param array $item
	 * @param array $params
	 * @return array
	 */
	protected function prepareItem(array $item, array $params = []): array
	{
		$id = $this->getItemId($item);
		$entityAttributes = ($params['permissionEntityAttributes'] ?? null);

		return [
			'id' => $id,
			'data' => [
				'id' => $id,
				'columnId' => $this->getColumnId($item),
				'name' => $this->getItemName($item),
				'date' => $this->getItemDate($item),
				'dateFormatted' => $this->getItemDateFormatted($item),
				'price' => $this->getItemPrice($item),
				'fields' => $this->prepareFields($item, $params),
				'badges' => $this->prepareBadges($item, $params),
				'return' => $this->getItemReturn($item),
				'returnApproach' => $this->getItemReturnApproach($item),
				'subTitleText' => $this->getSubTitleText($item),
				'descriptionRow' => $this->getDescriptionRow($item),
				'money' => $this->getMoney($item),
				'client' => $this->getClient($item, $params),
				'permissions' => $this->getPermissions($id, $entityAttributes),
				'counters' => $this->getItemCounters($item, $params),
			],
		];
	}

	/**
	 * @param array $item
	 * @return int
	 */
	protected function getItemId(array $item): int
	{
		return $item['ID'];
	}

	/**
	 * In the future, we can add the necessary permission checks.
	 * Now need to check permissions to edit an element in kanban
	 *
	 * @param int $id
	 * @param array|null $entityAttributes
	 * @return array
	 */
	protected function getPermissions(int $id, ?array $entityAttributes): array
	{
		$entityTypeName = $this->getPermissionEntityTypeName();

		$params = [
			$entityTypeName,
			$id,
			null,
			$entityAttributes,
		];

		return [
			'write' => \CCrmAuthorizationHelper::CheckUpdatePermission(...$params),
			'delete' => \CCrmAuthorizationHelper::CheckDeletePermission(...$params),
		];
	}

	protected function getPermissionEntityTypeName(): string
	{
		return $this->getEntityType();
	}

	protected function getItemCounters(array $item, array $params = []): array
	{
		$counters = [];

		$activityCounterTotal = ($item['activityCounterTotal'] ?? 0);
		$isCurrentUserAssigned = ((int)$this->params['userId'] === $this->getAssignedById($item));

		if (!$isCurrentUserAssigned)
		{
			$counters[] = ItemCounter::getInstance()->getEmptyCounter($activityCounterTotal);
		}
		else
		{
			$isReckonActivityLessItems = $this->params['isReckonActivityLessItems'];
			$activityErrorTotal = (int)($item['activityErrorTotal'] ?? 0);
			$activityIncomingTotal = (int)($item['activityIncomingTotal'] ?? 0);

			if ($activityErrorTotal)
			{
				$counters[] = ItemCounter::getInstance()->getErrorCounter($activityErrorTotal);
			}

			if ($activityIncomingTotal)
			{
				$counters[] = ItemCounter::getInstance()->getIncomingCounter($activityIncomingTotal);
			}

			if (empty($counters))
			{
				if ($isReckonActivityLessItems)
				{
					$counters[] = ItemCounter::getInstance()->getErrorCounter(self::DEFAULT_COUNT_WITH_RECKON_ACTIVITY);
				}
				else
				{
					$counters[] = ItemCounter::getInstance()->getEmptyCounter(0);
				}
			}
		}

		$indicator = null;
		if (!$activityCounterTotal && !empty($item['activityProgress']))
		{
			$userId = (int)$this->params['userId'];

			$activityProgressForCurrentUser = 0;
			if (isset($item['activitiesByUser'][$userId]))
			{
				$activityProgressForCurrentUser = ($item['activitiesByUser'][$userId]['activityProgress'] ?? 0);
			}

			$indicatorInstance = ItemIndicator::getInstance();
			$indicator = (
				$activityProgressForCurrentUser
					? $indicatorInstance->getOwnIndicator()
					: $indicatorInstance->getSomeoneIndicator()
			);
		}

		$renderLastActivityTime = ($params['renderLastActivityTime'] ?? false);

		return [
			'counters' => $counters,
			'activityCounterTotal' => $activityCounterTotal,
			'lastActivity' => $this->getLastActivityTimestamp($item),
			'indicator' => $indicator,
			'skipTimeRender' => !$renderLastActivityTime,
		];
	}

	protected function getAssignedById(array $item): ?int
	{
		return null;
	}

	/**
	 * @param array $item
	 * @return string|null
	 */
	protected function getColumnId(array $item): ?string
	{
		return null;
	}

	protected function getLastActivityTimestamp(array $item): ?int
	{
		return null;
	}

	/**
	 * @param array $item
	 * @return bool
	 */
	protected function getItemReturn(array $item): bool
	{
		return false;
	}

	/**
	 * @param array $item
	 * @return bool
	 */
	protected function getItemReturnApproach(array $item): bool
	{
		return false;
	}

	/**
	 * @param array $item
	 * @return float|null
	 */
	protected function getItemPrice(array $item): ?float
	{
		return null;
	}

	/**
	 * @param array $item
	 * @return array|null
	 */
	protected function getMoney(array $item): ?array
	{
		return null;
	}

	/**
	 * @param array $item
	 * @return string
	 */
	protected function getSubTitleText(array $item): string
	{
		return '';
	}

	/**
	 * @param array $item
	 * @return array
	 */
	protected function getDescriptionRow(array $item): array
	{
		return [];
	}

	/**
	 * @param array $item
	 * @param array $params
	 * @return array|null
	 */
	protected function getClient(array $item, array $params = []): ?array
	{
		return ($item['client'] ?? null);
	}

	/**
	 * @param array $item
	 * @return string
	 */
	protected function getItemName(array $item): string
	{
		return '';
	}

	/**
	 * @param array $item
	 * @return int
	 */
	protected function getItemDate(array $item): int
	{
		return $item['CREATED_TIME']->getTimestamp();
	}

	/**
	 * @todo need to format date as date in desktop kanban object
	 *
	 * @param array $item
	 * @return string
	 */
	protected function getItemDateFormatted(array $item): string
	{
		return '';
	}

	/**
	 * @param array $item
	 * @param array $params
	 * @return mixed
	 */
	abstract protected function prepareFields(array $item = [], array $params = []): array;
	abstract protected function prepareBadges(array $item = [], array $params = []): array;

	/**
	 * @param Field $field
	 */
	protected function prepareField(Field $field): void
	{
		$field->params['readOnly'] = true;

		if (in_array(
			$field->type,
			[
				self::CRM_FIELD_TYPE,
				self::CRM_STATUS_FIELD_TYPE,
				self::IBLOCK_ELEMENT_FIELD_TYPE,
				self::IBLOCK_SECTION_FIELD_TYPE,
			]
		))
		{
			$field->params['styleName'] = 'field';
		}
	}

	/**
	 * @param string $fieldName
	 * @return bool
	 */
	protected function isExcludedField(string $fieldName): bool
	{
		return in_array($fieldName, static::EXCLUDED_FIELDS, true);
	}

	protected function hasVisibleField(array $item, string $fieldName): bool
	{
		foreach ($item['fields'] as $field)
		{
			if ($field['code'] === $fieldName)
			{
				return true;
			}
		}

		return false;
	}

	public function getSearchPresetsAndCounters(int $userId, ?int $currentCategoryId = null): array
	{
		$presets = $this->getSearchPresets($currentCategoryId);
		$counters = $this->getCounters($userId, $currentCategoryId);

		return [
			'presets' => $presets,
			'counters' => $counters,
		];
	}

	private function getSearchPresets(int $currentCategoryId = 0): array
	{
		$entity = \Bitrix\Crm\Kanban\Entity::getInstance($this->getEntityType());
		$entity->setCategoryId($currentCategoryId);

		$filterOptions = new \Bitrix\Main\UI\Filter\Options(
			$this->getGridId(),
			$entity->getFilterPresets()
		);

		return $this->prepareFilterPresets(
			$filterOptions->getPresets(),
			$filterOptions->getDefaultFilterId()
		);
	}

	/**
	 * @param int|null $categoryId
	 * @return string
	 */
	public function getDesktopLink(?int $categoryId): string
	{
		$router = Container::getInstance()->getRouter();

		$url = $router->getItemListUrlInCurrentView($this->getEntityTypeId(), $categoryId);
		if ($url)
		{
			return $url->getLocator();
		}

		return $router->getRoot();
	}

	public function getEntityLink(): ?string
	{
		$someMagicNumberToPassIntTypeCheck = 666;

		$url = Container::getInstance()->getRouter()->getItemDetailUrl(
			$this->getEntityTypeId(),
			$someMagicNumberToPassIntTypeCheck
		);
		if ($url)
		{
			return str_replace($someMagicNumberToPassIntTypeCheck, '#ENTITY_ID#', $url->getLocator());
		}

		return null;
	}

	/**
	 * @param int $userId
	 * @param int|null $categoryId
	 * @return array
	 */
	public function getCounters(int $userId, ?int $categoryId): array
	{
		if (
			method_exists(\Bitrix\Crm\Settings\CounterSettings::class, 'getInstance')
			&& !\Bitrix\Crm\Settings\CounterSettings::getInstance()->isEnabled()
		)
		{
			return [];
		}

		$entityTypeId = $this->getEntityTypeId();

		$factory = Container::getInstance()->getFactory($entityTypeId);

		$data = [];
		if (!$factory || !$factory->isCountersEnabled())
		{
			return $data;
		}

		$extra = [];
		if ($categoryId !== null && $factory->isCategoriesEnabled())
		{
			$extra['CATEGORY_ID'] = $categoryId;
		}

		$this->fillCountersData($data, $userId, $extra);

		// @todo remove after creating view mode Activity in the mobile
		$data[] = [
			'typeId' => '999',
			'typeName' => 'MY_PENDING',
			'code' => 'my_pending',
			'value' => 0,
			'excludeUsers' => false,
		];

		if ($this->canUseOtherCounters($categoryId))
		{
			$extra['EXCLUDE_USERS'] = true;
			$this->fillCountersData($data, $userId, $extra);
		}

		return $data;
	}

	protected function fillCountersData(array &$data, int $userId, array $extra): void
	{
		$entityTypeId = $this->getEntityTypeId();

		$allSupportedTypes = EntityCounterType::getAllSupported($entityTypeId, true);
		foreach($allSupportedTypes as $typeId)
		{
			if(EntityCounterType::isGroupingForArray($typeId, $allSupportedTypes))
			{
				continue;
			}

			$counter = EntityCounterFactory::create($entityTypeId, $typeId, $userId, $extra);
			$code = $counter->getCode();
			$value = $counter->getValue(false);

			$data[] = [
				'typeId' => $typeId,
				'typeName' => EntityCounterType::resolveName($typeId),
				'code' => $code,
				'value' => $value,
				'excludeUsers' => ($extra['EXCLUDE_USERS'] ?? false),
			];
		}
	}

	// @todo fix code duplicate
	protected function canUseOtherCounters(?int $categoryId): bool
	{
		$entityTypeId = $this->getEntityTypeId();

		$uPermissions = Container::getInstance()->getUserPermissions();
		$permissionEntityType = $uPermissions::getPermissionEntityType($entityTypeId, (int) $categoryId);

		if ($uPermissions->isAdmin())
		{
			return true;
		}

		$permissions = $uPermissions->getCrmPermissions()->GetPermType($permissionEntityType);
		return $permissions >= $uPermissions::PERMISSION_ALL;
	}

	protected function setFilterPreset(string $presetId, Options $filterOptions): void
	{
		if ($presetId === 'default_filter')
		{
			$presetId = 'tmp_filter';
		}

		$presets = $filterOptions->getPresets();

		if ($presetId !== self::TMP_FILTER_PRESET_ID && !empty($presets[$presetId]))
		{
			$preset = $presets[$presetId];

			$data = [
				'fields' => $preset['fields'] ?? [],
				'preset_id' => $presetId,
				'rows' => (empty($preset['fields']) || !is_array($preset['fields'])) ? [] : array_keys($preset['fields']),
				'name' => $preset['name'],
			];
		}
		elseif ($presetId === self::TMP_FILTER_PRESET_ID)
		{
			$tmpFilter = ($this->params['filter']['tmpFields'] ?? []);
			$fields = [];
			foreach ($tmpFilter as $fieldName => $field)
			{
				$fields[$fieldName] = $field;
			}

			$data = [
				'fields' => $fields,
				'preset_id' => self::TMP_FILTER_PRESET_ID,
				'rows' => array_keys($fields),
			];
		}
		else
		{
			return;
		}

		$filterOptions->setFilterSettings($presetId, $data);
		$filterOptions->save();
	}

	protected function prepareActivityCounters(array &$items): void
	{
		if (empty($items))
		{
			return;
		}

		//@todo check activity counters supporting
		$errors = [];
		$entityActivityCounter = new EntityActivityCounter(
			$this->getEntityTypeId(),
			array_keys($items),
			$errors,
		);
		$entityActivityCounter->appendToEntityItems($items);
	}
}
