<?php

namespace Bitrix\Crm\Service\Timeline\Item\Compatible;

class Model extends \Bitrix\Crm\Service\Timeline\Item\Model
{
	private array $data = [];

	public function setData(array $data): self
	{
		$this->data = $data;

		return $this;
	}

	public function getData(): array
	{
		return $this->data;
	}
}
