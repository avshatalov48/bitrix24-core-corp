<?php

namespace Bitrix\DocumentGenerator\DataProvider;

use Bitrix\DocumentGenerator\DataProvider;

abstract class HashDataProvider extends DataProvider
{
	public function __construct($source, array $options = [])
	{
		parent::__construct($source, $options);
		if(is_array($source))
		{
			$this->data = [];
			foreach($this->getFields() as $placeholder => $field)
			{
				if(isset($source[$placeholder]))
				{
					$this->data[$placeholder] = $source[$placeholder];
				}
			}
		}
	}
}