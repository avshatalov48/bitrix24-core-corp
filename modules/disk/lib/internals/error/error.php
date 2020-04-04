<?php

namespace Bitrix\Disk\Internals\Error;

use Bitrix\Main;

class Error extends Main\Error
{
	/**
	 * @return mixed
	 */
	public function getData()
	{
		return $this->getCustomData();
	}
}