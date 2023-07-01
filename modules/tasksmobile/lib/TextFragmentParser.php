<?php

namespace Bitrix\TasksMobile;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class TextFragmentParser
{
	private string $text;
	private array $files;

	public function __construct(string $text = '', array $files = [])
	{
		$this->setText($text);
		$this->setFiles($files);
	}

	public function getParsedText(): string
	{
		$parsedText = $this->text;
		$parsedText = $this->parseTable($parsedText);
		$parsedText = $this->parseVideo($parsedText);
		$parsedText = $this->parseFiles($parsedText);
		$parsedText = $this->parseLists($parsedText);
		$parsedText = $this->parseSimpleCodes($parsedText);

		return $parsedText;
	}

	private function parseTable(string $text): string
	{
		$index = 0;

		while (true)
		{
			$index++;

			$tableName = Loc::getMessage(
				'TASKSMOBILE_TEXT_FRAGMENT_PARSER_TABLE_REPLACED_NAME',
				['#INDEX#' => $index]
			);
			$replace = "\n[URL=/?openWeb&type=table&id={$index}]{$tableName}[/URL]\n";


			$tableStart = mb_strpos($text, '[TABLE]');
			$tableEnd = mb_strpos($text, '[/TABLE]');

			if ($tableStart === false || $tableEnd === false)
			{
				break;
			}

			if ($tableStart >= $tableEnd)
			{
				break;
			}

			$text = mb_substr($text, 0, $tableStart) . $replace . mb_substr($text, $tableEnd + mb_strlen('[/TABLE]'));

			if ($index >= 100)
			{
				return $text;
			}
		}

		return $text;
	}

	private function parseVideo(string $text): string
	{
		if (
			preg_match_all("/\[VIDEO.*](?:.|\n)*\[\/VIDEO]/U", $text, $matches)
			&& !empty($matches)
		)
		{
			foreach ($matches[0] as $index => $search)
			{
				$videoName = Loc::getMessage(
					'TASKSMOBILE_TEXT_FRAGMENT_PARSER_VIDEO_REPLACED_NAME',
					['#INDEX#' => $index + 1]
				);
				$replace = "\n\n[URL=/?openWeb&type=video&id={$index}]{$videoName}[/URL]\n";
				$text = str_replace($search, $replace, $text);
			}
		}

		return $text;
	}

	private function parseFiles(string $text): string
	{
		if (
			preg_match_all("/\[DISK FILE ID=(\d+|n\d+)]/", $text, $matches)
			&& !empty($matches)
		)
		{
			if (empty($this->files))
			{
				foreach ($matches[0] as $index => $search)
				{
					$fileName = Loc::getMessage(
						'TASKSMOBILE_TEXT_FRAGMENT_PARSER_FILE_REPLACED_NAME',
						['#INDEX#' => $index + 1]
					);
					$replace = "[URL=/?openFile&fileId={$matches[1][$index]}]{$fileName}[/URL]";
					$text = str_replace($search, $replace, $text);
				}
			}
			else
			{
				foreach ($matches[0] as $index => $search)
				{
					if (array_key_exists($matches[1][$index], $this->files))
					{
						$file = $this->files[$matches[1][$index]];
						$replace = "[URL=/?openFile&fileId={$file['ID']}]{$file['NAME']}[/URL]";
						$text = str_replace($search, $replace, $text);
					}
				}
			}
		}

		return $text;
	}

	private function parseLists(string $text): string
	{
		if (
			preg_match_all("/\[LIST.*]((?:.|\n)*)\[\/LIST]/U", $text, $matches)
			&& !empty($matches)
		)
		{
			foreach ($matches[0] as $index => $search)
			{
				$replace = str_replace('[*]', '', $matches[1][$index]);
				$text = str_replace($search, $replace, $text);
			}
		}

		return $text;
	}

	private function parseSimpleCodes(string $text): string
	{
		$replaceMap = [
			[
				'PATTERN' => "/\[LEFT]((?:.|\n)*?)\[\/LEFT]/",
				'REPLACE' => "\n$1",
			],
			[
				'PATTERN' => "/\[CENTER]((?:.|\n)*?)\[\/CENTER]/",
				'REPLACE' => "\n$1",
			],
			[
				'PATTERN' => "/\[RIGHT]((?:.|\n)*?)\[\/RIGHT]/",
				'REPLACE' => "\n$1",
			],
			[
				'PATTERN' => "/\[JUSTIFY]((?:.|\n)*?)\[\/JUSTIFY]/",
				'REPLACE' => "\n$1",
			],
			[
				'PATTERN' => "/\[CODE]((?:.|\n)*?)\[\/CODE]/",
				'REPLACE' => "\n$1\n",
			],
			[
				'PATTERN' => "/\[SPOILER={0,1}]((?:.|\n)*?)\[\/SPOILER]/",
				'REPLACE' => "\n$1\n",
			],
			[
				'PATTERN' => "/\[P]\[\/P]/",
				'REPLACE' => "",
			],
			[
				'PATTERN' => "/\[P]((?:.|\n)*?)\[\/P]/",
				'REPLACE' => "\n$1\n",
			],
			[
				'PATTERN' => "/<br>/",
				'REPLACE' => "\n",
			],
			[
				'PATTERN' => "/<br\/>/",
				'REPLACE' => "\n",
			],
			[
				'PATTERN' => "/<br \/>/",
				'REPLACE' => "\n",
			],
		];
		foreach ($replaceMap as $replaceData)
		{
			$text = preg_replace($replaceData['PATTERN'], $replaceData['REPLACE'], $text);
			if (!isset($text))
			{
				return '';
			}
		}

		return $text;
	}

	public function getText(): string
	{
		return $this->text;
	}

	public function setText(string $text): void
	{
		$this->text = $text;
	}

	public function getFiles(): array
	{
		return $this->files;
	}

	public function setFiles(array $files): void
	{
		$this->files = [];

		foreach ($files as $file)
		{
			$this->files[$file['ID']] = $file;
			$this->files["n{$file['OBJECT_ID']}"] = $file;
		}
	}
}