<?php
namespace Bitrix\Timeman\Model\Schedule\Shift;

class ShiftCollection extends EO_Shift_Collection
{
	/**
	 * @param Shift|ShiftCollection|null $shiftsParam
	 * @return ShiftCollection
	 */
	public static function buildShiftCollection($shiftsParam)
	{
		if ($shiftsParam instanceof ShiftCollection)
		{
			return $shiftsParam;
		}

		$shifts = new ShiftCollection();
		if ($shiftsParam instanceof Shift)
		{
			$shifts->add($shiftsParam);
		}
		return $shifts;
	}
}