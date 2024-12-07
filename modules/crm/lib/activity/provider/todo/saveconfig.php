<?php

namespace Bitrix\Crm\Activity\Provider\ToDo;

final class SaveConfig
{
	private bool $needSave;

	public function __construct(bool $needSave = false)
	{
		$this->needSave = $needSave;
	}

	public function isNeedSave(): bool
	{
		return $this->needSave;
	}
}
