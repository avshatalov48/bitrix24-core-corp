<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Response;

final class AttachedObject extends Engine\Controller
{
	public function configureActions()
	{
		return [
			'download' => [
				'-prefilters' => [
					Main\Engine\ActionFilter\Csrf::class,
					Main\Engine\ActionFilter\Authentication::class,
				],
				'+prefilters' => [
					new Main\Engine\ActionFilter\Authentication(true),
					new Main\Engine\ActionFilter\CloseSession(),
				]
			],
		];
	}

	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(Disk\AttachedObject::class, 'attachedObject', function($className, $id){
			return Disk\AttachedObject::loadById($id);
		});
	}

	/**
	 * Returns default pre-filters for action.
	 * @return array
	 */
	protected function getDefaultPreFilters()
	{
		$defaultPreFilters = parent::getDefaultPreFilters();
		$defaultPreFilters[] = new Engine\ActionFilter\CheckReadPermission();

		return $defaultPreFilters;
	}

	public function copyToMeAction(Disk\AttachedObject $attachedObject)
	{
		$currentUserId = $this->getCurrentUser()->getId();
		$userStorage = Driver::getInstance()->getStorageByUserId($currentUserId);
		if (!$userStorage)
		{
			$this->addError(new Error('Could not find storage for current user'));

			return;
		}

		$folder = $userStorage->getFolderForSavedFiles();
		if (!$folder)
		{
			$this->addError(new Error('Could not find folder for created files'));

			return;
		}

		$file = $attachedObject->getFile();
		if (!$file)
		{
			$this->addError(new Error('Could not find file to copy'));

			return;
		}

		//so, now we don't copy links in the method copyTo. But here we have to copy content.
		//And after we set name to new object as it was on link.
		$newFile = $file->getRealObject()->copyTo($folder, $currentUserId, true);
		if ($file->getRealObject()->getName() != $file->getName())
		{
			$newFile->renameInternal($file->getName(), true);
		}

		if (!$newFile)
		{
			$this->addError(new Error('Could not copy file to storage for current user'));

			return;
		}

		return (new File())->getAction($newFile);
	}

	public function runPreviewGenerationAction(Disk\AttachedObject $attachedObject)
	{
		$file = $attachedObject->getFile();
		if (!$file)
		{
			$this->addError(new Error('Could not find file'));

			return;
		}

		return [
			'previewGeneration' => $file->getView()->transformOnOpen($file),
		];
	}

	public function downloadAction(Disk\AttachedObject $attachedObject)
	{
		$file = $attachedObject->getFile();
		if (!$file)
		{
			$this->addError(new Error('Could not find file'));

			return;
		}

		$response = Response\BFile::createByFileId($file->getFileId(), $attachedObject->getName());
		$response->setCacheTime(Disk\Configuration::DEFAULT_CACHE_TIME);

		return $response;
	}
}