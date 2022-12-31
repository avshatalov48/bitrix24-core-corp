<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Task\Result;

class Result extends EO_Result
{
	public function toArray(bool $fullInfo = true): array
	{
		$arr = [
			'id' => $this->getId(),
			'taskId' => $this->getTaskId(),
			'commentId' => $this->getCommentId(),
			'createdBy' => $this->getCreatedBy(),
			'createdAt' => $this->getCreatedAt(),
			'updatedAt' => $this->getUpdatedAt(),
			'status' => $this->getStatus(),
		];

		if ($fullInfo)
		{
			$arr['text'] = $this->getText();
			$arr['formattedText'] = $this->getFormattedText();
			$arr['files'] = $this->get(ResultTable::UF_FILE_NAME);
		}

		return $arr;
	}

	/**
	 * @return string
	 */
	public function getFormattedText(bool $isMobile = false): string
	{
		$text = $this->getText();
		if (empty($text))
		{
			return '';
		}

		$parser = new ResultParser();
		if ($isMobile)
		{
			$parser->bMobile = true;
		}

		return $parser
			->setResult($this)
			->convertText($text);
	}
}
