<?php

namespace Bitrix\DocumentGenerator\Storage;

use Bitrix\Disk\Driver;
use Bitrix\Disk\SystemUser;
use Bitrix\Disk\Ui\Text;
use Bitrix\DocumentGenerator\Integration\Disk\ProxyType;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\BinaryString;

final class Disk extends BFile
{
	const STORAGE_CODE = 'documents';

	/**
	 * Try to read content. Returns string on success, false on failure.
	 *
	 * @param mixed $fileId
	 * @return false|array
	 */
	public function read($fileId)
	{
		if(Loader::includeModule('disk'))
		{
			$file = \Bitrix\Disk\File::getById($fileId);
			if($file)
			{
				$bFileId = $file->getFileId();
				return parent::read($bFileId);
			}
			else
			{
				echo 'cant read from '.$fileId;
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
		if(Loader::includeModule('disk'))
		{
			$result = parent::write($content, $options);
			if($result->isSuccess())
			{
				$bFileId = $result->getId();
				$result = $this->addFile($bFileId, $options, BinaryString::getLength($content));
			}
		}
		else
		{
			$result = new AddResult();
			$result->addError(new Error('no disk module'));
		}

		return $result;
	}

	/**
	 * @return \Bitrix\Disk\Storage|null
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected static function getDiskStorage()
	{
		static $storage;
		if($storage === null)
		{
			Loc::loadMessages(__FILE__);
			$storageName = Loc::getMessage('DOCGEN_STORAGE_DISK_NAME');
			if(!$storageName)
			{
				$storageName = \Bitrix\DocumentGenerator\Driver::MODULE_ID;
			}
			$storage = Driver::getInstance()->addStorageIfNotExist([
				'NAME' => $storageName,
				'MODULE_ID' => \Bitrix\DocumentGenerator\Driver::MODULE_ID,
				'ENTITY_TYPE' => ProxyType::class,
				'ENTITY_ID' => \Bitrix\DocumentGenerator\Driver::MODULE_ID,
			]);
			if($storage && $storage->isEnabledTransformation())
			{
				$storage->disableTransformation();
			}
		}

		return $storage;
	}

	/**
	 * @param int $fileId
	 * @param string $fileName
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function download($fileId, $fileName = '')
	{
		if(Loader::includeModule('disk'))
		{
			$file = \Bitrix\Disk\File::getById($fileId);
			if($file)
			{
				$bFileId = $file->getFileId();
				return parent::download($bFileId, $fileName);
			}
			else
			{
				echo 'no disk file '.$fileId;
			}
		}
		else
		{
			echo 'no module disk';
		}

		return false;
	}

	/**
	 * @param mixed $fileId
	 * @return bool
	 */
	public function delete($fileId)
	{
		if(Loader::includeModule('disk'))
		{
			$file = \Bitrix\Disk\File::getById($fileId);
			if($file)
			{
				return $file->delete($this->getUserId());
			}
		}

		return false;
	}

	/**
	 * @param int $fileId
	 * @return false|int
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function getModificationTime($fileId)
	{
		if(Loader::includeModule('disk'))
		{
			$file = \Bitrix\Disk\File::getById($fileId);
			if($file)
			{
				return $file->getUpdateTime()->getTimestamp();
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
		if(Loader::includeModule('disk'))
		{
			$result = parent::upload($file);
			if($result->isSuccess())
			{
				$fileName = $this->getRandomFileName().'.'.GetFileExtension($file['name']);
				if(isset($file['fileName']))
				{
					$fileName = $file['fileName'];
				}
				$bFileId = $result->getId();
				$result = $this->addFile($bFileId, ['fileName' => $fileName], $file['size']);
			}
		}
		else
		{
			$result = new AddResult();
			$result->addError(new Error('no disk module'));
		}

		return $result;
	}

	/**
	 * @return int
	 */
	protected function getUserId()
	{
		return SystemUser::SYSTEM_USER_ID;
	}

	/**
	 * @param int $bFileId
	 * @param array $options
	 * @param int $size
	 * @return AddResult
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function addFile($bFileId, array $options = [], $size)
	{
		$name = $options['fileName'];
		$result = new AddResult();
		$storage = static::getDiskStorage();
		$file = $storage->addFile([
			'NAME' => Text::correctFilename($name),
			'FILE_ID' => (int)$bFileId,
			'SIZE' => $size,
			'CREATED_BY' => $this->getUserId(),
		], [], true);
		if($file && $file->getId() > 0)
		{
			$result->setId($file->getId());
		}
		else
		{
			$result->addErrors($storage->getErrors());
		}

		return $result;
	}

	protected function getRandomFileName()
	{
		return uniqid();
	}

	/**
	 * @param mixed $fileId
	 * @return false|int
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function getSize($fileId)
	{
		if(Loader::includeModule('disk'))
		{
			$file = \Bitrix\Disk\File::getById($fileId);
			if($file)
			{
				return $file->getSize();
			}
			else
			{
				echo 'cant read from '.$fileId;
			}
		}

		return false;
	}
}