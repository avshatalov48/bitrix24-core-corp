<?php

namespace Bitrix\Intranet\Settings\Controls;

use Bitrix\Intranet\Settings\SettingsPermission;

abstract class Control implements \JsonSerializable
{

	public function __construct(
		private string $id
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function setId(string $id): void
	{
		$this->id = $id;
	}
}
