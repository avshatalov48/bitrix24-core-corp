<?php

namespace Bitrix\Tasks\Integration\CRM\Fields;

use ArrayIterator;
use Bitrix\Main\Type\Contract\Arrayable;
use CCrmOwnerType;
use IteratorAggregate;

class Collection implements IteratorAggregate, Arrayable
{
	/** @var Crm[]  */
	private array $fields;

	public static function createFromArray(array $xmlIds): static
	{
		return (new Mapper())->map($xmlIds);
	}

	public function __construct(Crm ...$fields)
	{
		$this->fields = $fields;
	}

	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->fields);
	}

	public function filter(): static
	{
		foreach ($this->fields as $key => $crm)
		{
			if ($crm->getId() <= 0 || $crm->getTypeId() === CCrmOwnerType::Undefined)
			{
				unset($this->fields[$key]);
			}
		}

		return $this;
	}

	public function toArray(): array
	{
		return array_map(static fn (Crm $crm): string => $crm->getXmlId(), $this->fields);
	}

	public function count(): int
	{
		return count($this->fields);
	}
}
