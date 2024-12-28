<?php

namespace Bitrix\BIConnector\ExternalSource\FileReader\Csv;

use Bitrix\Main;
use Bitrix\BIConnector;

class Reader implements BIConnector\ExternalSource\FileReader\Base
{
	private Settings $settings;
	private $handle;
	private array $headers;

	public function __construct(Settings $settings)
	{
		$this->settings = $settings;
		$this->headers = [];
	}

	private function openFile(): void
	{
		if (!file_exists($this->settings->path) || !is_readable($this->settings->path))
		{
			throw new Main\SystemException("File not found or not readable");
		}

		$fileContent = file_get_contents($this->settings->path);
		if ($this->settings->encoding && $this->settings->encoding !== SITE_CHARSET)
		{
			$fileContent = Main\Text\Encoding::convertEncoding(
				$fileContent,
				$this->settings->encoding,
				SITE_CHARSET
			);
		}

		$this->handle = fopen("php://memory", "rwb");
		if (!$this->handle)
		{
			throw new Main\SystemException("Could not open file for reading");
		}

		fwrite($this->handle, $fileContent);
		fseek($this->handle, 0);

		if ($this->settings->hasHeaders)
		{
			$headers = fgetcsv($this->handle, 0, $this->settings->delimiter);
			if ($headers)
			{
				$this->headers = $headers;
			}
		}
	}

	private function closeFile(): void
	{
		if ($this->handle)
		{
			fclose($this->handle);
		}
	}

	public function getHeaders(): ?array
	{
		if (!$this->settings->hasHeaders)
		{
			return null;
		}

		if (empty($this->headers))
		{
			$this->openFile();
		}

		return $this->headers;
	}

	public function readFirstNRows(int $n): array
	{
		$this->openFile();
		$rows = [];
		$i = 0;

		try
		{
			while (($row = fgetcsv($this->handle, 0, $this->settings->delimiter)) !== false && $i < $n)
			{
				$rows[] = $row;
				$i++;
			}
		}
		catch (\Throwable $e)
		{
			$this->headers = [];
			$rows = [];
		}

		$this->closeFile();

		return $rows;
	}

	public function readAllRows(): array
	{
		$this->openFile();

		$rows = [];
		try
		{
			while (($row = fgetcsv($this->handle, 0, $this->settings->delimiter)) !== false)
			{
				$rows[] = $row;
			}
		}
		catch (\Throwable $e)
		{
			$this->headers = [];
			$rows = [];
		}

		$this->closeFile();

		return $rows;
	}

	public function readAllRowsByOne(): \Generator
	{
		$this->openFile();

		while (($row = fgetcsv($this->handle, 0, $this->settings->delimiter)) !== false)
		{
			yield $row;
		}

		$this->closeFile();
	}
}
