<?php

namespace Bitrix\DocumentGenerator\Controller;

use Bitrix\DocumentGenerator\Body\Docx;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Model\FileTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Uploader\Uploader;

class File extends Base
{
	const FILE_PARAM_NAME = 'file';

	protected $uploader;

	/**
	 * @return array|bool
	 */
	public function uploadAction()
	{
		return $this->getUploader()->checkPost();
	}

	/**
	 * @param $fileId
	 * @throws \Exception
	 */
	public function deleteAction($fileId)
	{
		$result = FileTable::delete($fileId);
		if(!$result->isSuccess())
		{
			$this->errorCollection = $result->getErrorCollection();
		}
	}

	/**
	 * @param $hash
	 * @param $file
	 * @param $package
	 * @param $upload
	 * @param $error
	 * @return bool
	 */
	public function uploadDocxFile($hash, &$file, &$package, &$upload, &$error)
	{
		Loc::loadMessages(__FILE__);
		if($file['size'] > 1024*1024)
		{
			$error = Loc::getMessage('DOCGEN_CONT_FILE_UPLOAD_WRONG_SIZE');
			return false;
		}
		$uploadResult = FileTable::saveFile($file['files']['default']);
		if($uploadResult->isSuccess())
		{
			$fileId = $uploadResult->getId();
			if(\Bitrix\Main\IO\File::isFileExists($file['files']['default']['tmp_name']))
			{
				$body = new Docx(\Bitrix\Main\IO\File::getFileContents($file['files']['default']['tmp_name']));
			}
			else
			{
				$body = new Docx(FileTable::getContent($fileId));
			}
			if(!$body->isFileProcessable())
			{
				$error = Loc::getMessage('DOCGEN_CONT_FILE_UPLOAD_CORRUPTED_FILE');
				FileTable::delete($fileId);
				return false;
			}
			$file['FILE_ID'] = $fileId;
			$file['name'] = GetFileNameWithoutExtension($file['name']);
			return true;
		}
		else
		{
			$error = implode(' ,', $uploadResult->getErrorMessages());
			return false;
		}
	}

	/**
	 * @return Uploader
	 */
	protected function getUploader()
	{
		if($this->uploader === null)
		{
			$this->uploader = new Uploader([
				"events" => [
					"onFileIsUploaded" => [$this, "uploadDocxFile"],
				],
				"storage" => [
					"cloud" => true,
					"moduleId" => Driver::MODULE_ID,
				],
			]);
		}

		return $this->uploader;
	}
}