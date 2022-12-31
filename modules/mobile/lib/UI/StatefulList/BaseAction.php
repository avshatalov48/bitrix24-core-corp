<?php

namespace Bitrix\Mobile\UI\StatefulList;

use Bitrix\Main\ErrorCollection;

class BaseAction extends \Bitrix\Main\Engine\Action
{
	/**
	 * @return array
	 */
	protected function showErrors(): array
	{
		return [
			'errors' => $this->getErrors(),
		];
	}

	/**
	 * @return boolean
	 */
	public function hasErrors(): bool
	{
		if ($this->errorCollection instanceof ErrorCollection)
		{
			return !$this->errorCollection->isEmpty();
		}

		return false;
	}
}
