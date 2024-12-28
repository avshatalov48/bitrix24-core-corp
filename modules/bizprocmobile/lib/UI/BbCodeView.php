<?php

namespace Bitrix\BizprocMobile\UI;

use Bitrix\Disk\AttachedObject;
use Bitrix\Main\Loader;
use Bitrix\Mobile\UI\File;
use Bitrix\BizprocMobile\Fields;

class BbCodeView implements \JsonSerializable
{
	private string $text;
	private array $files = [];

	public function __construct(string $sourceText)
	{
		$this->extractFromSource($sourceText);
	}

	public function getText(): string
	{
		return $this->text;
	}

	public function getFiles(): array
	{
		return $this->files;
	}

	private function extractFromSource(string $sourceText): void
	{
		$sourceText = preg_replace_callback(
			'|\[url\s*=\s*/bitrix/tools/bizproc_show_file\.php\?([^]]+)]|',
			$this->getFileLinksReplacer($this->files),
			$sourceText,
		);

		if (Loader::includeModule('disk'))
		{
			$sourceText = preg_replace_callback(
				'|\[url\s*=\s*/bitrix/tools/disk/uf.php\?([^]]+)]|',
				$this->getDiskFileLinksReplacer($this->files),
				$sourceText
			);
		}

		$this->text = $sourceText;
	}

	private function getFileLinksReplacer(array& $files): callable
	{
		return function ($matches) use (&$files)
		{
			parse_str(htmlspecialcharsback($matches[1]), $query);
			$fileId = $query['i'] ?? null;
			if (isset($fileId))
			{
				$fileId = (int)$fileId;
				$file = File::load($fileId);
				if (!isset($file))
				{
					return $matches[0];
				}
				$uri = 'fid://' . $file->getId();
				$files[$uri] = $file;

				return '[url=' . $uri . ']';
			}

			// File not found
			// TODO - delete link?
			return $matches[0];
		};
	}

	private function getDiskFileLinksReplacer(array& $files): callable
	{
		return function ($matches) use (&$files)
		{
			parse_str(htmlspecialcharsback($matches[1]), $query);
			$attachedModel = AttachedObject::loadById($query['attachedId'] ?? null);
			$diskFile = $attachedModel?->getFile();
			if (isset($diskFile))
			{
				$file = File::load($diskFile->getFileId());
				if (!isset($file))
				{
					return $matches[0];
				}

				$uri = 'fid://' . $file->getId();
				$files[$uri] = $file;

				return '[url=' . $uri . ']';
			}

			// File not found
			// TODO - delete link?
			return $matches[0];
		};
	}

	public function jsonSerialize(): array
	{
		return [
			'text' => $this->text,
			'files' => $this->files,
		];
	}
}
