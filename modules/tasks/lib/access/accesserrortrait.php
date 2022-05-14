<?php

namespace Bitrix\Tasks\Access;

trait AccessErrorTrait
{
	protected $errorCollection = [];

	/**
	 * @return array
	 */
	public function getErrors(): array
	{
		return $this->errorCollection;
	}

	/**
	 * @param string $message
	 * @return bool
	 */
	public function addError(string $class, string $message = ''): void
	{
		$this->errorCollection[] = $class .': '. $message;
	}
}