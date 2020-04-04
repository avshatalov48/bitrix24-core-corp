<?php

namespace Bitrix\Voximplant;

use Bitrix\Main\Web\Json;

class Result extends \Bitrix\Main\Result
{
	/**
	 * Converts result to array.
	 * @return array
	 */
	public function toArray()
	{
		$errors = array();
		foreach ($this->getErrors() as $error)
		{
			$errors[] = array(
				'CODE' => $error->getCode(),
				'MESSAGE' => $error->getMessage()
			);
		}
		return array(
			'SUCCESS' => $this->isSuccess() ? 'Y' : 'N',
			'DATA' => $this->getData(),
			'ERRORS' => $errors
		);
	}

	/**
	 * Converts result to JSON.
	 * @return string
	 */
	public function toJson()
	{
		return Json::encode($this->toArray());
	}
}