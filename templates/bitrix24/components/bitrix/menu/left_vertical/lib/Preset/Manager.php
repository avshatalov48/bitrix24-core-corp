<?php
namespace Bitrix\Intranet\LeftMenu\Preset;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
use \Bitrix\Main;

class Manager
{
	protected static $presets;

	public static function getAllPresets(): array
	{
		if (is_array(self::$presets))
		{
			return self::$presets;
		}

		$thisFile = new Main\IO\File(__FILE__);
		$thisDir = $thisFile->getDirectory();
		foreach ($thisDir->getChildren() as $child)
		{
			if (!($child instanceof Main\IO\File))
			{
				continue;
			}
			/*@var Main\IO\File $child */
			$name = 'Bitrix\Intranet\LeftMenu\Preset\\'.str_replace('.'.$child->getExtension(), '', $child->getName());
			if (!( interface_exists($name) || class_exists($name)))
			{
				$res = include_once $child->getPhysicalPath();
			}
		}
		$classes = array_slice(get_declared_classes(), 0 - sizeof($thisDir->getChildren()));
		$result = [];
		foreach ($classes as $class)
		{
			try
			{
				$res = new \ReflectionClass($class);
				if ($res->implementsInterface(PresetInterface::class)
					&& !$res->isAbstract()
					&& $class::isAvailable()
				)
				{
					$preset = $res->newInstance();
					$result[$preset->getCode()] = $preset;
				}
			}
			catch(\ReflectionException $exception)
			{
			}
		}
		self::$presets = $result;
		return self::$presets;
	}

	protected static function getCurrentPresetId(): string
	{
		//personal
		$presetId = \CUserOptions::GetOption('intranet', 'left_menu_preset_'.SITE_ID);
		//global
		if (!($presetId && array_key_exists($presetId, self::getAllPresets())))
		{
			$presetId = \COption::GetOptionString('intranet', 'left_menu_preset', '');
		}
		return is_string($presetId) ? $presetId : '';
	}

	public static function getPreset($presetId = null): PresetInterface
	{
		$presetId = $presetId ?: self::getCurrentPresetId();

		$presets = self::getAllPresets();
		return $presetId && array_key_exists($presetId, $presets) ? $presets[$presetId] : $presets['social'];
	}
}