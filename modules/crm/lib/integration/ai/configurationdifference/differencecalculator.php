<?php

namespace Bitrix\Crm\Integration\AI\ConfigurationDifference;

use Bitrix\Crm\Integration\AI\ConfigurationDifference\Contract;

final class DifferenceCalculator implements Contract\DifferenceCalculator
{
	public function calculate(Contract\ConfigurationProvider $provider): Difference
	{
		$fields = $provider->fields();
		$defaultItems = $provider->default();
		$actualItems = clone $provider->actual();

		$diff = new Difference($defaultItems->count(), $actualItems->count());

		foreach ($defaultItems as $defaultItem)
		{
			$itemId = $defaultItem->id();

			$possibleActualItem = $actualItems->get($itemId);
			$actualItems->unset($itemId);

			if ($possibleActualItem === null)
			{
				$diff->addRemoved($itemId);

				continue;
			}

			if ($this->isUpdated($defaultItem, $possibleActualItem, $fields))
			{
				$diff->addUpdated($itemId);
			}
		}

		$diff->addAdded(...$actualItems->ids());

		return $diff;
	}

	private function isUpdated(DifferenceItem $defaultItem, DifferenceItem $actualItem, array $fields): bool
	{
		foreach ($fields as $field)
		{
			if ($defaultItem->value($field) !== $actualItem->value($field))
			{
				return true;
			}
		}

		return false;
	}
}
