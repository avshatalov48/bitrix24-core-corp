<?php
namespace Bitrix\Tasks\Scrum\Internal\Fields;

use Bitrix\Main\ORM\Fields\ArrayField;

class InfoField extends ArrayField
{
	public function decode($value)
	{
		$callback = $this->decodeFunction;
		return $callback($value);
	}
}
