<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Header;

use Bitrix\Crm\Service\Timeline\Layout\Base;

class InfoHelperText extends Base
{
	protected string $text;

	public function __construct(string $text)
	{
		$this->text = $text;
	}

	public function toArray(): array
	{
		return [
			'type' => 'text',
			'options' => [
				'text' => $this->text,
			]
		];
	}
}
