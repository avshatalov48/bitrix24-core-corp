<?php

namespace Bitrix\Tasks\Internals\Task;

class RegularParametersObject extends EO_RegularParameters
{
	public static function createFromParams(array $regularParams): static
	{
		return (new static())
			->setRegularParameters($regularParams);
	}
}