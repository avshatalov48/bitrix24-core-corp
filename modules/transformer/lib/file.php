<?php
namespace Bitrix\Transformer;

use Bitrix\Main\IO;
use Bitrix\Main\IO\InvalidPathException;

class File
{
	/** @var int */
	private $size;
	/** @var  string */
	private $absolutePath;
	/** @var IO\File */
	private $ioFile;
	/** @var  \CCloudStorageBucket */
	private $bucket;
	private $localCloudPath;

	/**
	 * File constructor.
	 * @param int|string $file - ID in b_file or path.
	 */
	public function __construct($file)
	{
		if(empty($file))
		{
			return;
		}

		if(is_numeric($file))
		{
			$this->createByCFileId($file);
		}

		if(!$this->absolutePath)
		{
			$this->createByPath($file);
		}

		if(!$this->absolutePath)
		{
			$rootPath = $_SERVER['DOCUMENT_ROOT'];
			$this->createByPath(IO\Path::combine($rootPath, $file));
		}

		if(!$this->absolutePath)
		{
			//relative in upload path
			$absolutePath = FileUploader::getFullPath($file);
			$this->createByPath($absolutePath);
		}

		if(!$this->absolutePath)
		{
			$this->findInCloud($file);
		}
	}

	private function createByCFileId($fileId)
	{
		$file = \CFile::GetByID($fileId)->Fetch();
		if($file)
		{
			$this->absolutePath = \CFile::GetPath($fileId);
			$this->size = $file['FILE_SIZE'];
		}
	}

	private function createByPath($path)
	{
		try
		{
			$ioFile = new IO\File($path);
		}
		/** @noinspection PhpRedundantCatchClauseInspection */
		catch(InvalidPathException)
		{
			return;
		}

		if($ioFile->isExists())
		{
			$this->ioFile = $ioFile;
			$this->size = $this->ioFile->getSize();
			$path = $this->ioFile->getPath();
			$this->absolutePath = $path;
		}
	}

	private function findInCloud($path)
	{
		if (!\Bitrix\Main\Loader::includeModule('clouds'))
		{
			return;
		}

		if (str_starts_with((string)$path, 'http://') || str_starts_with((string)$path, 'https://'))
		{
			// absolute url

			$bucket = \CCloudStorage::FindBucketByFile($path);
			if ($bucket)
			{
				$this->bucket = $bucket;
				$this->absolutePath = $path;
				$this->localCloudPath = '/' . str_replace($this->bucket->GetFileSRC('/'), '', $this->absolutePath);
			}
		}
		else
		{
			// urn

			$cloudPath = \CCloudStorage::FindFileURIByURN($path, FileUploader::MODULE_ID);
			if (!empty($cloudPath))
			{
				$this->bucket = \CCloudStorage::FindBucketByFile($cloudPath);
				$this->absolutePath = $cloudPath;
				$this->localCloudPath = $path;
			}
		}

		if ($this->bucket && $this->localCloudPath)
		{
			$this->size = $this->bucket->GetFileSize($this->localCloudPath);
		}
	}

	/**
	 * @return string
	 */
	public function getAbsolutePath()
	{
		return $this->absolutePath;
	}

	public function getPublicPath()
	{
		$documentRoot = \Bitrix\Main\Application::getDocumentRoot();
		$publicPath = str_replace($documentRoot, '', $this->absolutePath);
		return $publicPath;
	}

	/**
	 * @return int
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * Delete file.
	 * @return bool
	 */
	public function delete()
	{
		if($this->ioFile && FileUploader::isCorrectFile($this->ioFile))
		{
			return $this->ioFile->delete();
		}
		elseif($this->bucket)
		{
			return $this->bucket->DeleteFile($this->localCloudPath);
		}

		return false;
	}

}
