<?php

namespace Bitrix\Intranet\Integration\UI\EntitySelector;

use Bitrix\Iblock\EO_Section_Collection;
use Bitrix\Iblock\SectionTable;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Integration\UI\EntitySelector\UserProvider;
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

	private $limit = 100;

	public function __construct(array $options = [])
	{
		parent::__construct();

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

		$this->options['hideChatBotDepartment'] = true;
		if (isset($options['hideChatBotDepartment']) && is_bool($options['hideChatBotDepartment']))
		{
			$this->options['hideChatBotDepartment'] = $options['hideChatBotDepartment'];
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

		$this->options['depthLevel'] = 1;
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
		if (!$GLOBALS['USER']->isAuthorized())
		{
			return false;
		}

		if (!Loader::includeModule('socialnetwork') || !Loader::includeModule('iblock'))
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

			// Try to select all departments
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
				$entity = $dialog->getEntity('department');
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
				'data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2223%22%20height%3D%2223%22%20fill%3D%22'.
				'none%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cpath%20d%3D%22M15.953%2018.654a29.847%'.
				'2029.847%200%2001-6.443.689c-2.672%200-5.212-.339-7.51-.948.224-1.103.53-2.573.672-3.106.238-.896'.
				'%201.573-1.562%202.801-2.074.321-.133.515-.24.71-.348.193-.106.386-.213.703-.347.036-.165.05-.333.'.
				'043-.5l.544-.064s.072.126-.043-.614c0%200-.61-.155-.64-1.334%200%200-.458.148-.486-.566a1.82%201.'.
				'82%200%2000-.08-.412c-.087-.315-.164-.597.233-.841l-.287-.74S5.87%204.583%207.192%204.816c-.537-.'.
				'823%203.99-1.508%204.29%201.015.119.76.119%201.534%200%202.294%200%200%20.677-.075.225%201.17%200'.
				'%200-.248.895-.63.693%200%200%20.062%201.133-.539%201.325%200%200%20.043.604.043.645l.503.074s-.01'.
				'4.503.085.557c.458.287.96.505%201.488.645%201.561.383%202.352%201.041%202.352%201.617%200%200%20.6'.
				'41%202.3.944%203.802z%22%20fill%3D%22%23ABB1B8%22/%3E%3Cpath%20d%3D%22M21.47%2016.728c-.36.182-.73'.
				'.355-1.112.52h-3.604c-.027-.376-.377-1.678-.58-2.434-.081-.299-.139-.513-.144-.549-.026-.711-1.015-'.
				'1.347-2.116-1.78a1.95%201.95%200%2000.213-.351c.155-.187.356-.331.585-.42l.017-.557-1.208-.367s-.31'.
				'-.14-.342-.14c.036-.086.08-.168.134-.245.023-.06.17-.507.17-.507-.177.22-.383.415-.614.58.211-.363.'.
				'39-.743.536-1.135a7.02%207.02%200%2000.192-1.15%2016.16%2016.16%200%2001.387-2.093c.125-.343.346-.64'.
				'7.639-.876a3.014%203.014%200%20011.46-.504h.062c.525.039%201.03.213%201.462.504.293.229.514.532.64.8'.
				'76.174.688.304%201.387.387%202.092.037.38.104.755.201%201.124.145.4.322.788.527%201.161a3.066%203.06'.
				'6%200%2001-.614-.579s.113.406.136.466c.063.09.119.185.167.283-.03%200-.342.141-.342.141l-1.208.367.0'.
				'17.558c.23.088.43.232.585.419.073.179.188.338.337.466.292.098.573.224.84.374.404.219.847.36%201.306.'.
				'416.463.074.755.8.755.8l.037.729.093%201.811z%22%20fill%3D%22%23ABB1B8%22/%3E%3C/svg%3E'
			;

			$dialog->addTab(new Tab([
				'id' => 'departments',
				'title' => Loc::getMessage('INTRANET_ENTITY_SELECTOR_DEPARTMENTS_TAB_TITLE'),
				'itemMaxDepth' => 7,
				'icon' => [
					'default' => $icon,
					'selected' => str_replace('ABB1B8', 'fff', $icon),
					//'default' => '/bitrix/js/intranet/entity-selector/src/images/department-tab-icon.svg',
					//'selected' => '/bitrix/js/intranet/entity-selector/src/images/department-tab-icon-selected.svg'
				],
			]));
		}

	}

	private function getAllowOnlyUserDepartment(): ?array
	{
		$result = null;
		if ($this->options['allowOnlyUserDepartments'] === true)
		{
			$result = [];
			global $USER;
			if ($USER->isAuthorized())
			{
				$res = \CUser::getById($USER->getId());
				if (($user = $res->fetch()) && !empty($user['UF_DEPARTMENT']))
				{
					$result = $user['UF_DEPARTMENT'];
				}
			}
		}

		return $result;
	}

	private function fillRecentDepartments(Dialog $dialog, EO_Section_Collection $departments)
	{
		foreach ($departments as $department)
		{
			$isRootDepartment = $department->getDepthLevel() === 1 || $department->getId() === self::getRootDepartmentId();
			$hideRootDepartment = $isRootDepartment && !$this->options['allowSelectRootDepartment'];

			if ($hideRootDepartment && $isRootDepartment)
			{
				continue;
			}

			$item = new Item([
				'id' => $department->getId(),
				'entityId' => 'department',
				'title' => $department->getName(),
				'tabs' => 'recent',
			]);

			$dialog->addRecentItem($item);
		}
	}

	private function fillDepartments(Dialog $dialog, EO_Section_Collection $departments, ?bool $forceDynamic = null)
	{
		$allowDepartment = $this->getAllowOnlyUserDepartment();
		$parents = [];
		$parentIdList = [];
		foreach ($departments as $department)
		{
			$isRootDepartment =
				$department->getDepthLevel() === 1 || $department->getId() === self::getRootDepartmentId()
			;
			$hideRootDepartment = $isRootDepartment && !$this->options['allowSelectRootDepartment'];
			$parentIdList[$department->getId()] = $department->getIblockSectionId();

			$availableInRecentTab = true;
			if ($this->getSelectMode() === self::MODE_USERS_ONLY || $hideRootDepartment)
			{
				$availableInRecentTab = false;
			}

			if (
				is_array($allowDepartment)
				&& !$this->isAllowDepartment(
					$department->getId(),
					$department->getIblockSectionId(),
					$allowDepartment,
					$parentIdList
				)
			)
			{
				continue;
			}

			$subdepartmentsCount = null;
			if ($this->options['shouldCountSubdepartments'])
			{
				$subdepartmentsCount = $this->getSubdepartmentsCount($department->getId());
			}

			$usersCount = null;
			if ($this->options['shouldCountUsers'])
			{
				$usersOptions = $this->getUserOptions($dialog);
				$usersCount = UserProvider::getUsers(['departmentId' => $department->getId()] + $usersOptions)->count();
			}

			$item = new Item([
				'id' => $department->getId(),
				'entityId' => 'department',
				'title' => $department->getName(),
				'tabs' => 'departments',
				'searchable' => $availableInRecentTab,
				'availableInRecentTab' => $availableInRecentTab,
				'customData' => [
					'subdepartmentsCount' => $subdepartmentsCount,
					'usersCount' => $usersCount,
				],
				'nodeOptions' => [
					'dynamic' => is_bool($forceDynamic) ? $forceDynamic : true,
					'open' => $isRootDepartment,
				],
			]);

			if ($this->getSelectMode() === self::MODE_DEPARTMENTS_ONLY && !$hideRootDepartment)
			{
				$item->addChild(new Item([
					'id' => $department->getId(),
					'title' => $department->getName(),
					'entityId' => 'department',
					'nodeOptions' => [
						'title' => Loc::getMessage('INTRANET_ENTITY_SELECTOR_SELECT_DEPARTMENT'),
						//'avatar' => '/bitrix/js/intranet/entity-selector/src/images/department-option.svg',
						'renderMode' => 'override',
					],
				]));
			}
			elseif ($this->getSelectMode() === self::MODE_USERS_AND_DEPARTMENTS)
			{
				if (!$hideRootDepartment)
				{
					$item->addChild(new Item([
						'id' => $department->getId(),
						'title' => $department->getName(),
						'entityId' => 'department',
						'nodeOptions' => [
							'title' => Loc::getMessage('INTRANET_ENTITY_SELECTOR_ALL_EMPLOYEES_SUBDIVISIONS'),
							'avatar' => '/bitrix/js/intranet/entity-selector/src/images/department-option.svg',
							'renderMode' => 'override',
						],
					]));
				}

				if ($this->options['allowFlatDepartments'])
				{
					$item->addChild(new Item([
						'id' => $department->getId() . ':F',
						'entityId' => 'department',
						'title' =>
							$department->getName() . ' '
							. Loc::getMessage('INTRANET_ENTITY_SELECTOR_ONLY_EMPLOYEES')
						,
						'nodeOptions' => [
							'title' => Loc::getMessage('INTRANET_ENTITY_SELECTOR_ONLY_DEPARTMENT_EMPLOYEES'),
							'avatar' => '/bitrix/js/intranet/entity-selector/src/images/department-option.svg',
							'renderMode' => 'override',
						],
					]));
				}
			}

			$parentItem = $parents[$department->getIblockSectionId()] ?? null;
			if ($parentItem)
			{
				$parentItem->addChild($item);
			}
			else
			{
				$dialog->addItem($item);
			}

			$parents[$department->getId()] = $item;
		}
	}

	private function isAllowDepartment($departmentId, $parentId, $allowDepartment, $parentList): bool
	{
		$result = false;
		if (
			in_array($departmentId, $allowDepartment, true)
			|| in_array($parentId, $allowDepartment, true)
		)
		{
			$result = true;
		}
		elseif ($parentList[$parentId] > 0)
		{
			$departmentId = $parentList[$parentId];
			$parentId = 0;
			if ($parentList[$departmentId] > 0)
			{
				$parentId = $parentList[$departmentId];
			}

			$result = $this->isAllowDepartment($departmentId, $parentId, $allowDepartment, $parentList);
		}

		return $result;
	}

	public function getChildren(Item $parentItem, Dialog $dialog): void
	{
		$departmentId = (int)$parentItem->getId();
		$departmentRepository = ServiceContainer::getInstance()->departmentRepository();
		$departmentHead = $departmentRepository->getDepartmentHead($departmentId);

		$departments = $this->getStructure(['parentId' => $departmentId]);
		$this->fillDepartments($dialog, $departments);
		if ($this->getSelectMode() === self::MODE_DEPARTMENTS_ONLY)
		{
			return;
		}

		$headId = 0;

		if ($departmentHead)
		{
			$headId = $departmentHead->getId();
		}

		$userOptions = $this->getUserOptions($dialog);

		$items = UserProvider::makeItems(
			UserProvider::getUsers(['departmentId' => $departmentId] + $userOptions),
			$userOptions,
		);

		usort(
			$items,
			function(Item $a, Item $b) use ($headId) {
				if ($a->getId() === $headId)
				{
					return -1;
				}
				else if ($b->getId() === $headId)
				{
					return 1;
				}

				$lastNameA = $a->getCustomData()->get('lastName');
				$lastNameB = $b->getCustomData()->get('lastName');

				if (!empty($lastNameA) && !empty($lastNameB))
				{
					return $lastNameA > $lastNameB ? 1 : -1;
				}
				else if (empty($lastNameA) && !empty($lastNameB))
				{
					return 1;
				}
				else if (!empty($lastNameA) && empty($lastNameB))
				{
					return -1;
				}

				return $a->getTitle() > $b->getTitle() ? 1 : -1;
			}
		);

		if ($headId > 0)
		{
			foreach ($items as $item)
			{
				if ($item->getId() === $headId)
				{
					$item->getNodeOptions()->set('caption', Loc::getMessage('INTRANET_ENTITY_SELECTOR_MANAGER'));
					break;
				}
			}
		}

		$dialog->addItems($items);
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		if ($this->getSelectMode() === self::MODE_USERS_ONLY)
		{
			return;
		}

		$limit = $this->getLimit();

		// Try to select all departments
		$departments = $this->getStructure([
			'searchQuery'=> $searchQuery->getQuery(),
			'limit' => $limit,
		]);

		$limitExceeded = $limit <= $departments->count();
		if ($limitExceeded)
		{
			$searchQuery->setCacheable(false);
		}

		foreach ($departments as $department)
		{
			$isRootDepartment = $department->getDepthLevel() === 1;
			$hideRootDepartment = $isRootDepartment && !$this->options['allowSelectRootDepartment'];
			if ($hideRootDepartment)
			{
				continue;
			}

			$dialog->addItem(new Item([
				'id' => $department->getId(),
				'entityId' => 'department',
				'title' => $department->getName(),
			]));

			if ($this->getSelectMode() === self::MODE_USERS_AND_DEPARTMENTS && $this->options['allowFlatDepartments'])
			{
				$dialog->addItem(new Item([
					'id' => $department->getId().':F',
					'entityId' => 'department',
					'title' => $department->getName() . ' ' . Loc::getMessage('INTRANET_ENTITY_SELECTOR_ONLY_EMPLOYEES'),
				]));
			}
		}
	}

	public function getStructure(array $options = []): EO_Section_Collection
	{
		$structureIBlockId = self::getStructureIBlockId();
		if ($structureIBlockId <= 0)
		{
			return new EO_Section_Collection();
		}

		$filter = [
			'=IBLOCK_ID' => $structureIBlockId,
			'=ACTIVE' => 'Y',
		];

		if (!empty($options['searchQuery']) && is_string($options['searchQuery']))
		{
			$filter['?NAME'] = $options['searchQuery'];
		}

		if (!empty($options['parentId']) && is_int($options['parentId']))
		{
			$filter['=IBLOCK_SECTION_ID'] = $options['parentId'];
		}

		$limit = isset($options['limit']) && is_int($options['limit']) ? $options['limit'] : 100;

		if ($this->getOptions()['hideChatBotDepartment'])
		{
			$filter['!=XML_ID'] = 'im_bot';
		}

		$rootDepartment = null;
		$rootDepartmentId = self::getRootDepartmentId();
		if ($rootDepartmentId > 0)
		{
			$rootDepartment = SectionTable::getList([
				'select' => ['ID', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'DEPTH_LEVEL'],
				'filter' => [
					'=ID' => $rootDepartmentId,
					'=IBLOCK_ID' => $structureIBlockId,
					'=ACTIVE' => 'Y',
				],
			])->fetchObject();

			if ($rootDepartment)
			{
				$filter['>=LEFT_MARGIN'] = $rootDepartment->getLeftMargin();
				$filter['<=RIGHT_MARGIN'] = $rootDepartment->getRightMargin();
			}
		}

		if (!empty($options['depthLevel']) && is_int($options['depthLevel']))
		{
			if ($rootDepartment)
			{
				$filter['<=DEPTH_LEVEL'] = $options['depthLevel'] + $rootDepartment->getDepthLevel();
			}
			else
			{
				$filter['<=DEPTH_LEVEL'] = $options['depthLevel'];
			}
		}

		return SectionTable::getList([
				'select' => ['ID', 'NAME', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID', 'LEFT_MARGIN', 'RIGHT_MARGIN'],
				'filter' => $filter,
				'order' => ['LEFT_MARGIN' => 'asc'],
				'limit' => $limit,
		])->fetchCollection();
	}

	protected function getSubdepartmentsCount($departmentId, ?int $limit = null)
	{
		return SectionTable::getList([
			'select' => [],
			'filter' => ['=IBLOCK_SECTION_ID' => $departmentId],
			'order' => [],
			'limit' => $limit ?? $this->getLimit(),
		])->fetchCollection()->count();
	}

	public static function getStructureIBlockId(): int
	{
		return (int)Option::get('intranet', 'iblock_structure', 0);
	}

	public static function getRootDepartmentId(): int
	{
		static $rootDepartmentId = null;

		if ($rootDepartmentId === null)
		{
			$rootDepartmentId = (int)Option::get('main', 'wizard_departament', false, SITE_ID);
		}

		return $rootDepartmentId;
	}

	public function getDepartments(array $ids, array $options = []): array
	{
		$structureIBlockId = self::getStructureIBlockId();
		if ($structureIBlockId <= 0)
		{
			return [];
		}

		$query = SectionTable::query();
		$query->setSelect(['ID', 'NAME', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID', 'LEFT_MARGIN', 'RIGHT_MARGIN']);
		$query->setOrder(['LEFT_MARGIN' => 'asc']);
		$query->where('IBLOCK_ID', $structureIBlockId);

		// ids = [1, '1:F', '10:F', '5:F', 5]
		$integerIds = array_map('intval', $ids);
		$idMap = array_combine($ids, $integerIds);
		$query->whereIn('ID', array_unique($integerIds));

		$active = isset($options['active']) ? $options['active'] : true;
		if (is_bool($active))
		{
			$query->where('ACTIVE', $active ? 'Y' : 'N');
		}

		$items = [];
		$departments = $query->exec()->fetchCollection();
		if ($departments->count() > 0)
		{
			foreach ($idMap as $id => $integerId)
			{
				$department = $departments->getByPrimary($integerId);
				if (!$department)
				{
					continue;
				}

				$isFlatDepartment = is_string($id) && $id[-1] === 'F';
				$availableInRecentTab = false;
				if ($isFlatDepartment)
				{
					$availableInRecentTab =
						$this->getSelectMode() === self::MODE_USERS_AND_DEPARTMENTS &&
						$this->options['allowFlatDepartments'] === true
					;
				}
				else
				{
					$availableInRecentTab = $this->getSelectMode() !== self::MODE_USERS_ONLY;
					if ($department->getDepthLevel() === 1 && !$this->options['allowSelectRootDepartment'])
					{
						$availableInRecentTab = false;
					}
				}

				$titlePostfix =
					$isFlatDepartment
						? ' ' . Loc::getMessage('INTRANET_ENTITY_SELECTOR_ONLY_EMPLOYEES')
						: ''
				;

				$items[] = new Item([
					'id' => $id,
					'entityId' => 'department',
					'title' => $department->getName() . $titlePostfix,
					'availableInRecentTab' => $availableInRecentTab,
					'searchable' => $availableInRecentTab,
				]);
			}
		}

		return $items;
	}
}