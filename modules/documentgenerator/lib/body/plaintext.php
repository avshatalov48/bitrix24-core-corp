<?php

namespace Bitrix\DocumentGenerator\Body;

use Bitrix\DocumentGenerator\Body;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class PlainText extends Body
{
	public function process(): Result
	{
		$result = new Result();

		if (!$this->isFileProcessable())
		{
			return $result->addError(
				new Error('Cannot process empty content', 'FILE_NOT_PROCESSABLE'),
			);
		}

		$this->content = $this->replacePlaceholders();

		return $result;
	}

	public function getPlaceholders()
	{
		$names = array_unique(self::matchFieldNames($this->content));
		foreach ($names as $key => $name)
		{
			if (
				str_contains($name, Body::BLOCK_START_PLACEHOLDER)
				|| str_contains($name, Body::BLOCK_END_PLACEHOLDER)
			)
			{
				unset($names[$key]);
			}
		}

		return $names;
	}

	public function getFileExtension()
	{
		return 'txt';
	}

	public function getFileMimeType()
	{
		return 'text/plain';
	}

	public function isFileProcessable(): bool
	{
		return is_string($this->content) && !empty($this->content);
	}
}
