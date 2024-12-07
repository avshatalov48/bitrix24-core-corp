<?php
namespace Bitrix\Sign\Upload;

use Bitrix\UI\FileUploader\UploaderController;
use Bitrix\UI\FileUploader\FileOwnershipCollection;
use Bitrix\UI\FileUploader\Configuration;

class BlankUploadController extends UploaderController
{
	public function __construct(array $options = [])
	{
		parent::__construct($options);
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function getConfiguration(): Configuration
	{
		$configuration = new Configuration();
		$configuration->setMaxFileSize(\Bitrix\Sign\Config\Storage::instance()->getUploadDocumentMaxSize());
		$configuration->setImageMaxFileSize(\Bitrix\Sign\Config\Storage::instance()->getUploadImagesMaxSize());
//		$configuration->setAcceptedFileTypes([
//			// 'image/*',
//			'.jpg', '.jpeg', '.png',
//			'image/png', 'image/jpeg',
//		]);
		return $configuration;
	}

	public function verifyFileOwner(FileOwnershipCollection $files): void
	{
		// $myFiles = [1, 2, 3, 123, 858, 859, 1077, 1078, 1451, 4514, 4515, 1472];
		// foreach ($files as $file)
		// {
		// 	if (in_array($file->getId(), $myFiles, true))
		// 	{
		// 		$file->markAsOwn();
		// 	}
		// }
	}

	public function canUpload(): bool
	{
		return true;
	}

	public function canView(): bool
	{
		return false;
	}

	public function canRemove(): bool
	{
		return false;
	}
}