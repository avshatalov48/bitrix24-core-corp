<?php

namespace Bitrix\BizprocMobile\UI;

use Bitrix\Bizproc\FieldType;
use Bitrix\Disk\AttachedObject;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Loader;
use Bitrix\Mobile\UI\File;
use Bitrix\BizprocMobile\Fields;

class TaskView implements \JsonSerializable
{
	private array $files = [];

	public function __construct(private array $task)
	{
		$this->prepareTask();
	}

	private function prepareTask(): void
	{
		$this->replaceFileLinks();
	}

	private function replaceFileLinks(): void
	{
		$description = $this->task['DESCRIPTION'] ?? null;

		if (is_string($description))
		{
			$description = preg_replace_callback(
				'|\[url\s*=\s*/bitrix/tools/bizproc_show_file\.php\?([^]]+)]|',
				$this->getFileLinksReplacer($this->files),
				$description,
			);

			if (Loader::includeModule('disk'))
			{
				$description = preg_replace_callback(
					'|\[url\s*=\s*/bitrix/tools/disk/uf.php\?([^]]+)]|',
					$this->getDiskFileLinksReplacer($this->files),
					$description
				);
			}

			$this->task['DESCRIPTION'] = $description;
		}
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
			'id' => (int)$this->task['ID'],
			'data' => [
				'id' => $this->task['ID'],
				'name' => $this->task['NAME'],
				'task' => $this->getTask(),
				'files' => $this->files,
			],
		];
	}

	private function getTask(): array
	{
		$converter = new Converter(Converter::KEYS | Converter::LC_FIRST | Converter::TO_CAMEL);

		return $converter->process($this->task);
	}
}
