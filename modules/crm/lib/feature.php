<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Feature\BaseFeature;
use Bitrix\Main\ArgumentOutOfRangeException;

class Feature
{
	public static function enabled(string $featureClassName): bool
	{
		if (mb_strpos($featureClassName, '\\') === false)
		{
			$featureClassName = '\\Bitrix\\Crm\\Feature\\' . $featureClassName;
		}
		if (!class_exists($featureClassName))
		{
			return true; // feature is already enabled for everybody
		}

		if (!is_subclass_of($featureClassName, BaseFeature::class))
		{
			throw new ArgumentOutOfRangeException('featureClassName');
		}

		return (new $featureClassName)->isEnabled();
	}
}
