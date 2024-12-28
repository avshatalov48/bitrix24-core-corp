<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Controller\DataProviders\ChildrenDataProvider;
use Bitrix\Disk\Controller\Types;
use Bitrix\Disk\Configuration;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\ZipNginx;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Engine\Response;

class Folder extends BaseObject
{
	public function configureActions()
	{
		$configureActions = parent::configureActions();
		$configureActions['downloadArchive'] = [
			'-prefilters' => [
				ActionFilter\Csrf::class,
			],
			'+prefilters' => [
				new ActionFilter\CloseSession(),
			]
		];

		return $configureActions;
	}

	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(Disk\Folder::class, 'folder', function($className, $id){
			return Disk\Folder::loadById($id);
		});
	}

	public function getAction(Disk\Folder $folder)
	{
		return $this->get($folder);
	}

	protected function get(Disk\BaseObject $folder)
	{
		if (!($folder instanceof Disk\Folder))
		{
			throw new ArgumentTypeException('folder', Disk\Folder::class);
		}

		$data = parent::get($folder);
		$data['folder'] = $data['object'];
		unset($data['object']);

		return $data;
	}

	/**
	 * Creates new folder.
	 * @param Disk\Folder $folder Destination folder to add new folder.
	 * @param string $name Name of new folder.
	 * @param CurrentUser $currentUser Current user, autowired automatically.
	 * @param bool $generateUniqueName Should be true if you want to generate unique name for folder.
	 * @return array|null
	 * @throws ArgumentException
	 * @throws ArgumentTypeException
	 */
	public function addSubFolderAction(
		Disk\Folder $folder,
		string $name,
		CurrentUser $currentUser,
		bool $generateUniqueName = false
	): ?array
	{
		if (empty($name))
		{
			$this->addError(new Error('Could not create folder. Name is empty.'));

			return null;
		}

		$storage = $folder->getStorage();
		if (!$storage)
		{
			$this->addError(new Error('Could not find storage for folder.'));

			return null;
		}

		$securityContext = $storage->getSecurityContext($currentUser->getId());
		if (!$folder->canAdd($securityContext))
		{
			$this->addError(new Error('Could not create folder. Access denied.'));

			return null;
		}

		$subFolder = $folder->addSubFolder([
			'NAME' => $name,
			'CREATED_BY' => $currentUser->getId(),
		], generateUniqueName: $generateUniqueName);

		if (!$subFolder)
		{
			$this->addError(new Error('Could not create folder.'));
			$this->addErrors($folder->getErrors());

			return null;
		}

		return $this->get($subFolder);
	}

	/**
	 * Returns children of folder.
	 * @param Disk\Folder $folder Destination folder to get children.
	 * @param CurrentUser $currentUser Current user, autowired automatically.
	 * @param string|null $search Search string.
	 * @param bool $showRights Should be true if you want to show rights for each element.
	 * @param array $context Additional context for recognizing folderType. Necessary when user deep in folder tree.
	 * @param array $order How to sort elements. For example: ['NAME' => 'ASC']
	 * @param PageNavigation|null $pageNavigation Autowired automatically. Describe how to slice elements.
	 * @return Page|null
	 * @throws NotImplementedException
	 * @see \Bitrix\DiskMobile\Controller\Folder::getChildrenAction()
	 */
	public function getChildrenAction(
		Disk\Folder $folder,
		CurrentUser $currentUser,
		string $search = null,
		string $searchScope = 'currentFolder',
		bool $showRights = false,
		array $context = [],
		array $order = [],
		PageNavigation $pageNavigation = null
	): ?Response\DataType\Page
	{
		$childrenDataProvider = new ChildrenDataProvider();
		$result = $childrenDataProvider->getChildren($folder, $currentUser, $search, $searchScope, $showRights, $context, $order, $pageNavigation);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$data = $result->getData();

		return new Response\DataType\Page('children', $data['children'], $data['total']);
	}

	public function copyToAction(Disk\Folder $folder, Disk\Folder $toFolder)
	{
		return $this->copyTo($folder, $toFolder);
	}

	public function moveToAction(Disk\Folder $folder, Disk\Folder $toFolder)
	{
		return $this->move($folder, $toFolder);
	}

	public function renameAction(Disk\Folder $folder, $newName, $autoCorrect = false)
	{
		return $this->rename($folder, $newName, $autoCorrect);
	}

	public function markDeletedAction(Disk\Folder $folder)
	{
		$this->markDeleted($folder);
	}

	public function deleteTreeAction(Disk\Folder $folder)
	{
		return $this->deleteFolder($folder);
	}

	public function restoreAction(Disk\BaseObject $folder)
	{
		return $this->restore($folder);
	}

	public function generateExternalLinkAction(Disk\Folder $folder)
	{
		if (!Bitrix24Manager::isFeatureEnabled('disk_manual_external_link'))
		{
			$this->addError(new Error('Could not generate external link. Feature is disabled by tarif.'));

			return null;
		}

		return $this->generateExternalLink($folder);
	}

	public function disableExternalLinkAction(Disk\Folder $folder)
	{
		return $this->disableExternalLink($folder);
	}
	
	public function getAllowedOperationsRightsAction(Disk\Folder $folder)
	{
		return $this->getAllowedOperationsRights($folder);
	}

	public function downloadArchiveAction(Disk\Folder $folder): ?ZipNginx\Archive
	{
		if (!ZipNginx\Configuration::isEnabled())
		{
			$this->addError(new Error('Work with mod_zip is disabled in module settings.'));

			return null;
		}

		$storage = $folder->getStorage();
		if (!$storage)
		{
			$this->addError(new Error('Could not find storage for folder.'));

			return null;
		}

		$securityContext = $storage->getSecurityContext($this->getCurrentUser()?->getId());

		return ZipNginx\Archive::createFromFolder($folder, $securityContext);
	}
}