<?php

namespace Bitrix\DocumentGenerator\Body;

use Bitrix\DocumentGenerator\Body;
use Bitrix\Main\IO\File;

abstract class ZipDocument extends Body
{
	protected $file;
	/** @var \ZipArchive */
	protected $zip;

	public function __construct($content)
	{
		parent::__construct($content);

		$this->file = $this->getTemporaryFile();
	}

	public function __destruct()
	{
		if($this->file)
		{
			try
			{
				$this->file->delete();
			}
			catch (\Throwable $throwable)
			{
			}
		}
	}

	/**
	 * @return bool
	 */
	public function isFileProcessable()
	{
		return $this->open() === true;
	}

	/**
	 * Creates temporary file to store $content as ZipArchive can work with files only.
	 *
	 * @return File|false
	 */
	protected function getTemporaryFile()
	{
		$fileName = \CTempFile::GetFileName();
		$file = new File($fileName);
		if($file->putContents($this->content) !== false)
		{
			return $file;
		}

		return false;
	}

	/**
	 * Tries to open zip archive.
	 * Returns true on success.
	 *
	 * @return bool
	 */
	protected function open()
	{
		$openResult = false;

		if($this->file)
		{
			$this->zip = new \ZipArchive();
			$openResult = $this->zip->open($this->file->getPhysicalPath());
		}

		return $openResult;
	}


	/**
	 * @param string $content
	 * @param string $localName
	 */
	protected function addContentToZip($content, $localName)
	{
		if($this->zip)
		{
			$this->zip->addFromString($localName, $content);
			$this->zip->close();
			$this->zip->open($this->file->getPhysicalPath());
		}
	}
}
