<?php

namespace Bitrix\Sign\Service\Sign\Document;

use Bitrix\Main\Result;
use Bitrix\Main;
use Bitrix\Sign\Item\Document\Template;
use Bitrix\Sign\Item\Document\TemplateCollection;
use Bitrix\Sign\Repository\Document\TemplateFolderRelationRepository;
use Bitrix\Sign\Repository\Document\TemplateFolderRepository;
use Bitrix\Sign\Repository\Document\TemplateRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\Template\EntityType;
use Bitrix\Sign\Type\Template\Status;
use Bitrix\Sign\Type\Template\Visibility;

final class TemplateService
{
	private readonly TemplateRepository $templateRepository;
	private readonly TemplateFolderRepository $templateFolderRepository;
	private readonly TemplateFolderRelationRepository $templateFolderRelationRepository;

	public function __construct(
		?TemplateRepository $templateRepository = null,
		?TemplateFolderRepository $templateFolderRepository = null,
		?TemplateFolderRelationRepository $templateFolderRelationRepository = null,
	)
	{
		$container = Container::instance();

		$this->templateRepository = $templateRepository ?? $container->getDocumentTemplateRepository();
		$this->templateFolderRepository = $templateFolderRepository ?? $container->getTemplateFolderRepository();
		$this->templateFolderRelationRepository = $templateFolderRelationRepository ?? $container->getTemplateFolderRelationRepository();
	}

	/**
	 * @param array<array{entityType: string, id: int}> $entities
	 */
	public function moveToFolder(array $entities, int $folderId): Result
	{
		$result = new Result();

		$templateIds = $this->getTemplateIdsFromEntities($entities);
		if (empty($templateIds))
		{
			return $result->addError(new Main\Error('Folder relation not created'));
		}

		$allTemplatesIsInitiatedByCompany = $this->templateRepository->isAllInitiatedByTypeByIds(
			$templateIds,
			InitiatedByType::COMPANY,
		);
		if (!$allTemplatesIsInitiatedByCompany)
		{
			return $result->addError(new Main\Error('All templates should be initiated by company'));
		}

		if ($folderId !== 0)
		{
			$changeFolderVisibilityOnTemplateAdditionalResult = $this->changeFolderVisibilityOnTemplateAddition($templateIds, $folderId);
			if (!$changeFolderVisibilityOnTemplateAdditionalResult->isSuccess())
			{
				return $result->addError(new Main\Error('Change folder visibility error'));
			}
		}

		$parentIdForTemplateInFolderBeforeUpdate = $this->templateFolderRelationRepository->getByEntityIdAndType(
			$templateIds[0],
			EntityType::TEMPLATE
		)
			?->parentId
		;

		$updateParentIdForTemplatesResult = $this->updateParentIdForTemplates(
			$templateIds,
			$parentIdForTemplateInFolderBeforeUpdate,
			$folderId)
		;
		if (!$updateParentIdForTemplatesResult->isSuccess())
		{
			return $result->addError(new Main\Error('Update parent id for templates error'));
		}

		if ($folderId === 0)
		{
			$changeFolderVisibilityOnTemplateRemovalResult = $this->changeFolderVisibilityOnTemplateRemoval($parentIdForTemplateInFolderBeforeUpdate);
			if (!$changeFolderVisibilityOnTemplateRemovalResult->isSuccess())
			{
				return $result->addError(new Main\Error('Change folder visibility error'));
			}
		}

		return $result;
	}

	/**
	 * @param list<int> $updatableEntityIds
	 * @return Result
	 */
	public function updateParent(int $parentId, array $updatableEntityIds, EntityType $entityType): Result
	{
		return $this->templateFolderRelationRepository->updateParent($parentId, $updatableEntityIds, $entityType);
	}

	/**
	 * @param array<array{entityType: string, id: int}> $entities
	 * @return list<int>
	 */
	private function getTemplateIdsFromEntities(array $entities): array
	{
		$templateIds = [];
		foreach ($entities as $entity)
		{
			$entityType = EntityType::tryFrom($entity['entityType'] ?? '');

			if ($entityType === null || !$entityType->isTemplate())
			{
				continue;
			}

			$templateIds[] = $entity['id'];
		}

		return $templateIds;
	}

	/**
	 * @param list<int> $templateIds
	 */
	private function changeFolderVisibilityOnTemplateAddition(array $templateIds, int $folderId): Result
	{
		$folderVisible = Visibility::INVISIBLE;
		$templates = $this->templateRepository->getByIds($templateIds);
		foreach ($templates as $template)
		{
			if ($template->visibility->isVisible())
			{
				$folderVisible = Visibility::VISIBLE;
			}
		}

		$result = new Result();
		$folderItem = $this->templateFolderRepository->getById($folderId);
		if ($folderItem === null)
		{
			return $result->addError(new Main\Error('Folder not found'));
		}

		$folderItem->visibility = $folderVisible;

		$updateFolderResult = $this->templateFolderRepository->update($folderItem);
		if (!$updateFolderResult->isSuccess())
		{
			return $result->addError(new Main\Error('Update folder visible error'));
		}

		return $result;
	}

	/**
	 * @param list<int> $templateIds
	 */
	private function updateParentIdForTemplates(array $templateIds, ?int $sourceFolderId, int $destinationFolderId): Result
	{
		$result = new Result();
		if ($sourceFolderId === null)
		{
			return $result->addError(new Main\Error('Parent id for template in folder before update not found'));
		}

		$updateTemplateFolderRelationResult = $this->templateFolderRelationRepository->updateParent(
			$destinationFolderId,
			$templateIds,
			EntityType::TEMPLATE,
		);

		if (!$updateTemplateFolderRelationResult->isSuccess())
		{
			return $result->addError(new Main\Error('Update relations error'));
		}

		return $result;
	}

	private function changeFolderVisibilityOnTemplateRemoval(?int $folderId): Result
	{
		$folderChildRelations =  $this->templateFolderRelationRepository->getAllByParentIdAndType(
			$folderId,
			EntityType::TEMPLATE
		);

		$templateInFolderIds = $folderChildRelations->getEntityIds();
		$templatesInFolder = $this->templateRepository->getByIds($templateInFolderIds);
		$hasVisibleTemplate = false;
		foreach ($templatesInFolder as $template)
		{
			if ($template->visibility === Visibility::VISIBLE)
			{
				$hasVisibleTemplate = true;
				break;
			}
		}

		$result = new Result();
		if (!$hasVisibleTemplate && $folderId !== 0)
		{
			$folderItem = $this->templateFolderRepository->getById($folderId);
			if ($folderItem === null)
			{
				return $result->addError(new Main\Error('Folder not found'));
			}

			$folderItem->visibility = Visibility::INVISIBLE;
			$updateFolderResult = $this->templateFolderRepository->update($folderItem);
			if (!$updateFolderResult->isSuccess())
			{
				return $result->addError(new Main\Error('Update folder visible error'));
			}
		}

		return $result;
	}

	public function changeVisibility(int $templateId, Visibility $visibility): Result
	{
		$result = new Result();
		$currentTemplate = $this->templateRepository->getById($templateId);

		if ($this->isInvalidVisibilityChange($currentTemplate, $visibility))
		{
			return $result->addError(new Main\Error('Incorrect visibility status'));
		}

		$updateTemplateVisibilityResult = $this->templateRepository->updateVisibility($templateId, $visibility);
		if (!$updateTemplateVisibilityResult->isSuccess())
		{
			return $result->addError(new Main\Error('Update visibility template error'));
		}

		if ($currentTemplate->folderId !== null)
		{
			if (!$this->updateFolderVisibilityBasedOnTemplates($currentTemplate)->isSuccess())
			{
				return $result->addError(new Main\Error('Failed to change folder visibility'));
			}
		}

		return $result;
	}

	private function isInvalidVisibilityChange(?Template $template, Visibility $visibility): bool
	{
		$currentStatus = $template?->status ?? Status::NEW;
		return $currentStatus === Status::NEW && $visibility === Visibility::VISIBLE;
	}

	private function updateFolderVisibilityBasedOnTemplates(Template $currentTemplate): Result
	{
		$currentTemplateRelation = $this->templateFolderRelationRepository->getByEntityIdAndType(
			$currentTemplate->getId(),
			EntityType::TEMPLATE
		);

		$templateIdsInFolder = $this->getTemplateIdsInFolder($currentTemplateRelation->parentId);
		$isAnyTemplateVisible = $this->isAnyTemplateVisible($templateIdsInFolder);

		return $this->templateFolderRepository->updateVisibility(
			$currentTemplateRelation->parentId,
			$isAnyTemplateVisible ? Visibility::VISIBLE : Visibility::INVISIBLE
		);
	}

	private function getTemplateIdsInFolder(int $parentId): array
	{
		$templateRelationsInCurrentFolder = $this->templateFolderRelationRepository->getAllByParentIdAndType(
			$parentId,
			EntityType::TEMPLATE
		);

		return $templateRelationsInCurrentFolder->getEntityIds();
	}

	/**
	 * @param list<int> $templateIds
	 */
	private function isAnyTemplateVisible(array $templateIds): bool
	{
		$allTemplatesInFolder = $this->templateRepository->getByIds($templateIds);
		foreach ($allTemplatesInFolder as $template)
		{
			if ($template->visibility === Visibility::VISIBLE)
			{
				return true;
			}
		}

		return false;
	}

	public function updateTitle(int $templateId, string $title): Result
	{
		return $this->templateRepository->updateTitle($templateId, $title);
	}

	public function getById(int $templateId): ?Template
	{
		if ($templateId < 1)
		{
			return null;
		}

		return $this->templateRepository->getById($templateId);
	}

	/**
	 * @param list<int> $templateIds
	 * @return TemplateCollection
	 */
	public function getByIds(array $templateIds): TemplateCollection
	{
		return $this->templateRepository->getByIds($templateIds);
	}

	public function getByUid(string $templateUid): ?Template
	{
		if ($templateUid === '')
		{
			return null;
		}

		return $this->templateRepository->getByUid($templateUid);
	}

	public function getCompletedAndVisibleCompanyTemplateByUid(string $templateUid): ?Template
	{
		if ($templateUid === '')
		{
			return null;
		}

		return $this->templateRepository->getCompletedAndVisibleCompanyTemplateByUid($templateUid);
	}

	public function hasAnyInvisibleTemplates(TemplateCollection $templates): bool
	{
		foreach ($templates as $template)
		{
			if ($template->visibility->isInvisible())
			{
				return true;
			}
		}

		return false;
	}
}
