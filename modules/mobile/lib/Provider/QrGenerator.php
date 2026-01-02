<?php

namespace Bitrix\Mobile\Provider;

use Bitrix\Main\IO\File;
use Bitrix\Main\IO\Path;
use Bitrix\UI\Barcode\Barcode;

final class QrGenerator
{
	private string $url;
	private bool $darkMode;
	private ?string $qrContent = null;
	private ?string $filePath = null;

	/**
	 * @param string $url
	 * @param bool $darkMode
	 */
	public function __construct(string $url, bool $darkMode = false)
	{
		$this->url = $url;
		$this->darkMode = $darkMode;
	}

	/**
	 * @return string
	 */
	public function getContent(): string
	{
		if ($this->qrContent === null)
		{
			$this->generateQr();
		}

		return $this->qrContent;
	}

	/**
	 * @return string|null
	 */
	public function getUrl(): ?string
	{
		if ($this->filePath === null)
		{
			$this->saveQrToTemporaryFile();
		}

		return $this->filePath ? $this->getRelativePath($this->filePath) : null;
	}

	/**
	 * @return void
	 */
	private function generateQr(): void
	{
		$bgColor = $this->darkMode ? '#171717' : '#FFFFFF';
		$qrColor = $this->darkMode ? '#FFFFFF' : '#171717';

		$this->qrContent = (new Barcode())
			->format('svg')
			->option('w', 300)
			->option('h', 300)
			->option('p', 18)
			->option('wq', 0)
			->option('bc', $bgColor)
			->option('wq', 0)
			->option('cm', $qrColor)
			->render($this->url);
	}

	/**
	 * @return void
	 */
	private function saveQrToTemporaryFile(): void
	{
		$qrContent = $this->getContent();

		$hash = md5($qrContent);
		$tmpDirName = \CTempFile::GetDirectoryName(1, 'qr_tmp');
		$tmpFileName = Path::combine($tmpDirName, $hash . '.svg');

		if (!Path::validate($tmpFileName))
		{
			return;
		}

		$tmpFile = new File($tmpFileName);

		if (!$tmpFile->isFileExists($tmpFile->getPath()))
		{
			$writeResult = $tmpFile->putContents($qrContent);
			if ($writeResult === false)
			{
				return;
			}
		}

		$this->filePath = $tmpFileName;
	}

	/**
	 * @param string $absoluteFilePath
	 * @return string
	 */
	private function getRelativePath(string $absoluteFilePath): string
	{
		$absoluteRoot = \CTempFile::GetAbsoluteRoot();
		$relativePath = str_replace($absoluteRoot . '/', '', $absoluteFilePath);

		return '/upload/tmp/' . $relativePath;
	}
}