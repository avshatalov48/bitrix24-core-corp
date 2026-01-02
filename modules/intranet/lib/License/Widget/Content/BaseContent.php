<?php

namespace Bitrix\Intranet\License\Widget\Content;

abstract class BaseContent implements \JsonSerializable
{
	abstract public function getName(): string;

	abstract public function getConfiguration(): array;

	public function jsonSerialize(): array
	{
		return $this->getConfiguration();
	}
}
