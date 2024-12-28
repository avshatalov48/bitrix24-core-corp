<?php

namespace Bitrix\BIConnector\ExternalSource\Viewer\Provider;

final class Row
{
	protected array $data = [];

	public function add(mixed $value): void
	{
		$this->data[] = $value;
	}

	public function getData(): array
	{
		return $this->data;
	}
}
