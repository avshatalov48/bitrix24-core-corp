<?php

namespace Bitrix\Tasks\Flow\Integration\BizProc\Trigger;

use Bitrix\Tasks\AbstractCommand;

class TriggerCommand extends AbstractCommand
{
	public function __construct(
		public readonly string $name,
		public readonly int    $status,
	)
	{

	}

	public function toArray(bool $withDefault = true): array
	{
		return [
			'NAME' => $this->name,
			'CODE' => 'STATUS',
			'APPLY_RULES' => [
				'STATUS' => $this->status,
				'ALLOW_BACKWARDS' => 'Y',
			],
		];
	}
}