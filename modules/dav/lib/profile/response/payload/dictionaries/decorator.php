<?php

namespace Bitrix\Dav\Profile\Response\Payload\Dictionaries;

/**
 * Class Decorator
 * @package Bitrix\Dav\Profile\Response\Payload\Dictionaries
 */
class Decorator extends DecoratorBase
{
	const TEMPLATE_FILE_NAME = '';
	protected $params;

	/**
	 * @return string
	 */
	public function prepareBodyContent()
	{
		$result = '';
		if ($this->isAvailable())
		{
			foreach ($this->dictionaries as $dictionary)
			{
				$dictionary->setUser($this->getUser());
				if ($dictionary->isAvailable())

					$result .= $dictionary->prepareBodyContent();
			}
		}
		return $result ?: null;
	}
}