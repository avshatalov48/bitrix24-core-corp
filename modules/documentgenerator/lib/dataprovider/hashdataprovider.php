<?php

namespace Bitrix\DocumentGenerator\DataProvider;

use Bitrix\DocumentGenerator\DataProvider;

abstract class HashDataProvider extends DataProvider
{
	public function __construct($source, array $options = [])
	{
		parent::__construct($source, $options);
		$this->data = [];
		if(is_array($source))
		{
			$this->setData($source);
		}
	}

	public function setData(array $data): HashDataProvider
	{
		$this->data = [];
		foreach($this->getFields() as $placeholder => $field)
		{
			if(isset($data[$placeholder]))
			{
				$this->data[$placeholder] = $data[$placeholder];
			}
		}

		return $this;
	}

	public function getData(): array
	{
		return $this->data;
	}
}