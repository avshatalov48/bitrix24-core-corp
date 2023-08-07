<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\ZipNginx;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\AutoWire\ExactParameter;

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

	public function renameAction(Disk\Folder $folder, $newName, $autoCorrect = false)
	{
		return $this->rename($folder, $newName, $autoCorrect);
	}

	public function markDeletedAction(Disk\Folder $folder)
	{
		return $this->markDeleted($folder);
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