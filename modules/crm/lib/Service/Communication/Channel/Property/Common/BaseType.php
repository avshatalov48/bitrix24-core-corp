<?php

namespace Bitrix\Crm\Service\Communication\Channel\Property\Common;

use Bitrix\Crm\Service\Communication\Channel\Property\Property;

abstract class BaseType
{
	public function __construct(protected readonly array $bindings)
	{

	}

	abstract public function getValue(array $params = []): mixed;

	abstract public function getCode(): string;

	abstract public function getTitle(): string;

	abstract public function getType(): string;

	public function createProperty(): Property
	{
		return new Property(
			$this->getCode(),
			$this->getTitle(),
			$this->getType(),
			$this->getPropertyParams(),
		);
	}

	protected function getPropertyParams(): array
	{
		return [
			'isCommon' => true,
		];
	}
}
