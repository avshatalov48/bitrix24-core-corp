<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Grid;
use Bitrix\Main\Grid\Cell;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Context;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Filter\Condition;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UserTable;
use Bitrix\Main\UI\Filter;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService;
use Bitrix\Sign\Callback\Handler;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Connector\Crm\MyCompany;
use Bitrix\Sign\Document\Entity\SmartB2e;
use Bitrix\Sign\Integration\CRM\Entity;
use Bitrix\Sign\Item;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type;
use Bitrix\Sign\Type\EntityFileCode;
use Bitrix\Sign\Type\Member\EntityType;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;
use Bitrix\Sign\Ui;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass('bitrix:sign.base');
\Bitrix\Main\Loader::includeModule('sign');

class SignUserDocumentListComponent extends SignBaseComponent implements Controllerable
{
	private const PERSONAL_TYPE = 'personal';
	private const PERSONAL_DOCUMENT_GRID_ID = 'DOCUMENT_GRID_ID_PERSONAL';
	private const PERSONAL_DOCUMENT_FILTER_ID = 'DOCUMENT_FILTER_ID_PERSONAL';

	private const DOCUMENT_TYPE = 'document';
	private const DOCUMENT_DOCUMENT_GRID_ID = 'DOCUMENT_GRID_ID_DOCUMENT';
	private const DOCUMENT_DOCUMENT_FILTER_ID = 'DOCUMENT_FILTER_ID_DOCUMENT';

	private const SAFE_TYPE = 'safe';
	private const SAFE_DOCUMENT_GRID_ID = 'DOCUMENT_GRID_ID_SAFE';
	private const SAFE_DOCUMENT_FILTER_ID = 'DOCUMENT_FILTER_ID_SAFE';

	private const CURRENT_TYPE = 'current';
	private const CURRENT_DOCUMENT_GRID_ID = 'DOCUMENT_GRID_ID_CURRENT';
	private const CURRENT_DOCUMENT_FILTER_ID = 'DOCUMENT_FILTER_ID_CURRENT';

	private const DOCUMENT_CREATOR_ICON_SIZE = ['width' => 42, 'height' => 42];
	private const PATH_TO_USER_PROFILE_TEMPLATE = '/company/personal/user/#USER_ID#/';
	private const URL_TO_USER_PROFILE_TEMPLATE_USER_KEY = 'USER_ID';

	private const DEFAULT_GRID_ID = 'DEFAULT_GRID_ID';
	private const DEFAULT_PAGE_SIZE = 10;
	private const DEFAULT_NAV_KEY = "sign-document-list-nav";
	private const DEFAULT_FILTER_ID = 'DEFAULT_FILTER_ID';
	private const DEFAULT_RESULT_FILE_DOWNLOAD_URL_TEMPLATE_HASH_KEY = 'MEMBER_HASH';
	private const DEFAULT_RESULT_FILE_DOWNLOAD_URL_TEMPLATE =
		'/bitrix/services/main/ajax.php?action=sign.document.getFileForSrc&documentHash=#MEMBER_HASH#'
	;
	private const PREFIX_FOR_SHORT_FILTER_MEMBER_STATUS = 'member_status__';
	private const BANNER_OPTION_CLOSE_PERSONAL = 'show_b2e_personal_grid_banner';
	private const BANNER_OPTION_CLOSE_SAFE = 'show_b2e_safe_grid_banner';
	private const BANNER_OPTION_CLOSE_CURRENT = 'show_b2e_current_grid_banner';

	private const ROLE_RELEVANCE = [
		Role::REVIEWER => 100,
		Role::EDITOR => 200,
		Role::ASSIGNEE => 300,
		Role::SIGNER => 400,
	];

	private const STATUS_RELEVANCE = [
		MemberStatus::READY => 100,
		MemberStatus::STOPPABLE_READY => 100,
		MemberStatus::PROCESSING => 100,
		MemberStatus::WAIT => 200,
		MemberStatus::REFUSED => 300,
		MemberStatus::STOPPED => 400,
		MemberStatus::DONE => 500,
	];

	protected ErrorCollection $errors;
	protected ?string $type;
	protected ?int $entityId;
	protected array $companyData = [];

	private array $usersDataForGridById = [];

	private MemberRepository $memberRepository;
	private DocumentRepository $documentRepository;

	private \Bitrix\Sign\Service\Sign\DocumentService $documentService;
	private \Bitrix\Sign\Service\Sign\MemberService $memberService;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errors = new ErrorCollection();
		$this->memberRepository = Container::instance()->getMemberRepository();
		$this->documentRepository = Container::instance()->getDocumentRepository();
		$this->documentService = Container::instance()->getDocumentService();
		$this->memberService = Container::instance()->getMemberService();
	}

	public function executeComponent(): void
	{
		if (!Storage::instance()->isB2eAvailable())
		{
			showError('access denied');
			return;
		}

		parent::executeComponent();
	}

	public function preparePermissionFilterForQuery(ConditionTree $filter): void
	{
		if (CurrentUser::get()->isAdmin())
		{
			return;
		}

		$permission = (new RolePermissionService)->getValueForPermission(
			$this->accessController->getUser()->getRoles(),
			SignPermissionDictionary::SIGN_B2E_MY_SAFE_DOCUMENTS
		);

		$condition = match ($permission)
		{
			CCrmPerms::PERM_SUBDEPARTMENT => $this->preparePermissionFilterForMySafe(
				$this->accessController->getUser()->getUserDepartmentMembers(true)
			),
			CCrmPerms::PERM_DEPARTMENT => $this->preparePermissionFilterForMySafe(
				$this->accessController->getUser()->getUserDepartmentMembers()
			),
			CCrmPerms::PERM_SELF => $this->preparePermissionFilterForMySafe(
				[$this->accessController->getUser()->getUserId()]
			),
			CCrmPerms::PERM_ALL => Query::filter(),
			default => Query::filter()->where('CREATED_BY_ID', null),
		};

		$filter->where($condition);
	}

	private function preparePermissionFilterForMySafe(array $userIdList): ConditionTree
	{
		return Query::filter()
			->logic(ConditionTree::LOGIC_OR)
			->where(
				Query::filter()
					->where('DOCUMENT.INITIATED_BY_TYPE', '=', Type\Document\InitiatedByType::EMPLOYEE->toInt())
					->whereIn('DOCUMENT.REPRESENTATIVE_ID', $userIdList)
					->where('ENTITY_TYPE', '=', EntityType::COMPANY),
			)
			->whereIn('CREATED_BY_ID', $userIdList)
		;
	}

	public function filterFilteredItems(array $items): array
	{
		return $this->getIntersectFields($this->getAvailableFilterItems(), $items);
	}

	public function filterGridColumnsWithDefault(array $items): array
	{
		return $this->getIntersectFields($this->getDefaultGridColumnsList(), $items);
	}

	public function getIntersectFields(array $defaultItems, array $items): array
	{
		return array_replace_recursive(
			array_intersect_key($defaultItems, $items),
			$items
		);
	}

	protected function exec(): void
	{
		if (!$this->errors->isEmpty())
		{
			$this->showErrors();

			return;
		}

		$this->prepareResult();
	}

	public function getAction(): array
	{
		return match ($this->arParams['COMPONENT_TYPE'])
		{
			self::SAFE_TYPE => [
				AccessController::RULE_AND => [
					ActionDictionary::ACTION_B2E_MY_SAFE,
				],
			],
			default => parent::getAction(),
		};
	}

	protected function getCallbackAction(): array
	{
		return match ($this->arParams['COMPONENT_TYPE'])
		{
			self::PERSONAL_TYPE => [
				fn() => (int)CurrentUser::get()->getId() !== (int)$this->arParams['ENTITY_ID'],
			],
			self::DOCUMENT_TYPE => [
				function() {
					$document = $this->documentRepository->getById((int)$this->arParams['ENTITY_ID']);

					if (!$document)
					{
						return true;
					}

					return !$this->accessController->checkByItem(
						ActionDictionary::ACTION_B2E_DOCUMENT_READ,
						$document,
					);
				},
			],
			default => parent::getCallbackAction(),
		};
	}

	private function prepareResult(): void
	{
		$this->prepareComponentParams();
		$this->prepareNavigationParams();
		$this->prepareFilters();
		$this->prepareGridParams();
		$this->prepareBannerParams();
		$this->prepareData();
		$this->prepareStub();
		$this->prepareEvents();
	}

	private function prepareComponentParams(): void
	{
		$this->type = mb_strtolower($this->arParams['COMPONENT_TYPE']);
		if (
			!in_array($this->type, [
				self::PERSONAL_TYPE,
				self::DOCUMENT_TYPE,
				self::SAFE_TYPE,
				self::CURRENT_TYPE,
			], true)
		)
		{
			$this->addError('Wrong component type', 'You use wrong component type');
		}

		$this->arResult['GRID_TYPE'] = $this->type;

		$this->entityId = isset($this->arParams['ENTITY_ID']) ? (int)$this->arParams['ENTITY_ID'] : null;
		if ($this->entityId === 0 && $this->type !== self::SAFE_TYPE)
		{
			$this->addError('Wrong entity id', 'You use wrong entity id');
		}

		$this->arResult['IS_SHOW_TITLE'] = true;
		$this->arResult['TITLE'] = match ($this->type)
		{
			self::PERSONAL_TYPE => Loc::getMessage('SIGN_PERSONAL_DOCUMENT_LIST_TITLE'),
			self::DOCUMENT_TYPE => Loc::getMessage('SIGN_DOCUMENT_DOCUMENT_LIST_TITLE'),
			self::SAFE_TYPE => Loc::getMessage('SIGN_SAFE_DOCUMENT_LIST_TITLE'),
			self::CURRENT_TYPE => Loc::getMessage('SIGN_CURRENT_DOCUMENT_LIST_TITLE'),
		};

		$this->arResult['IS_SHOW_RESULT_STATUS_BUTTON'] = false;

		if ($this->type === self::DOCUMENT_TYPE)
		{
			$this->arResult['IS_SHOW_RESULT_STATUS_BUTTON'] = true;
		}

		$this->arResult['IS_SHOW_TOOLBAR_FILTER'] = true;
		if ($this->type === self::CURRENT_TYPE)
		{
			$this->arResult['IS_SHOW_TOOLBAR_FILTER'] = false;
		}
	}

	private function prepareGridParams(): void
	{
		$this->arResult['GRID_ID'] = match ($this->type) {
			self::DOCUMENT_TYPE => self::DOCUMENT_DOCUMENT_GRID_ID,
			self::PERSONAL_TYPE => self::PERSONAL_DOCUMENT_GRID_ID,
			self::SAFE_TYPE => self::SAFE_DOCUMENT_GRID_ID,
			self::CURRENT_TYPE => self::CURRENT_DOCUMENT_GRID_ID,
			default => self::DEFAULT_GRID_ID
		};
		$this->arResult['COLUMNS'] = $this->getGridColumns();

		$this->arResult['DOCUMENT_RESULT_FILE_DOWNLOAD_URL_TEMPLATE'] ??= self::DEFAULT_RESULT_FILE_DOWNLOAD_URL_TEMPLATE;
		$this->arResult['DOCUMENT_RESULT_FILE_DOWNLOAD_URL_TEMPLATE_HASH_KEY'] ??=
			self::DEFAULT_RESULT_FILE_DOWNLOAD_URL_TEMPLATE_HASH_KEY;
	}

	private function getDefaultGridColumnsList(): array
	{
		return [
			'id' => [
				'id' => 'ID',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_COLUMN_NAME_ID'),
				'editable' => false,
				'type' => Grid\Types::GRID_INT,
				'gridSort' => 0,
			],
			'title' => [
				'id' => 'TITLE',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_COLUMN_NAME_TITLE'),
				'default' => false,
				'editable' => false,
				'gridSort' => 0,
				'resizeable' => true,
				'width' => 600,
			],
			'member' => [
				'id' => 'MEMBER',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_COLUMN_NAME_MEMBER'),
				'default' => false,
				'editable' => false,
				'gridSort' => 0,
			],
			'role' => [
				'id' => 'ROLE',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_COLUMN_NAME_ROLE'),
				'default' => false,
				'editable' => false,
				'gridSort' => 0,
			],
			'download' => [
				'id' => 'DOWNLOAD_DOCUMENT',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_COLUMN_NAME_DOWNLOAD_ACTION_MSGVER_1'),
				'default' => false,
				'editable' => false,
				'gridSort' => 0,
			],
			'initiator' => [
				'id' => 'INITIATOR',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_COLUMN_NAME_INITIATOR'),
				'default' => false,
				'editable' => false,
				'gridSort' => 0,
			],
			'dateSign' => [
				'id' => 'DATE_SIGN',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_COLUMN_NAME_SIGN_DATE'),
				'default' => false,
				'editable' => false,
				'gridSort' => 0,
			],
			'createdBy' => [
				'id' => 'CREATED_BY',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_COLUMN_NAME_CREATED_BY'),
				'default' => false,
				'editable' => false,
				'gridSort' => 0,
			],
			'memberStatus' => [
				'id' => 'MEMBER_STATUS',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_COLUMN_NAME_STATUS'),
				'default' => false,
				'editable' => false,
				'gridSort' => 0,
			],
			'action' => [
				'id' => 'ACTION',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_COLUMN_NAME_ACTION'),
				'default' => false,
				'editable' => false,
				'gridSort' => 0,
			],
			'dateCreate' => [
				'id' => 'DATE_CREATE',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_COLUMN_NAME_CREATE_DATE'),
				'default' => false,
				'editable' => false,
				'gridSort' => 0,
			],
		];
	}

	private function getGridColumnsListForPersonal(): array
	{
		return $this->filterGridColumnsWithDefault([
			'id' => [],
			'title' => [
				'default' => true,
			],
			'download' => [
				'default' => true,
				'gridSort' => 800,
			],
			'initiator' => [],
			'dateSign' => [
				'default' => true,
			],
			'createdBy' => [],
		]);
	}

	private function getGridColumnsListForDocument(): array
	{
		return $this->filterGridColumnsWithDefault([
			'id' => [],
			'member' => [
				'default' => true,
			],
			'role' => [
				'default' => true,
			],
			'memberStatus' => [
				'default' => true,
			],
			'action' => [
				'default' => true,
				'gridSort' => 800,
			],
			'dateSign' => [
				'gridSort' => 200
			],
		]);
	}

	private function getGridColumnsListForSafe(): array
	{
		return $this->filterGridColumnsWithDefault([
			'id' => [],
			'title' => [
				'default' => true,
//				'width' => 400,
			],
			'role' => [
				'default' => true,
			],
			'member' => [
				'default' => true,
			],
			'download' => [
				'default' => true,
				'gridSort' => 800,
			],
			'initiator' => [],
			'dateSign' => [
				'default' => true,
			],
			'createdBy' => [],
		]);
	}

	private function getGridColumns(): array
	{
		if (isset($this->arResult['COLUMNS']))
		{
			return $this->arResult['COLUMNS'];
		}

		$gridColumns = match ($this->type) {
			self::PERSONAL_TYPE => $this->getGridColumnsListForPersonal(),
			self::DOCUMENT_TYPE => $this->getGridColumnsListForDocument(),
			self::SAFE_TYPE => $this->getGridColumnsListForSafe(),
			self::CURRENT_TYPE => $this->getGridColumnsListForCurrent(),
		};

		uasort(
			$gridColumns,
			static fn(array $columnA, array $columnB) => $columnA['gridSort'] <=> $columnB['gridSort']
		);

		return $gridColumns;
	}

	private function getGridOptions(): Grid\Options
	{
		return new Grid\Options($this->arResult["GRID_ID"]);
	}

	private function getPersonalMemberCollection(array $requestFilter): MemberCollection
	{
		return $this->memberRepository->listSignersByUserIdIsDone(
			$this->entityId,
			$this->getFilterForQuery($requestFilter),
			$this->getLimitForQuery(),
			$this->getOffsetForQuery()
		);
	}

	private function getDocumentMemberCollection(array $requestFilter): MemberCollection
	{
		$filter = $this->getFilterForQuery($requestFilter);

		if (isset($requestFilter['ENTITY_ID']) && is_array($requestFilter['ENTITY_ID']))
		{
			$filter->where(
				\Bitrix\Main\ORM\Query\Query::filter()
					->logic('or')
					->where(
						\Bitrix\Main\ORM\Query\Query::filter()
							->logic('and')
							->where('ENTITY_TYPE', '=', EntityType::USER)
							->whereIn('ENTITY_ID', $this->prepareCollectionIds($requestFilter['ENTITY_ID']))
					)
					->where(
						\Bitrix\Main\ORM\Query\Query::filter()
							->logic('and')
							->where('ENTITY_TYPE', '=', EntityType::COMPANY)
							->where('ROLE', '=', $this->memberRepository->convertRoleToInt(Role::ASSIGNEE))
					)
			);
		}

		$document = $this->documentRepository->getById($this->entityId);

		if (isset($requestFilter['MEMBER_STATUS']) && is_array($requestFilter['MEMBER_STATUS']))
		{
			if (
				(
					in_array(MemberStatus::READY, $requestFilter['MEMBER_STATUS'], true)
					|| in_array(MemberStatus::PROCESSING, $requestFilter['MEMBER_STATUS'], true)
				)
				&& $document->status !== Type\DocumentStatus::STOPPED
			)
			{
				$requestFilter['MEMBER_STATUS'][] = MemberStatus::STOPPABLE_READY;
			}

			$signed = array_filter($requestFilter['MEMBER_STATUS'], static fn($item) => in_array($item, MemberStatus::getAll(), true));

			if (
				$document->status === Type\DocumentStatus::STOPPED
				&& in_array(MemberStatus::STOPPED, $requestFilter['MEMBER_STATUS'], true)
			)
			{
				$signed = [...$signed, MemberStatus::STOPPED, MemberStatus::WAIT, MemberStatus::STOPPABLE_READY, MemberStatus::PROCESSING];
			}

			$roleCondition = array_filter(
				$filter->getConditions(),
				static fn(ConditionTree|Condition $item) => $item instanceof Condition && $item->getColumn() === 'ROLE'
			);
			if (empty($roleCondition))
			{
				$filter->where('ROLE', '=', $this->memberRepository->convertRoleToInt(Role::SIGNER));
			}
			$filter->whereIn('SIGNED', array_unique($signed));
		}

		$limit = $this->getLimitForQuery() + 1;
		$page = $this->getNavigation()->getCurrentPage() - 1;
		$offset = $page * $this->getNavigation()->getPageSize();

		$memberCollection = $this->memberRepository->listB2eMemberByDocumentId(
			(int)$this->entityId,
			$filter,
			self::ROLE_RELEVANCE,
			self::STATUS_RELEVANCE,
			$limit,
			$offset,
		);

		$this->arResult['COUNTER_ITEMS'] = $this->getCounterItems($document, $filter);
		$items = $memberCollection->toArray();

		array_walk($items, static function (Item\Member $member) use ($document) {
			if ($member->role === Role::ASSIGNEE)
			{
				$member->entityId = $document->representativeId;
				$member->entityType = EntityType::USER;
			}
		});

		if (isset($requestFilter['ENTITY_ID']) && is_array($requestFilter['ENTITY_ID']))
		{
			$items = array_filter($items, static function (Item\Member $member) use ($requestFilter) {
				return in_array(
					$member->entityId,
					array_map(static fn($id) => (int)$id, $requestFilter['ENTITY_ID']),
					true
				);
			});
		}
		// get N+1 to understand there are any more elements in the database after that,
		// and we will display N elements.
		if ($memberCollection->count() === $limit)
		{
			array_pop($items);
		}
		$resultCollection = new MemberCollection(...$items);
		$resultCollection->setQueryTotal($memberCollection->count() + $this->getNavigation()->getOffset());
		$this->arResult['SHOW_TOTAL_COUNTER'] = false;

		return $resultCollection;
	}

	private function getSafeMemberCollection(array $requestFilter): MemberCollection
	{
		return $this->memberRepository->listB2eMembersWithResultFilesForMySafe(
			$this->getFilterForQuery($requestFilter),
			$this->getLimitForQuery(),
			$this->getOffsetForQuery()
		);
	}

	private function getFilterForQuery(array $requestFilter): ConditionTree
	{
		$filter = Bitrix\Main\ORM\Query\Query::filter();

		if (isset($requestFilter['DATE_SIGN_from']) && $requestFilter['DATE_SIGN_from'])
		{
			$filter->where('DATE_SIGN', '>=', new \Bitrix\Main\Type\DateTime($requestFilter['DATE_SIGN_from']));
		}
		if (isset($requestFilter['DATE_SIGN_to']) && $requestFilter['DATE_SIGN_to'])
		{
			$filter->where('DATE_SIGN', '<=', new \Bitrix\Main\Type\DateTime($requestFilter['DATE_SIGN_to']));
		}
		if (isset($requestFilter['DATE_CREATE_from']) && $requestFilter['DATE_CREATE_from'])
		{
			$filter->where('DATE_CREATE', '>=', new \Bitrix\Main\Type\DateTime($requestFilter['DATE_CREATE_from']));
		}
		if (isset($requestFilter['DATE_CREATE_to']) && $requestFilter['DATE_CREATE_to'])
		{
			$filter->where('DATE_CREATE', '<=', new \Bitrix\Main\Type\DateTime($requestFilter['DATE_CREATE_to']));
		}

		if (isset($requestFilter['SIGNED']) && is_array($requestFilter['SIGNED']))
		{
			$filter->whereIn('SIGNED', array_filter($requestFilter['SIGNED'], static fn($item) => in_array($item, MemberStatus::getAll(), true)));
		}

		if (
			isset($requestFilter['MEMBER_STATUS'])
			&& is_array($requestFilter['MEMBER_STATUS'])
			&& in_array($this->type, [self::PERSONAL_TYPE, self::SAFE_TYPE], true)
		)
		{
			if (
				in_array(MemberStatus::READY, $requestFilter['MEMBER_STATUS'], true)
				|| in_array(MemberStatus::PROCESSING, $requestFilter['MEMBER_STATUS'], true )
			)
			{
				$requestFilter['MEMBER_STATUS'][] = MemberStatus::STOPPABLE_READY;
			}

			$filter->whereIn('SIGNED', array_filter($requestFilter['MEMBER_STATUS'], static fn($item) => in_array($item, MemberStatus::getAll(), true)));
			$filter->where('ROLE', '=',  $this->memberRepository->convertRoleToInt(Role::SIGNER));
		}

		if (isset($requestFilter['DOCUMENT_NAME']) && $requestFilter['DOCUMENT_NAME'] !== '')
		{
			$filter->where((\Bitrix\Main\ORM\Query\Query::filter())
				->logic('OR')
				->whereLike('DOCUMENT.TITLE', '%' . (string)$requestFilter['DOCUMENT_NAME'] . '%')
				->whereLike('DOCUMENT.EXTERNAL_ID', '%' . (string)$requestFilter['DOCUMENT_NAME'] . '%')
			);
		}

		if (isset($requestFilter['REPRESENTATIVE']) && is_array($requestFilter['REPRESENTATIVE']))
		{
			$filter->whereIn('DOCUMENT.REPRESENTATIVE_ID', $this->prepareCollectionIds($requestFilter['REPRESENTATIVE']));
		}

		if (isset($requestFilter['CREATED_BY_ID']) && is_array($requestFilter['CREATED_BY_ID']))
		{
			$filter->whereIn('DOCUMENT.CREATED_BY_ID', $this->prepareCollectionIds($requestFilter['CREATED_BY_ID']));
		}

		if (
			isset($requestFilter['ENTITY_ID'])
			&& is_array($requestFilter['ENTITY_ID'])
			&& in_array($this->type, [self::PERSONAL_TYPE, self::SAFE_TYPE], true)
		)
		{
			$entityIds = $this->prepareCollectionIds($requestFilter['ENTITY_ID']);
			$filter->where((\Bitrix\Main\ORM\Query\Query::filter())
				->logic('or')
				// filter signers
				->where(\Bitrix\Main\ORM\Query\Query::filter()
					->whereIn('ENTITY_ID', $entityIds)
					->where('ENTITY_TYPE', '=', EntityType::USER)
				)
				// filter assignee
				->where(\Bitrix\Main\ORM\Query\Query::filter()
					->where('ROLE', '=', $this->memberRepository->convertRoleToInt(Role::ASSIGNEE))
					->whereIn("DOCUMENT.REPRESENTATIVE_ID", $entityIds)
				)
			);
		}

		if (isset($requestFilter['ROLE']) && is_array($requestFilter['ROLE']))
		{
			$roles = array_map(
				fn(string $role) => $this->memberRepository->convertRoleToInt($role),
				array_filter($requestFilter['ROLE'], static fn ($role) => in_array($role, Role::getAll(), true))
			);
			$filter->whereIn('ROLE', $roles);
		}

		if ($this->type === self::SAFE_TYPE)
		{
			$this->preparePermissionFilterForQuery($filter);
		}

		if (isset($requestFilter['COMPANY']))
		{
			$this->prepareFilterForCompanyFilterField($filter, $requestFilter);
		}

		return $filter;
	}

	private function getOffsetForQuery(): int
	{
		return (int)$this->getNavigation()->getOffset();
	}

	private function getLimitForQuery(): int
	{
		return (int)$this->getNavigation()->getLimit();
	}

	private function prepareData(): void
	{
		$filterOptions = $this->getFilterOptions();
		$requestFilter = $this->getRequestFilters($filterOptions);
		$memberCollection = match ($this->type) {
			self::PERSONAL_TYPE => $this->getPersonalMemberCollection($requestFilter),
			self::DOCUMENT_TYPE => $this->getDocumentMemberCollection($requestFilter),
			self::SAFE_TYPE => $this->getSafeMemberCollection($requestFilter),
			self::CURRENT_TYPE => $this->getCurrentMemberCollection(),
		};

		$documentCollection = $this->getDocumentsByMembers($memberCollection);

		$this->arResult['DOCUMENTS'] = $this->getDocumentsDataForGrid($memberCollection, $documentCollection);
		$this->arResult['TOTAL_COUNT'] = $memberCollection->getQueryTotal() ?? 0;
		$navigationObject = $this->getNavigation();
		$navigationObject->setRecordCount($this->arResult['TOTAL_COUNT']);
	}

	private function getDocumentsDataForGrid(MemberCollection $memberCollection, Item\DocumentCollection $documentCollection): array
	{
		$result = [];

		$documents = $documentCollection->getArrayByIds();

		$currentCulture = Context::getCurrent()->getCulture();
		/** @var Item\Member $member */
		foreach ($memberCollection as $member)
		{
			$memberData = [];
			$document = $documents[$member->documentId];

			$memberData['ID'] = $member->id;

			if (isset($this->arResult['COLUMNS']['title']))
			{
				$withLink = !in_array($this->type, [self::PERSONAL_TYPE, self::CURRENT_TYPE], true);
				$title = $this->type === self::PERSONAL_TYPE
					? $this->documentService->getComposedTitleByDocument($document)
					: $this->documentService->getTitleWithAutoNumber($document)
				;
				$memberData['TITLE_INFO'] = $this->getTitleInfo($title, $document->entityId, $withLink);
			}

			if (isset($this->arResult['COLUMNS']['createdBy']))
			{
				try
				{
					$memberData['CREATED_BY'] = $this->getUsersInfo($document->createdById);
				}
				catch (ObjectPropertyException|ArgumentException|SystemException $e)
				{
				}
			}

			if (isset($this->arResult['COLUMNS']['download']))
			{
				$resultFileInfo = $this->getResultFileInfo($member);
				$memberData['RESULT_FILE_INFO'] = $resultFileInfo;
			}

			if (isset($this->arResult['COLUMNS']['action']))
			{
				$isCurrentUserEqualsMember = CurrentUser::get()->getId() !== null
					&& Container::instance()
						->getMemberService()
						->isUserLinksWithMember($member, $document, CurrentUser::get()->getId())
				;
				if ($isCurrentUserEqualsMember && $this->isSigningLinkAvailable($member, $document))
				{
					$memberData['ACTION'] = [
						'TYPE' => 'link',
						'DATA' => [
							'role' => $member->role,
							'memberId' => $member->id,
						],
					];
				}
				elseif (
					$member->status === MemberStatus::DONE
					|| (
						$document->status === Type\DocumentStatus::STOPPED
						&& $member->status === Type\MemberStatus::READY
						&& $member->role === Role::ASSIGNEE
					)
				)
				{
					$resultFileInfo = $this->getResultFileInfo($member);
					if ($resultFileInfo !== null)
					{
						$memberData['ACTION'] = [
							'TYPE' => 'file',
							'DATA' => $resultFileInfo,
						];
					}
				}
			}

			if (isset($this->arResult['COLUMNS']['initiator']))
			{
				$memberData['INITIATOR'] = $this->getSignWithInfo($document);
			}

			if (isset($this->arResult['COLUMNS']['role']))
			{
				$memberData['ROLE'] = $this->getRoleInfoCaption($member->role);
			}

			if (isset($this->arResult['COLUMNS']['member']))
			{
				$memberData['MEMBER_INFO'] = $this->getMemberInfo($member);
			}

			if (isset($this->arResult['COLUMNS']['memberStatus']))
			{
				$memberData['MEMBER_STATUS'] = self::calculateStatus($member, $document);
			}

			if (isset($this->arResult['COLUMNS']['dateSign']) && $this->isMemberSignDateAllowedToShow($member))
			{
				$memberData['DATE_SIGN_INFO'] = $this->getDateSignWithInfo($member->dateSigned, $currentCulture);
			}

			if (isset($this->arResult['COLUMNS']['dateCreate']))
			{
				$memberData['DATE_CREATE_INFO'] = $this->getDateSignWithInfo($member->dateCreated, $currentCulture);
			}

			$result[] = $memberData;
		}

		return $result;
	}

	private function getTitleInfo(string $title, int $id, bool $withLink): array
	{
		$result = [
			'TEXT' => $title,
		];

		if ($withLink)
		{
			$result ['DOCUMENT_LINK'] = Entity::getDetailPageUri(SmartB2e::getEntityTypeId(), $id);
		}

		return $result;
	}

	private function getUsersInfo(int $userId): array
	{
		if (isset($this->usersDataForGridById[$userId]))
		{
			return $this->usersDataForGridById[$userId];
		}

		$userNameTemplate = empty($this->arParams['USER_NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(["#NOBR#", "#/NOBR#"], ["", ""], $this->arParams["USER_NAME_TEMPLATE"])
		;

		$userTableQueryResult = UserTable::getRowById($userId);
		$userFullName = $userTableQueryResult === null
			? ''
			: CUser::FormatName(
				$userNameTemplate,
				[
					'LOGIN' => $userTableQueryResult['LOGIN'],
					'NAME' => $userTableQueryResult['NAME'],
					'LAST_NAME' => $userTableQueryResult['LAST_NAME'],
					'SECOND_NAME' => $userTableQueryResult['SECOND_NAME'],
				],
				true,
				false,
			);

		$userIconFileTmp = $userTableQueryResult === null
			? null
			: CFile::ResizeImageGet(
				$userTableQueryResult['PERSONAL_PHOTO'],
				self::DOCUMENT_CREATOR_ICON_SIZE,
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true,
			);
		$userIcon = ($userIconFileTmp && isset($userIconFileTmp['src'])) ? $userIconFileTmp['src'] : null;

		$userPageUrlLink = CComponentEngine::makePathFromTemplate(
			self::PATH_TO_USER_PROFILE_TEMPLATE,
			[self::URL_TO_USER_PROFILE_TEMPLATE_USER_KEY => $userId],
		);

		$this->usersDataForGridById[$userId] = [
			'ID' => $userId,
			'FULL_NAME' => $userFullName,
			'ICON' => $userIcon,
			'LINK' => $userPageUrlLink,
		];

		return $this->usersDataForGridById[$userId];
	}

	private function getResultFileInfo(Item\Member $member): ?array
	{
		$data = $this->getDownloadResultFileUrl(
			\Bitrix\Sign\Type\EntityType::MEMBER,
			$member->id,
			EntityFileCode::SIGNED,
		);

		$dataPrinted = $this->getDownloadResultFileUrl(
			\Bitrix\Sign\Type\EntityType::MEMBER,
			$member->id,
			EntityFileCode::PRINT_VERSION,
		);

		if ($data === null)
		{
			return null;
		}

		return [
			'EXTENSION' => $data['ext'],
			'DOWNLOAD_URL' => $data['url'],
			'DOWNLOAD_URL_PRINTED' => $dataPrinted['url'] ?? null,
		];
	}

	private function getDownloadResultFileUrl(int $entityTypeId, int $entityId, int $fileCode): ?array
	{
		$operation = new Bitrix\Sign\Operation\GetSignedB2eFileUrl($entityTypeId, $entityId, $fileCode);
		$result = $operation->launch();
		if (!$result->isSuccess() || !$operation->ready)
		{
			return null;
		}

		$data = $result->getData();

		if (!isset($data['url']))
		{
			return null;
		}

		return $data;
	}

	private function getDateSignWithInfo(?\Bitrix\Main\Type\DateTime $dateSign, Context\Culture $culture): ?array
	{
		if ($dateSign === null)
		{
			return null;
		}

		$signDateTime = $dateSign;

		$signDateTime = clone $signDateTime;

		$signDateTime->setDefaultTimeZone();
		$signDateTimeZone = $signDateTime->getTimezone();

		$dateFormat = $this->getDefaultDateFormat($culture);

		return $this->getTextWithDetailInfoFormat(
			FormatDate($dateFormat, $signDateTime->getTimestamp()),
			$this->getDateTimeWithTimezoneOffsetRepresentation($signDateTime, $signDateTimeZone)
		);
	}

	private function getDefaultDateFormat(Context\Culture $culture): string
	{
		return $culture->getMediumDateFormat();
	}

	private function getTextWithDetailInfoFormat(string $text, string $detailInfo): array
	{
		return [
			'TEXT' => $text,
			'DETAIL' => $detailInfo,
		];
	}

	private function getDateTimeWithTimezoneOffsetRepresentation(
		\Bitrix\Main\Type\DateTime $dateTime,
		DateTimeZone $timezone
	): string
	{
		$dateTime = clone $dateTime;
		$dateTime->setTimeZone($timezone);

		return $dateTime . ' (UTC' . $dateTime->format('P') . ')';
	}

	private function getFilterId(): string
	{
		return match ($this->type)
		{
			self::PERSONAL_TYPE => self::PERSONAL_DOCUMENT_FILTER_ID,
			self::DOCUMENT_TYPE => self::DOCUMENT_DOCUMENT_FILTER_ID,
			self::SAFE_TYPE => self::SAFE_DOCUMENT_FILTER_ID,
			self::CURRENT_TYPE => self::CURRENT_DOCUMENT_FILTER_ID,
			default => self::DEFAULT_FILTER_ID,
		};
	}

	private function prepareFilters(): void
	{
		$this->arResult['FILTER_ID'] = $this->getFilterId();
		$this->arResult['FILTER'] = $this->getFilterItems();
		$this->arResult['FILTER_PRESETS'] = $this->getFilterPresets();
	}

	private function getFilterItems(): array
	{
		return match ($this->type)
		{
			self::DOCUMENT_TYPE => $this->getFilterItemsForDocument(),
			self::PERSONAL_TYPE => $this->getFilterItemsForPersonal(),
			self::SAFE_TYPE => $this->getFilterItemsForSafe(),
			self::CURRENT_TYPE => $this->getFilterItemsForCurrent(),
		};
	}

	private function getAvailableFilterItems(): array
	{
		return [
			'id' => [
				'id' => 'ID',
				'name' => 'ID',
				'default' => false,
			],
			'dateSign' => [
				'id' => 'DATE_SIGN',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_TITLE_FILTER_DATE_SIGN_NAME'),
				'type' => 'date',
				'default' => false,
			],
			'dateCreate' => [
				'id' => 'DATE_CREATE',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_TITLE_FILTER_DATE_CREATE_NAME'),
				'type' => 'date',
				'default' => false,
			],
			'employee' => [
				'id' => 'ENTITY_ID',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_TITLE_FILTER_LABEL_EMPLOYEE'),
				'type' => 'entity_selector',
				'partial' => true,
				'default' => false,
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'height' => 240,
						'context' => 'filter',
						'entities' => [
							[
								'id' => 'user',
								'options' => [
									'inviteEmployeeLink' => false,
								],
							],
						],
					],
				],
			],
			'role' => [
				'id' => 'ROLE',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_TITLE_FILTER_LABEL_ROLE'),
				'type' => 'list',
				'default' => false,
				'params' => [
					'multiple' => 'Y',
				],
				'items' => array_combine(
					Role::getAll(),
					array_map(fn($role): string => $this->getRoleInfoCaption($role), Role::getAll())
				),
			],
			'memberStatus' => [
				'id' => 'MEMBER_STATUS',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_TITLE_FILTER_LABEL_MEMBER_STATUS'),
				'type' => 'list',
				'default' => false,
				'params' => [
					'multiple' => 'Y',
				],
				'items' => $this->getMemberAllStatusesCaption(),
			],
			'representative' => [
				'id' => 'REPRESENTATIVE',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_TITLE_FILTER_LABEL_REPRESENTATIVE'),
				'type' => 'entity_selector',
				'partial' => true,
				'default' => false,
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'height' => 240,
						'context' => 'filter',
						'entities' => [
							[
								'id' => 'user',
								'options' => [
									'inviteEmployeeLink' => false,
								],
							],
						],
					],
				],
			],
			'company' => [
				'id' => 'COMPANY',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_TITLE_FILTER_LABEL_COMPANY'),
				'type' => 'entity_selector',
				'partial' => true,
				'default' => false,
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'height' => 240,
						'dropdownMode' => false,
						'entities' => [
							[
								'id' => 'sign-mycompany',
								'dynamicLoad' => true,
								'dynamicSearch' => true,
								'options' => [
									'enableMyCompanyOnly' => true,
								],
							],
						],
					],
				],
			],
			'document' => [
				'id' => 'DOCUMENT_NAME',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_TITLE_FILTER_LABEL_DOCUMENT_NAME'),
				'type' => 'string',
			],
			'initiator' => [
				'id' => 'CREATED_BY_ID',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_TITLE_FILTER_LABEL_INITIATOR'),
				'type' => 'entity_selector',
				'partial' => true,
				'default' => false,
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'height' => 240,
						'context' => 'filter',
						'entities' => [
							[
								'id' => 'user',
								'options' => [
									'inviteEmployeeLink' => false,
								],
							],
						],
					],
				],
			],
			'action' => [
				'id' => 'ACTION',
				'name' => Loc::getMessage('SIGN_DOCUMENT_LIST_TITLE_FILTER_LABEL_ACTION'),
				'type' => 'string',
				'default' => false,
			]
		];
	}

	private function getFilterItemsForDocument(): array
	{
		$items = [
			'dateSign' => ['default' => true,],
			'role' => ['default' => true,],
			'memberStatus' => ['default' => true,],
			'employee' => ['default' => true,],
		];

		return $this->filterFilteredItems($items);
	}

	private function getFilterItemsForPersonal(): array
	{
		$items = [
			'dateSign' => ['default' => true],
			'representative' => ['default' => true],
			'company' => ['default' => true],
			'document' => ['default' => true],
			'initiator' => [],
		];

		return $this->filterFilteredItems($items);
	}

	private function getFilterItemsForSafe(): array
	{
		$items = [
			'dateSign' => ['default' => true],
			'dateCreate' => [],
			'employee' => ['default' => true],
			'representative' => ['default' => true],
			'document' => ['default' => true],
			'company' => ['default' => true],
			'initiator' => [],
		];

		return $this->filterFilteredItems($items);
	}

	private function getFilterPresets(): array
	{
		return [];
	}

	private function getUseDefaultStubParamValue(): bool
	{
		$filterOptions = $this->getFilterOptions();
		if (trim($filterOptions->getSearchString()))
		{
			return true;
		}

		if (count($this->getRequestFilters($filterOptions)) > 0)
		{
			return true;
		}

		return false;
	}

	private function getFilterOptions(): Filter\Options
	{
		return new Filter\Options($this->arResult['FILTER_ID']);
	}

	private function getRequestFilters(Filter\Options $filterOptions): array
	{
		return $filterOptions->getFilter($this->arResult['FILTER']);
	}

	public function prepareStub(): void
	{
		$stubTitle = match ($this->type) {
			self::PERSONAL_TYPE,
			self::SAFE_TYPE,
			self::DOCUMENT_TYPE => Loc::getMessage('SIGN_DOCUMENT_LIST_TITLE_STUB_NO_DOCUMENT'),
			self::CURRENT_TYPE => Loc::getMessage('SIGN_DOCUMENT_LIST_TITLE_STUB_NO_DOCUMENT_FOR_SIGN')
		};
		$this->setResult('STUB', ['title' => $stubTitle]);

		$this->setResult(
			'USE_DEFAULT_STUB',
			$this->getParam('USE_DEFAULT_STUB') ?? $this->getUseDefaultStubParamValue()
		);
	}

	private function prepareNavigationParams(): void
	{
		$this->arResult['PAGE_SIZE'] =
			isset($this->arParams['PAGE_SIZE']) && (int)$this->arParams['PAGE_SIZE'] > 0
				? (int)$this->arParams['PAGE_SIZE']
				: self::DEFAULT_PAGE_SIZE
		;
		$this->arResult['NAVIGATION_KEY'] = $this->arParams['NAVIGATION_KEY'] ?? self::DEFAULT_NAV_KEY;
	}

	private function getNavigation()
	{
		if (!isset($this->arResult['NAVIGATION_OBJECT']))
		{
			return $this->prepareNavigation();
		}

		return $this->arResult['NAVIGATION_OBJECT'];
	}

	private function prepareNavigation(): PageNavigation
	{
		$pageNavigation = new PageNavigation($this->arResult['NAVIGATION_KEY']);
		$pageNavigation
			->setPageSize($this->arResult['PAGE_SIZE'])
			->allowAllRecords(false)
			->initFromUri()
		;
		$this->arResult['NAVIGATION_OBJECT'] = $pageNavigation;

		return $pageNavigation;
	}

	private function getDocumentsByMembers(MemberCollection $members): Item\DocumentCollection
	{
		$ids = [];
		foreach ($members as $member)
		{
			$ids[] = $member->documentId;
		}

		return $this->documentRepository->listByIds(array_unique($ids));
	}

	private function getSignWithInfo(Item\Document $document): array
	{
		$userInfo = $this->getUsersInfo($document->representativeId);
		$userInfo['COMPANY_NAME'] = $this->getCompanyName($document);

		return $userInfo;
	}

	private function getCounterItems(Item\Document $document, ?ConditionTree $filter): array
	{
		$counters = $this->memberRepository->getMembersCountersByDocument($document, null);

		return [
			[
				'id' => self::PREFIX_FOR_SHORT_FILTER_MEMBER_STATUS . MemberStatus::DONE,
				'title' => Loc::getMessage('SIGN_DOCUMENT_LIST_GRID_COUNTER_SIGNED'),
				'value' => $counters['SUCCESS_MEMBERS_COUNTER'],
				'color' =>'THEME',
				'isRestricted' => false,
			],
			[
				'id' => self::PREFIX_FOR_SHORT_FILTER_MEMBER_STATUS . MemberStatus::READY,
				'title' => Loc::getMessage('SIGN_DOCUMENT_LIST_GRID_COUNTER_READY'),
				'value' => $counters['READY_MEMBERS_COUNTER'],
				'color' => $counters['READY_MEMBERS_COUNTER'] === 0 ? 'THEME' : 'WARNING',
				'isRestricted' => false,
			],
			[
				'id' => self::PREFIX_FOR_SHORT_FILTER_MEMBER_STATUS . MemberStatus::REFUSED,
				'title' => Loc::getMessage('SIGN_DOCUMENT_LIST_GRID_COUNTER_REFUSED'),
				'value' => $counters['REFUSED_MEMBERS_COUNTER'],
				'color' => 'THEME',
				'isRestricted' => false,
			],
		];
	}

	private function getMemberInfo(Item\Member $member): array
	{
		return $this->getUsersInfo($this->memberService->getUserIdForMember($member) ?? 0);
	}

	private function getCompanyName(Item\Document $document): ?string
	{
		if ($document->entityType === \Bitrix\Sign\Type\Document\EntityType::SMART_B2E)
		{
			if (!isset($this->companyData[$document->entityId]))
			{
				$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(CCrmOwnerType::SmartB2eDocument);
				$companyId = $factory->getItem($document->entityId)?->getMycompanyId();
				if ($companyId === null)
				{
					$this->companyData[$document->entityId] = null;

					return null;
				}

				$companyName = MyCompany::getById($companyId)?->name;

				$this->companyData[$document->entityId] = $companyName;
			}

			return $this->companyData[$document->entityId];
		}

		return null;
	}


	/**
	 * @param array $data
	 *
	 * @return array<int>
	 */
	private function prepareCollectionIds(array $data): array
	{
		return array_map(
			static fn($item): int => (int)$item,
			array_filter($data, static fn ($item) => (int)$item > 0)
		);
	}

	private function prepareEvents(): void
	{
		if (Bitrix\Main\Loader::includeModule('pull'))
		{
			CPullWatch::Add(
				CurrentUser::get()->getId(),
				Handler::FILTER_COUNTER_TAG);
		}
	}

	private function getRoleInfoCaption(string $role): string
	{
		return match ($role) {
			Role::ASSIGNEE => Loc::getMessage('SIGN_DOCUMENT_LIST_ROLE_CAPTION_ASSIGNEE'),
			Role::EDITOR => Loc::getMessage('SIGN_DOCUMENT_LIST_ROLE_CAPTION_EDITOR_MSG_1'),
			Role::REVIEWER => Loc::getMessage('SIGN_DOCUMENT_LIST_ROLE_CAPTION_REVIEWER'),
			Role::SIGNER => Loc::getMessage('SIGN_DOCUMENT_LIST_ROLE_CAPTION_SIGNER'),
			default => Loc::getMessage('SIGN_DOCUMENT_LIST_ROLE_CAPTION_UNDEFINED')
		};
	}

	private function getMemberAllStatusesCaption(): array
	{
		return [
			MemberStatus::DONE => Loc::getMessage('SIGN_DOCUMENT_LIST_GRID_COUNTER_SIGNED'),
			MemberStatus::READY => Loc::getMessage('SIGN_DOCUMENT_LIST_GRID_COUNTER_READY'),
			MemberStatus::REFUSED => Loc::getMessage('SIGN_DOCUMENT_LIST_GRID_COUNTER_REFUSED'),
			MemberStatus::WAIT => Loc::getMessage('SIGN_DOCUMENT_LIST_GRID_COUNTER_WAIT'),
			MemberStatus::STOPPED => Loc::getMessage('SIGN_DOCUMENT_LIST_GRID_COUNTER_STOPPED'),
		];
	}

	private function prepareBannerParams(): void
	{
		if (!in_array($this->type, [self::PERSONAL_TYPE, self::SAFE_TYPE], true))
		{
			$this->arResult['IS_SHOW_B2E_GRID_BANNER'] = false;

			return;
		}

		$this->arResult['IS_SHOW_B2E_GRID_BANNER'] = true;
		if ($this->type === self::SAFE_TYPE )
		{
			if (CUserOptions::GetOption('sign', self::BANNER_OPTION_CLOSE_SAFE, 'Y', CurrentUser::get()->getId()) !== 'Y')
			{
				$this->arResult['IS_SHOW_B2E_GRID_BANNER'] = false;
				return;
			}
		}
		elseif ($this->type === self::PERSONAL_TYPE)
		{
			if (CUserOptions::GetOption('sign', self::BANNER_OPTION_CLOSE_PERSONAL, 'Y', CurrentUser::get()->getId()) !== 'Y')
			{
				$this->arResult['IS_SHOW_B2E_GRID_BANNER'] = false;
				return;
			}
		}
		$this->arResult['BANNER_TEXT'] = Loc::getMessage('SIGN_DOCUMENT_GRID_BANNER_TEXT_MSGVER_1');
	}

	private function setBannerOptionCloseAction($type): void
	{
		if (!in_array($type, [self::PERSONAL_TYPE, self::SAFE_TYPE], true))
		{
			return;
		}

		$option = match ($type) {
			self::PERSONAL_TYPE => self::BANNER_OPTION_CLOSE_PERSONAL,
			self::SAFE_TYPE => self::BANNER_OPTION_CLOSE_SAFE,
		};

		CUserOptions::SetOption('sign', $option, 'N', false, CurrentUser::get()->getId());
	}

	public function configureActions(): void
	{
		return;
	}

	private function prepareFilterForCompanyFilterField(ConditionTree $filter, array $requestFilter): void
	{
		$prefix = \Bitrix\Sign\Repository\MemberRepository::SIGN_DOCUMENT_LIST_QUERY_REF_FIELD_NAME_COMPANY;
		$filter
			->where($prefix . '.ENTITY_TYPE', \Bitrix\Sign\Type\Member\EntityType::COMPANY)
			->where($prefix . '.ROLE', $this->memberRepository->convertRoleToInt(Role::ASSIGNEE))
			->whereIn( $prefix . '.ENTITY_ID', (array)$requestFilter['COMPANY'])
		;
	}

	private function getFilterItemsForCurrent(): array
	{
		$items = [];
		return $this->filterFilteredItems($items);
	}

	private function getGridColumnsListForCurrent(): array
	{
		return $this->filterGridColumnsWithDefault([
			'id' => [],
			'title' => [
				'default' => true,
				'gridSort' => 100,
			],
			'role' => [
				'default' => true,
				'gridSort' => 300,
			],
			'createdBy' => [
				'default' => true,
				'gridSort' => 200,
			],
			'action' => [
				'default' => true,
				'gridSort' => 800,
			],
		]);
	}

	private function getCurrentMemberCollection(): MemberCollection
	{
		return $this->memberRepository->listB2eMembersWithReadyStatus(
			$this->entityId,
			$this->getLimitForQuery(),
			$this->getOffsetForQuery()
		);
	}

	private static function calculateStatus(Item\Member $member, Item\Document $document): array
	{
		$stageInfo = Ui\Member\Stage::createInstance($member, $document)->getInfo();

		return [
			'TEXT' => $stageInfo['text'],
			'COLOR' => $stageInfo['color'],
			'IDENTIFIER' => 'sign_document_grid_label_id_' . $member->id,
		];
	}

	private function isSigningLinkAvailable(Item\Member $member, Item\Document $document): bool
	{
		if (
			$document->providerCode === Type\ProviderCode::GOS_KEY
			&& $member->role === Role::SIGNER
			&& $member->status === MemberStatus::READY
		)
		{
			return true;
		}

		return MemberStatus::isReadyForSigning($member->status)
			&& !in_array($document->status, Type\DocumentStatus::getFinalStatuses(), true)
			;
	}

	private function isMemberSignDateAllowedToShow(Item\Member $member): bool
	{
		if ($member->role === Role::ASSIGNEE && $member->status !== MemberStatus::DONE)
		{
			return false;
		}

		return true;
	}
}
