<?php

namespace Bitrix\Sign\Service\Sign\Document;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Exception\SignException;
use Bitrix\Sign\Item\Document\Template\TemplateFolderRelation;
use Bitrix\Sign\Item\Document\TemplateCollection;
use Bitrix\Sign\Item\Document\TemplateFolderCollection;
use Bitrix\Sign\Item\DocumentTemplateGrid\Row;
use Bitrix\Sign\Operation\Document\Template\DeleteTemplateEntity;
use Bitrix\Sign\Repository\Document\TemplateFolderRelationRepository;
use Bitrix\Sign\Repository\Document\TemplateRepository;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Type\DateTime;
use Bitrix\Sign\Type\Template\EntityType;
use Bitrix\Sign\Type\Template\Visibility;
use Bitrix\Sign\Item\Document\TemplateFolder;
use Bitrix\Sign\Repository\Document\TemplateFolderRepository;
use Bitrix\Sign\Service\Container;

final class TemplateFolderService
{
	private readonly TemplateRepository $templateRepository;
	private readonly TemplateFolderRepository $templateFolderRepository;
	private readonly TemplateFolderRelationRepository $templateFolderRelationRepository;
	private readonly DocumentRepository $documentRepository;

	public function __construct(
		?TemplateRepository $templateRepository = null,
		?TemplateFolderRepository $templateFolderRepository = null,
		?TemplateFolderRelationRepository $templateFolderRelationRepository = null,
		?DocumentRepository $documentRepository = null,
	)
	{
		$container = Container::instance();

		$this->templateRepository = $templateRepository ?? $container->getDocumentTemplateRepository();
		$this->templateFolderRepository = $templateFolderRepository ?? $container->getTemplateFolderRepository();
		$this->templateFolderRelationRepository = $templateFolderRelationRepository ?? $container->getTemplateFolderRelationRepository();
		$this->documentRepository = $documentRepository ?? $container->getDocumentRepository();
	}

	public function create(string $title): Main\Result
	{
		$result = new Main\Result();
		$currentUserId = (int)CurrentUser::get()->getId();
		if (!$currentUserId)
		{
			return $result->addError(new Main\Error('User not found'));
		}

		$newTemplateFolder = new TemplateFolder(
			title: $title,
			createdById: $currentUserId,
			modifiedById: $currentUserId,
			dateCreate: new DateTime(),
			visibility: Visibility::INVISIBLE,
		);

		$result = $this->templateFolderRepository->add($newTemplateFolder);
		if (!$result->isSuccess())
		{
			return $result->addError(new Main\Error('Folder not created'));
		}

		$templateFolder = $result->templateFolder;
		$newTemplateFolderRelation = new TemplateFolderRelation(
			entityId: $templateFolder->id,
			entityType: EntityType::FOLDER,
			createdById: $currentUserId,
			parentId: 0,
		);

		$result = $this->templateFolderRelationRepository->add($newTemplateFolderRelation);
		if (!$result->isSuccess())
		{
			return $result->addError(new Main\Error('Folder relation not created'));
		}

		return $result;
	}

	public function delete(int $templateFolderId): Main\Result
	{
		$currentUserId = (int)CurrentUser::get()->getId();
		if (!$currentUserId)
		{
			return (new Main\Result())->addError(new Main\Error('User not found'));
		}

		$templateEntity = new TemplateFolderRelation($templateFolderId, EntityType::FOLDER, $currentUserId);
		$result = (new DeleteTemplateEntity([$templateEntity]))->launch();
		if (!$result->isSuccess())
		{
			return $result->addError(new Main\Error('Delete folders error'));
		}

		return $result;
	}

	public function rename(int $templateFolderId, string $newTitle): Main\Result
	{
		$result = new Main\Result();
		$currentUserId = (int)CurrentUser::get()->getId();
		if (!$currentUserId)
		{
			return $result->addError(new Main\Error('User not found'));
		}

		$templateFolder = $this->templateFolderRepository->getById($templateFolderId);
		if ($templateFolder === null)
		{
			return $result->addError(new Main\Error('Folder not found'));
		}

		$templateFolder->dateModify = new DateTime();
		$templateFolder->modifiedById = $currentUserId;
		$templateFolder->title = $newTitle;
		$result = $this->templateFolderRepository->update($templateFolder);
		if (!$result->isSuccess())
		{
			return $result->addError(new Main\Error('Folder not updated'));
		}

		return $result;
	}

	public function changeVisibility(int $folderId, Visibility $visibility): Main\Result
	{
		$result = new Main\Result();
		$connection = Application::getConnection();
		$connection->startTransaction();
		try
		{
			$updateFolderResult = $this->templateFolderRepository->updateVisibility($folderId, $visibility);
			if (!$updateFolderResult->isSuccess())
			{
				$result->addErrors($updateFolderResult->getErrors());
				throw new SignException('Visibility for the folder has not been updated');
			}

			$templateIdsByFolder = $this->templateFolderRepository->getTemplateIdsByFolderId($folderId);

			$updateTemplatesResult = $this->templateRepository->updateVisibilities($templateIdsByFolder, $visibility);
			if (!$updateTemplatesResult->isSuccess())
			{
				$result->addErrors($updateTemplatesResult->getErrors());
				throw new SignException('Visibility for the templates has not been updated');
			}

			$connection->commitTransaction();
		}
		catch (SignException $e)
		{
			$connection->rollbackTransaction();
			$result->addError(new Main\Error($e->getMessage()));
		}

		return $result;
	}

	/**
	 * @param list<int> $folderIds
	 * @return TemplateCollection
	 */
	public function getTemplatesInFolders(array $folderIds): TemplateCollection
	{
		$templateRelations = $this
			->templateFolderRelationRepository
			->getAllByParentIdsAndType($folderIds, EntityType::TEMPLATE);

		$templateIds = $templateRelations->getEntityIds();

		return $this->templateRepository->getByIds($templateIds);
	}

	/**
	 * @param int $folderId
	 * @return TemplateFolder|null
	 */
	public function getById(int $folderId): ?TemplateFolder
	{
		if ($folderId < 1)
		{
			return null;
		}

		return $this->templateFolderRepository->getById($folderId);
	}

	/**
	 * @param list<int> $folderIds
	 * @return TemplateFolderCollection
	 */
	public function getByIds(array $folderIds): TemplateFolderCollection
	{
		return $this->templateFolderRepository->getByIds($folderIds);
	}

	/**
	 * @param int $folderId
	 * @return list<int>
	 */
	public function getTemplateIdsById(int $folderId): array
	{
		if ($folderId < 1)
		{
			return [];
		}

		return $this->templateFolderRepository->getTemplateIdsByFolderId($folderId);
	}

	/**
	 * @return list<string>
	 */
	public function getTemplateIdsByRow(Row $row): array
	{
		if ($row->entityType->isTemplate())
		{
			return $row->id ? [$row->id] : [];
		}

		if (!$row->entityType->isFolder())
		{
			return [];
		}

		$templates = $this->getTemplatesInFolders([$row->id]);
		if ($templates->isEmpty())
		{
			return [];
		}

		return $templates->getIds();
	}

	/**
	 * @param list<int> $folderIds
	 * @return array<int, list<int>>
	 */
	public function getTemplateIdsByIdsMap(array $folderIds): array
	{
		$templatesByFolders = [];
		foreach ($folderIds as $folderId)
		{
			$templatesByFolders[$folderId] = [];
		}

		$relations = $this->templateFolderRelationRepository->getTemplateIdsByIdsMap($folderIds);
		foreach ($relations as $relation)
		{
			$folderId = $relation->parentId;
			if (!isset($templatesByFolders[$folderId]))
			{
				continue;
			}

			$templateId = $relation->entityId;
			$templatesByFolders[$folderId][] = $templateId;
		}

		return $templatesByFolders;
	}
}