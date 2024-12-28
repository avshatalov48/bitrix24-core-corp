<?php

namespace Bitrix\Tasks\Flow\Efficiency;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

trait EfficiencyTrait
{
	protected array $totals;
	protected array $violations;
	protected array $efficiencies;

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected function countEfficiencies(): void
	{
		$this->countTotals();
		$this->countViolations();

		foreach ($this->totals as $entityId => $total)
		{
			if ($total === 0)
			{
				$value = $this->getDefaultEfficiency();
			}
			else
			{
				$value = (int)round(100 * (1 - $this->violations[$entityId] / $total));
			}

			if ($value < 0)
			{
				$value = 0;
			}

			$this->efficiencies[$entityId] = $value;
		}
	}

	protected function getDefaultEfficiency(): int
	{
		return 100;
	}

	abstract protected function countTotals(): void;

	abstract protected function countViolations(): void;
}
