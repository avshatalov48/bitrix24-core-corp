<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die;
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Access\Model\UserModel;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass('bitrix:sign.base');

final class SignB2eSignersSignerEdit extends SignBaseComponent
{
	private \Bitrix\Sign\Repository\SignersList\SignersListRepository $signersListRepository;
	private \Bitrix\Sign\Service\SignersListService $signersListService;

	private ?\Bitrix\Sign\Item\SignersList $list = null;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->signersListRepository = Container::instance()->getSignersListRepository();
		$this->signersListService = Container::instance()->getSignersListService();
	}

	public function executeComponent(): void
	{
		if (!\Bitrix\Sign\Config\Storage::instance()->isB2eAvailable())
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

		$listId = $this->getIntParam('LIST_ID');
		$this->list = $listId !== 0
			? $this->signersListService->getById($listId)
			: null
		;

		if ($listId === 0 || $this->list === null)
		{
			showError('No such list');

			return;
		}

		parent::executeComponent();
	}

	public function exec(): void
	{
		$this->setResult('CAN_ADD_SIGNER', $this->canCurrentUserEditList($this->list));
		$this->setResult('CAN_DELETE_SIGNER', $this->canCurrentUserEditList($this->list));
		$this->setResult('LIST_ID', $this->list->id);
		$this->setResult('LIST_TITLE', $this->list->title);
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
			&& $this->getAccessController()->checkAll([
				ActionDictionary::ACTION_B2E_SIGNERS_LIST_REFUSED_EDIT,
				ActionDictionary::ACTION_B2E_SIGNERS_LIST_EDIT,
			])
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
}
