<?php

namespace Bitrix\Sign\Service\Sign\Document\Template;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Collection;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Sign\Repository\Document\TemplateFolderRepository;
use Bitrix\Sign\Repository\Document\TemplateRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Template\EntityType;
use Bitrix\Sign\Access\Model\UserModel;

class AccessService
{
	private TemplateRepository $templateRepository;
	private TemplateFolderRepository $templateFolderRepository;
	private AccessController $accessController;

	private UserModel $currentUserAccessModel;

	/** @var array<int|string, string|null> */
	private array $currentUserPermissionValuesCache = [];

	public function __construct(
		?TemplateRepository $templateRepository = null,
		?TemplateFolderRepository $templateFolderRepository = null,
	)
	{
		$container = Container::instance();

		$userId = (int)$this->getCurrentUserAccessModel()?->getUserId();
		if ($userId <= 0)
		{
			showError('Access denied');

			return;
		}

		$this->accessController = new AccessController($userId);
		$this->templateRepository = $templateRepository ?? $container->getDocumentTemplateRepository();
		$this->templateFolderRepository = $templateFolderRepository ?? $container->getTemplateFolderRepository();
	}

	public function prepareQueryFilterByTemplatePermission(?ConditionTree $queryFilter = null): ConditionTree
	{
		if ($queryFilter === null)
		{
			$queryFilter = Query::filter();
		}

		if ((!Storage::instance()->isB2eAvailable()))
		{
			return $queryFilter;
		}

		$user = $this->getCurrentUserAccessModel();
		if ($user === null)
		{
			return $queryFilter;
		}

		if ($user->isAdmin())
		{
			return $queryFilter;
		}

		$templateReadPermission = $this->getValueForPermissionFromCurrentUser(SignPermissionDictionary::SIGN_B2E_TEMPLATE_READ);

		return match ($templateReadPermission)
		{
			\Bitrix\Crm\Service\UserPermissions::PERMISSION_ALL => $queryFilter,
			\Bitrix\Crm\Service\UserPermissions::PERMISSION_SELF => $queryFilter->where('CREATED_BY_ID', $user->getUserId()),
			\Bitrix\Crm\Service\UserPermissions::PERMISSION_DEPARTMENT => $queryFilter->whereIn('CREATED_BY_ID', $user->getUserDepartmentMembers()),
			\Bitrix\Crm\Service\UserPermissions::PERMISSION_SUBDEPARTMENT => $queryFilter->whereIn('CREATED_BY_ID', $user->getUserDepartmentMembers(true)),
			default => $queryFilter->where('CREATED_BY_ID', 0),
		};
	}

	public function hasCurrentUserAccessToPermissionByItemWithOwnerId(int $itemOwnerId, int|string $permissionId): bool
	{
		if ((!Storage::instance()->isB2eAvailable()))
		{
			return false;
		}

		$userAccessModel = $this->getCurrentUserAccessModel();
		if ($userAccessModel->isAdmin())
		{
			return true;
		}

		$permission = $this->getValueForPermissionFromCurrentUser($permissionId);

		return match ($permission)
		{
			\Bitrix\Crm\Service\UserPermissions::PERMISSION_ALL => true,
			\Bitrix\Crm\Service\UserPermissions::PERMISSION_SELF => $itemOwnerId === $userAccessModel->getUserId(),
			\Bitrix\Crm\Service\UserPermissions::PERMISSION_DEPARTMENT => in_array($itemOwnerId, $userAccessModel->getUserDepartmentMembers(), true),
			\Bitrix\Crm\Service\UserPermissions::PERMISSION_SUBDEPARTMENT => in_array($itemOwnerId, $userAccessModel->getUserDepartmentMembers(true), true),
			default => false,
		};
	}

	public function getValueForPermissionFromCurrentUser(string|int $permissionId): ?string
	{
		$permissionService = new RolePermissionService();

		$this->currentUserPermissionValuesCache[$permissionId] ??= $permissionService->getValueForPermission(
			$this->getCurrentUserAccessModel()->getRoles(),
			$permissionId,
		);

		return $this->currentUserPermissionValuesCache[$permissionId];
	}

	public function getCurrentUserAccessModel(): ?UserModel
	{
		$currentUserId = (int)CurrentUser::get()->getId();
		if (!$currentUserId)
		{
			return null;
		}

		$this->currentUserAccessModel ??= UserModel::createFromId($currentUserId);

		return $this->currentUserAccessModel;
	}

	/**
	 * @param array<array{entityType: string, id: int}> $entities
	 * @return Result
	 */
	public function hasAccessToDeleteTemplateEntities(array $entities): Result
	{
		$result = new Result();
		$folderIds = $this->extractTemplateEntityIdsByType($entities, EntityType::FOLDER);
		$templateFolders = $this->templateFolderRepository->getByIds($folderIds);
		if (!$this->hasAccessToDeleteForCollection($templateFolders))
		{
			return $result->addError(new Error('No access rights to delete folder'));
		}

		$templateIds = $this->extractTemplateEntityIdsByType($entities, EntityType::TEMPLATE);
		$templates = $this->templateRepository->getByIds($templateIds);
		if (!$this->hasAccessToDeleteForCollection($templates))
		{
			return $result->addError(new Error('No access rights to delete templates'));
		}

		$templatesInFolders = Container::instance()->getTemplateFolderService()->getTemplatesInFolders($folderIds);
		if (!$this->hasAccessToDeleteForCollection($templatesInFolders))
		{
			return $result->addError(new Error('No access rights to delete templates in folder'));
		}

		return $result;
	}

	public function hasAccessToDeleteForCollection(Collection $templateEntities): bool
	{
		foreach ($templateEntities as $templateEntity)
		{
			if (!$this->hasAccessToDelete($templateEntity))
			{
				return false;
			}
		}

		return true;
	}

	public function hasAccessToReadForCollection(Collection $templateEntities): bool
	{
		foreach ($templateEntities as $templateEntity)
		{
			if (!$this->hasAccessToRead($templateEntity))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array<array{entityType: string, id: int}> $entities
	 * @return Result
	 */
	public function hasAccessToEditTemplateEntities(array $entities): Result
	{
		$result = new Result();
		$folderIds = $this->extractTemplateEntityIdsByType($entities, EntityType::FOLDER);
		$templateFolders = $this->templateFolderRepository->getByIds($folderIds);
		if (!$this->hasAccessToEditForCollection($templateFolders, $this->accessController))
		{
			return $result->addError(new Error('No access rights to edit folder'));
		}

		$templateIds = $this->extractTemplateEntityIdsByType($entities, EntityType::TEMPLATE);
		$templates = $this->templateRepository->getByIds($templateIds);
		if (!$this->hasAccessToEditForCollection($templates, $this->accessController))
		{
			return $result->addError(new Error('No access rights to edit templates'));
		}

		return $result;
	}

	public function hasAccessToEditForCollection(Collection $templateEntities): bool
	{
		foreach ($templateEntities as $templateEntity)
		{
			if (!$this->hasAccessToEdit($templateEntity))
			{
				return false;
			}
		}

		return true;
	}


	/**
	 * @param array<array{entityType: string, id: int}> $entities
	 * @return int[]
	 */
	private function extractTemplateEntityIdsByType(array $entities, EntityType $entityType): array
	{
		$ids = [];

		foreach ($entities as $entity)
		{
			$entityTypeFromEntities = EntityType::tryFrom($entity['entityType'] ?? '');

			if ($entityTypeFromEntities === null)
			{
				continue;
			}

			if ($entityType === $entityTypeFromEntities)
			{
				$id = (int)($entity['id'] ?? null);
				if ($id < 1)
				{
					continue;
				}

				$ids[] = $id;
			}
		}

		return $ids;
	}

	public function hasAccessToEdit(Contract\Item $item): bool
	{
		return $this->accessController->checkByItem(
			ActionDictionary::ACTION_B2E_TEMPLATE_EDIT,
			$item,
		);
	}

	public function hasAccessToDelete(Contract\Item $item): bool
	{
		return $this->accessController->checkByItem(
			ActionDictionary::ACTION_B2E_TEMPLATE_DELETE,
			$item,
		);
	}

	public function hasAccessToRead(Contract\Item $item): bool
	{
		return $this->accessController->checkByItem(
			ActionDictionary::ACTION_B2E_TEMPLATE_READ,
			$item,
		);
	}
}
