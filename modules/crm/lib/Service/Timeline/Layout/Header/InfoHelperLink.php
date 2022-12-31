<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Header;

use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Base;

class InfoHelperLink extends Base
{
	protected string $text;

	protected Action $action;

	public function __construct(string $text, Action $action)
	{
		$this->text = $text;
		$this->action = $action;
	}

	public function toArray(): array
	{
		return [
			'type' => 'link',
			'options' => [
				'text' => $this->text,
				'action' => $this->action,
			]
		];
	}
}
