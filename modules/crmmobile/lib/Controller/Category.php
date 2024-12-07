<?php

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Bizproc\Automation\Helper;
use Bitrix\Bizproc\Workflow\Template\SourceType;
use Bitrix\Bizproc\Workflow\Type\GlobalConst;
use Bitrix\Bizproc\Workflow\Type\GlobalVar;
use Bitrix\Crm;
use Bitrix\Crm\Automation\TunnelManager;
use Bitrix\Crm\Color\PhaseColorScheme;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Counter;
use Bitrix\Crm\EO_Status_Collection;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Security\Role\RolePermission;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\CrmMobile\Dto;
use Bitrix\CrmMobile\Kanban\Entity;
use Bitrix\CrmMobile\Kanban\GridId;
use Bitrix\CrmMobile\Kanban\Kanban;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\CrmMobile\Controller\Base;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;

Loader::requireModule('crm');

class Category extends Base
{
	private array $usedDocumentFields = [];

	public function configureActions(): array
	{
		return [
			'getList' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'get' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getCategoryAccess' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getCounters' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	/**
	 * @param Factory $factory
	 * @param int $categoryId
	 * @return array
	 */
	public function setAction(Factory $factory, int $categoryId): array
	{
		$userPermissions = Container::getInstance()->getUserPermissions();

		$entityTypeId = $factory->getEntityTypeId();

		$canRead = false;
		if ($categoryId >= 0)
		{
			$canRead = $userPermissions->checkReadPermissions($entityTypeId, 0, $categoryId);
		}

		if (!$canRead)
		{
			$categoriesCollection = $this->getCategoriesCollection($factory);
			$category = array_shift($categoriesCollection);
			if (!$category)
			{
				return [
					'categoryId' => null,
				];
			}

			$categoryId = $category->getId();
			$canRead = true;
		}

		if ($entityTypeId === \CCrmOwnerType::Deal && $categoryId >= 0)
		{
			\CUserOptions::SetOption('crm', 'current_deal_category', $categoryId);
		}
		else if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId) && $categoryId >= 0)
		{
			$optionName = 'current_' . mb_strtolower($factory->getEntityName()) . '_category';
			\CUserOptions::SetOption('crm', $optionName, $categoryId);
		}

		$entity = Entity::getInstance(\CCrmOwnerType::ResolveName($entityTypeId));
		$userId = (int)$this->getCurrentUser()->getId();

		$searchRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getSearchLimitRestriction();
		$infoHelperId = $searchRestriction->getMobileInfoHelperId();

		return [
			'permissions' => [
				'add' => $userPermissions->checkAddPermissions($entityTypeId, $categoryId),
				'read' => $canRead,
				'delete' => $userPermissions->checkDeletePermissions($entityTypeId, 0, $categoryId),
				'update' => $userPermissions->checkUpdatePermissions($entityTypeId, 0, $categoryId),
			],
			'link' => $entity->getDesktopLink($categoryId),
			'counters' => $entity->getCounters($userId, $categoryId),
			'categoryId' => $categoryId,
			'sortType' => $this->getSortType($entityTypeId, $categoryId),
			'restrictions' => [
				'search' => [
					'isExceeded' => $searchRestriction->isExceeded($entityTypeId),
					'infoHelperId' => $infoHelperId,
				],
			],
		];
	}

	private function getSortType(int $entityTypeId, ?int $categoryId): ?string
	{
		// only deals support now
		if ($entityTypeId !== \CCrmOwnerType::Deal)
		{
			return null;
		}

		$instance = \Bitrix\Crm\Kanban\Entity::getInstance(\CCrmOwnerType::ResolveName($entityTypeId));
		if ($instance)
		{
			if ($categoryId !== null)
			{
				$instance->setCategoryId($categoryId);
			}

			return $instance->getSortSettings()->getCurrentType();
		}

		return null;
	}

	/**
	 * Get array of category list
	 *
	 * @param Factory $factory
	 * @return Dto\CategoryList
	 */
	public function getListAction(Factory $factory): Dto\CategoryList
	{
		if ($factory->isCategoriesSupported())
		{
			return $this->getCategoryList($factory);
		}

		return new Dto\CategoryList();
	}

	/**
	 * @param Factory $factory
	 * @param int $categoryId
	 * @return Dto\Category|null
	 */
	public function getAction(Factory $factory, int $categoryId): ?Dto\Category
	{
		if ($factory->isCategoriesSupported())
		{
			return $this->getCategoryById($factory, $categoryId);
		}

		return $this->getCategoryFromCategorylessEntity($factory, $categoryId);
	}

	private function getCategoryFromCategorylessEntity(Factory $factory, int $categoryId = 0): Dto\Category
	{
		$entityTypeId = $factory->getEntityTypeId();
		$permissions = $this->getCategoryPermissions($entityTypeId, $categoryId);

		$categoriesEnabled = $factory->isCategoriesEnabled();
		$stagesEnabled = $factory->isStagesEnabled();
		$tunnelsEnabled = $categoriesEnabled && $stagesEnabled;

		$stages = $factory->getStages($categoryId);
		$stageColors = $this->getStageColors($stages);
		$stagesBySemantics = $this->getStagesBySemantics($stages, [], $stageColors);

		return Dto\Category::make([
			'id' => 0,
			'categoryId' => 0,
			'name' => $factory->getEntityDescriptionInPlural(),
			'isDefault' => true,
			'editable' => $this->canUserEditCategory(),
			'access' => $this->getAccess($permissions),
			'categoriesSupported' => $factory->isCategoriesSupported(),
			'categoriesEnabled' => $categoriesEnabled,
			'stagesEnabled' => $stagesEnabled,
			'tunnelsEnabled' => $tunnelsEnabled,
			'processStages' => $stagesBySemantics[PhaseSemantics::PROCESS] ?? [],
			'successStages' => $stagesBySemantics[PhaseSemantics::SUCCESS] ?? [],
			'failedStages' => $stagesBySemantics[PhaseSemantics::FAILURE] ?? [],
			'documentFields' => $this->getUsedDocumentFields($entityTypeId),
		]);
	}

	public function createAction(Factory $factory, array $fields): ?int
	{
		$newCategory = $factory->createCategory($fields);

		if (!Container::getInstance()->getUserPermissions()->canAddCategory($newCategory))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		$result = $newCategory->save();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $newCategory->getId();
	}

	public function deleteAction(Factory $factory, int $categoryId): void
	{
		$category = $factory->getCategory($categoryId);
		if (!$category)
		{
			$this->addError(ErrorCode::getNotFoundError());
			return;
		}

		if (!Container::getInstance()->getUserPermissions()->canDeleteCategory($category))
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return;
		}

		$result = $category->delete();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
	}

	public function updateAction(Factory $factory, int $categoryId, array $fields): void
	{
		if (!$this->canUpdateCategory($factory, $categoryId))
		{
			return;
		}

		$entityId = $factory->getStagesEntityId($categoryId);
		$stages = array_merge(
			$fields['processStages'] ?? [],
			$fields['successStages'] ?? [],
			$fields['failedStages'] ?? [],
		);

		foreach ($stages as $stage)
		{
			$stageResult = $this->updateStageSort($entityId, $stage['id'], $stage['sort']);
			if (!$stageResult->isSuccess())
			{
				$this->addErrors($stageResult->getErrors());
				return;
			}
		}

		$newCategoryName = ($fields['name'] ?? null);
		if (!$this->updateCategoryName($factory, $categoryId, $newCategoryName))
		{
			return;
		}

		$permissions = [
			UserPermissions::PERMISSION_ALL,
			UserPermissions::PERMISSION_SELF,
			UserPermissions::PERMISSION_NONE,
		];
		$entityTypeId = $factory->getEntityTypeId();
		if (in_array($fields['access'], $permissions, true))
		{
			$this->setAccess($entityTypeId, $fields['id'], $fields['access']);
		}
		elseif (is_numeric($fields['access']))
		{
			$this->copyAccessCategory($entityTypeId, $fields['id'], (int)$fields['access']);
		}
	}

	private function canUpdateCategory(Factory $factory, int $categoryId): bool
	{
		$isCategoriesEnabled = $factory->isCategoriesEnabled();
		if ($isCategoriesEnabled)
		{
			$category = $this->getCategoryFromFactory($factory, $categoryId);
			if (!$category)
			{
				return false;
			}

			if (!Container::getInstance()->getUserPermissions()->canUpdateCategory($category))
			{
				$this->addError(ErrorCode::getAccessDeniedError());
				return false;
			}
		}
		elseif (!Container::getInstance()->getUserPermissions()->canUpdateType($factory->getEntityTypeId()))
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return false;
		}

		return true;
	}

	private function updateCategoryName(Factory $factory, int $categoryId, ?string $name): bool
	{
		if (!$name || !$factory->isCategoriesEnabled())
		{
			return true;
		}

		$category = $this->getCategoryFromFactory($factory, $categoryId);
		if (!$category)
		{
			return false;
		}

		$category->setName($name);
		$result = $category->save();
		if ($result->isSuccess())
		{
			return true;
		}

		$this->addErrors($result->getErrors());

		return false;
	}

	private function getCategoryFromFactory(Factory $factory, int $categoryId): ?Crm\Category\Entity\Category
	{
		$category = $factory->getCategory($categoryId);
		if ($category)
		{
			return $category;
		}

		$this->addError(ErrorCode::getNotFoundError());

		return null;
	}

	private function setAccess(int $entityTypeId, int $categoryId, string $access): void
	{
		$result = Crm\Category\CategoryPermissionsManager::getInstance()->setPermissions(new \Bitrix\Crm\CategoryIdentifier($entityTypeId, $categoryId), $access);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
	}

	private function copyAccessCategory(int $entityTypeId, int $categoryId, int $donorId): void
	{
		$result = Crm\Category\CategoryPermissionsManager::getInstance()->copyPermissions(
			new \Bitrix\Crm\CategoryIdentifier($entityTypeId, $donorId),
			new \Bitrix\Crm\CategoryIdentifier($entityTypeId, $categoryId),
		);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
	}

	/**
	 * @param Factory $factory
	 * @param int $categoryId
	 * @param array $params
	 * @return Dto\StageCounter[]|null
	 */
	public function getCountersAction(Factory $factory, int $categoryId, array $params = []): ?array
	{
		if (!$this->canViewItems($factory, $categoryId))
		{
			return null;
		}

		$entityTypeName = $factory->getEntityName();

		$extra = ['CATEGORY_ID' => $categoryId];
		$kanban = Kanban::getInstance($entityTypeName, $extra);

		$filterParams = $this->getPreparedFilterParams($params, $kanban, $factory);

		$columns = $kanban->getColumns(false, false, $filterParams);

		$stages = [];
		foreach ($columns as $column)
		{
			$id = (int)$column['real_id'];
			if ($id > 0)
			{
				$stages[] = Dto\StageCounter::make([
					'id' => $id,
					'total' => $column['total'],
					'count' => $column['count'],
					'currency' => $column['currency'],
					'dropzone' => $column['dropzone'],
				]);
			}
		}

		return [
			'stages' => $stages,
		];
	}

	private function getPreparedFilterParams(array $params, Kanban $kanban, Factory $factory): array
	{
		$filterParams = [];
		$filter = $params['filter'];

		if (empty($filter['presetId']))
		{
			$filterParams['FORCE_FILTER'] = 'Y';
		}
		else
		{
			$filterParams['FILTER_PRESET_ID'] = $filter['presetId'];
		}

		if (isset($filter['search']))
		{
			$filterParams['filter']['SEARCH_CONTENT'] = $filter['search'];
		}

		if (isset($filter['tmpFields']))
		{
			$entityTypeId = $factory->getEntityTypeId();
			$entityTypeName = $factory->getEntityName();

			$prepareParams = array_merge($params, ['filterParams' => $filterParams]);

			$entity = $kanban->getEntity()->setGridIdInstance(new GridId($entityTypeId), $entityTypeId);

			Entity::getInstance($entityTypeName)->prepare($prepareParams)->prepareFilter($entity);
		}

		return $filterParams;
	}

	private function getAccess($permissions): string
	{
		$access = null;
		array_walk_recursive($permissions, static function ($item) use (&$access) {
			if ($access === null)
			{
				$access = $item;
			}
			elseif ($access !== false && $access !== $item)
			{
				$access = false;
			}
		});

		return (
		!in_array($access, [BX_CRM_PERM_ALL, BX_CRM_PERM_SELF, BX_CRM_PERM_NONE], true)
			? 'C'
			: $access
		);
	}

	private function updateStageSort(string $entityTypeId, int $stageId, int $stageSort): Result
	{
		$status = new \CCrmStatus($entityTypeId);

		$stage = $status->GetStatusById($stageId);
		if (!$stage)
		{
			return (new Result())->addError(ErrorCode::getNotFoundError());
		}

		$fields = [];
		if ($stageSort > 0)
		{
			$fields['SORT'] = $stageSort;
		}
		else
		{
			$fields['SORT'] = (int)$stage['SORT'];
		}

		return \Bitrix\Crm\StatusTable::update($stageId, $fields);
	}

	private function getCategoryList(Factory $factory): ?Dto\CategoryList
	{
		$categories = [];
		$restrictions = [];
		$canUserEditCategory = $this->canUserEditCategory();

		$sortedTunnelsByCategory = [];
		if ($canUserEditCategory)
		{
			$tunnelScheme = (new TunnelManager($factory->getEntityTypeId()))->getScheme();

			$sortedTunnelsByCategory = array_reduce($tunnelScheme['stages'],
				function ($acc, $stage) use ($factory) {
					foreach ($stage['tunnels'] as $tunnel)
					{
						$tunnelData = $this->prepareTunnelData($factory, $tunnel);
						if ($tunnelData)
						{
							$acc[$stage['categoryId']][] = $tunnelData;
						}
					}
					return $acc;
				}, []);
		}

		$categoriesCollection = $this->getCategoriesCollection($factory);

		foreach ($categoriesCollection as $category)
		{
			$categoryId = $category->getId();

			$categoryData = [
				'id' => $categoryId,
				'categoryId' => $categoryId,
				'name' => $category->getName(),
				'sort' => $category->getSort(),
				'isDefault' => $category->getIsDefault(),
				'tunnels' => $sortedTunnelsByCategory[$categoryId] ?? [],
				'categoriesEnabled' => $factory->isCategoriesEnabled(),
			];

			if (Counter\EntityCounterFactory::isEntityTypeSupported($factory->getEntityTypeId()))
			{
				$categoryData['counter'] = Counter\EntityCounterFactory::create(
					$factory->getEntityTypeId(),
					Counter\EntityCounterType::ALL,
					$this->getCurrentUser()->getId(),
					['DEAL_CATEGORY_ID' => $categoryId]
				)->getValue();
			}

			$categories[] = $categoryData;
		}

		if ($factory->getEntityName() === \CCrmOwnerType::DealName)
		{
			$dealCategoryLimitRestriction = RestrictionManager::getDealCategoryLimitRestriction();
			if ($dealCategoryLimitRestriction)
			{
				$restrictions[] = [
					'id' => $dealCategoryLimitRestriction->getMobileInfoHelperId(),
					'name' => $dealCategoryLimitRestriction->getName(),
					'isExceeded' => $dealCategoryLimitRestriction->isExceeded(),
				];
			}
		}

		return Dto\CategoryList::make([
			'categories' => $categories,
			'restrictions' => $restrictions,
			'canUserEditCategory' => $canUserEditCategory,
		]);
	}

	private function canUserEditCategory(): bool
	{
		return Container::getInstance()->getUserPermissions()->canWriteConfig();
	}

	private function getCategoriesCollection(Factory $factory): array
	{
		return
			Container::getInstance()
				->getUserPermissions()
				->filterAvailableForReadingCategories(
					$factory->getCategories()
				)
		;
	}

	/**
	 * @param Factory $factory
	 * @param int $categoryId
	 * @return Dto\Category|null
	 */
	private function getCategoryById(Factory $factory, int $categoryId): ?Dto\Category
	{
		$categoryNotFound = false;
		$category = ($factory->isCategoriesSupported() ? $factory->getCategory($categoryId) : null);
		if (!$category)
		{
			$categoryNotFound = true;
			$category = $factory->createCategory([
				'ID' => $categoryId,
				'NAME' => "#{$categoryId}",
			]);
		}

		if (!Container::getInstance()->getUserPermissions()->canViewItemsInCategory($category))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		$categoriesEnabled = $factory->isCategoriesEnabled();
		$stagesEnabled = $factory->isStagesEnabled();
		$tunnelsEnabled = $categoriesEnabled && $stagesEnabled;

		$stageObjects = $factory->getStages($categoryId);
		$stageColors = $this->getStageColors($stageObjects);
		$canUserEditCategory = Container::getInstance()->getUserPermissions()->canWriteConfig();

		$filteredTunnelsByCategory = [];

		if ($tunnelsEnabled && $canUserEditCategory)
		{
			$tunnelScheme = (new TunnelManager($factory->getEntityTypeId()))->getScheme();

			$filteredTunnelsByCategory = array_reduce($tunnelScheme['stages'],
				function ($acc, $stage) use ($factory, $categoryId, $stageColors) {
					if ($stage['categoryId'] === $categoryId)
					{
						foreach ($stage['tunnels'] as $tunnel)
						{
							$tunnelData = $this->prepareTunnelData($factory, $tunnel, $stageColors);
							if (!is_null($tunnelData))
							{
								$acc[$stage['stageId']][] = $tunnelData;
							}
						}
					}

					return $acc;
				}, []);
		}

		$stagesBySemantics = $this->getStagesBySemantics($stageObjects, $filteredTunnelsByCategory, $stageColors);

		if ($categoryNotFound && empty($stagesBySemantics))
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		$entityTypeId = $factory->getEntityTypeId();
		$permissions = $this->getCategoryPermissions($entityTypeId, $categoryId);

		return Dto\Category::make([
			'id' => $category->getId(),
			'categoryId' => $category->getId(),
			'name' => $categoriesEnabled ? $category->getName() : $factory->getEntityDescriptionInPlural(),
			'isDefault' => $category->getIsDefault(),
			'editable' => $this->canUserEditCategory(),
			'access' => $this->getAccess($permissions),
			'categoriesSupported' => $factory->isCategoriesSupported(),
			'categoriesEnabled' => $categoriesEnabled,
			'stagesEnabled' => $stagesEnabled,
			'tunnelsEnabled' => $tunnelsEnabled,
			'processStages' => $stagesBySemantics[PhaseSemantics::PROCESS] ?? [],
			'successStages' => $stagesBySemantics[PhaseSemantics::SUCCESS] ?? [],
			'failedStages' => $stagesBySemantics[PhaseSemantics::FAILURE] ?? [],
			'documentFields' => $this->getUsedDocumentFields($entityTypeId),
		]);
	}

	private function getStagesBySemantics(
		EO_Status_Collection $stageObjects,
		array $filteredTunnelsByCategory = [],
		array $stageColors = []
	): array
	{
		$stagesBySemantics = [];

		foreach ($stageObjects as $stage)
		{
			$statusId = $stage->getStatusId();
			$semanticId = ($stage->getSemantics() ?? PhaseSemantics::PROCESS);
			$semanticId = $semanticId ?: PhaseSemantics::PROCESS;
			$color = $stageColors[$statusId]['COLOR'] ?? $stage->getColor();

			$stagesBySemantics[$semanticId][] = [
				'id' => $stage->getId(),
				'name' => $stage->getName(),
				'sort' => $stage->getSort(),
				'statusId' => $statusId,
				'semantics' => $semanticId,
				'color' => $color,
				'tunnels' => ($filteredTunnelsByCategory[$statusId] ?? []),
			];
		}

		return $stagesBySemantics;
	}

	private function getCategoryPermissions(int $entityTypeId, int $categoryId = 0): array
	{
		$permissionEntityName = UserPermissions::getPermissionEntityType($entityTypeId, $categoryId);
		return RolePermission::getByEntityId($permissionEntityName);
	}

	private function getUsedDocumentFields(int $entityTypeId): array
	{
		$documentFields = [];

		if (!empty($this->usedDocumentFields))
		{
			$allFields = $this->getTunnelDocumentFields($entityTypeId);

			foreach ($this->usedDocumentFields as $field)
			{
				if (!empty($allFields[$field]))
				{
					$documentFields[] = $allFields[$field];
				}
			}
		}

		return $documentFields;
	}

	private function getTunnelGlobalVariables(int $entityTypeId): array
	{
		static $tunnelGlobalVariables = [];

		if (!isset($tunnelGlobalVariables[$entityTypeId]))
		{
			$globalConstants = [];

			foreach (GlobalVar::getAll(\CCrmBizProcHelper::ResolveDocumentType($entityTypeId)) as $key => $value)
			{
				$globalConstants[$key] = $value['Name'];
			}

			$tunnelGlobalVariables[$entityTypeId] = $globalConstants;
		}

		return $tunnelGlobalVariables[$entityTypeId];
	}

	private function getTunnelGlobalConstants(int $entityTypeId): array
	{
		static $tunnelGlobalConstants = [];

		if (!isset($tunnelGlobalConstants[$entityTypeId]))
		{
			$globalConstants = [];

			foreach (GlobalConst::getAll(\CCrmBizProcHelper::ResolveDocumentType($entityTypeId)) as $key => $value)
			{
				$globalConstants[$key] = $value['Name'];
			}

			$tunnelGlobalConstants[$entityTypeId] = $globalConstants;
		}

		return $tunnelGlobalConstants[$entityTypeId];
	}

	private function getTunnelDocumentFields(int $entityTypeId): array
	{
		static $tunnelDocumentFields = [];

		if (!isset($tunnelDocumentFields[$entityTypeId]))
		{
			$documentFields = [];

			$documentType = \CCrmBizProcHelper::ResolveDocumentType($entityTypeId);
			foreach (Helper::getDocumentFields($documentType) as $key => $documentField)
			{
				$options = [];

				if (!empty($documentField['Options']) && is_array($documentField['Options']))
				{
					foreach ($documentField['Options'] as $id => $value)
					{
						$options[] = [
							'id' => $id,
							'value' => $value,
						];
					}
				}

				$documentFields[$key] = [
					'id' => $documentField['Id'],
					'name' => $documentField['Name'],
					'type' => $documentField['Type'],
					'baseType' => $documentField['BaseType'],
					'expression' => $documentField['Expression'],
					'systemExpression' => $documentField['SystemExpression'],
					'multiple' => $documentField['Multiple'],
					'options' => $options,
				];
			}

			$tunnelDocumentFields[$entityTypeId] = $documentFields;
		}

		return $tunnelDocumentFields[$entityTypeId];
	}

	private function canViewItems(Factory $factory, int $categoryId): bool
	{
		if ($factory->isCategoriesSupported())
		{
			$category = $factory->getCategory($categoryId);

			if (!$category)
			{
				$this->addError(ErrorCode::getNotFoundError());
				return false;
			}

			if (!Container::getInstance()->getUserPermissions()->canViewItemsInCategory($category))
			{
				$this->addError(ErrorCode::getAccessDeniedError());
				return false;
			}

			return true;
		}

		$canRead = Container::getInstance()
			->getUserPermissions()
			->checkReadPermissions($factory->getEntityTypeId(), 0, $categoryId)
		;

		if (!$canRead)
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return false;
		}

		return true;
	}

	private function prepareTunnelData(Factory $factory, array $tunnel, array $stageColors = []): ?array
	{
		$srcStage = $factory->getStage($tunnel['srcStage']);
		$dstStage = $factory->getStage($tunnel['dstStage']);

		if ($srcStage === null || $dstStage === null)
		{
			return null;
		}

		$dstCategory = $factory->getCategory($tunnel['dstCategory']);
		$srcCategory = $factory->getCategory($tunnel['srcCategory']);

		if ($dstCategory === null || $srcCategory === null)
		{
			return null;
		}

		$userLabel = \CBPHelper::UsersArrayToString(
			$tunnel['robot']['Properties']['Responsible'] ?? [],
			[],
			\CCrmBizProcHelper::ResolveDocumentType($factory->getEntityTypeId()),
			false
		);
		$userId = (int)\CBPHelper::StripUserPrefix($tunnel['robot']['Properties']['Responsible'][0] ?? null);

		$conditionGroup = [];

		if (!empty($tunnel['robot']['Condition']['items']) && is_array($tunnel['robot']['Condition']['items']))
		{
			foreach ($tunnel['robot']['Condition']['items'] as $condition)
			{
				$object = $condition[0]['object'] ?? null;
				$field = $condition[0]['field'] ?? null;

				if ($object === SourceType::GlobalVariable)
				{
					$field = $this->getTunnelGlobalVariables($factory->getEntityTypeId())[$field] ?? $field;
				}
				elseif ($object === SourceType::GlobalConstant)
				{
					$field = $this->getTunnelGlobalConstants($factory->getEntityTypeId())[$field] ?? $field;
				}
				elseif ($object === SourceType::DocumentField)
				{
					$this->usedDocumentFields[$field] = $field;
					$field = $this->getTunnelDocumentFields($factory->getEntityTypeId())[$field]['name'] ?? $field;
				}

				$conditionGroup[] = [
					'joiner' => $condition[1],
					'properties' => [
						'operator' => $condition[0]['operator'],
						'value' => $condition[0]['value'],
						'field' => $field,
						'object' => $object,
					],
				];
			}
		}

		$robotProperties = [];
		if (!empty($tunnel['robot']['Properties']))
		{
			$properties = $tunnel['robot']['Properties'];
			$robotProperties = [
				'categoryId' => $properties['CategoryId'],
				'stageId' => $properties['StageId'],
			];
		}

		if (!empty($tunnel['robot']['Delay']))
		{
			$basisId = $this->extractDelayBasisId($tunnel['robot']['Delay']['basis']);
			$robotDelayBasisName = $this->getTunnelDocumentFields($factory->getEntityTypeId())[$basisId] ?? null;
			$tunnel['robot']['Delay']['basisName'] = $robotDelayBasisName['name'] ?? null;
		}

		return [
			'dstStageId' => $dstStage->getId(),
			'dstStageStatusId' => $dstStage->getStatusId(),
			'dstStageName' => $dstStage->getName(),
			'dstStageColor' => $stageColors[$dstStage->getStatusId()]['COLOR'] ?? $dstStage->getColor(),
			'dstCategoryId' => $dstCategory->getId(),
			'dstCategoryName' => $dstCategory->getName(),
			'srcStageId' => $srcStage->getId(),
			'srcStageStatusId' => $srcStage->getStatusId(),
			'srcCategoryId' => $srcCategory->getId(),
			'srcStageColor' => $stageColors[$srcStage->getStatusId()]['COLOR'] ?? $srcStage->getColor(),
			'robot' => [
				'name' => $tunnel['robot']['Name'] ?? '',
				'properties' => $robotProperties,
				'delay' => $tunnel['robot']['Delay'] ?? null,
				'conditionGroup' => [
					'type' => $tunnel['robot']['Condition']['type'] ?? null,
					'items' => $conditionGroup,
				],
				'responsible' => [
					'id' => $userId,
					'label' => $userLabel,
				],
			],
		];
	}

	private function extractDelayBasisId(string $basis): string
	{
		$startString = '{=Document:';
		$endString = '}';
		$startPos = strpos($basis, $startString);
		$endPos = strpos($basis, $endString, $startPos + strlen($startString));

		if ($startPos !== false && $endPos !== false) {
			return substr($basis, $startPos + strlen($startString), $endPos - $startPos - strlen($startString));
		}

		return '';
	}

	private function getStageColors(EO_Status_Collection $stageObjects): array
	{
		$colors = [];
		foreach ($stageObjects as $stage)
		{
			$colors[$stage->getStatusId()] = [
				'COLOR' => $stage->getColor(),
				'SEMANTICS' => $stage->getSemantics(),
			];
		}

		return PhaseColorScheme::fillDefaultColors($colors);
	}
}
