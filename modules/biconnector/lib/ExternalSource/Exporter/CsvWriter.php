<?php

namespace Bitrix\BIConnector\ExternalSource\Exporter;

use Bitrix\Main\IO\File;

class CsvWriter extends Writer
{
	private ?File $file = null;
	/** @var ?resource $fileHandle  */
	private $fileHandle = null;

	public function open(): void
	{
		$filePath = \CTempFile::GetFileName('export.csv');
		$this->file = new File($filePath);
		$this->file->putContents('');
		$this->fileHandle = $this->file->open('a');
	}

	public function close(): void
	{
		$this->file->close();
		$this->fileHandle = null;
	}

	public function writeLine(array $data): void
	{
		if (!$this->file)
		{
			return;
		}

		fputcsv($this->fileHandle, $data);
	}

	public function writeLines(iterable $data): void
	{
		if (!$this->file)
		{
			return;
		}

		foreach ($data as $row)
		{
			fputcsv($this->fileHandle, $row);
		}

		unset($row);
	}

	public function getFile(): File
	{
		return $this->file;
	}
}
