<?

namespace Bitrix\Intranet\Integration\UI\EntitySelector;

use Bitrix\Iblock\EO_Section_Collection;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Integration\UI\EntitySelector\UserProvider;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\Tab;

class DepartmentProvider extends BaseProvider
{
	public const MODE_USERS_AND_DEPARTMENTS = 'usersAndDepartments';
	public const MODE_USERS_ONLY = 'usersOnly';
	public const MODE_DEPARTMENTS_ONLY = 'departmentsOnly';

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
			self::MODE_USERS_AND_DEPARTMENTS
		];
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
		$parents = [];
		$departments = self::getStructure();
		foreach ($departments as $department)
		{
			$isRootDepartment = $department->getDepthLevel() === 1;
			$hideRootDepartment = $isRootDepartment && !$this->options['allowSelectRootDepartment'];

			$availableInRecentTab = true;
			if ($this->getSelectMode() === self::MODE_USERS_ONLY || $hideRootDepartment)
			{
				$availableInRecentTab = false;
			}

			$item = new Item(
				[
					'id' => $department->getId(),
					'entityId' => 'department',
					'title' => $department->getName(),
					'tabs' => 'departments',
					'searchable' => $availableInRecentTab,
					'availableInRecentTab' => $availableInRecentTab,
					'nodeOptions' => [
						'dynamic' => $this->getSelectMode() !== self::MODE_DEPARTMENTS_ONLY,
						'open' => $isRootDepartment
					],
				]
			);

			if ($this->getSelectMode() === self::MODE_DEPARTMENTS_ONLY && !$hideRootDepartment)
			{
				$item->addChild(
					new Item(
						[
							'id' => $department->getId(),
							'title' => $department->getName(),
							'entityId' => 'department',
							'nodeOptions' => [
								'title' => Loc::getMessage('INTRANET_ENTITY_SELECTOR_SELECT_DEPARTMENT'),
								//'avatar' => '/bitrix/js/intranet/entity-selector/src/images/department-option.svg',
								'renderMode' => 'override'
							],
						]
					)
				);
			}
			elseif ($this->getSelectMode() === self::MODE_USERS_AND_DEPARTMENTS)
			{
				if (!$hideRootDepartment)
				{
					$item->addChild(
						new Item(
							[
								'id' => $department->getId(),
								'title' => $department->getName(),
								'entityId' => 'department',
								'nodeOptions' => [
									'title' => Loc::getMessage('INTRANET_ENTITY_SELECTOR_ALL_EMPLOYEES_SUBDIVISIONS'),
									'avatar' => '/bitrix/js/intranet/entity-selector/src/images/department-option.svg',
									'renderMode' => 'override'
								],
							]
						)
					);
				}

				if ($this->options['allowFlatDepartments'])
				{
					$item->addChild(
						new Item(
							[
								'id' => $department->getId().':F',
								'entityId' => 'department',
								'title' =>
									$department->getName().' '.
									Loc::getMessage("INTRANET_ENTITY_SELECTOR_ONLY_EMPLOYEES")
								,
								'nodeOptions' => [
									'title' => Loc::getMessage("INTRANET_ENTITY_SELECTOR_ONLY_DEPARTMENT_EMPLOYEES"),
									'avatar' => '/bitrix/js/intranet/entity-selector/src/images/department-option.svg',
									'renderMode' => 'override'
								],
							]
						)
					);

					// It is not attached to the Department Tab
					$dialog->addItem(
						new Item(
							[
								'id' => $department->getId().':F',
								'entityId' => 'department',
								'title' =>
									$department->getName().' '.
									Loc::getMessage("INTRANET_ENTITY_SELECTOR_ONLY_EMPLOYEES")
								,
							]
						)
					);
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

		$icon =
			'data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2219%22%20height%3D%2215%22%20fill%3D%22none%22%'.
			'20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cpath%20fill-rule%3D%22evenodd%22%20clip-rule%3D%22'.
			'evenodd%22%20d%3D%22M13.517%2014.198a28.914%2028.914%200%2001-6.242.667c-2.588%200-5.05-.328-7.275-.'.
			'918.217-1.069.513-2.492.65-3.008.231-.869%201.525-1.514%202.715-2.01.31-.129.498-.232.688-.337a6.53%2'.
			'06.53%200%2001.68-.336c.035-.16.049-.322.042-.485l.527-.061s.07.122-.042-.595c0%200-.592-.15-.62-1.'.
			'293%200%200-.444.144-.47-.548a1.764%201.764%200%2000-.078-.4c-.084-.304-.16-.577.226-.814l-.278-.717S3'.
			'.748.568%205.03.793c-.52-.797%203.865-1.46%204.156.984a7.223%207.223%200%20010%202.222s.655-.073.218%201'.
			'.134c0%200-.24.867-.61.671%200%200%20.06%201.098-.523%201.284%200%200%20.041.584.041.624l.487.072s-.013'.
			'.487.083.54c.444.278.93.488%201.442.624%201.512.372%202.278%201.01%202.278%201.568%200%200%20.622%202.2'.
			'27.915%203.682zm5.345-1.865c-.348.175-.708.343-1.078.503h-3.491c-.026-.364-.366-1.626-.563-2.357-.078-'.
			'.29-.133-.497-.138-.532-.026-.69-.984-1.305-2.05-1.726a1.9%201.9%200%2000.206-.34c.15-.18.345-.32.566-.'.
			'405l.017-.54-1.17-.356s-.301-.136-.331-.136c.034-.083.078-.163.13-.237.022-.058.163-.49.163-.49-.17.212-'.
			'.37.401-.594.56.205-.351.379-.719.52-1.099a6.8%206.8%200%2000.185-1.114c.08-.683.206-1.36.375-2.027.121-'.
			'.333.336-.627.619-.849A2.92%202.92%200%200113.642.7h.06c.509.038.998.207%201.418.49.283.22.497.514.619.'.
			'847.169.667.294%201.344.375%202.027.036.367.101.732.195%201.09.14.386.311.762.51%201.124a2.972%202.972%'.
			'200%2001-.595-.56s.11.392.133.45c.06.088.114.18.16.275-.028%200-.33.136-.33.136l-1.17.356.016.54c.222.08'.
			'5.417.225.566.406.071.173.183.328.328.451.282.096.554.217.813.363.391.212.82.348%201.265.403.448.072.73'.
			'.775.73.775l.037.706.09%201.755z%22%20fill%3D%22%23ACB2B8%22/%3E%3C/svg%3E'
		;

		$dialog->addTab(new Tab([
			'id' => 'departments',
			'title' => Loc::getMessage('INTRANET_ENTITY_SELECTOR_DEPARTMENTS_TAB_TITLE'),
			'icon' => [
				'default' => $icon,
				'selected' => str_replace('ACB2B8', 'fff', $icon),
				//'default' => '/bitrix/js/intranet/entity-selector/src/images/department-tab-icon.svg',
				//'selected' => '/bitrix/js/intranet/entity-selector/src/images/department-tab-icon-selected.svg'
			]
		]));
	}

	public function getChildren(Item $parentItem, Dialog $dialog): void
	{
		$departmentId = (int)$parentItem->getId();
		$structureIBlockId = self::getStructureIBlockId();
		if ($structureIBlockId <= 0)
		{
			return;
		}

		// SectionTable cannot select UF_HEAD
		$department = \CIBlockSection::getList(
			[],
			[
				'ID' => $departmentId,
				'IBLOCK_ID' => $structureIBlockId,
				'ACTIVE' => 'Y'
			],
			false,
			['UF_HEAD']
		)->fetch();

		if (!$department)
		{
			return;
		}

		$headId = 0;
		if (isset($department['UF_HEAD']))
		{
			$headId = is_array($department['UF_HEAD']) ? (int)$department['UF_HEAD'][0] : (int)$department['UF_HEAD'];
		}

		$userOptions = [];
		if (isset($this->getOptions()['userOptions']) && is_array($this->getOptions()['userOptions']))
		{
			$userOptions = $this->getOptions()['userOptions'];
		}
		elseif ($dialog->getEntity('user') && is_array($dialog->getEntity('user')->getOptions()))
		{
			$userOptions = $dialog->getEntity('user')->getOptions();
		}

		$items = UserProvider::makeItems(
			UserProvider::getUsers(['departmentId' => $departmentId] + $userOptions),
			$userOptions
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
					$item->getNodeOptions()->set('caption', Loc::getMessage('INTRANET_ENTITY_SELECTOR_MANAGER'));;
					break;
				}
			}
		}

		$dialog->addItems($items);
	}

	public function getStructure(): EO_Section_Collection
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

		if ($this->getOptions()['hideChatBotDepartment'])
		{
			$filter['!=XML_ID'] = 'im_bot';
		}

		$rootDepartmentId = intval(Option::get('main', 'wizard_departament', false, SITE_ID));
		if ($rootDepartmentId > 0)
		{
			$rootDepartment = SectionTable::getList(
				[
					'select' => ['ID', 'LEFT_MARGIN', 'RIGHT_MARGIN'],
					'filter' => [
						'=ID' => $rootDepartmentId,
						'=IBLOCK_ID' => $structureIBlockId,
						'=ACTIVE' => 'Y'
					]
				]
			)->fetchObject();

			if ($rootDepartment)
			{
				$filter[">=LEFT_MARGIN"] = $rootDepartment->getLeftMargin();
				$filter["<=RIGHT_MARGIN"] = $rootDepartment->getRightMargin();
			}
		}

		return SectionTable::getList([
			'select' => ['ID', 'NAME', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID', 'LEFT_MARGIN', 'RIGHT_MARGIN'],
			'filter' => $filter,
			'order' => ["LEFT_MARGIN" => "asc"]
		])->fetchCollection();
	}

	public static function getStructureIBlockId()
	{
		return (int)Option::get('intranet', 'iblock_structure', 0);
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
		$query->setOrder(["LEFT_MARGIN" => "asc"]);
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

				$titlePostfix = $isFlatDepartment ? ' '.Loc::getMessage("INTRANET_ENTITY_SELECTOR_ONLY_EMPLOYEES") : '';
				$items[] = new Item(
					[
						'id' => $id,
						'entityId' => 'department',
						'title' => $department->getName().$titlePostfix,
						'availableInRecentTab' => $availableInRecentTab,
						'searchable' => $availableInRecentTab
					]
				);
			}
		}

		return $items;
	}
}