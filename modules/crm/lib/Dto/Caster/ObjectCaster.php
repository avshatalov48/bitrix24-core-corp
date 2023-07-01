<?php

namespace Bitrix\Crm\Dto\Caster;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Caster;
use Bitrix\Main\ArgumentException;

final class ObjectCaster extends Caster
{
	private string $type;

	public function __construct(string $type)
	{
		$isDto = class_exists($type) && is_subclass_of($type, Dto::class);

		if (!$isDto)
		{
			throw new ArgumentException('Nested structures must extend Dto. '.$type);
		}

		$this->type = $type;
	}

	protected function castSingleValue($value)
	{
		$className = $this->type;

		return new $className(is_array($value) ? $value : null);
	}
}
