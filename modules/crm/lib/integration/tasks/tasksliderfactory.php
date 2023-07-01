<?php

namespace Bitrix\Crm\Integration\Tasks;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Slider\Factory\SliderFactory;

class TaskSliderFactory
{
	public static function getFactory(): ?SliderFactory
	{
		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		return new SliderFactory();
	}
}