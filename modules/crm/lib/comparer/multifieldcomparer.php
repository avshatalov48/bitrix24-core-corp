<?php

namespace Bitrix\Crm\Comparer;

use Bitrix\Crm\Multifield;

final class MultifieldComparer extends ComparerBase
{
	public function getChangedCompatibleArray(Multifield\Collection $previous, Multifield\Collection $current): array
	{
		$previous = clone $previous;
		$current = clone $current;

		foreach ($previous as $previousValue)
		{
			if (!$current->getById($previousValue->getId()))
			{
				// was deleted
				$previousValue->setValue('');
				$current->add($previousValue);
			}
		}

		return $current->toArray();
	}
}
