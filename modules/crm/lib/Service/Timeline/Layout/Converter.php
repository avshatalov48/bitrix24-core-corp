<?php

namespace Bitrix\Crm\Service\Timeline\Layout;

use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Web\Uri;

class Converter
{
	protected Layout $layout;

	public function __construct(Layout $layout)
	{
		$this->layout = $layout;
	}

	public function toArray(): array
	{
		return (array)$this->doConvert($this->layout->toArray());
	}

	protected function doConvert($value)
	{
		if (is_array($value))
		{
			foreach ($value as $key => $item)
			{
				$value[$key] = $this->doConvert($item);
				if (is_null($value[$key]))
				{
					unset($value[$key]);
				}
			}
			if (empty($value))
			{
				$value = null;
			}
		}
		if ($value instanceof Layout\Base)
		{
			return $this->doConvert($value->toArray());
		}
		if ($value instanceof Date)
		{
			return $value->getTimestamp();
		}
		if ($value instanceof Uri)
		{
			return (string)$value;
		}
		if (is_object($value))
		{
			throw new NotSupportedException(
				'Class '
				. get_class($value)
				. ' must inherit \Bitrix\Crm\Service\Timeline\Layout\Base'
			);
		}

		return $value;
	}
}
