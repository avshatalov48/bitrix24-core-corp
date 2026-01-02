<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die;
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Service\Container;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Access\Model\UserModel;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService;
use Bitrix\Sign\Item\UserCollection;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass('bitrix:sign.base');

final class SignB2eSignersEdit extends SignBaseComponent
{
	private const DEFAULT_GRID_ID = 'SIGN_B2E_SIGNERS_LIST_GRID_EDIT';
	private const DEFAULT_FILTER_ID = 'SIGN_B2E_SIGNERS_EDIT_FILTER';
	private const DEFAULT_PAGE_SIZE = 10;
	private const DEFAULT_NAVIGATION_KEY = 'sign-b2e-signers-edit';

	private ?\Bitrix\Sign\Item\SignersList $list = null;
	
	private \Bitrix\Sign\Service\SignersListService $signersListService;
	private \Bitrix\Sign\Repository\UserRepository $userRepository;
	private \Bitrix\Sign\Service\Sign\UrlGeneratorService $urlGenerator;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->signersListService = Container::instance()->getSignersListService();
		$this->userRepository = Container::instance()->getUserRepository();
		$this->urlGenerator = Container::instance()->getUrlGeneratorService();
	}

	public function executeComponent(): void
	{
		if (!\Bitrix\Sign\Config\Storage::instance()->isB2eAvailable())
		{
			showError((string)Loc::getMessage('SIGN_B2E_SIGNERS_LIST_B2E_NOT_ACTIVATED'));

			return;
		}

		$listId = $this->getIntParam('LIST_ID');
		$this->list = $listId !== 0
			? $this->signersListService->getById($listId)
			: null
		;

		if ($this->list === null)
		{
			showError('Access denied or list not found');

			return;
		}

		if (!$this->hasCurrentUserAccessToListForRead($this->list->id))
		{
			showError('Access denied or list not found');

			return;
		}

		parent::executeComponent();
	}

	private function hasCurrentUserAccessToListForRead(int $listId)
	{
		if (
			$this->isListForRefusedSigners($listId)
			&& $this->getAccessController()->checkAll([
				ActionDictionary::ACTION_B2E_SIGNERS_LIST_REFUSED_EDIT,
				ActionDictionary::ACTION_B2E_SIGNERS_LIST_READ,
			])
		)
		{
			return true;
		}

		return $this->hasCurrentUserAccessToPermissionByItemWithOwnerId(
			$this->list->getOwnerId(),
			SignPermissionDictionary::SIGN_B2E_SIGNERS_LIST_READ,
		);
	}

	public function exec(): void
	{
		$this->setResult('SIGNERS', $this->getGridData());
		$this->setParam('COLUMNS', $this->getGridColumnList());
		$this->setParam('NAVIGATION_KEY', $this->getNavigation()->getId());
		$this->setResult('NAVIGATION_KEY', $this->getNavigation()->getId());
		$this->setParam('ADD_NEW_SIGNER_LINK', $this->urlGenerator->makeAddSignerUrl($this->list->id));
		$this->setParam('FILTER_FIELDS', $this->getFilterFieldList());
		$this->setParam('FILTER_PRESETS', $this->getFilterPresets());
		$this->setParam('GRID_ID', self::DEFAULT_GRID_ID);
		$this->setParam('FILTER_ID', self::DEFAULT_FILTER_ID);
		$this->setResult('TOTAL_COUNT', $this->getNavigation()->getRecordCount());
		$this->setResult('PAGE_NAVIGATION', $this->getNavigation());
		$this->setResult('CAN_ADD_SIGNER', $this->canCurrentUserEditList($this->list));
		$this->setResult('CAN_DELETE_SIGNER', $this->canCurrentUserEditList($this->list));
		$this->setResult('LIST_ID', $this->list->id);
		$this->setResult('LIST_TITLE', $this->list->title);
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
				'id' => 'SIGNER',
				'name' => (string)Loc::getMessage('SIGN_B2E_SIGNERS_LIST_COLUMN_SIGNER'),
				'default' => true,
			],
			[
				'id' => 'DATE_CREATE',
				'name' => (string)Loc::getMessage('SIGN_B2E_SIGNERS_LIST_COLUMN_DATE_CREATE'),
				'default' => true,
			],
		];
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

	private function getCurrentPageElements(): \Bitrix\Sign\Item\SignersListUserCollection
	{
		return $this->signersListService->listSignersWithFilter(
			$this->list->id,
			$this->getFilterQuery(),
			$this->getNavigation()->getPageSize(),
			$this->getNavigation()->getOffset(),
		);
	}

	private function decrementCurrentPage(): void
	{
		$this->getNavigation()->setCurrentPage($this->getNavigation()->getCurrentPage() - 1);
	}

	private function mapElementsToGridData(\Bitrix\Sign\Item\SignersListUserCollection $users): array
	{
		$signerIds = [];
		foreach ($users as $user)
		{
			$signerIds[$user->userId] = $user->userId;
		}
		$signers = $this->userRepository->getByIds($signerIds);

		return array_map(
			fn(\Bitrix\Sign\Item\SignersListUser $user): array => $this->mapSignerToGridData(
				$user,
				$signers,
			),
			$users->toArray(),
		);
	}

	private function mapSignerToGridData(
		\Bitrix\Sign\Item\SignersListUser $signer,
		UserCollection $signersData,
	): array
	{
		$signerData = $signersData->getByIdMap($signer->userId ?? 0);
		$personalPhoto = $signerData?->personalPhotoId;
		$signerAvatarPath = $personalPhoto ? htmlspecialcharsbx(CFile::GetPath($personalPhoto)): '';
		$signerFullName = $signerData
			? Container::instance()->getUserService()->getUserName($signerData)
			: "$signerData?->name $signerData?->lastName"
		;
		$signerFullName = htmlspecialcharsbx($signerFullName);

		return [
			'id' => $signer->userId,
			'columns' => [
				'ID' => $signer->listId.'_'.$signer->userId,
				'SIGNER' => [
					'ID' => $signer->userId,
					'FULL_NAME' => $signerFullName,
					'AVATAR_PATH' => $signerAvatarPath,
					'PROFILE_URL' => $this->urlGenerator->makeProfileUrl($signer->userId),
				],
				'DATE_CREATE' => $signer->dateCreate ?? null,
			],
		];
	}

	private function getFilterQuery(): ConditionTree
	{
		$filterData = $this->getFilterValues();
		return $this->prepareQueryFilterByGridFilterData($filterData);
	}

	private function getFilterValues(): array
	{
		return (new Options(self::DEFAULT_FILTER_ID))->getFilter($this->getFilterFieldList());
	}

	private function getFilterFieldList(): array
	{
		return [
			[
				'id' => 'SIGNER',
				'name' => (string)Loc::getMessage('SIGN_B2E_SIGNERS_LIST_FILTER_FIELD_SIGNER'),
				'default' => true,
				'type' => 'entity_selector',
				'partial' => true,
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'height' => 240,
						'entities' => [
							[
								'id' => \Bitrix\Sign\Type\Member\EntityType::USER,
								'dynamicLoad' => true,
								'dynamicSearch' => true,
								'options' => [
									'inviteEmployeeLink' => false,
								],
							],
						],
					],
				],
			],
			[
				'id' => 'DATE_CREATE',
				'name' => (string)Loc::getMessage('SIGN_B2E_SIGNERS_LIST_FILTER_FIELD_DATE_CREATE'),
				'type' => 'date',
				'default' => true,
			],
		];
	}

	private function prepareQueryFilterByGridFilterData(array $filterData): ConditionTree
	{
		$filter = Bitrix\Main\ORM\Query\Query::filter();

		$dateCreateFrom = $filterData['DATE_CREATE_from'] ?? null;
		if ($dateCreateFrom && \Bitrix\Main\Type\DateTime::isCorrect($dateCreateFrom))
		{
			$filter->where('DATE_CREATE', '>=', new \Bitrix\Main\Type\DateTime($dateCreateFrom));
		}

		$dateCreateTo = $filterData['DATE_CREATE_to'] ?? null;
		if ($dateCreateTo && \Bitrix\Main\Type\DateTime::isCorrect($dateCreateTo))
		{
			$filter->where('DATE_CREATE', '<=', new \Bitrix\Main\Type\DateTime($dateCreateTo));
		}

		$signerIds = $this->ensureArray($filterData['SIGNER'] ?? []);
		if ($signerIds)
		{
			$filter->whereIn('USER_ID', $signerIds);
		}

		$find = $filterData['FIND'] ?? null;
		if ($find)
		{
			$words = array_slice(explode(' ', $find), 0, 3);
			foreach ($words as $word)
			{
				$filter->whereLike('USER_SEARCH_NAME', '%' . $word . '%');
			}
		}

		return $filter;
	}

	private function ensureArray($value): array
	{
		return is_array($value) ? $value : [$value];
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

	private function canCurrentUserEditList(\Bitrix\Sign\Item\SignersList $list): bool
	{
		if (
			$this->isListForRefusedSigners($list->id)
			&& $this->getAccessController()->check(ActionDictionary::ACTION_B2E_SIGNERS_LIST_REFUSED_EDIT)
			&& $this->getAccessController()->check(ActionDictionary::ACTION_B2E_SIGNERS_LIST_EDIT)
		)
		{
			return true;
		}

		return $this->hasCurrentUserAccessToPermissionByItemWithOwnerId(
			$list->getOwnerId(),
			SignPermissionDictionary::SIGN_B2E_SIGNERS_LIST_EDIT,
		);
	}

	private function isListForRefusedSigners(?int $listId): bool
	{
		return $listId === \Bitrix\Sign\Config\Storage::instance()->getSignersListRejectedId();
	}

	private function hasCurrentUserAccessToPermissionByItemWithOwnerId(int $itemOwnerId, int|string $permissionId): bool
	{
		$userAccessModel = $this->getCurrentUserAccessModel();
		if ($userAccessModel->isAdmin())
		{
			return true;
		}

		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return false;
		}

		$permission = $this->getValueForPermissionFromCurrentUser($permissionId);

		return match ($permission)
		{
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

	private function getNavigation(): PageNavigation
	{
		if (!isset($this->arResult['PAGE_NAVIGATION']))
		{
			return $this->prepareNavigation();
		}

		return $this->arResult['PAGE_NAVIGATION'];
	}

	private function prepareNavigation(): PageNavigation
	{
		$pageSize = (int)$this->getParam('PAGE_SIZE');
		$pageSize = $pageSize > 0 ? $pageSize : self::DEFAULT_PAGE_SIZE;
		$navigationKey = $this->getParam('NAVIGATION_KEY') ?? self::DEFAULT_NAVIGATION_KEY;

		$pageNavigation = new \Bitrix\Sign\Util\UI\PageNavigation($navigationKey);
		$pageNavigation->setPageSize($pageSize)
			->setRecordCount($this->signersListService->countSignersWithFilter(
				$this->getIntParam('LIST_ID'),
				$this->getFilterQuery(),
			))
			->setPageSizes([10, 20, 50, 100, 500])
			->allowAllRecords(false)
			->initFromUri()
		;

		$this->arResult['PAGE_NAVIGATION'] = $pageNavigation;

		return $pageNavigation;
	}

	private function getFilterPresets(): array
	{
		return [];
	}
}
