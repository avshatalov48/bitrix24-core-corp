<?php
namespace Bitrix\Transformer;

use Bitrix\Main\Web\Uri;
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

		if(is_int($file))
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
			$this->createByPath($rootPath.$file);
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
		$file = \CFile::GetByID($fileId)->fetch();
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
		catch(InvalidPathException $exception)
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
		if(\Bitrix\Main\Loader::includeModule('clouds'))
		{
			$cloudPath = \CCloudStorage::FindFileURIByURN($path, FileUploader::MODULE_ID);
			if(!empty($cloudPath))
			{
				$this->bucket = \CCloudStorage::FindBucketByFile($cloudPath);
				$this->size = $this->bucket->GetFileSize($cloudPath);
				$this->absolutePath = $cloudPath;
				$this->localCloudPath = $path;
			}
		}
	}

	private function findByURL($url)
	{
		$uri = new Uri($url);
		if($uri->getHost() <> '')
		{
			if(mb_strpos($uri->getHost(), \CBXPunycode::PREFIX) === false)
			{
				$errors = array();
				if(defined("BX_UTF"))
				{
					$punicodedPath = \CBXPunycode::ToUnicode($uri->getHost(), $errors);
				}
				else
				{
					$punicodedPath = \CBXPunycode::ToASCII($uri->getHost(), $errors);
				}

				if($punicodedPath != $uri->getHost())
				{
					$uri->setHost($punicodedPath);
				}
			}
			$this->absolutePath = $uri->getLocator();
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