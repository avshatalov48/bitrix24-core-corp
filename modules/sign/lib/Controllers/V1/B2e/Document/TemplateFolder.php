<?php

namespace Bitrix\Sign\Controllers\V1\B2e\Document;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute\ActionAccess;
use Bitrix\Sign\Config\Feature;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Engine\Controller;
use Bitrix\Sign\Item\DocumentTemplateGrid\Row;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Access\AccessibleItemType;
use Bitrix\Sign\Type\Template\EntityType;
use Bitrix\Sign\Type\Template\Visibility;

class TemplateFolder extends Controller
{
	#[ActionAccess(
		permission: ActionDictionary::ACTION_B2E_TEMPLATE_ADD,
	)]
	public function createAction(string $title): array
	{
		if (!Feature::instance()->isTemplateFolderGroupingAllowed())
		{
			$this->addErrorByMessage('Folder grouping is not allowed');

			return [];
		}

		if(empty(trim($title)))
		{
			$this->addErrorByMessage('Folder name cannot be empty');

			return [];
		}

		$container = Container::instance();
		$templateFolderService = $container->getTemplateFolderService();

		$result = $templateFolderService->create($title);
		if (!$result->isSuccess())
		{
			$this->addErrorByMessage('Folder not created');

			return [];
		}

		return $result->getData();
	}

	public function deleteAction(int $folderId): array
	{
		if (!Feature::instance()->isTemplateFolderGroupingAllowed())
		{
			$this->addErrorByMessage('Folder grouping is not allowed');

			return [];
		}

		if ($folderId < 1)
		{
			$this->addErrorByMessage('Incorrect folder id');

			return [];
		}

		$container = Container::instance();
		$templateFolderService = $container->getTemplateFolderService();

		$folder = $templateFolderService->getById($folderId);
		if ($folder?->id === null)
		{
			$this->addErrorByMessage('Folder not found');

			return [];
		}

		$templateAccessService = $container->getTemplateAccessService();

		$templates = $templateFolderService->getTemplatesInFolders([$folder->id]);
		if($templates->isEmpty() && !$templateAccessService->hasAccessToDelete($folder))
		{
			$this->addErrorByMessage('No access rights to delete folder');

			return [];
		}

		if (!$templateAccessService->hasAccessToDeleteForCollection($templates))
		{
			$this->addError(new Error(
				Loc::getMessage('SIGN_CONTROLLERS_V1_B2E_DOCUMENT_TEMPLATE_FOLDER_DELETE_ERROR_ACCESS_DENIED'),
				'ACCESS_DENIED'
			));

			return [];
		}

		$result = $templateFolderService->delete($folderId);
		if (!$result->isSuccess())
		{
			$this->addErrorByMessage('Folder not deleted');

			return [];
		}

		return $result->getData();
	}

	#[ActionAccess(
		permission: ActionDictionary::ACTION_B2E_TEMPLATE_DELETE,
		itemType: AccessibleItemType::TEMPLATE_FOLDER,
		itemIdOrUidRequestKey: 'folderId',
	)]
	public function renameAction(int $folderId, string $newTitle): array
	{
		if (!Feature::instance()->isTemplateFolderGroupingAllowed())
		{
			$this->addErrorByMessage('Folder grouping is not allowed');

			return [];
		}

		if(empty(trim($newTitle)))
		{
			$this->addErrorByMessage('Folder name cannot be empty');

			return [];
		}

		$container = Container::instance();
		$templateFolderService = $container->getTemplateFolderService();

		$result = $templateFolderService->rename($folderId, $newTitle);
		if (!$result->isSuccess())
		{
			$this->addErrorByMessage('The folder has not been renamed');

			return [];
		}

		return $result->getData();
	}

	public function changeVisibilityAction(int $folderId, string $visibility): array
	{
		if (!Feature::instance()->isTemplateFolderGroupingAllowed())
		{
			$this->addErrorByMessage('Folder grouping is not allowed');

			return [];
		}

		$visibility = Visibility::tryFrom($visibility);
		if ($visibility === null)
		{
			$this->addErrorByMessage('Incorrect visibility status');

			return [];
		}

		$container = Container::instance();
		$templateFolderService = $container->getTemplateFolderService();

		$folder = $templateFolderService->getById($folderId);
		if ($folder?->id === null)
		{
			$this->addErrorByMessage('Folder not found');

			return [];
		}

		$templateAccessService = $container->getTemplateAccessService();

		$templates = $templateFolderService->getTemplatesInFolders([$folderId]);
		if($templates->isEmpty() && !$templateAccessService->hasAccessToEdit($folder))
		{
			$this->addErrorByMessage('No access rights to edit folder');

			return [];
		}
		if (!$templateAccessService->hasAccessToEditForCollection($templates))
		{
			$this->addError(new Error(
				Loc::getMessage('SIGN_CONTROLLERS_V1_B2E_DOCUMENT_TEMPLATE_FOLDER_EDIT_ERROR_ACCESS_DENIED'),
				'ACCESS_DENIED'
			));

			return [];
		}

		$result = $templateFolderService->changeVisibility($folderId, $visibility);
		if (!$result->isSuccess())
		{
			$this->addErrorByMessage('Failed to change folder visibility');

			return [];
		}

		return $result->getData();
	}

	public function listByDepthLevelAction(int $depthLevel): array
	{
		if (!Feature::instance()->isTemplateFolderGroupingAllowed())
		{
			$this->addErrorByMessage('Folder grouping is not allowed');

			return [];
		}

		$container = Container::instance();
		$templateGridRepository = $container->getTemplateGridRepository();
		$templateAccessService = $container->getTemplateAccessService();

		$queryFilterByTemplatePermission = $templateAccessService->prepareQueryFilterByTemplatePermission();
		$firstLevelDeepFolders = $templateGridRepository->listByDepthAndEntityType(
			$depthLevel,
			EntityType::FOLDER,
			$queryFilterByTemplatePermission,
		);

		return array_map(
			static fn(Row $folder) =>  [
				'id' => $folder->id,
				'title' => $folder->title,
			],
			$firstLevelDeepFolders->toArray(),
		);
	}
}