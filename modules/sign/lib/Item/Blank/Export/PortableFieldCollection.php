<?php

namespace Bitrix\Sign\Item\Blank\Export;

use Bitrix\Sign\Item\Collection;

/**
 * @extends Collection<PortableField>
 */
class PortableFieldCollection extends Collection implements \JsonSerializable
{
	protected function getItemClassName(): string
	{
		return PortableField::class;
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}

	private function isFieldWithIdExists(string $id): bool
	{
		return (bool)$this->findByRule(static fn(PortableField $exist) => $exist->getId() === $id);
	}

	public function addIfNoSameName(PortableField $field): static
	{
		if (!$this->isFieldWithIdExists($field->getId()))
		{
			$this->add($field);
		}

		return $this;
	}
}