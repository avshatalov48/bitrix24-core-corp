<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Feature\BaseFeature;
use Bitrix\Main\ArgumentOutOfRangeException;

class Feature
{
	public static function enabled(string $featureClassName): bool
	{
		$featureClassName = self::getFeatureClassName($featureClassName);
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

	public static function enable(string $featureClassName): void
	{
		$featureClassName = self::getFeatureClassName($featureClassName);
		if (class_exists($featureClassName) && is_subclass_of($featureClassName, BaseFeature::class))
		{
			(new $featureClassName)->enable();
		}
	}

	public static function disable(string $featureClassName): void
	{
		$featureClassName = self::getFeatureClassName($featureClassName);
		if (class_exists($featureClassName) && is_subclass_of($featureClassName, BaseFeature::class))
		{
			(new $featureClassName)->disable();
		}
	}

	private static function getFeatureClassName(string $featureClassName): string
	{
		if (mb_strpos($featureClassName, '\\') === false)
		{
			$featureClassName = '\\Bitrix\\Crm\\Feature\\' . $featureClassName;
		}

		return $featureClassName;
	}
}
