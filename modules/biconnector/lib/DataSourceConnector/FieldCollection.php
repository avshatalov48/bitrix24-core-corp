<?php

namespace Bitrix\BIConnector\DataSourceConnector;

use Bitrix\BIConnector\Integration\Superset\Integrator\Dto\Collection;

final class FieldCollection extends Collection
{
	public function offsetSet(mixed $offset, mixed $value): void
	{
		if ($value instanceof FieldDto)
		{
			parent::offsetSet(null, $value);
		}
	}

	public function toArray(): array
	{
		$result = [];
		/** @var FieldDto $field */
		foreach ($this->getIterator() as $field)
		{
			$result[] = $field->toArray();
		}

		return $result;
	}
}
