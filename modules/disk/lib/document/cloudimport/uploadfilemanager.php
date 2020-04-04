<?php

namespace Bitrix\Disk\Document\CloudImport;

use Bitrix\Disk\Document\GoogleHandler;
use Bitrix\Disk\Driver;

class UploadFileManager extends \Bitrix\Disk\Bitrix24Disk\UploadFileManager
{
	/** @var Entry */
	protected $entry;

	public function __construct()
	{
		parent::__construct();
		$this->tmpFileClass = TmpFile::className();
	}

	/**
	 * @return Entry
	 */
	public function getEntry()
	{
		return $this->entry;
	}

	/**
	 * @param Entry $entry
	 *
	 * @return $this
	 */
	public function setEntry($entry)
	{
		$this->entry = $entry;

		return $this;
	}

	/**
	 * @param $startRange
	 * @param $endRange
	 * @param array $fileData
	 *
	 * @return bool
	 */
	private function isBadGoogleChunk($startRange, $endRange, array $fileData)
	{
		if (!$this->entry)
		{
			return false;
		}

		$documentHandler = Driver::getInstance()
			->getDocumentHandlersManager()
			->getHandlerByCode($this->entry->getService())
		;

		if (!$documentHandler instanceof GoogleHandler)
		{
			return false;
		}

		//mr. Google sends content with different length. It does not equal size in metadata.

		return
			$startRange == 0 &&
			$fileData['size'] - $endRange <= 3  &&
			$fileData['size'] - $this->getFileSize() <= 3
		;
	}

	protected function isInvalidChunkSize($startRange, $endRange, array $fileData)
	{
		return
			parent::isInvalidChunkSize($startRange, $endRange, $fileData) &&
			!$this->isBadGoogleChunk($startRange, $endRange, $fileData)
		;
	}
}