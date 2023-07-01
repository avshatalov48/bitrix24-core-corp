<?php

namespace Bitrix\DocumentGenerator\Storage;

use Bitrix\DocumentGenerator\Driver;
use Bitrix\Main\Error;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Entity\AddResult;

class BFile extends File
{
	/**
	 * Try to read content. Returns string on success, false on failure.
	 *
	 * @param mixed $fileId
	 * @return false|string
	 */
	public function read($fileId)
	{
		if(intval($fileId) > 0)
		{
			$fileArray = \CFile::MakeFileArray($fileId);
			if($fileArray && $fileArray['tmp_name'])
			{
				return parent::read($fileArray['tmp_name']);
			}
			$path = \CFile::getPath($fileId);
			if($path)
			{
				return parent::read($path);
			}
		}

		return false;
	}

	/**
	 * Save $content. Returns true on success, false on failure.
	 *
	 * @param string $content
	 * @param array $options
	 * @return AddResult
	 */
	public function write($content, array $options = [])
	{
		$result = parent::write($content, $options);
		if($result->isSuccess())
		{
			$filePath = $result->getId();
			$contentType = false;
			if(isset($options['contentType']))
			{
				$contentType = $options['contentType'];
			}
			if(!isset($options['MODULE_ID']))
			{
				$options['MODULE_ID'] = Driver::MODULE_ID;
			}
			$fileDescription = \CFile::MakeFileArray($filePath, $contentType);
			if($fileDescription)
			{
				if(isset($options['fileName']))
				{
					$options['fileName'] = str_replace(' ', '_', Path::replaceInvalidFilename($options['fileName'], function()
					{
						return '_';
					}));
					$fileDescription['name'] = $fileDescription['fileName'] = $options['fileName'];
				}
				if(isset($options['MODULE_ID']))
				{
					$fileDescription['MODULE_ID'] = $options['MODULE_ID'];
				}
				$path = $this->getPath($fileDescription);
				$fileId = \CFile::SaveFile($fileDescription, $path);
				parent::delete($filePath);
				$result->setId($fileId);
			}
			else
			{
				$result->addError(new Error('Cant get file description from '.$filePath));
			}
		}

		return $result;
	}

	/**
	 * @param int $fileId
	 * @param string $fileName
	 * @return bool
	 */
	public function download($fileId, $fileName = '')
	{
		if(intval($fileId) > 0)
		{
			$fileDescription = \CFile::GetFileArray($fileId);
			$options = [];
			if($fileName)
			{
				$options['attachment_name'] = $this->correctFileName($fileName);
			}
			\CFile::ViewByUser($fileDescription, $options);
			return true;
		}

		return false;
	}

	/**
	 * @param mixed $fileId
	 * @return bool
	 */
	public function delete($fileId)
	{
		if(intval($fileId) > 0)
		{
			\CFile::Delete($fileId);
		}

		return true;
	}

	/**
	 * @param int $fileId
	 * @return false|int
	 */
	public function getModificationTime($fileId)
	{
		if(intval($fileId) > 0)
		{
			$file = \CFile::GetByID($fileId)->Fetch();
			if($file)
			{
				return intval(MakeTimeStamp($file["TIMESTAMP_X"]));
			}
		}

		return false;
	}

	/**
	 * @param array $file
	 * @return AddResult
	 */
	public function upload(array $file)
	{
		$result = new AddResult();
		$path = $this->getPath($file);
		$fileId = \CFile::saveFile($file, $path, true, true);
		if($fileId > 0)
		{
			$result->setId($fileId);
		}
		else
		{
			$result->addError(new Error('Cant save file to b_file'));
		}

		return $result;
	}

	/**
	 * @param mixed $fileId
	 * @return false|int
	 */
	public function getSize($fileId)
	{
		if(intval($fileId) > 0)
		{
			$file = \CFile::GetByID($fileId)->Fetch();
			if($file)
			{
				return $file['FILE_SIZE'];
			}
		}

		return false;
	}

	/**
	 * @param string $fileName
	 * @return string
	 */
	protected function correctFileName($fileName)
	{
		$fileName = Path::replaceInvalidFilename($fileName, function(){
			return '_';
		});

		$correctedFileName = preg_replace('~\x{00a0}~siu', ' ', $fileName);
		if($correctedFileName !== null)
		{
			$fileName = $correctedFileName;
		}

		return $fileName;
	}

	/**
	 * @param array $file
	 * @return string
	 */
	protected function getPath(array $file)
	{
		$path = Driver::MODULE_ID;
		$isTemplate = $file['isTemplate'] ?? false;
		if ($isTemplate === true)
		{
			$path = Path::combine('templates', $path);
		}

		return $path;
	}
}
