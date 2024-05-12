<?php

namespace Bitrix\Mobile\Dto\Caster;

use Bitrix\Mobile\Dto\InvalidDtoException;

final class ScalarCaster extends Caster
{
	public function __construct(
		protected string $type,
	)
	{
		parent::__construct();
	}

	protected function castSingleValue($value)
	{
		if (!settype($value, $this->type))
		{
			throw new InvalidDtoException('Failed to cast' . $value . 'into' . $this->type);
		}

		return $value;
	}
}