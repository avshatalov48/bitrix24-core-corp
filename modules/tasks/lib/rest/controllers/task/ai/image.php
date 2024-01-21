<?php

namespace Bitrix\Tasks\Rest\Controllers\Task\AI;

use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Security\DiskSecurityContext;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Tasks\Integration\AI\Model;
use \Bitrix\Tasks\Integration\AI\Restriction;
use Bitrix\Tasks\Integration\AI\WhiteList;
use CFile;
use Exception;

/**
 * @restController tasks.task.ai.image
 */
class Image extends Controller
{
	private HttpClient $httpClient;
	private int $userId;

	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				Model\Image::class,
				'image',
				static fn ($className, $image): Model\Image => new $className($image, new WhiteList(), new Restriction\Image())
			),
		];
	}

	protected function init(): void
	{
		parent::init();
		$this->httpClient = new HttpClient();
		$this->userId = CurrentUser::get()->getId();
	}

	/**
	 * @restMethod tasks.task.ai.image.save
	 */
	public function saveAction(Model\Image $image): ?array
	{
		if (!Loader::includeModule('disk'))
		{
			$this->addError(new Error('Disk is not installed'));
			return null;
		}

		if (!$image->getRestriction()->isAvailable())
		{
			$this->addError(new Error('Not available.'));
			return null;
		}

		// if (!$image->isValid())
		// {
		// 	$this->addError(new Error('Image is not valid'));
		// 	return null;
		// }

		try
		{
			$file = $this->upload($image);
		}
		catch (Exception $exception)
		{
			$this->addError(new Error('Upload error.'));
			return null;
		}

		return is_null($file) ? null : ['fileId' => 'n' . $file->getId()];
	}

	private function upload(Model\Image $image): ?File
	{
		$storage = Driver::getInstance()->getStorageByUserId($this->userId);
		if (is_null($storage))
		{
			$this->addError(new Error('No storage for that user.'));
			return null;
		}

		$folder = $storage->getFolderForUploadedFiles();

		if (!$folder->canAdd(new DiskSecurityContext($this->userId)))
		{
			$this->addError(new Error('You have no permissions.'));
			return null;
		}

		$file = $folder->uploadFile($this->getRecord($image), [
			'CREATED_BY' => $this->userId,
		], [], true);

		return $file;
	}

	private function getRecord(Model\Image $image): array
	{
		$tempPath = CFile::GetTempName('', bx_basename($image->getUrl()));
		$isDownloaded = $this->httpClient->setPrivateIp(false)->download(
			$image->getUrl(),
			$tempPath
		);
		if (!$isDownloaded)
		{
			$this->addError(new Error('File cannot be downloaded.'));
			return [];
		}

		$fileType = $this->httpClient->getHeaders()->getContentType() ?: CFile::GetContentType($tempPath);
		$recordFile = CFile::MakeFileArray($tempPath, $fileType);
		$recordFile['MODULE_ID'] = 'tasks';

		return $recordFile;
	}
}