<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die;
}

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Access\Model\UserModel;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Repository\UserRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Item\UserCollection;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass('bitrix:sign.base');

final class SignB2eSignersList extends SignBaseComponent
{
	private const DEFAULT_GRID_ID = 'SIGN_B2E_SIGNERS_LIST_GRID';
	private const DEFAULT_FILTER_ID = 'SIGN_B2E_SIGNERS_LIST_FILTER';
	private const DEFAULT_NAVIGATION_KEY = 'sign-b2e-signers-list';
	private const DEFAULT_PAGE_SIZE = 10;
	private readonly \Bitrix\Sign\Service\SignersListService $signersListService;
	private readonly UserRepository $userRepository;
	private UserModel $currentUserAccessModel;
	/** @var array<int|string, string|null> */
	private array $currentUserPermissionValuesCache = [];

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->signersListService = Container::instance()->getSignersListService();
		$this->userRepository = Container::instance()->getUserRepository();
	}

	public function executeComponent(): void
	{
		if (!Storage::instance()->isB2eAvailable())
		{
			showError((string)Loc::getMessage('SIGN_B2E_SIGNERS_LIST_B2E_NOT_ACTIVATED'));

			return;
		}

		$accessController = $this->getAccessController();
		if (!$accessController->check(ActionDictionary::ACTION_B2E_SIGNERS_LIST_READ))
		{
			showError('Access denied');

			return;
		}

		parent::executeComponent();
	}

	public function exec(): void
	{
		$this->setResult('NAVIGATION_KEY', $this->getNavigation()->getId());
		$this->setResult('CURRENT_PAGE', $this->getNavigation()->getCurrentPage());
		$this->setParam('COLUMNS', $this->getGridColumnList());
		$this->setParam('FILTER_FIELDS', $this->getFilterFieldList());
		$this->setParam('FILTER_PRESETS', []);
		$this->setParam('GRID_ID', self::DEFAULT_GRID_ID);
		$this->setParam('FILTER_ID', self::DEFAULT_FILTER_ID);
		$this->setResult('TOTAL_COUNT', $this->getNavigation()->getRecordCount());
		$this->setResult('SIGNERS_LISTS', $this->getGridData());
		$this->setResult('PAGE_SIZE', $this->getNavigation()->getPageSize());
		$this->setResult('PAGE_NAVIGATION', $this->getNavigation());
		$this->setResult('CAN_ADD_LIST', $this->canCreateList());
	}

	private function getNavigation(): PageNavigation
	{
		if (!isset($this->arResult['PAGE_NAVIGATION']))
		{
			return $this->prepareNavigation();
		}

		return $this->arResult['PAGE_NAVIGATION'];
	}

	private function getGridData(): array
	{
		$currentPageElements = $this->getCurrentPageElements();

		if ($currentPageElements->isEmpty() && $this->getNavigation()->getCurrentPage() > 1)
		{
			$this->decrementCurrentPage();
			$currentPageElements = $this->getCurrentPageElements();
		}

		return $this->mapElementsToGridData($currentPageElements);
	}

	private function getCurrentPageElements(): \Bitrix\Sign\Item\SignersListCollection
	{
		return $this->signersListService->listWithFilter(
            $this->getFilterQuery(),
            $this->getNavigation()->getPageSize(),
            $this->getNavigation()->getOffset(),
        );
	}

	private function decrementCurrentPage(): void
	{
		$this->getNavigation()->setCurrentPage($this->getNavigation()->getCurrentPage() - 1);
	}

	private function mapElementsToGridData(\Bitrix\Sign\Item\SignersListCollection $lists): array
	{
		$responsibleIds = [];
		foreach ($lists as $list)
		{
			$responsibleId = $this->getResponsibleByList($list);
			$responsibleIds[$responsibleId] = $responsibleId;
		}
		$responsibleUsers = $this->userRepository->getByIds($responsibleIds);

		return array_map(
			fn(\Bitrix\Sign\Item\SignersList $list): array => $this->mapListToGridData(
				$list,
				$responsibleUsers,
			),
			$lists->toArray(),
		);
	}

	private function mapListToGridData(
		\Bitrix\Sign\Item\SignersList $list,
		UserCollection $responsibleUsers,
	): array
	{
		$responsibleData = $responsibleUsers->getByIdMap($this->getResponsibleByList($list) ?? 0);
		$personalPhoto = $responsibleData?->personalPhotoId;
		$responsibleAvatarPath = $personalPhoto
			? htmlspecialcharsbx(CFile::GetPath($personalPhoto))
			: ''
		;
		$responsibleName = $responsibleData?->name ?? '';
		$responsibleLastName = $responsibleData?->lastName ?? '';
		$responsibleFullName = "$responsibleName $responsibleLastName";
		$responsibleId = $responsibleData?->id ?? 0;

		return [
			'id' => $list->id,
			'columns' => [
				'ID' => $list->id,
				'TITLE' => $list->title,
				'DATE_MODIFY' => $list->dateModify ?? $list->dateCreate ?? null,
				'RESPONSIBLE' => [
					'ID' => $responsibleId,
					'FULL_NAME' => $responsibleFullName,
					'AVATAR_PATH' => $responsibleAvatarPath,
				],
			],
			'access' => [
				'canEdit' => $this->canCurrentUserEditList($list),
				'canDelete' => $this->canCurrentUserDeleteList($list),
				'canCopy' => $this->canCurrentUserCopyList($list),
			],
		];
	}

	private function getGridColumnList(): array
	{
		return [
			[
				'id' => 'ID',
				'name' => (string)Loc::getMessage('SIGN_B2E_SIGNERS_LIST_COLUMN_ID'),
				'default' => false,
			],
			[
				'id' => 'TITLE',
				'name' => (string)Loc::getMessage('SIGN_B2E_SIGNERS_LIST_COLUMN_TITLE'),
				'default' => true,
			],
			[
				'id' => 'RESPONSIBLE',
				'name' => (string)Loc::getMessage('SIGN_B2E_SIGNERS_LIST_COLUMN_RESPONSIBLE'),
				'default' => true,
			],
			[
				'id' => 'DATE_MODIFY',
				'name' => (string)Loc::getMessage('SIGN_B2E_SIGNERS_LIST_COLUMN_DATE_MODIFY'),
				'default' => true,
			],
		];
	}

	private function getFilterFieldList(): array
	{
		return [
			[
				'id' => 'TITLE',
				'name' => (string)Loc::getMessage('SIGN_B2E_SIGNERS_LIST_FILTER_FIELD_TITLE'),
				'default' => true,
			],
			[
				'id' => 'DATE_MODIFY',
				'name' => (string)Loc::getMessage('SIGN_B2E_SIGNERS_LIST_FILTER_FIELD_DATE_MODIFY'),
				'type' => 'date',
				'default' => true,
			],
		];
	}

	private function prepareNavigation(): PageNavigation
	{
		$pageSize = (int)$this->getParam('PAGE_SIZE');
		$pageSize = $pageSize > 0 ? $pageSize : self::DEFAULT_PAGE_SIZE;
		$navigationKey = $this->getParam('NAVIGATION_KEY') ?? self::DEFAULT_NAVIGATION_KEY;

		$pageNavigation = new \Bitrix\Sign\Util\UI\PageNavigation($navigationKey);
		$pageNavigation->setPageSize($pageSize)
			->setRecordCount($this->signersListService->countListsWithFilter($this->getFilterQuery()))
			->allowAllRecords(false)
			->initFromUri()
		;

		return $pageNavigation;
	}

	private function getFilterQuery(): ConditionTree
	{
		$filterData = $this->getFilterValues();

		$queryFilter = $this->prepareQueryFilterByGridFilterData($filterData);

		return $this->prepareQueryFilterByListPermission($queryFilter);
	}

	private function getFilterValues(): array
	{
		$options = new Options(self::DEFAULT_FILTER_ID);

		return $options->getFilter($this->getFilterFieldList());
	}

	private function prepareQueryFilterByGridFilterData(array $filterData): ConditionTree
	{
		$filter = Bitrix\Main\ORM\Query\Query::filter();

		$dateModifyFrom = $filterData['DATE_MODIFY_from'] ?? null;
		if ($dateModifyFrom && \Bitrix\Main\Type\DateTime::isCorrect($dateModifyFrom))
		{
			$filter->where('DATE_MODIFY', '>=', new \Bitrix\Main\Type\DateTime($dateModifyFrom));
		}

		$dateModifyTo = $filterData['DATE_MODIFY_to'] ?? null;
		if ($dateModifyTo && \Bitrix\Main\Type\DateTime::isCorrect($dateModifyTo))
		{
			$filter->where('DATE_MODIFY', '<=', new \Bitrix\Main\Type\DateTime($dateModifyTo));
		}

		$title = $filterData['TITLE'] ?? $filterData['FIND'] ?? null;
		if ($title)
		{
			$filter->whereLike('TITLE', '%' . $title . '%');
		}

		return $filter;
	}

	private function canCreateList(): bool
	{
		return $this
			->getAccessController()
			->check(ActionDictionary::ACTION_B2E_SIGNERS_LIST_ADD)
		;
	}

	private function getCurrentUserAccessModel(): UserModel
	{
		$currentUserId = CurrentUser::get()->getId();

		if ($currentUserId < 1)
		{
			throw new \Bitrix\Main\SystemException('Current user is not authorized');
		}

		$this->currentUserAccessModel ??= UserModel::createFromId($currentUserId);

		return $this->currentUserAccessModel;
	}

	private function prepareQueryFilterByListPermission(ConditionTree $queryFilter): ConditionTree
	{
		if (!Loader::includeModule('crm'))
		{
			return $queryFilter;
		}

		$user = $this->getCurrentUserAccessModel();
		if ($user->isAdmin())
		{
			return $queryFilter;
		}

		$listReadPermission = $this->getValueForPermissionFromCurrentUser(SignPermissionDictionary::SIGN_B2E_SIGNERS_LIST_READ);

		return match ($listReadPermission)
		{
			CCrmPerms::PERM_ALL => $queryFilter,
			CCrmPerms::PERM_SELF => $queryFilter->where('CREATED_BY_ID', $user->getUserId()),
			CCrmPerms::PERM_DEPARTMENT => $queryFilter->whereIn('CREATED_BY_ID', $user->getUserDepartmentMembers()),
			CCrmPerms::PERM_SUBDEPARTMENT => $queryFilter->whereIn('CREATED_BY_ID', $user->getUserDepartmentMembers(true)),
			default => $queryFilter->where('CREATED_BY_ID', 0),
		};
	}

	private function canCurrentUserEditList(\Bitrix\Sign\Item\SignersList $list): bool
	{
		return $this->hasCurrentUserAccessToPermissionByItemWithOwnerId(
			$list->getOwnerId(),
			SignPermissionDictionary::SIGN_B2E_SIGNERS_LIST_EDIT,
		);
	}

	private function canCurrentUserDeleteList(\Bitrix\Sign\Item\SignersList $list): bool
	{
		return $this->hasCurrentUserAccessToPermissionByItemWithOwnerId(
			$list->getOwnerId(),
			SignPermissionDictionary::SIGN_B2E_SIGNERS_LIST_DELETE,
		);
	}

	private function canCurrentUserCopyList(\Bitrix\Sign\Item\SignersList $list): bool
	{
		if (!$this->canCreateList())
		{
			return false;
		}

		return $this->hasCurrentUserAccessToPermissionByItemWithOwnerId(
			$list->getOwnerId(),
			SignPermissionDictionary::SIGN_B2E_SIGNERS_LIST_READ,
		);
	}

	private function hasCurrentUserAccessToPermissionByItemWithOwnerId(int $itemOwnerId, int|string $permissionId): bool
	{
		$userAccessModel = $this->getCurrentUserAccessModel();
		if ($userAccessModel->isAdmin())
		{
			return true;
		}

		if (!Loader::includeModule('crm'))
		{
			return false;
		}


		$permission = $this->getValueForPermissionFromCurrentUser($permissionId);

		return match ($permission) {
			CCrmPerms::PERM_ALL => true,
			CCrmPerms::PERM_SELF => $itemOwnerId === $userAccessModel->getUserId(),
			CCrmPerms::PERM_DEPARTMENT => in_array($itemOwnerId, $userAccessModel->getUserDepartmentMembers(), true),
			CCrmPerms::PERM_SUBDEPARTMENT => in_array($itemOwnerId, $userAccessModel->getUserDepartmentMembers(true), true),
			default => false,
		};
	}

	private function getValueForPermissionFromCurrentUser(string|int $permissionId): ?string
	{
		$permissionService = new RolePermissionService();

		$this->currentUserPermissionValuesCache[$permissionId] ??= $permissionService->getValueForPermission(
			$this->getCurrentUserAccessModel()->getRoles(),
			$permissionId,
		);

		return $this->currentUserPermissionValuesCache[$permissionId];
	}

	private function getResponsibleByList(\Bitrix\Sign\Item\SignersList $list): ?int
	{
		return $list->modifiedById ?? $list->createdById;
	}
}
