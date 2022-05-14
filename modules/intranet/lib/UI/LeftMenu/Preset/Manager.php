<?php
namespace Bitrix\Intranet\UI\LeftMenu\Preset;

use \Bitrix\Main;
use \Bitrix\Intranet\UI\LeftMenu;

class Manager
{
	protected static $presets = [];

	public static function getAllPresets(string $siteId): array
	{
		if (isset(self::$presets[$siteId]))
		{
			return self::$presets[$siteId];
		}

		$thisFile = new Main\IO\File(__FILE__);
		$thisDir = $thisFile->getDirectory();
		foreach ($thisDir->getChildren() as $child)
		{
			if (!($child instanceof Main\IO\File) || $child->getExtension() !== 'php')
			{
				continue;
			}
			/*@var Main\IO\File $child */
			$name = 'Bitrix\Intranet\UI\LeftMenu\Preset\\'.str_replace('.'.$child->getExtension(), '', $child->getName());
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
					$preset = $res->newInstance($siteId);
					$result[$preset->getCode()] = $preset;
				}
			}
			catch(\ReflectionException $exception)
			{
			}
		}
		self::$presets[$siteId] = $result;
		return self::$presets[$siteId];
	}

	protected static function getCurrentPresetId(string $siteId): string
	{
		//personal
		$presetId = \CUserOptions::GetOption('intranet', 'left_menu_preset_'.$siteId);
		//global
		if (!($presetId && array_key_exists($presetId, self::getAllPresets($siteId))))
		{
			$presetId = \COption::GetOptionString('intranet', 'left_menu_preset', '', $siteId);
		}
		return is_string($presetId) ? $presetId : '';
	}

	public static function getPreset($presetId = null, $siteId = null): PresetInterface
	{
		$siteId = (!is_null($siteId) ? $siteId :
			(defined('SITE_ID') ? SITE_ID : LeftMenu\Menu::getDefaultSiteId()));
		$presetId = $presetId ?: self::getCurrentPresetId($siteId);

		$presets = self::getAllPresets($siteId);
		return $presetId && array_key_exists($presetId, $presets) ? $presets[$presetId] : $presets['social'];
	}
}