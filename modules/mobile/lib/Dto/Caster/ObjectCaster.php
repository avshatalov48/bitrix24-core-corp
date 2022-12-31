<?php

namespace Bitrix\Mobile\Dto\Caster;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\InvalidDtoException;

final class ObjectCaster extends Caster
{
	private string $type;

	public function __construct(string $type, bool $isCollection = false)
	{
		$isDto = class_exists($type) && is_subclass_of($type, Dto::class);

		if (!$isDto)
		{
			throw new InvalidDtoException('Nested structures must extend Dto');
		}

		parent::__construct($isCollection);

		$this->type = $type;
	}

	protected function castSingleValue($value)
	{
		$className = $this->type;

		return new $className($value);
	}
}
