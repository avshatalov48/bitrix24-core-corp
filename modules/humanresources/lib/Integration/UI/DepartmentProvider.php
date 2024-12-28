<?php

namespace Bitrix\HumanResources\Integration\UI;

use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Enum\NodeActiveFilter;
use Bitrix\HumanResources\Item\Collection\NodeCollection;
use Bitrix\HumanResources\Item\Collection\NodeMemberCollection;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Util\StructureHelper;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Socialnetwork\Integration\UI\EntitySelector\UserProvider;
use Bitrix\Tasks\Exception;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;
use Bitrix\UI\EntitySelector\Tab;

class DepartmentProvider extends BaseProvider
{
	public const MODE_USERS_AND_DEPARTMENTS = 'usersAndDepartments';
	public const MODE_USERS_ONLY = 'usersOnly';
	public const MODE_DEPARTMENTS_ONLY = 'departmentsOnly';

	private const ENTITY_ID = 'structure-node';

	private int $limit = 100;
	private NodeRepository $nodeRepository;

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->nodeRepository = Container::getNodeRepository(true);

		if (isset($options['selectMode']) && in_array($options['selectMode'], self::getSelectModes()))
		{
			$this->options['selectMode'] = $options['selectMode'];
		}
		else
		{
			$this->options['selectMode'] = self::MODE_USERS_ONLY;
		}

		$this->options['allowFlatDepartments'] = (
			isset($options['allowFlatDepartments']) && $options['allowFlatDepartments'] === true
		);

		$this->options['allowOnlyUserDepartments'] = (
			isset($options['allowOnlyUserDepartments']) && $options['allowOnlyUserDepartments'] === true
		);

		$this->options['allowSelectRootDepartment'] = $this->getSelectMode() === self::MODE_DEPARTMENTS_ONLY;
		if (isset($options['allowSelectRootDepartment']) && is_bool($options['allowSelectRootDepartment']))
		{
			$this->options['allowSelectRootDepartment'] = $options['allowSelectRootDepartment'];
		}

		if (isset($options['userOptions']) && is_array($options['userOptions']))
		{
			if (Loader::includeModule('socialnetwork'))
			{
				$userProvider = new UserProvider($options['userOptions']); // process options by UserProvider
				$this->options['userOptions'] = $userProvider->getOptions();
			}
		}

		$this->options['fillDepartmentsTab'] = true;
		if (isset($options['fillDepartmentsTab']) && is_bool($options['fillDepartmentsTab']))
		{
			$this->options['fillDepartmentsTab'] = $options['fillDepartmentsTab'];
		}

		$this->options['fillRecentTab'] = false;
		if (isset($options['fillRecentTab']) && is_bool($options['fillRecentTab']))
		{
			$this->options['fillRecentTab'] =
				$options['fillRecentTab'] && $this->options['selectMode'] === self::MODE_DEPARTMENTS_ONLY;
		}

		$this->options['depthLevel'] = 0;
		if (isset($options['depthLevel']) && is_int($options['depthLevel']) && $this->options['fillRecentTab'])
		{
			$this->options['depthLevel'] = $options['depthLevel'];
		}

		$this->options['shouldCountSubdepartments'] = false;
		if (isset($options['shouldCountSubdepartments']) && is_bool($options['shouldCountSubdepartments']))
		{
			$this->options['shouldCountSubdepartments'] = $options['shouldCountSubdepartments']
				&& (
					$this->options['selectMode'] === self::MODE_DEPARTMENTS_ONLY
					|| $this->options['selectMode'] === self::MODE_USERS_AND_DEPARTMENTS
				);
		}

		$this->options['shouldCountUsers'] = false;
		if (isset($options['shouldCountUsers']) && is_bool($options['shouldCountUsers']))
		{
			$this->options['shouldCountUsers'] = $options['shouldCountUsers']
				&& (
					$this->options['selectMode'] === self::MODE_USERS_ONLY
					|| $this->options['selectMode'] === self::MODE_USERS_AND_DEPARTMENTS
				);
		}

		$this->options['forSearch'] = false;

		if (isset($options['forSearch']) && is_bool($options['forSearch']))
		{
			$this->options['forSearch'] = $options['forSearch'];
		}

		$this->options['flatMode'] = false;

		if (isset($options['flatMode']) && is_bool($options['flatMode']))
		{
			$this->options['flatMode'] = $options['flatMode'];
		}
	}

	public function getSelectMode()
	{
		return $this->options['selectMode'];
	}

	public static function getSelectModes()
	{
		return [
			self::MODE_DEPARTMENTS_ONLY,
			self::MODE_USERS_ONLY,
			self::MODE_USERS_AND_DEPARTMENTS,
		];
	}

	public function getLimit(): int
	{
		return $this->limit;
	}

	protected function getUserOptions(Dialog $dialog): array
	{
		if (isset($this->getOptions()['userOptions']) && is_array($this->getOptions()['userOptions']))
		{
			return $this->getOptions()['userOptions'];
		}
		elseif ($dialog->getEntity('user') && is_array($dialog->getEntity('user')->getOptions()))
		{
			return $dialog->getEntity('user')->getOptions();
		}

		return [];
	}

	public function isAvailable(): bool
	{
		if (!CurrentUser::get())
		{
			return false;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		return UserProvider::isIntranetUser();
	}

	public function getItems(array $ids): array
	{
		return $this->getDepartments($ids);
	}

	public function getSelectedItems(array $ids): array
	{
		return $this->getDepartments($ids, ['active' => null]);
	}

	public function fillDialog(Dialog $dialog): void
	{
		if ($this->options['fillDepartmentsTab'] === true || $this->options['fillRecentTab'] === true)
		{
			$limit = $this->getLimit();

			$departments = self::getStructure(['limit' => $limit]);
			$limitExceeded = $limit <= $departments->count();
			if ($limitExceeded)
			{
				// Select only the first level
				$departments = self::getStructure(['depthLevel' => $this->options['depthLevel']]);
			}

			if (!$limitExceeded || $this->getSelectMode() === self::MODE_USERS_ONLY)
			{
				// Turn off the user search
				$entity = $dialog->getEntity('structure-nodes');
				if ($entity)
				{
					$entity->setDynamicSearch(false);
				}
			}

			$forceDynamic = $this->getSelectMode() === self::MODE_DEPARTMENTS_ONLY && !$limitExceeded ? false : null;

			if ($this->options['fillRecentTab'] === true)
			{
				$this->fillRecentDepartments($dialog, $departments);
			}

			if ($this->options['fillDepartmentsTab'] === true)
			{
				$this->fillDepartments($dialog, $departments, $forceDynamic);
			}
		}

		if ($this->options['fillDepartmentsTab'] === true)
		{
			$icon =
				'data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2223%22%20height%3D%2223%22%20fill%3D%22' .
				'none%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cpath%20d%3D%22M15.953%2018.654a29.847%' .
				'2029.847%200%2001-6.443.689c-2.672%200-5.212-.339-7.51-.948.224-1.103.53-2.573.672-3.106.238-.896' .
				'%201.573-1.562%202.801-2.074.321-.133.515-.24.71-.348.193-.106.386-.213.703-.347.036-.165.05-.333.' .
				'043-.5l.544-.064s.072.126-.043-.614c0%200-.61-.155-.64-1.334%200%200-.458.148-.486-.566a1.82%201.' .
				'82%200%2000-.08-.412c-.087-.315-.164-.597.233-.841l-.287-.74S5.87%204.583%207.192%204.816c-.537-.' .
				'823%203.99-1.508%204.29%201.015.119.76.119%201.534%200%202.294%200%200%20.677-.075.225%201.17%200' .
				'%200-.248.895-.63.693%200%200%20.062%201.133-.539%201.325%200%200%20.043.604.043.645l.503.074s-.01' .
				'4.503.085.557c.458.287.96.505%201.488.645%201.561.383%202.352%201.041%202.352%201.617%200%200%20.6' .
				'41%202.3.944%203.802z%22%20fill%3D%22%23ABB1B8%22/%3E%3Cpath%20d%3D%22M21.47%2016.728c-.36.182-.73' .
				'.355-1.112.52h-3.604c-.027-.376-.377-1.678-.58-2.434-.081-.299-.139-.513-.144-.549-.026-.711-1.015-' .
				'1.347-2.116-1.78a1.95%201.95%200%2000.213-.351c.155-.187.356-.331.585-.42l.017-.557-1.208-.367s-.31' .
				'-.14-.342-.14c.036-.086.08-.168.134-.245.023-.06.17-.507.17-.507-.177.22-.383.415-.614.58.211-.363.' .
				'39-.743.536-1.135a7.02%207.02%200%2000.192-1.15%2016.16%2016.16%200%2001.387-2.093c.125-.343.346-.64' .
				'7.639-.876a3.014%203.014%200%20011.46-.504h.062c.525.039%201.03.213%201.462.504.293.229.514.532.64.8' .
				'76.174.688.304%201.387.387%202.092.037.38.104.755.201%201.124.145.4.322.788.527%201.161a3.066%203.06' .
				'6%200%2001-.614-.579s.113.406.136.466c.063.09.119.185.167.283-.03%200-.342.141-.342.141l-1.208.367.0' .
				'17.558c.23.088.43.232.585.419.073.179.188.338.337.466.292.098.573.224.84.374.404.219.847.36%201.306.' .
				'416.463.074.755.8.755.8l.037.729.093%201.811z%22%20fill%3D%22%23ABB1B8%22/%3E%3C/svg%3E';

			$dialog->addTab(
				new Tab(
					[
						'id' => 'structure-nodes',
						'title' => Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_DEPARTMENTS_TAB_TITLE'),
						'itemMaxDepth' => 7,
						'icon' => [
							'default' => $icon,
							'selected' => str_replace('ABB1B8', 'fff', $icon),
						],
					],
				),
			);
		}

	}

	private function getCurrentUserDepartments(): ?NodeMemberCollection
	{
		$currentUser = CurrentUser::get()->getId();

		if (!$currentUser || $this->options['allowOnlyUserDepartments'] !== true)
		{
			return new NodeMemberCollection();
		}

		return Container::getNodeMemberRepository()->findAllByEntityIdAndEntityType(
			$currentUser,
			MemberEntityType::USER,
		);
	}

	private function fillRecentDepartments(Dialog $dialog, NodeCollection $departments)
	{
		foreach ($departments as $department)
		{
			$isRootDepartment = (int)$department->parentId === 0;
			$hideRootDepartment = $isRootDepartment && !$this->options['allowSelectRootDepartment'];

			if ($hideRootDepartment && $isRootDepartment)
			{
				continue;
			}

			$item = new Item(
				[
					'id' => $department->id,
					'entityId' => self::ENTITY_ID,
					'title' => $department->name,
					'tabs' => 'recent',
					'customData' => [
						'iblock_section_id' => DepartmentBackwardAccessCode::extractIdFromCode($department->accessCode),
					],
				],
			);

			$dialog->addRecentItem($item);
		}
	}

	private function fillDepartments(Dialog $dialog, NodeCollection $nodeCollection, ?bool $forceDynamic = null)
	{
		$parents = [];
		$active = $options['active'] ?? NodeActiveFilter::ONLY_GLOBAL_ACTIVE;

		if ($this->options['allowOnlyUserDepartments'])
		{
			$currentDepartments = $this->getCurrentUserDepartments();
			$allowedNodeCollection = new NodeCollection();

			foreach ($currentDepartments as $currentDepartment)
			{
				$allowedNodeCollection->add(
					new Node(
						name: '',
						type: NodeEntityType::DEPARTMENT,
						structureId: 0,
						id: $currentDepartment->nodeId,
					),
				);
			}
			$nodeCollection = $this->nodeRepository->getChildOfNodeCollection(
				nodeCollection: $allowedNodeCollection,
				depthLevel: DepthLevel::FULL,
				activeFilter: $active,
			)->orderMapByInclude();
		}

		$selectMode = $dialog->getEntity(self::ENTITY_ID)?->getOptions()['selectMode'] ?? $this->getSelectMode();
		foreach ($nodeCollection as $node)
		{
			$isStructureRoot = !((int)$node->parentId > 0);
			$isRootDepartment = !$nodeCollection->getItemById($node->parentId);
			$hideRootDepartment = $isRootDepartment && !$this->options['allowSelectRootDepartment'];

			$availableInRecentTab = true;
			if ($selectMode === self::MODE_USERS_ONLY)
			{
				$availableInRecentTab = false;
			}

			$childDepartmentCount = null;
			if ($this->options['shouldCountSubdepartments'])
			{
				$childDepartmentCount = $this->nodeRepository->getChildOf($node->getId())->count();
			}

			$usersCount = null;
			if ($this->options['shouldCountUsers'])
			{
				$usersOptions = $this->getUserOptions($dialog);
				$usersCount = UserProvider::getUsers(
					[
						'departmentId' => DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode),
					] + $usersOptions,
				)->count();
			}

			$item = new Item(
				[
					'id' => $node->id,
					'entityId' => self::ENTITY_ID,
					'title' => $node->name,
					'tabs' => 'structure-nodes',
					'searchable' => $availableInRecentTab,
					'availableInRecentTab' => $availableInRecentTab,
					'customData' => [
						'subdepartmentsCount' => $childDepartmentCount,
						'usersCount' => $usersCount,
						'iblock_section_id' => DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode),
					],
					'nodeOptions' => [
						'dynamic' => !$this->options['flatMode']
							&& ((!is_bool($forceDynamic) || $forceDynamic)),
						'open' => $isRootDepartment,
					],
				],
			);

			if ($selectMode === self::MODE_DEPARTMENTS_ONLY && !$hideRootDepartment)
			{
				$item->addChild(
					new Item(
						[
							'id' => $node->id,
							'title' => $node->name,
							'entityId' => self::ENTITY_ID,
							'nodeOptions' => [
								'title' => Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_SELECT_DEPARTMENT'),
								'renderMode' => 'override',
							],
							'customData' => [
								'iblock_section_id' => DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode),
							],
						],
					),
				);
			}
			elseif ($selectMode === self::MODE_USERS_AND_DEPARTMENTS)
			{
				if (!$hideRootDepartment && !$this->options['flatMode'])
				{
					$item->addChild(
						new Item(
							[
								'id' => $node->id,
								'title' => $node->name,
								'entityId' => self::ENTITY_ID,
								'nodeOptions' => [
									'title' => Loc::getMessage(
										$this->options['forSearch'] ? 'HUMANRESOURCES_ENTITY_SELECTOR_ALL_EMPLOYEES_SELECT'
											: 'HUMANRESOURCES_ENTITY_SELECTOR_ALL_EMPLOYEES_SUBDIVISIONS',
									),
									'avatar' => '/bitrix/js/humanresourcres/entity-selector/src/images/department-option.svg',
									'renderMode' => 'override',
								],
								'customData' => [
									'iblock_section_id' => DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode),
								],
							],
						),
					);
				}

				if ($this->options['allowFlatDepartments'] && !$this->options['flatMode'])
				{
					$item->addChild(
						new Item(
							[
								'id' => $node->id . ':F',
								'entityId' => self::ENTITY_ID,
								'title' =>
									$node->name . ''
									. Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_ONLY_EMPLOYEES')
								,
								'nodeOptions' => [
									'title' => Loc::getMessage(
										'HUMANRESOURCES_ENTITY_SELECTOR_ONLY_DEPARTMENT_EMPLOYEES',
									),
									'avatar' => '/bitrix/js/humanresources/entity-selector/src/images/department-option.svg',
									'renderMode' => 'override',
								],
								'customData' => [
									'iblock_section_id' => DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode) . 'F',
								],
							],
						),
					);
				}
			}

			if ($isStructureRoot)
			{
				$item->setTagOptions(
					[
						'bgColor' => '#F1FBD0',
						'textColor' => '#7FA800',
						'avatar' => '/bitrix/js/humanresources/entity-selector/src/images/company.svg',
					],
				);
			}

			if ($this->options['flatMode'])
			{
				$dialog->addItem($item);

				continue;
			}

			$parentItem = $parents[$node->parentId] ?? null;
			if ($parentItem)
			{
				$parentItem->addChild($item);
			}
			else
			{
				$dialog->addItem($item);
			}

			$parents[$node->id] = $item;
		}
	}

	public function getChildren(Item $parentItem, Dialog $dialog): void
	{
		try
		{
			$department = $this->nodeRepository->getById((int)$parentItem->getId());
		}
		catch (Exception $e)
		{
			return;
		}

		$departments = $this->getStructure(['parentId' => $department->id]);
		$this->fillDepartments($dialog, $departments);
		if ($this->getSelectMode() === self::MODE_DEPARTMENTS_ONLY)
		{
			return;
		}

		$userOptions = $this->getUserOptions($dialog);

		$items = UserProvider::makeItems(
			UserProvider::getUsers(
				['departmentId' => DepartmentBackwardAccessCode::extractIdFromCode($department->accessCode)]
				+ $userOptions,
			),
			$userOptions,
		);

		$headIds = array_column(
			Container::getNodeMemberService()->getDefaultHeadRoleEmployees($department->id)->getItemMap(),
			'entityId',
		);

		foreach ($items as $item)
		{
			if (in_array($item->getId(), $headIds))
			{
				$item->getNodeOptions()->set('caption', Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_MANAGER'));
				break;
			}
		}

		$dialog->addItems($items);
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$selectMode = $dialog->getEntity(self::ENTITY_ID)?->getOptions()['selectMode'] ?? self::MODE_USERS_ONLY;
		if ($selectMode === self::MODE_USERS_ONLY)
		{
			return;
		}

		$limit = $this->getLimit();

		$departments = $this->getStructure(
			[
				'searchQuery' => $searchQuery->getQuery(),
				'limit' => $limit,
			],
		);

		$limitExceeded = $limit <= $departments->count();
		if ($limitExceeded)
		{
			$searchQuery->setCacheable(false);
		}

		foreach ($departments as $department)
		{
			$isRootDepartment = (int)$department->parentId === 0;
			$hideRootDepartment = $isRootDepartment && !$this->options['allowSelectRootDepartment'];
			if ($hideRootDepartment)
			{
				continue;
			}

			$dialog->addItem(
				new Item(
					[
						'id' => $department->id,
						'entityId' => self::ENTITY_ID,
						'title' => $department->name,
						'customData' => [
							'iblock_section_id' => DepartmentBackwardAccessCode::extractIdFromCode($department->accessCode),
						],
					],
				),
			);

			if ($selectMode === self::MODE_USERS_AND_DEPARTMENTS && $this->options['allowFlatDepartments'])
			{
				$dialog->addItem(
					new Item(
						[
							'id' => $department->id . ':F',
							'entityId' => self::ENTITY_ID,
							'title' => $department->name . '' . Loc::getMessage(
									'HUMANRESOURCES_ENTITY_SELECTOR_ONLY_EMPLOYEES',
								),
							'customData' => [
								'iblock_section_id' => DepartmentBackwardAccessCode::extractIdFromCode($department->accessCode) . 'F',
							],
						],
					),
				);
			}
		}
	}

	private function getStructure(array $options = []): NodeCollection
	{
		$limit = isset($options['limit']) && is_int($options['limit']) ? $options['limit'] : 100;
		$active = $options['active'] ?? NodeActiveFilter::ONLY_GLOBAL_ACTIVE;

		$structure = StructureHelper::getDefaultStructure();

		return $this->nodeRepository->getNodesByName(
			structureId: $structure->id,
			name: $options['searchQuery'] ?? null,
			limit: $limit,
			parentId: $options['parentId'] ?? null,
			depth: $options['depthLevel'] ?? DepthLevel::FULL,
			activeFilter: $active,
		)->orderMapByInclude();
	}

	public function getDepartments(array $ids, array $options = []): array
	{
		$integerIds = array_map('intval', $ids);
		$idMap = array_combine($ids, $integerIds);

		$active = $options['active'] ?? NodeActiveFilter::ONLY_GLOBAL_ACTIVE;

		$items = [];
		try
		{
			$departments = $this->nodeRepository->findAllByIds($integerIds, $active);
		}
		catch (ObjectPropertyException|ArgumentException|SystemException $e)
		{
			return [];
		}

		if ($departments->count() > 0)
		{
			foreach ($idMap as $id => $integerId)
			{
				$department = $departments->getItemById($integerId);
				if (!$department)
				{
					continue;
				}

				$isFlatDepartment = is_string($id) && $id[-1] === 'F';
				if ($isFlatDepartment)
				{
					$availableInRecentTab =
						$this->getSelectMode() === self::MODE_USERS_AND_DEPARTMENTS
						&& $this->options['allowFlatDepartments'] === true;
				}
				else
				{
					$availableInRecentTab = $this->getSelectMode() !== self::MODE_USERS_ONLY;
					if ($department->depth === 1 && !$this->options['allowSelectRootDepartment'])
					{
						$availableInRecentTab = false;
					}
				}

				$titlePostfix =
					$isFlatDepartment
						? ' ' . Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_ONLY_EMPLOYEES')
						: ''
				;

				$items[] = new Item(
					[
						'id' => $id,
						'entityId' => self::ENTITY_ID,
						'title' => $department->name . $titlePostfix,
						'availableInRecentTab' => $availableInRecentTab,
						'searchable' => $availableInRecentTab,
						'customData' => [
							'iblock_section_id' => DepartmentBackwardAccessCode::extractIdFromCode($department->accessCode),
						],
					],
				);
			}
		}

		return $items;
	}
}