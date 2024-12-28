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
		$description = $this->task['DESCRIPTION'] ?? null;
		if (is_string($description))
		{
			$parser = new \CTextParser();
			$bbDescription = $parser->convertHTMLToBB(
				$description,
				[
					'ANCHOR' => 'Y',
					'BIU' => 'Y',
					'FONT' => 'Y',
					'LIST' => 'Y',
					'NL2BR' => 'Y',
					'IMG' => 'Y',

					'HTML' => 'N',
					'QUOTE' => 'N',
					'CODE' => 'N',
					'SMILES' => 'N',
					'VIDEO' => 'N',
					'TABLE' => 'N',
					'ALIGN' => 'N',
					'P' => 'N',
				]
			);

			$codeView = new BbCodeView($bbDescription);
			$this->task['DESCRIPTION'] = $codeView->getText();
			$this->files = $codeView->getFiles();
		}
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
