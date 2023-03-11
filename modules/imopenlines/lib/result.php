<?php
namespace Bitrix\ImOpenLines;

class Result extends \Bitrix\Main\Result
{
	/**
	 * Sets only the result.
	 * @param $result
	 */
	public function setResult($result)
	{
		$this->data = ['RESULT' => $result];
	}

	/**
	 * To return a single result
	 * @return mixed
	 */
	public function getResult()
	{
		return $this->data['RESULT'] ?? null;
	}
}