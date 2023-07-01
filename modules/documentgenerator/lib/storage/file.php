<?php

namespace Bitrix\DocumentGenerator\Storage;

use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Storage;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Loader;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\Error;
use Bitrix\Main\Text\BinaryString;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\IO;

class File implements Storage
{
	const ROOT_DIRECTORY_NAME = 'body';

	/**
	 * Try to read of the file from $path.
	 *
	 * @param string $path
	 * @return false|string
	 */
	public function read($path)
	{
		$uri = new Uri($path);
		if($uri->getHost() <> '')
		{
			$path = $this->readFromCloud($path);
			if(!$path)
			{
				return false;
			}
		}
		$file = new IO\File($path);
		if($file->isExists() && $file->isReadable())
		{
			return $file->getContents();
		}
		else
		{
			$path = Path::convertPhysicalToLogical($path);
			$file = new IO\File($path);
			if($file->isExists() && $file->isReadable())
			{
				return $file->getContents();
			}
		}

		return false;
	}

	protected function readFromCloud($path)
	{
		$uri = new Uri($path);
		if($uri->getHost() <> '')
		{
			if(mb_strpos($uri->getHost(), \CBXPunycode::PREFIX) === false)
			{
				$errors = array();
				if(defined("BX_UTF"))
				{
					$punycodedPath = \CBXPunycode::ToUnicode($uri->getHost(), $errors);
				}
				else
				{
					$punycodedPath = \CBXPunycode::ToASCII($uri->getHost(), $errors);
				}

				if($punycodedPath != $uri->getHost())
				{
					$uri->setHost($punycodedPath);
				}
			}
			$path = $uri->getLocator();

			$fileArray = \CFile::makeFileArray($path);
			if($fileArray && $fileArray['tmp_name'])
			{
				return \CBXVirtualIo::getInstance()->getLogicalName($fileArray['tmp_name']);
			}
		}

		return false;
	}

	/**
	 * Try to write $content to the file on $path.
	 *
	 * @param string $content
	 * @param array $options
	 * @return AddResult
	 */
	public function write($content, array $options = [])
	{
		$result = new AddResult();
		$fileName = uniqid(Driver::MODULE_ID, true);
		if (isset($options['isTemplate']) && $options['isTemplate'] === true)
		{
			$fileName = Path::combine('templates', $fileName);
		}
		$path = \CTempFile::getFileName($fileName);
		$dir = IO\Path::getDirectory($path);
		IO\Directory::createDirectory($dir);
		$file = new IO\File($path);
		if($file->putContents($content))
		{
			$result->setId($path);
		}

		return $result;
	}

	/**
	 * @param string $content
	 * @param string $fileName
	 * @param array $options
	 * @return AddResult
	 */
	protected function saveLocal($content, $fileName, array $options = [])
	{
		$result = new AddResult();
		$path = \CTempFile::GetFileName($fileName);
		$file = new IO\File($path);
		if($file->putContents($content))
		{
			$result->setId($path);
		}
		else
		{
			$result->addError(new Error('Cant save file to '.$path));
		}

		return $result;
	}

	/**
	 * @param string $content
	 * @param string $fileName
	 * @param \CCloudStorageBucket $bucket
	 * @param array $options
	 * @return AddResult
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function saveToCloud($content, $fileName, \CCloudStorageBucket $bucket, array $options = [])
	{
		$fileSize = BinaryString::getLength($content);
		$fileName = \CCloudTempFile::GetFileName($bucket, $fileName);
		$result = new AddResult();
		if(Loader::includeModule('clouds'))
		{
			if(!$bucket->init())
			{
				$result->addError(new Error('Could not init bucket'));
				return $result;
			}
			$isStarted = true;
			$upload = new \CCloudStorageUpload($fileName);

			if(!$upload->isStarted())
			{
				/** @noinspection PhpParamsInspection */
				$isStarted = $upload->start($bucket, $fileSize);
			}

			if(!$isStarted)
			{
				$result->addError(new Error('Could not start upload'));
			}

			$currentPos = 0;
			$partSize = $bucket->getService()->GetMinUploadPartSize();
			do
			{
				$data = BinaryString::getSubstring($content, $currentPos, $partSize);
				$currentPos += $partSize;
				$success = false;
				while($upload->hasRetries())
				{
					if($upload->Next($data, $bucket))
					{
						$success = true;
						break;
					}
				}

				if(!$success)
				{
					$result->addError(new Error('Could not upload part'));
					return $result;
				}
				$fileSize -= $partSize;
			}
			while($fileSize > 0);

			if(!$upload->finish())
			{
				$result->addError(new Error('Could not finish upload'));
				return $result;
			}
			$result->setId($bucket->GetFileSRC($fileName));
		}
		else
		{
			$result->addError(new Error('Module clouds is not installed'));
		}
		return $result;
	}

	/**
	 * @param string $path
	 * @param string $fileName
	 * @return bool
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	public function download($path, $fileName = '')
	{
		$file = new IO\File($path);
		if($file->isExists() && $file->isReadable())
		{
			$fileDescription = \CFile::MakeFileArray($path);
			if($fileName)
			{
				$fileDescription['name'] = $fileName;
			}
			\CFile::ViewByUser($fileDescription);
			return true;
		}

		return false;
	}

	/**
	 * @param mixed $path
	 * @return bool
	 */
	public function delete($path)
	{
		$file = new IO\File($path);
		if($file->isExists())
		{
			return $file->delete();
		}

		if(Loader::includeModule('clouds'))
		{
			$cloudPath = \CCloudStorage::FindFileURIByURN($path, Driver::MODULE_ID);
			if(!empty($cloudPath))
			{
				$bucket = \CCloudStorage::FindBucketByFile($cloudPath);
				$bucket->DeleteFile($path);
			}
		}

		return true;
	}

	/**
	 * @param mixed $path
	 * @return false|int
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	public function getModificationTime($path)
	{
		$file = new IO\File($path);
		if($file->isExists() && $file->isReadable())
		{
			return $file->getModificationTime();
		}

		return false;
	}

	/**
	 * @param array $file
	 * @return AddResult
	 * @throws NotImplementedException
	 */
	public function upload(array $file)
	{
		throw new NotImplementedException();
	}

	/**
	 * @param mixed $path
	 * @return false|int
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 * @throws \Bitrix\Main\IO\FileOpenException
	 */
	public function getSize($path)
	{
		$file = new IO\File($path);
		if($file->isExists() && $file->isReadable())
		{
			return $file->getSize();
		}

		return false;
	}
}
